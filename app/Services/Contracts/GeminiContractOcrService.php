<?php

namespace App\Services\Contracts;

use Illuminate\Support\Arr;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GeminiContractOcrService
{
    private const INLINE_PDF_MAX_BYTES = 50 * 1024 * 1024;

    public function extractImagePages(array $imageFiles): array
    {
        $apiKey = config('services.google.gemini_api_key');
        $baseUrl = rtrim(config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = config('services.google.contract_ocr_model', 'models/gemini-3.6-flash');
        $timeout = max(10, (int) config('services.google.contract_ocr_page_timeout', 90));

        if (!$apiKey) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $imageFiles = array_values(array_filter($imageFiles));
        if ($imageFiles === []) {
            throw new RuntimeException('Rendered PDF page images were not provided.');
        }

        $pages = [];
        $pageCount = count($imageFiles);

        foreach ($imageFiles as $index => $imageFile) {
            $pageNumber = $index + 1;
            $imagePath = method_exists($imageFile, 'getRealPath') ? $imageFile->getRealPath() : (string) $imageFile;
            $mimeType = method_exists($imageFile, 'getMimeType') ? $imageFile->getMimeType() : null;
            $mimeType = $mimeType ?: $this->guessImageMimeType($imagePath);

            if (!is_file($imagePath)) {
                throw new RuntimeException('Rendered PDF page image was not found.');
            }

            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $this->renderedPagePrompt($pageNumber, $pageCount),
                        ],
                        [
                            'inline_data' => [
                                'data' => base64_encode((string) file_get_contents($imagePath)),
                                'mimeType' => $mimeType,
                            ],
                        ],
                    ],
                ]],
                'generationConfig' => $this->pageJsonGenerationConfig(),
            ];

            $response = $this->postGemini($baseUrl, $model, $apiKey, $payload, $timeout);
            if (!$response->successful()) {
                throw new RuntimeException('Gemini contract OCR failed on rendered page '.$pageNumber.': '.$this->sanitizeGeminiError($response->body()));
            }

            $rawResponse = $response->json();
            $this->ensureCompleteGeminiResponse(is_array($rawResponse) ? $rawResponse : [], 'rendered page '.$pageNumber);
            $pageResults = $this->parseGeminiPages($rawResponse);
            $pages[] = [
                'page' => $pageNumber,
                'text' => trim(implode("\n\n", array_map(
                    static fn (array $page) => (string) ($page['text'] ?? ''),
                    $pageResults
                ))),
            ];
        }

        return $this->normalizePages($pages);
    }

    public function extractPdfPages(string $absolutePath): array
    {
        if (!is_file($absolutePath)) {
            throw new RuntimeException('PDF file was not found.');
        }

        $fileSize = filesize($absolutePath);
        if ($fileSize === false || $fileSize <= 0) {
            throw new RuntimeException('PDF file is empty or unreadable.');
        }

        if ($fileSize > (int) config('contracts.inline_ocr_max_bytes', self::INLINE_PDF_MAX_BYTES)) {
            throw new RuntimeException('PDF is too large for inline Gemini OCR.');
        }

        $apiKey = config('services.google.gemini_api_key');
        $baseUrl = rtrim(config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = config('services.google.contract_ocr_model', 'models/gemini-3.6-flash');
        $timeout = max(10, (int) config('services.google.contract_ocr_timeout', 120));
        $pageTimeout = max(10, (int) config('services.google.contract_ocr_page_timeout', 90));
        $maxOutputTokens = max(2048, (int) config('services.google.contract_ocr_max_output_tokens', 32768));

        if (!$apiKey) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $pageCount = $this->estimatePdfPageCount($absolutePath);
        if ($this->shouldRenderPdfPages()) {
            try {
                return $this->extractRenderedPdfPagesOneAtATime($absolutePath, $pageCount, $baseUrl, $model, $apiKey, $pageTimeout);
            } catch (PdfRenderingUnavailableException $exception) {
                throw new RuntimeException(
                    'PDF page rendering failed before Gemini OCR. Install/enable Ghostscript for Imagick PDF rendering, or set GEMINI_CONTRACT_OCR_RENDER_PAGES=false to allow inline PDF OCR fallback. '.$exception->getMessage(),
                    previous: $exception
                );
            }
        }

        if ($pageCount > 1 && filter_var(config('services.google.contract_ocr_chunk_pages', true), FILTER_VALIDATE_BOOL)) {
            return $this->extractPdfPagesOneAtATime($absolutePath, $pageCount, $baseUrl, $model, $apiKey, $pageTimeout);
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

        $response = $this->postGemini($baseUrl, $model, $apiKey, $payload, $timeout);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini contract OCR failed: '.$this->sanitizeGeminiError($response->body()));
        }

        $rawResponse = $response->json();
        $this->ensureCompleteGeminiResponse(is_array($rawResponse) ? $rawResponse : [], 'PDF OCR');

        return $this->parseGeminiPages($rawResponse);
    }

    private function parseGeminiPages(mixed $rawResponse): array
    {
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

    private function extractPdfPagesOneAtATime(
        string $absolutePath,
        int $pageCount,
        string $baseUrl,
        string $model,
        string $apiKey,
        int $timeout,
    ): array {
        $pages = [];
        $pdfData = base64_encode((string) file_get_contents($absolutePath));

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $this->pageOnlyPrompt($pageNumber, $pageCount),
                        ],
                        [
                            'inline_data' => [
                                'data' => $pdfData,
                                'mimeType' => 'application/pdf',
                            ],
                        ],
                    ],
                ]],
                'generationConfig' => $this->pageJsonGenerationConfig(),
            ];

            $response = $this->postGemini($baseUrl, $model, $apiKey, $payload, $timeout);
            if (!$response->successful()) {
                throw new RuntimeException('Gemini contract OCR failed on page '.$pageNumber.': '.$this->sanitizeGeminiError($response->body()));
            }

            $rawResponse = $response->json();
            $this->ensureCompleteGeminiResponse(is_array($rawResponse) ? $rawResponse : [], 'page '.$pageNumber);
            $pageResults = $this->parseGeminiPages($rawResponse);
            $pageText = trim(implode("\n\n", array_map(
                static fn (array $page) => (string) ($page['text'] ?? ''),
                $pageResults
            )));
            $pages[] = [
                'page' => $pageNumber,
                'text' => $pageText,
            ];
        }

        return $this->normalizePages($pages);
    }

    private function extractRenderedPdfPagesOneAtATime(
        string $absolutePath,
        int $pageCount,
        string $baseUrl,
        string $model,
        string $apiKey,
        int $timeout,
    ): array {
        $pages = [];

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            try {
                $pngBytes = $this->renderPdfPageToPng($absolutePath, $pageNumber);
            } catch (Throwable $exception) {
                throw new PdfRenderingUnavailableException($exception->getMessage(), previous: $exception);
            }

            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $this->renderedPagePrompt($pageNumber, $pageCount),
                        ],
                        [
                            'inline_data' => [
                                'data' => base64_encode($pngBytes),
                                'mimeType' => 'image/png',
                            ],
                        ],
                    ],
                ]],
                'generationConfig' => $this->pageJsonGenerationConfig(),
            ];

            $response = $this->postGemini($baseUrl, $model, $apiKey, $payload, $timeout);
            if (!$response->successful()) {
                throw new RuntimeException('Gemini contract OCR failed on rendered page '.$pageNumber.': '.$this->sanitizeGeminiError($response->body()));
            }

            $rawResponse = $response->json();
            $this->ensureCompleteGeminiResponse(is_array($rawResponse) ? $rawResponse : [], 'rendered page '.$pageNumber);
            $pageResults = $this->parseGeminiPages($rawResponse);
            $pageText = trim(implode("\n\n", array_map(
                static fn (array $page) => (string) ($page['text'] ?? ''),
                $pageResults
            )));
            $pages[] = [
                'page' => $pageNumber,
                'text' => $pageText,
            ];
        }

        return $this->normalizePages($pages);
    }

    private function pageOnlyPrompt(int $pageNumber, int $pageCount): string
    {
        return <<<TEXT
You are extracting visible text from a Japanese contract PDF.
OCR only page {$pageNumber} of {$pageCount}. Ignore all other pages.
Ignore broken embedded/selectable text layers and OCR the rendered visual page.
Transcribe every visible heading, article title, numbered paragraph, bullet, table cell, footer, and signature label.
Return only visible text. Do not summarize, translate, explain, rewrite, or add missing clauses.
If a small part is unreadable, write [illegible] at that position instead of skipping the surrounding line.
Do not preserve layout whitespace. Never emit more than one blank line in a row.
Return compact JSON only in this shape:
{"pages":[{"page":{$pageNumber},"text":"..."}]}
TEXT;
    }

    private function renderedPagePrompt(int $pageNumber, int $pageCount): string
    {
        return <<<TEXT
You are extracting visible text from a rendered image of a Japanese contract page.
OCR this image as page {$pageNumber} of {$pageCount}.
Transcribe every visible heading, article title, numbered paragraph, bullet, table cell, footer, and signature label.
Return only visible text. Do not summarize, translate, explain, rewrite, or add missing clauses.
If a small part is unreadable, write [illegible] at that position instead of skipping the surrounding line.
Preserve reading order and useful line breaks.
Do not preserve layout whitespace. Never emit more than one blank line in a row.
Return compact JSON only in this shape:
{"pages":[{"page":{$pageNumber},"text":"..."}]}
TEXT;
    }

    private function pageJsonGenerationConfig(): array
    {
        return [
            'temperature' => 0.0,
            'maxOutputTokens' => max(8192, (int) config('services.google.contract_ocr_max_output_tokens', 32768)),
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
        ];
    }

    private function ensureCompleteGeminiResponse(array $rawResponse, string $context): void
    {
        $finishReason = Arr::get($rawResponse, 'candidates.0.finishReason');
        if (!is_string($finishReason) || $finishReason === '' || $finishReason === 'STOP') {
            return;
        }

        throw new RuntimeException(
            'Gemini contract OCR stopped before completing '.$context.' (finishReason: '.$finishReason.').'
        );
    }

    private function guessImageMimeType(string $path): string
    {
        $mimeType = is_file($path) ? @mime_content_type($path) : null;

        return is_string($mimeType) && str_starts_with($mimeType, 'image/')
            ? $mimeType
            : 'image/png';
    }

    protected function renderPdfPageToPng(string $absolutePath, int $pageNumber): string
    {
        if (!extension_loaded('imagick') || !class_exists(\Imagick::class)) {
            throw new RuntimeException('Imagick is not available for PDF page rendering.');
        }

        $resolution = max(72, min(300, (int) config('services.google.contract_ocr_render_resolution', 180)));
        $sourceImage = new \Imagick();
        $renderedImage = null;

        try {
            $sourceImage->setResolution($resolution, $resolution);
            $sourceImage->readImage($absolutePath.'['.($pageNumber - 1).']');
            $sourceImage->setImageBackgroundColor('white');
            $renderedImage = $sourceImage->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $renderedImage->setImageFormat('png');
            $renderedImage->setImageDepth(8);
            $renderedImage->stripImage();

            $blob = $renderedImage->getImageBlob();
            if (!is_string($blob) || $blob === '') {
                throw new RuntimeException('Rendered PDF page image was empty.');
            }

            return $blob;
        } finally {
            if ($renderedImage instanceof \Imagick) {
                $renderedImage->clear();
                $renderedImage->destroy();
            }

            $sourceImage->clear();
            $sourceImage->destroy();
        }
    }

    private function postGemini(string $baseUrl, string $model, string $apiKey, array $payload, int $timeout): Response
    {
        try {
            return Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->retry(
                    3,
                    750,
                    function (Throwable $exception): bool {
                        if ($exception instanceof ConnectionException) {
                            return true;
                        }

                        if ($exception instanceof RequestException) {
                            $status = $exception->response->status();

                            return $status === 429 || $status >= 500;
                        }

                        return false;
                    },
                    throw: false,
                )
                ->post("{$baseUrl}/{$model}:generateContent", $payload);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Gemini contract OCR request failed: '.$this->sanitizeGeminiError($exception->getMessage()),
                previous: $exception
            );
        }
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

    private function estimatePdfPageCount(string $absolutePath): int
    {
        $contents = (string) file_get_contents($absolutePath);
        $inspectionContents = $this->appendDecodedPdfStreams($contents);
        $pageCount = preg_match_all('/\/Type\s*\/Page\b/', $inspectionContents) ?: 0;

        return max(1, $pageCount);
    }

    private function shouldRenderPdfPages(): bool
    {
        return filter_var(config('services.google.contract_ocr_render_pages', false), FILTER_VALIDATE_BOOL);
    }

    private function appendDecodedPdfStreams(string $contents): string
    {
        if (!str_contains($contents, 'stream')) {
            return $contents;
        }

        $decodedContents = '';
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $contents, $matches);

        foreach ($matches[1] ?? [] as $stream) {
            $decoded = @zlib_decode($stream);
            if ($decoded === false) {
                $decoded = @gzuncompress($stream);
            }

            if ($decoded !== false && $decoded !== '') {
                $decodedContents .= "\n".$decoded;
            }
        }

        return $decodedContents === '' ? $contents : $contents."\n".$decodedContents;
    }

    private function sanitizeGeminiError(string $message): string
    {
        $message = preg_replace('/([?&]key=)[^&\s)]+/i', '$1[redacted]', $message) ?? $message;
        $message = preg_replace('/AIza[0-9A-Za-z_\-]{20,}/', '[redacted-api-key]', $message) ?? $message;

        return $message;
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
