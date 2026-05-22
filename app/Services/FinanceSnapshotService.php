<?php

namespace App\Services;

use App\Imports\YearlyPlanImport;
use App\Infrastructure\Kintone\KintoneClient;
use App\Infrastructure\Sheets\GoogleSheetsClient;
use App\Models\ProjectPlanYear;
use App\Models\ProjectRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class FinanceSnapshotService
{
    private const FINANCE_FISCAL_START_MONTH = 3;
    private const ACTUAL_RELEASE_DAY = 20;

    private const ACCOUNT_MAP = [
        'sales' => '4020',
        't_expense' => '6270',
        'profit' => '9130',
        'bonus' => '9120',
    ];

    public function __construct(
        private KintoneClient $kintone,
        private GoogleSheetsClient $sheets,
        private ProjectPlanFormulaService $planFormulaService,
    ) {
    }

    public function currentFiscalYear(?Carbon $now = null): int
    {
        $now ??= now(config('app.timezone', 'Asia/Tokyo'));

        return $now->month >= self::FINANCE_FISCAL_START_MONTH
            ? (int) $now->year
            : (int) $now->year - 1;
    }

    public function latestClosedPeriod(?Carbon $now = null): Carbon
    {
        $now ??= now(config('app.timezone', 'Asia/Tokyo'));

        $monthsBack = $now->day >= self::ACTUAL_RELEASE_DAY ? 1 : 2;

        return $now->copy()->startOfMonth()->subMonthsNoOverflow($monthsBack);
    }

    public function fiscalPeriod(int $fiscalYear): array
    {
        $start = Carbon::create($fiscalYear, self::FINANCE_FISCAL_START_MONTH, 1)->startOfMonth();
        $end = $start->copy()->addMonthsNoOverflow(11)->startOfMonth();

        return [$start, $end];
    }

    public static function projectNameKey(?string $name): string
    {
        $value = mb_convert_kana((string) $name, 'asKV', 'UTF-8');
        $value = str_replace(
            ['（', '）', '［', '］', '【', '】', '　'],
            ['(', ')', '[', ']', '[', ']', ' '],
            $value
        );
        $value = preg_replace('/\s+/u', '', $value) ?? '';

        return mb_strtolower($value);
    }

    public function buildFiscalYearSnapshot(
        ?int $fiscalYear = null,
        array $projectIds = [],
        ?string $asOfPeriod = null,
        int $limit = 15
    ): array {
        $fiscalYear ??= $this->currentFiscalYear();
        [$start, $end] = $this->fiscalPeriod($fiscalYear);
        $latestClosed = $asOfPeriod
            ? Carbon::createFromFormat('Y-m-d', $asOfPeriod . '-01')->startOfMonth()
            : $this->latestClosedPeriod();
        if ($latestClosed->greaterThan($end)) {
            $latestClosed = $end->copy();
        }

        $projects = $this->projectsForPeriod($start, $projectIds);
        $projectNames = $projects->pluck('name', 'id')->all();
        
        $plans = $this->fetchYearlyPlans($projects, $fiscalYear);
        $profits = $this->fetchProfitData($start, $end, $projectNames);
        $settlements = $this->fetchSettlementData($start, $end, $projectNames);

        $months = $this->monthKeys($start, $end);
        $summary = [
            'yearly_plan' => $this->emptyUnit(),
            'profit' => $this->emptyUnit(),
            'settlement' => $this->emptyUnit(),
            'forecast' => $this->emptyUnit(),
        ];
        $monthlyTotals = [];
        $projectRows = [];
        $missingSettlementPeriods = [];
        $forecastPeriods = [];

        foreach ($months as $periodKey) {
            $monthlyTotals[$periodKey] = [
                'yearly_plan' => $this->emptyUnit(),
                'profit' => $this->emptyUnit(),
                'settlement' => $this->emptyUnit(),
                'forecast' => $this->emptyUnit(),
            ];
        }

        foreach ($projects as $project) {
            $projectId = (int) $project->id;
            $projectName = (string) $project->name;
            $projectKey = self::projectNameKey($projectName);
            $completedAt = $project->completed_at
                ? Carbon::parse($project->completed_at)->startOfMonth()
                : null;
            $projectTotals = [
                'yearly_plan' => $this->emptyUnit(),
                'profit' => $this->emptyUnit(),
                'settlement' => $this->emptyUnit(),
                'forecast' => $this->emptyUnit(),
            ];
            $monthly = [];
            $projectForecastPeriods = [];
            $projectMissingSettlements = [];

            foreach ($months as $periodKey) {
                $period = Carbon::createFromFormat('Y-m-d', $periodKey . '-01')->startOfMonth();
                $plan = $plans[$projectId][$periodKey] ?? $this->emptyUnit();
                $profit = $profits[$projectKey][$periodKey] ?? $this->emptyUnit(false);
                $rawSettlement = $settlements[$projectKey][$periodKey] ?? $this->emptyUnit(false);
                $settlement = $period->lessThanOrEqualTo($latestClosed)
                    ? $rawSettlement
                    : array_merge($this->emptyUnit(false), ['source' => 'actual_not_released']);
                if ($this->isAfterProjectCompletion($period, $completedAt) && empty($settlement['has_data'])) {
                    $settlement = array_merge($settlement, ['source' => 'project_completed']);
                }
                $forecast = $this->forecastUnit($profit, $settlement, $period, $latestClosed, $completedAt);

                $monthly[$periodKey] = [
                    'yearly_plan' => $plan,
                    'profit' => $profit,
                    'settlement' => $settlement,
                    'forecast' => $forecast,
                ];

                foreach (['yearly_plan' => $plan, 'profit' => $profit, 'settlement' => $settlement, 'forecast' => $forecast] as $bucket => $unit) {
                    $projectTotals[$bucket] = $this->addUnit($projectTotals[$bucket], $unit);

                    $summaryUnit = $this->summaryUnit($bucket, $unit, $projectName, $period);
                    $summary[$bucket] = $this->addUnit($summary[$bucket], $summaryUnit);
                    $monthlyTotals[$periodKey][$bucket] = $this->addUnit($monthlyTotals[$periodKey][$bucket], $summaryUnit);
                }

                if (! empty($forecast['is_forecast'])) {
                    $projectForecastPeriods[] = $periodKey;
                    $forecastPeriods[$periodKey] = true;
                }
                if ($this->shouldExpectActual($period, $latestClosed, $completedAt) && empty($settlement['has_data'])) {
                    $projectMissingSettlements[] = $periodKey;
                    $missingSettlementPeriods[$periodKey] = true;
                }
            }

            foreach (['yearly_plan', 'profit'] as $bucket) {
                $projectTotals[$bucket] = $this->finalizeUnit($projectTotals[$bucket]);
            }
            foreach (['settlement', 'forecast'] as $bucket) {
                $projectTotals[$bucket] = $this->finalizeUnit($projectTotals[$bucket], keepProfit: true);
            }

            $projectRows[] = [
                'project_id' => $projectId,
                'project_name' => $projectName,
                'completed_at' => $completedAt?->toDateString(),
                'totals' => $projectTotals,
                'variance_vs_plan' => $this->variance($projectTotals['forecast'], $projectTotals['yearly_plan']),
                'forecast_periods' => $projectForecastPeriods,
                'missing_settlement_periods' => $projectMissingSettlements,
                'months' => $monthly,
            ];
        }

        foreach (array_keys($monthlyTotals) as $periodKey) {
            foreach (['yearly_plan', 'profit', 'settlement', 'forecast'] as $bucket) {
                $monthlyTotals[$periodKey][$bucket] = $this->finalizeUnit($monthlyTotals[$periodKey][$bucket]);
            }
        }

        foreach (['yearly_plan', 'profit', 'settlement', 'forecast'] as $bucket) {
            $summary[$bucket] = $this->finalizeUnit($summary[$bucket]);
        }

        usort($projectRows, function (array $a, array $b) {
            $aGap = $a['variance_vs_plan']['profit_amount'] ?? 0;
            $bGap = $b['variance_vs_plan']['profit_amount'] ?? 0;

            return $aGap <=> $bGap;
        });

        return [
            'fiscal_year' => $fiscalYear,
            'fiscal_start_month' => self::FINANCE_FISCAL_START_MONTH,
            'period' => [
                'start' => $start->format('Y-m'),
                'end' => $end->format('Y-m'),
                'months' => $months,
            ],
            'latest_closed_period' => $latestClosed->format('Y-m'),
            'latest_actual_period' => $latestClosed->format('Y-m'),
            'project_count' => $projects->count(),
            'totals' => $summary,
            'variance_vs_plan' => $this->variance($summary['forecast'], $summary['yearly_plan']),
            'monthly_totals' => $monthlyTotals,
            'risk_projects' => array_slice($projectRows, 0, max(1, $limit)),
            'projects' => $projectRows,
            'data_status' => [
                'missing_settlement_periods' => array_values(array_keys($missingSettlementPeriods)),
                'forecast_periods' => array_values(array_keys($forecastPeriods)),
                'summary_adjustment' => [
                    'rule' => '集計では、間接費部門・積立部門、および2025-03から2026-02の経営管理本部を売上加算せず、販管費に「販管費 - 売上」として反映します。',
                    'project_names' => ['間接費部門', '積立部門', '経営管理本部'],
                    'special_period' => ['start' => '2025-03', 'end' => '2026-02'],
                ],
                'forecast_rule' => '着地見込みは、実績反映済み月はGoogle Sheets実績のみを使います。実績がない反映済み月と完了済みプロジェクトの完了後月は、Kintone損益や年間計画で補完しません。未反映の将来月だけKintone損益を予測値として使います。',
            ],
        ];
    }

    public function compactFiscalSummary(array $snapshot, int $limit = 10): array
    {
        $latestActualPeriod = $snapshot['latest_actual_period'] ?? $snapshot['latest_closed_period'];
        $latestActualMonthTotals = $snapshot['monthly_totals'][$latestActualPeriod]['settlement'] ?? null;

        return [
            'scope' => 'fiscal_year_finance',
            'fiscal_year' => $snapshot['fiscal_year'],
            'period' => $snapshot['period'],
            'latest_closed_period' => $snapshot['latest_closed_period'],
            'latest_actual_period' => $latestActualPeriod,
            'project_count' => $snapshot['project_count'],
            'answer_contract' => [
                'yearly_plan_totals' => '年間計画。財務年度全体の計画合計。',
                'latest_actual_month_totals' => '最新実績反映月の単月Google Sheets実績。最新実績（YYYY-MM）と書く場合はこの値だけを使う。',
                'actual_to_date_totals' => '財務年度開始から最新実績反映月までのGoogle Sheets実績累計。',
                'forecast_totals' => '着地見込み。実績反映済み月はGoogle Sheets実績のみ、未反映の将来月はKintone損益を使用。実績がない反映済み月と完了後月はKintone損益や年間計画で補完しない。',
                'forecast_vs_yearly_plan' => '着地見込みと年間計画の差分。',
            ],
            'yearly_plan_totals' => $snapshot['totals']['yearly_plan'],
            'latest_actual_month_totals' => $latestActualMonthTotals,
            'actual_to_date_totals' => $snapshot['totals']['settlement'],
            'forecast_totals' => $snapshot['totals']['forecast'],
            'forecast_vs_yearly_plan' => $snapshot['variance_vs_plan'],
            'totals' => $snapshot['totals'],
            'variance_vs_plan' => $snapshot['variance_vs_plan'],
            'monthly_totals' => $snapshot['monthly_totals'],
            'risk_projects' => array_map(
                fn(array $project) => $this->compactProject($project, false),
                array_slice($snapshot['risk_projects'], 0, max(1, $limit))
            ),
            'data_status' => $snapshot['data_status'],
        ];
    }

    public function compactProject(array $project, bool $includeMonths = true): array
    {
        $out = [
            'project_id' => $project['project_id'],
            'project_name' => $project['project_name'],
            'completed_at' => $project['completed_at'] ?? null,
            'totals' => $project['totals'],
            'variance_vs_plan' => $project['variance_vs_plan'],
            'forecast_periods' => $project['forecast_periods'],
            'missing_settlement_periods' => $project['missing_settlement_periods'],
        ];

        if ($includeMonths) {
            $out['months'] = $project['months'];
        }

        return $out;
    }

    public function summaryAdjustedUnit(string $bucket, array $unit, string $projectName, Carbon $period): array
    {
        return $this->summaryUnit($bucket, $unit, $projectName, $period);
    }

    public function buildDataQualityReport(
        ?int $fiscalYear = null,
        array $projectIds = [],
        ?string $asOfPeriod = null,
        int $limit = 25
    ): array {
        $snapshot = $this->buildFiscalYearSnapshot($fiscalYear, $projectIds, $asOfPeriod, $limit);
        $latestActual = Carbon::createFromFormat('Y-m-d', $snapshot['latest_actual_period'] . '-01')->startOfMonth();
        $releasedPeriods = array_values(array_filter(
            $snapshot['period']['months'],
            fn(string $period) => Carbon::createFromFormat('Y-m-d', $period . '-01')->lessThanOrEqualTo($latestActual)
        ));

        $issues = [
            'missing_yearly_plan' => [],
            'missing_profit' => [],
            'missing_actual' => [],
            'forecast_from_profit' => [],
        ];

        $projectNames = [];
        foreach ($snapshot['projects'] as $project) {
            $projectNames[(int) $project['project_id']] = (string) $project['project_name'];
            $completedAt = ! empty($project['completed_at'])
                ? Carbon::parse($project['completed_at'])->startOfMonth()
                : null;
            foreach ($project['months'] as $period => $monthData) {
                $periodMonth = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();
                if (empty($monthData['yearly_plan']['has_data'])) {
                    $issues['missing_yearly_plan'][] = $this->qualityIssue($project, $period, '年間計画がありません。');
                }

                if (empty($monthData['profit']['has_data'])) {
                    $issues['missing_profit'][] = $this->qualityIssue($project, $period, 'Kintone損益がありません。');
                }

                $isReleasedActualPeriod = in_array($period, $releasedPeriods, true);
                if (
                    $isReleasedActualPeriod
                    && ! $this->isAfterProjectCompletion($periodMonth, $completedAt)
                    && empty($monthData['settlement']['has_data'])
                ) {
                    $issues['missing_actual'][] = $this->qualityIssue($project, $period, 'Google Sheets実績が未反映または該当行なしです。');
                }

                if (($monthData['forecast']['source'] ?? null) === 'profit_forecast') {
                    $issues['forecast_from_profit'][] = $this->qualityIssue($project, $period, '実績がないため損益を予測値として使用しています。');
                }
            }
        }

        $duplicates = $this->fetchDuplicateProfitRecords(
            Carbon::createFromFormat('Y-m-d', $snapshot['period']['start'] . '-01')->startOfMonth(),
            Carbon::createFromFormat('Y-m-d', $snapshot['period']['end'] . '-01')->startOfMonth(),
            array_values($projectNames)
        );

        $issueCounts = array_map('count', $issues);
        foreach ($issues as $key => $rows) {
            $issues[$key] = array_slice($rows, 0, max(1, $limit));
        }

        return [
            'fiscal_year' => $snapshot['fiscal_year'],
            'period' => $snapshot['period'],
            'latest_actual_period' => $snapshot['latest_actual_period'],
            'scope' => 'finance_data_quality',
            'source_contract' => [
                'yearly_plan' => '年間計画',
                'profit' => 'Kintone損益',
                'actual' => 'Google Sheets実績',
                'forecast' => '実績反映済み月はGoogle Sheets実績のみ。未反映の将来月だけKintone損益を予測値として使用し、実績欠損済み月や完了後月は補完しない。',
            ],
            'summary' => [
                'project_count' => $snapshot['project_count'],
                'released_actual_periods' => $releasedPeriods,
                'missing_yearly_plan_count' => $issueCounts['missing_yearly_plan'] ?? 0,
                'missing_profit_count' => $issueCounts['missing_profit'] ?? 0,
                'missing_actual_count' => $issueCounts['missing_actual'] ?? 0,
                'forecast_from_profit_count' => $issueCounts['forecast_from_profit'] ?? 0,
                'duplicate_profit_record_count' => count($duplicates),
            ],
            'issues' => $issues,
            'duplicate_profit_records' => array_slice($duplicates, 0, max(1, $limit)),
        ];
    }

    private function qualityIssue(array $project, string $period, string $message): array
    {
        return [
            'project_id' => $project['project_id'],
            'project_name' => $project['project_name'],
            'period' => $period,
            'message' => $message,
        ];
    }

    private function projectsForPeriod(Carbon $start, array $projectIds): Collection
    {
        return ProjectRecord::query()
            ->select('id', 'name', 'completed_at')
            ->when($projectIds !== [], fn($q) => $q->whereIn('id', $projectIds))
            ->where(function ($q) use ($start) {
                $q->whereNull('completed_at')
                    ->orWhereDate('completed_at', '>=', $start->toDateString());
            })
            ->orderBy('name')
            ->get();
    }

    private function monthKeys(Carbon $start, Carbon $end): array
    {
        $months = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addMonth()) {
            $months[] = $cursor->format('Y-m');
        }

        return $months;
    }

    private function fetchYearlyPlans(Collection $projects, int $fiscalYear): array
    {
        $filePath = storage_path("app/yearly_plan/{$fiscalYear}.xlsx");
        if (file_exists($filePath)) {
            return $this->fetchYearlyPlansFromExcel($projects, $fiscalYear, $filePath);
        }

        return $this->fetchYearlyPlansFromDatabase($projects, $fiscalYear);
    }

    private function fetchYearlyPlansFromDatabase(Collection $projects, int $fiscalYear): array
    {
        $planYear = ProjectPlanYear::query()
            ->where('fiscal_year', $fiscalYear)
            ->where('start_month', self::FINANCE_FISCAL_START_MONTH)
            ->first();

        if (! $planYear) {
            return [];
        }

        $monthMap = collect(range(1, 12))->mapWithKeys(function (int $month) use ($fiscalYear) {
            return [
                $month => sprintf(
                    '%04d-%02d',
                    $month < self::FINANCE_FISCAL_START_MONTH ? $fiscalYear + 1 : $fiscalYear,
                    $month
                ),
            ];
        })->all();

        $out = [];
        foreach ($projects as $project) {
            $balances = $this->planFormulaService->buildMonthlyBalance(
                (int) $project->id,
                (int) $planYear->id,
                (int) $planYear->start_month,
                0,
                self::ACCOUNT_MAP,
                false,
                null
            );

            foreach ($balances as $month => $row) {
                $periodKey = $monthMap[(int) $month] ?? null;
                if (! $periodKey) {
                    continue;
                }

                $out[(int) $project->id][$periodKey] = $this->normalizeUnit([
                    'sales' => $row['sales'] ?? 0,
                    'expense' => $row['expense'] ?? 0,
                    'profit' => ($row['sales'] ?? 0) - ($row['expense'] ?? 0),
                    'profit_rate' => $row['profit_rate'] ?? null,
                    'has_data' => true,
                    'source' => 'yearly_plan_db',
                ]);
            }
        }

        return $out;
    }

    private function fetchYearlyPlansFromExcel(Collection $projects, int $fiscalYear, string $filePath): array
    {
        $sheet = Excel::toCollection(new YearlyPlanImport(), $filePath)[0] ?? collect();
        if ($sheet->count() < 3) {
            return [];
        }

        $sheet->shift();
        $monthHeaders = $sheet->shift()?->toArray() ?? [];
        $subHeaders = $sheet->shift()?->toArray() ?? [];
        $monthIndexMap = $this->buildExcelMonthIndexMap($monthHeaders, $fiscalYear);
        $projectRows = [];

        foreach ($sheet as $row) {
            $projectName = (string) ($row[1] ?? '');
            if ($projectName !== '') {
                $projectRows[self::projectNameKey($projectName)][] = $row;
            }
        }

        $out = [];
        foreach ($projects as $project) {
            $rows = $projectRows[self::projectNameKey((string) $project->name)] ?? [];
            foreach ($monthIndexMap as $periodKey => $indexes) {
                if ($indexes === []) {
                    continue;
                }

                $relativeHeaders = [];
                foreach ($indexes as $relative => $absolute) {
                    $relativeHeaders[$relative] = $subHeaders[$absolute] ?? null;
                }

                $columns = [
                    'sales_1' => $this->findAbsoluteColumn('合計 売上高', $relativeHeaders, $indexes),
                    'sales_2' => $this->findAbsoluteColumn('合計 内部売上高合計', $relativeHeaders, $indexes),
                    'exp_1' => $this->findAbsoluteColumn('合計 給料手当', $relativeHeaders, $indexes),
                    'exp_2' => $this->findAbsoluteColumn('合計 外注費', $relativeHeaders, $indexes),
                    'exp_3' => $this->findAbsoluteColumn('合計 販管費その他', $relativeHeaders, $indexes),
                    'exp_4' => $this->findAbsoluteColumn('合計 間接費配賦', $relativeHeaders, $indexes),
                    'exp_5' => $this->findAbsoluteColumn('合計 内部発注合計', $relativeHeaders, $indexes),
                    'exp_6' => $this->findAbsoluteColumn('業績連動型賞与引当金', $relativeHeaders, $indexes),
                    'profit' => $this->findAbsoluteColumn('利益', $relativeHeaders, $indexes),
                    'profit_rate' => $this->findAbsoluteColumn('利益率', $relativeHeaders, $indexes),
                ];

                $sales = 0.0;
                $expense = 0.0;
                $profit = 0.0;
                $profitRate = null;
                foreach ($rows as $row) {
                    $sales += $this->numberAt($row, $columns['sales_1']) + $this->numberAt($row, $columns['sales_2']);
                    $expense += $this->numberAt($row, $columns['exp_1'])
                        + $this->numberAt($row, $columns['exp_2'])
                        + $this->numberAt($row, $columns['exp_3'])
                        + $this->numberAt($row, $columns['exp_4'])
                        + $this->numberAt($row, $columns['exp_5'])
                        + $this->numberAt($row, $columns['exp_6']);
                    $profit += $this->numberAt($row, $columns['profit']);
                    $profitRate = $columns['profit_rate'] !== null ? $this->numberAt($row, $columns['profit_rate']) : $profitRate;
                }

                $out[(int) $project->id][$periodKey] = $this->normalizeUnit([
                    'sales' => $sales,
                    'expense' => $expense,
                    'profit' => $profit,
                    'profit_rate' => $profitRate,
                    'has_data' => $rows !== [],
                    'source' => 'yearly_plan_excel',
                ]);
            }
        }

        return $out;
    }

    private function buildExcelMonthIndexMap(array $monthHeaders, int $fiscalYear): array
    {
        [$start, $end] = $this->fiscalPeriod($fiscalYear);
        $labels = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addMonth()) {
            $labels[sprintf('%d年%d月', $cursor->year, $cursor->month)] = $cursor->format('Y-m');
        }

        $map = array_fill_keys(array_values($labels), []);
        $currentKey = null;
        foreach ($monthHeaders as $absoluteIndex => $header) {
            if (is_string($header)) {
                $label = preg_replace('/\s+/', '', $header);
                if (isset($labels[$label])) {
                    $currentKey = $labels[$label];
                    $map[$currentKey][] = $absoluteIndex;
                    continue;
                }
            }

            if ($currentKey !== null && ($header === null || $header === '')) {
                $map[$currentKey][] = $absoluteIndex;
                continue;
            }

            $currentKey = null;
        }

        return $map;
    }

    private function findAbsoluteColumn(string $label, array $relativeHeaders, array $indexes): ?int
    {
        $relative = array_search($label, $relativeHeaders, true);

        return $relative === false ? null : ($indexes[$relative] ?? null);
    }

    private function fetchProfitData(Carbon $start, Carbon $end, array $projectNames): array
    {
        $fields = ['売上高合計', '内部売上高合計', '販売管理費合計', '間接費配賦', '利益', '利益率', '部門', '日付', '業績連動賞与積立金'];
        $startDate = $start->copy()->startOfMonth()->toDateString();
        $endDate = $end->copy()->endOfMonth()->toDateString();
        $projectKeys = $this->projectKeySet($projectNames);
        $out = [];
        $offset = 0;
        $limit = 500;

        do {
            $records = $this->kintone->getRecords(
                1068,
                sprintf('日付 >= "%s" and 日付 <= "%s" limit %d offset %d', $startDate, $endDate, $limit, $offset),
                $fields
            );

            foreach ($records as $record) {
                $projectName = (string) ($record['部門']['value'] ?? '');
                $projectKey = self::projectNameKey($projectName);
                if ($projectName === '' || ($projectKeys !== [] && ! isset($projectKeys[$projectKey]))) {
                    continue;
                }

                $date = (string) ($record['日付']['value'] ?? '');
                if ($date === '') {
                    continue;
                }

                $periodKey = Carbon::parse($date)->format('Y-m');
                if (isset($out[$projectKey][$periodKey])) {
                    continue;
                }

                $sales = $this->toNumber($record['売上高合計']['value'] ?? 0)
                    + $this->toNumber($record['内部売上高合計']['value'] ?? 0);
                $expense = $this->toNumber($record['販売管理費合計']['value'] ?? 0)
                    + $this->toNumber($record['間接費配賦']['value'] ?? 0)
                    + $this->toNumber($record['業績連動賞与積立金']['value'] ?? 0);

                $out[$projectKey][$periodKey] = $this->normalizeUnit([
                    'sales' => $sales,
                    'expense' => $expense,
                    'profit' => $sales - $expense,
                    'profit_rate' => $this->toNullableNumber($record['利益率']['value'] ?? null),
                    'has_data' => true,
                    'source' => 'profit',
                    'source_project_name' => $projectName,
                ]);
            }

            $count = count($records);
            $offset += $limit;
        } while ($count === $limit);

        return $out;
    }

    private function projectKeySet(array $projectNames): array
    {
        $keys = [];
        foreach ($projectNames as $name) {
            $key = self::projectNameKey((string) $name);
            if ($key !== '') {
                $keys[$key] = true;
            }
        }

        return $keys;
    }

    private function summaryUnit(string $bucket, array $unit, string $projectName, Carbon $period): array
    {
        $shouldNet = match ($bucket) {
            'yearly_plan', 'profit' => $this->shouldNetForSummary($projectName, $period),
            'settlement' => $this->shouldNetActualSettlementForSummary($projectName),
            'forecast' => ($unit['source'] ?? null) === 'settlement'
                ? $this->shouldNetActualSettlementForSummary($projectName)
                : $this->shouldNetForSummary($projectName, $period),
            default => false,
        };

        if (! $shouldNet) {
            return $unit;
        }

        return $this->netSalesAgainstExpense($unit);
    }

    private function shouldNetForSummary(string $projectName, Carbon $period): bool
    {
        $projectKey = self::projectNameKey($projectName);

        if (in_array($projectKey, array_map([self::class, 'projectNameKey'], ['間接費部門', '積立部門']), true)) {
            return true;
        }

        $rangeStart = Carbon::create(2025, 3, 1)->startOfMonth();
        $rangeEnd = Carbon::create(2026, 2, 1)->startOfMonth();

        return $projectKey === self::projectNameKey('経営管理本部')
            && $period->copy()->startOfMonth()->betweenIncluded($rangeStart, $rangeEnd);
    }

    private function shouldNetActualSettlementForSummary(string $projectName): bool
    {
        return in_array(self::projectNameKey($projectName), array_map([self::class, 'projectNameKey'], ['間接費部門', '積立部門']), true);
    }

    private function netSalesAgainstExpense(array $unit): array
    {
        $sales = (float) ($unit['sales'] ?? 0);
        $expense = (float) ($unit['expense'] ?? 0);

        return array_merge($unit, [
            'sales' => 0,
            'expense' => $expense - $sales,
            'profit' => $sales - $expense,
            'profit_rate' => null,
            'summary_adjusted' => true,
        ]);
    }

    private function fetchDuplicateProfitRecords(Carbon $start, Carbon $end, array $projectNames): array
    {
        $fields = ['$id', '部門', '日付'];
        $startDate = $start->copy()->startOfMonth()->toDateString();
        $endDate = $end->copy()->endOfMonth()->toDateString();
        $projectKeys = $this->projectKeySet($projectNames);
        $counts = [];
        $offset = 0;
        $limit = 500;

        do {
            $records = $this->kintone->getRecords(
                1068,
                sprintf('日付 >= "%s" and 日付 <= "%s" limit %d offset %d', $startDate, $endDate, $limit, $offset),
                $fields
            );

            foreach ($records as $record) {
                $projectName = (string) ($record['部門']['value'] ?? '');
                $projectKey = self::projectNameKey($projectName);
                $date = (string) ($record['日付']['value'] ?? '');
                if ($projectName === '' || $date === '') {
                    continue;
                }
                if ($projectKeys !== [] && ! isset($projectKeys[$projectKey])) {
                    continue;
                }

                $period = Carbon::parse($date)->format('Y-m');
                $key = $projectKey . '|' . $period;
                $counts[$key]['project_name'] = $projectName;
                $counts[$key]['period'] = $period;
                $counts[$key]['record_ids'][] = (string) ($record['$id']['value'] ?? '');
            }

            $count = count($records);
            $offset += $limit;
        } while ($count === $limit);

        $duplicates = [];
        foreach ($counts as $row) {
            $recordIds = array_values(array_filter($row['record_ids']));
            if (count($recordIds) <= 1) {
                continue;
            }

            $duplicates[] = [
                'project_name' => $row['project_name'],
                'period' => $row['period'],
                'count' => count($recordIds),
                'record_ids' => $recordIds,
                'message' => '同じプロジェクト・月のKintone損益レコードが複数あります。月次回答では最初のレコードを使用します。',
            ];
        }

        usort($duplicates, fn(array $a, array $b) => $b['count'] <=> $a['count']);

        return $duplicates;
    }

    private function fetchSettlementData(Carbon $start, Carbon $end, array $projectNames): array
    {
        $sheetId = config('services.google.spreadsheet_id');
        if (! $sheetId) {
            return [];
        }
        $projectKeys = $this->projectKeySet($projectNames);

        $neededRanges = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addMonth()) {
            $neededRanges[] = $cursor->format('Ym');
        }

        $spreadsheet = $this->sheets->svc->spreadsheets->get($sheetId);
        $existingRanges = [];
        foreach ($spreadsheet->getSheets() as $sheet) {
            $title = $sheet['properties']['title'] ?? null;
            if ($title && in_array($title, $neededRanges, true)) {
                $existingRanges[] = $title;
            }
        }

        if ($existingRanges === []) {
            return [];
        }

        $response = $this->sheets->svc->spreadsheets_values->batchGet($sheetId, [
            'ranges' => $existingRanges,
            'valueRenderOption' => 'UNFORMATTED_VALUE',
        ]);

        $out = [];
        foreach ($response->getValueRanges() as $index => $range) {
            $title = $existingRanges[$index] ?? null;
            $rows = $range->getValues() ?? [];
            if (! $title || count($rows) < 3) {
                continue;
            }

            $periodKey = substr($title, 0, 4) . '-' . substr($title, 4, 2);
            $headers = $rows[1] ?? [];
            $dataRows = array_slice($rows, 2);
            $projectIndex = 1;
            $salesIndex = array_search('収入', $headers, true);
            $expenseIndex = array_search('支出', $headers, true);
            $overheadIndex = array_search('間接費配賦', $headers, true);
            $profitIndex = array_search('利益', $headers, true);
            $profitRateIndex = array_search('利益率', $headers, true);

            foreach ($dataRows as $row) {
                $projectName = (string) ($row[$projectIndex] ?? '');
                $projectKey = self::projectNameKey($projectName);
                if ($projectName === '' || ($projectKeys !== [] && ! isset($projectKeys[$projectKey]))) {
                    continue;
                }

                if (isset($out[$projectKey][$periodKey])) {
                    continue;
                }

                $expense = $this->numberAt($row, $expenseIndex) + $this->numberAt($row, $overheadIndex);
                // Pass keepProfit=true so the sheet's own 利益 column value is preserved
                // instead of being recalculated from (収入 - 支出 - 間接費配賦). The sheet
                // may include additional deductions (e.g. bonus reserves) that our expense
                // sum does not capture, making the sheet's profit the authoritative figure.
                $out[$projectKey][$periodKey] = $this->normalizeUnit([
                    'sales' => round($this->numberAt($row, $salesIndex), 0, PHP_ROUND_HALF_UP),
                    'expense' => $expense,
                    'profit' => round($this->numberAt($row, $profitIndex), 0, PHP_ROUND_HALF_UP),
                    'profit_rate' => $profitRateIndex !== false ? $this->toNullableNumber($row[$profitRateIndex] ?? null) : null,
                    'has_data' => true,
                    'source' => 'settlement',
                    'source_project_name' => $projectName,
                ], keepProfit: true, roundValues: false);
            }
        }

        return $out;
    }

    private function forecastUnit(
        array $profit,
        array $settlement,
        Carbon $period,
        Carbon $latestClosed,
        ?Carbon $completedAt
    ): array
    {
        if (! empty($settlement['has_data'])) {
            return array_merge($settlement, ['source' => 'settlement', 'is_forecast' => false]);
        }

        if ($this->isAfterProjectCompletion($period, $completedAt)) {
            return array_merge($this->emptyUnit(false), ['source' => 'project_completed', 'is_forecast' => false]);
        }

        if ($period->lessThanOrEqualTo($latestClosed)) {
            return array_merge($this->emptyUnit(false), ['source' => 'missing_released_actual', 'is_forecast' => false]);
        }

        if (! empty($profit['has_data'])) {
            return array_merge($profit, ['source' => 'profit_forecast', 'is_forecast' => true]);
        }

        return array_merge($this->emptyUnit(false), ['source' => 'missing_actual_and_profit', 'is_forecast' => true]);
    }

    private function isAfterProjectCompletion(Carbon $period, ?Carbon $completedAt): bool
    {
        return $completedAt !== null
            && $period->copy()->startOfMonth()->greaterThan($completedAt->copy()->startOfMonth());
    }

    private function shouldExpectActual(Carbon $period, Carbon $latestClosed, ?Carbon $completedAt): bool
    {
        return $period->lessThanOrEqualTo($latestClosed)
            && ! $this->isAfterProjectCompletion($period, $completedAt);
    }

    private function variance(array $actual, array $plan): array
    {
        return [
            'sales_amount' => (int) round(($actual['sales'] ?? 0) - ($plan['sales'] ?? 0)),
            'expense_amount' => (int) round(($actual['expense'] ?? 0) - ($plan['expense'] ?? 0)),
            'profit_amount' => (int) round(($actual['profit'] ?? 0) - ($plan['profit'] ?? 0)),
            'sales_pct' => $this->variancePercent($actual['sales'] ?? null, $plan['sales'] ?? null),
            'expense_pct' => $this->variancePercent($actual['expense'] ?? null, $plan['expense'] ?? null),
            'profit_pct' => $this->variancePercent($actual['profit'] ?? null, $plan['profit'] ?? null),
        ];
    }

    private function variancePercent(?float $actual, ?float $plan): ?float
    {
        if ($actual === null || $plan === null || $plan == 0.0) {
            return null;
        }

        return round((($actual / $plan) * 100) - 100, 2);
    }

    private function addUnit(array $left, array $right): array
    {
        $unit = [
            'sales'      => ($left['sales']   ?? 0) + ($right['sales']   ?? 0),
            'expense'    => ($left['expense'] ?? 0) + ($right['expense'] ?? 0),
            'profit'     => ($left['profit']  ?? 0) + ($right['profit']  ?? 0),
            'has_data'   => ! empty($left['has_data'])   || ! empty($right['has_data']),
            'is_forecast'=> ! empty($left['is_forecast'])|| ! empty($right['is_forecast']),
        ];

        // Keep raw sums during aggregation. ProjectTotalFinance totals round at the end,
        // so rounding each project/month here creates small yen-level drift.
        return $this->finalizeUnit($unit, keepProfit: true, roundValues: false);
    }

    private function finalizeUnit(array $unit, bool $keepProfit = false, bool $roundValues = true): array
    {
        $sales = (float) ($unit['sales'] ?? 0);
        $expense = (float) ($unit['expense'] ?? 0);
        // When $keepProfit is true (e.g. Google Sheets settlement where the sheet's own
        // 利益 column may include bonus-reserve deductions not captured in our expense sum),
        // preserve the source profit value instead of recalculating it.
        $profit = ($keepProfit && isset($unit['profit']) && $unit['profit'] !== null)
            ? (float) $unit['profit']
            : $sales - $expense;

        return array_merge($unit, [
            'sales' => $roundValues ? (int) round($sales, 0, PHP_ROUND_HALF_UP) : $sales,
            'expense' => $roundValues ? (int) round($expense, 0, PHP_ROUND_HALF_UP) : $expense,
            'profit' => $roundValues ? (int) round($profit, 0, PHP_ROUND_HALF_UP) : $profit,
            'profit_rate' => $sales !== 0.0 ? round(($profit / $sales) * 100, 2, PHP_ROUND_HALF_UP) : null,
        ]);
    }

    private function normalizeUnit(array $unit, bool $keepProfit = false, bool $roundValues = true): array
    {
        return $this->finalizeUnit(array_merge([
            'sales' => 0,
            'expense' => 0,
            'profit' => null,
            'profit_rate' => null,
            'has_data' => false,
            'is_forecast' => false,
            'source' => null,
        ], $unit), $keepProfit, $roundValues);
    }

    private function emptyUnit(bool $hasData = false): array
    {
        return [
            'sales' => 0,
            'expense' => 0,
            'profit' => 0,
            'profit_rate' => null,
            'has_data' => $hasData,
            'is_forecast' => false,
            'source' => null,
        ];
    }

    private function numberAt(mixed $row, int|false|null $index): float
    {
        if ($index === false || $index === null) {
            return 0.0;
        }

        return $this->toNumber($row[$index] ?? null);
    }

    private function toNullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->toNumber($value);
    }

    private function toNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_string($value)) {
            $value = str_replace([',', '%'], '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    // =========================================================================
    // Helpers used by new tools
    // =========================================================================

    /**
     * Returns the fiscal quarter label (Q1–Q4) and month range for a given Carbon date.
     * Fiscal year starts March, so:
     *   Q1 = Mar–May, Q2 = Jun–Aug, Q3 = Sep–Nov, Q4 = Dec–Feb
     */
    public function fiscalQuarterOf(Carbon $date): array
    {
        $month = $date->month;
        if ($month >= 3 && $month <= 5)  return ['q' => 1, 'label' => 'Q1', 'months' => ['03','04','05']];
        if ($month >= 6 && $month <= 8)  return ['q' => 2, 'label' => 'Q2', 'months' => ['06','07','08']];
        if ($month >= 9 && $month <= 11) return ['q' => 3, 'label' => 'Q3', 'months' => ['09','10','11']];
        return                                  ['q' => 4, 'label' => 'Q4', 'months' => ['12','01','02']];
    }

    /**
     * Returns how many fiscal months have elapsed through (and including) the given period.
     * Period format: YYYY-MM.
     */
    public function monthsElapsed(string $fiscalStartPeriod, string $asOfPeriod): int
    {
        $start = Carbon::createFromFormat('Y-m-d', $fiscalStartPeriod . '-01')->startOfMonth();
        $asOf  = Carbon::createFromFormat('Y-m-d', $asOfPeriod . '-01')->startOfMonth();

        return (int) $start->diffInMonths($asOf) + 1;
    }

    /**
     * Forecast confidence level based on how many months still rely on Kintone (not settled actuals).
     */
    public function forecastConfidence(int $forecastMonthsFromKintone, int $totalFiscalMonths = 12): string
    {
        $ratio = $forecastMonthsFromKintone / $totalFiscalMonths;
        if ($ratio > 0.5) return 'low';
        if ($ratio > 0.25) return 'medium';

        return 'high';
    }

    /**
     * Budget utilisation rate: how much of the expected paced profit has been realised.
     * Returns null when plan or expected pace is zero.
     */
    public function budgetUtilizationRate(int $actualProfitToDate, int $planProfit, int $monthsElapsed): ?float
    {
        if ($planProfit == 0 || $monthsElapsed <= 0) return null;
        $expectedPace = $planProfit * ($monthsElapsed / 12);

        return round(($actualProfitToDate / $expectedPace) * 100, 1);
    }
}
