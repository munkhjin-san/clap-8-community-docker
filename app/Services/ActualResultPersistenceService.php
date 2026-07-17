<?php

namespace App\Services;

use App\Models\ActualResultDepartment;
use App\Models\ActualResultEditHistory;
use App\Models\ActualResultReport;
use App\Models\ActualResultUpload;
use App\Models\ProjectRecord;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ActualResultPersistenceService
{
    private const MANUAL_BUCKETS = [
        'sales' => [
            'operating_sales',
            'other_sales',
        ],
        'expense' => [
            'ordinary_expense',
            'basic_bonus_reserve',
            'paid_leave_reserve',
            'welfare_reserve',
            'refresh_reserve',
            'beginning_inventory',
            'purchases',
            'ending_inventory',
        ],
    ];

    private const MANUAL_BUCKET_LABELS = [
        'operating_sales' => '売上',
        'other_sales' => 'その他収入',
        'ordinary_expense' => '通常経費',
        'basic_bonus_reserve' => '基本賞与積立金',
        'paid_leave_reserve' => '有給積立金',
        'welfare_reserve' => '福利厚生積立金',
        'refresh_reserve' => 'リフレッシュ積立金',
        'beginning_inventory' => '期首商品棚卸高',
        'purchases' => '仕入高',
        'ending_inventory' => '期末商品棚卸高',
    ];

    private const SENSITIVE_PAYROLL_ACCOUNTS = [
        '給料手当',
        '給与',
        '給与手当',
        '役員報酬',
        '役員賞与',
        '賞与',
        '賞与引当金繰入額',
    ];

    private const EXPORT_COLUMNS = [
        '月' => 'month',
        '部門' => 'department',
        'project_record_id' => 'project_record_id',
        '売上高合計' => 'external_sales',
        '内部売上高合計' => 'internal_sales',
        '売上高' => 'sales',
        '売上原価' => 'cost_of_goods_sold',
        '販管費合計' => 'sg_and_a_expenses',
        '間接費配賦' => 'indirect_allocation_expense',
        '通常利益' => 'normal_profit',
        '業績連動賞与積立金' => 'performance_bonus_reserve',
        '利益' => 'real_profit',
        '利益率' => 'real_margin',
        '基本賞与積立金' => 'basic_bonus_reserve',
        '有給積立金' => 'paid_leave_reserve',
        '福利厚生積立金' => 'welfare_reserve',
        'リフレッシュ積立金' => 'refresh_reserve',
        '積立振替売上' => 'reserve_transfer_sales',
    ];

    public function __construct(private ActualResultCsvService $calculator)
    {
    }

    public function payloadForMonth(string $month): ?array
    {
        $report = $this->reportForMonth($month);

        return $report ? $this->payloadFromReport($report) : null;
    }

    public function saveUploadedResult(array $result, UploadedFile $file, string $month, int $actorId): array
    {
        $targetMonth = $this->targetMonth($month);

        return DB::transaction(function () use ($result, $file, $targetMonth, $actorId) {
            $report = ActualResultReport::withTrashed()->firstOrNew(['target_month' => $targetMonth]);
            $report->fill([
                'created_by' => $report->exists ? $report->created_by : $actorId,
                'updated_by' => $actorId,
                'deleted_at' => null,
            ]);
            $report->save();

            $storedPath = $this->storeUploadedCsv($file, $targetMonth);
            $upload = ActualResultUpload::create([
                'actual_result_report_id' => $report->id,
                'target_month' => $targetMonth,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'file_hash' => hash_file('sha256', $file->getRealPath()),
                'file_size' => $file->getSize() ?: 0,
                'file_metadata' => $result['file'] ?? [],
                'calculated_summary' => $result['summary'] ?? [],
                'uploaded_by' => $actorId,
            ]);

            $result['month'] = Carbon::parse($targetMonth)->format('Y-m');
            $result['file']['saved_upload_id'] = $upload->id;
            $result['file']['stored_path'] = $storedPath;

            $this->syncReportPayload($report, $result, $actorId, [], false);

            $report->forceFill([
                'current_upload_id' => $upload->id,
            ])->save();

            return $this->payloadFromReport($report->fresh(['departments', 'uploads']));
        });
    }

    public function updateDepartmentAccount(ActualResultDepartment $department, array $data, int $actorId): array
    {
        return DB::transaction(function () use ($department, $data, $actorId) {
            $department = $department->fresh(['report.departments']);
            $report = $department->report;
            $payload = $this->payloadFromReport($report);
            $departmentIndex = $this->departmentPayloadIndex($payload, $department->department_name);

            if ($departmentIndex === null) {
                throw new InvalidArgumentException('Department payload was not found.');
            }

            $accounts = array_values($payload['departments'][$departmentIndex]['accounts'] ?? []);
            $accountKey = (string) ($data['account_key'] ?? '');
            $accountIndex = $this->accountPayloadIndex($accounts, $accountKey);
            $before = $accountIndex === null ? null : $accounts[$accountIndex];
            $delete = (bool) ($data['delete'] ?? false);

            if ($before !== null && $this->isCalculatedAccount($before)) {
                throw ValidationException::withMessages([
                    'account_key' => '自動計算された明細は編集できません。',
                ]);
            }

            if ($delete) {
                if ($accountIndex === null) {
                    throw new InvalidArgumentException('Account was not found.');
                }

                array_splice($accounts, $accountIndex, 1);
                $after = null;
                $action = 'delete_account';
            } else {
                $after = $this->manualAccountPayload($data['account'] ?? [], $before, $department->department_name);
                $accountKey = $accountKey !== '' ? $accountKey : $after['account_key'];

                if ($accountIndex === null) {
                    $accounts[] = $after;
                    $action = 'add_account';
                } else {
                    $accounts[$accountIndex] = $after;
                    $action = 'update_account';
                }
            }

            $payload['departments'][$departmentIndex]['accounts'] = $accounts;
            $payload = $this->calculator->recalculateResultPayload($payload);

            ActualResultEditHistory::create([
                'actual_result_report_id' => $report->id,
                'actual_result_department_id' => $department->id,
                'project_record_id' => $department->project_record_id,
                'department_name' => $department->department_name,
                'action' => $action,
                'account_key' => $accountKey,
                'before_value' => $before,
                'after_value' => $after,
                'note' => $data['note'] ?? null,
                'edited_by' => $actorId,
            ]);

            $this->syncReportPayload($report, $payload, $actorId, [$department->department_name], true);

            return $this->payloadFromReport($report->fresh(['departments', 'uploads']));
        });
    }

    public function accountOptions(string $month): array
    {
        $payload = $this->payloadForMonth($month);

        if ($payload === null) {
            return [];
        }

        $options = [];

        foreach ($payload['departments'] ?? [] as $department) {
            foreach ($department['accounts'] ?? [] as $account) {
                if ($this->isCalculatedAccount($account)) {
                    continue;
                }

                $key = $account['account_key'] ?? $this->accountKey($account);

                if (isset($options[$key])) {
                    continue;
                }

                $options[$key] = [
                    'account_key' => $key,
                    'account_code' => $account['account_code'] ?? '',
                    'account_name' => $account['account_name'] ?? '',
                    'category' => $account['category'] ?? 'expense',
                    'bucket' => $account['bucket'] ?? 'ordinary_expense',
                    'bucket_label' => $account['bucket_label'] ?? '',
                    'amount_source' => $account['amount_source'] ?? '',
                    'source_department' => $department['department'] ?? '',
                ];
            }
        }

        return array_values($options);
    }

    public function editHistories(string $month, ?int $departmentId = null): array
    {
        $report = $this->reportForMonth($month);

        if ($report === null) {
            return [];
        }

        return ActualResultEditHistory::query()
            ->with('editor:id,name')
            ->where('actual_result_report_id', $report->id)
            ->when($departmentId, fn ($query) => $query->where('actual_result_department_id', $departmentId))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ActualResultEditHistory $history) => [
                'id' => $history->id,
                'actual_result_department_id' => $history->actual_result_department_id,
                'project_record_id' => $history->project_record_id,
                'department_name' => $history->department_name,
                'action' => $history->action,
                'account_key' => $history->account_key,
                'before_value' => $history->before_value,
                'after_value' => $history->after_value,
                'note' => $history->note,
                'edited_by' => $history->edited_by,
                'editor_name' => $history->editor?->name,
                'created_at' => optional($history->created_at)->toISOString(),
            ])
            ->all();
    }

    public function exportCsv(string $month): string
    {
        $payload = $this->payloadForMonth($month);

        if ($payload === null) {
            throw new InvalidArgumentException('Actual result was not found.');
        }

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array_keys(self::EXPORT_COLUMNS), ',', '"', '');

        foreach ($payload['departments'] ?? [] as $department) {
            $row = [];

            foreach (self::EXPORT_COLUMNS as $key) {
                $row[] = $key === 'month'
                    ? ($payload['month'] ?? $month)
                    : ($department[$key] ?? '');
            }

            fputcsv($stream, $row, ',', '"', '');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return "\xEF\xBB\xBF".$csv;
    }

    public function reportForMonth(string $month): ?ActualResultReport
    {
        return ActualResultReport::query()
            ->with(['departments', 'uploads' => fn ($query) => $query->latest()])
            ->whereDate('target_month', $this->targetMonth($month))
            ->first();
    }

    public function targetMonth(string $month): string
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new InvalidArgumentException('Month must be YYYY-MM.');
        }

        return Carbon::createFromFormat('Y-m-d', "{$month}-01")->startOfMonth()->toDateString();
    }

    private function payloadFromReport(ActualResultReport $report): array
    {
        $report->loadMissing(['departments', 'uploads' => fn ($query) => $query->latest()]);

        $payload = $report->result_payload ?: [
            'file' => $report->file_metadata ?: [],
            'summary' => $report->summary ?: [],
            'departments' => [],
            'account_totals' => $report->account_totals ?: [],
        ];

        $payload['exists'] = true;
        $payload['id'] = $report->id;
        $payload['month'] = $report->target_month?->format('Y-m');
        $payload['file'] = array_merge($payload['file'] ?? [], $report->file_metadata ?: []);
        $payload['summary'] = $report->summary ?: ($payload['summary'] ?? []);
        $payload['account_totals'] = $report->account_totals ?: ($payload['account_totals'] ?? []);
        $payload['departments'] = $report->departments
            ->sortByDesc('sales')
            ->values()
            ->map(fn (ActualResultDepartment $department) => $this->departmentPayload($department))
            ->all();
        $payload['uploads'] = $report->uploads
            ->take(10)
            ->map(fn (ActualResultUpload $upload) => [
                'id' => $upload->id,
                'original_name' => $upload->original_name,
                'stored_path' => $upload->stored_path,
                'file_hash' => $upload->file_hash,
                'file_size' => $upload->file_size,
                'uploaded_by' => $upload->uploaded_by,
                'created_at' => optional($upload->created_at)->toISOString(),
            ])
            ->values()
            ->all();

        return $payload;
    }

    public function departmentPayload(ActualResultDepartment $department, bool $includePayrollAccounts = true): array
    {
        $metrics = $department->metrics ?: [];
        $accounts = array_map(function (array $account) {
            $account['account_key'] = $account['account_key'] ?? $this->accountKey($account);

            return $account;
        }, $department->accounts ?: []);

        $restrictedAccountCount = 0;

        if (! $includePayrollAccounts) {
            $accounts = array_values(array_filter($accounts, function (array $account) use (&$restrictedAccountCount) {
                if (! in_array(trim((string) ($account['account_name'] ?? '')), self::SENSITIVE_PAYROLL_ACCOUNTS, true)) {
                    return true;
                }

                $restrictedAccountCount++;

                return false;
            }));
        }

        $payload = array_merge($metrics, [
            'id' => $department->id,
            'actual_result_report_id' => $department->actual_result_report_id,
            'project_record_id' => $department->project_record_id,
            'department' => $department->department_name,
            'source_departments' => $department->source_departments ?: [$department->department_name],
            'manual_adjusted' => $department->manual_adjusted,
            'accounts_restricted' => $restrictedAccountCount > 0,
            'restricted_account_count' => $restrictedAccountCount,
            'accounts' => $accounts,
        ]);

        $sales = (int) ($payload['sales'] ?? $department->sales ?? 0);
        $payload['margin'] = $this->profitMargin($sales, (int) ($payload['normal_profit'] ?? $department->normal_profit ?? 0));
        $payload['real_margin'] = $this->profitMargin($sales, (int) ($payload['real_profit'] ?? $department->real_profit ?? 0));

        return $payload;
    }

    private function syncReportPayload(
        ActualResultReport $report,
        array $payload,
        int $actorId,
        array $manualDepartmentNames = [],
        bool $preserveManualFlags = true
    ): void {
        $payload['exists'] = true;
        $payload['id'] = $report->id;
        $payload['month'] = $report->target_month?->format('Y-m');

        $projectIdsByName = $this->projectIdsByDepartmentName($payload['departments'] ?? []);
        $departmentNames = collect($payload['departments'] ?? [])->pluck('department')->filter()->values();

        ActualResultDepartment::query()
            ->where('actual_result_report_id', $report->id)
            ->whereNotIn('department_name', $departmentNames)
            ->delete();

        foreach ($payload['departments'] ?? [] as $department) {
            $departmentName = (string) ($department['department'] ?? '');

            if ($departmentName === '') {
                continue;
            }

            $existing = ActualResultDepartment::query()
                ->where('actual_result_report_id', $report->id)
                ->where('department_name', $departmentName)
                ->first();

            $manualAdjusted = in_array($departmentName, $manualDepartmentNames, true)
                || ($preserveManualFlags && (bool) ($existing?->manual_adjusted));

            ActualResultDepartment::updateOrCreate(
                [
                    'actual_result_report_id' => $report->id,
                    'department_name' => $departmentName,
                ],
                array_merge(
                    $this->departmentColumns($department, $projectIdsByName[$departmentName] ?? null),
                    [
                        'manual_adjusted' => $manualAdjusted,
                        'updated_by' => $actorId,
                    ]
                )
            );
        }

        $report->forceFill([
            'file_metadata' => $payload['file'] ?? [],
            'summary' => $payload['summary'] ?? [],
            'account_totals' => $payload['account_totals'] ?? [],
            'result_payload' => $payload,
            'updated_by' => $actorId,
        ])->save();
    }

    private function departmentColumns(array $department, ?int $projectRecordId): array
    {
        return [
            'project_record_id' => $projectRecordId,
            'source_departments' => $department['source_departments'] ?? [],
            'metrics' => $this->departmentMetrics($department),
            'accounts' => $department['accounts'] ?? [],
            'external_sales' => (int) ($department['external_sales'] ?? 0),
            'internal_sales' => (int) ($department['internal_sales'] ?? 0),
            'sales' => (int) ($department['sales'] ?? 0),
            'cost_of_goods_sold' => (int) ($department['cost_of_goods_sold'] ?? 0),
            'sg_and_a_expenses' => (int) ($department['sg_and_a_expenses'] ?? 0),
            'indirect_allocation_expense' => (int) ($department['indirect_allocation_expense'] ?? 0),
            'normal_profit' => (int) ($department['normal_profit'] ?? 0),
            'performance_bonus_reserve' => (int) ($department['performance_bonus_reserve'] ?? 0),
            'real_profit' => (int) ($department['real_profit'] ?? 0),
            'real_margin' => $department['real_margin'] ?? null,
            'basic_bonus_reserve' => (int) ($department['basic_bonus_reserve'] ?? 0),
            'paid_leave_reserve' => (int) ($department['paid_leave_reserve'] ?? 0),
            'welfare_reserve' => (int) ($department['welfare_reserve'] ?? 0),
            'refresh_reserve' => (int) ($department['refresh_reserve'] ?? 0),
        ];
    }

    private function departmentMetrics(array $department): array
    {
        $metrics = $department;
        unset($metrics['accounts']);

        return $metrics;
    }

    private function projectIdsByDepartmentName(array $departments): array
    {
        $names = collect($departments)
            ->pluck('department')
            ->filter()
            ->unique()
            ->values();

        return ProjectRecord::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function storeUploadedCsv(UploadedFile $file, string $targetMonth): string
    {
        $month = Carbon::parse($targetMonth);
        $baseName = pathinfo($file->getClientOriginalName() ?: 'actual-result.csv', PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension() ?: 'csv';
        $safeName = Str::slug($baseName, '_') ?: 'actual_result';
        $fileName = now()->format('YmdHis')."_{$safeName}.{$extension}";

        return $file->storeAs("actual-results/{$month->format('Y/m')}", $fileName, 'local');
    }

    private function departmentPayloadIndex(array $payload, string $departmentName): ?int
    {
        foreach ($payload['departments'] ?? [] as $index => $department) {
            if (($department['department'] ?? '') === $departmentName) {
                return $index;
            }
        }

        return null;
    }

    private function accountPayloadIndex(array $accounts, string $accountKey): ?int
    {
        if ($accountKey === '') {
            return null;
        }

        foreach ($accounts as $index => $account) {
            if (($account['account_key'] ?? $this->accountKey($account)) === $accountKey) {
                return $index;
            }
        }

        return null;
    }

    private function manualAccountPayload(array $account, ?array $before, string $departmentName): array
    {
        $category = in_array(($account['category'] ?? $before['category'] ?? 'expense'), ['sales', 'expense'], true)
            ? (string) ($account['category'] ?? $before['category'] ?? 'expense')
            : 'expense';
        $requestedBucket = (string) ($account['bucket'] ?? $before['bucket'] ?? '');
        $bucket = in_array($requestedBucket, self::MANUAL_BUCKETS[$category], true)
            ? $requestedBucket
            : ($category === 'sales' ? 'operating_sales' : 'ordinary_expense');
        $amount = (int) round((float) ($account['amount'] ?? $before['amount'] ?? 0));

        $payload = [
            'account_code' => (string) ($account['account_code'] ?? $before['account_code'] ?? ''),
            'account_name' => trim((string) ($account['account_name'] ?? $before['account_name'] ?? '')),
            'category' => $category,
            'bucket' => $bucket,
            'bucket_label' => self::MANUAL_BUCKET_LABELS[$bucket],
            'amount_source' => 'manual',
            'amount' => $amount,
            'debit' => (int) round((float) ($account['debit'] ?? ($category === 'expense' ? $amount : 0))),
            'credit' => (int) round((float) ($account['credit'] ?? ($category === 'sales' ? $amount : 0))),
            'balance' => (int) round((float) ($account['balance'] ?? $amount)),
            'ending_balance' => (int) round((float) ($account['ending_balance'] ?? $before['ending_balance'] ?? 0)),
            'rows' => max((int) ($before['rows'] ?? 0), 1),
            'source_departments' => [$departmentName],
            'source_amounts' => [],
        ];

        if ($payload['account_name'] === '') {
            throw new InvalidArgumentException('Account name is required.');
        }

        $payload['account_key'] = $this->accountKey($payload);

        return $payload;
    }

    private function accountKey(array $account): string
    {
        return implode('|', [
            (string) ($account['account_code'] ?? ''),
            (string) ($account['account_name'] ?? ''),
            (string) ($account['category'] ?? ''),
            (string) ($account['bucket'] ?? ''),
        ]);
    }

    private function isCalculatedAccount(array $account): bool
    {
        return in_array((string) ($account['bucket'] ?? ''), [
            'performance_bonus_reserve',
            'indirect_allocation_expense',
            'reserve_transfer_sales',
            'indirect_allocation_sales',
        ], true) || in_array((string) ($account['amount_source'] ?? ''), [
            'generated_charge',
            'generated_internal_sales',
            'generated_bonus_accrual',
            'timecard_kintone',
        ], true);
    }

    private function profitMargin(int $sales, int $profit): ?float
    {
        if ($sales === 0) {
            return null;
        }

        return round($profit / $sales * 100, 2, PHP_ROUND_HALF_UP);
    }
}
