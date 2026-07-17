<?php

namespace App\Services\SalaryIssue;

use App\Models\EvaluationRecord;
use App\Models\LessonMaterial;
use App\Models\LessonPersonalMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonTheme;
use App\Models\SalaryIssue;
use OpenAI;
use Throwable;

/**
 * Path-3 (salary challenger) learning generation.
 *
 * Unlike the plain repeater (path 2, which uses a previous_version theme), a
 * salary challenger studies the SAME target theme they already cleared. So the
 * "previous learning" is the user's own path-1 portfolio on that theme, and the
 * generated study material is additionally focused on the chosen monthly goal.
 *
 * The generated material and the resulting portfolio are scoped to the specific
 * challenge (personal-material config_key "salary_issue_{id}"; portfolio via the
 * lesson_portfolios.salary_issue_id column).
 */
class SalaryIssueLearningService
{
    public function __construct(private SalaryIssueEligibilityService $eligibility) {}

    public function configKey(SalaryIssue $salaryIssue): string
    {
        return 'salary_issue_'.$salaryIssue->id;
    }

    /** Resolve (and backfill) the target lesson theme for a challenge. */
    public function targetTheme(SalaryIssue $salaryIssue): ?LessonTheme
    {
        $themeId = $salaryIssue->lesson_theme_id ?: $this->eligibility->resolveThemeId($salaryIssue->theme);

        if (! $themeId) {
            return null;
        }

        if (! $salaryIssue->lesson_theme_id) {
            $salaryIssue->forceFill(['lesson_theme_id' => $themeId])->save();
        }

        return LessonTheme::find($themeId);
    }

    /** The user's path-1 (first-learner) completed portfolio on the target theme. */
    public function priorPortfolio(SalaryIssue $salaryIssue, LessonTheme $theme): ?LessonPortfolio
    {
        return LessonPortfolio::where('lesson_theme_id', $theme->id)
            ->where('user_id', $salaryIssue->user_id)
            ->whereNull('salary_issue_id')
            ->where('status', 3)
            ->first();
    }

    /** The challenge-scoped portfolio (created on demand) as a new attempt. */
    public function challengePortfolio(SalaryIssue $salaryIssue, LessonTheme $theme): LessonPortfolio
    {
        $existing = LessonPortfolio::where('salary_issue_id', $salaryIssue->id)->first();
        if ($existing) {
            return $existing;
        }

        $nextAttempt = (int) LessonPortfolio::where('lesson_theme_id', $theme->id)
            ->where('user_id', $salaryIssue->user_id)
            ->max('attempt_no') + 1;

        return LessonPortfolio::create([
            'lesson_theme_id' => $theme->id,
            'user_id' => $salaryIssue->user_id,
            'salary_issue_id' => $salaryIssue->id,
            'attempt_no' => $nextAttempt,
            'status' => 0,
        ]);
    }

    /**
     * Generate the personalized study material for a challenge.
     *
     * @return array{content:?string, personal_material_id:?int, portfolio_id:?int}
     */
    public function generateStudyMaterial(SalaryIssue $salaryIssue): array
    {
        $theme = $this->targetTheme($salaryIssue);
        if (! $theme) {
            return ['content' => null, 'personal_material_id' => null, 'portfolio_id' => null];
        }

        $portfolio = $this->challengePortfolio($salaryIssue, $theme);
        $prior = $this->priorPortfolio($salaryIssue, $theme);

        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            return ['content' => null, 'personal_material_id' => null, 'portfolio_id' => $portfolio->id];
        }

        $instructions = <<<'TXT'
        あなたは人材育成の専門家です。昇給課題に挑戦する社員向けに、選択された研修テーマと本人の成果目標に沿った
        「自分専用の職能研修資料」を日本語で作成してください。前回のポートフォリオがある場合は、その内容を最新の考え方で
        見直す形で発展させること。復習ではなく、成果目標の達成に必要な知識・考え方・行動を補完する構成にすること。
        出力はMarkdownの本文のみとし、末尾にグループディスカッション用のトークテーマを3つ提示してください。
        TXT;

        try {
            $response = OpenAI::client($apiKey)->responses()->create([
                'model' => config('services.openai.chat_model', 'gpt-4.1-mini'),
                'instructions' => $instructions,
                'input' => $this->buildInput($salaryIssue, $theme, $prior),
            ]);
        } catch (Throwable $e) {
            return ['content' => null, 'personal_material_id' => null, 'portfolio_id' => $portfolio->id];
        }

        $content = $this->extractText($response);
        if ($content === '') {
            return ['content' => null, 'personal_material_id' => null, 'portfolio_id' => $portfolio->id];
        }

        $material = LessonPersonalMaterial::updateOrCreate(
            [
                'lesson_theme_id' => $theme->id,
                'user_id' => $salaryIssue->user_id,
                'config_key' => $this->configKey($salaryIssue),
            ],
            [
                'content' => $content,
                'understand' => null,
                'important_point' => null,
                'source_snapshot' => [
                    'salary_issue_id' => $salaryIssue->id,
                    'project_goal_id' => $salaryIssue->project_goal_id,
                    'prior_portfolio_id' => $prior?->id,
                ],
                'generated_at' => now(),
                'completed_at' => null,
            ]
        );

