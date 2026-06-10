<?php

namespace App\Http\Controllers;

use App\Domain\Contracts\ActualProvider;
use App\Domain\Contracts\PlanProvider;
use App\Models\ProjectFinanceComment;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Services\FinanceSnapshotService;
use App\Services\VarianceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finance MCP — tools for 実績 vs 計画 variance and finance analysis.
 *
 * Tools:
 *  - get_variance_summary     : Rank all projects by plan vs actual gap for a given month
 *  - get_project_pl           : Monthly P&L breakdown for one project (full year)
 *  - get_fiscal_year_finance_summary : Full fiscal-year result/forecast vs plan
 *  - get_project_fiscal_year_pl      : Fiscal-year monthly P&L for one project
 *  - get_finance_forecast_ranking    : Projects with worst forecast profit gap
 */
class FinanceToolController extends Controller
{
    public function __construct(
        private PlanProvider   $plans,
        private ActualProvider $actuals,
        private FinanceSnapshotService $financeSnapshots,
    ) {}

    // =========================================================================
    // Public dispatcher — used by FinanceChatController
    // =========================================================================

    public function executeTool(string $name, array $args): array
    {
        return match ($name) {
            'get_variance_summary'            => $this->toolGetVarianceSummary($args),
            'get_project_pl'                  => $this->toolGetProjectPl($args),
            'get_fiscal_year_finance_summary' => $this->toolGetFiscalYearFinanceSummary($args),
            'get_project_fiscal_year_pl'      => $this->toolGetProjectFiscalYearPl($args),
            'get_project_variance_explanation' => $this->toolGetProjectVarianceExplanation($args),
            'get_finance_forecast_ranking'    => $this->toolGetFinanceForecastRanking($args),
            'get_finance_data_quality'        => $this->toolGetFinanceDataQuality($args),
            // New tools
            'get_monthly_trend'               => $this->toolGetMonthlyTrend($args),
            'get_project_health_matrix'       => $this->toolGetProjectHealthMatrix($args),
            'get_revenue_concentration'       => $this->toolGetRevenueConcentration($args),
            'compare_fiscal_years'            => $this->toolCompareFiscalYears($args),
            'get_pm_finance_summary'          => $this->toolGetPmFinanceSummary($args),
            'get_pm_finance_ranking'          => $this->toolGetPmFinanceRanking($args),
            default                           => ['error' => "Unknown finance tool: {$name}"],
        };
    }

    // =========================================================================
    // TOOL: get_variance_summary
    // =========================================================================

    public function toolGetVarianceSummary(array $args): array
    {
        // Defaults to the latest Google Sheets 実績 release month.
        // Example: May 1 => March, May 20 => April.
        $defaultPeriod = $this->financeSnapshots->latestClosedPeriod();
        $year  = (int) ($args['year']  ?? $defaultPeriod->year);
        $month = isset($args['month']) ? (int) $args['month'] : (int) $defaultPeriod->month;

        $period    = CarbonImmutable::create($year, $month, 1)->endOfMonth();
        $threshold = (float) config('app.variance_threshold', env('VARIANCE_ALERT_THRESHOLD', 10));
        $periodKey = $period->format('Y-m');
        $fiscalYear = $period->month >= 3 ? $period->year : $period->year - 1;
        $limit = max(1, min((int) ($args['limit'] ?? 10), 50));

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot((int) $fiscalYear, [], $periodKey, 30);
        } catch (\Throwable $e) {
            return ['error' => '財務データの取得に失敗しました: ' . $e->getMessage()];
        }

        $rows = [];
        foreach ($snapshot['projects'] ?? [] as $project) {
            $monthData = $project['months'][$periodKey] ?? null;
            if (! $monthData) {
                continue;
            }

            $plan = $monthData['profit'] ?? null;      // 損益 row in finance table
            $act  = $monthData['settlement'] ?? null;  // Google Sheets 実績 row
            if (! $act || empty($act['has_data'])) {
                continue;
            }

            $v = [
                'sales'    => VarianceService::achToVar(VarianceService::pct($act['sales']    ?? null, $plan['sales']    ?? null)),
                'expenses' => VarianceService::achToVar(VarianceService::pct($act['expense'] ?? null, $plan['expense'] ?? null)),
                'profit'   => VarianceService::achToVar(VarianceService::pct($act['profit']   ?? null, $plan['profit']   ?? null)),
            ];
            $thresholdVariance = [
                'sales' => $v['sales'],
                'expense' => $v['expenses'],
                'profit' => $v['profit'],
            ];

            $amountGap = [
                'sales'    => $this->amountGap($act['sales'] ?? null, $plan['sales'] ?? null),
                'expenses' => $this->amountGap($act['expense'] ?? null, $plan['expense'] ?? null),
                'profit'   => $this->amountGap($act['profit'] ?? null, $plan['profit'] ?? null),
            ];
            $maxAmountGap = collect($amountGap)->filter(fn ($x) => $x !== null)->map(fn ($x) => abs($x))->max() ?? 0;
            $maxVar = collect($v)->filter(fn ($x) => $x !== null)->map(fn ($x) => abs($x))->max() ?? 0;

            $rows[] = [
                'project_id'   => $project['project_id'],
                'project_name' => $project['project_name'],
                'period'       => $periodKey,
                'comparison_base' => '損益',
                'actual_source' => 'Google Sheets 実績',
                'plan'         => $plan,
                'profit_plan'  => $plan,
                'actual'       => $act,
                'amount_gap'   => $amountGap,
                'variance'     => $v,
                'max_amount_gap' => round($maxAmountGap, 0),
                'max_variance' => round($maxVar, 2),
                'alert'        => VarianceService::anyOverThreshold($thresholdVariance, $threshold),
            ];
        }

        // Sort by absolute yen gap descending for "largest gap" questions.
        usort($rows, fn ($a, $b) => $b['max_amount_gap'] <=> $a['max_amount_gap']);

        $alertRows = array_filter($rows, fn ($r) => $r['alert']);

