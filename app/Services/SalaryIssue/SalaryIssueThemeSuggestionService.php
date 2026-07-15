<?php

namespace App\Services\SalaryIssue;

use App\Models\EvaluationRecord;
use App\Models\ProjectGoal;
use OpenAI;
use Throwable;

/**
 * Suggests ONE salary-issue (昇給課題) theme for a goal, from the caller-supplied
 * eligible candidate set, using the learner's goal + competency (職能) context.
 * Advisory only — the user makes the final choice, and creation is re-validated
 * server-side by SalaryIssueEligibilityService.
 */
class SalaryIssueThemeSuggestionService
{
    /**
     * @param  array<int,array{title_full?:string,title?:string,level?:string,theme?:string,content?:string}>  $candidates
     * @return array{title_full:?string,rationale:?string}
     */
    public function suggest(ProjectGoal $goal, array $candidates): array
    {
        $candidates = array_values(array_filter($candidates, fn ($c) => ! empty($c['title_full'])));

        if ($candidates === []) {
            return ['title_full' => null, 'rationale' => null];
        }

        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            return ['title_full' => null, 'rationale' => null];
        }

        $instructions = <<<'TXT'
        あなたは人材育成の専門家です。従業員の成果目標と職能（コンピテンシー）情報をふまえ、
        提示された候補テーマの中から、その成果目標の達成に最も貢献する職能研修テーマを1つだけ選定してください。
        必ず候補の中から選び、"title_full" は候補の値と完全一致させること。
        出力は次のJSONのみとし、前後に文章やコードフェンスを付けないこと：
        {"title_full": "<候補のtitle_full>", "rationale": "<日本語で200字以内の選定理由>"}
        TXT;

        try {
            $response = OpenAI::client($apiKey)->responses()->create([
                'model' => config('services.openai.chat_model', 'gpt-4.1-mini'),
                'instructions' => $instructions,
                'input' => $this->buildInput($goal, $candidates),
            ]);
        } catch (Throwable $e) {
            return ['title_full' => null, 'rationale' => null];
        }

        $parsed = $this->parseJson($this->extractText($response));
        $chosen = $parsed['title_full'] ?? null;

        if (! in_array($chosen, array_column($candidates, 'title_full'), true)) {
            return ['title_full' => null, 'rationale' => null];
        }

        return [
            'title_full' => $chosen,
            'rationale' => $parsed['rationale'] ?? null,
        ];
    }

    private function buildInput(ProjectGoal $goal, array $candidates): string
    {
        return json_encode([
            'goal' => [
                'title' => $goal->title,
                'outcome_goal' => $goal->outcome_goal,
                'action_plan' => $goal->action_plan,
                'expected_effect' => $goal->expected_effect,
                'situation_analysis' => $goal->situation_analysis,
            ],
            'competency' => $this->competencyContext((int) $goal->user_id, (int) $goal->year, (string) $goal->which_half),
            'candidates' => array_map(fn ($c) => [
                'title_full' => $c['title_full'],
                'axis' => $c['level'] ?? null,
                'theme' => $c['theme'] ?? null,
                'title' => $c['title'] ?? null,
                'description' => $c['content'] ?? null,
            ], $candidates),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function competencyContext(int $userId, int $year, string $whichHalf): array
    {
        $eval = EvaluationRecord::where('user_id', $userId)
            ->where('year', $year)
            ->where('which_half', $whichHalf)
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

    private function parseJson(string $reply): array
    {
        if (preg_match('/\{.*\}/s', trim($reply), $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
