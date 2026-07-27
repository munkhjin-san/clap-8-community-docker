<?php

namespace App\Services;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\CostItem;
use App\Models\CostItemRate;
use App\Models\User;
use App\Models\Worksite;
use App\Models\WorksiteRent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KintoneCostMasterSyncService
{
    public const APP_PEOPLE_COSTS = 96;
    public const APP_EMPLOYMENT_CONTRACTS = 777;
    public const APP_RENT_COSTS = 236;

    private const SOURCE_SYSTEM = 'kintone';
    private const WORKSITE_RENT_SOURCE_SEGMENTS = [
        WorksiteRent::SEGMENT_RENT_LEGACY,
        WorksiteRent::SEGMENT_OFFICE,
        WorksiteRent::SEGMENT_PARKING,
    ];

    /**
     * @var array<string, User|null>
     */
    private array $userByCode = [];

    public function __construct(private KintoneClient $kintone)
    {
    }

    /**
     * @param array<int, int|string> $apps
     * @return array<string, mixed>
     */
    public function sync(array $apps = [self::APP_PEOPLE_COSTS, self::APP_EMPLOYMENT_CONTRACTS, self::APP_RENT_COSTS]): array
    {
        $requestedApps = array_values(array_unique(array_map('intval', $apps)));
        $results = [];

        foreach ($requestedApps as $appId) {
            $results[(string) $appId] = match ($appId) {
                self::APP_PEOPLE_COSTS => $this->syncApp(
                    $appId,
                    [
                        '$id',
                        '更新日時',
                        '月合計',
                        '日当_0',
                        '時給_0',
                        '所定労働日数当月',
                        '所定労働日数月平均',
                        '所定労働時間当月',
                        '所定労働時間月平均',
                        '収支用',
                        '社員コード',
                        '氏名',
                        '計算基準日',
                        '退職フラグ',
                    ],
                    fn (array $record) => $this->normalizePeopleCostRecord($record)
                ),
                self::APP_EMPLOYMENT_CONTRACTS => $this->syncApp(
                    $appId,
                    [
                        '$id',
                        '更新日時',
                        'ラジオボタン',
                        '社員ｺｰﾄﾞ',
                        '従業員番号',
                        '従業員識別子',
                        '氏名',
                        '適用開始日',
                        '契約満了日',
                        '月給',
                        '時給_0',
                        '日給_0',
                        '職務手当',
                        '固定残業手当',
                        '住宅手当_0',
                        '地域手当_0',
                        '通勤手当',
                        '待機手当',
                        '補正手当',
                    ],
                    fn (array $record) => $this->normalizeEmploymentContractRecord($record)
                ),
                self::APP_RENT_COSTS => $this->syncRentApp($appId),
                default => throw new InvalidArgumentException("Unsupported Kintone cost master app: {$appId}"),
            };
        }

        return [
            'synced_at' => now()->toIso8601String(),
            'apps' => $results,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function normalizePeopleCostRecord(array $record): ?array
    {
        $recordId = $this->stringValue($record, '$id');
        $employeeCode = $this->stringValue($record, '社員コード');
        $profitLookupKey = $this->stringValue($record, '収支用');
        $personKey = $employeeCode ?: $profitLookupKey;

        if ($personKey === '') {
            return null;
        }

        $name = $this->stringValue($record, '氏名') ?: $profitLookupKey ?: $employeeCode;
        $monthlyTotal = $this->numberValue($record, '月合計');
        $dailyProfitRate = $this->numberValue($record, '日当_0');
        $hourlyRate = $this->numberValue($record, '時給_0');
        $quantityHint = $this->numberValue($record, '所定労働日数当月')
            ?: $this->numberValue($record, '所定労働日数月平均');
        $hoursHint = $this->numberValue($record, '所定労働時間当月')
            ?: $this->numberValue($record, '所定労働時間月平均');
        $usesDailyRate = $dailyProfitRate > 0;
        // 時給 members (no 日当_損益用, no 月合計) bill by the hour. Everyone else keeps
        // the existing 日当(day)/月合計(month) classification untouched.
        $usesHourlyRate = ! $usesDailyRate && $hourlyRate > 0 && $monthlyTotal <= 0;
        $unit = match (true) {
            $usesDailyRate => 'day',
            $usesHourlyRate => 'hour',
            default => 'month',
        };
        $sourceRate = match ($unit) {
            'day' => $dailyProfitRate,
            'hour' => $hourlyRate,
            default => $monthlyTotal,
        };
        $rate = floor($sourceRate);
        $hoursHintValue = $usesHourlyRate
            ? ($hoursHint > 0 ? $hoursHint : (float) config('profitplan.standard_monthly_hours', 160))
            : null;
        $effectiveFrom = $this->dateValue($record, '計算基準日')
            ?? $this->dateValue($record, '更新日時')
            ?? now()->toDateString();
        $updatedAt = $this->dateTimeValue($record, '更新日時');
        $retired = $this->isRetiredFlag($this->stringValue($record, '退職フラグ'));
        $sourceKey = "member:{$personKey}";
        $hashSource = [
            'app' => self::APP_PEOPLE_COSTS,
            'record_id' => $recordId,
            'rate' => $rate,
            'unit' => $unit,
            'monthly_total' => $monthlyTotal,
            'quantity_hint' => $quantityHint,
            'effective_from' => $effectiveFrom,
        ];
        if ($hoursHintValue !== null) {
            $hashSource['hours_hint'] = $hoursHintValue;
        }
        $rateLabel = match ($unit) {
            'day' => 'Kintone 96 日当_損益用',
            'hour' => 'Kintone 96 時給',
            default => 'Kintone 96 月合計',
        };
        $memoParts = array_filter([
            $profitLookupKey ? "収支用: {$profitLookupKey}" : null,
            $monthlyTotal > 0 ? 'monthly_total=' . $this->formatSourceNumber($monthlyTotal) : null,
            $quantityHint > 0 ? 'quantity_hint=' . $this->formatSourceNumber($quantityHint) : null,
            $hoursHintValue !== null ? 'hours_hint=' . $this->formatSourceNumber($hoursHintValue) : null,
        ]);
        $rateMemoParts = array_filter([
            $rateLabel,
            $monthlyTotal > 0 ? 'monthly_total=' . $this->formatSourceNumber($monthlyTotal) : null,
            $quantityHint > 0 ? 'quantity_hint=' . $this->formatSourceNumber($quantityHint) : null,
            $hoursHintValue !== null ? 'hours_hint=' . $this->formatSourceNumber($hoursHintValue) : null,
        ]);

        return [
            'item' => [
                'type' => 'member',
                'name' => $name,
                'source_type' => 'manual',
                'source_id' => $personKey,
                'source_label' => "Kintone 96 / {$name}",
                'source_system' => self::SOURCE_SYSTEM,
                'source_app_id' => self::APP_PEOPLE_COSTS,
                'source_record_id' => $recordId,
                'source_key' => $sourceKey,
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
                'account_category' => 'people',
                'unit' => $unit,
                'default_rate' => $rate,
                'active' => ! $retired,
                'memo' => $memoParts ? implode('; ', $memoParts) : null,
            ],
            'rate' => [
                'rate' => $rate,
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'memo' => implode('; ', $rateMemoParts),
                'source_system' => self::SOURCE_SYSTEM,
                'source_app_id' => self::APP_PEOPLE_COSTS,
                'source_record_id' => $recordId,
                'source_hash' => $this->sourceHash($hashSource),
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function normalizeEmploymentContractRecord(array $record): ?array
    {
        $recordId = $this->stringValue($record, '$id');
        $latestFlag = $this->stringValue($record, 'ラジオボタン');
        $effectiveFrom = $this->dateValue($record, '適用開始日')
            ?? $this->dateValue($record, '更新日時')
            ?? now()->toDateString();
        $effectiveTo = $this->dateValue($record, '契約満了日');
        $futureEffective = Carbon::parse($effectiveFrom)->gte(Carbon::today());

        if ($latestFlag !== '現在' && $latestFlag !== '未来' && ! $futureEffective) {
            return null;
        }

        $employeeCode = $this->stringValue($record, '社員ｺｰﾄﾞ')
            ?: $this->stringValue($record, '従業員番号')
            ?: $this->stringValue($record, '従業員識別子');
        $name = $this->stringValue($record, '氏名') ?: $employeeCode;

        if ($employeeCode === '') {
            return null;
        }

        $monthlyBase = $this->numberValue($record, '月給');
        if ($monthlyBase <= 0) {
            $monthlyBase = $this->numberValue($record, '時給_0') * 172;
        }
        if ($monthlyBase <= 0) {
            $monthlyBase = $this->numberValue($record, '日給_0') * 21.5;
        }

        $allowances = [
            '職務手当',
            '固定残業手当',
            '住宅手当_0',
            '地域手当_0',
            '通勤手当',
            '待機手当',
            '補正手当',
        ];
        $allowanceTotal = array_reduce(
            $allowances,
            fn (float $total, string $field) => $total + $this->numberValue($record, $field),
            0.0
        );
        $rate = $monthlyBase + $allowanceTotal;
        $updatedAt = $this->dateTimeValue($record, '更新日時');
        $sourceKey = "member:{$employeeCode}";
        $ended = $effectiveTo !== null && Carbon::parse($effectiveTo)->lt(Carbon::today());
        $hashSource = [
            'app' => self::APP_EMPLOYMENT_CONTRACTS,
            'record_id' => $recordId,
            'base' => $monthlyBase,
            'allowances' => $allowanceTotal,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
        ];

        return [
            'item' => [
                'type' => 'member',
                'name' => $name,
                'source_type' => 'manual',
                'source_id' => $employeeCode,
                'source_label' => "Kintone 777 / {$name}",
                'source_system' => self::SOURCE_SYSTEM,
                'source_app_id' => self::APP_EMPLOYMENT_CONTRACTS,
                'source_record_id' => $recordId,
                'source_key' => $sourceKey,
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
                'account_category' => 'people',
                'unit' => 'month',
                'default_rate' => $rate,
                'active' => ! $ended,
                'memo' => "最新版フラグ: {$latestFlag}",
            ],
            'rate' => [
                'rate' => $rate,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'memo' => 'Kintone 777 給与 + 手当',
                'source_system' => self::SOURCE_SYSTEM,
                'source_app_id' => self::APP_EMPLOYMENT_CONTRACTS,
                'source_record_id' => $recordId,
                'source_hash' => $this->sourceHash($hashSource),
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function normalizeRentCostRecord(array $record): ?array
    {
        $recordId = $this->stringValue($record, '$id');
        $officeName = $this->firstStringValue($record, ['ルックアップ', '部門']);
        $propertyName = $this->firstStringValue($record, ['文字列__1行__1', '文字列__1行__0']);
        $note = $this->stringValue($record, 'NOTE');
        $name = $propertyName ?: ($officeName ?: ($note ?: "地代家賃 #{$recordId}"));
        $totalAmount = $this->numberValue($record, '合計金額');
        if ($totalAmount <= 0) {
            $totalAmount = $this->sumNumberValues($record, [
                '家賃',
                '光熱費',
                '共益費',
                'その他費用',
                '駐車場',
                'ネット費用',
            ]);
        }
        $perPerson = $this->numberValue($record, '計算');
        $rate = $perPerson > 0 ? $perPerson : $totalAmount;
        $effectiveFrom = $this->dateValue($record, '日付')
            ?? $this->dateValue($record, '更新日時')
            ?? now()->toDateString();
        $effectiveTo = $this->dateValue($record, '日付_0');
        $updatedAt = $this->dateTimeValue($record, '更新日時');
        $sourceKey = "office-rent:{$recordId}";
        $ended = $effectiveTo !== null && Carbon::parse($effectiveTo)->lt(Carbon::today());
        $memoParts = array_filter([
            $officeName ? "営業所: {$officeName}" : null,
            $note ? "備考: {$note}" : null,
            $perPerson > 0 ? '一人当たり=' . $this->formatSourceNumber($perPerson) : null,
            $totalAmount > 0 ? '合計金額=' . $this->formatSourceNumber($totalAmount) : null,
        ]);
        $hashSource = [
            'app' => self::APP_RENT_COSTS,
            'record_id' => $recordId,
            'rate' => $rate,
            'per_person' => $perPerson,
            'total_amount' => $totalAmount,
            'note' => $note,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
        ];

        return [
            'item' => [
                'type' => 'rent',
                'name' => $name,
                'source_type' => 'manual',
                'source_id' => $recordId,
                'source_label' => "Kintone 236 / {$name}",
                'source_system' => self::SOURCE_SYSTEM,
                'source_app_id' => self::APP_RENT_COSTS,
                'source_record_id' => $recordId,
                'source_key' => $sourceKey,
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
                'account_category' => 'office',
                'unit' => 'month',
                'default_rate' => $rate,
                'active' => ! $ended,
                'memo' => $memoParts ? implode('; ', $memoParts) : null,
            ],
            'rate' => [
                'rate' => $rate,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'memo' => $memoParts ? implode('; ', $memoParts) : 'Kintone 236',
                'source_system' => self::SOURCE_SYSTEM,
                'source_app_id' => self::APP_RENT_COSTS,
                'source_record_id' => $recordId,
                'source_hash' => $this->sourceHash($hashSource),
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
            ],
        ];
    }

    /**
     * Worksite view of an app-236 record: identity keyed on 営業所No (stable across
     * record churn), per-person rent (一人当たり) per 区分 segment.
     *
     * @return array{worksite: array<string, mixed>, rent: array<string, mixed>|null}|null
     */
    public function normalizeWorksiteRecord(array $record): ?array
    {
        $recordId = $this->stringValue($record, '$id');
        if ($recordId === '') {
            return null;
        }

        $rawSegment = $this->stringValue($record, 'ラジオボタン') ?: 'その他';
        $segment = $this->normalizeWorksiteRentSegment($rawSegment);
        $officeNo = $this->numberValue($record, '営業所No');
        $officeName = $this->firstStringValue($record, ['ルックアップ', '部門']);
        $usersNote = $this->stringValue($record, '文字列__1行__3');
        $name = $this->firstStringValue($record, ['文字列__1行__7', '文字列__1行__1', '文字列__1行__0', 'ルックアップ', '部門'])
            ?: "拠点 #{$recordId}";
        if (
            $officeNo <= 0
            && $rawSegment === WorksiteRent::SEGMENT_PARKING
            && $usersNote !== ''
            && ! str_starts_with($name, '拠点 #')
            && ! str_contains($name, $usersNote)
        ) {
            $name = "{$name} / {$usersNote}";
        }
        $sourceKey = $officeNo > 0 ? 'worksite:' . (int) $officeNo : "worksite:rec{$recordId}";

        $totalAmount = $this->numberValue($record, '合計金額');
        if ($totalAmount <= 0) {
            $totalAmount = $this->sumNumberValues($record, [
                '家賃',
                '光熱費',
                '共益費',
                'その他費用',
                '駐車場',
                'ネット費用',
            ]);
        }
        $headcount = $this->numberValue($record, '人数');
        $perPerson = $this->numberValue($record, '計算');
        if ($perPerson <= 0 && $totalAmount > 0 && $headcount > 0) {
            $perPerson = round($totalAmount / $headcount, 2);
        }

        $effectiveFrom = $this->dateValue($record, '日付')
            ?? $this->dateValue($record, '更新日時')
            ?? now()->toDateString();
        $effectiveTo = $this->dateValue($record, '日付_0');
        $updatedAt = $this->dateTimeValue($record, '更新日時');
        $ended = $effectiveTo !== null && Carbon::parse($effectiveTo)->lt(Carbon::today());

        $rent = null;
        if (($perPerson > 0 || $totalAmount > 0) && $this->isWorksiteRentSourceSegment($rawSegment)) {
            $rent = [
                'segment' => $segment,
                'per_person_amount' => $perPerson,
                'total_amount' => $totalAmount > 0 ? $totalAmount : null,
                'headcount' => $headcount > 0 ? $headcount : null,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'active' => ! $ended,
                'source_record_id' => $recordId,
                'source_hash' => $this->sourceHash([
                    'app' => self::APP_RENT_COSTS,
                    'worksite' => $sourceKey,
                    'segment' => $segment,
                    'per_person' => $perPerson,
                    'total_amount' => $totalAmount,
                    'headcount' => $headcount,
                    'effective_from' => $effectiveFrom,
                    'effective_to' => $effectiveTo,
                ]),
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
            ];
        }

        return [
            'worksite' => [
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => $sourceKey,
                'office_no' => $officeNo > 0 ? (int) $officeNo : null,
                'name' => $name,
                'office_name' => $officeName ?: null,
                'users_note' => $usersNote ?: null,
                'active' => true,
                'source_record_id' => $recordId,
                'source_updated_at' => $updatedAt,
                'source_synced_at' => now(),
            ],
            'rent' => $rent,
        ];
    }

    /**
     * @param array<int, string> $fields
     * @param callable(array<string, mixed>): ?array<string, mixed> $normalizer
     * @return array<string, int>
     */
    private function syncApp(int $appId, array $fields, callable $normalizer): array
    {
        $records = $this->kintone->getAllRecords($appId, '', $fields);
        $stats = [
            'fetched' => count($records),
            'mapped' => 0,
            'skipped' => 0,
            'created' => 0,
            'updated' => 0,
            'rates_created' => 0,
            'rates_updated' => 0,
            'rates_skipped' => 0,
        ];

        foreach ($records as $record) {
            $normalized = $normalizer($record);

            if ($normalized === null) {
                $stats['skipped']++;
                continue;
            }

            if ($this->shouldSkipPeopleCostWithoutActiveLocalUser($normalized)) {
                $stats['skipped']++;
                continue;
            }

            $stats['mapped']++;
            $result = $this->upsertCost($normalized);
            $stats[$result['item']]++;
            $stats[$result['rate']]++;
        }

        return $stats;
    }

    /**
     * App 236 feeds two targets from one fetch: rent cost_items (existing, unchanged)
     * and the worksites/worksite_rents tables used by ProfitPlan v2.
     *
     * @return array<string, int>
     */
    private function syncRentApp(int $appId): array
    {
        $records = $this->kintone->getAllRecords($appId, '', [
            '$id',
            '更新日時',
            '部門',
            'ルックアップ',
            '文字列__1行__1',
            '文字列__1行__0',
            '文字列__1行__3',
            '文字列__1行__7',
            'NOTE',
            'ラジオボタン',
            '営業所No',
            '家賃',
            '光熱費',
            '共益費',
            'その他費用',
            '駐車場',
            'ネット費用',
            '倉庫代',
            '人数',
            '合計金額',
            '計算',
            '日付',
            '日付_0',
        ]);

        $stats = [
            'fetched' => count($records),
            'mapped' => 0,
            'skipped' => 0,
            'created' => 0,
            'updated' => 0,
            'rates_created' => 0,
            'rates_updated' => 0,
            'rates_skipped' => 0,
            'worksites_created' => 0,
            'worksites_updated' => 0,
            'worksite_rents_created' => 0,
            'worksite_rents_updated' => 0,
            'worksite_rents_skipped' => 0,
            'worksites_deactivated' => 0,
        ];
        $seenWorksiteKeys = [];
        $seenRentKeys = [];

        foreach ($records as $record) {
            $normalized = $this->normalizeRentCostRecord($record);

            if ($normalized === null) {
                $stats['skipped']++;
            } else {
                $stats['mapped']++;
                $seenRentKeys[] = (string) $normalized['item']['source_key'];
                $result = $this->upsertCost($normalized);
                $stats[$result['item']]++;
                $stats[$result['rate']]++;
            }

            $worksite = $this->normalizeWorksiteRecord($record);
            if ($worksite !== null) {
                $seenWorksiteKeys[] = $worksite['worksite']['source_key'];
                $worksiteResult = $this->upsertWorksite($worksite);
                $stats[$worksiteResult['worksite']]++;
                if ($worksiteResult['rent'] !== null) {
                    $stats[$worksiteResult['rent']]++;
                }
            }
        }

        // Deactivate worksites/rent items whose kintone records disappeared.
        // Guarded against an empty fetch so an API hiccup can't deactivate everything.
        if ($records !== [] && $seenWorksiteKeys !== []) {
            $stats['worksites_deactivated'] = Worksite::query()
                ->where('source_system', self::SOURCE_SYSTEM)
                ->whereNotIn('source_key', array_unique($seenWorksiteKeys))
                ->where('active', true)
                ->update([
                    'active' => false,
                    'source_synced_at' => now(),
                ]);
        }

        if ($records !== [] && $seenRentKeys !== []) {
            $stats['rent_items_deactivated'] = CostItem::query()
                ->where('source_system', self::SOURCE_SYSTEM)
                ->where('source_app_id', self::APP_RENT_COSTS)
                ->where('source_key', 'like', 'office-rent:%')
                ->whereNotIn('source_key', array_unique($seenRentKeys))
                ->where('active', true)
                ->update([
                    'active' => false,
                    'source_synced_at' => now(),
                ]);
        }

        return $stats;
    }

    /**
     * @param array{item: array<string, mixed>, rate: array<string, mixed>} $normalized
     */
    private function shouldSkipPeopleCostWithoutActiveLocalUser(array $normalized): bool
    {
        $itemFields = $normalized['item'];
        if (
            ($itemFields['source_system'] ?? null) !== self::SOURCE_SYSTEM
            || ($itemFields['account_category'] ?? null) !== 'people'
            || ! in_array((int) ($itemFields['source_app_id'] ?? 0), [self::APP_PEOPLE_COSTS, self::APP_EMPLOYMENT_CONTRACTS], true)
        ) {
            return false;
        }

        $employeeCode = (string) ($itemFields['source_id'] ?? '');
        if ($this->localUserForCode($employeeCode)) {
            return false;
        }

        $this->deactivateExistingPeopleCost($itemFields);

        return true;
    }

    /**
     * @param array<string, mixed> $itemFields
     */
    private function deactivateExistingPeopleCost(array $itemFields): void
    {
        CostItem::query()
            ->where('source_system', $itemFields['source_system'])
            ->where('source_key', $itemFields['source_key'])
            ->where('account_category', 'people')
            ->update([
                'active' => false,
                'source_synced_at' => now(),
            ]);
    }

    /**
     * @param array{item: array<string, mixed>, rate: array<string, mixed>} $normalized
     * @return array{item: string, rate: string}
     */
    private function upsertCost(array $normalized): array
    {
        return DB::transaction(function () use ($normalized) {
            $itemFields = $this->resolveLocalSource($normalized['item']);
            $item = CostItem::query()
                ->where('source_system', $itemFields['source_system'])
                ->where('source_key', $itemFields['source_key'])
                ->where('account_category', $itemFields['account_category'])
                ->first();

            $itemWasCreated = false;
            if (! $item) {
                $item = new CostItem();
                $itemWasCreated = true;
            }

            $keepPrimaryPeopleItem = $item->exists
                && (int) $item->source_app_id === self::APP_PEOPLE_COSTS
                && (int) $itemFields['source_app_id'] === self::APP_EMPLOYMENT_CONTRACTS;

            if ($keepPrimaryPeopleItem) {
                $item->fill([
                    'source_synced_at' => $itemFields['source_synced_at'],
                ]);
                $item->save();

                return [
                    'item' => 'updated',
                    'rate' => 'rates_skipped',
                ];
            }

            $promotingFallbackToPrimary = $item->exists
                && (int) $item->source_app_id === self::APP_EMPLOYMENT_CONTRACTS
                && (int) $itemFields['source_app_id'] === self::APP_PEOPLE_COSTS;

            $item->fill($itemFields);
            $item->save();

            if ($promotingFallbackToPrimary) {
                CostItemRate::query()
                    ->where('cost_item_id', $item->id)
                    ->where('source_app_id', self::APP_EMPLOYMENT_CONTRACTS)
                    ->delete();
            }

            // Skip the rate write when the source content is unchanged (hash match),
            // so repeat syncs are no-ops instead of rewriting every row.
            $incomingHash = $normalized['rate']['source_hash'] ?? null;
            $existingRate = CostItemRate::query()
                ->where('cost_item_id', $item->id)
                ->whereDate('effective_from', $normalized['rate']['effective_from'])
                ->first();

            if ($existingRate && $incomingHash !== null && $existingRate->source_hash === $incomingHash) {
                return [
                    'item' => $itemWasCreated ? 'created' : 'updated',
                    'rate' => 'rates_skipped',
                ];
            }

            $rate = CostItemRate::updateOrCreate(
                [
                    'cost_item_id' => $item->id,
                    'effective_from' => $normalized['rate']['effective_from'],
                ],
                [
                    ...$normalized['rate'],
                    'cost_item_id' => $item->id,
                ]
            );

            $this->syncDefaultRate($item);

            return [
                'item' => $itemWasCreated ? 'created' : 'updated',
                'rate' => $rate->wasRecentlyCreated ? 'rates_created' : 'rates_updated',
            ];
        });
    }

    /**
     * @param array{worksite: array<string, mixed>, rent: array<string, mixed>|null} $normalized
     * @return array{worksite: string, rent: string|null}
     */
    private function upsertWorksite(array $normalized): array
    {
        return DB::transaction(function () use ($normalized) {
            $fields = $normalized['worksite'];
            $worksite = Worksite::query()
                ->where('source_system', $fields['source_system'])
                ->where('source_key', $fields['source_key'])
                ->first();

            $worksiteWasCreated = false;
            if (! $worksite) {
                $worksite = new Worksite();
                $worksiteWasCreated = true;
            } else {
                // Several 236 records share one worksite (賃料/駐車場/事務所 segments).
                // Don't let a sparse record clobber a good name with the "拠点 #N" fallback,
                // and never let a parking record rename an already-named worksite.
                $incomingSegment = $normalized['rent']['segment'] ?? null;
                $existingNameIsFallback = str_starts_with((string) $worksite->name, '拠点 #');
                if (
                    str_starts_with((string) $fields['name'], '拠点 #')
                    || ($incomingSegment === WorksiteRent::SEGMENT_PARKING && ! $existingNameIsFallback)
                ) {
                    unset($fields['name']);
                }
                if ($fields['office_name'] === null) {
                    unset($fields['office_name']);
                }
                if ($fields['users_note'] === null) {
                    unset($fields['users_note']);
                }
            }

            $worksite->fill($fields);
            $worksite->save();

            $rentStat = null;
            if ($normalized['rent'] !== null) {
                $rentFields = $normalized['rent'];
                $existing = WorksiteRent::query()
                    ->where('worksite_id', $worksite->id)
                    ->where('segment', $rentFields['segment'])
                    ->whereDate('effective_from', $rentFields['effective_from'])
                    ->first();

                if ($existing && $existing->source_hash === $rentFields['source_hash']) {
                    $rentStat = 'worksite_rents_skipped';
                } else {
                    $rent = WorksiteRent::updateOrCreate(
                        [
                            'worksite_id' => $worksite->id,
                            'segment' => $rentFields['segment'],
                            'effective_from' => $rentFields['effective_from'],
                        ],
                        $rentFields
                    );
                    $rentStat = $rent->wasRecentlyCreated ? 'worksite_rents_created' : 'worksite_rents_updated';
                }
            }

            return [
                'worksite' => $worksiteWasCreated ? 'worksites_created' : 'worksites_updated',
                'rent' => $rentStat,
            ];
        });
    }

    private function syncDefaultRate(CostItem $item): void
    {
        $today = Carbon::today()->toDateString();
        $currentRate = CostItemRate::query()
            ->where('cost_item_id', $item->id)
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->first();

        $latestRate = $currentRate ?: CostItemRate::query()
            ->where('cost_item_id', $item->id)
            ->orderByDesc('effective_from')
            ->first();

        if ($latestRate) {
            $item->update(['default_rate' => $latestRate->rate]);
        }
    }

    /**
     * @param array<string, mixed> $itemFields
     * @return array<string, mixed>
     */
    private function resolveLocalSource(array $itemFields): array
    {
        if (
            ($itemFields['source_system'] ?? null) !== self::SOURCE_SYSTEM
            || ($itemFields['account_category'] ?? null) !== 'people'
            || ! in_array((int) ($itemFields['source_app_id'] ?? 0), [self::APP_PEOPLE_COSTS, self::APP_EMPLOYMENT_CONTRACTS], true)
        ) {
            return $itemFields;
        }

        $employeeCode = (string) ($itemFields['source_id'] ?? '');
        $user = $this->localUserForCode($employeeCode);

        if (! $user) {
            return $itemFields;
        }

        return [
            ...$itemFields,
            'name' => $user->name ?: $itemFields['name'],
            'source_type' => 'user',
            'source_id' => (string) $user->id,
            'source_label' => $user->name,
        ];
    }

    private function localUserForCode(string $employeeCode): ?User
    {
        $code = trim($employeeCode);
        if ($code === '') {
            return null;
        }

        if (array_key_exists($code, $this->userByCode)) {
            return $this->userByCode[$code];
        }

        $candidates = array_values(array_unique([
            $code,
            ltrim($code, '0'),
            str_pad(ltrim($code, '0'), strlen($code), '0', STR_PAD_LEFT),
        ]));

        $this->userByCode[$code] = User::query()
            ->whereIn('user_code', $candidates)
            ->where('retire', 0)
            ->first();

        return $this->userByCode[$code];
    }

    private function fieldValue(array $record, string $field): mixed
    {
        $value = $record[$field] ?? null;

        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    private function stringValue(array $record, string $field): string
    {
        $value = $this->fieldValue($record, $field);

        if (is_array($value) || $value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function numberValue(array $record, string $field): float
    {
        $value = $this->fieldValue($record, $field);

        if (is_array($value) || $value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    private function formatSourceNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function normalizeWorksiteRentSegment(string $segment): string
    {
        return $segment === WorksiteRent::SEGMENT_RENT_LEGACY
            ? WorksiteRent::SEGMENT_RENT
            : $segment;
    }

    private function isWorksiteRentSourceSegment(string $rawSegment): bool
    {
        return in_array($rawSegment, self::WORKSITE_RENT_SOURCE_SEGMENTS, true);
    }

    /**
     * @param array<int, string> $fields
     */
    private function firstStringValue(array $record, array $fields): string
    {
        foreach ($fields as $field) {
            $value = $this->stringValue($record, $field);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param array<int, string> $fields
     */
    private function sumNumberValues(array $record, array $fields): float
    {
        return array_reduce(
            $fields,
            fn (float $total, string $field) => $total + $this->numberValue($record, $field),
            0.0
        );
    }

    private function isRetiredFlag(string $flag): bool
    {
        $normalized = trim(strtolower($flag));
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'none', '-', '未退職', '在籍', 'いいえ'], true)) {
            return false;
        }

        if (is_numeric($normalized)) {
            return (float) $normalized > 0;
        }

        return str_contains($normalized, '退職') || in_array($normalized, ['true', 'yes', 'あり'], true);
    }

    private function dateValue(array $record, string $field): ?string
    {
        $value = $this->fieldValue($record, $field);

        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dateTimeValue(array $record, string $field): ?Carbon
    {
        $value = $this->fieldValue($record, $field);

        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $source
     */
    private function sourceHash(array $source): string
    {
        return hash('sha256', json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