        return [
            'period'      => $periodKey,
            'period_basis' => 'Google Sheets 実績反映月（毎月20日ルール）',
            'comparison_base' => '損益',
            'scope' => 'single_month',
            'threshold'   => $threshold,
            'total'       => count($rows),
            'alert_count' => count($alertRows),
            'projects'    => array_slice(array_values($rows), 0, $limit),
        ];
    }

    // =========================================================================
    // TOOL: get_project_pl
    // =========================================================================

    public function toolGetProjectPl(array $args): array
    {
        $projectName = $args['project_name'] ?? null;
        $projectId   = isset($args['project_id']) ? (int) $args['project_id'] : null;
        $year        = (int) ($args['year'] ?? now()->year);

        // Resolve project
        if ($projectId) {
            $project = ProjectRecord::find($projectId);
        } elseif ($projectName) {
            $project = $this->findProjectByName((string) $projectName);
        } else {
            return ['error' => 'project_id または project_name を指定してください。'];
        }

        if (!$project) {
            return ['error' => 'プロジェクトが見つかりません。'];
        }

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $period = CarbonImmutable::create($year, $m, 1)->endOfMonth();
            // Skip future months beyond current
            if ($period->isFuture() && $period->month !== now()->month) {
                $months[] = [
                    'month'   => $period->format('Y-m'),
                    'plan'    => null,
                    'actual'  => null,
                    'variance'=> null,
                ];
                continue;
            }

            try {
                $P = $this->plans->fetchMonthlyPlans($period, [$project->name]);
                $A = $this->actuals->fetchMonthlyActuals($period, [$project->name]);
            } catch (\Throwable) {
                $months[] = ['month' => $period->format('Y-m'), 'plan' => null, 'actual' => null, 'variance' => null];
                continue;
            }

            $plan = $P[$project->name] ?? null;
            $act  = $A[$project->name] ?? null;

            $variance = null;
            if ($plan && $act) {
                $variance = [
                    'sales'    => VarianceService::achToVar(VarianceService::pct($act['sales']    ?? null, $plan['sales']    ?? null)),
                    'expenses' => VarianceService::achToVar(VarianceService::pct($act['expenses'] ?? null, $plan['expenses'] ?? null)),
                    'profit'   => VarianceService::achToVar(VarianceService::pct($act['profit']   ?? null, $plan['profit']   ?? null)),
                ];
            }

            $months[] = [
                'month'    => $period->format('Y-m'),
                'plan'     => $plan,
                'actual'   => $act,
                'variance' => $variance,
            ];
        }

        return [
            'project'  => ['id' => $project->id, 'name' => $project->name],
            'year'     => $year,
            'months'   => $months,
        ];
    }

    // =========================================================================
    // TOOL: get_fiscal_year_finance_summary
    // =========================================================================

    public function toolGetFiscalYearFinanceSummary(array $args): array
    {
        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $projectIds = $this->normalizeProjectIds($args['project_ids'] ?? []);
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);
        $limit = max(1, min((int) ($args['limit'] ?? 10), 30));

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, $projectIds, $asOfPeriod, $limit);
            $compact  = $this->financeSnapshots->compactFiscalSummary($snapshot, $limit);

            // -- Quick-win: budget utilization rate --
            $latestActual = $snapshot['latest_actual_period'];
            $monthsElapsed = $this->financeSnapshots->monthsElapsed(
                $snapshot['period']['start'],
                $latestActual
            );
            $planProfit   = (int) ($snapshot['totals']['yearly_plan']['profit'] ?? 0);
            $actualProfit = (int) ($snapshot['totals']['settlement']['profit'] ?? 0);
            $compact['budget_utilization'] = [
                'months_elapsed'            => $monthsElapsed,
                'expected_pace_pct'         => round(($monthsElapsed / 12) * 100, 1),
                'actual_vs_plan_pct'        => $this->financeSnapshots->budgetUtilizationRate($actualProfit, $planProfit, $monthsElapsed),
                'note'                      => '期首ペース対比。100%超えれば順調、100%未満なら実績が計画ペースより遅れています。',
            ];

            // -- Quick-win: current fiscal quarter context --
            $compact['current_quarter'] = $this->financeSnapshots->fiscalQuarterOf(Carbon::now());

            // -- Quick-win: forecast confidence for overall totals --
            $forecastPeriodCount = count($snapshot['data_status']['forecast_periods'] ?? []);
            $compact['forecast_confidence'] = $this->financeSnapshots->forecastConfidence($forecastPeriodCount);

            return $compact;
        } catch (\Throwable $e) {
            return ['error' => '年度財務サマリーの取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_project_fiscal_year_pl
    // =========================================================================

    public function toolGetProjectFiscalYearPl(array $args): array
    {
        $project = $this->resolveProject($args);
        if (is_array($project)) {
            return $project;
        }

        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, [(int) $project->id], $asOfPeriod, 1);
            $projectRow = $snapshot['projects'][0] ?? null;

            if (! $projectRow) {
                return ['error' => '対象プロジェクトの年度財務データがありません。'];
            }

            // Quick-win: last 5 finance comments for context
            $recentComments = ProjectFinanceComment::query()
                ->where('project_record_id', $project->id)
                ->with('author:id,name')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id'      => $c->id,
                    'period'  => $c->period,
                    'author'  => $c->author?->name,
                    'comment' => Str::limit($c->comment, 200),
                    'date'    => $c->created_at?->format('Y-m-d'),
                ])
                ->all();

            return [
                'fiscal_year'          => $snapshot['fiscal_year'],
                'period'               => $snapshot['period'],
                'latest_closed_period' => $snapshot['latest_closed_period'],
                'latest_actual_period' => $snapshot['latest_actual_period'] ?? $snapshot['latest_closed_period'],
                'answer_contract' => [
                    'totals.yearly_plan'                       => '指定プロジェクトの年間計画。',
                    'months.{latest_actual_period}.settlement' => '指定プロジェクトの最新実績反映月の単月Google Sheets実績。',
                    'totals.settlement'                        => '指定プロジェクトの実績累計。',
                    'totals.forecast'                          => '指定プロジェクトの着地見込み。get_total_financeと同じく、Google Sheets実績がある月は実績を使い、実績がない月はKintone損益を見込み値として使う。完了後月は補完しない。',
                    'variance_vs_plan'                         => '指定プロジェクトの着地見込みと年間計画の差分。',
                ],
                'project'         => $this->financeSnapshots->compactProject($projectRow, true),
                'recent_comments' => $recentComments,
                'data_status'     => $snapshot['data_status'],
            ];
        } catch (\Throwable $e) {
            return ['error' => 'プロジェクト年度P&Lの取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_project_variance_explanation
    // =========================================================================

    public function toolGetProjectVarianceExplanation(array $args): array
    {
        $project = $this->resolveProject($args);
        if (is_array($project)) {
            return $project;
        }

        $period = $this->normalizePeriod($args['period'] ?? null)
            ?? $this->financeSnapshots->latestClosedPeriod()->format('Y-m');
        $comparisonBase = in_array(($args['comparison_base'] ?? 'profit'), ['profit', 'yearly_plan'], true)
            ? (string) ($args['comparison_base'] ?? 'profit')
            : 'profit';
        $comparisonBaseLabel = $comparisonBase === 'yearly_plan'
            ? '年間予算'
            : 'Kintone損益計画';

        try {
            $periodDate = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();
            $fiscalYear = $periodDate->month >= 3 ? (int) $periodDate->year : (int) $periodDate->year - 1;
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot(
                $fiscalYear,
                [(int) $project->id],
                $period,
                1
            );
            $projectRow = collect($snapshot['projects'] ?? [])
                ->first(fn (array $row) => (int) ($row['project_id'] ?? 0) === (int) $project->id);

            if (! $projectRow) {
                return ['error' => '対象プロジェクトの財務データがありません。'];
            }

            $monthData = $projectRow['months'][$period] ?? null;
            if (! $monthData) {
                return ['error' => '対象月の財務データがありません。'];
            }

            $actual = $monthData['settlement'] ?? $this->emptyFinanceUnit();
            $profitPlan = $monthData['profit'] ?? $this->emptyFinanceUnit();
            $yearlyPlan = $monthData['yearly_plan'] ?? $this->emptyFinanceUnit();
            $plan = $comparisonBase === 'yearly_plan' ? $yearlyPlan : $profitPlan;
            $actualHasData = ! empty($actual['has_data']);
            $planHasData = ! empty($plan['has_data']);
            $variance = ($actualHasData && $planHasData)
                ? $this->financeVarianceForChat($actual, $plan)
                : null;
            $comments = $this->financeCommentsForProjectPeriod((int) $project->id, $period);

            return [
                'scope' => 'project_month_variance_explanation',
                'project' => [
                    'id' => (int) $project->id,
                    'name' => (string) $project->name,
                ],
                'period' => $period,
                'fiscal_year' => $snapshot['fiscal_year'],
                'latest_actual_period' => $snapshot['latest_actual_period'] ?? $snapshot['latest_closed_period'],
                'comparison_base' => $comparisonBase,
                'comparison_base_label' => $comparisonBaseLabel,
                'actual_source' => 'Google Sheets 実績',
                'answer_contract' => [
                    'actual' => '指定プロジェクト・指定月のGoogle Sheets実績。',
                    'plan' => "指定プロジェクト・指定月の{$comparisonBaseLabel}。",
                    'variance_actual_vs_plan' => "Google Sheets実績 - {$comparisonBaseLabel}。",
                    'comments' => '指定プロジェクト・指定月のproject_finance_comments。コメントがある場合だけ差異理由の根拠にする。',
                ],
                'actual_data_available' => $actualHasData,
                'plan_data_available' => $planHasData,
                'actual' => $this->compactFinanceUnitForChat($actual),
                'plan' => $this->compactFinanceUnitForChat($plan),
                'profit_plan' => $this->compactFinanceUnitForChat($profitPlan),
                'yearly_plan' => $this->compactFinanceUnitForChat($yearlyPlan),
                'variance_actual_vs_plan' => $variance,
                'largest_gap_metric' => $variance ? $this->largestVarianceMetric($variance) : null,
                'comments_exist' => count($comments) > 0,
                'comments' => $comments,
                'guidance' => [
                    'with_comments' => 'コメントがある場合は「コメントでは」「記録では」と明示して差異理由を説明する。',
                    'without_comments' => 'コメントがない場合は理由を推測せず、数値上どこに差異があるかだけ説明し、要確認と伝える。',
                ],
            ];
        } catch (\Throwable $e) {
            return ['error' => 'プロジェクト月次差異理由の取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_finance_forecast_ranking
    // =========================================================================

    public function toolGetFinanceForecastRanking(array $args): array
    {
        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $projectIds = $this->normalizeProjectIds($args['project_ids'] ?? []);
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);
        $limit = max(1, min((int) ($args['limit'] ?? 10), 30));

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, $projectIds, $asOfPeriod, $limit);

            return [
                'fiscal_year' => $snapshot['fiscal_year'],
                'period' => $snapshot['period'],
                'latest_closed_period' => $snapshot['latest_closed_period'],
                'latest_actual_period' => $snapshot['latest_actual_period'] ?? $snapshot['latest_closed_period'],
                'ranking_basis' => 'forecast profit gap vs yearly plan, worst first',
                'yearly_plan_totals' => $snapshot['totals']['yearly_plan'],
                'actual_to_date_totals' => $snapshot['totals']['settlement'],
                'forecast_totals' => $snapshot['totals']['forecast'],
                'forecast_vs_yearly_plan' => $snapshot['variance_vs_plan'],
                'totals' => $snapshot['totals'],
                'variance_vs_plan' => $snapshot['variance_vs_plan'],
                'projects' => array_map(
                    fn(array $project) => $this->financeSnapshots->compactProject($project, false),
                    array_slice($snapshot['risk_projects'], 0, $limit)
                ),
                'data_status' => $snapshot['data_status'],
            ];
        } catch (\Throwable $e) {
            return ['error' => '着地見込みランキングの取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_finance_data_quality
    // =========================================================================

    public function toolGetFinanceDataQuality(array $args): array
    {
        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $projectIds = $this->normalizeProjectIds($args['project_ids'] ?? []);
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);
        $limit = max(1, min((int) ($args['limit'] ?? 25), 100));

        try {
            return $this->financeSnapshots->buildDataQualityReport($fiscalYear, $projectIds, $asOfPeriod, $limit);
        } catch (\Throwable $e) {
            return ['error' => '財務データ品質チェックに失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_monthly_trend
    // =========================================================================

    /**
     * Returns the month-by-month evolution of the forecast vs plan gap.
     * Directors use this to see whether the fiscal year is improving or deteriorating
     * as each month's actuals replace forecast values.
     */
    public function toolGetMonthlyTrend(array $args): array
    {
        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $projectIds = $this->normalizeProjectIds($args['project_ids'] ?? []);
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, $projectIds, $asOfPeriod, 999);
            $months   = $snapshot['period']['months'];
            $mt       = $snapshot['monthly_totals'];

            $cumPlan     = 0;
            $cumForecast = 0;
            $prevCumGap  = null;
            $trend       = [];

            foreach ($months as $period) {
                $monthData = $mt[$period] ?? [];
                $planUnit     = $monthData['yearly_plan'] ?? ['profit' => 0];
                $actualUnit   = $monthData['settlement']  ?? ['profit' => 0, 'has_data' => false];
                $forecastUnit = $monthData['forecast']    ?? ['profit' => 0];

                $cumPlan     += (int) ($planUnit['profit'] ?? 0);
                $cumForecast += (int) ($forecastUnit['profit'] ?? 0);
                $cumGap       = $cumForecast - $cumPlan;
                $gapChange    = $prevCumGap !== null ? $cumGap - $prevCumGap : null;

                $trend[] = [
                    'period'              => $period,
                    'is_actual'           => ! empty($actualUnit['has_data']),
                    'is_forecast_month'   => ! empty($forecastUnit['is_forecast']),
                    'forecast_source'     => $forecastUnit['source'] ?? null,
                    'forecast_sources'    => $forecastUnit['source_counts'] ?? [],
                    'monthly' => [
                        'plan_profit'     => (int) ($planUnit['profit'] ?? 0),
                        'actual_profit'   => ! empty($actualUnit['has_data']) ? (int) ($actualUnit['profit'] ?? 0) : null,
                        'forecast_profit' => (int) ($forecastUnit['profit'] ?? 0),
                        'monthly_gap'     => (int) ($forecastUnit['profit'] ?? 0) - (int) ($planUnit['profit'] ?? 0),
                    ],
                    'cumulative' => [
                        'plan_profit'     => $cumPlan,
                        'forecast_profit' => $cumForecast,
                        'gap'             => $cumGap,
                        'gap_change'      => $gapChange,
                        'trend_signal'    => $gapChange === null ? null : ($gapChange > 0 ? 'improving' : ($gapChange < 0 ? 'deteriorating' : 'flat')),
                    ],
                ];

                $prevCumGap = $cumGap;
            }

            // Summarise direction: count improving vs deteriorating months (after first)
            $improving    = count(array_filter($trend, fn ($r) => ($r['cumulative']['gap_change'] ?? 0) > 0));
            $deteriorating = count(array_filter($trend, fn ($r) => ($r['cumulative']['gap_change'] ?? 0) < 0));
            $overallDirection = $improving > $deteriorating ? 'improving' : ($deteriorating > $improving ? 'deteriorating' : 'flat');

            return [
                'fiscal_year'          => $snapshot['fiscal_year'],
                'period'               => $snapshot['period'],
                'latest_actual_period' => $snapshot['latest_actual_period'],
                'overall_direction'    => $overallDirection,
                'trend'                => $trend,
                'data_status'          => $snapshot['data_status'],
            ];
        } catch (\Throwable $e) {
            return ['error' => '月次トレンドの取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_project_health_matrix
    // =========================================================================

    /**
     * Returns a traffic-light (🔴/🟡/🟢) overview of every project.
     * Includes PM name, forecast gap, budget utilisation pace, forecast confidence,
     * completing-this-quarter flag, missing actuals count, and the latest comment.
     */
    public function toolGetProjectHealthMatrix(array $args): array
    {
        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, [], $asOfPeriod, 999);

            $projectIds   = array_column($snapshot['projects'], 'project_id');
            $monthsElapsed = $this->financeSnapshots->monthsElapsed(
                $snapshot['period']['start'],
                $snapshot['latest_actual_period']
            );

            // Load PM names (authority=1 in project_members)
            $managerMap = ProjectRecord::query()
                ->whereIn('id', $projectIds)
                ->with(['manager' => fn ($q) => $q->select('users.id', 'users.name')])
                ->get()
                ->keyBy('id')
                ->map(fn ($p) => optional($p->manager->first())->name);

            // Load last comment per project
            $lastCommentMap = ProjectFinanceComment::query()
                ->whereIn('project_record_id', $projectIds)
                ->with('author:id,name')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('project_record_id')
                ->map(fn ($g) => $g->first());

            // Fiscal quarter end (for "completing this quarter" flag)
            $now              = Carbon::now();
            $quarterInfo      = $this->financeSnapshots->fiscalQuarterOf($now);
            $quarterLastMonth = end($quarterInfo['months']);
            $quarterYear      = (int) $quarterLastMonth < (int) $now->format('m')
                ? $now->year + 1 : $now->year;
            $quarterEnd       = Carbon::createFromFormat('Y-m', $quarterYear . '-' . $quarterLastMonth)->endOfMonth();

            $rows = [];
            foreach ($snapshot['projects'] as $project) {
                $pid          = $project['project_id'];
                $totals       = $project['totals'];
                $forecastProfit = (int) ($totals['forecast']['profit'] ?? 0);
                $planProfit     = (int) ($totals['yearly_plan']['profit'] ?? 0);
                $actualProfit   = (int) ($totals['settlement']['profit'] ?? 0);
                $gapAmount      = $forecastProfit - $planProfit;
                $gapPct         = $planProfit !== 0
                    ? round(($gapAmount / abs($planProfit)) * 100, 1)
                    : null;

                // Traffic light
                $color = 'green'; $label = '🟢';
                if ($gapPct !== null) {
                    if ($gapPct < -10) { $color = 'red';    $label = '🔴'; }
                    elseif ($gapPct < -3) { $color = 'yellow'; $label = '🟡'; }
                }
                // Escalate to yellow when actuals are missing in released months
                if ($color === 'green' && count($project['missing_settlement_periods']) > 0) {
                    $color = 'yellow'; $label = '🟡';
                }

                // Budget utilisation
                $utilPct = $this->financeSnapshots->budgetUtilizationRate($actualProfit, $planProfit, $monthsElapsed);

                // Forecast confidence
                $forecastMonths = count($project['forecast_periods']);
                $confidence     = $this->financeSnapshots->forecastConfidence($forecastMonths);

                // Completing this quarter?
                $completingThisQuarter = false;
                if ($project['completed_at']) {
                    $completedAt = Carbon::parse($project['completed_at']);
                    $completingThisQuarter = $completedAt->lte($quarterEnd)
                        && $completedAt->gte($now->copy()->startOfMonth());
                }

                $lastComment = $lastCommentMap->get($pid);

                $rows[] = [
                    'project_id'               => $pid,
                    'project_name'             => $project['project_name'],
                    'is_internal_cost_center'  => FinanceSnapshotService::isInternalCostCenter($project['project_name'] ?? null),
                    'pm'                       => $managerMap->get($pid),
                    'completed_at'             => $project['completed_at'],
                    'completing_this_quarter'  => $completingThisQuarter,
                    'color'                    => $color,
                    'label'                    => $label,
                    'forecast_profit'          => $forecastProfit,
                    'plan_profit'              => $planProfit,
                    'gap_amount'               => $gapAmount,
                    'gap_pct'                  => $gapPct,
                    'budget_utilization_pct'   => $utilPct,
                    'expected_utilization_pct' => round(($monthsElapsed / 12) * 100, 1),
                    'missing_actuals_count'    => count($project['missing_settlement_periods']),
                    'forecast_confidence'      => $confidence,
                    'forecast_months_from_kintone' => $forecastMonths,
                    'last_comment'             => $lastComment ? Str::limit($lastComment->comment, 120) : null,
                    'last_comment_date'        => $lastComment?->created_at?->format('Y-m-d'),
                    'last_comment_author'      => $lastComment?->author?->name,
                ];
            }

            // Sort: red → yellow → green, with internal cost centers after normal projects.
            $colorOrder = ['red' => 0, 'yellow' => 1, 'green' => 2];
            usort($rows, function ($a, $b) use ($colorOrder) {
                $oa = $colorOrder[$a['color']] ?? 3;
                $ob = $colorOrder[$b['color']] ?? 3;
                if ($oa !== $ob) return $oa <=> $ob;

                $aInternal = ! empty($a['is_internal_cost_center']);
                $bInternal = ! empty($b['is_internal_cost_center']);
                if ($aInternal !== $bInternal) return $aInternal <=> $bInternal;

                return ($a['gap_amount'] ?? 0) <=> ($b['gap_amount'] ?? 0);
            });

            return [
                'fiscal_year'              => $snapshot['fiscal_year'],
                'latest_actual_period'     => $snapshot['latest_actual_period'],
                'months_elapsed'           => $monthsElapsed,
                'expected_utilization_pct' => round(($monthsElapsed / 12) * 100, 1),
                'current_quarter'          => $quarterInfo,
                'summary' => [
                    'red_count'    => count(array_filter($rows, fn ($r) => $r['color'] === 'red')),
                    'yellow_count' => count(array_filter($rows, fn ($r) => $r['color'] === 'yellow')),
                    'green_count'  => count(array_filter($rows, fn ($r) => $r['color'] === 'green')),
                    'total'        => count($rows),
                    'completing_this_quarter_count' => count(array_filter($rows, fn ($r) => $r['completing_this_quarter'])),
                ],
                'color_thresholds' => ['red' => '計画比 -10% 未満', 'yellow' => '計画比 -10%〜-3%', 'green' => '計画比 -3% 以上'],
                'projects'         => $rows,
                'data_status'      => $snapshot['data_status'],
            ];
        } catch (\Throwable $e) {
            return ['error' => 'プロジェクト健全度マトリクスの取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_revenue_concentration
    // =========================================================================

    /**
     * Ranks projects by forecast revenue and computes concentration risk.
     * High concentration (top-3 > 40% of total) means single-client loss is a major risk.
     */
    public function toolGetRevenueConcentration(array $args): array
    {
        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);
        $limit      = max(1, min((int) ($args['limit'] ?? 10), 50));

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, [], $asOfPeriod, 999);

            $rows = [];
            foreach ($snapshot['projects'] as $p) {
                $revenue = (int) ($p['totals']['forecast']['sales'] ?? 0);
                if ($revenue <= 0) continue;
                $rows[] = ['project_id' => $p['project_id'], 'project_name' => $p['project_name'], 'forecast_revenue' => $revenue];
            }
            usort($rows, fn ($a, $b) => $b['forecast_revenue'] <=> $a['forecast_revenue']);

            $totalRevenue = (int) ($snapshot['totals']['forecast']['sales'] ?? 0);

            // Add rank + share
            $cumShare = 0.0;
            foreach ($rows as $i => &$row) {
                $row['rank']      = $i + 1;
                $row['share_pct'] = $totalRevenue > 0 ? round(($row['forecast_revenue'] / $totalRevenue) * 100, 1) : null;
                $cumShare        += $row['share_pct'] ?? 0;
                $row['cumulative_share_pct'] = round($cumShare, 1);
            }
            unset($row);

            $top3Share = collect($rows)->take(3)->sum('share_pct');
            $top5Share = collect($rows)->take(5)->sum('share_pct');

            $riskLevel = $top3Share > 50 ? 'very_high' : ($top3Share > 40 ? 'high' : ($top3Share > 25 ? 'medium' : 'low'));
            $riskNote  = match ($riskLevel) {
                'very_high' => "上位3社で売上の{$top3Share}%を占めています。主要顧客の喪失リスクが非常に高い状態です。",
                'high'      => "上位3社で売上の{$top3Share}%を占めています。主要顧客への依存度が高いため注意が必要です。",
                'medium'    => "上位3社で売上の{$top3Share}%を占めています。集中リスクは中程度です。",
                default     => "上位3社で売上の{$top3Share}%です。分散が効いており集中リスクは低い状態です。",
            };

            return [
                'fiscal_year'          => $snapshot['fiscal_year'],
                'latest_actual_period' => $snapshot['latest_actual_period'],
                'total_forecast_revenue' => $totalRevenue,
                'top3_share_pct'       => round($top3Share, 1),
                'top5_share_pct'       => round($top5Share, 1),
                'risk_level'           => $riskLevel,
                'risk_note'            => $riskNote,
                'projects'             => array_slice($rows, 0, $limit),
            ];
        } catch (\Throwable $e) {
            return ['error' => '売上集中リスク分析の取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: compare_fiscal_years
    // =========================================================================

    /**
     * Side-by-side comparison of two fiscal years (year-over-year).
     * Uses the forecast/actual for each year as the basis of comparison.
     */
    public function toolCompareFiscalYears(array $args): array
    {
        $baseFiscalYear    = isset($args['base_fiscal_year'])    ? (int) $args['base_fiscal_year']    : null;
        $compareFiscalYear = isset($args['compare_fiscal_year']) ? (int) $args['compare_fiscal_year'] : null;
        $projectIds        = $this->normalizeProjectIds($args['project_ids'] ?? []);

        // Default: compare current FY vs previous FY
        if (! $compareFiscalYear) {
            $compareFiscalYear = $this->financeSnapshots->currentFiscalYear();
        }
        if (! $baseFiscalYear) {
            $baseFiscalYear = $compareFiscalYear - 1;
        }

        try {
            $baseSnapshot    = $this->financeSnapshots->buildFiscalYearSnapshot($baseFiscalYear,    $projectIds, null, 10);
            $compareSnapshot = $this->financeSnapshots->buildFiscalYearSnapshot($compareFiscalYear, $projectIds, null, 10);

            $base    = $baseSnapshot['totals']['forecast'];
            $compare = $compareSnapshot['totals']['forecast'];

            $yoyChange = [
                'sales_amount'   => (int) (($compare['sales']   ?? 0) - ($base['sales']   ?? 0)),
                'expense_amount' => (int) (($compare['expense'] ?? 0) - ($base['expense'] ?? 0)),
                'profit_amount'  => (int) (($compare['profit']  ?? 0) - ($base['profit']  ?? 0)),
                'sales_pct'      => ($base['sales']   ?? 0) != 0 ? round((((($compare['sales']   ?? 0) / ($base['sales']   ?? 0)) * 100) - 100), 1) : null,
                'expense_pct'    => ($base['expense'] ?? 0) != 0 ? round((((($compare['expense'] ?? 0) / ($base['expense'] ?? 0)) * 100) - 100), 1) : null,
                'profit_pct'     => ($base['profit']  ?? 0) != 0 ? round((((($compare['profit']  ?? 0) / ($base['profit']  ?? 0)) * 100) - 100), 1) : null,
            ];

            return [
                'scope' => 'fiscal_year_comparison',
                'comparison' => [
                    'base' => [
                        'fiscal_year'        => $baseFiscalYear,
                        'period'             => $baseSnapshot['period'],
                        'latest_actual'      => $baseSnapshot['latest_actual_period'],
                        'forecast_totals'    => $base,
                        'yearly_plan_totals' => $baseSnapshot['totals']['yearly_plan'],
                        'variance_vs_plan'   => $baseSnapshot['variance_vs_plan'],
                        'project_count'      => $baseSnapshot['project_count'],
                        'data_status'        => $baseSnapshot['data_status'],
                    ],
                    'current' => [
                        'fiscal_year'        => $compareFiscalYear,
                        'period'             => $compareSnapshot['period'],
                        'latest_actual'      => $compareSnapshot['latest_actual_period'],
                        'forecast_totals'    => $compare,
                        'yearly_plan_totals' => $compareSnapshot['totals']['yearly_plan'],
                        'variance_vs_plan'   => $compareSnapshot['variance_vs_plan'],
                        'project_count'      => $compareSnapshot['project_count'],
                        'data_status'        => $compareSnapshot['data_status'],
                    ],
                ],
                'yoy_change' => $yoyChange,
                'interpretation' => [
                    'note' => "FY{$baseFiscalYear}の最終着地とFY{$compareFiscalYear}の現時点着地見込みを比較しています。FY{$baseFiscalYear}にはまだ実績が反映されていない月がある場合、Kintone損益を予測として使用しています。",
                ],
            ];
        } catch (\Throwable $e) {
            return ['error' => '年度比較の取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_pm_finance_summary
    // =========================================================================

    /**
     * Returns fiscal-year finance for projects owned by one PM.
     * PM ownership comes from project_members.authority = 1.
     */
    public function toolGetPmFinanceSummary(array $args): array
    {
        $pm = $this->resolvePm($args);
        if (is_array($pm)) {
            return $pm;
        }

        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);
        $limit = max(1, min((int) ($args['limit'] ?? 10), 30));
        $projectIds = $this->projectIdsForPm((int) $pm->id);

        if ($projectIds === []) {
            return [
                'scope' => 'pm_finance_summary',
                'pm' => $this->compactPm($pm),
                'fiscal_year' => $fiscalYear ?? $this->financeSnapshots->currentFiscalYear(),
                'project_count' => 0,
                'projects' => [],
                'message' => 'このPMが担当しているプロジェクトが見つかりません。',
            ];
        }

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, $projectIds, $asOfPeriod, $limit);
            $summary = $this->financeSnapshots->compactFiscalSummary($snapshot, $limit);

            $projectRows = array_slice($snapshot['projects'] ?? [], 0, $limit);

            return array_merge($summary, [
                'scope' => 'pm_finance_summary',
                'pm' => $this->compactPm($pm),
                'project_ids' => $projectIds,
                'project_details_limited_to' => $limit,
                'projects' => array_map(
                    fn(array $project) => $this->financeSnapshots->compactProject($project, false),
                    $projectRows
                ),
            ]);
        } catch (\Throwable $e) {
            return ['error' => 'PM別財務サマリーの取得に失敗しました: ' . $e->getMessage()];
        }
    }

    // =========================================================================
    // TOOL: get_pm_finance_ranking
    // =========================================================================

    /**
     * Aggregates fiscal-year finance by PM.
     * Projects with multiple PMs are counted for each assigned PM.
     */
    public function toolGetPmFinanceRanking(array $args): array
    {
        $fiscalYear = isset($args['fiscal_year']) ? (int) $args['fiscal_year'] : null;
        $asOfPeriod = $this->normalizePeriod($args['as_of_period'] ?? null);
        $limit = max(1, min((int) ($args['limit'] ?? 20), 100));
        $sortBy = (string) ($args['sort_by'] ?? 'profit_gap_worst');

        try {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot($fiscalYear, [], $asOfPeriod, 999);
            $projectIds = array_map('intval', array_column($snapshot['projects'] ?? [], 'project_id'));
            $pmMap = $this->pmAssignmentsForProjects($projectIds);
            $rows = [];

            foreach ($snapshot['projects'] ?? [] as $project) {
                $projectSummaryTotals = $this->summaryTotalsForProject($project);
                $projectVariance = $this->financeVariance(
                    $projectSummaryTotals['forecast'],
                    $projectSummaryTotals['yearly_plan']
                );
                $assignedPms = $pmMap[(int) $project['project_id']] ?? [[
                    'id' => 0,
                    'name' => 'PM未設定',
                    'user_code' => null,
                ]];

                foreach ($assignedPms as $pmRow) {
                    $key = (string) ($pmRow['id'] ?? 0);
                    if (! isset($rows[$key])) {
                        $rows[$key] = [
                            'pm' => [
                                'id' => (int) ($pmRow['id'] ?? 0),
                                'name' => (string) ($pmRow['name'] ?? 'PM未設定'),
                                'user_code' => $pmRow['user_code'] ?? null,
                            ],
                            'project_count' => 0,
                            'project_ids' => [],
                            'project_names' => [],
                            'totals' => [
                                'yearly_plan' => $this->emptyFinanceUnit(),
                                'settlement' => $this->emptyFinanceUnit(),
                                'forecast' => $this->emptyFinanceUnit(),
                            ],
                            'risk_project_count' => 0,
                            'missing_actual_project_count' => 0,
                        ];
                    }

                    $rows[$key]['project_count']++;
                    $rows[$key]['project_ids'][] = (int) $project['project_id'];
                    $rows[$key]['project_names'][] = (string) $project['project_name'];
                    foreach (['yearly_plan', 'settlement', 'forecast'] as $bucket) {
                        $rows[$key]['totals'][$bucket] = $this->addFinanceUnit(
                            $rows[$key]['totals'][$bucket],
                            $projectSummaryTotals[$bucket] ?? []
                        );
                    }

                    if (($projectVariance['profit_amount'] ?? 0) < 0) {
                        $rows[$key]['risk_project_count']++;
                    }
                    if (! empty($project['missing_settlement_periods'])) {
                        $rows[$key]['missing_actual_project_count']++;
                    }
                }
            }

            foreach ($rows as &$row) {
                $row['variance_vs_plan'] = $this->financeVariance(
                    $row['totals']['forecast'],
                    $row['totals']['yearly_plan']
                );
            }
            unset($row);

            $rows = array_values($rows);
            $this->sortPmRankingRows($rows, $sortBy);

            return [
                'scope' => 'pm_finance_ranking',
                'fiscal_year' => $snapshot['fiscal_year'],
                'period' => $snapshot['period'],
                'latest_actual_period' => $snapshot['latest_actual_period'],
                'sort_by' => $sortBy,
                'ranking_basis' => [
                    'pm_source' => 'project_members.authority = 1',
                    'totals' => 'PM担当プロジェクトの年度合計。複数PM案件は各PMに計上。',
                    'variance_vs_plan' => '着地見込み - 年間計画',
                ],
                'pms' => array_slice($rows, 0, $limit),
                'data_status' => $snapshot['data_status'],
            ];
        } catch (\Throwable $e) {
            return ['error' => 'PM別財務ランキングの取得に失敗しました: ' . $e->getMessage()];
        }
    }

    private function summaryTotalsForProject(array $project): array
    {
        $totals = [
            'yearly_plan' => $this->emptyFinanceUnit(),
            'settlement' => $this->emptyFinanceUnit(),
            'forecast' => $this->emptyFinanceUnit(),
        ];
        $projectName = (string) ($project['project_name'] ?? '');

        foreach ($project['months'] ?? [] as $periodKey => $month) {
            try {
                $period = Carbon::createFromFormat('Y-m-d', $periodKey . '-01')->startOfMonth();
            } catch (\Throwable) {
                continue;
            }

            foreach (['yearly_plan', 'settlement', 'forecast'] as $bucket) {
                $unit = $month[$bucket] ?? $this->emptyFinanceUnit();
                $adjustedUnit = $this->financeSnapshots->summaryAdjustedUnit($bucket, $unit, $projectName, $period);
                $totals[$bucket] = $this->addFinanceUnit($totals[$bucket], $adjustedUnit);
            }
        }

        return $totals;
    }

    private function resolvePm(array $args): User|array
    {
        $pmUserId = isset($args['pm_user_id']) ? (int) $args['pm_user_id'] : null;
        $pmName = $this->normalizePmSearchTerm((string) ($args['pm_name'] ?? ''));

        if ($pmUserId) {
            $pm = User::query()
                ->select('id', 'name', 'user_code')
                ->find($pmUserId);

            return $pm ?: ['error' => '指定されたPMユーザーが見つかりません。'];
        }

        if ($pmName === '') {
            return ['error' => 'pm_user_id または pm_name を指定してください。'];
        }

        $direct = User::query()
            ->select('id', 'name', 'user_code')
            ->where(function ($query) use ($pmName) {
                $query->where('name', $pmName)
                    ->orWhere('user_code', $pmName);
            })
            ->first();
        if ($direct) {
            return $direct;
        }

        $likeMatches = User::query()
            ->select('id', 'name', 'user_code')
            ->where(function ($query) use ($pmName) {
                $query->where('name', 'like', "%{$pmName}%")
                    ->orWhere('user_code', 'like', "%{$pmName}%");
            })
            ->limit(6)
            ->get();
        if ($likeMatches->isNotEmpty()) {
            return $this->pickPmMatch($likeMatches);
        }

        $needle = FinanceSnapshotService::projectNameKey($pmName);
        if ($needle === '') {
            return ['error' => 'PMが見つかりません。'];
        }

        $normalizedMatches = User::query()
            ->select('id', 'name', 'user_code')
            ->get()
            ->filter(function (User $user) use ($needle) {
                $nameKey = FinanceSnapshotService::projectNameKey((string) $user->name);
                $codeKey = FinanceSnapshotService::projectNameKey((string) ($user->user_code ?? ''));

                return $nameKey === $needle
                    || $codeKey === $needle
                    || str_contains($nameKey, $needle)
                    || ($codeKey !== '' && str_contains($codeKey, $needle));
            })
            ->values();

        return $normalizedMatches->isNotEmpty()
            ? $this->pickPmMatch($normalizedMatches)
            : ['error' => 'PMが見つかりません。'];
    }

    private function normalizePmSearchTerm(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/(さん|様|氏|PM|ＰＭ|プロジェクトマネージャー|担当|案件|の)+/u', '', $value) ?? $value;

        return trim($value);
    }

    private function pickPmMatch($matches): User|array
    {
        if ($matches->count() === 1) {
            return $matches->first();
        }

        return [
            'error' => 'PM候補が複数見つかりました。pm_user_id またはフルネームで指定してください。',
            'candidates' => $matches
                ->take(5)
                ->map(fn(User $user) => $this->compactPm($user))
                ->values()
                ->all(),
        ];
    }

    private function compactPm(User $pm): array
    {
        return [
            'id' => (int) $pm->id,
            'name' => (string) $pm->name,
            'user_code' => $pm->user_code ?? null,
        ];
    }

    private function projectIdsForPm(int $pmUserId): array
    {
        return DB::table('project_members')
            ->where('authority', 1)
            ->where('user_id', $pmUserId)
            ->distinct()
            ->pluck('project_id')
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0)
            ->values()
            ->all();
    }

    private function pmAssignmentsForProjects(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $rows = DB::table('project_members')
            ->join('users', 'users.id', '=', 'project_members.user_id')
            ->where('project_members.authority', 1)
            ->whereIn('project_members.project_id', $projectIds)
            ->whereNull('users.deleted_at')
            ->select([
                'project_members.project_id',
                'users.id',
                'users.name',
                'users.user_code',
            ])
            ->distinct()
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->project_id][] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'user_code' => $row->user_code,
            ];
        }

        return $out;
    }

    private function emptyFinanceUnit(): array
    {
        return [
            'sales' => 0,
            'expense' => 0,
            'profit' => 0,
            'profit_rate' => null,
            'has_data' => false,
            'is_forecast' => false,
        ];
    }

    private function addFinanceUnit(array $left, array $right): array
    {
        $sales = (float) ($left['sales'] ?? 0) + (float) ($right['sales'] ?? 0);
        $expense = (float) ($left['expense'] ?? 0) + (float) ($right['expense'] ?? 0);
        $profit = (float) ($left['profit'] ?? 0) + (float) ($right['profit'] ?? 0);

        return [
            'sales' => (int) round($sales, 0, PHP_ROUND_HALF_UP),
            'expense' => (int) round($expense, 0, PHP_ROUND_HALF_UP),
            'profit' => (int) round($profit, 0, PHP_ROUND_HALF_UP),
            'profit_rate' => $sales !== 0.0 ? round(($profit / $sales) * 100, 2, PHP_ROUND_HALF_UP) : null,
            'has_data' => ! empty($left['has_data']) || ! empty($right['has_data']),
            'is_forecast' => ! empty($left['is_forecast']) || ! empty($right['is_forecast']),
        ];
    }

    private function financeVariance(array $actual, array $plan): array
    {
        return [
            'sales_amount' => (int) round(($actual['sales'] ?? 0) - ($plan['sales'] ?? 0)),
            'expense_amount' => (int) round(($actual['expense'] ?? 0) - ($plan['expense'] ?? 0)),
            'profit_amount' => (int) round(($actual['profit'] ?? 0) - ($plan['profit'] ?? 0)),
            'sales_pct' => $this->financeVariancePct($actual['sales'] ?? null, $plan['sales'] ?? null),
            'expense_pct' => $this->financeVariancePct($actual['expense'] ?? null, $plan['expense'] ?? null),
            'profit_pct' => $this->financeVariancePct($actual['profit'] ?? null, $plan['profit'] ?? null),
        ];
    }

    private function financeVariancePct(?float $actual, ?float $plan): ?float
    {
        if ($actual === null || $plan === null || $plan == 0.0) {
            return null;
        }

        return round((($actual / $plan) * 100) - 100, 2);
    }

    private function compactFinanceUnitForChat(array $unit): array
    {
        $sales = (float) ($unit['sales'] ?? 0);
        $expense = (float) ($unit['expense'] ?? 0);
        $profit = array_key_exists('profit', $unit)
            ? (float) $unit['profit']
            : $sales - $expense;

        return [
            'sales' => (int) round($sales),
            'expense' => (int) round($expense),
            'profit' => (int) round($profit),
            'sales_display' => $this->formatYen($sales),
            'expense_display' => $this->formatYen($expense),
            'profit_display' => $this->formatYen($profit),
            'profit_rate' => $sales !== 0.0 ? round(($profit / $sales) * 100, 2) : null,
            'has_data' => ! empty($unit['has_data']) || $sales !== 0.0 || $expense !== 0.0 || $profit !== 0.0,
            'is_forecast' => ! empty($unit['is_forecast']),
            'source' => $unit['source'] ?? null,
        ];
    }

    private function financeVarianceForChat(array $actual, array $plan): array
    {
        $variance = $this->financeVariance($actual, $plan);

        return array_merge($variance, [
            'sales_amount_display' => $this->formatYen($variance['sales_amount']),
            'expense_amount_display' => $this->formatYen($variance['expense_amount']),
            'profit_amount_display' => $this->formatYen($variance['profit_amount']),
        ]);
    }

    private function largestVarianceMetric(array $variance): ?array
    {
        $metrics = [
            'sales' => [
                'label' => '売上',
                'amount' => $variance['sales_amount'] ?? null,
                'amount_display' => $variance['sales_amount_display'] ?? null,
                'pct' => $variance['sales_pct'] ?? null,
            ],
            'expense' => [
                'label' => '販管費',
                'amount' => $variance['expense_amount'] ?? null,
                'amount_display' => $variance['expense_amount_display'] ?? null,
                'pct' => $variance['expense_pct'] ?? null,
            ],
            'profit' => [
                'label' => '利益',
                'amount' => $variance['profit_amount'] ?? null,
                'amount_display' => $variance['profit_amount_display'] ?? null,
                'pct' => $variance['profit_pct'] ?? null,
            ],
        ];

        uasort($metrics, fn (array $left, array $right) => abs((float) ($right['amount'] ?? 0)) <=> abs((float) ($left['amount'] ?? 0)));
        $key = array_key_first($metrics);

        return $key ? array_merge(['metric' => $key], $metrics[$key]) : null;
    }

    private function financeCommentsForProjectPeriod(int $projectId, string $period): array
    {
        return ProjectFinanceComment::query()
            ->where('project_record_id', $projectId)
            ->where('period', $period)
            ->with('author:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (ProjectFinanceComment $comment) {
                $text = $this->sanitizeFinanceComment($comment->comment);
                if ($text === '') {
                    return null;
                }

                return [
                    'id' => (int) $comment->id,
                    'type' => $comment->type ?: null,
                    'author' => $comment->author?->name,
                    'commented_at' => $comment->created_at?->format('Y-m-d'),
                    'comment' => $text,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function sanitizeFinanceComment(?string $comment): string
    {
        $text = trim((string) $comment);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s*\[To:[^:\]\|]+(?:\|\d+)?:\]\s*/u', ' ', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($text === '' || $this->looksLikeSensitiveComment($text)) {
            return '';
        }

        return Str::limit($text, 300, '...');
    }

    private function looksLikeSensitiveComment(string $comment): bool
    {
        return preg_match('/\b(pass|pw|password)\b/i', $comment) === 1
            || str_contains($comment, 'パスワード')
            || str_contains($comment, 'ﾊﾟｽﾜｰﾄﾞ');
    }

    private function formatYen(int|float $amount): string
    {
        $rounded = (int) round($amount);
        $abs = abs($rounded);
        $sign = $rounded < 0 ? '-' : '';

        if ($abs >= 100_000_000) {
            return $sign . number_format($abs / 100_000_000, 1) . '億円';
        }

        if ($abs >= 10_000) {
            return $sign . number_format((int) round($abs / 10_000)) . '万円';
        }

        return $sign . number_format($abs) . '円';
    }

    private function sortPmRankingRows(array &$rows, string $sortBy): void
    {
        usort($rows, function (array $a, array $b) use ($sortBy) {
            return match ($sortBy) {
                'forecast_sales_desc' => ($b['totals']['forecast']['sales'] ?? 0) <=> ($a['totals']['forecast']['sales'] ?? 0),
                'forecast_profit_desc' => ($b['totals']['forecast']['profit'] ?? 0) <=> ($a['totals']['forecast']['profit'] ?? 0),
                'expense_desc' => ($b['totals']['forecast']['expense'] ?? 0) <=> ($a['totals']['forecast']['expense'] ?? 0),
                'project_count_desc' => ($b['project_count'] ?? 0) <=> ($a['project_count'] ?? 0),
                'risk_count_desc' => ($b['risk_project_count'] ?? 0) <=> ($a['risk_project_count'] ?? 0),
                default => ($a['variance_vs_plan']['profit_amount'] ?? 0) <=> ($b['variance_vs_plan']['profit_amount'] ?? 0),
            };
        });
    }

    private function resolveProject(array $args): ProjectRecord|array
    {
        $projectName = $args['project_name'] ?? null;
        $projectId   = isset($args['project_id']) ? (int) $args['project_id'] : null;

        if ($projectId) {
            $project = ProjectRecord::find($projectId);
        } elseif ($projectName) {
            $project = $this->findProjectByName((string) $projectName);
        } else {
            return ['error' => 'project_id または project_name を指定してください。'];
        }

        return $project ?: ['error' => 'プロジェクトが見つかりません。'];
    }

    private function findProjectByName(string $projectName): ?ProjectRecord
    {
        $direct = ProjectRecord::where('name', 'like', "%{$projectName}%")->first();
        if ($direct) {
            return $direct;
        }

        $needle = FinanceSnapshotService::projectNameKey($projectName);
        if ($needle === '') {
            return null;
        }

        return ProjectRecord::query()
            ->select('id', 'name')
            ->get()
            ->first(function (ProjectRecord $project) use ($needle) {
                $candidate = FinanceSnapshotService::projectNameKey((string) $project->name);

                return $candidate === $needle
                    || str_contains($candidate, $needle)
                    || str_contains($needle, $candidate);
            });
    }

    private function normalizeProjectIds(mixed $projectIds): array
    {
        if (! is_array($projectIds)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $projectIds), fn(int $id) => $id > 0));
    }

    private function normalizePeriod(?string $period): ?string
    {
        if (! $period) {
            return null;
        }

        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw new \InvalidArgumentException('as_of_period は YYYY-MM 形式で指定してください。');
        }

        return $period;
    }

    private function amountGap(?float $actual, ?float $plan): ?float
    {
        if ($actual === null || $plan === null) {
            return null;
        }

        return round($actual - $plan, 0, PHP_ROUND_HALF_UP);
    }
}
