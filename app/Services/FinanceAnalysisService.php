<?php

namespace App\Services;

use App\Models\ProjectRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use OpenAI;
use RuntimeException;

class FinanceAnalysisService
{
    public function __construct(
        private FinanceSnapshotService $financeSnapshots,
    ) {
    }

    public function analyze(array $payload, User $user): array
    {
        $facts = $this->buildFacts($payload, $user);
        $analysis = $this->generateAnalysis($facts);

        return array_merge($analysis, [
            'generated_at' => now(config('app.timezone', 'Asia/Tokyo'))->toIso8601String(),
            'scope' => $facts['scope'],
            'metrics' => $facts['metrics'],
        ]);
    }

    private function buildFacts(array $payload, User $user): array
    {
        $projectIds = $this->normalizeIds($payload['projects'] ?? []);
        if ($projectIds === []) {
            throw ValidationException::withMessages([
                'projects' => '少なくとも1つのプロジェクトを選択してください。',
            ]);
        }

        $managerIds = $this->normalizeIds($payload['managers'] ?? []);
        $projects = ProjectRecord::query()
            ->select('id', 'name', 'completed_at')
            ->with(['manager' => fn ($q) => $q->select('users.id', 'users.name')])
            ->whereIn('id', $projectIds)
            ->orderBy('name')
            ->get();

        $managers = $managerIds === []
            ? collect()
            : User::query()->select('id', 'name')->whereIn('id', $managerIds)->orderBy('name')->get();

        $includeForecastSettlement = (bool) ($payload['includeForecastSettlement'] ?? true);
        $grouping = (string) ($payload['grouping'] ?? 'range');

        $baseScope = [
            'grouping' => $grouping,
            'include_forecast_settlement' => $includeForecastSettlement,
            'project_count' => $projects->count(),
            'project_names' => $projects->pluck('name')->take(20)->values()->all(),
            'manager_names' => $managers->pluck('name')->values()->all(),
            'requested_by' => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
            ],
        ];

