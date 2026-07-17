<?php

namespace App\Services;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\TimecardProjectSegment;
use App\Models\timecardRecord;
use Carbon\Carbon;
use InvalidArgumentException;

class ActualReserveAllocationService
{
    private const APP_PEOPLE_COSTS = KintoneCostMasterSyncService::APP_PEOPLE_COSTS;

    private const SOURCE_LABEL = 'timecard work time + Kintone app 96';

    private const BUCKET_POSITION_LIMITS = [
        'basic_bonus_reserve' => 11,
        'paid_leave_reserve' => 13,
        'welfare_reserve' => 12,
        'refresh_reserve' => 12,
    ];

    private const RESERVE_ELIGIBLE_EXTRA_POSITION_IDS = [16];

    private const EMPLOYEE_CODE_FIELDS = [
        '社員コード数値',
        '社員コード',
    ];

    private const RESERVE_FIELDS = [
        'basic_bonus_reserve' => [
            'account_name' => '基本賞与積立金',
        ],
        'paid_leave_reserve' => [
            'account_name' => '有給積立金',
            'fields' => [
                '有給引当金',
                '有休引当金',
                '有給休暇引当金',
            ],
        ],
        'welfare_reserve' => [
            'account_name' => '福利厚生積立金',
            'fields' => [
                '福利厚生費等引当金（リ補除く）',
                '福利厚生費等引当金(リ補除く)',
                '福利厚生費等引当金_リ補除く',
                '福利厚生費等引当金_リ補除く_',
                '福利厚生費等引当金',
                '福利厚生引当金',
            ],
        ],
        'refresh_reserve' => [
            'account_name' => 'リフレッシュ補助積立金',
            'fields' => [
                'リ補引当金',
                'リフレッシュ補助金引当金',
                'リフレッシュ補助引当金',
                'リフレッシュ引当金',
                'リフレッシュ補助積立金',
            ],
        ],
    ];

    public function __construct(private KintoneClient $kintone)
    {
    }

    /**
     * @return array{
     *     month: string,
     *     source: string,
     *     allocations: array<int, array<string, mixed>>,
     *     warnings: array<int, string>,
     *     stats: array<string, int>
     * }
     */
    public function allocationsForMonth(string $month): array
    {
        [$year, $monthNumber] = $this->parseMonth($month);
        $monthEnd = Carbon::create($year, $monthNumber, 1)->endOfMonth();
        [$workMinutesByUser, $warnings] = $this->timecardWorkMinutesByUser($year, $monthNumber);
        $peopleCostRecords = $this->peopleCostRecordsByCode($monthEnd);

        return $this->allocationsFromSources(
            sprintf('%04d-%02d', $year, $monthNumber),
            $workMinutesByUser,
            $peopleCostRecords,
            $warnings
        );
    }