        return [
            'content' => $content,
            'personal_material_id' => $material->id,
            'portfolio_id' => $portfolio->id,
        ];
    }

    /** Current learning state for the in-modal journey. */
    public function state(SalaryIssue $salaryIssue): array
    {
        $theme = $this->targetTheme($salaryIssue);

        if (! $theme) {
            return ['theme_title' => null, 'has_prior_portfolio' => false, 'material' => null, 'portfolio' => null];
        }

        $material = LessonPersonalMaterial::where('lesson_theme_id', $theme->id)
            ->where('user_id', $salaryIssue->user_id)
            ->where('config_key', $this->configKey($salaryIssue))
            ->first();

        $portfolio = LessonPortfolio::where('salary_issue_id', $salaryIssue->id)->first();

        return [
            'theme_title' => $theme->title,
            'has_prior_portfolio' => (bool) $this->priorPortfolio($salaryIssue, $theme),
            'material' => $material ? [
                'content' => $material->content,
                'understand' => $material->understand,
                'important_point' => $material->important_point,
            ] : null,
            'portfolio' => $portfolio ? [
                'public_title' => $portfolio->public_title,
                'public_content' => $portfolio->public_content,
                'noticed' => $portfolio->noticed,
                'discussion_topic' => $portfolio->discussion_topic,
                'status' => (int) $portfolio->status,
            ] : null,
        ];
    }

    public function saveUnderstanding(SalaryIssue $salaryIssue, bool $understand, ?string $importantPoint): void
    {
        $theme = $this->targetTheme($salaryIssue);
        if (! $theme) {
            return;
        }

        LessonPersonalMaterial::where('lesson_theme_id', $theme->id)
            ->where('user_id', $salaryIssue->user_id)
            ->where('config_key', $this->configKey($salaryIssue))
            ->update([
                'understand' => $understand,
                'important_point' => $importantPoint,
                'completed_at' => $understand ? now() : null,
            ]);
    }

    /**
     * Save (draft) or submit the challenge portfolio. Status: 1 = draft, 3 = submitted.
     * Additive artifact — does not change the salary-issue status flow.
     */
    public function savePortfolio(SalaryIssue $salaryIssue, array $data, bool $submit): LessonPortfolio
    {
        $theme = $this->targetTheme($salaryIssue);
        $portfolio = $this->challengePortfolio($salaryIssue, $theme);

        $portfolio->update([
            'public_title' => $data['public_title'] ?? $portfolio->public_title,
            'public_content' => $data['public_content'] ?? null,
            'noticed' => $data['noticed'] ?? null,
            'discussion_topic' => $data['discussion_topic'] ?? null,
            'status' => $submit ? 3 : 1,
        ]);

        return $portfolio;
    }

    private function buildInput(SalaryIssue $salaryIssue, LessonTheme $theme, ?LessonPortfolio $prior): string
    {
        $goal = $salaryIssue->project_goal;

        $materials = LessonMaterial::where('lesson_theme_id', $theme->id)
            ->orderBy('priority')
            ->orderBy('id')
            ->get(['title', 'material_type', 'content'])
            ->map(fn (LessonMaterial $m) => [
                'title' => $m->title,
                'material_type' => $m->material_type,
                'content' => strip_tags((string) $m->content),
            ])
            ->values()
            ->all();

        return json_encode([
            'purpose' => '昇給課題に挑戦する社員向けの、成果目標に沿った個人専用研修資料を作成する',
            'theme' => [
                'title' => $theme->title,
                'title_full' => $salaryIssue->theme,
                'guidance' => strip_tags((string) $theme->guidance),
                'materials' => $materials,
            ],
            'goal' => $goal ? [
                'title' => $goal->title,
                'outcome_goal' => $goal->outcome_goal,
                'action_plan' => $goal->action_plan,
                'expected_effect' => $goal->expected_effect,
                'kgi' => $goal->kgi,
            ] : null,
            'competency' => $this->competencyContext($salaryIssue),
            'previous_portfolio' => $prior ? [
                'title' => $prior->public_title,
                'content' => $prior->public_content,
                'positive_feedback' => $prior->positive_feedback,
                'negative_feedback' => $prior->negative_feedback,
                'basic_knowledge' => $prior->basic_knowledge,
            ] : null,
            'output_expectation' => [
                '個人専用の研修資料（Markdown本文）',
                'グループディスカッションテーマ3つ',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function competencyContext(SalaryIssue $salaryIssue): array
    {
        $goal = $salaryIssue->project_goal;
        if (! $goal) {
            return ['current_level' => null, 'target_skills' => []];
        }

        $eval = EvaluationRecord::where('user_id', $salaryIssue->user_id)
            ->where('year', $goal->year)
            ->where('which_half', $goal->which_half)
            ->with('checklist')
            ->first();

        if (! $eval) {
            return ['current_level' => null, 'target_skills' => []];
        }

        return [
            'current_level' => $eval->current_level,
            'target_skills' => $eval->checklist->pluck('content')->filter()->values()->all(),
        ];
    }

    private function extractText($response): string
    {
        $reply = '';

        if (($response->status ?? null) === 'completed') {
            foreach ($response->output as $output) {
                if (isset($output['role']) && $output['role'] === 'assistant') {
                    foreach ($output['content'] as $content) {
                        $reply .= $content['text'] ?? '';
                    }
                }
            }
        }

        return $reply;
    }
}
