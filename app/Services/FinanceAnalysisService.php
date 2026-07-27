<?php

namespace App\Services;

use App\Models\ProjectFinanceComment;
use App\Models\ProjectRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
            'scenario_labels' => $this->scenarioLabels(),
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
        $internalReferenceProjects = $this->internalReferenceProjects($projectRows, $resultBucket);
        $actualResultDrivers = $this->actualResultDriverContext($snapshots, $periods, $worstProjects, 16);
        $dataStatus = $this->mergeDataStatus($snapshots, $periods);
        $commentContext = $this->financeCommentContext($projectIds, $periods, $snapshots);

        return [
            'scope' => array_merge($baseScope, [
                'period_start' => $start->format('Y-m'),
                'period_end' => $end->format('Y-m'),
                'periods' => $periods,
                'fiscal_years' => $fiscalYears,
                'analysis_basis' => $resultBucket === 'forecast'
                    ? '着地見込み（実績反映済み月は実績、未反映月はKintone損益）'
                    : '実績のみ（保存済み実績優先、Google Sheets補完）',
                'finance_comment_count' => $commentContext['comment_count'],
                'actionable_project_count' => $this->actionableProjectCount($projectRows),
                'internal_cost_center_policy' => $this->internalCostCenterPolicy(),
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
                'analysis_vs_monthly_plan' => $this->variance(
                    $selectedTotals[$resultBucket],
                    $selectedTotals['profit']
                ),
                'monthly_totals' => $monthlyTotals,
                'worst_projects' => $worstProjects,
                'internal_reference_projects' => $internalReferenceProjects,
                'actual_result_drivers' => $actualResultDrivers,
                'data_status' => $dataStatus,
            ],
            'comment_context' => $commentContext,
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
            $projectRows = $this->snapshotProjectRows($snapshot, 'forecast');
            $riskProjects = $this->worstProjects($projectRows, 'forecast');
            $snapshots[$fiscalYear] = $snapshot;
            $yearFacts[$fiscalYear] = [
                'fiscal_year' => $fiscalYear,
                'period' => $snapshot['period'],
                'latest_actual_period' => $snapshot['latest_actual_period'] ?? $snapshot['latest_closed_period'],
                'project_count' => $snapshot['project_count'],
                'actionable_project_count' => $this->actionableProjectCount($projectRows),
                'yearly_plan_totals' => $this->compactUnit($snapshot['totals']['yearly_plan'] ?? []),
                'actual_to_date_totals' => $this->compactUnit($snapshot['totals']['settlement'] ?? []),
                'forecast_totals' => $this->compactUnit($snapshot['totals']['forecast'] ?? []),
                'forecast_vs_yearly_plan' => $this->variance(
                    $snapshot['totals']['forecast'] ?? [],
                    $snapshot['totals']['yearly_plan'] ?? []
                ),
                'risk_projects' => $riskProjects,
                'internal_reference_projects' => $this->internalReferenceProjects($projectRows, 'forecast'),
                'actual_result_drivers' => $this->actualResultDriverContext(
                    [$fiscalYear => $snapshot],
                    $snapshot['period']['months'] ?? [],
                    $riskProjects,
                    12
                ),
                'data_status' => $snapshot['data_status'] ?? [],
            ];
        }

        $targetFiscalYear = end($fiscalYears);
        $targetSnapshot = $snapshots[$targetFiscalYear] ?? null;
        $resultBucket = $baseScope['include_forecast_settlement'] ? 'forecast' : 'settlement';
        $targetProjectRows = $targetSnapshot ? $this->snapshotProjectRows($targetSnapshot, $resultBucket) : [];
        $commentPeriods = $this->snapshotPeriods($snapshots);
        $commentContext = $this->financeCommentContext($projectIds, $commentPeriods, $snapshots);

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
                'finance_comment_count' => $commentContext['comment_count'],
                'actionable_project_count' => $this->actionableProjectCount($targetProjectRows),
                'internal_cost_center_policy' => $this->internalCostCenterPolicy(),
            ]),
            'metrics' => [
                'fiscal_years' => array_values($yearFacts),
                'comparison' => $comparison,
                'target_year' => $targetFiscalYear,
                'target_worst_projects' => $this->worstProjects($targetProjectRows, $resultBucket),
                'target_internal_reference_projects' => $this->internalReferenceProjects($targetProjectRows, $resultBucket),
                'data_status' => $this->mergeDataStatus($snapshots),
            ],
            'comment_context' => $commentContext,
        ];
    }

    private function generateAnalysis(array $facts): array
    {
        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            throw new RuntimeException('OpenAI APIキーが設定されていません。');
        }

        $client = OpenAI::client($apiKey);
        $model = config('services.openai.chat_model', 'gpt-5.6-luna');
        $factsForModel = $this->factsForModel($facts);

        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => json_encode($factsForModel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            'max_completion_tokens' => 1200,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content ?? '';

        $analysis = $this->enrichAnalysisWithVerifiedDrivers(
            $this->normalizeAnalysisResponse($content),
            $facts
        );
        $analysis = $this->enrichCommentInsights($analysis, $facts);

        return $this->enrichDataNotesWithActualCoverage($analysis, $facts);
    }

    private function systemPrompt(): string
    {
        return <<<TXT
あなたは取締役向けの財務分析AIです。与えられたJSON factsだけを使い、日本語で簡潔に分析してください。

ルール:
- 売上、販管費、利益、計画差分、前年比/年度差分、データ欠損を優先して見る
- 金額換算は必ず 1億円 = 100,000,000円、1万円 = 10,000円 とする
- 例: 800,282,891円 は 約8.0億円 または 80,028万円。800億円ではない
- 例: 13,086,730円 は 1,309万円 または 約0.1億円。1.3億円ではない
- facts 内の sales / expense / profit / *_amount は既に表示用に換算済み。必ずその文字列をそのまま使い、独自に億円換算しない
- 「万円」として渡された金額を「億円」に言い換えない
- 用語定義: 予算 = yearly_plan。PMが作成し取締役が承認した、年度開始時点の年間計画
- 用語定義: 計画 = facts内の scenario/bucket 名 profit。予算を基準にしたKintone損益の月次修正計画で、人員退職・体制変更・見積更新などにより月次で変わる
- 用語定義: 実績 = settlement。保存済み実績データを優先し、該当プロジェクト・月にない場合はGoogle Sheets実績を使う
- 用語定義: 着地見込み = forecast。実績反映済み月は実績、未反映月は計画を見込みとして使う
- 「予算差分」は着地見込み/実績と yearly_plan の差分、「計画差分」は着地見込み/実績と profit（計画）の差分として扱う
- factsにない数値やプロジェクト名は推測しない
- metrics.actual_result_drivers は保存済み実績がある月だけの詳細根拠。売上内訳、売上原価、販管費、間接費、積立金、上位費目を分析に使う
- actual_result_drivers がないプロジェクト・月について、費用増減の内訳を推測しない
- actual_result_drivers がある場合、単に「販管費が増加」と書かず、highlights または risks にプロジェクト名・費目名・金額を含む具体的な内訳を最低2件入れる。対象が1件だけなら1件でよい
- top_expense_accounts は実績費用の構成であり、増減原因そのものではない。「主な費用内訳は」と表現し、コメントの裏付けなしに「この費目が差異の原因」と断定しない
- 計画差分を説明するときは variance_vs_profit_plan、予算差分を説明するときは variance_vs_yearly_plan を使い、両者を混同しない
- 予算費用が0円で実績費用があるだけでは「予算未計上が原因」と断定しない。「予算0円に対し実績費用が発生」と事実を示す
- 推奨対応は根拠データに直接つながる確認事項にする。「営業戦略の見直し」などfactsから導けない一般論を追加しない
- top_expense_accounts の「給与関連」は個別給与項目を集約した値であり、個人単位の情報ではない
- internal_reference_projects は社内配賦・積立・振替の影響を持つ参考部門であり、通常案件のリスク順位として扱わない
- 賞与などの社内振替は通常費用の急増として扱わず、会社全体で相殺される管理会計上の振替として扱う
- comment_context.context_rows がある場合、コメントは対象プロジェクト・対象月の人間による記録として扱い、差異理由を説明する補助根拠にする
- コメントを使う場合は「コメントでは」「記録では」など、コメント由来であることが分かる表現にする
- actual_data_available=false の月について、実績差異の原因をコメントから断定しない
- コメントがない差異については原因を推測せず、必要なら「理由コメントなし」「要確認」とする
- comment_insights には、コメントで裏付けられる差異理由だけを最大4件入れる。コメントがない場合は空配列にする
- include_forecast_settlement=false または analysis_basis が実績のみの場合、未反映月を含む年度計画との比較は進捗途中であることを明示する
- data_status.actual_coverage を実績反映状況の正とする。actual_reflected_count は実績反映済み、not_expected_count は未開始・終了済み・月次財務活動なしの対象外、needs_review_count だけが要確認
- actual_coverage がある場合、件数は「実績対象N件のうち、実績反映済みN件、対象外N件、要確認N件」の形式で扱い、対象外を欠損やリスクと表現しない
- data_status に missing_settlement_periods または forecast_periods がある場合は data_notes に含める
- 出力本文では英字の内部データ名や計算モード名を使わず、「保存済み実績」「実績」「予算」「CSV確定値」「システム計算値」の日本語表示を使う
- 出力は必ずJSONオブジェクトのみ。Markdownやコードフェンスは禁止

JSON schema:
{
  "headline": "一行の結論",
  "summary": "3文以内の要約",
  "highlights": ["重要な良い/中立ポイントを最大4件"],
  "risks": ["注意点や未達リスクを最大4件"],
  "comment_insights": ["コメントに基づく差異理由を最大4件"],
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
            'comment_insights' => $this->stringList($decoded['comment_insights'] ?? []),
            'recommended_actions' => $this->stringList($decoded['recommended_actions'] ?? []),
            'data_notes' => $this->stringList($decoded['data_notes'] ?? []),
        ];
    }

    private function enrichAnalysisWithVerifiedDrivers(array $analysis, array $facts): array
    {
        $verifiedSummary = $this->verifiedExecutiveSummary($facts);
        if ($verifiedSummary !== null) {
            $analysis['summary'] = $verifiedSummary;
        }

        $overallHighlights = $this->verifiedOverallHighlights($facts);
        $drivers = $this->analysisDriverRows($facts);
        if ($drivers === []) {
            $analysis['highlights'] = array_slice(array_merge(
                $overallHighlights,
                $analysis['highlights'] ?? []
            ), 0, 4);

            return $analysis;
        }

        $projectTokens = $this->analysisProjectTokens($facts, $drivers);
        $analysis['summary'] = $this->withoutProjectClaims(
            (string) ($analysis['summary'] ?? ''),
            $projectTokens
        );
        foreach (['highlights', 'risks', 'recommended_actions'] as $section) {
            $analysis[$section] = array_values(array_filter(
                $analysis[$section] ?? [],
                fn (string $item) => ! $this->mentionsProject($item, $projectTokens)
            ));
        }

        $positiveDrivers = array_values(array_filter(
            $drivers,
            fn (array $driver) => (int) data_get($driver, 'variance_vs_profit_plan.profit_amount', 0) > 0
        ));
        usort($positiveDrivers, fn (array $left, array $right) =>
            (int) data_get($right, 'variance_vs_profit_plan.profit_amount', 0)
                <=> (int) data_get($left, 'variance_vs_profit_plan.profit_amount', 0)
        );
        $highlightDrivers = $positiveDrivers !== [] ? $positiveDrivers : $drivers;
        $highlightDrivers = $this->distinctProjectDrivers($highlightDrivers, 2);
        $verifiedHighlights = array_values(array_filter(array_map(
            fn (array $driver) => $this->verifiedDriverHighlight($driver),
            $highlightDrivers
        )));

        $riskDrivers = array_values(array_filter(
            $drivers,
            fn (array $driver) => (int) data_get($driver, 'variance_vs_profit_plan.profit_amount', 0) < 0
        ));
        usort($riskDrivers, fn (array $left, array $right) =>
            (int) data_get($left, 'variance_vs_profit_plan.profit_amount', 0)
                <=> (int) data_get($right, 'variance_vs_profit_plan.profit_amount', 0)
        );
        $riskDrivers = $this->distinctProjectDrivers($riskDrivers, 2);
        $verifiedRisks = array_values(array_filter(array_map(
            fn (array $driver) => $this->verifiedDriverRisk($driver),
            $riskDrivers
        )));
        $verifiedActions = array_values(array_filter(array_map(
            fn (array $driver) => $this->verifiedDriverAction($driver),
            $riskDrivers
        )));

        $analysis['highlights'] = array_slice(array_merge(
            $overallHighlights,
            $verifiedHighlights,
            $analysis['highlights']
        ), 0, 4);
        $analysis['risks'] = array_slice(array_merge(
            $verifiedRisks,
            $analysis['risks']
        ), 0, 4);
        $analysis['recommended_actions'] = array_slice(array_merge(
            $verifiedActions,
            $analysis['recommended_actions']
        ), 0, 4);

        return $analysis;
    }

    private function distinctProjectDrivers(array $drivers, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $selected = [];
        $seen = [];

        foreach ($drivers as $driver) {
            $projectKey = FinanceSnapshotService::projectNameKey($driver['project_name'] ?? '');
            if ($projectKey === '' || isset($seen[$projectKey])) {
                continue;
            }

            $seen[$projectKey] = true;
            $selected[] = $driver;

            if (count($selected) >= $limit) {
                break;
            }
        }

        return $selected;
    }

    private function verifiedExecutiveSummary(array $facts): ?string
    {
        $result = $facts['metrics']['selected_totals']['analysis_result'] ?? null;
        $yearlyPlanVariance = $facts['metrics']['analysis_vs_yearly_plan'] ?? null;
        $profitPlanVariance = $facts['metrics']['analysis_vs_profit'] ?? null;
        if (! is_array($result) || empty($result['has_data'])) {
            return null;
        }

        $basis = str_contains((string) ($facts['scope']['analysis_basis'] ?? ''), '着地見込み')
            ? '着地見込み'
            : '実績';
        $periodLabel = $this->executivePeriodLabel($facts['scope'] ?? []);
        $subject = $periodLabel !== '' ? "{$periodLabel}の{$basis}" : $basis;
        $profitRate = $result['profit_rate'] ?? null;
        $rateText = $profitRate !== null ? sprintf('（利益率%s%%）', $this->formatPercentage((float) $profitRate)) : '';
        $sentences = [sprintf(
            '%sは売上%s、販管費%s、利益%s%sです。',
            $subject,
            (string) ($result['sales_display'] ?? $this->formatYen((float) ($result['sales'] ?? 0))),
            (string) ($result['expense_display'] ?? $this->formatYen((float) ($result['expense'] ?? 0))),
            (string) ($result['profit_display'] ?? $this->formatYen((float) ($result['profit'] ?? 0))),
            $rateText
        )];

        if (is_array($yearlyPlanVariance)) {
            $sentences[] = '予算比では' . $this->varianceNarrative($yearlyPlanVariance) . '。';
        }

        if (is_array($profitPlanVariance)) {
            $planSentence = '計画比では' . $this->varianceNarrative($profitPlanVariance);
            $coverageText = $this->executiveCoverageText($facts);
            $sentences[] = $planSentence . ($coverageText !== '' ? "。{$coverageText}" : '。');
        }

        return implode('', array_slice($sentences, 0, 3));
    }

    private function executivePeriodLabel(array $scope): string
    {
        $start = (string) ($scope['period_start'] ?? ($scope['periods'][0] ?? ''));
        $end = (string) ($scope['period_end'] ?? (Arr::last($scope['periods'] ?? []) ?? $start));
        if (preg_match('/^(20\d{2})-(\d{2})$/', $start, $startParts) !== 1) {
            return '';
        }

        if ($start === $end) {
            return sprintf('%d年%d月', (int) $startParts[1], (int) $startParts[2]);
        }

        if (preg_match('/^(20\d{2})-(\d{2})$/', $end, $endParts) !== 1) {
            return '';
        }

        if ($startParts[1] === $endParts[1]) {
            return sprintf('%d年%d月〜%d月', (int) $startParts[1], (int) $startParts[2], (int) $endParts[2]);
        }

        return sprintf(
            '%d年%d月〜%d年%d月',
            (int) $startParts[1],
            (int) $startParts[2],
            (int) $endParts[1],
            (int) $endParts[2]
        );
    }

    private function varianceNarrative(array $variance): string
    {
        $parts = [];
        foreach (['sales' => '売上', 'expense' => '販管費', 'profit' => '利益'] as $metric => $label) {
            $amount = (int) ($variance[$metric . '_amount'] ?? 0);
            if ($amount === 0) {
                $parts[] = "{$label}は同額";
                continue;
            }

            $amountText = $this->formatYen(abs($amount));
            $percentage = $variance[$metric . '_pct'] ?? null;
            $percentageText = is_numeric($percentage)
                ? sprintf('（%s%%）', $this->formatPercentage(abs((float) $percentage)))
                : '';
            $direction = $metric === 'profit'
                ? ($amount > 0 ? '改善' : '悪化')
                : ($amount > 0 ? '増加' : '減少');
            $parts[] = "{$label}が{$amountText}{$percentageText}{$direction}";
        }

        if (count($parts) <= 1) {
            return $parts[0] ?? '';
        }

        $last = array_pop($parts);

        return implode('、', $parts) . 'し、' . $last;
    }

    private function executiveCoverageText(array $facts): string
    {
        $coverage = $facts['metrics']['data_status']['actual_coverage'] ?? [];
        if ($coverage === []) {
            return '';
        }

        $totals = array_reduce($coverage, function (array $carry, array $row) {
            $carry['selected'] += (int) ($row['selected_project_count'] ?? 0);
            $carry['reflected'] += (int) ($row['actual_reflected_count'] ?? 0);
            $carry['excluded'] += (int) ($row['not_expected_count'] ?? 0);
            $carry['review'] += (int) ($row['needs_review_count'] ?? 0);

            return $carry;
        }, ['selected' => 0, 'reflected' => 0, 'excluded' => 0, 'review' => 0]);

        return sprintf(
            '実績対象%d件のうち、実績反映済み%d件、対象外%d件、要確認%d件です。',
            $totals['selected'],
            $totals['reflected'],
            $totals['excluded'],
            $totals['review']
        );
    }

    private function formatPercentage(float $percentage): string
    {
        return rtrim(rtrim(number_format($percentage, 2, '.', ''), '0'), '.');
    }

    private function verifiedOverallHighlights(array $facts): array
    {
        $result = $facts['metrics']['selected_totals']['analysis_result'] ?? null;
        if (! is_array($result) || empty($result['has_data'])) {
            return [];
        }

        $basis = str_contains((string) ($facts['scope']['analysis_basis'] ?? ''), '着地見込み')
            ? '着地見込み'
            : '実績';
        $profitRate = $result['profit_rate'] ?? null;
        $rateText = $profitRate !== null ? sprintf('（利益率%s%%）', $profitRate) : '';
        $highlights = [sprintf(
            '%sは売上%s、販管費%s、利益%s%s。',
            $basis,
            (string) ($result['sales_display'] ?? $this->formatYen((float) ($result['sales'] ?? 0))),
            (string) ($result['expense_display'] ?? $this->formatYen((float) ($result['expense'] ?? 0))),
            (string) ($result['profit_display'] ?? $this->formatYen((float) ($result['profit'] ?? 0))),
            $rateText
        )];

        $yearlyPlanVariance = $facts['metrics']['analysis_vs_yearly_plan'] ?? null;
        $profitPlanVariance = $facts['metrics']['analysis_vs_profit'] ?? null;
        if (is_array($yearlyPlanVariance) && is_array($profitPlanVariance)) {
            $highlights[] = sprintf(
                '予算差分は売上%s、販管費%s、利益%s。計画差分は売上%s、販管費%s、利益%s。',
                $this->varianceMoney($yearlyPlanVariance, 'sales'),
                $this->varianceMoney($yearlyPlanVariance, 'expense'),
                $this->varianceMoney($yearlyPlanVariance, 'profit'),
                $this->varianceMoney($profitPlanVariance, 'sales'),
                $this->varianceMoney($profitPlanVariance, 'expense'),
                $this->varianceMoney($profitPlanVariance, 'profit')
            );
        }

        return $highlights;
    }

    private function varianceMoney(array $variance, string $metric): string
    {
        $amount = (int) ($variance[$metric . '_amount'] ?? 0);
        $display = (string) ($variance[$metric . '_amount_display'] ?? $this->formatYen($amount));

        return $amount > 0 ? '+' . $display : $display;
    }

    private function enrichCommentInsights(array $analysis, array $facts): array
    {
        $insights = array_values($analysis['comment_insights'] ?? []);

        foreach ($facts['comment_context']['context_rows'] ?? [] as $row) {
            if (count($insights) >= 4) {
                break;
            }
            if (empty($row['actual_data_available'])) {
                continue;
            }

            $projectName = trim((string) ($row['project_name'] ?? ''));
            if ($projectName === '' || $this->mentionsProject(implode(' ', $insights), [$projectName])) {
                continue;
            }

            $comment = $row['comments'][0]['comment'] ?? null;
            if (! is_string($comment) || trim($comment) === '') {
                continue;
            }

            $period = trim((string) ($row['period'] ?? ''));
            $insights[] = sprintf(
                '%s%sのコメントでは「%s」と記録されています。',
                $projectName,
                $period !== '' ? "（{$period}）" : '',
                Str::limit(trim($comment), 140, '...')
            );
        }

        $analysis['comment_insights'] = array_slice($insights, 0, 4);

        return $analysis;
    }

    private function analysisDriverRows(array $facts): array
    {
        $rows = $facts['metrics']['actual_result_drivers'] ?? [];

        foreach ($facts['metrics']['fiscal_years'] ?? [] as $fiscalYear) {
            $rows = array_merge($rows, $fiscalYear['actual_result_drivers'] ?? []);
        }

        return array_values(array_filter($rows, fn ($row) => is_array($row)));
    }

    private function analysisProjectTokens(array $facts, array $drivers): array
    {
        $names = array_column($drivers, 'project_name');
        $names = array_merge(
            $names,
            array_column($facts['metrics']['worst_projects'] ?? [], 'project_name'),
            array_column($facts['metrics']['target_worst_projects'] ?? [], 'project_name')
        );

        foreach ($facts['metrics']['fiscal_years'] ?? [] as $fiscalYear) {
            $names = array_merge($names, array_column($fiscalYear['risk_projects'] ?? [], 'project_name'));
        }

        $tokens = [];
        foreach (array_unique(array_filter($names)) as $name) {
            $name = trim((string) $name);
            $baseName = trim((string) preg_split('/[（(]/u', $name, 2)[0]);

            foreach ([$name, $baseName] as $token) {
                if (mb_strlen($token) >= 3) {
                    $tokens[$token] = true;
                }
            }
        }

        return array_keys($tokens);
    }

    private function mentionsProject(string $text, array $projectTokens): bool
    {
        $normalizedText = FinanceSnapshotService::projectNameKey($text);

        foreach ($projectTokens as $token) {
            if (str_contains($normalizedText, FinanceSnapshotService::projectNameKey($token))) {
                return true;
            }
        }

        return false;
    }

    private function enrichDataNotesWithActualCoverage(array $analysis, array $facts): array
    {
        $coverage = $facts['metrics']['data_status']['actual_coverage'] ?? [];
        if ($coverage === []) {
            return $analysis;
        }

        $includePeriod = count($coverage) > 1;
        $coverageNotes = array_map(function (array $row) use ($includePeriod) {
            $prefix = $includePeriod ? ((string) ($row['period'] ?? '') . ': ') : '';

            return sprintf(
                '%s実績対象%d件のうち、実績反映済み%d件、対象外%d件、要確認%d件。',
                $prefix,
                (int) ($row['selected_project_count'] ?? 0),
                (int) ($row['actual_reflected_count'] ?? 0),
                (int) ($row['not_expected_count'] ?? 0),
                (int) ($row['needs_review_count'] ?? 0)
            );
        }, $coverage);

        $existingNotes = array_values(array_filter(
            $analysis['data_notes'] ?? [],
            fn (string $note) => preg_match('/実績.*(?:欠損|未反映|対象外|要確認)/u', $note) !== 1
                && $this->dataNoteWithinPeriods($note, $facts['scope']['periods'] ?? [])
        ));
        $analysis['data_notes'] = array_slice(array_merge($coverageNotes, $existingNotes), 0, 3);

        return $analysis;
    }

    private function dataNoteWithinPeriods(string $note, array $periods): bool
    {
        if ($periods === []) {
            return true;
        }

        preg_match_all('/(20\d{2})年(\d{1,2})月/u', $note, $japaneseMatches, PREG_SET_ORDER);
        preg_match_all('/20\d{2}-\d{2}/', $note, $isoMatches);
        $referencedPeriods = array_map(
            fn (array $match) => sprintf('%04d-%02d', (int) $match[1], (int) $match[2]),
            $japaneseMatches
        );
        $referencedPeriods = array_merge($referencedPeriods, $isoMatches[0] ?? []);

        return array_diff(array_unique($referencedPeriods), $periods) === [];
    }

    private function withoutProjectClaims(string $summary, array $projectTokens): string
    {
        $sentences = preg_split('/(?<=[。！？])/u', $summary, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sentences = array_values(array_filter(
            $sentences,
            fn (string $sentence) => ! $this->mentionsProject($sentence, $projectTokens)
        ));

        $filtered = trim(implode('', $sentences));

        return $filtered !== '' || trim($summary) === ''
            ? $filtered
            : '全体数値と確認済みのプロジェクト別内訳を以下に示します。';
    }

    private function verifiedDriverHighlight(array $driver): ?string
    {
        $projectName = trim((string) ($driver['project_name'] ?? ''));
        if ($projectName === '') {
            return null;
        }

        $period = (string) ($driver['period'] ?? '');
        $expenseParts = $this->driverExpenseParts($driver);
        $expenseText = $expenseParts === []
            ? ''
            : '主な実績費用内訳は' . implode('、', $expenseParts) . '。';

        return sprintf(
            '%s%sは計画利益差%s（予算比%s）。%s',
            $projectName,
            $period !== '' ? "（{$period}）" : '',
            $this->driverProfitGap($driver, 'variance_vs_profit_plan'),
            $this->driverProfitGap($driver, 'variance_vs_yearly_plan'),
            $expenseText
        );
    }

    private function verifiedDriverRisk(array $driver): ?string
    {
        $projectName = trim((string) ($driver['project_name'] ?? ''));
        if ($projectName === '') {
            return null;
        }

        $period = (string) ($driver['period'] ?? '');
        $expenseParts = $this->driverExpenseParts($driver);
        $expenseText = $expenseParts === []
            ? ''
            : '確認対象となる主な実績費用内訳は' . implode('、', $expenseParts) . '。';

        return sprintf(
            '%s%sは利益が計画比%s（予算比%s）。%s',
            $projectName,
            $period !== '' ? "（{$period}）" : '',
            $this->driverProfitGap($driver, 'variance_vs_profit_plan'),
            $this->driverProfitGap($driver, 'variance_vs_yearly_plan'),
            $expenseText
        );
    }

    private function verifiedDriverAction(array $driver): ?string
    {
        $projectName = trim((string) ($driver['project_name'] ?? ''));
        if ($projectName === '') {
            return null;
        }

        $expenseParts = $this->driverExpenseParts($driver);
        $expenseText = $expenseParts === []
            ? '実績費用内訳'
            : '実績費用内訳（' . implode('、', $expenseParts) . '）';

        return sprintf(
            '%sの計画利益差%sについて、%sと月次計画の前提を照合する。',
            $projectName,
            $this->driverProfitGap($driver, 'variance_vs_profit_plan'),
            $expenseText
        );
    }

    private function driverExpenseParts(array $driver): array
    {
        $details = $driver['drivers'] ?? [];
        $parts = [];

        foreach ($details['top_expense_accounts'] ?? [] as $account) {
            $name = trim((string) ($account['account_name'] ?? ''));
            $amount = trim((string) ($account['amount'] ?? ''));
            if ($name !== '' && $this->isNonZeroMoney($amount)) {
                $parts[] = "{$name}{$amount}";
            }
            if (count($parts) >= 3) {
                return $parts;
            }
        }

        $components = [
            'cost_of_goods_sold' => '売上原価',
            'indirect_allocation_expense' => '間接費配賦',
            'performance_bonus_reserve' => '業績賞与',
            'reserve_expenses.basic_bonus' => '基本賞与',
            'reserve_expenses.paid_leave' => '有給',
            'reserve_expenses.welfare' => '福利厚生',
            'reserve_expenses.refresh' => 'リフレッシュ',
        ];

        foreach ($components as $key => $label) {
            $amount = trim((string) data_get($details, $key, ''));
            if ($this->isNonZeroMoney($amount)) {
                $parts[] = "{$label}{$amount}";
            }
            if (count($parts) >= 3) {
                return $parts;
            }
        }

        $sgAndA = trim((string) ($details['sg_and_a_expenses'] ?? ''));
        if ($parts === [] && $this->isNonZeroMoney($sgAndA)) {
            $parts[] = "販管費{$sgAndA}";
        }

        return $parts;
    }

    private function driverProfitGap(array $driver, string $varianceKey): string
    {
        $variance = $driver[$varianceKey] ?? [];
        $amount = (int) ($variance['profit_amount'] ?? 0);
        $display = (string) ($variance['profit_amount_display'] ?? $this->formatYen($amount));

        return $amount > 0 ? '+' . $display : $display;
    }

    private function isNonZeroMoney(string $amount): bool
    {
        return $amount !== '' && ! in_array($amount, ['0円', '-0円'], true);
    }

    private function factsForModel(array $value): array
    {
        $out = [];
        foreach ($value as $key => $item) {
            $out[$key] = is_array($item) ? $this->factsForModel($item) : $item;
        }

        foreach (['sales', 'expense', 'profit'] as $moneyKey) {
            $displayKey = "{$moneyKey}_display";
            if (array_key_exists($displayKey, $out)) {
                $out[$moneyKey] = $out[$displayKey];
                unset($out[$displayKey]);
            }
        }

        foreach (['sales_amount', 'expense_amount', 'profit_amount'] as $moneyKey) {
            $displayKey = "{$moneyKey}_display";
            if (array_key_exists($displayKey, $out)) {
                $out[$moneyKey] = $out[$displayKey];
                unset($out[$displayKey]);
            }
        }

        return $out;
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
                $projectName = (string) $project['project_name'];
                $rows[$projectId] ??= [
                    'project_id' => $projectId,
                    'project_name' => $projectName,
                    'is_internal_cost_center' => ! empty($project['is_internal_cost_center'])
                        || FinanceSnapshotService::isInternalCostCenter($projectName),
                    'is_summary_adjusted_project' => false,
                    'is_analysis_excluded_project' => false,
                    'totals' => $this->emptyScenarioTotals(),
                    'missing_settlement_periods' => [],
                    'forecast_periods' => [],
                ];

                foreach ($periods as $period) {
                    $month = $project['months'][$period] ?? null;
                    if (! $month) {
                        continue;
                    }
                    if ($this->monthUsesSummaryAdjustment($projectName, $period, $month, $resultBucket)) {
                        $rows[$projectId]['is_summary_adjusted_project'] = true;
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
            $row['is_summary_adjusted_project'] = ! empty($row['is_summary_adjusted_project'])
                || ! empty($row['is_internal_cost_center']);
            $row['is_analysis_excluded_project'] = ! empty($row['is_summary_adjusted_project']);
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
        $projectRows = array_values(array_filter(
            $projectRows,
            fn (array $project) => empty($project['is_analysis_excluded_project'])
        ));
        $projectRows = $this->sortProjectRowsByProfitGap($projectRows, $resultBucket);

        return array_map(
            fn (array $project) => $this->compactProjectRisk($project, $resultBucket),
            array_slice($projectRows, 0, 8)
        );
    }

    private function internalReferenceProjects(array $projectRows, string $resultBucket, int $limit = 5): array
    {
        $projectRows = array_values(array_filter(
            $projectRows,
            fn (array $project) => ! empty($project['is_analysis_excluded_project'])
        ));
        $projectRows = $this->sortProjectRowsByProfitGap($projectRows, $resultBucket);

        return array_map(
            fn (array $project) => $this->compactProjectRisk($project, $resultBucket),
            array_slice($projectRows, 0, max(0, $limit))
        );
    }

    private function compactProjectRisk(array $project, string $bucket): array
    {
        $totals = $project['totals'] ?? [];

        return [
            'project_id' => (int) ($project['project_id'] ?? 0),
            'project_name' => (string) ($project['project_name'] ?? ''),
            'is_internal_cost_center' => ! empty($project['is_internal_cost_center']),
            'is_summary_adjusted_project' => ! empty($project['is_summary_adjusted_project']),
            'is_analysis_excluded_project' => ! empty($project['is_analysis_excluded_project']),
            'yearly_plan' => $this->compactUnit($totals['yearly_plan'] ?? []),
            'analysis_result' => $this->compactUnit($totals[$bucket] ?? []),
            'totals' => [
                'yearly_plan' => $this->compactUnit($totals['yearly_plan'] ?? []),
                'analysis_result' => $this->compactUnit($totals[$bucket] ?? []),
            ],
            'variance_vs_yearly_plan' => $this->variance($totals[$bucket] ?? [], $totals['yearly_plan'] ?? []),
            'missing_settlement_periods' => $project['missing_settlement_periods'] ?? [],
            'forecast_periods' => $project['forecast_periods'] ?? [],
        ];
    }

    private function actualResultDriverContext(
        array $snapshots,
        array $periods,
        array $priorityProjects,
        int $limit
    ): array {
        $priorityIds = array_fill_keys(array_map(
            fn (array $project) => (int) ($project['project_id'] ?? 0),
            $priorityProjects
        ), true);
        $rows = [];

        foreach ($snapshots as $snapshot) {
            foreach ($snapshot['projects'] ?? [] as $project) {
                $projectId = (int) ($project['project_id'] ?? 0);
                $projectName = (string) ($project['project_name'] ?? '');

                if (FinanceSnapshotService::isInternalCostCenter($projectName)) {
                    continue;
                }

                foreach ($periods as $period) {
                    $month = $project['months'][$period] ?? null;
                    $actual = $month['settlement'] ?? [];
                    $details = $actual['actual_result_details'] ?? null;

                    if (! is_array($details) || ! empty($details['internal_transfer_expense'])) {
                        continue;
                    }

                    $profitPlan = $month['profit'] ?? [];
                    $yearlyPlan = $month['yearly_plan'] ?? [];
                    $varianceVsProfitPlan = $this->variance($actual, $profitPlan);
                    $varianceVsYearlyPlan = $this->variance($actual, $yearlyPlan);

                    $profitPlanScore = abs((int) ($varianceVsProfitPlan['profit_amount'] ?? 0));
                    $yearlyPlanScore = abs((int) ($varianceVsYearlyPlan['profit_amount'] ?? 0));

                    $rows[] = [
                        '_priority' => isset($priorityIds[$projectId]) ? 1 : 0,
                        '_score' => max($profitPlanScore, $yearlyPlanScore),
                        '_expense' => abs((int) ($actual['expense'] ?? 0)),
                        'project_id' => $projectId,
                        'project_name' => $projectName,
                        'period' => (string) $period,
                        'selection_basis' => $profitPlanScore >= $yearlyPlanScore ? '計画差分' : '予算差分',
                        'actual' => $this->compactUnit($actual),
                        'profit_plan' => $this->compactUnit($profitPlan),
                        'yearly_plan' => $this->compactUnit($yearlyPlan),
                        'variance_vs_profit_plan' => $varianceVsProfitPlan,
                        'variance_vs_yearly_plan' => $varianceVsYearlyPlan,
                        'drivers' => $this->compactActualResultDetails($details),
                    ];
                }
            }
        }

        usort($rows, function (array $left, array $right) {
            return ($right['_score'] <=> $left['_score'])
                ?: ($right['_priority'] <=> $left['_priority'])
                ?: ($right['_expense'] <=> $left['_expense']);
        });

        return array_map(function (array $row) {
            unset($row['_priority'], $row['_score'], $row['_expense']);

            return $row;
        }, array_slice($rows, 0, max(0, $limit)));
    }

    private function compactActualResultDetails(array $details): array
    {
        $money = fn (string $key) => $this->formatYen((float) ($details[$key] ?? 0));

        return [
            'data_source' => '保存済み実績',
            'calculation_source' => $this->calculationSourceLabel($details['source_mode'] ?? null),
            'manual_adjusted' => ! empty($details['manual_adjusted']),
            'external_sales' => $money('external_sales'),
            'internal_sales' => $money('internal_sales'),
            'cost_of_goods_sold' => $money('cost_of_goods_sold'),
            'sg_and_a_expenses' => $money('sg_and_a_expenses'),
            'indirect_allocation_expense' => $money('indirect_allocation_expense'),
            'performance_bonus_reserve' => $money('performance_bonus_reserve'),
            'reserve_expenses' => [
                'basic_bonus' => $money('basic_bonus_reserve'),
                'paid_leave' => $money('paid_leave_reserve'),
                'welfare' => $money('welfare_reserve'),
                'refresh' => $money('refresh_reserve'),
            ],
            'top_expense_accounts' => array_map(fn (array $account) => [
                'account_name' => (string) ($account['account_name'] ?? ''),
                'amount' => $this->formatYen((float) ($account['amount'] ?? 0)),
            ], $details['top_expense_accounts'] ?? []),
        ];
    }

    private function mergeDataStatus(array $snapshots, array $periods = []): array
    {
        $missing = [];
        $forecast = [];
        $actualResultPeriods = [];
        $googleSheetPeriods = [];
        $summaryAdjustment = null;
        $forecastRule = null;

        foreach ($snapshots as $snapshot) {
            $missing = array_merge($missing, $snapshot['data_status']['missing_settlement_periods'] ?? []);
            $forecast = array_merge($forecast, $snapshot['data_status']['forecast_periods'] ?? []);
            $actualResultPeriods = array_merge($actualResultPeriods, $snapshot['data_status']['actual_result_periods'] ?? []);
            $googleSheetPeriods = array_merge($googleSheetPeriods, $snapshot['data_status']['google_sheet_periods'] ?? []);
            $summaryAdjustment ??= $snapshot['data_status']['summary_adjustment'] ?? null;
            $forecastRule ??= $snapshot['data_status']['forecast_rule'] ?? null;
        }

        $periodSet = $periods !== [] ? array_fill_keys($periods, true) : null;
        $filterPeriods = fn (array $values) => array_values(array_filter(
            array_unique($values),
            fn (string $period) => $periodSet === null || isset($periodSet[$period])
        ));
        $coverage = $this->actualCoverageForPeriods($snapshots, $periods);
        $unexpectedMissingPeriods = array_values(array_map(
            fn (array $row) => (string) $row['period'],
            array_filter($coverage, fn (array $row) => (int) $row['needs_review_count'] > 0)
        ));

        return [
            'missing_settlement_periods' => $coverage !== [] ? $unexpectedMissingPeriods : $filterPeriods($missing),
            'forecast_periods' => $filterPeriods($forecast),
            'actual_result_periods' => $filterPeriods($actualResultPeriods),
            'google_sheet_periods' => $filterPeriods($googleSheetPeriods),
            'actual_coverage' => $coverage,
            'summary_adjustment' => $summaryAdjustment,
            'forecast_rule' => $forecastRule,
        ];
    }

    private function actualCoverageForPeriods(array $snapshots, array $periods = []): array
    {
        $periodSet = $periods !== [] ? array_fill_keys($periods, true) : null;
        $coverage = [];

        foreach ($snapshots as $snapshot) {
            $latestActualPeriod = (string) ($snapshot['latest_actual_period'] ?? $snapshot['latest_closed_period'] ?? '');

            foreach ($snapshot['projects'] ?? [] as $project) {
                foreach ($project['months'] ?? [] as $period => $month) {
                    if (($periodSet !== null && ! isset($periodSet[$period])) || ($latestActualPeriod !== '' && $period > $latestActualPeriod)) {
                        continue;
                    }

                    $coverage[$period] ??= [
                        'period' => (string) $period,
                        'selected_project_count' => 0,
                        'actual_reflected_count' => 0,
                        'not_expected_count' => 0,
                        'needs_review_count' => 0,
                    ];
                    $coverage[$period]['selected_project_count']++;

                    if (! empty($month['settlement']['has_data'])) {
                        $coverage[$period]['actual_reflected_count']++;
                        continue;
                    }

                    if ($this->actualNotExpectedForProjectMonth($project, (string) $period, $month)) {
                        $coverage[$period]['not_expected_count']++;
                    } else {
                        $coverage[$period]['needs_review_count']++;
                    }
                }
            }
        }

        ksort($coverage);

        return array_values($coverage);
    }

    private function actualNotExpectedForProjectMonth(array $project, string $period, array $month): bool
    {
        $periodStart = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $dateStart = $this->projectPeriodDate($project['date_start'] ?? null);
        $dateEnd = $this->projectPeriodDate($project['date_end'] ?? null);
        $completedAt = $this->projectPeriodDate($project['completed_at'] ?? null);

        if ($dateStart?->greaterThan($periodEnd)) {
            return true;
        }

        if ($dateEnd?->lessThan($periodStart) || $completedAt?->lessThan($periodStart)) {
            return true;
        }

        return empty($month['yearly_plan']['has_data']) && empty($month['profit']['has_data']);
    }

    private function projectPeriodDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function snapshotProjectRows(array $snapshot, string $resultBucket): array
    {
        $periods = $snapshot['period']['months'] ?? [];

        return array_map(
            fn (array $project) => $this->markProjectAnalysisFlags($project, $resultBucket, $periods),
            $snapshot['projects'] ?? []
        );
    }

    private function markProjectAnalysisFlags(array $project, string $resultBucket, array $periods = []): array
    {
        $projectName = (string) ($project['project_name'] ?? '');
        $isInternalCostCenter = ! empty($project['is_internal_cost_center'])
            || FinanceSnapshotService::isInternalCostCenter($projectName);
        $isSummaryAdjusted = $isInternalCostCenter;
        $months = $project['months'] ?? [];
        $periods = $periods !== [] ? $periods : array_keys($months);

        foreach ($periods as $period) {
            $month = $months[$period] ?? null;
            if (! is_array($month)) {
                continue;
            }

            if ($this->monthUsesSummaryAdjustment($projectName, (string) $period, $month, $resultBucket)) {
                $isSummaryAdjusted = true;
                break;
            }
        }

        $project['is_internal_cost_center'] = $isInternalCostCenter;
        $project['is_summary_adjusted_project'] = $isSummaryAdjusted;
        $project['is_analysis_excluded_project'] = $isSummaryAdjusted;

        return $project;
    }

    private function monthUsesSummaryAdjustment(string $projectName, string $periodKey, array $month, string $resultBucket): bool
    {
        if (! empty($month['settlement']['actual_result_details']['internal_transfer_expense'])) {
            return true;
        }

        $period = $this->periodFromKey($periodKey);
        if (! $period) {
            return FinanceSnapshotService::isInternalCostCenter($projectName);
        }

        foreach (array_values(array_unique(['yearly_plan', 'profit', $resultBucket])) as $bucket) {
            if ($this->financeSnapshots->shouldAdjustSummaryForProject($bucket, $projectName, $period, $month[$bucket] ?? [])) {
                return true;
            }
        }

        return false;
    }

    private function periodFromKey(string $periodKey): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $periodKey) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $periodKey . '-01')->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    private function actionableProjectCount(array $projectRows): int
    {
        return count(array_filter(
            $projectRows,
            fn (array $project) => empty($project['is_analysis_excluded_project'])
        ));
    }

    private function sortProjectRowsByProfitGap(array $projectRows, string $resultBucket): array
    {
        usort($projectRows, fn (array $left, array $right) => $this->projectProfitGap($left, $resultBucket) <=> $this->projectProfitGap($right, $resultBucket));

        return $projectRows;
    }

    private function projectProfitGap(array $project, string $resultBucket): int|float
    {
        if (isset($project['variance_vs_yearly_plan']['profit_amount'])) {
            return $project['variance_vs_yearly_plan']['profit_amount'];
        }

        if ($resultBucket === 'forecast' && isset($project['variance_vs_plan']['profit_amount'])) {
            return $project['variance_vs_plan']['profit_amount'];
        }

        $totals = $project['totals'] ?? [];

        return $this->variance($totals[$resultBucket] ?? [], $totals['yearly_plan'] ?? [])['profit_amount'] ?? 0;
    }

    private function internalCostCenterPolicy(): array
    {
        return [
            'totals' => '社内コストセンターはget_total_financeと同じ純額調整ルールで集計する。',
            'risk_lists' => '集計調整対象の社内部門は優先リスク一覧から除外し、参考部門としてのみ扱う。',
        ];
    }

    private function scenarioLabels(): array
    {
        return [
            'yearly_plan' => '予算: PMが作成し取締役が承認した年度開始時点の年間計画',
            'profit' => '計画: 予算を基準にしたKintone損益の月次修正計画。人員退職・体制変更・見積更新などで月次更新される',
            'settlement' => '実績: 保存済み実績を優先し、ない場合はGoogle Sheets実績',
            'forecast' => '着地見込み: 実績反映済み月は実績、未反映月は計画を見込みとして使用',
        ];
    }

    private function financeCommentContext(array $projectIds, array $periods, array $snapshots): array
    {
        $periods = array_values(array_unique(array_filter(
            $periods,
            fn (string $period) => preg_match('/^\d{4}-\d{2}$/', $period) === 1
        )));

        if ($projectIds === [] || $periods === []) {
            return [
                'comment_count' => 0,
                'context_rows' => [],
                'truncated' => false,
            ];
        }

        $maxCommentsPerProjectPeriod = 3;
        $maxRows = 60;
        $maxLoadedComments = 1000;

        $comments = ProjectFinanceComment::query()
            ->select('id', 'project_record_id', 'comment', 'type', 'period', 'created_at')
            ->with('project:id,name')
            ->whereIn('project_record_id', $projectIds)
            ->whereIn('period', $periods)
            ->orderByDesc('period')
            ->orderByDesc('created_at')
            ->limit($maxLoadedComments)
            ->get();

        $groups = [];
        $commentCount = 0;

        foreach ($comments as $comment) {
            $text = $this->sanitizeFinanceComment($comment->comment);
            if ($text === '') {
                continue;
            }

            $key = ((int) $comment->project_record_id) . '|' . (string) $comment->period;
            $groups[$key] ??= [
                'project_id' => (int) $comment->project_record_id,
                'project_name' => (string) ($comment->project?->name ?? ''),
                'period' => (string) $comment->period,
                'comments' => [],
            ];

            if (count($groups[$key]['comments']) < $maxCommentsPerProjectPeriod) {
                $groups[$key]['comments'][] = [
                    'type' => $comment->type ?: null,
                    'commented_at' => $comment->created_at?->format('Y-m-d'),
                    'comment' => $text,
                ];
            }

            $commentCount++;
        }

        $monthLookup = $this->snapshotMonthLookup($snapshots, $periods);
        $rows = [];

        foreach ($groups as $key => $group) {
            if (count($rows) >= $maxRows) {
                break;
            }

            $month = $monthLookup[$key] ?? null;
            $row = [
                'project_id' => $group['project_id'],
                'project_name' => $month['project_name'] ?? $group['project_name'],
                'period' => $group['period'],
                'comments' => $group['comments'],
            ];

            if ($month) {
                $monthData = $month['month'];
                $actual = $monthData['settlement'] ?? [];
                $profitPlan = $monthData['profit'] ?? [];
                $yearlyPlan = $monthData['yearly_plan'] ?? [];
                $actualHasData = ! empty($actual['has_data']);

                $row['actual_data_available'] = $actualHasData;
                $row['profit_plan'] = $this->compactUnit($profitPlan);
                $row['yearly_plan'] = $this->compactUnit($yearlyPlan);

                if ($actualHasData) {
                    $row['actual'] = $this->compactUnit($actual);
                    $row['variance_actual_vs_profit_plan'] = $this->variance($actual, $profitPlan);
                    $row['variance_actual_vs_yearly_plan'] = $this->variance($actual, $yearlyPlan);
                }
            }

            $rows[] = $row;
        }

        return [
            'comment_count' => $commentCount,
            'context_rows' => $rows,
            'truncated' => $comments->count() >= $maxLoadedComments || count($groups) > $maxRows,
            'max_comments_per_project_period' => $maxCommentsPerProjectPeriod,
        ];
    }

    private function snapshotMonthLookup(array $snapshots, array $periods): array
    {
        $periodSet = array_flip($periods);
        $lookup = [];

        foreach ($snapshots as $snapshot) {
            foreach ($snapshot['projects'] ?? [] as $project) {
                $projectId = (int) ($project['project_id'] ?? 0);
                foreach (($project['months'] ?? []) as $period => $month) {
                    if (! isset($periodSet[$period])) {
                        continue;
                    }

                    $lookup[$projectId . '|' . $period] = [
                        'project_name' => (string) ($project['project_name'] ?? ''),
                        'month' => $month,
                    ];
                }
            }
        }

        return $lookup;
    }

    private function snapshotPeriods(array $snapshots): array
    {
        $periods = [];

        foreach ($snapshots as $snapshot) {
            $periods = array_merge($periods, $snapshot['period']['months'] ?? []);
        }

        return array_values(array_unique($periods));
    }

    private function sanitizeFinanceComment(?string $comment): string
    {
        $text = trim((string) $comment);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s*\[To:[^:\]\|]+(?:\|\d+)?:\]\s*/u', ' ', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($text === '' || $this->looksLikeSensitiveComment($text) || $this->looksLikeLowInformationComment($text)) {
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

    private function looksLikeLowInformationComment(string $comment): bool
    {
        $normalized = mb_strtolower(preg_replace('/[\s\p{P}\p{S}]+/u', '', $comment) ?? '');
        if (mb_strlen($normalized) < 4) {
            return true;
        }

        if (in_array($normalized, ['test', 'testing', 'テスト', '確認', '確認済み', 'なし', '特になし'], true)) {
            return true;
        }

        if (preg_match('/^[a-z]+$/i', $normalized) !== 1 || strlen($normalized) >= 20) {
            return false;
        }

        return count(array_unique(str_split($normalized))) <= 2;
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

    private function calculationSourceLabel(mixed $sourceMode): ?string
    {
        return match ((string) $sourceMode) {
            'csv_finalized' => 'CSV確定値',
            'reserve_csv_uploaded' => '積立金CSV反映値',
            'auto_calculated' => 'システム計算値',
            default => null,
        };
    }

    private function stringValue(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return str_ireplace(
            [
                '予算未計上費用が発生し、計画との差異要因',
                '予算未計上の販管費',
                '予算未計上の費用',
                '予算未計上費用',
                'Google Sheets settlement',
                'reserve_csv_uploaded',
                'auto_calculated',
                'csv_finalized',
                'profit_forecast',
                'actual_result',
                'ActualResult',
                'yearly_plan',
                'settlement',
            ],
            [
                '予算0円に対する実績費用があり、予算差分の要因',
                '予算0円に対して発生した販管費',
                '予算0円に対して発生した費用',
                '予算0円に対する実績費用',
                'Google Sheets実績',
                '積立金CSV反映値',
                'システム計算値',
                'CSV確定値',
                'Kintone損益見込み',
                '保存済み実績',
                '保存済み実績',
                '予算',
                '実績',
            ],
            trim((string) $value)
        );
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