    /**
     * @param array<int, array{user_id: int, user_name: string, user_code: string, position_id?: int|null, departments: array<string, int>}> $workMinutesByUser
     * @param array<string, array<string, mixed>> $peopleCostRecords
     * @param array<int, string> $warnings
     * @return array{
     *     month: string,
     *     source: string,
     *     allocations: array<int, array<string, mixed>>,
     *     warnings: array<int, string>,
     *     stats: array<string, int>
     * }
     */
    public function allocationsFromSources(string $month, array $workMinutesByUser, array $peopleCostRecords, array $warnings = []): array
    {
        $allocations = [];
        $matchedUsers = 0;
        $basicBonusAccrualUsers = 0;
        $basicBonusAccrualTotal = 0;

        foreach ($workMinutesByUser as $userData) {
            $userCode = (string) ($userData['user_code'] ?? '');
            $codeKey = $this->normalizeEmployeeCode($userCode);

            if ($codeKey === '') {
                $warnings[] = "社員コードがないため、{$userData['user_name']} の積立金を計算できませんでした。";
                continue;
            }

            $peopleCostRecord = $peopleCostRecords[$codeKey] ?? null;

            if ($peopleCostRecord === null) {
                $warnings[] = "Kintone app 96 に社員コード {$userCode} のレコードがないため、{$userData['user_name']} の積立金を計算できませんでした。";
                continue;
            }

            $departmentMinutes = $userData['departments'];
            $totalMinutes = array_sum($departmentMinutes);

            if ($totalMinutes <= 0) {
                continue;
            }

            $matchedUsers++;
            $reserveSources = $this->reserveSourceAmounts($peopleCostRecord);

            if ($this->isEligibleForBucket($userData['position_id'] ?? null, 'basic_bonus_reserve')) {
                $basicBonusAccrualAmount = $reserveSources['basic_bonus_reserve']['amount'] ?? 0;

                if ($basicBonusAccrualAmount !== 0) {
                    $basicBonusAccrualUsers++;
                    $basicBonusAccrualTotal += $basicBonusAccrualAmount;
                }
            }

            foreach ($reserveSources as $bucket => $source) {
                if (! $this->isEligibleForBucket($userData['position_id'] ?? null, $bucket)) {
                    continue;
                }

                $sourceAmount = $source['amount'];

                if ($sourceAmount === 0) {
                    continue;
                }

                foreach ($this->splitAmount($sourceAmount, $departmentMinutes) as $department => $amount) {
                    if ($amount === 0) {
                        continue;
                    }

                    $allocations[] = [
                        'department' => $department,
                        'account_name' => self::RESERVE_FIELDS[$bucket]['account_name'],
                        'bucket' => $bucket,
                        'amount' => $amount,
                        'amount_source' => 'timecard_kintone',
                        'source_department' => self::SOURCE_LABEL,
                        'source_amount' => $sourceAmount,
                        'source_fields' => $source['fields'],
                        'work_minutes' => $departmentMinutes[$department],
                        'total_work_minutes' => $totalMinutes,
                        'user_id' => $userData['user_id'],
                        'user_name' => $userData['user_name'],
                        'user_code' => $userCode,
                        'position_id' => $userData['position_id'] ?? null,
                    ];
                }
            }
        }
//                 dd(
//     collect($allocations)
//         ->groupBy('department')
//         ->map(fn ($projectRows) => $projectRows
//             ->groupBy('user_name')
//             ->map(fn ($userRows) => $userRows
//                 ->mapWithKeys(fn ($row) => [
//                     $row['account_name'] => [
//                         'amount' => $row['amount'],
//                         'source_amount' => $row['source_amount'],
//                         'work_minutes' => $row['work_minutes'],
//                         'total_work_minutes' => $row['total_work_minutes'],
//                         'user_code' => $row['user_code'],
//                         'source_fields' => $row['source_fields'] ?? [],
//                     ],
//                 ])
//             )
//         )
//         ->toArray()
// );
        return [
            'month' => $month,
            'source' => self::SOURCE_LABEL,
            'allocations' => $allocations,
            'warnings' => $warnings,
            'stats' => [
                'timecard_users' => count($workMinutesByUser),
                'matched_users' => $matchedUsers,
                'generated_rows' => count($allocations),
                'generated_total' => array_sum(array_column($allocations, 'amount')),
                'basic_bonus_accrual_users' => $basicBonusAccrualUsers,
                'basic_bonus_accrual_total' => $basicBonusAccrualTotal,
            ],
        ];
    }