        return $grouping === 'fiscal'
            ? $this->buildFiscalFacts($payload, $projectIds, $baseScope)
            : $this->buildRangeFacts($payload, $projectIds, $baseScope);
    }

    private function buildRangeFacts(array $payload, array $projectIds, array $baseScope): array
    {
        [$start, $end, $periods] = $this->periodsFromInterval($payload['interval'] ?? []);
        $fiscalYears = array_values(array_unique(array_map(
            fn (string $period) => $this->fiscalYearFromPeriod($period),
            $periods
        )));
        sort($fiscalYears);

        $snapshots = [];
        foreach ($fiscalYears as $fiscalYear) {
            $snapshots[$fiscalYear] = $this->financeSnapshots->buildFiscalYearSnapshot(
                $fiscalYear,
                $projectIds,
                null,
                999
            );
        }

        $selectedTotals = $this->emptyScenarioTotals();
        $monthlyTotals = [];
        foreach ($periods as $period) {
            $snapshot = $snapshots[$this->fiscalYearFromPeriod($period)] ?? null;
            $monthTotals = $snapshot['monthly_totals'][$period] ?? [];
            $monthlyTotals[] = [
                'period' => $period,
                'yearly_plan' => $this->compactUnit($monthTotals['yearly_plan'] ?? []),
                'profit' => $this->compactUnit($monthTotals['profit'] ?? []),
                'actual' => $this->compactUnit($monthTotals['settlement'] ?? []),
                'forecast' => $this->compactUnit($monthTotals['forecast'] ?? []),
            ];

            foreach (['yearly_plan', 'profit', 'settlement', 'forecast'] as $bucket) {
                $selectedTotals[$bucket] = $this->addUnit(
                    $selectedTotals[$bucket],
                    $monthTotals[$bucket] ?? []
                );
            }
        }

        foreach ($selectedTotals as $bucket => $unit) {
            $selectedTotals[$bucket] = $this->compactUnit($unit);
        }

        $resultBucket = $baseScope['include_forecast_settlement'] ? 'forecast' : 'settlement';
        $projectRows = $this->rangeProjectRows($snapshots, $periods, $resultBucket);
        $worstProjects = $this->worstProjects($projectRows, $resultBucket);
        $dataStatus = $this->mergeDataStatus($snapshots);

        return [
            'scope' => array_merge($baseScope, [
                'period_start' => $start->format('Y-m'),
                'period_end' => $end->format('Y-m'),
                'periods' => $periods,
                'fiscal_years' => $fiscalYears,
                'analysis_basis' => $resultBucket === 'forecast'
                    ? '着地見込み（実績反映済み月は実績、未反映月はKintone損益）'
                    : 'Google Sheets実績のみ',
            ]),
            'metrics' => [
                'selected_totals' => [
                    'yearly_plan' => $selectedTotals['yearly_plan'],
                    'profit' => $selectedTotals['profit'],
                    'actual' => $selectedTotals['settlement'],
                    'forecast' => $selectedTotals['forecast'],
                    'analysis_result' => $selectedTotals[$resultBucket],
                ],
                'analysis_vs_yearly_plan' => $this->variance(
                    $selectedTotals[$resultBucket],
                    $selectedTotals['yearly_plan']
                ),
                'analysis_vs_profit' => $this->variance(
                    $selectedTotals[$resultBucket],
                    $selectedTotals['profit']
                ),
                'monthly_totals' => $monthlyTotals,
                'worst_projects' => $worstProjects,
                'data_status' => $dataStatus,
            ],
        ];
    }

    private function buildFiscalFacts(array $payload, array $projectIds, array $baseScope): array
    {
        $fiscalYears = $this->normalizeFiscalYears($payload['fiscalYears'] ?? []);
        $yearFacts = [];
        $snapshots = [];

        foreach ($fiscalYears as $fiscalYear) {
            $snapshot = $this->financeSnapshots->buildFiscalYearSnapshot(
                $fiscalYear,
                $projectIds,
                null,
                999
            );
            $snapshots[$fiscalYear] = $snapshot;
            $yearFacts[$fiscalYear] = [
                'fiscal_year' => $fiscalYear,
                'period' => $snapshot['period'],
                'latest_actual_period' => $snapshot['latest_actual_period'] ?? $snapshot['latest_closed_period'],
                'project_count' => $snapshot['project_count'],
                'yearly_plan_totals' => $this->compactUnit($snapshot['totals']['yearly_plan'] ?? []),
                'actual_to_date_totals' => $this->compactUnit($snapshot['totals']['settlement'] ?? []),
                'forecast_totals' => $this->compactUnit($snapshot['totals']['forecast'] ?? []),
                'forecast_vs_yearly_plan' => $this->variance(
                    $snapshot['totals']['forecast'] ?? [],
                    $snapshot['totals']['yearly_plan'] ?? []
                ),
                'risk_projects' => array_map(
                    fn (array $project) => $this->compactProjectRisk($project, 'forecast'),
                    array_slice($snapshot['risk_projects'] ?? [], 0, 8)
                ),
                'data_status' => $snapshot['data_status'] ?? [],
            ];
        }

        $targetFiscalYear = end($fiscalYears);
        $targetSnapshot = $snapshots[$targetFiscalYear] ?? null;
        $resultBucket = $baseScope['include_forecast_settlement'] ? 'forecast' : 'settlement';

        $comparison = null;
        if (count($fiscalYears) >= 2) {
            $baseYear = $fiscalYears[0];
            $compareYear = $fiscalYears[count($fiscalYears) - 1];
            $baseTotals = $snapshots[$baseYear]['totals'][$resultBucket] ?? [];
            $compareTotals = $snapshots[$compareYear]['totals'][$resultBucket] ?? [];
            $comparison = [
                'base_fiscal_year' => $baseYear,
                'compare_fiscal_year' => $compareYear,
                'basis' => $resultBucket === 'forecast' ? 'forecast_totals' : 'actual_to_date_totals',
                'delta' => $this->difference($compareTotals, $baseTotals),
            ];
        }

        return [
            'scope' => array_merge($baseScope, [
                'fiscal_years' => $fiscalYears,
                'analysis_basis' => $resultBucket === 'forecast'
                    ? '年度着地見込み'
                    : '年度実績累計のみ',
            ]),
            'metrics' => [
                'fiscal_years' => array_values($yearFacts),
                'comparison' => $comparison,
                'target_year' => $targetFiscalYear,
                'target_worst_projects' => $targetSnapshot
                    ? array_map(
                        fn (array $project) => $this->compactProjectRisk($project, $resultBucket),
                        array_slice($targetSnapshot['risk_projects'] ?? [], 0, 8)
                    )
                    : [],
                'data_status' => $this->mergeDataStatus($snapshots),
            ],
        ];
    }

    private function generateAnalysis(array $facts): array
    {
        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            throw new RuntimeException('OpenAI APIキーが設定されていません。');
        }

        $client = OpenAI::client($apiKey);
        $model = config('services.openai.chat_model', 'gpt-4.1-mini');

        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            'temperature' => 0.2,
            'max_tokens' => 1200,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content ?? '';

        return $this->normalizeAnalysisResponse($content);
    }

    private function systemPrompt(): string
    {
        return <<<TXT
あなたは取締役向けの財務分析AIです。与えられたJSON factsだけを使い、日本語で簡潔に分析してください。

ルール:
- 売上、販管費、利益、計画差分、前年比/年度差分、データ欠損を優先して見る
- 金額換算は必ず 1億円 = 100,000,000円、1万円 = 10,000円 とする
- 例: 800,282,891円 は 約8.0億円 または 80,028万円。800億円ではない
- 金額には facts 内の *_display フィールドを優先して使い、整数値から独自に換算しない
- factsにない数値やプロジェクト名は推測しない
- include_forecast_settlement=false または analysis_basis が実績のみの場合、未反映月を含む年度計画との比較は進捗途中であることを明示する
- data_status に missing_settlement_periods または forecast_periods がある場合は data_notes に含める
- 出力は必ずJSONオブジェクトのみ。Markdownやコードフェンスは禁止

JSON schema:
{
  "headline": "一行の結論",
  "summary": "3文以内の要約",
  "highlights": ["重要な良い/中立ポイントを最大4件"],
  "risks": ["注意点や未達リスクを最大4件"],
  "recommended_actions": ["次の確認/対応を最大4件"],
  "data_notes": ["データ前提や欠損注記を最大3件"]
}
TXT;
    }

    private function normalizeAnalysisResponse(string $content): array
    {
        $decoded = json_decode($content, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $content, $match)) {
            $decoded = json_decode($match[0], true);
        }

        if (! is_array($decoded)) {
            $decoded = ['summary' => trim($content)];
        }

        return [
            'headline' => $this->stringValue($decoded['headline'] ?? '財務分析'),
            'summary' => $this->stringValue($decoded['summary'] ?? ''),
            'highlights' => $this->stringList($decoded['highlights'] ?? []),
            'risks' => $this->stringList($decoded['risks'] ?? []),
            'recommended_actions' => $this->stringList($decoded['recommended_actions'] ?? []),
            'data_notes' => $this->stringList($decoded['data_notes'] ?? []),
        ];
    }

    private function periodsFromInterval(array $interval): array
    {
        foreach (['startYear', 'startMonth', 'endYear', 'endMonth'] as $key) {
            if (! isset($interval[$key])) {
                throw ValidationException::withMessages(['interval' => '分析期間を指定してください。']);
            }
        }

        $start = Carbon::create((int) $interval['startYear'], (int) $interval['startMonth'], 1)->startOfMonth();
        $end = Carbon::create((int) $interval['endYear'], (int) $interval['endMonth'], 1)->startOfMonth();
        if ($end->lt($start)) {
            throw ValidationException::withMessages(['interval' => '開始日付は終了日付より前で設定してください。']);
        }

        if (((int) $start->diffInMonths($end)) + 1 > 12) {
            throw ValidationException::withMessages(['interval' => '最大12ヶ月まで選択できます。']);
        }

        $periods = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addMonth()) {
            $periods[] = $cursor->format('Y-m');
        }

        return [$start, $end, $periods];
    }

    private function rangeProjectRows(array $snapshots, array $periods, string $resultBucket): array
    {
        $rows = [];

        foreach ($snapshots as $snapshot) {
            foreach ($snapshot['projects'] ?? [] as $project) {
                $projectId = (int) $project['project_id'];
                $rows[$projectId] ??= [
                    'project_id' => $projectId,
                    'project_name' => (string) $project['project_name'],
                    'totals' => $this->emptyScenarioTotals(),
                    'missing_settlement_periods' => [],
                    'forecast_periods' => [],
                ];

                foreach ($periods as $period) {
                    $month = $project['months'][$period] ?? null;
                    if (! $month) {
                        continue;
                    }
                    foreach (['yearly_plan', 'profit', 'settlement', 'forecast'] as $bucket) {
                        $rows[$projectId]['totals'][$bucket] = $this->addUnit(
                            $rows[$projectId]['totals'][$bucket],
                            $month[$bucket] ?? []
                        );
                    }
                }

                $rows[$projectId]['missing_settlement_periods'] = array_values(array_unique(array_merge(
                    $rows[$projectId]['missing_settlement_periods'],
                    array_values(array_intersect($project['missing_settlement_periods'] ?? [], $periods))
                )));
                $rows[$projectId]['forecast_periods'] = array_values(array_unique(array_merge(
                    $rows[$projectId]['forecast_periods'],
                    array_values(array_intersect($project['forecast_periods'] ?? [], $periods))
                )));
            }
        }

        foreach ($rows as &$row) {
            foreach ($row['totals'] as $bucket => $unit) {
                $row['totals'][$bucket] = $this->compactUnit($unit);
            }
            $row['variance_vs_yearly_plan'] = $this->variance(
                $row['totals'][$resultBucket],
                $row['totals']['yearly_plan']
            );
        }
        unset($row);

        return array_values($rows);
    }

    private function worstProjects(array $projectRows, string $resultBucket): array
    {
        usort($projectRows, fn (array $left, array $right) => ($left['variance_vs_yearly_plan']['profit_amount'] ?? 0) <=> ($right['variance_vs_yearly_plan']['profit_amount'] ?? 0));

        return array_map(
            fn (array $project) => [
                'project_id' => $project['project_id'],
                'project_name' => $project['project_name'],
                'totals' => [
                    'yearly_plan' => $project['totals']['yearly_plan'],
                    'analysis_result' => $project['totals'][$resultBucket],
                ],
                'variance_vs_yearly_plan' => $project['variance_vs_yearly_plan'],
                'missing_settlement_periods' => $project['missing_settlement_periods'],
                'forecast_periods' => $project['forecast_periods'],
            ],
            array_slice($projectRows, 0, 8)
        );
    }

    private function compactProjectRisk(array $project, string $bucket): array
    {
        $totals = $project['totals'] ?? [];

        return [
            'project_id' => (int) ($project['project_id'] ?? 0),
            'project_name' => (string) ($project['project_name'] ?? ''),
            'yearly_plan' => $this->compactUnit($totals['yearly_plan'] ?? []),
            'analysis_result' => $this->compactUnit($totals[$bucket] ?? []),
            'variance_vs_yearly_plan' => $this->variance($totals[$bucket] ?? [], $totals['yearly_plan'] ?? []),
            'missing_settlement_periods' => $project['missing_settlement_periods'] ?? [],
            'forecast_periods' => $project['forecast_periods'] ?? [],
        ];
    }

    private function mergeDataStatus(array $snapshots): array
    {
        $missing = [];
        $forecast = [];
        $summaryAdjustment = null;
        $forecastRule = null;

        foreach ($snapshots as $snapshot) {
            $missing = array_merge($missing, $snapshot['data_status']['missing_settlement_periods'] ?? []);
            $forecast = array_merge($forecast, $snapshot['data_status']['forecast_periods'] ?? []);
            $summaryAdjustment ??= $snapshot['data_status']['summary_adjustment'] ?? null;
            $forecastRule ??= $snapshot['data_status']['forecast_rule'] ?? null;
        }

        return [
            'missing_settlement_periods' => array_values(array_unique($missing)),
            'forecast_periods' => array_values(array_unique($forecast)),
            'summary_adjustment' => $summaryAdjustment,
            'forecast_rule' => $forecastRule,
        ];
    }

    private function normalizeFiscalYears(array $values): array
    {
        $years = array_values(array_unique(array_filter(
            array_map('intval', $values),
            fn (int $year) => $year >= 2024 && $year <= ((int) now()->year + 2)
        )));

        if ($years === []) {
            $years = [$this->financeSnapshots->currentFiscalYear()];
        }

        sort($years);

        return array_slice($years, 0, 2);
    }

    private function normalizeIds(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $values),
            fn (int $id) => $id > 0
        )));
    }

    private function emptyScenarioTotals(): array
    {
        return [
            'yearly_plan' => $this->emptyUnit(),
            'profit' => $this->emptyUnit(),
            'settlement' => $this->emptyUnit(),
            'forecast' => $this->emptyUnit(),
        ];
    }

    private function emptyUnit(): array
    {
        return [
            'sales' => 0,
            'expense' => 0,
            'profit' => 0,
            'has_data' => false,
            'is_forecast' => false,
        ];
    }

    private function addUnit(array $base, array $unit): array
    {
        $base['sales'] = (float) ($base['sales'] ?? 0) + (float) ($unit['sales'] ?? 0);
        $base['expense'] = (float) ($base['expense'] ?? 0) + (float) ($unit['expense'] ?? 0);
        $base['profit'] = (float) ($base['profit'] ?? 0) + (float) ($unit['profit'] ?? (($unit['sales'] ?? 0) - ($unit['expense'] ?? 0)));
        $base['has_data'] = ! empty($base['has_data']) || ! empty($unit['has_data']);
        $base['is_forecast'] = ! empty($base['is_forecast']) || ! empty($unit['is_forecast']) || (($unit['source'] ?? null) === 'profit_forecast');

        return $base;
    }

    private function compactUnit(array $unit): array
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
        ];
    }

    private function variance(array $actual, array $base): array
    {
        $actualUnit = $this->compactUnit($actual);
        $baseUnit = $this->compactUnit($base);

        return [
            'sales_amount' => $actualUnit['sales'] - $baseUnit['sales'],
            'expense_amount' => $actualUnit['expense'] - $baseUnit['expense'],
            'profit_amount' => $actualUnit['profit'] - $baseUnit['profit'],
            'sales_amount_display' => $this->formatYen($actualUnit['sales'] - $baseUnit['sales']),
            'expense_amount_display' => $this->formatYen($actualUnit['expense'] - $baseUnit['expense']),
            'profit_amount_display' => $this->formatYen($actualUnit['profit'] - $baseUnit['profit']),
            'sales_pct' => $this->percentageChange($actualUnit['sales'], $baseUnit['sales']),
            'expense_pct' => $this->percentageChange($actualUnit['expense'], $baseUnit['expense']),
            'profit_pct' => $this->percentageChange($actualUnit['profit'], $baseUnit['profit']),
        ];
    }

    private function difference(array $current, array $previous): array
    {
        $currentUnit = $this->compactUnit($current);
        $previousUnit = $this->compactUnit($previous);

        return [
            'sales_amount' => $currentUnit['sales'] - $previousUnit['sales'],
            'expense_amount' => $currentUnit['expense'] - $previousUnit['expense'],
            'profit_amount' => $currentUnit['profit'] - $previousUnit['profit'],
            'sales_amount_display' => $this->formatYen($currentUnit['sales'] - $previousUnit['sales']),
            'expense_amount_display' => $this->formatYen($currentUnit['expense'] - $previousUnit['expense']),
            'profit_amount_display' => $this->formatYen($currentUnit['profit'] - $previousUnit['profit']),
            'sales_pct' => $this->percentageChange($currentUnit['sales'], $previousUnit['sales']),
            'expense_pct' => $this->percentageChange($currentUnit['expense'], $previousUnit['expense']),
            'profit_pct' => $this->percentageChange($currentUnit['profit'], $previousUnit['profit']),
        ];
    }

    private function percentageChange(int|float $actual, int|float $base): ?float
    {
        if ((float) $base === 0.0) {
            return null;
        }

        return round((($actual - $base) / abs($base)) * 100, 2);
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

    private function fiscalYearFromPeriod(string $period): int
    {
        $date = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();

        return $date->month >= 3 ? (int) $date->year : (int) $date->year - 1;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item) => $this->stringValue($item),
            Arr::flatten($value)
        )));
    }
}
