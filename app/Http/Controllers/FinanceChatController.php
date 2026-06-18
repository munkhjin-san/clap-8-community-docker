<?php

namespace App\Http\Controllers;

use App\Services\FinanceSnapshotService;
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

    private function active_user(): \App\Models\User
    {
        $user = Auth::user();
        $sub = $user->linked()
            ->where('main_id', $user->id)
            ->wherePivot('active', 1)
            ->first();

        return $sub ?: $user;
    }

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
                    'description' => '財務年度（3月開始、翌2月終了）の予算（yearly_plan）・計画（Kintone損益）・Google Sheets実績・実績/着地見込み（予測込み）・差分を返します。実績/着地見込みはget_total_financeの予測ONと同じく、Google Sheets実績がある月は実績を使い、該当月シートがない月はKintone損益を見込み値として使用します。「今期の財務状況」「FY2026の着地見込み」「年度の計画対比」など取締役向けの質問で使用します。',
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
                    'description' => '指定プロジェクトの財務年度（3月-翌2月）の月別P&L、予算（yearly_plan）、計画（Kintone損益）、Google Sheets実績、実績/着地見込み（予測込み）、差分を返します。「この案件の年度PL」「プロジェクトの通期見込み」に使用します。',
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
                    'description' => '財務年度の着地見込みで、計画（Kintone損益）に対して利益が悪いプロジェクトをランキングします。予算差分も参考値として返します。着地見込みはget_total_financeの予測ONと同じく、Google Sheets実績がある月は実績を使い、該当月シートがない月はKintone損益を見込み値として使用します。「利益が危ない案件」「計画未達になりそうな案件」に使用します。',
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
            // ---- New tools ----
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_monthly_trend',
                    'description' => '財務年度の月次着地見込み対計画差分の推移を返します。「今期は改善しているの？」「着地見込みのトレンドを見せて」「月次で差分が大きくなったのはいつ？」に使用します。明示的に予算と聞かれた場合のみ予算対比にします。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year'  => ['type' => 'integer', 'description' => '財務年度の開始年'],
                            'project_ids'  => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '絞り込み対象プロジェクトID。省略時は全プロジェクト合計。'],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済基準月 YYYY-MM。省略時は自動判定。'],
                            'comparison_base' => [
                                'type' => 'string',
                                'enum' => ['yearly_plan', 'profit'],
                                'description' => '差分の比較対象。yearly_plan=予算、profit=計画（Kintone損益）。',
                            ],
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
                    'description' => '2つの財務年度を年度比較（前期FY vs 今期FY）します。「去年と比べてどう？」「YoY成長は？」に使用します。着地見込み・予算・計画・YoY差分を返します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'base_fiscal_year'    => ['type' => 'integer', 'description' => '比較元の財務年度（省略時は前期）'],
                            'compare_fiscal_year' => ['type' => 'integer', 'description' => '比較先の財務年度（省略時は今期）'],
                            'project_ids'         => ['type' => 'array', 'items' => ['type' => 'integer']],
                            'pm_name'             => ['type' => 'string', 'description' => 'PM名またはユーザーコード。指定時はそのPM担当プロジェクトだけを比較。'],
                            'pm_user_id'          => ['type' => 'integer', 'description' => 'PMのユーザーID。pm_nameより優先。'],
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
                    'description' => 'PM別に担当プロジェクトの予算・計画・実績累計・着地見込み・計画差分を集計してランキングします。「PM別の売上/利益ランキング」「PMごとの財務リスク」に使用します。予算差分も参考値として返します。',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fiscal_year'  => ['type' => 'integer', 'description' => '財務年度の開始年。'],
                            'as_of_period' => ['type' => 'string', 'description' => '実績反映済基準月 YYYY-MM。省略時は自動判定。'],
                            'limit'        => ['type' => 'integer', 'description' => '返すPM件数。'],
                            'sort_by'      => [
                                'type' => 'string',
                                'description' => 'profit_gap_worst（既定）, profit_gap_best, forecast_sales_desc, forecast_profit_desc, expense_desc, project_count_desc, risk_count_desc',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],

        ];
    }

    private static function toolNames(): array
    {
        return array_map(
            fn (array $tool) => (string) $tool['function']['name'],
            self::tools()
        );
    }

    // =========================================================================
    // Role-based tool filtering
    // =========================================================================

    /**
     * Finance tools are for directors/admins/managers.
     */
    private static function toolsForRole(string $role): array
    {
        if ($role === 'member') {
            return [];
        }

        $tools = self::tools();
        if ($role !== 'manager') {
            return $tools;
        }

        $managerHiddenTools = [
            'compare_fiscal_years',
            'get_revenue_concentration',
        ];

        return array_values(array_filter(
            $tools,
            fn (array $tool) => ! in_array((string) $tool['function']['name'], $managerHiddenTools, true)
        ));
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
            'director' => "あなたは取締役・上位役員向けの財務AIアシスタントです。全プロジェクトの予算、計画、実績、着地見込み、データ品質を確認できます。",
            'admin'    => "あなたは管理部門向けの財務AIアシスタントです。全プロジェクトの予算、計画、実績、着地見込み、データ品質を確認できます。",
            'manager'  => "あなたはマネージャー向けの財務AIアシスタントです。プロジェクト財務状況とデータ品質を確認できます。",
            default    => "あなたは社員向けのAIアシスタントです。このチャットでは財務データへのアクセス権がありません。",
        };

        $toolGuide = match ($role) {
            'director', 'admin' => "利用可能なデータ: 予算（yearly_plan）、計画（Kintone損益）、Google Sheets実績、着地見込み、財務データ品質、差異理由コメント、月次トレンド、健全度マトリクス、売上集中リスク、年度比較、PM別財務",
            'manager'           => "利用可能なデータ: 予算（yearly_plan）、計画（Kintone損益）、Google Sheets実績、着地見込み、財務データ品質、差異理由コメント、月次トレンド、健全度マトリクス、PM別財務",
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
- 「必要であれば取得します」「詳細をお求めなら」「その旨をお知らせください」のように、財務データ取得をユーザーに再依頼してはいけない。必要な財務データはこの場でツールから取得する
- 数値は具体的に引用し、視点を添えて説明する（例: 「利益が計画比-15%、2,300万円下回り。年間目標の23%分のラグ」）
- 財務以外（目標、勤怠、承認、雑談）の質問には、このチャットは財務専用だと短く伝える
- 財務の「今期」「年度」「着地」「経営状況」は、月次ではなく財務年度（3月-翌2月）の予算・計画・Google Sheets実績・着地見込みで回答する
- 用語定義: 予算=yearly_plan、計画=Kintone損益（profit）、実績=Google Sheets settlement、着地見込み=実績がある月は実績・該当月シートがない月はKintone損益で補完したforecast
- Google Sheets実績は毎月20日ごろ前月分が反映される。ユーザーが明示しない限り、この反映済み月を基準に回答する
- 着地見込みは get_total_finance の予測ONと同じく、Google Sheets実績がある月は実績を使い、該当月シートがない月はKintone損益を見込み値として使う。予算は比較対象であり、見込み値としては使わない
- Google Sheets実績がない月をKintone損益で補完した場合は、実績ではなく見込みとして扱う。完了済みプロジェクトの完了後月は予測補完しない
- 年度財務サマリーでは、予算=yearly_plan_totals、計画=profit_plan_totals、最新実績（単月）=latest_actual_month_totals、実績累計=actual_to_date_totals、着地見込み=forecast_totals、予算差分=forecast_vs_yearly_plan、計画差分=forecast_vs_profit_plan を使う
- 年度財務サマリーを回答する場合、売上・販管費・利益の予算、計画、実績累計、着地見込み、予算差分、計画差分を具体的な数値で示す
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
- PM別・PM個人の質問（「井上PMの案件」「井上さんのFY2025とFY2026」「PM別ランキング」など）は get_pm_finance_summary / compare_fiscal_years / get_pm_finance_ranking を使う。PM担当は project_members.authority=1 の関係を正とする
- 「良いPM」「優秀なPM」「ベストPM」「利益差分が良い順」は get_pm_finance_ranking の sort_by=profit_gap_best を使う。「悪いPM」「リスクが大きいPM」は sort_by=profit_gap_worst を使う
- ツール結果に alert_count > 0 または🔴の案件がある場合、回答内に「❗ 要注意：[N]件のアラート案件」として先頭で明示する
- get_finance_forecast_ranking を使った場合、projects の上位案件について project_name、forecast_totals/analysis_result または totals.forecast、variance_vs_plan（計画差分）の利益差分を必ず含める。予算差分は variance_vs_yearly_plan を参考として扱う。案件名や数値を省略して「詳細を確認してください」と言わない
- データの信頼性や欠損が気になる場合は get_finance_data_quality を使う
- ツールで取得できないことは「データがありません」と正直に伝える
- このチャットからコメント投稿やデータ更新は行わない。ユーザーに通常のコメント画面を使うよう案内する
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

        $rawMessages = $request->input('messages');
        if (! is_array($rawMessages) || $rawMessages === []) {
            return response()->json(['message' => 'メッセージを入力してください。'], 422);
        }

        $validated = validator(['messages' => array_slice($rawMessages, -20)], [
            'messages'           => 'required|array|min:1',
            'messages.*.role'    => 'required|string|in:user,assistant',
            'messages.*.content' => 'nullable|string|max:2000',
        ])->validate();
        $filteredMessages = collect($validated['messages'])
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => trim((string) ($message['content'] ?? '')),
            ])
            ->filter(fn (array $message) => $message['content'] !== '')
            ->values()
            ->all();
        if ($filteredMessages === []) {
            return response()->json(['message' => 'メッセージを入力してください。'], 422);
        }
        if (($filteredMessages[array_key_last($filteredMessages)]['role'] ?? null) !== 'user') {
            return response()->json(['message' => 'ユーザーの最新メッセージを入力してください。'], 422);
        }

        $user  = $this->active_user();
        $role  = self::resolveRole($user);
        $now   = Carbon::now();
        $financeFiscalYear = $now->month >= 3 ? (int) $now->year : (int) $now->year - 1;
        $latestActualPeriod = $now->copy()
            ->startOfMonth()
            ->subMonthsNoOverflow($now->day >= 20 ? 1 : 2)
            ->format('Y-m');

        $systemPrompt = self::systemPrompt($user, $role, $financeFiscalYear, $latestActualPeriod);

        $history  = array_slice($filteredMessages, -10);
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );
        $latestUserContent = $this->latestUserContent($history);
        $forceVarianceExplanationTool = $this->isProjectVarianceReasonQuestion($latestUserContent);
        $forcedFirstTool = $this->forcedFirstFinanceTool($latestUserContent, $history);

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

        $forcedReply = $role !== 'member'
            ? $this->directForcedFinanceToolReply(
                $forcedFirstTool,
                $tools,
                $latestUserContent,
                $financeFiscalYear,
                $latestActualPeriod,
                $financeMcp
            )
            : null;
        if ($role !== 'member' && $forcedReply !== null) {
            return response()->json(['reply' => $forcedReply]);
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
                $payload['tool_choice'] = ($i === 0 && $forcedFirstTool)
                    ? ['type' => 'function', 'function' => ['name' => $forcedFirstTool]]
                    : 'auto';
            }

            try {
                $response = $client->chat()->create($payload);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'OpenAI応答の取得に失敗しました。もう一度お試しください。',
                ], 502);
            }

            $choice = $response->choices[0];

            if ($choice->finishReason === 'stop') {
                $content = trim((string) ($choice->message->content ?? ''));
                if ($this->isUnacceptableFinanceStopResponse($content)) {
                    if ($role === 'member') {
                        return response()->json([
                            'reply' => 'このチャットでは財務データへのアクセス権がありません。',
                        ]);
                    }

                    $fallbackReply = $this->directForcedFinanceToolReply(
                        $forcedFirstTool,
                        $tools,
                        $latestUserContent,
                        $financeFiscalYear,
                        $latestActualPeriod,
                        $financeMcp
                    );
                    if ($role !== 'member' && $fallbackReply !== null) {
                        return response()->json(['reply' => $fallbackReply]);
                    }

                    if ($i >= 4) {
                        return response()->json([
                            'reply' => '財務データを使った回答を生成できませんでした。質問内容を少し具体化してもう一度お試しください。',
                        ]);
                    }

                    $messages[] = [
                        'role' => 'user',
                        'content' => '内部指示: 待機文、XXX、○億○千万円、±X%などの仮置きテンプレートで終了しないでください。必要な財務ツールを今すぐ呼び出すか、直前のツール結果から案件名・金額・差分を具体的に引用して最終回答してください。',
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
                    $args = json_decode($toolCall->function->arguments, true);
                    if (! is_array($args) || json_last_error() !== JSON_ERROR_NONE) {
                        $messages[] = [
                            'role'         => 'tool',
                            'tool_call_id' => $toolCall->id,
                            'content'      => json_encode(['error' => 'Invalid tool arguments JSON.']),
                        ];
                        continue;
                    }

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
            '/少々お待ち|お待ちください|確認します|確認いたします|調べます|確認して.*回答|確認して.*お伝え|今から.*取得|最新データを取得|正確な数値を取得/u',
            $plain
        ) === 1;

        return $isShort && $looksLikeWaiting;
    }

    private function isUnacceptableFinanceStopResponse(string $content): bool
    {
        if ($content === '') {
            return true;
        }

        if ($this->isWaitingOnlyResponse($content)) {
            return true;
        }

        $plain = trim(strip_tags($content));
        $hasPlaceholder = preg_match(
            '/XXX|ＸＸＸ|○|〇|◯|△|▲|×|✕|✖|±X|±Ｘ|X%|Ｘ%|X億|Ｘ億|X千万円|Ｘ千万円|x{2,}/iu',
            $plain
        ) === 1;
        $hasFinanceTemplate = preg_match('/年間計画|実績累計|着地見込み|計画.*乖離|計画との差分/u', $plain) === 1;
        if ($hasPlaceholder && $hasFinanceTemplate) {
            return true;
        }

        if (preg_match('/少々お待ち|お待ちください|今から.*取得|最新データを取得|正確な数値を取得/u', $plain) === 1) {
            return true;
        }

        $looksLikeDeferral = preg_match(
            '/必要であれば|必要でしたら|詳細をお求め|その旨をお知らせ|取得しますので|具体的な.*情報を取得|アクセス制限|財務データが未取得|データが未取得|詳細表示できません|管理部門にご確認|リアルタイムの財務システム|データアクセス範囲に.*ない|具体的な金額.*示すことができません/u',
            $plain
        ) === 1;

        return $looksLikeDeferral;
    }

    private function directForcedFinanceToolReply(
        ?string $tool,
        array $tools,
        string $latestUserContent,
        int $financeFiscalYear,
        string $latestActualPeriod,
        FinanceToolController $financeMcp
    ): ?string {
        if ($tool === null || ! in_array($tool, $this->toolNamesFromDefinitions($tools), true)) {
            return null;
        }

        return match ($tool) {
            'get_fiscal_year_finance_summary' => $this->formatFiscalSummaryReply(
                $this->dispatchTool($tool, $this->fiscalSummaryArgsFromQuestion($latestUserContent, $financeFiscalYear, $latestActualPeriod), $financeMcp)
            ),
            'get_monthly_trend' => $this->formatMonthlyTrendReply(
                $this->dispatchTool($tool, $this->monthlyTrendArgsFromQuestion($latestUserContent, $financeFiscalYear, $latestActualPeriod), $financeMcp)
            ),
            'get_finance_forecast_ranking' => $this->formatForecastRankingReply(
                $this->dispatchTool($tool, $this->fiscalSummaryArgsFromQuestion($latestUserContent, $financeFiscalYear, $latestActualPeriod), $financeMcp)
            ),
            'get_project_health_matrix' => $this->formatHealthMatrixReply(
                $this->dispatchTool($tool, $this->fiscalSummaryArgsFromQuestion($latestUserContent, $financeFiscalYear, $latestActualPeriod), $financeMcp)
            ),
            'get_pm_finance_summary' => $this->formatPmSummaryReply(
                $this->dispatchTool($tool, $this->pmSummaryArgsFromQuestion($latestUserContent, $financeFiscalYear, $latestActualPeriod), $financeMcp)
            ),
            'get_pm_finance_ranking' => $this->formatPmRankingReply(
                $this->dispatchTool($tool, $this->pmRankingArgsFromQuestion($latestUserContent, $financeFiscalYear, $latestActualPeriod), $financeMcp)
            ),
            'compare_fiscal_years' => $this->formatFiscalComparisonReply(
                $this->dispatchTool($tool, $this->fiscalComparisonArgsFromQuestion($latestUserContent, $financeFiscalYear), $financeMcp)
            ),
            default => null,
        };
    }

    private function toolNamesFromDefinitions(array $tools): array
    {
        return array_values(array_filter(array_map(
            fn (array $tool) => (string) ($tool['function']['name'] ?? ''),
            $tools
        )));
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

    private function previousUserContent(array $history): string
    {
        $seenLatest = false;
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? null) !== 'user') {
                continue;
            }

            if (! $seenLatest) {
                $seenLatest = true;
                continue;
            }

            return trim((string) ($history[$i]['content'] ?? ''));
        }

        return '';
    }

    private function forcedFirstFinanceTool(string $latestUserContent, array $history): ?string
    {
        if ($this->isMoreInfoRequest($latestUserContent)) {
            return $this->forcedFirstFinanceTool($this->previousUserContent($history), []);
        }

        if ($this->isHealthMatrixQuestion($latestUserContent)) {
            return 'get_project_health_matrix';
        }

        if ($this->isPmFiscalComparisonQuestion($latestUserContent)) {
            return 'compare_fiscal_years';
        }

        if ($this->isPmFinanceQuestion($latestUserContent)) {
            return $this->isPmRankingQuestion($latestUserContent)
                ? 'get_pm_finance_ranking'
                : 'get_pm_finance_summary';
        }

        if ($this->isForecastRiskQuestion($latestUserContent)) {
            return 'get_finance_forecast_ranking';
        }

        if ($this->isVarianceSummaryQuestion($latestUserContent)) {
            return 'get_variance_summary';
        }

        if ($this->isDataQualityQuestion($latestUserContent)) {
            return 'get_finance_data_quality';
        }

        if ($this->isMonthlyTrendQuestion($latestUserContent)) {
            return 'get_monthly_trend';
        }

        if ($this->isRevenueConcentrationQuestion($latestUserContent)) {
            return 'get_revenue_concentration';
        }

        if ($this->isFiscalComparisonQuestion($latestUserContent)) {
            return 'compare_fiscal_years';
        }

        if ($this->isFiscalSummaryQuestion($latestUserContent)) {
            return 'get_fiscal_year_finance_summary';
        }

        if ($this->isProjectVarianceReasonQuestion($latestUserContent)) {
            return 'get_project_variance_explanation';
        }

        return null;
    }

    private function isMoreInfoRequest(string $content): bool
    {
        return preg_match('/もっと|詳細|詳しく|具体|案件名|数値|情報が必要|続きを/u', $content) === 1
            && mb_strlen(trim($content)) <= 80;
    }

    private function isFiscalSummaryQuestion(string $content): bool
    {
        $hasFiscalScope = preg_match('/今期|今年度|FY\d{4}|財務年度|着地/u', $content) === 1;
        $asksFinance = preg_match('/財務|財務状況|サマリー|概況|状況|着地|見込み|売上|利益|販管費|経営状況|どう/u', $content) === 1;

        return $hasFiscalScope && $asksFinance;
    }

    private function isVarianceSummaryQuestion(string $content): bool
    {
        $hasActualVsProfit = preg_match('/実績/u', $content) === 1
            && preg_match('/損益|Kintone/u', $content) === 1
            && preg_match('/乖離|差異|ズレ|違い/u', $content) === 1;
        $asksRanking = preg_match('/一番|最大|大きい|ランキング|一覧|どれ|どこ|プロジェクト|案件/u', $content) === 1;

        return $hasActualVsProfit && $asksRanking;
    }

    private function isDataQualityQuestion(string $content): bool
    {
        return preg_match('/データ品質|欠損|重複|実績反映月|未反映|反映済/u', $content) === 1;
    }

    private function isMonthlyTrendQuestion(string $content): bool
    {
        return preg_match('/トレンド|推移|月ごと|月次|変化|改善|悪化|ギャップ/u', $content) === 1
            && preg_match('/着地|見込み|計画|差分|乖離/u', $content) === 1;
    }

    private function isHealthMatrixQuestion(string $content): bool
    {
        return preg_match('/健全度|赤信号|🔴|🟡|🟢|全プロジェクトの状態|全案件の状態/u', $content) === 1;
    }

    private function isPmFinanceQuestion(string $content): bool
    {
        if (preg_match('/PM別|PMごと|担当PM|(^|[^一-龠ぁ-んァ-ンA-Za-z0-9])PMの/u', $content) === 1) {
            return true;
        }

        return $this->extractPmNameFromQuestion($content) !== null
            && preg_match('/案件|プロジェクト|担当|FY\d{4}|今期|前期|実績|着地|売上|利益|財務|計画|予算|projects?|actual|jisseki|forecast|sales|profit|finance/iu', $content) === 1;
    }

    private function isPmRankingQuestion(string $content): bool
    {
        return preg_match('/ランキング|順|一覧|PM別|PMごと/u', $content) === 1;
    }

    private function isPmFiscalComparisonQuestion(string $content): bool
    {
        return $this->isFiscalComparisonQuestion($content)
            && $this->extractPmNameFromQuestion($content) !== null;
    }

    private function isRevenueConcentrationQuestion(string $content): bool
    {
        return preg_match('/集中リスク|売上.*集中|トップ\d+|トップ[一二三四五六七八九十]+|シェア/u', $content) === 1;
    }

    private function isFiscalComparisonQuestion(string $content): bool
    {
        if (preg_match_all('/FY\d{4}/iu', $content, $yearMentions) >= 2) {
            return true;
        }

        return preg_match('/YoY|前年比|前期|去年|昨年|年度比較|比較/u', $content) === 1
            && preg_match('/FY\d{4}|今期|前期|売上|利益|着地/u', $content) === 1;
    }

    private function isForecastRiskQuestion(string $content): bool
    {
        $hasRisk = preg_match('/悪く|悪化|下回|未達|危な|リスク|赤信号|アラート/u', $content) === 1;
        $hasProfitOrPlan = preg_match('/利益|計画|年間計画|予算|着地/u', $content) === 1;
        $asksProjects = preg_match('/プロジェクト|案件|どれ|どこ|一覧|ランキング/u', $content) === 1;

        return $hasRisk && $hasProfitOrPlan && $asksProjects;
    }

    private function fiscalSummaryArgsFromQuestion(string $content, int $financeFiscalYear, string $latestActualPeriod): array
    {
        $fiscalYear = preg_match('/FY(\d{4})/iu', $content, $match) === 1
            ? (int) $match[1]
            : $financeFiscalYear;

        return [
            'fiscal_year' => $fiscalYear,
            'as_of_period' => $latestActualPeriod,
            'limit' => 10,
        ];
    }

    private function fiscalComparisonArgsFromQuestion(string $content, int $financeFiscalYear): array
    {
        preg_match_all('/FY(\d{4})/iu', $content, $matches);
        $years = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        $pmName = $this->extractPmNameFromQuestion($content);

        if (count($years) >= 2) {
            sort($years);

            return array_filter([
                'base_fiscal_year' => $years[0],
                'compare_fiscal_year' => $years[count($years) - 1],
                'pm_name' => $pmName,
            ], fn ($value) => $value !== null && $value !== '');
        }

        if (count($years) === 1) {
            $year = $years[0];

            return array_filter([
                'base_fiscal_year' => preg_match('/前期|前年|去年|昨年/u', $content) === 1 ? $year : $year - 1,
                'compare_fiscal_year' => preg_match('/前期|前年|去年|昨年/u', $content) === 1 ? $financeFiscalYear : $year,
                'pm_name' => $pmName,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return array_filter([
            'base_fiscal_year' => $financeFiscalYear - 1,
            'compare_fiscal_year' => $financeFiscalYear,
            'pm_name' => $pmName,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function pmSummaryArgsFromQuestion(string $content, int $financeFiscalYear, string $latestActualPeriod): array
    {
        $args = $this->fiscalSummaryArgsFromQuestion($content, $financeFiscalYear, $latestActualPeriod);
        $pmName = $this->extractPmNameFromQuestion($content);
        if ($pmName !== null) {
            $args['pm_name'] = $pmName;
        }

        return $args;
    }

    private function pmRankingArgsFromQuestion(string $content, int $financeFiscalYear, string $latestActualPeriod): array
    {
        $args = $this->fiscalSummaryArgsFromQuestion($content, $financeFiscalYear, $latestActualPeriod);
        $args['sort_by'] = $this->pmRankingSortFromQuestion($content);

        return $args;
    }

    private function pmRankingSortFromQuestion(string $content): string
    {
        if (preg_match('/良い|よい|優秀|ベスト|上位|トップ|好調|黒字|改善|利益差分.*(大きい|高い|良い)|プラス/u', $content) === 1) {
            return 'profit_gap_best';
        }

        if (preg_match('/売上/u', $content) === 1 && preg_match('/順|ランキング|トップ|上位/u', $content) === 1) {
            return 'forecast_sales_desc';
        }

        if (preg_match('/着地利益|利益額/u', $content) === 1 && preg_match('/順|ランキング|トップ|上位/u', $content) === 1) {
            return 'forecast_profit_desc';
        }

        if (preg_match('/件数|担当数|案件数/u', $content) === 1) {
            return 'project_count_desc';
        }

        return 'profit_gap_worst';
    }

    private function extractPmNameFromQuestion(string $content): ?string
    {
        $patterns = [
            '/([^\s、。,.，．]{1,40})(?:さん|様|氏|PM|ＰＭ)/u',
            '/([A-Za-z][A-Za-z0-9._-]{0,39})-san\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $match) !== 1) {
                continue;
            }

            $name = trim((string) ($match[1] ?? ''));
            if ($name === '' || preg_match('/^(PM|ＰＭ|FY\d{4})$/iu', $name) === 1) {
                continue;
            }

            return $name;
        }

        return null;
    }

    private function monthlyTrendArgsFromQuestion(string $content, int $financeFiscalYear, string $latestActualPeriod): array
    {
        $args = $this->fiscalSummaryArgsFromQuestion($content, $financeFiscalYear, $latestActualPeriod);
        $asksBudget = preg_match('/予算|年間計画/u', $content) === 1;
        $args['comparison_base'] = $asksBudget ? 'yearly_plan' : 'profit';

        return $args;
    }

    private function isProjectVarianceReasonQuestion(string $content): bool
    {
        $plain = trim($content);
        if ($plain === '') {
            return false;
        }

        if (
            $this->isVarianceSummaryQuestion($plain)
            || $this->isDataQualityQuestion($plain)
            || $this->isMonthlyTrendQuestion($plain)
            || $this->isHealthMatrixQuestion($plain)
            || $this->isPmFinanceQuestion($plain)
            || $this->isRevenueConcentrationQuestion($plain)
            || $this->isFiscalComparisonQuestion($plain)
        ) {
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

    private function formatFiscalSummaryReply(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $fiscalYear = (int) ($result['fiscal_year'] ?? 0);
        $latestActualPeriod = (string) ($result['latest_actual_period'] ?? '');
        $budget = $result['yearly_plan_totals'] ?? [];
        $plan = $result['profit_plan_totals'] ?? ($result['totals']['profit'] ?? []);
        $actual = $result['actual_to_date_totals'] ?? [];
        $forecast = $result['forecast_totals'] ?? [];
        $budgetGap = $result['forecast_vs_yearly_plan'] ?? [];
        $planGap = $result['forecast_vs_profit_plan'] ?? [];

        $lines = [
            "FY{$fiscalYear}の財務状況（最新実績反映月: {$latestActualPeriod}）",
            '',
            '【予算（年間計画）】',
            $this->formatFinanceUnitLine($budget),
            '',
            '【計画（月次修正 / Kintone損益）】',
            $this->formatFinanceUnitLine($plan),
            '',
            "【実績累計（{$latestActualPeriod}まで / Google Sheets）】",
            $this->formatFinanceUnitLine($actual),
            '',
            '【実績/着地見込み（予測込み）】',
            $this->formatFinanceUnitLine($forecast),
            '',
            '【差分（実績/着地見込み - 計画）】',
            sprintf(
                '売上 %s、販管費 %s、利益 %s',
                $this->formatSignedYen((int) ($planGap['sales_amount'] ?? 0)),
                $this->formatSignedYen((int) ($planGap['expense_amount'] ?? 0)),
                $this->formatSignedYen((int) ($planGap['profit_amount'] ?? 0))
            ),
            '',
            '【参考: 差分（実績/着地見込み - 予算）】',
            sprintf(
                '売上 %s、販管費 %s、利益 %s',
                $this->formatSignedYen((int) ($budgetGap['sales_amount'] ?? 0)),
                $this->formatSignedYen((int) ($budgetGap['expense_amount'] ?? 0)),
                $this->formatSignedYen((int) ($budgetGap['profit_amount'] ?? 0))
            ),
        ];

        $profitGap = (int) ($budgetGap['profit_amount'] ?? 0);
        $planProfitGap = (int) ($planGap['profit_amount'] ?? 0);
        $lines[] = '';
        $lines[] = '利益は計画に対して' . $this->formatSignedYen($planProfitGap)
            . '、予算に対して' . $this->formatSignedYen($profitGap)
            . 'の見込みです。';

        $alertCount = (int) ($result['alert_count'] ?? 0);
        if ($alertCount > 0) {
            array_splice($lines, 1, 0, ["要注意: {$alertCount}件のアラート案件があります。"]);
        }

        return implode("\n", $lines);
    }

    private function formatForecastRankingReply(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $fiscalYear = (int) ($result['fiscal_year'] ?? 0);
        $latestActualPeriod = (string) ($result['latest_actual_period'] ?? '');
        $projects = array_values(array_filter(
            $result['projects'] ?? [],
            fn ($project) => is_array($project) && (int) ($project['variance_vs_plan']['profit_amount'] ?? 0) < 0
        ));
        $priorityProjects = array_values(array_filter($projects, fn (array $project) => ! $this->isInternalCostCenterProject($project)));
        $internalProjects = array_values(array_filter($projects, fn (array $project) => $this->isInternalCostCenterProject($project)));

        if ($projects === []) {
            return "FY{$fiscalYear}では、利益着地見込みが計画を下回るプロジェクトはありません。（最新実績反映月: {$latestActualPeriod}）";
        }

        $formatProjectLine = function (string $prefix, array $project): string {
            $forecast = $project['totals']['forecast'] ?? [];
            $plan = $project['totals']['profit'] ?? [];
            $variance = $project['variance_vs_plan'] ?? [];
            $gap = (int) ($variance['profit_amount'] ?? 0);
            $gapPct = $variance['profit_pct'] ?? null;
            $pctLabel = is_numeric($gapPct) ? '、計画比 ' . $this->formatSignedPercent((float) $gapPct) : '';

            return sprintf(
                '%s %s: 着地利益 %s、計画利益 %s、差分 %s%s',
                $prefix,
                (string) ($project['project_name'] ?? '名称未設定'),
                $this->formatYen((int) ($forecast['profit'] ?? 0)),
                $this->formatYen((int) ($plan['profit'] ?? 0)),
                $this->formatSignedYen($gap),
                $pctLabel
            );
        };

        $lines = [
            "FY{$fiscalYear} 利益着地見込みが計画を下回るプロジェクト（最新実績反映月: {$latestActualPeriod}）",
            $priorityProjects !== [] ? '優先確認（通常案件）' : '参考（社内部門のみ）',
            '',
        ];

        foreach (array_slice($priorityProjects !== [] ? $priorityProjects : $projects, 0, 10) as $index => $project) {
            $lines[] = $formatProjectLine(($index + 1) . '.', $project);
        }

        if ($priorityProjects !== [] && $internalProjects !== []) {
            $lines[] = '';
            $lines[] = '参考（社内部門: 優先順位外）';
            foreach (array_slice($internalProjects, 0, 3) as $project) {
                $lines[] = $formatProjectLine('-', $project);
            }
        }

        $totalGap = $result['forecast_vs_profit_plan']['profit_amount'] ?? ($result['variance_vs_plan']['profit_amount'] ?? null);
        if (is_numeric($totalGap)) {
            $lines[] = '';
            $lines[] = '全体（社内部門を含む）の計画差分は ' . $this->formatSignedYen((int) $totalGap) . ' です。';
        }

        $budgetGap = $result['forecast_vs_yearly_plan']['profit_amount'] ?? null;
        if (is_numeric($budgetGap)) {
            $lines[] = '参考: 予算差分は ' . $this->formatSignedYen((int) $budgetGap) . ' です。';
        }

        return implode("\n", $lines);
    }

    private function formatHealthMatrixReply(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $fiscalYear = (int) ($result['fiscal_year'] ?? 0);
        $latestActualPeriod = (string) ($result['latest_actual_period'] ?? '');
        $summary = $result['summary'] ?? [];
        $projects = array_values(array_filter($result['projects'] ?? [], fn ($project) => is_array($project)));
        $priorityProjects = array_values(array_filter($projects, fn (array $project) => ! $this->isInternalCostCenterProject($project)));
        $internalProjects = array_values(array_filter($projects, fn (array $project) => $this->isInternalCostCenterProject($project)));

        $priorityLabel = match (true) {
            $priorityProjects !== [] && $internalProjects !== [] => '優先確認（通常案件）。間接費部門・積立部門は参考として後段に表示します。',
            $priorityProjects !== [] => '優先確認（通常案件）',
            default => '参考（社内部門のみ）',
        };

        $lines = [
            "FY{$fiscalYear} プロジェクト健全度（最新実績反映月: {$latestActualPeriod}）",
            sprintf(
                '🔴 %d件、🟡 %d件、🟢 %d件、合計 %d件',
                (int) ($summary['red_count'] ?? 0),
                (int) ($summary['yellow_count'] ?? 0),
                (int) ($summary['green_count'] ?? 0),
                (int) ($summary['total'] ?? count($projects))
            ),
            $priorityLabel,
            '',
        ];

        foreach (array_slice($priorityProjects !== [] ? $priorityProjects : $projects, 0, 10) as $project) {
            $gapPct = $project['gap_pct'] ?? null;
            $pctLabel = is_numeric($gapPct) ? '、計画比 ' . $this->formatSignedPercent((float) $gapPct) : '';
            $pmName = $this->formatPmName($project['pm'] ?? null);

            $lines[] = sprintf(
                '%s %s（PM: %s）: 利益差分 %s%s、着地利益 %s、計画利益 %s、信頼度 %s',
                (string) ($project['label'] ?? ''),
                (string) ($project['project_name'] ?? '名称未設定'),
                $pmName,
                $this->formatSignedYen((int) ($project['gap_amount'] ?? 0)),
                $pctLabel,
                $this->formatYen((int) ($project['forecast_profit'] ?? 0)),
                $this->formatYen((int) ($project['plan_profit'] ?? 0)),
                (string) ($project['forecast_confidence'] ?? 'unknown')
            );
        }

        if ($priorityProjects !== [] && $internalProjects !== []) {
            $lines[] = '';
            $lines[] = '参考（社内部門）';
            foreach (array_slice($internalProjects, 0, 3) as $project) {
                $gapPct = $project['gap_pct'] ?? null;
                $pctLabel = is_numeric($gapPct) ? '、計画比 ' . $this->formatSignedPercent((float) $gapPct) : '';
                $pmName = $this->formatPmName($project['pm'] ?? null);

                $lines[] = sprintf(
                    '%s %s（PM: %s）: 利益差分 %s%s、着地利益 %s、計画利益 %s、信頼度 %s',
                    (string) ($project['label'] ?? ''),
                    (string) ($project['project_name'] ?? '名称未設定'),
                    $pmName,
                    $this->formatSignedYen((int) ($project['gap_amount'] ?? 0)),
                    $pctLabel,
                    $this->formatYen((int) ($project['forecast_profit'] ?? 0)),
                    $this->formatYen((int) ($project['plan_profit'] ?? 0)),
                    (string) ($project['forecast_confidence'] ?? 'unknown')
                );
            }
        }

        return implode("\n", $lines);
    }

    private function isInternalCostCenterProject(array $project): bool
    {
        if (array_key_exists('is_internal_cost_center', $project)) {
            return (bool) $project['is_internal_cost_center'];
        }

        return FinanceSnapshotService::isInternalCostCenter($project['project_name'] ?? null);
    }

    private function formatPmSummaryReply(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $pmName = $this->formatPmName($result['pm'] ?? null);
        $fiscalYear = (int) ($result['fiscal_year'] ?? 0);
        $latestActualPeriod = (string) ($result['latest_actual_period'] ?? '');
        $projectCount = (int) ($result['project_count'] ?? count($result['project_ids'] ?? []));

        if ($projectCount === 0) {
            return "{$pmName}の担当プロジェクトは見つかりませんでした。";
        }

        $budget = $result['yearly_plan_totals'] ?? [];
        $plan = $result['profit_plan_totals'] ?? ($result['totals']['profit'] ?? []);
        $actual = $result['actual_to_date_totals'] ?? [];
        $forecast = $result['forecast_totals'] ?? [];
        $budgetGap = $result['forecast_vs_yearly_plan'] ?? [];
        $planGap = $result['forecast_vs_profit_plan'] ?? [];

        $lines = [
            "{$pmName} 担当プロジェクト FY{$fiscalYear} 財務サマリー（最新実績反映月: {$latestActualPeriod}）",
            "対象プロジェクト: {$projectCount}件",
            '',
            '【予算】' . $this->formatFinanceUnitLine($budget),
            '【計画】' . $this->formatFinanceUnitLine($plan),
            "【実績累計】" . $this->formatFinanceUnitLine($actual),
            '【実績/着地見込み】' . $this->formatFinanceUnitLine($forecast),
            '',
            '計画差分: 売上 ' . $this->formatSignedYen((int) ($planGap['sales_amount'] ?? 0))
                . '、利益 ' . $this->formatSignedYen((int) ($planGap['profit_amount'] ?? 0)),
            '参考（予算差分）: 売上 ' . $this->formatSignedYen((int) ($budgetGap['sales_amount'] ?? 0))
                . '、利益 ' . $this->formatSignedYen((int) ($budgetGap['profit_amount'] ?? 0)),
        ];

        $projects = array_values(array_filter($result['projects'] ?? [], fn ($project) => is_array($project)));
        if ($projects !== []) {
            $lines[] = '';
            $lines[] = '主な担当案件: ' . implode('、', array_slice(array_map(
                fn (array $project) => (string) ($project['project_name'] ?? '名称未設定'),
                $projects
            ), 0, 8));
        }

        return implode("\n", $lines);
    }

    private function formatPmRankingReply(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $fiscalYear = (int) ($result['fiscal_year'] ?? 0);
        $latestActualPeriod = (string) ($result['latest_actual_period'] ?? '');
        $pms = array_values(array_filter($result['pms'] ?? [], fn ($pm) => is_array($pm)));

        if ($pms === []) {
            return "FY{$fiscalYear}のPM別財務ランキング対象はありません。（最新実績反映月: {$latestActualPeriod}）";
        }

        $sortBy = (string) ($result['sort_by'] ?? 'profit_gap_worst');
        $titleSuffix = match ($sortBy) {
            'profit_gap_best' => '計画差分が良い順',
            'forecast_sales_desc' => '着地売上が大きい順',
            'forecast_profit_desc' => '着地利益が大きい順',
            'project_count_desc' => '担当件数が多い順',
            'risk_count_desc' => 'リスク案件数が多い順',
            default => '計画未達リスクが大きい順',
        };

        $lines = [
            "FY{$fiscalYear} PM別 着地見込みと計画差分ランキング（{$titleSuffix} / 最新実績反映月: {$latestActualPeriod}）",
            '',
        ];

        foreach (array_slice($pms, 0, 10) as $index => $row) {
            $variance = $row['variance_vs_plan'] ?? [];
            $budgetVariance = $row['variance_vs_yearly_plan'] ?? [];
            $totals = $row['totals'] ?? [];
            $forecast = $totals['forecast'] ?? [];
            $plan = $totals['profit'] ?? [];
            $budgetReference = isset($budgetVariance['profit_amount'])
                ? '、予算差分 ' . $this->formatSignedYen((int) ($budgetVariance['profit_amount'] ?? 0))
                : '';

            $lines[] = sprintf(
                '%d. %s: 担当%d件、着地利益 %s、計画利益 %s、計画差分 %s%s',
                $index + 1,
                $this->formatPmName($row['pm'] ?? null),
                (int) ($row['project_count'] ?? 0),
                $this->formatYen((int) ($forecast['profit'] ?? 0)),
                $this->formatYen((int) ($plan['profit'] ?? 0)),
                $this->formatSignedYen((int) ($variance['profit_amount'] ?? 0)),
                $budgetReference
            );
        }

        return implode("\n", $lines);
    }

    private function formatMonthlyTrendReply(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $fiscalYear = (int) ($result['fiscal_year'] ?? 0);
        $latestActualPeriod = (string) ($result['latest_actual_period'] ?? '');
        $comparisonBaseLabel = (string) ($result['comparison_base_label'] ?? '予算');
        $trend = array_values(array_filter($result['trend'] ?? [], fn ($row) => is_array($row)));
        $actualRows = array_values(array_filter($trend, fn (array $row) => ! empty($row['is_actual'])));
        $forecastRows = array_values(array_filter($trend, fn (array $row) => empty($row['is_actual'])));
        $lastActual = $actualRows !== [] ? $actualRows[count($actualRows) - 1] : null;
        $finalRow = $trend !== [] ? $trend[count($trend) - 1] : null;
        $direction = (string) ($result['overall_direction'] ?? '');
        $directionLabel = match ($direction) {
            'improving' => '改善傾向',
            'deteriorating' => '悪化傾向',
            'flat' => '横ばい',
            default => '判定不可',
        };
        $lastActualGap = is_array($lastActual)
            ? (int) ($lastActual['cumulative']['gap'] ?? 0)
            : 0;
        $finalGap = is_array($finalRow)
            ? (int) ($finalRow['cumulative']['gap'] ?? 0)
            : $lastActualGap;

        $lines = [
            "FY{$fiscalYear}の着地見込みと{$comparisonBaseLabel}のギャップは、現時点では{$directionLabel}です。",
            "最新実績反映月は{$latestActualPeriod}で、実績反映済み累計の利益差分は" . $this->formatSignedYen($lastActualGap) . 'です。',
            '通期の見込みでは累計差分は' . $this->formatSignedYen($finalGap) . 'です。',
            '',
            '実績反映済み月の動き:',
        ];

        foreach ($actualRows as $row) {
            $period = (string) ($row['period'] ?? '');
            $monthly = is_array($row['monthly'] ?? null) ? $row['monthly'] : [];
            $cumulative = is_array($row['cumulative'] ?? null) ? $row['cumulative'] : [];
            $change = $cumulative['gap_change'] ?? null;
            $changeLabel = is_numeric($change)
                ? '（前月から' . $this->formatSignedYen((int) $change) . '）'
                : '';

            $lines[] = sprintf(
                '- %s: 単月 %s、累計 %s%s',
                $period,
                $this->formatSignedYen((int) ($monthly['monthly_gap'] ?? 0)),
                $this->formatSignedYen((int) ($cumulative['gap'] ?? 0)),
                $changeLabel
            );
        }

        if ($forecastRows !== []) {
            $nonZeroForecastRows = array_values(array_filter(
                $forecastRows,
                fn (array $row) => (int) ($row['monthly']['monthly_gap'] ?? 0) !== 0
            ));
            $lines[] = '';

            if ($nonZeroForecastRows === []) {
                $lines[] = "未実績月はKintone損益見込みを{$comparisonBaseLabel}として使っているため、単月差分は基本的に±0円です。";
            } else {
                $lines[] = '未実績月で差分がある月:';
                foreach (array_slice($nonZeroForecastRows, 0, 4) as $row) {
                    $lines[] = sprintf(
                        '- %s: 単月 %s、累計 %s',
                        (string) ($row['period'] ?? ''),
                        $this->formatSignedYen((int) ($row['monthly']['monthly_gap'] ?? 0)),
                        $this->formatSignedYen((int) ($row['cumulative']['gap'] ?? 0))
                    );
                }
            }
        }

        return implode("\n", $lines);
    }

    private function formatMonthlyTrendSourceLabel(array $row): string
    {
        $sourceCounts = is_array($row['forecast_sources'] ?? null) ? $row['forecast_sources'] : [];
        if ($sourceCounts !== []) {
            $hasActual = ! empty($sourceCounts['settlement']);
            $hasForecast = ! empty($sourceCounts['profit_forecast']);

            if ($hasActual && $hasForecast) {
                return '実績・見込み混在';
            }

            if ($hasActual) {
                return 'Google Sheets実績';
            }

            if ($hasForecast) {
                return 'Kintone損益見込み';
            }

            if (! empty($sourceCounts['project_completed'])) {
                return '完了後月';
            }

            return 'データなし';
        }

        $forecastSource = (string) ($row['forecast_source'] ?? '');

        return match ($forecastSource) {
            'settlement' => 'Google Sheets実績',
            'profit_forecast' => 'Kintone損益見込み',
            'project_completed' => '完了後月',
            'missing_released_actual', 'missing_actual_and_profit' => 'データなし',
            default => ! empty($row['is_forecast_month'])
                ? '見込み'
                : (! empty($row['is_actual']) ? 'Google Sheets実績' : 'データなし'),
        };
    }

    private function formatFiscalComparisonReply(array $result): string
    {
        if (isset($result['error'])) {
            return '取得できませんでした: ' . $result['error'];
        }

        $base = $result['comparison']['base'] ?? [];
        $current = $result['comparison']['current'] ?? [];
        $baseYear = (int) ($base['fiscal_year'] ?? 0);
        $currentYear = (int) ($current['fiscal_year'] ?? 0);
        $baseForecast = $base['forecast_totals'] ?? [];
        $currentForecast = $current['forecast_totals'] ?? [];
        $yoy = $result['yoy_change'] ?? [];
        $filterPm = $result['filter']['pm'] ?? null;
        $scopeLabel = is_array($filterPm)
            ? $this->formatPmName($filterPm) . ' 担当プロジェクト '
            : '';

        $salesPct = is_numeric($yoy['sales_pct'] ?? null)
            ? $this->formatSignedPercent((float) $yoy['sales_pct'])
            : '算出不可';
        $profitPct = is_numeric($yoy['profit_pct'] ?? null)
            ? $this->formatSignedPercent((float) $yoy['profit_pct'])
            : '算出不可';

        $lines = [
            "{$scopeLabel}FY{$baseYear}とFY{$currentYear}の着地見込み比較",
            '',
            "FY{$baseYear}: 売上 " . $this->formatYen((int) ($baseForecast['sales'] ?? 0))
                . '、利益 ' . $this->formatYen((int) ($baseForecast['profit'] ?? 0)),
            "FY{$currentYear}: 売上 " . $this->formatYen((int) ($currentForecast['sales'] ?? 0))
                . '、利益 ' . $this->formatYen((int) ($currentForecast['profit'] ?? 0)),
            '',
            '前年比: 売上 '
                . $this->formatSignedYen((int) ($yoy['sales_amount'] ?? 0))
                . "（{$salesPct}）、利益 "
                . $this->formatSignedYen((int) ($yoy['profit_amount'] ?? 0))
                . "（{$profitPct}）",
        ];

        $salesDirection = ((int) ($yoy['sales_amount'] ?? 0)) >= 0 ? '増収' : '減収';
        $profitDirection = ((int) ($yoy['profit_amount'] ?? 0)) >= 0 ? '増益' : '減益';
        $lines[] = "結論: {$salesDirection}・{$profitDirection}の着地見込みです。";

        return implode("\n", $lines);
    }

    private function formatFinanceUnitLine(array $unit): string
    {
        return sprintf(
            '売上 %s、販管費 %s、利益 %s',
            $this->formatYen((int) ($unit['sales'] ?? 0)),
            $this->formatYen((int) ($unit['expense'] ?? 0)),
            $this->formatYen((int) ($unit['profit'] ?? 0))
        );
    }

    private function formatSignedYen(int|float $amount): string
    {
        $rounded = (int) round($amount);
        if ($rounded === 0) {
            return '±0円';
        }

        return ($rounded > 0 ? '+' : '-') . $this->formatYen(abs($rounded));
    }

    private function formatSignedPercent(int|float $value): string
    {
        $rounded = round((float) $value, 1);
        if ($rounded == 0.0) {
            return '±0%';
        }

        return ($rounded > 0 ? '+' : '') . number_format($rounded, 1) . '%';
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

    private function formatPmName(mixed $pm): string
    {
        if (is_array($pm)) {
            return (string) ($pm['name'] ?? $pm['user_code'] ?? 'PM未設定');
        }

        if (is_string($pm) && $pm !== '') {
            return $pm;
        }

        return 'PM未設定';
    }

    // =========================================================================
    // Tool dispatcher — routes to the correct domain MCP
    // =========================================================================

    private function dispatchTool(
        string $name,
        array $args,
        FinanceToolController $finance
    ): array {
        if (in_array($name, self::toolNames(), true)) {
            return $finance->executeTool($name, $args);
        }

        return ['error' => "Unknown tool: {$name}"];
    }
}
