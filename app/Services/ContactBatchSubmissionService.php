<?php

namespace App\Services;

use App\Jobs\Concerns\HandlesContactBatch;
use App\Models\ContactBatch;

class ContactBatchSubmissionService
{
    use HandlesContactBatch;

    public function submit(ContactBatch $batch): void
    {
        $batch->loadMissing('items');

        if ($batch->status !== ContactBatch::STATUS_QUEUED) {
            return;
        }

        $apiKey = config('services.google.gemini_api_key');
        if (empty($apiKey)) {
            $this->markBatchFailed($batch, 'Gemini API key is not configured.');
            return;
        }

        if ($batch->items->isEmpty()) {
            $this->markBatchFailed($batch, 'No batch items found.');
            return;
        }

        $this->submitScan($batch, $apiKey);
    }

    protected function submitScan(ContactBatch $batch, string $apiKey): void
    {
        $instruction = $this->scanPrompt();

        $generationConfig = [
            'responseMimeType' => 'application/json',
            'responseSchema' => [
                'type' => 'OBJECT',
                'required' => ['company_name', 'name', 'position', 'address', 'phone', 'email', 'fax', 'url'],
                'properties' => [
                    'company_name' => ['type' => 'STRING'],
                    'name' => ['type' => 'STRING'],
                    'position' => ['type' => 'STRING'],
                    'address' => ['type' => 'STRING'],
                    'phone' => ['type' => 'STRING'],
                    'email' => ['type' => 'STRING'],
                    'fax' => ['type' => 'STRING'],
                    'url' => ['type' => 'STRING'],
                ],
            ],
            'temperature' => 0,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 8192,
        ];

        $requests = $this->buildScanRequests($batch, $instruction, $generationConfig);

        if (empty($requests)) {
            $this->markBatchFailed($batch, 'No valid batch requests could be created.');
            return;
        }

        $payload = [
            'batch' => [
                'displayName' => 'contact-scan-' . now()->format('YmdHis'),
                'model' => 'models/gemini-3-flash-preview',
                'inputConfig' => [
                    'requests' => [
                        'requests' => $requests,
                    ],
                ],
            ],
        ];

        $this->logEntry($batch, 'scan_submit', 'Submitting scan batch.', ['request_count' => count($requests)], 'models/gemini-3-flash-preview');

        $operation = $this->startGeminiBatch($batch, $apiKey, 'models/gemini-3-flash-preview', $payload);

        $batch->update([
            'status' => ContactBatch::STATUS_SCANNING,
            'scan_operation' => $operation['name'] ?? null,
            'scan_attempts' => 0,
            'scan_requested_at' => now(),
            'scan_completed_at' => null,
            'error' => null,
        ]);
    }

    protected function scanPrompt(): string
    {
        return <<<EOD
            あなたは厳密な名刺OCR抽出器です。添付された名刺画像だけを見て、連絡先情報を抽出してください。

            返却はJSONオブジェクトのみです。説明文やMarkdownは出力しないでください。

            {
              "name": "氏名",
              "company_name": "会社名",
              "position": "役職",
              "address": "住所",
              "phone": "電話番号",
              "email": "メールアドレス",
              "fax": "FAX",
              "url": "ホームページURL"
            }

            規則:
            - 抽出元は添付画像のみです。推測・補完・創作は禁止です。
            - すべての値は文字列にしてください。
            - 見つからない項目は空文字 "" にしてください。
            - 電話番号、FAX、メール、URLは画像に見える内容を優先して返してください。
            - 役職や部署が複数行にまたがる場合は、自然な1つの文字列にまとめてください。
            - 住所は郵便番号を含め、同じ住所情報として自然な1つの文字列にまとめてください。
            - メールアドレスは小文字で返してください。
            - 会社名、氏名、役職、住所、電話番号、FAX、URLを取り違えないでください。
        EOD;
    }
}
