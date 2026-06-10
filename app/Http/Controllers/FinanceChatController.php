<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenAI;

/**
 * Finance AI Chat — director-facing finance and data-quality tools.
 *
 * Route: POST /mcp/chat
 */
class FinanceChatController extends Controller
{
    // =========================================================================
    // Role helpers
    // =========================================================================

    private static function resolveRole(\App\Models\User $user): string
    {
        $adminIds = [608, 610];
        if (in_array($user->id, $adminIds)) return 'admin';
        $pos = (int) ($user->position_id ?? 99);
        if ($pos <= 5) return 'director';    // 取締役・代表
        if ($pos <= 9) return 'manager';     // 執行役員・マネージャー・SV
        return 'member';
    }

    // =========================================================================
    // Tool definitions
    // =========================================================================

    private static function tools(): array
    {
        return [
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_variance_summary',
                    'description' => '単月のGoogle Sheets実績 vs 損益の乖離ランキングを返します。月省略時は毎月20日反映ルールに基づく最新実績反映月を使います。年度合計として扱ってはいけません。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'year'  => ['type' => 'integer', 'description' => '対象年（例: 2026）'],
                            'month' => ['type' => 'integer', 'description' => '対象月 1–12（省略時: 最新の実績反映月。20日前は2ヶ月前、20日以降は前月）'],
                            'limit' => ['type' => 'integer', 'description' => '返す件数。省略時は10件。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_fiscal_year_finance_summary',
                    'description' => '財務年度（3月開始、翌2月終了）の年間計画・Google Sheets実績・損益・着地見込み・計画差分を返します。着地見込みは、実績反映済み月はGoogle Sheets実績のみ、未反映の将来月はKintone損益を使用します。「今期の財務状況」「FY2026の着地見込み」「年度の計画対比」など取締役向けの質問で使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year' => ['type' => 'integer', 'description' => '財務年度の開始年（例: FY2026 = 2026年3月-2027年2月）'],
                            'project_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => '対象プロジェクトID。省略時は対象期間内の全プロジェクト。',
                            ],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済み基準月 YYYY-MM。省略時は毎月20日反映ルールから自動判定。'],
                            'limit' => ['type' => 'integer', 'description' => 'リスク案件の最大件数。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_project_fiscal_year_pl',
                    'description' => '指定プロジェクトの財務年度（3月-翌2月）の月別P&L、年間計画、Google Sheets実績、着地見込み、計画差分を返します。着地見込みは、実績反映済み月はGoogle Sheets実績のみ、未反映の将来月はKintone損益を使用します。「この案件の年度PL」「プロジェクトの通期見込み」に使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'project_name' => ['type' => 'string', 'description' => 'プロジェクト名（部分一致）'],
                            'project_id'   => ['type' => 'integer', 'description' => 'プロジェクトID（project_nameより優先）'],
                            'fiscal_year'  => ['type' => 'integer', 'description' => '財務年度の開始年'],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済み基準月 YYYY-MM。省略時は毎月20日反映ルールから自動判定。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_project_variance_explanation',
                    'description' => '指定プロジェクト・指定月のGoogle Sheets実績と計画（既定はKintone損益）の差異、差異額、差異率、該当月のproject_finance_commentsを返します。「なぜ実績が計画と違う？」「差異理由は？」「コメントに理由はある？」など、単月の実績差異理由を説明する質問で必ず使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'project_name' => ['type' => 'string', 'description' => 'プロジェクト名（部分一致）'],
                            'project_id'   => ['type' => 'integer', 'description' => 'プロジェクトID（project_nameより優先）'],
                            'period'       => ['type' => 'string', 'description' => '対象月 YYYY-MM。省略時は最新の実績反映月。'],
                            'comparison_base' => [
                                'type' => 'string',
                                'description' => '比較対象。profit=Kintone損益計画（既定）、yearly_plan=年間予算。',
                                'enum' => ['profit', 'yearly_plan'],
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_finance_forecast_ranking',
                    'description' => '財務年度の着地見込みで、年間計画に対して利益が悪いプロジェクトをランキングします。着地見込みは、実績反映済み月はGoogle Sheets実績のみ、未反映の将来月はKintone損益を使用します。「利益が危ない案件」「計画未達になりそうな案件」に使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year' => ['type' => 'integer', 'description' => '財務年度の開始年'],
                            'project_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                            ],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済み基準月 YYYY-MM。省略時は毎月20日反映ルールから自動判定。'],
                            'limit' => ['type' => 'integer', 'description' => '返す件数。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_finance_data_quality',
                    'description' => '財務データ品質を確認します。年間計画・損益・Google Sheets実績の欠損、最新実績反映月、重複した月次損益レコード、実績未反映月を返します。回答の数値が怪しい場合や、データが正しく戻っているか確認するときに使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year' => ['type' => 'integer', 'description' => '財務年度の開始年'],
                            'project_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                            ],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済み基準月 YYYY-MM。省略時は毎月20日反映ルールから自動判定。'],
                            'limit' => ['type' => 'integer', 'description' => '返す問題件数。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'send_finance_comment',
                    'description' => '指定プロジェクト・指定月の損益コメント欄にメッセージを投稿します。「田中PMに実績の乖離について説明を求めるコメントを送って」などに使用します。ユーザーが明示的に送信を依頼した場合のみ呼び出してください。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'project_name' => ['type' => 'string'],
                            'project_id'   => ['type' => 'integer'],
                            'period'       => ['type' => 'string', 'description' => '対象月 YYYY-MM 形式（例: 2026-03）'],
                            'message'      => ['type' => 'string', 'description' => '投稿するコメント本文'],
                        ],
                        'required' => ['period', 'message'],
                    ],
                ],
            ],
            // ---- New tools ----
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_monthly_trend',
                    'description' => '財務年度の月次着地見込み対計画差分の推移を返します。「今期は改善しているの？」「着地見込みのトレンドを見せて」「月次で差分が大きくなったのはいつ？」に使用します。累積計画対比・月次差分変化も返します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year'  => ['type' => 'integer', 'description' => '財務年度の開始年'],
                            'project_ids'  => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '絞り込み対象プロジェクトID。省略時は全プロジェクト合計。'],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済基準月 YYYY-MM。省略時は自動判定。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_project_health_matrix',
                    'description' => '全プロジェクトの健全度をトラフィックライト（🔴🟡🟢）で一覧表示します。PM名・著地見込み差分・余算利用率・データ信頼度・最新コメントを含めます。「全案件の状態を一目で見たい」「赤の案件は何件？」に使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year'  => ['type' => 'integer'],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済基準月 YYYY-MM。省略時は自動判定。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_revenue_concentration',
                    'description' => '著地見込み売上のプロジェクト別集中度を分析します。「トップ3社で売上の何％を占めている？」「売上集中リスクは？」などに使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year'  => ['type' => 'integer'],
                            'as_of_period' => ['type' => 'string'],
                            'limit'        => ['type' => 'integer', 'description' => '返すプロジェクト件数。省略時は10件。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'compare_fiscal_years',
                    'description' => '2つの財務年度を年度比較（前期FY vs 今期FY）します。「去年と比べてどう？」「YoY成長は？」に使用します。着地見込み・年間計画・YoY差分を返します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'base_fiscal_year'    => ['type' => 'integer', 'description' => '比較元の財務年度（省略時は前期）'],
                            'compare_fiscal_year' => ['type' => 'integer', 'description' => '比較先の財務年度（省略時は今期）'],
                            'project_ids'         => ['type' => 'array', 'items' => ['type' => 'integer']],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_pm_finance_summary',
                    'description' => '指定PMが担当するプロジェクトだけに絞った財務年度サマリーを返します。PMの担当は project_members.authority=1 で判定します。「井上PMの案件の着地見込み」「このPMの担当プロジェクトの財務状況」に使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'pm_name'      => ['type' => 'string', 'description' => 'PM名またはユーザーコード。部分一致可。'],
                            'pm_user_id'   => ['type' => 'integer', 'description' => 'PMのユーザーID。pm_nameより優先。'],
                            'fiscal_year'  => ['type' => 'integer', 'description' => '財務年度の開始年。'],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済基準月 YYYY-MM。省略時は自動判定。'],
                            'limit'        => ['type' => 'integer', 'description' => 'リスク案件の最大件数。'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_pm_finance_ranking',
                    'description' => 'PM別に担当プロジェクトの年間計画・実績累計・着地見込み・計画差分を集計してランキングします。「PM別の売上/利益ランキング」「PMごとの財務リスク」に使用します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year'  => ['type' => 'integer', 'description' => '財務年度の開始年。'],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済基準月 YYYY-MM。省略時は自動判定。'],
                            'limit'        => ['type' => 'integer', 'description' => '返すPM件数。'],
                            'sort_by'      => [
                                'type' => 'string',
                                'description' => 'profit_gap_worst（既定）, forecast_sales_desc, forecast_profit_desc, expense_desc, project_count_desc, risk_count_desc',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],

        ];
    }

    // =========================================================================
    // Role-based tool filtering
    // =========================================================================

    /**
     * Finance tools are for directors/admins/managers.
     */
    private static function toolsForRole(string $role): array
    {
        return $role === 'member' ? [] : self::tools();
    }

    // =========================================================================
    // Role-based system prompt
    // =========================================================================

    private static function systemPrompt(\App\Models\User $user, string $role, int $financeFiscalYear, string $latestActualPeriod): string
    {
        $now = Carbon::now();
        $nowStr = $now->format('Y年m月d日');

        // Fiscal quarter awareness (Q1=Mar-May, Q2=Jun-Aug, Q3=Sep-Nov, Q4=Dec-Feb)
        $month = (int) $now->month;
        [$qNum, $qMonths] = match (true) {
            $month >= 3 && $month <= 5  => [1, 'Q1: 3月・4月・5月'],
            $month >= 6 && $month <= 8  => [2, 'Q2: 6月・7月・8月'],
            $month >= 9 && $month <= 11 => [3, 'Q3: 9月・10月・11月'],
            default                     => [4, 'Q4: 12月・1月・2月'],
        };

        $roleDesc = match ($role) {
            'director' => "あなたは取締役・上位役員向けの財務AIアシスタントです。全プロジェクトの損益、実績、年間計画、着地見込み、データ品質を確認できます。",
            'admin'    => "あなたは管理部門向けの財務AIアシスタントです。全プロジェクトの損益、実績、年間計画、着地見込み、データ品質を確認できます。",
            'manager'  => "あなたはマネージャー向けの財務AIアシスタントです。プロジェクト財務状況とデータ品質を確認できます。",
            default    => "あなたは社員向けのAIアシスタントです。このチャットでは財務データへのアクセス権がありません。",
        };

        $toolGuide = match ($role) {
            'director', 'admin' => "利用可能なデータ: 年間計画、Kintone損益、Google Sheets実績、着地見込み、財務データ品質、差異理由コメント、損益コメント送信、月次トレンド、健全度マトリクス、売上集中リスク、年度比較、PM別財務",
            'manager'           => "利用可能なデータ: 年間計画、Kintone損益、Google Sheets実績、着地見込み、財務データ品質、差異理由コメント、月次トレンド、健全度マトリクス、PM別財務",
            default             => "利用可能なデータ: なし",
        };

        return <<<TXT
{$roleDesc}

現在の情報:
- 日時: {$nowStr}
- 現在の財務年度: FY{$financeFiscalYear}（3月開始、翌2月終了）
- 現在の財務四半期: {$qMonths}（Q{$qNum}）※Q1=3-5月, Q2=6-8月, Q3=9-11月, Q4=12-2月
- 最新の実績反映月: {$latestActualPeriod}（Google Sheets実績は毎月20日ごろ前月分が反映）
- ログインユーザー: {$user->name}（ID: {$user->id}、役割: {$role}）
- {$toolGuide}

回答のルール:
- 必ず日本語で回答する
- 「少々お待ちください」「確認します」「調べます」などの待機文だけで回答を終えてはいけない。必要な場合はこの場でツールを呼び出してから最終回答する
- 数値は具体的に引用し、視点を添えて説明する（例: 「利益が計画比-15%、2,300万円下回り。年間目標の23%分のラグ」）
- 財務以外（目標、勤怠、承認、雑談）の質問には、このチャットは財務専用だと短く伝える
- 財務の「今期」「年度」「着地」「経営状況」は、月次ではなく財務年度（3月-翌2月）の年間計画・Google Sheets実績・着地見込みで回答する
- Google Sheets実績は毎月20日ごろ前月分が反映される。ユーザーが明示しない限り、この反映済み月を基準に回答する
- 着地見込みは、実績反映済み月はGoogle Sheets実績のみ、未反映の将来月はKintone損益を足して計算する。年間計画は比較対象であり、実績欠損月の予測値としては使わない
- ただし、実績反映済み月にGoogle Sheets実績がない場合は、Kintone損益や年間計画を実績として扱わない。完了済みプロジェクトの完了後月も予測補完しない
- 年度財務サマリーでは、年間計画=yearly_plan_totals、最新実績（単月）=latest_actual_month_totals、実績累計=actual_to_date_totals、着地見込み=forecast_totals、計画乖離=forecast_vs_yearly_plan を使う
- 「最新実績（YYYY-MM）」と書く場合は、財務年度合計・実績累計・着地見込みではなく latest_actual_month_totals だけを使う
- 月次の「乖離」は単月のGoogle Sheets実績 vs Kintone損益として扱う。年度合計として説明しない
- get_variance_summary の結果は単月データ。年度合計として説明しない
- 特定プロジェクトの「なぜ実績が計画と違う」「差異理由」「コメントに理由があるか」という質問では get_project_variance_explanation を使う
- 3月と4月など複数月の差異理由を聞かれた場合、対象月ごとに get_project_variance_explanation を呼び出す
- 月だけ指定され年がない場合は、現在の財務年度 FY{$financeFiscalYear} の月として解釈する（例: FY{$financeFiscalYear}の3月={$financeFiscalYear}-03、4月={$financeFiscalYear}-04）
- get_project_variance_explanation の comments は project_finance_comments の記録。コメントがある場合は「コメントでは」「記録では」と明示して理由を説明する
- コメントがない差異理由は推測しない。数値上の差異箇所を説明し、「理由コメントはありません」「要確認」と伝える
- get_project_variance_explanation の *_display / *_amount_display は表示用の金額。独自に億円換算し直さず、その文字列を使う
- 「今期Q{$qNum}」の成績質問には、Q{$qNum}に属する3ヶ月の月次データまたは月次トレンドツールを使う
- PM別の質問（「井上PMの案件」「PM別ランキング」など）は get_pm_finance_summary または get_pm_finance_ranking を使う。PM担当は project_members.authority=1 の関係を正とする
- ツール結果に alert_count > 0 または🔴の案件がある場合、回答内に「❗ 要注意：[N]件のアラート案件」として先頭で明示する
- データの信頼性や欠損が気になる場合は get_finance_data_quality を使う
- ツールで取得できないことは「データがありません」と正直に伝える
- send_finance_comment はユーザーが明示的に「送って」「投稿して」と言った場合のみ呼び出す
- 回答は簡潔に（500字以内を目安）

集計ルール（合計値への含め方）:
- 「間接費部門」と「積立部門」は売上を合計に含まない社内コストセンター。費用の純額（支出－収入）のみが合計費用に加算される。売上合計には貢献しない
- 「経営管理本部」はFY2025（2025-03〜2026-02）のみ同様の除外扱い。FY2026以降は通常プロジェクトとして売上・費用ともに合計に含まれる
- 「今期の合計に含まれていないプロジェクトは？」と聞かれた場合、FY2026であれば「間接費部門」と「積立部門」の2つと答える
TXT;
    }

    // =========================================================================
    // Chat endpoint
    // =========================================================================

    public function chat(Request $request): JsonResponse
    {
        $apiKey = config('services.openai.api_key');
        abort_if(!$apiKey, 500, 'OpenAI APIキーが設定されていません。');

        $validated = $request->validate([
            'messages'           => 'required|array|min:1|max:20',
            'messages.*.role'    => 'required|string|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
        ]);

        $user  = Auth::user();
        $role  = self::resolveRole($user);
        $now   = Carbon::now();
        $financeFiscalYear = $now->month >= 3 ? (int) $now->year : (int) $now->year - 1;
        $latestActualPeriod = $now->copy()
            ->startOfMonth()
            ->subMonthsNoOverflow($now->day >= 20 ? 1 : 2)
            ->format('Y-m');

        $systemPrompt = self::systemPrompt($user, $role, $financeFiscalYear, $latestActualPeriod);

        $history  = array_slice($validated['messages'], -10);
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );
        $latestUserContent = $this->latestUserContent($history);
        $forceVarianceExplanationTool = $this->isProjectVarianceReasonQuestion($latestUserContent);

        $client = OpenAI::client($apiKey);
        $model  = config('services.openai.chat_model', 'gpt-4.1-mini');
        $tools  = self::toolsForRole($role);

        $financeMcp = app(FinanceToolController::class);
        if ($role !== 'member' && ! empty($tools) && $forceVarianceExplanationTool) {
            $directRequests = $this->varianceExplanationArgsFromQuestion(
                $latestUserContent,
                $financeFiscalYear,
                $latestActualPeriod
            );

            if ($directRequests !== []) {
                $results = array_map(
                    fn (array $args) => $this->dispatchTool('get_project_variance_explanation', $args, $financeMcp),
                    $directRequests
                );

                return response()->json([
                    'reply' => $this->formatVarianceExplanationReply($results),
                ]);
            }
        }

        // Agentic loop — cap at 5 iterations
        for ($i = 0; $i < 5; $i++) {
            $payload = [
                'model'       => $model,
                'messages'    => $messages,
                'max_tokens'  => 1000,
            ];
            if (! empty($tools)) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = ($i === 0 && $forceVarianceExplanationTool)
                    ? ['type' => 'function', 'function' => ['name' => 'get_project_variance_explanation']]
                    : 'auto';
            }

            $response = $client->chat()->create($payload);

            $choice = $response->choices[0];

            if ($choice->finishReason === 'stop') {
                $content = trim((string) ($choice->message->content ?? ''));
                if (! empty($tools) && $this->isWaitingOnlyResponse($content)) {
                    if ($i >= 4) {
                        return response()->json([
                            'reply' => '財務データ取得のためのツール呼び出しが発生しませんでした。プロジェクト名と対象月を指定してもう一度質問してください。',
                        ]);
                    }

                    $messages[] = [
                        'role' => 'user',
                        'content' => '内部指示: 待機文だけで終了せず、必要な財務ツールを今すぐ呼び出して回答してください。複数月の差異理由は対象月ごとに get_project_variance_explanation を呼び出してください。',
                    ];
                    continue;
                }

                return response()->json(['reply' => $content]);
            }

            if ($choice->finishReason === 'tool_calls') {
                $toolCallsPayload = array_map(fn ($tc) => [
                    'id'       => $tc->id,
                    'type'     => 'function',
                    'function' => [
                        'name'      => $tc->function->name,
                        'arguments' => $tc->function->arguments,
                    ],
                ], $choice->message->toolCalls ?? []);

                $messages[] = [
                    'role'       => 'assistant',
                    'content'    => $choice->message->content,
                    'tool_calls' => $toolCallsPayload,
                ];

                foreach ($choice->message->toolCalls ?? [] as $toolCall) {
                    $name = $toolCall->function->name;
                    $args = json_decode($toolCall->function->arguments, true) ?? [];

                    if ($role === 'member') {
                        $resultJson = json_encode(['error' => 'このチャットでは財務データへのアクセス権がありません。']);
                        $messages[] = ['role' => 'tool', 'tool_call_id' => $toolCall->id, 'content' => $resultJson];
                        continue;
                    }

                    try {
                        $result = $this->dispatchTool($name, $args, $financeMcp);
                        $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);
                    } catch (\Throwable $e) {
                        $resultJson = json_encode(['error' => $e->getMessage()]);
                    }

                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content'      => $resultJson,
                    ];
                }

                continue;
            }

            break;
        }

        return response()->json(['reply' => 'データの取得に失敗しました。もう一度お試しください。']);
    }

    private function isWaitingOnlyResponse(string $content): bool
    {
        if ($content === '') {
            return false;
        }

        $plain = trim(strip_tags($content));
        $isShort = mb_strlen($plain) <= 300;
        $looksLikeWaiting = preg_match(
            '/少々お待ち|お待ちください|確認します|確認いたします|調べます|確認して.*回答|確認して.*お伝え/u',
            $plain
        ) === 1;

        return $isShort && $looksLikeWaiting;
    }

    private function latestUserContent(array $history): string
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? null) === 'user') {
                return trim((string) ($history[$i]['content'] ?? ''));
            }
        }

        return '';
    }

    private function isProjectVarianceReasonQuestion(string $content): bool
    {
        $plain = trim($content);
        if ($plain === '') {
            return false;
        }

        $hasActual = preg_match('/実績/u', $plain) === 1;
        $hasPlan = preg_match('/計画|予算|損益/u', $plain) === 1;
        $asksReasonOrGap = preg_match('/理由|なぜ|何故|差異|乖離|異な|違う|違い|ずれ|ズレ/u', $plain) === 1;
        if (! $hasActual || ! $hasPlan || ! $asksReasonOrGap) {
            return false;
        }

        if (preg_match('/^(今期|今年度|今月|全体|全社|全プロジェクト|プロジェクト全体|会社全体|FY\d{4})(?:は|の|で)/u', $plain) === 1) {
            return false;
        }

        return preg_match('/(?:「[^」]+」|『[^』]+』|[A-Za-z0-9Ａ-Ｚａ-ｚ０-９ァ-ヶｦ-ﾟ一-龯ー]{2,})(?:プロジェクト|案件)?(?:は|の|で)/u', $plain) === 1;
    }

    private function varianceExplanationArgsFromQuestion(string $content, int $financeFiscalYear, string $latestActualPeriod): array
    {
        $projectName = $this->extractProjectNameFromVarianceQuestion($content);
        if ($projectName === '') {
            return [];
        }

        $comparisonBase = preg_match('/予算/u', $content) === 1
            && preg_match('/計画|損益/u', $content) !== 1
                ? 'yearly_plan'
                : 'profit';

        return array_map(
            fn (string $period) => [
                'project_name' => $projectName,
                'period' => $period,
                'comparison_base' => $comparisonBase,
            ],
            $this->periodsFromVarianceQuestion($content, $financeFiscalYear, $latestActualPeriod)
        );
    }

    private function extractProjectNameFromVarianceQuestion(string $content): string
    {
        $plain = trim($content);
        if (preg_match('/[「『]([^」』]+)[」』]/u', $plain, $match) === 1) {
            return trim($match[1]);
        }

        foreach (['プロジェクト', '案件'] as $suffix) {
            $position = mb_strpos($plain, $suffix);
            if ($position !== false) {
                $candidate = $this->cleanProjectNameCandidate(mb_substr($plain, 0, $position));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        foreach (['について', 'は', 'で'] as $marker) {
            $position = mb_strpos($plain, $marker);
            if ($position !== false) {
                $candidate = $this->cleanProjectNameCandidate(mb_substr($plain, 0, $position));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return '';
    }

    private function cleanProjectNameCandidate(string $value): string
    {
        $value = preg_replace('/FY\d{4}年?/iu', ' ', $value) ?? $value;
        $value = preg_replace('/20\d{2}[-\/年]\d{1,2}月?/u', ' ', $value) ?? $value;
        $value = preg_replace('/\d{1,2}月/u', ' ', $value) ?? $value;
        $value = preg_replace('/[、。:：\s]+/u', ' ', $value) ?? $value;
        $value = trim($value);
        $value = preg_replace('/^(?:の|は|で|と|\s)+|(?:の|は|で|と|\s)+$/u', '', $value) ?? $value;

        return trim($value);
    }

    private function periodsFromVarianceQuestion(string $content, int $financeFiscalYear, string $latestActualPeriod): array
    {
        $periods = [];

        if (preg_match_all('/(?<!FY)(20\d{2})[-\/年](\d{1,2})月?/u', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $month = max(1, min(12, (int) $match[2]));
                $periods[] = sprintf('%04d-%02d', (int) $match[1], $month);
            }
        }

        if ($periods !== []) {
            return array_values(array_unique($periods));
        }

        $fiscalYear = preg_match('/FY(\d{4})/iu', $content, $fyMatch) === 1
            ? (int) $fyMatch[1]
            : $financeFiscalYear;

        if (preg_match_all('/(\d{1,2})月/u', $content, $matches)) {
            foreach ($matches[1] as $monthValue) {
                $month = max(1, min(12, (int) $monthValue));
                $year = $month >= 3 ? $fiscalYear : $fiscalYear + 1;
                $periods[] = sprintf('%04d-%02d', $year, $month);
            }
        }

        return $periods !== []
            ? array_values(array_unique($periods))
            : [$latestActualPeriod];
    }

    private function formatVarianceExplanationReply(array $results): string
    {
        $blocks = array_map(fn (array $result) => $this->formatVarianceExplanationBlock($result), $results);

        return implode("\n\n", array_filter($blocks));
    }

    private function formatVarianceExplanationBlock(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $projectName = (string) ($result['project']['name'] ?? '対象プロジェクト');
        $period = (string) ($result['period'] ?? '');
        $baseLabel = (string) ($result['comparison_base_label'] ?? '計画');
        $lines = ["【{$period} {$projectName}】"];

        if (empty($result['actual_data_available'])) {
            $lines[] = "Google Sheets実績がないため、{$baseLabel}との差異理由は判定できません。";
        }

        if (empty($result['plan_data_available'])) {
            $lines[] = "{$baseLabel}がないため、実績との差異理由は判定できません。";
        }

        $variance = $result['variance_actual_vs_plan'] ?? null;
        if (is_array($variance)) {
            $lines[] = sprintf(
                '実績 - %s の差異は、売上 %s、販管費 %s、利益 %s です。',
                $baseLabel,
                $variance['sales_amount_display'] ?? '不明',
                $variance['expense_amount_display'] ?? '不明',
                $variance['profit_amount_display'] ?? '不明'
            );
        }

        $comments = array_values(array_filter($result['comments'] ?? [], fn ($comment) => is_array($comment)));
        if ($comments !== []) {
            $firstComment = (string) ($comments[0]['comment'] ?? '');
            if ($firstComment !== '') {
                $lines[] = "コメントでは「{$firstComment}」と記録されています。";
            }

            if (count($comments) > 1) {
                $secondComment = (string) ($comments[1]['comment'] ?? '');
                if ($secondComment !== '') {
                    $lines[] = "追加コメントとして「{$secondComment}」もあります。";
                }
            }
        } else {
            $lines[] = 'この月の理由コメントはありません。数値上の差異は確認できますが、原因は要確認です。';
        }

        return implode("\n", $lines);
    }

    // =========================================================================
    // Tool dispatcher — routes to the correct domain MCP
    // =========================================================================

    private function dispatchTool(
        string $name,
        array $args,
        FinanceToolController $finance
    ): array {
        $financeTools   = [
            'get_variance_summary',
            'get_fiscal_year_finance_summary',
            'get_project_fiscal_year_pl',
            'get_project_variance_explanation',
            'get_finance_forecast_ranking',
            'get_finance_data_quality',
            'send_finance_comment',
            'get_monthly_trend',
            'get_project_health_matrix',
            'get_revenue_concentration',
            'compare_fiscal_years',
            'get_pm_finance_summary',
            'get_pm_finance_ranking',
        ];

        if (in_array($name, $financeTools)) {
            return $finance->executeTool($name, $args);
        }

        return ['error' => "Unknown tool: {$name}"];
    }
}
