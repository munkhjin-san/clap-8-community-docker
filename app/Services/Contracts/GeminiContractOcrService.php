<?php

namespace App\Services\Contracts;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiContractOcrService
{
    private const INLINE_PDF_MAX_BYTES = 50 * 1024 * 1024;

    public function extractPdfPages(string $absolutePath): array
    {
        if (!is_file($absolutePath)) {
            throw new RuntimeException('PDF file was not found.');
        }

        $fileSize = filesize($absolutePath);
        if ($fileSize === false || $fileSize <= 0) {
            throw new RuntimeException('PDF file is empty or unreadable.');
        }

        if ($fileSize > self::INLINE_PDF_MAX_BYTES) {
            throw new RuntimeException('PDF is too large for inline Gemini OCR.');
        }

        $apiKey = config('services.google.gemini_api_key');
        $baseUrl = rtrim(config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = config('services.google.contract_ocr_model', 'models/gemini-3-flash-preview');
        $timeout = max(10, (int) config('services.google.contract_ocr_timeout', 120));
        $maxOutputTokens = max(2048, (int) config('services.google.contract_ocr_max_output_tokens', 32768));

        if (!$apiKey) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    [
                        'text' => <<<'TEXT'
You are extracting visible text from a Japanese contract PDF.
Return only text that is visible in the document.
Do not summarize, translate, explain, or add missing clauses.
Preserve page boundaries and reading order as much as possible.
For each page, return the complete page text with line breaks.
If a page has no readable text, return an empty string for that page.
Ignore broken embedded/selectable text layers and OCR the rendered visual document.
Do not preserve layout whitespace. Never emit more than one blank line in a row.
Return compact JSON only.
TEXT,
                    ],
                    [
                        'inline_data' => [
                            'data' => base64_encode((string) file_get_contents($absolutePath)),
                            'mimeType' => 'application/pdf',
                        ],
                    ],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.0,
                'maxOutputTokens' => $maxOutputTokens,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'pages' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'page' => ['type' => 'integer'],
                                    'text' => ['type' => 'string'],
                                ],
                                'required' => ['page', 'text'],
                            ],
                        ],
                    ],
                    'required' => ['pages'],
                ],
            ],
        ];

        $response = Http::timeout($timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$baseUrl}/{$model}:generateContent?key={$apiKey}", $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini contract OCR failed: '.$response->body());
        }

        $rawResponse = $response->json();
        $rawText = $this->extractResponseText(is_array($rawResponse) ? $rawResponse : []);

        if ($rawText === '') {
            throw new RuntimeException('Gemini contract OCR returned an empty response.');
        }

        $decoded = $this->decodeJsonPayload($rawText);
        if (!is_array($decoded)) {
            $pages = $this->extractPagesFromMalformedJson($rawText);
            if ($pages !== []) {
                return $this->normalizePages($pages);
            }

            if ($this->looksLikeJsonPayload($rawText)) {
                throw new RuntimeException('Gemini contract OCR returned malformed JSON.');
            }

            return $this->normalizePages([[
                'page' => 1,
                'text' => $rawText,
            ]]);
        }

        $pages = Arr::get($decoded, 'pages');
        if (!is_array($pages)) {
            return $this->normalizePages([[
                'page' => 1,
                'text' => $rawText,
            ]]);
        }

        return $this->normalizePages($pages);
    }

    private function extractPagesFromMalformedJson(string $rawText): array
    {
        if (!str_contains($rawText, '"text"') && !str_contains($rawText, "'text'")) {
            return [];
        }

        $pages = [];
        preg_match_all(
            '/["\']page["\']\s*:\s*(\d+).*?["\']text["\']\s*:\s*"((?:\\\\.|[^"\\\\])*)"/su',
            $rawText,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $pages[] = [
                'page' => (int) $match[1],
                'text' => $this->decodeJsonStringFragment($match[2]),
            ];
        }

        if ($pages !== []) {
            return $pages;
        }

        if (preg_match('/["\']text["\']\s*:\s*"(.+)\z/su', $rawText, $match) === 1) {
            return [[
                'page' => 1,
                'text' => $this->decodeJsonStringFragment($match[1]),
            ]];
        }

        return [];
    }

    private function decodeJsonStringFragment(string $value): string
    {
        $decoded = json_decode('"'.$value.'"');
        if (is_string($decoded)) {
            return $decoded;
        }

        return str_replace(
            ['\\r\\n', '\\n', '\\r', '\\t', '\\"', '\\/'],
            ["\n", "\n", "\n", "\t", '"', '/'],
            $value
        );
    }

    private function looksLikeJsonPayload(string $text): bool
    {
        $trimmed = ltrim($text);

        return str_starts_with($trimmed, '{')
            || str_starts_with($trimmed, '[')
            || str_contains($text, '"pages"')
            || str_contains($text, "'pages'");
    }

    private function extractResponseText(array $rawResponse): string
    {
        $parts = Arr::get($rawResponse, 'candidates.0.content.parts', []);
        if (!is_array($parts)) {
            return '';
        }

        $texts = [];
        foreach ($parts as $part) {
            if (!is_array($part)) {
                continue;
            }

            $text = trim((string) Arr::get($part, 'text', ''));
            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return trim(implode("\n", $texts));
    }

    private function decodeJsonPayload(string $rawText): ?array
    {
        foreach ($this->jsonDecodeCandidates($rawText) as $candidate) {
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function jsonDecodeCandidates(string $rawText): array
    {
        $rawText = trim($rawText, "\xEF\xBB\xBF \t\n\r\0\x0B");
        $candidates = [$rawText];

        if (preg_match('/```(?:json)?\s*(.*?)```/is', $rawText, $matches) === 1) {
            $candidates[] = trim($matches[1]);
        }

        $balancedJson = $this->extractBalancedJson($rawText);
        if ($balancedJson !== null) {
            $candidates[] = $balancedJson;
        }

        return array_values(array_unique(array_filter(
            $candidates,
            static fn (string $candidate) => trim($candidate) !== ''
        )));
    }

    private function extractBalancedJson(string $text): ?string
    {
        $start = $this->findFirstJsonStart($text);
        if ($start === null) {
            return null;
        }

        $opening = $text[$start];
        $closing = $opening === '{' ? '}' : ']';
        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($index = $start; $index < $length; $index++) {
            $char = $text[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }

            if ($char === $opening) {
                $depth++;
                continue;
            }

            if ($char === $closing) {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $index - $start + 1);
                }
            }
        }

        return null;
    }

    private function findFirstJsonStart(string $text): ?int
    {
        $objectStart = strpos($text, '{');
        $arrayStart = strpos($text, '[');

        if ($objectStart === false && $arrayStart === false) {
            return null;
        }

        if ($objectStart === false) {
            return $arrayStart;
        }

        if ($arrayStart === false) {
            return $objectStart;
        }

        return min($objectStart, $arrayStart);
    }

    private function normalizePages(array $pages): array
    {
        $normalized = [];

        foreach ($pages as $index => $page) {
            if (!is_array($page)) {
                continue;
            }

            $text = $this->normalizeText((string) Arr::get($page, 'text', ''));
            $pageNumber = (int) Arr::get($page, 'page', $index + 1);

            $normalized[] = [
                'page' => $pageNumber > 0 ? $pageNumber : count($normalized) + 1,
                'lines' => $this->splitLines($text),
                'text' => $text,
            ];
        }

        if ($normalized !== []) {
            return $normalized;
        }

        return [[
            'page' => 1,
            'lines' => [],
            'text' => '',
        ]];
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace("\r", '', $text);
        $text = str_replace("\u{3000}", ' ', $text);
        $text = preg_replace('/[ \t]+\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n[ \t]+/u', "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function splitLines(string $text): array
    {
        return array_map(
            static fn (string $line) => trim($line),
            preg_split("/\n/u", $text) ?: []
        );
    }
}
