<?php

namespace App\Services\TimeSheet;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorkReceiptOcrService
{
    public function extract(string $filePath): array
    {
        $disk = Storage::disk('local');
        $storagePath = "timecard_files/{$filePath}";

        if (!$disk->exists($storagePath)) {
            throw ValidationException::withMessages(['message' => '領収書ファイルが見つかりません。']);
        }

        $apiKey = config('services.google.gemini_api_key');
        $baseUrl = rtrim(config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = config('services.google.receipt_ocr_model', 'models/gemini-3-flash-preview');

        if (!$apiKey) {
            throw ValidationException::withMessages(['message' => 'Gemini APIキーが設定されていません。']);
        }

        $fullPath = $disk->path($storagePath);
        $mimeType = $disk->mimeType($storagePath) ?: 'application/octet-stream';
        $base64Data = base64_encode((string) file_get_contents($fullPath));

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    [
                        'text' => <<<TEXT
                            You extract receipt fields from Japanese receipts.
                            Return only normalized data based on visible evidence in the file.
                            If a value is uncertain, return an empty string instead of guessing.
                            Use ISO date format YYYY-MM-DD for receipt_date when possible.
                            Use a plain number for amount and tax_amount without commas or currency symbols.

                            For receipt_source_type:
                            - If the receipt is scanned from paper, return "スキャナ保存"
                            - If the receipt is originally digital (e.g. email PDF, online invoice), return "電子取引データ"
                            - If unclear, default to "スキャナ保存"

                            IMPORTANT: Set multiple_receipts_detected to true if the image contains more than one
                            distinct receipt document (e.g. two receipts photographed side by side, or a collage).
                            When multiple receipts are detected, extract fields from the first (leftmost/topmost)
                            receipt only and set multiple_receipts_detected to true.

                            Return only the result.
                        TEXT,
                    ],
                    [
                        'inline_data' => [
                            'data' => $base64Data,
                            'mimeType' => $mimeType,
                        ],
                    ],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'multiple_receipts_detected' => ['type' => 'boolean'],
                        'merchant_name' => ['type' => 'string'],
                        'receipt_date' => ['type' => 'string'],
                        'amount' => ['type' => 'string'],
                        'currency' => ['type' => 'string'],
                        'receipt_source_type' => ['type' => 'string'],
                        'tax_amount' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => ['multiple_receipts_detected', 'merchant_name', 'receipt_date', 'amount', 'currency', 'receipt_source_type', 'tax_amount', 'notes'],
                ],
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post("{$baseUrl}/{$model}:generateContent?key={$apiKey}", $payload);

        if (!$response->successful()) {
            throw ValidationException::withMessages(['message' => 'Gemini OCRの実行に失敗しました。']);
        }

        $rawResponse = $response->json();
        $rawText = (string) data_get($rawResponse, 'candidates.0.content.parts.0.text', '');

        if ($rawText === '') {
            throw ValidationException::withMessages(['message' => 'Gemini OCRのレスポンスを解析できませんでした。']);
        }

        $decoded = json_decode(trim($rawText), true);
        if (!is_array($decoded)) {
            throw ValidationException::withMessages(['message' => 'Gemini OCRのJSON解析に失敗しました。']);
        }

        $normalized = [
            'merchant_name' => trim((string) Arr::get($decoded, 'merchant_name', '')),
            'receipt_date' => trim((string) Arr::get($decoded, 'receipt_date', '')),
            'amount' => $this->normalizeNumericString(Arr::get($decoded, 'amount')),
            'currency' => strtoupper(trim((string) Arr::get($decoded, 'currency', '円'))) ?: '円',
            'receipt_source_type' => trim((string) Arr::get($decoded, 'receipt_source_type', 'paper_scan')) ?: 'paper_scan',
            'tax_amount' => $this->normalizeNumericString(Arr::get($decoded, 'tax_amount')),
            'notes' => trim((string) Arr::get($decoded, 'notes', '')),
        ];

        $multipleDetected = (bool) Arr::get($decoded, 'multiple_receipts_detected', false);

        return [
            'provider' => 'gemini',
            'model' => $model,
            'multiple_receipts_detected' => $multipleDetected,
            'normalized_result' => $normalized,
            'raw_response' => $rawResponse,
        ];
    }

    private function normalizeNumericString(mixed $value): string
    {
        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return '';
        }

        return preg_replace('/[^0-9.-]/', '', $stringValue) ?: '';
    }
}
