<?php

namespace App\Services\TimeSheet;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorkReceiptOcrService
{
    public function extract(string $filePath, ?int $expenseType = null): array
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

        $shouldExtractTransport = in_array($expenseType, [1, 4], true);
        $transportPrompt = $shouldExtractTransport
            ? <<<TEXT
                The current expense type is transportation-related ({$expenseType}).
                Aggressively extract transport_type, departure_place, and arrival_place when visible.
                If a route summary or transit screenshot is shown, prefer the actual departure and arrival points.
            TEXT
            : <<<TEXT
                The current expense type is not confirmed as transportation-related.
                Only return transport_type, departure_place, and arrival_place if they are clearly visible in the image.
                Otherwise return empty strings for those fields.
            TEXT;

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
                            If the image shows month/day without year (for example 4月4日 or 04/04),
                            use the current year.
                            Use a plain number for amount and tax_amount without commas or currency symbols.

                            For receipt_source_type:
                            - If the receipt is scanned from paper, return "スキャナ保存"
                            - If the receipt is originally digital (e.g. email PDF, online invoice), return "電子取引データ"
                            - If unclear, default to "スキャナ保存"

                            For transport_type, use:
                            - 1: 電車のみ
                            - 2: 電車・バス
                            - 3: タクシー
                            - 4: 飛行機
                            - 5: その他

                            {$transportPrompt}
                            If departure or arrival is not visible, return an empty string for that field.

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
                        'transport_type' => ['type' => 'integer'],
                        'departure_place' => ['type' => 'string'],
                        'arrival_place' => ['type' => 'string'],
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
            'receipt_date' => $this->normalizeReceiptDate(Arr::get($decoded, 'receipt_date')),
            'amount' => $this->normalizeNumericString(Arr::get($decoded, 'amount')),
            'currency' => strtoupper(trim((string) Arr::get($decoded, 'currency', 'JPY'))) ?: 'JPY',
            'receipt_source_type' => $this->normalizeReceiptSourceType(Arr::get($decoded, 'receipt_source_type')),
            'transport_type' => $this->normalizeTransportType(Arr::get($decoded, 'transport_type')),
            'departure_place' => trim((string) Arr::get($decoded, 'departure_place', '')),
            'arrival_place' => trim((string) Arr::get($decoded, 'arrival_place', '')),
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

    private function normalizeReceiptDate(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return Carbon::today()->toDateString();
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        if (preg_match('/^(\d{4})[\/\.](\d{1,2})[\/\.](\d{1,2})$/', $raw, $matches)) {
            return Carbon::createFromDate((int) $matches[1], (int) $matches[2], (int) $matches[3])->toDateString();
        }

        if (preg_match('/^(\d{1,2})[\/\.](\d{1,2})$/', $raw, $matches)) {
            return Carbon::createFromDate((int) Carbon::today()->year, (int) $matches[1], (int) $matches[2])->toDateString();
        }

        if (preg_match('/(\d{1,2})月(\d{1,2})日/u', $raw, $matches)) {
            return Carbon::createFromDate((int) Carbon::today()->year, (int) $matches[1], (int) $matches[2])->toDateString();
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $e) {
            return Carbon::today()->toDateString();
        }
    }

    private function normalizeReceiptSourceType(mixed $value): string
    {
        $raw = trim((string) $value);

        return match ($raw) {
            '電子取引データ', '電子取引', 'electronic' => 'electronic',
            default => 'paper_scan',
        };
    }

    private function normalizeTransportType(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $normalized = (int) $value;
            return in_array($normalized, [1, 2, 3, 4, 5], true) ? $normalized : null;
        }

        $raw = trim((string) $value);
        return match ($raw) {
            '電車のみ', '電車', 'train' => 1,
            '電車・バス', 'バス', 'bus' => 2,
            'タクシー', 'taxi' => 3,
            '飛行機', 'airplane', 'plane', 'flight' => 4,
            'その他', 'other' => 5,
            default => null,
        };
    }
}