    /**
     * @return array{int, int}
     */
    private function parseMonth(string $month): array
    {
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $month, $matches) !== 1) {
            throw new InvalidArgumentException("Invalid month format: {$month}");
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    /**
     * @return array{0: array<int, array{user_id: int, user_name: string, user_code: string, position_id: int|null, departments: array<string, int>}>, 1: array<int, string>}
     */
    private function timecardWorkMinutesByUser(int $year, int $month): array
    {
        $records = timecardRecord::query()
            ->whereYear('day', $year)
            ->whereMonth('day', $month)
            ->with([
                'user:id,name,user_code,position_id',
                'department:id,name',
                'project_segments' => function ($query) {
                    $query->with('project:id,name');
                },
            ])
            ->get(['id', 'user_id', 'day', 'work_group_id', 'work_time']);

        $workMinutesByUser = [];
        $warnings = [];

        foreach ($records as $record) {
            $user = $record->user;

            if ($user === null) {
                $warnings[] = "timecard_records #{$record->id} のユーザーが見つかりませんでした。";
                continue;
            }

            $departmentMinutes = $this->timecardDepartmentMinutes($record, $warnings);

            if ($departmentMinutes === []) {
                continue;
            }

            $userId = (int) $user->id;

            if (! isset($workMinutesByUser[$userId])) {
                $workMinutesByUser[$userId] = [
                    'user_id' => $userId,
                    'user_name' => (string) $user->name,
                    'user_code' => trim((string) $user->user_code),
                    'position_id' => $user->position_id,
                    'departments' => [],
                ];
            }

            foreach ($departmentMinutes as $departmentName => $minutes) {
                if ($minutes <= 0) {
                    continue;
                }

                $workMinutesByUser[$userId]['departments'][$departmentName] =
                    ($workMinutesByUser[$userId]['departments'][$departmentName] ?? 0) + $minutes;
            }
        }

        return [$workMinutesByUser, $warnings];
    }

    /**
     * @param array<int, string> $warnings
     * @return array<string, int>
     */
    private function timecardDepartmentMinutes(timecardRecord $record, array &$warnings): array
    {
        $segments = $record->project_segments ?? collect();

        if ($segments->isNotEmpty()) {
            return $this->timecardProjectSegmentMinutes($record, $warnings);
        }

        return $this->legacyTimecardDepartmentMinutes($record, $warnings);
    }

    /**
     * @param array<int, string> $warnings
     * @return array<string, int>
     */
    private function timecardProjectSegmentMinutes(timecardRecord $record, array &$warnings): array
    {
        $minutesByDepartment = [];

        foreach ($record->project_segments ?? collect() as $segment) {
            if (($segment->segment_type ?? TimecardProjectSegment::TYPE_WORK) !== TimecardProjectSegment::TYPE_WORK) {
                continue;
            }

            $minutes = (int) ($segment->minutes ?? 0);

            if ($minutes <= 0) {
                continue;
            }

            $departmentName = trim((string) ($segment->project?->name ?? ''));

            if ($departmentName === '') {
                $warnings[] = "timecard_project_segments #{$segment->id} の部門が見つかりませんでした。";
                continue;
            }

            $minutesByDepartment[$departmentName] = ($minutesByDepartment[$departmentName] ?? 0) + $minutes;
        }

        if ($minutesByDepartment === []) {
            $warnings[] = "timecard_records #{$record->id} に就業セグメントの勤務時間がないため、積立金の時間按分から除外しました。";
        }

        return $minutesByDepartment;
    }

    /**
     * @param array<int, string> $warnings
     * @return array<string, int>
     */
    private function legacyTimecardDepartmentMinutes(timecardRecord $record, array &$warnings): array
    {
        $departmentName = trim((string) ($record->department?->name ?? ''));

        if ($departmentName === '') {
            $warnings[] = "timecard_records #{$record->id} の部門が見つかりませんでした。";
            return [];
        }

        $minutes = (int) ($record->work_time ?? 0);

        if ($minutes <= 0) {
            $warnings[] = "timecard_records #{$record->id} の勤務時間がないため、積立金の時間按分から除外しました。";
            return [];
        }

        return [
            $departmentName => $minutes,
        ];
    }

    private function isEligibleForBucket(mixed $positionId, string $bucket): bool
    {
        if (! isset(self::BUCKET_POSITION_LIMITS[$bucket])) {
            return true;
        }

        if ($positionId === null || $positionId === '') {
            return false;
        }

        $positionId = (int) $positionId;

        return $positionId <= self::BUCKET_POSITION_LIMITS[$bucket]
            || in_array($positionId, self::RESERVE_ELIGIBLE_EXTRA_POSITION_IDS, true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function peopleCostRecordsByCode(Carbon $monthEnd): array
    {
        $records = $this->kintone->getAllRecords(self::APP_PEOPLE_COSTS);
        $recordsByCode = [];

        foreach ($records as $record) {
            $code = $this->normalizeEmployeeCode($this->firstStringValue($record, self::EMPLOYEE_CODE_FIELDS));

            if ($code === '') {
                continue;
            }

            if (! isset($recordsByCode[$code]) || $this->isBetterPeopleCostRecord($record, $recordsByCode[$code], $monthEnd)) {
                $recordsByCode[$code] = $record;
            }
        }

        return $recordsByCode;
    }

    /**
     * @return array<string, array{amount: int, fields: array<int, string>}>
     */
    private function reserveSourceAmounts(array $record): array
    {
        [$baseSalary, $baseSalaryField] = $this->firstNumberValueWithField($record, [
            '基本給',
        ]);
        [$positionAllowance, $positionAllowanceField] = $this->firstNumberValueWithField($record, [
            '役職手当',
        ]);
        [$paidLeaveReserve, $paidLeaveField] = $this->firstNumberValueWithField($record, self::RESERVE_FIELDS['paid_leave_reserve']['fields']);
        [$welfareReserve, $welfareField] = $this->firstNumberValueWithField($record, self::RESERVE_FIELDS['welfare_reserve']['fields']);
        [$refreshReserve, $refreshField] = $this->firstNumberValueWithField($record, self::RESERVE_FIELDS['refresh_reserve']['fields']);

        return [
            'basic_bonus_reserve' => [
                'amount' => $this->yenAmount(($baseSalary + $positionAllowance) / 12),
                'fields' => array_values(array_filter([$baseSalaryField, $positionAllowanceField])),
            ],
            'paid_leave_reserve' => [
                'amount' => $this->yenAmount($paidLeaveReserve),
                'fields' => array_values(array_filter([$paidLeaveField])),
            ],
            'welfare_reserve' => [
                'amount' => $this->yenAmount($welfareReserve),
                'fields' => array_values(array_filter([$welfareField])),
            ],
            'refresh_reserve' => [
                'amount' => $this->yenAmount($refreshReserve),
                'fields' => array_values(array_filter([$refreshField])),
            ],
        ];
    }

    /**
     * @param array<string, int> $weights
     * @return array<string, int>
     */
    private function splitAmount(int $amount, array $weights): array
    {
        $totalWeight = array_sum($weights);

        if ($amount === 0 || $totalWeight <= 0) {
            return array_fill_keys(array_keys($weights), 0);
        }

        $sign = $amount < 0 ? -1 : 1;
        $absoluteAmount = abs($amount);
        $allocated = array_fill_keys(array_keys($weights), 0);
        $entries = [];
        $used = 0;
        $index = 0;

        foreach ($weights as $key => $weight) {
            if ($weight > 0) {
                $value = $this->yenAmount($absoluteAmount * $weight / $totalWeight);

                $allocated[$key] = $value;
                $used += $value;
                $entries[] = [
                    'key' => $key,
                    'weight' => $weight,
                    'index' => $index,
                ];
            }

            $index++;
        }

        if ($entries !== [] && $used !== $absoluteAmount) {
            usort(
                $entries,
                fn (array $a, array $b) => ($b['weight'] <=> $a['weight']) ?: ($a['index'] <=> $b['index'])
            );

            $allocated[$entries[0]['key']] += $absoluteAmount - $used;
        }

        foreach ($allocated as $key => $value) {
            $allocated[$key] = $value * $sign;
        }

        return $allocated;
    }

    private function yenAmount(float $amount): int
    {
        return (int) round($amount);
    }

    private function isBetterPeopleCostRecord(array $candidate, array $current, Carbon $monthEnd): bool
    {
        $candidateDate = $this->peopleCostDate($candidate);
        $currentDate = $this->peopleCostDate($current);

        if ($candidateDate !== null && $candidateDate->gt($monthEnd)) {
            return false;
        }

        if ($currentDate !== null && $currentDate->gt($monthEnd)) {
            return true;
        }

        if ($candidateDate === null) {
            return false;
        }

        return $currentDate === null || $candidateDate->gt($currentDate);
    }

    private function peopleCostDate(array $record): ?Carbon
    {
        foreach (['計算基準日', '更新日時'] as $field) {
            $date = $this->dateValue($record, $field);

            if ($date !== null) {
                return $date;
            }
        }

        return null;
    }

    private function normalizeEmployeeCode(string $code): string
    {
        return \App\Support\EmployeeCode::normalize($code);
    }

    private function fieldValue(array $record, string $field): mixed
    {
        $resolvedField = $this->resolveFieldKey($record, $field);

        if ($resolvedField === null) {
            return null;
        }

        $value = $record[$resolvedField] ?? null;

        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    private function resolveFieldKey(array $record, string $field): ?string
    {
        if (array_key_exists($field, $record)) {
            return $field;
        }

        $target = $this->normalizeFieldKey($field);

        foreach (array_keys($record) as $candidate) {
            $candidate = (string) $candidate;
            $normalizedCandidate = $this->normalizeFieldKey($candidate);

            if ($normalizedCandidate === $target || $this->stripTrailingDigits($normalizedCandidate) === $target) {
                return $candidate;
            }
        }

        return null;
    }

    private function stringValue(array $record, string $field): string
    {
        $value = $this->fieldValue($record, $field);

        if (is_array($value) || $value === null) {
            return '';
        }

        return trim((string) $value);
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

    private function numberValue(array $record, string $field): float
    {
        $value = $this->fieldValue($record, $field);

        if (is_array($value) || $value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    /**
     * @param array<int, string> $fields
     * @return array{0: float, 1: string|null}
     */
    private function firstNumberValueWithField(array $record, array $fields): array
    {
        foreach ($fields as $field) {
            $resolvedField = $this->resolveFieldKey($record, $field);

            if ($resolvedField === null) {
                continue;
            }

            return [$this->numberValue($record, $resolvedField), $resolvedField];
        }

        return [0.0, null];
    }

    private function normalizeFieldKey(string $field): string
    {
        if (function_exists('mb_convert_kana')) {
            $field = mb_convert_kana($field, 'asKV', 'UTF-8');
        }

        $field = str_replace(['（', '）', '＿', '－', 'ー'], ['(', ')', '_', '-', '-'], $field);

        return preg_replace('/[\s_\-()]+/u', '', $field) ?? $field;
    }

    private function stripTrailingDigits(string $field): string
    {
        return preg_replace('/\d+$/', '', $field) ?? $field;
    }

    private function dateValue(array $record, string $field): ?Carbon
    {
        $value = $this->fieldValue($record, $field);

        if (is_array($value) || $value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
