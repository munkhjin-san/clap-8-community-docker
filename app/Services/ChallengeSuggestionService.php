<?php

namespace App\Services;

use App\Models\User;
use OpenAI;
use RuntimeException;

class ChallengeSuggestionService
{
    public function suggest(User $challenger, ?string $idea = null, ?bool $mini = false): array
{
    $promptId = $mini
        ? config('services.openai.prompts.challenge_suggestion')
        : config('services.openai.prompts.normal_challenge_suggestion');

    $apiKey = config('services.openai.api_key');

    if (! $promptId || ! $apiKey) {
        throw new RuntimeException('Challenge suggestion is not configured.');
    }

  
    $payload = [
        'challenge_history' => $challenger->post
            ->map(fn ($post) => array_filter([
                'title' => $post->title,
                'rule' => $post->content_rule,
                'goal' => $post->content_goal,
            ], fn ($value) => filled($value)))
            ->values()
            ->toArray(),

        'portfolio_history' => $challenger->portfolio
            ->map(fn ($item) => array_filter([
                'theme' => $item->lesson_theme?->title,
                'title' => $item->public_title,
                'content' => $item->public_content,
            ], fn ($value) => filled($value)))
            ->values()
            ->toArray(),
    ];
    

    $client = OpenAI::client($apiKey);

    $response = $client->responses()->create([
        'prompt' => [
                'id' => $promptId,
                'variables' => [
                'challenge_history' => json_encode($payload['challenge_history'], JSON_UNESCAPED_UNICODE),
                'portfolio_history' => json_encode($payload['portfolio_history'], JSON_UNESCAPED_UNICODE),
                'user_idea' => $idea ? json_encode($idea, JSON_UNESCAPED_UNICODE) : 'null',
            ]
        ],
    ]);

    $text = trim((string) $this->extractText($response));

    if (str_starts_with($text, '```')) {
        $text = preg_replace('/^```(?:json)?\s*/', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;
    }

    $json = json_decode($text, true);

    if (! is_array($json)) {
        throw new RuntimeException('Challenge suggestion response was not valid JSON.');
    }

    return $json;
}
    private function extractText(object $response): ?string
    {
        $text = $response->outputText ?? null;

        if ($text) {
            return $text;
        }

        $buffer = '';

        foreach (($response->output ?? []) as $output) {
            $contents = is_array($output)
                ? ($output['content'] ?? [])
                : ($output->content ?? []);

            foreach ($contents as $content) {
                $value = is_array($content)
                    ? ($content['text'] ?? null)
                    : ($content->text ?? null);

                if ($value) {
                    $buffer .= $value;
                }
            }
        }

        return $buffer !== '' ? $buffer : null;
    }
}
