<?php

namespace App\Services;

use App\Models\ActualResultDepartment;
use App\Models\ActualResultReport;
use App\Models\ActualResultUpload;
use App\Models\ProjectRecord;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ActualResultPersistenceService
{
    private const SENSITIVE_PAYROLL_ACCOUNTS = [
        '給料手当',
        '給与',
        '給与手当',
        '役員報酬',
        '役員賞与',
        '賞与',
        '賞与引当金繰入額',
    ];

    private const ANALYSIS_INTERNAL_TRANSFER_DEPARTMENTS = [
        '経営管理本部',
        '積立部門',
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

            $this->syncReportPayload($report, $result, $actorId);

            $report->forceFill([
                'current_upload_id' => $upload->id,
            ])->save();

            return $this->payloadFromReport($report->fresh(['departments', 'uploads']));
        });
    }

    /**
     * freee APIから取り込んだ実績を保存する。
     *
     * CSVアップロードと違い実ファイルが無いので、取込元が後から辿れるように
     * stored_path には擬似URIを残す（列がNOT NULLのため空にはできない）。
     */
    public function saveSyncedResult(array $result, string $month, int $actorId, string $source = 'freee'): array
    {
        $targetMonth = $this->targetMonth($month);

        return DB::transaction(function () use ($result, $targetMonth, $actorId, $source) {
            $report = ActualResultReport::withTrashed()->firstOrNew(['target_month' => $targetMonth]);
            $report->fill([
                'created_by' => $report->exists ? $report->created_by : $actorId,
                'updated_by' => $actorId,
                'deleted_at' => null,
            ]);
            $report->save();

            $monthLabel = Carbon::parse($targetMonth)->format('Y-m');
            $upload = ActualResultUpload::create([
                'actual_result_report_id' => $report->id,
                'target_month' => $targetMonth,
                'original_name' => $source.' 損益計算書 ('.$monthLabel.')',
                'stored_path' => $source.'://reports/trial_pl/'.$monthLabel,
                'file_hash' => null,
                'file_size' => 0,
                'file_metadata' => $result['file'] ?? [],
                'calculated_summary' => $result['summary'] ?? [],
                'uploaded_by' => $actorId,
            ]);

            $result['month'] = $monthLabel;
            $result['file']['saved_upload_id'] = $upload->id;

            $this->syncReportPayload($report, $result, $actorId);

            $report->forceFill([
                'current_upload_id' => $upload->id,
            ])->save();

            return $this->payloadFromReport($report->fresh(['departments', 'uploads']));
        });
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

    public function settlementUnitsForProjects(
        iterable $projects,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        return $this->departmentUnitsForProjects(
            $projects,
            $start,
            $end,
            false,
            fn (ActualResultDepartment $department) => $this->settlementUnit($department)
        );
    }

    public function analysisUnitsForProjects(
        iterable $projects,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        return $this->departmentUnitsForProjects(
            $projects,
            $start,
            $end,
            true,
            fn (ActualResultDepartment $department) => array_merge(
                $this->settlementUnit($department),
                ['actual_result_details' => $this->analysisDetails($department)]
            )
        );
    }

    public function settlementUnit(ActualResultDepartment $department): array
    {
        $metrics = $department->metrics ?: [];
        $sales = (int) ($metrics['sales'] ?? $department->sales ?? 0);
        $profit = (int) ($metrics['real_profit'] ?? $department->real_profit ?? 0);
        $expense = array_key_exists('total_expenses', $metrics)
            ? (int) $metrics['total_expenses']
            : $sales - $profit;

        return [
            'row' => null,
            'sales' => $sales,
            'expense' => $expense,
            'overhead' => (int) ($metrics['indirect_allocation_expense'] ?? $department->indirect_allocation_expense ?? 0),
            'profit' => $profit,
            'profit_rate' => $this->profitMargin($sales, $profit),
            'has_data' => true,
            'is_forecast' => false,
            'source' => 'actual_result',
        ];
    }

    public function analysisDetails(ActualResultDepartment $department): array
    {
        $metrics = $department->metrics ?: [];
        $topExpenseAccounts = [];
        $internalTransferExpense = 0;

        foreach ($department->accounts ?: [] as $account) {
            if (($account['category'] ?? 'expense') !== 'expense') {
                continue;
            }

            $accountName = trim((string) ($account['account_name'] ?? ''));
            $amount = (int) ($account['amount'] ?? 0);

            if (
                $accountName === '賞与'
                && in_array($department->department_name, self::ANALYSIS_INTERNAL_TRANSFER_DEPARTMENTS, true)
            ) {
                $internalTransferExpense += $amount;
                continue;
            }

            if ($amount <= 0 || ($account['bucket'] ?? 'ordinary_expense') !== 'ordinary_expense') {
                continue;
            }

            $label = in_array($accountName, self::SENSITIVE_PAYROLL_ACCOUNTS, true)
                ? '給与関連'
                : $accountName;

            if ($label !== '') {
                $topExpenseAccounts[$label] = ($topExpenseAccounts[$label] ?? 0) + $amount;
            }
        }

        arsort($topExpenseAccounts, SORT_NUMERIC);
        $topExpenseAccounts = array_map(
            fn (string $accountName, int $amount) => [
                'account_name' => $accountName,
                'amount' => $amount,
            ],
            array_keys(array_slice($topExpenseAccounts, 0, 5, true)),
            array_values(array_slice($topExpenseAccounts, 0, 5, true))
        );

        return [
            'report_id' => $department->actual_result_report_id,
            'source_mode' => $department->report?->file_metadata['calculation_source_mode'] ?? null,
            'manual_adjusted' => (bool) $department->manual_adjusted,
            'external_sales' => (int) ($metrics['external_sales'] ?? $department->external_sales ?? 0),
            'internal_sales' => (int) ($metrics['internal_sales'] ?? $department->internal_sales ?? 0),
            'cost_of_goods_sold' => (int) ($metrics['cost_of_goods_sold'] ?? $department->cost_of_goods_sold ?? 0),
            'sg_and_a_expenses' => (int) ($metrics['sg_and_a_expenses'] ?? $department->sg_and_a_expenses ?? 0),
            'indirect_allocation_expense' => (int) ($metrics['indirect_allocation_expense'] ?? $department->indirect_allocation_expense ?? 0),
            'performance_bonus_reserve' => (int) ($metrics['performance_bonus_reserve'] ?? $department->performance_bonus_reserve ?? 0),
            'basic_bonus_reserve' => (int) ($metrics['basic_bonus_reserve'] ?? $department->basic_bonus_reserve ?? 0),
            'paid_leave_reserve' => (int) ($metrics['paid_leave_reserve'] ?? $department->paid_leave_reserve ?? 0),
            'welfare_reserve' => (int) ($metrics['welfare_reserve'] ?? $department->welfare_reserve ?? 0),
            'refresh_reserve' => (int) ($metrics['refresh_reserve'] ?? $department->refresh_reserve ?? 0),
            'reserve_transfer_sales' => (int) ($metrics['reserve_transfer_sales'] ?? 0),
            'internal_transfer_expense' => $internalTransferExpense,
            'top_expense_accounts' => $topExpenseAccounts,
        ];
    }

    private function departmentUnitsForProjects(
        iterable $projects,
        CarbonInterface $start,
        CarbonInterface $end,
        bool $includeAnalysisDetails,
        callable $mapDepartment
    ): array {
        $projects = collect($projects)
            ->filter(fn ($project) => $project instanceof ProjectRecord)
            ->values();

        if ($projects->isEmpty()) {
            return [];
        }

        $projectIds = $projects->pluck('id')->map(fn ($id) => (int) $id)->all();
        $projectNames = $projects->pluck('name')->filter()->unique()->values()->all();
        $projectIdsByName = $projects->pluck('id', 'name');
        $columns = [
            'id',
            'actual_result_report_id',
            'project_record_id',
            'department_name',
            'metrics',
            'sales',
            'indirect_allocation_expense',
            'real_profit',
        ];

        if ($includeAnalysisDetails) {
            $columns = array_merge($columns, [
                'accounts',
                'manual_adjusted',
                'external_sales',
                'internal_sales',
                'cost_of_goods_sold',
                'sg_and_a_expenses',
                'performance_bonus_reserve',
                'basic_bonus_reserve',
                'paid_leave_reserve',
                'welfare_reserve',
                'refresh_reserve',
            ]);
        }

        $departments = ActualResultDepartment::query()
            ->select($columns)
            ->with(['report' => fn ($query) => $query->select('id', 'target_month', 'file_metadata')])
            ->where(function ($query) use ($projectIds, $projectNames) {
                $query->whereIn('project_record_id', $projectIds)
                    ->orWhere(function ($fallback) use ($projectNames) {
                        $fallback->whereNull('project_record_id')
                            ->whereIn('department_name', $projectNames);
                    });
            })
            ->whereHas('report', function ($query) use ($start, $end) {
                $query->whereBetween('target_month', [
                    $start->copy()->startOfMonth()->toDateString(),
                    $end->copy()->endOfMonth()->toDateString(),
                ]);
            })
            ->get();

        $units = [];

        foreach ($departments as $department) {
            $projectId = $department->project_record_id !== null
                ? (int) $department->project_record_id
                : (int) ($projectIdsByName->get($department->department_name) ?? 0);
            $month = $department->report?->target_month?->format('Y-m');

            if ($projectId === 0 || $month === null) {
                continue;
            }

            // A direct project relation is more reliable than the legacy name fallback.
            if (isset($units[$projectId][$month]) && $department->project_record_id === null) {
                continue;
            }

            $units[$projectId][$month] = $mapDepartment($department);
        }

        return $units;
    }

    private function syncReportPayload(
        ActualResultReport $report,
        array $payload,
        int $actorId
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

            ActualResultDepartment::updateOrCreate(
                [
                    'actual_result_report_id' => $report->id,
                    'department_name' => $departmentName,
                ],
                array_merge(
                    $this->departmentColumns($department, $projectIdsByName[$departmentName] ?? null),
                    [
                        // 手動編集は廃止。freee取込で常に上書きされる。
                        'manual_adjusted' => false,
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

    private function accountKey(array $account): string
    {
        return implode('|', [
            (string) ($account['account_code'] ?? ''),
            (string) ($account['account_name'] ?? ''),
            (string) ($account['category'] ?? ''),
            (string) ($account['bucket'] ?? ''),
        ]);
    }
    private function profitMargin(int $sales, int $profit): ?float
    {
        if ($sales === 0) {
            return null;
        }

        return round($profit / $sales * 100, 2, PHP_ROUND_HALF_UP);
    }
}
