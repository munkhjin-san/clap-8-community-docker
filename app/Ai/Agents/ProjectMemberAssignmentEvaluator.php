<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-5.5')]
#[Timeout(120)]
class ProjectMemberAssignmentEvaluator implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
業務アサイン適正評価AI 指示文書（最終版）

あなたの役割
あなたは、提示される情報を基に、特定の従業員が指定されたプロジェクトの特定の役割に適正かどうかを評価するアサイン判定AIです。
本評価は人事評価・査定を目的とするものではありません。業務ミスマッチ、過重負荷、リスクの発生を未然に防ぐための業務アサイン可否判断の補助を目的とします。

入力される情報
- 個人情報: 氏名、個別配慮事項、過去36ヶ月間のインシデント履歴、過去24ヶ月間の月次目標履歴、最新の人事考課の詳細
- アサイン先情報: プロジェクト名、ミッション、イノベーション、ストラテジー、オペレーション、役割、役割業務詳細、業務遂行条件

評価観点とスコアリング
以下の4項目について、それぞれ1〜10点で評価し、必ず理由を記載してください。

① 就業条件・配慮事項の適合（MUST条件）
10: 完全に適合。制約なく業務遂行可能
8-9: 軽微な配慮は必要だが業務に支障なし
5-7: 一部制約あり。業務設計や補助が必要
3-4: 制約が大きく、継続的な支障が想定される
1-2: 業務遂行条件を満たさない
重要ルール: 本項目が2点以下の場合、他の評価に関わらず overall.score = 0.0、総合判断は「不適」とする。

② 業務相性（スキル・役割適合）
10: 職能レベル・経験ともに役割要件を大きく上回る
8-9: 役割要件を十分に満たしている
6-7: 概ね対応可能だが一部キャッチアップが必要
4-5: 業務遂行は可能だがミスマッチ要素が多い
1-3: 現時点では不十分

③ 実績確認（過去24ヶ月）
10: 継続的に高水準で目標達成、安定性が高い
8-9: 概ね目標達成、再現性あり
6-7: 達成と未達が混在
4-5: 未達が多く安定性に懸念
1-3: 実績不足または継続的未達

④ リスク確認（インシデント）
10: 過去36ヶ月でインシデントなし
8-9: 軽微な事案のみ、再発防止が確認できる
6-7: 注意喚起レベルの事案あり
4-5: 同種インシデントの複数発生
1-3: 重大または反復的なインシデントあり

⑤ プロジェクトマネジャー確認チェック項目
①〜④の評価内容を踏まえ、当メンバーを当役割にアサインした場合に想定される弱点・リスクを明示し、それに対する確認質問型チェック項目を3〜5個出力してください。
抽象論ではなく、評価結果に基づく具体的なリスク内容を質問形式または確認チェック形式で記載してください。
最後に固定項目として content = "その他（自由入力）"、type = "multitext"、options = [] を必ず追加してください。
checkbox の options は基本1個だけとし、質問内容に合わせて「理解しました」または「はい」を使用してください。

⑦ 対応レベル判定
overall score に依存せず、①〜④の評価内容および⑤のリスク内容を基に総合的に判断してください。
- green: 明確なリスクが確認されず、または軽微であり、追加のマネジメント対応を必要としない状態
- orange: リスクまたは不足が存在するが、適切な対応により管理可能な状態。必要な対応内容と、その目的を support_suggestions に具体的に出力すること。
- red: リスクまたは不適合が顕著であり、強い対応または業務設計の見直しが必要な状態。必要な対応内容と、対応しない場合に想定されるリスクを support_suggestions に具体的に出力すること。

overall score の算出
- ① <= 2 の場合: overall score = 0.0、総合判断 = 不適
- ① >= 3 の場合: overall score = ① * 0.30 + ② * 0.30 + ③ * 0.25 + ④ * 0.15
- 小数点第1位で四捨五入する。

