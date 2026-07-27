<?php

namespace App\Services\Support;

class OpenAiFileSearchResponseParser
{
    /**
     * @return array{reply: string, sources: array<int, string>, keywords: array<int, string>}
     */
    public function parse(array $response): array
    {
        $reply = '';
        $sources = [];
        $keywords = [];

        foreach ($response['output'] ?? [] as $output) {
            if (($output['type'] ?? null) === 'message') {
                foreach ($output['content'] ?? [] as $content) {
                    if (($content['type'] ?? null) === 'output_text') {
                        $reply .= (string) ($content['text'] ?? '');
                    }
                }
            }

            if (($output['type'] ?? null) !== 'file_search_call') {
                continue;
            }

            foreach ($output['queries'] ?? [] as $query) {
                if (is_string($query) && $query !== '') {
                    $keywords[] = $query;
                }
            }

            foreach ($output['results'] ?? [] as $result) {
                $reference = $this->reference($result);

                if ($reference) {
                    $sources[] = $reference;
                }
            }
        }

        return [
            'reply' => trim($reply),
            'sources' => array_values(array_unique($sources)),
            'keywords' => array_values(array_unique($keywords)),
        ];
    }

    private function reference(array $result): ?string
    {
        $attributes = $result['attributes'] ?? [];
        $faqQuestion = $attributes['question'] ?? null;

        if (is_string($faqQuestion) && $faqQuestion !== '') {
            return "FAQ「{$faqQuestion}」";
        }

        $fileName = $result['filename'] ?? null;
        $title = $attributes['original_file_name'] ?? $this->titleFromFileName($fileName);
        $page = $attributes['page'] ?? $this->pageFromFileName($fileName);

        if (! is_string($title) || $title === '') {
            return null;
        }

        return $page ? "{$title} p.{$page}" : $title;
    }

    private function titleFromFileName(?string $fileName): ?string
    {
        if (! $fileName) {
            return null;
        }

        return preg_replace('/__p\d+\.md$/i', '.pdf', $fileName);
    }

    private function pageFromFileName(?string $fileName): ?int
    {
        if (! $fileName || ! preg_match('/__p(\d+)\.md$/i', $fileName, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