総合判断ルール
- if ① <= 2: 不適
- else if overall score >= 8.0 and 全項目 >= 7: 適正あり
- else if overall score >= 6.5: 条件付き適正
- else if overall score >= 5.0: 要再検討
- else: 不適

注意事項
- 推測や補完は行わず、提示された情報のみを根拠とすること。
- 感情的・人格的評価は行わないこと。
- 判断理由は簡潔かつ具体的に記載すること。
- 出力は指定された JSON schema に厳密に合わせること。
PROMPT;
    }

    /**
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        $criterion = fn () => $schema->object([
            'score' => $schema->integer()->min(1)->max(10)->required(),
            'reason' => $schema->string()->min(1)->required(),
            'evidence' => $schema->array()->items($schema->string()->min(1)),
        ]);

        $condition = fn () => $schema->object([
            'title' => $schema->string()->min(1)->required(),
            'detail' => $schema->string()->min(1)->required(),
        ]);

        return [
            'version' => $schema->string()->pattern('^[0-9]+\.[0-9]+\.[0-9]+$')->required(),
            'employee' => $schema->object([
                'name' => $schema->string()->min(1)->required(),
                'employee_id' => $schema->string()->min(1),
            ])->required(),
            'assignment' => $schema->object([
                'project_name' => $schema->string()->min(1)->required(),
                'role_name' => $schema->string()->min(1)->required(),
            ])->required(),
            'evaluations' => $schema->object([
                'must_conditions' => $criterion()->required(),
                'job_fit' => $criterion()->required(),
                'performance_history' => $criterion()->required(),
                'risk_history' => $criterion()->required(),
            ])->required(),
            'overall' => $schema->object([
                'score' => $schema->number()->min(0)->max(10)->multipleOf(0.1)->required(),
                'method' => $schema->string()->enum(['weighted_sum_with_gate'])->required(),
                'weights' => $schema->object([
                    'must_conditions' => $schema->number()->enum([0.3])->required(),
                    'job_fit' => $schema->number()->enum([0.3])->required(),
                    'performance_history' => $schema->number()->enum([0.25])->required(),
                    'risk_history' => $schema->number()->enum([0.15])->required(),
                ])->required(),
                'rounding' => $schema->string()->enum(['round_half_up_1dp'])->required(),
            ])->required(),
            'final_judgement' => $schema->object([
                'decision' => $schema->string()->enum(['適正あり', '条件付き適正', '要再検討', '不適'])->required(),
                'rationale' => $schema->string()->min(1)->required(),
                'conditions' => $schema->array()->items($condition()),
            ])->required(),
            'notes' => $schema->object([
                'limitations' => $schema->array()->items($schema->string()->min(1))->required(),
            ])->required(),
            'project_manager_check_items' => $schema->array()
                ->min(3)
                ->max(6)
                ->items($schema->object([
                    'type' => $schema->string()->enum(['checkbox', 'multitext'])->required(),
                    'content' => $schema->string()->min(1)->required(),
                    'options' => $schema->array()->max(1)->items($schema->string()->min(1))->required(),
                ]))
                ->required(),
            'support_level' => $schema->object([
                'decision' => $schema->string()->enum(['green', 'orange', 'red'])->required(),
                'support_suggestions' => $schema->array()
                    ->min(0)
                    ->max(5)
                    ->items($schema->string()->min(1))
                    ->required(),
            ])->required(),
            'manager_free_notes' => $schema->string(),
            'x-rules' => $schema->object([
                'gate_rule' => $schema->string()->enum([
                    'If must_conditions.score <= 2 then overall.score = 0.0 and final_judgement.decision = \'不適\'.',
                ]),
                'decision_rule' => $schema->string()->enum([
                    'If overall.score >= 8.0 and all criterion scores >= 7 -> 適正あり; else if overall.score >= 6.5 -> 条件付き適正; else if overall.score >= 5.0 -> 要再検討; else -> 不適 (unless gate rule triggered).',
                ]),
            ]),
        ];
    }
}
