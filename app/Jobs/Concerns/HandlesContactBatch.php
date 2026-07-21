<?php

namespace App\Jobs\Concerns;

use App\Models\ContactBatch;
use App\Models\ContactBatchItem;
use App\Models\ContactBatchLog;
use App\Models\ContactRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesContactBatch
{
    protected function logEntry(ContactBatch $batch, string $stage, string $message, array $context = [], ?string $model = null): void
    {
        ContactBatchLog::create([
            'contact_batch_id' => $batch->id,
            'stage' => $stage,
            'model' => $model,
            'message' => $message,
            'context' => $context ?: null,
        ]);
    }

    protected function markBatchFailed(ContactBatch $batch, string $message): void
    {
        $attributes = [
            'status' => ContactBatch::STATUS_FAILED,
            'error' => $message,
        ];

        if ($batch->status === ContactBatch::STATUS_SCANNING && !$batch->scan_completed_at) {
            $attributes['scan_completed_at'] = now();
        }

        if ($batch->status === ContactBatch::STATUS_ENRICHING && !$batch->enrich_completed_at) {
            $attributes['enrich_completed_at'] = now();
        }

        $batch->update($attributes);

        $this->logEntry($batch, 'failed', $message);

        $batch->items()
            ->whereNotIn('status', [ContactBatchItem::STATUS_COMPLETED])
            ->update([
                'status' => ContactBatchItem::STATUS_FAILED,
                'error' => $message,
            ]);

        $this->cleanupBatchDirectory($batch);
    }

    protected function startGeminiBatch(ContactBatch $batch, string $apiKey, string $model, array $payload): array
    {
        $url = config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta';
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post("{$url}/{$model}:batchGenerateContent?key={$apiKey}", $payload);

        if ($response->failed()) {
            $this->logEntry($batch, 'error', 'Failed to start Gemini batch.', ['response' => $response->json()], $model);
            throw new \RuntimeException('Failed to start Gemini batch: ' . $response->body());
        }

        return $response->json();
    }

    protected function pollGeminiOperation(string $apiKey, string $operationName): array
    {
        $url = config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta';
        $response = Http::get("{$url}/{$operationName}?key={$apiKey}");

        if ($response->failed()) {
            throw new \RuntimeException('Failed to poll Gemini batch: ' . $response->body());
        }

        return $response->json();
    }

    protected function applyScanResults(ContactBatch $batch, array $payload): void
    {
        $items = $batch->items()->get()->keyBy('id');
        $responses = data_get($payload, 'response.inlinedResponses.inlinedResponses', []);

        foreach ($responses as $inline) {
            $itemId = data_get($inline, 'metadata.batch_item_id');
            if (!$itemId || !$items->has($itemId)) {
                continue;
            }

            /** @var ContactBatchItem $item */
            $item = $items->get($itemId);

            if (isset($inline['error'])) {
                $message = data_get($inline, 'error.message', 'Unknown error during scan stage.');
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => $message,
                ]);
                continue;
            }

            $rawText = data_get($inline, 'response.candidates.0.content.parts.0.text');

            if (!$rawText) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Scan response did not include text content.',
                ]);
                continue;
            }

            $parsed = $this->decodeJsonText($rawText);

            if ($parsed === null) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Scan response JSON could not be decoded.',
                    'scan_result' => [
                        'raw_text' => $rawText,
                    ],
                ]);
                continue;
            }

            $item->update([
                'status' => ContactBatchItem::STATUS_SCANNED,
                'error' => null,
                'scan_result' => [
                    'raw_text' => $rawText,
                    'parsed' => $this->normalizeParsedContacts($parsed),
                ],
            ]);
        }

        $items->each(function (ContactBatchItem $item) {
            if ($item->status === ContactBatchItem::STATUS_SCANNING) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Scan result was not returned for this item.',
                ]);
            }
        });
    }

    protected function applyEnrichmentResults(ContactBatch $batch, array $payload): void
    {
        $items = $batch->items()->get()->keyBy('id');
        $responses = data_get($payload, 'response.inlinedResponses.inlinedResponses', []);

        foreach ($responses as $inline) {
            $itemId = data_get($inline, 'metadata.batch_item_id');
            if (!$itemId || !$items->has($itemId)) {
                continue;
            }

            /** @var ContactBatchItem $item */
            $item = $items->get($itemId);

            if (isset($inline['error'])) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => data_get($inline, 'error.message', 'Unknown error during enrichment stage.'),
                ]);
                continue;
            }

            $rawText = data_get($inline, 'response.candidates.0.content.parts.0.text');

            if (!$rawText) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Enrichment response did not include text content.',
                ]);
                continue;
            }

            $item->enrich_result = [
                'raw_text' => $rawText,
            ];

            $scanData = Arr::get($item->scan_result, 'parsed.0', []);
            $contactRecord = $this->storeContactRecord($batch, $item, $scanData, $rawText);

            $item->contact_record_id = $contactRecord?->id;
            $item->status = ContactBatchItem::STATUS_COMPLETED;
            $item->error = null;
            $item->save();
        }

        $items->each(function (ContactBatchItem $item) {
            if ($item->status === ContactBatchItem::STATUS_ENRICHING) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Enrichment result was not returned for this item.',
                ]);
            }
        });
    }

    protected function storeContactRecord(ContactBatch $batch, ContactBatchItem $item, array $cardData, string $markdown): ?ContactRecord
    {
        $this->logEntry($batch, 'store_contact_start', 'Preparing to store contact record.', [
            'item_id' => $item->id,
            'stored_path' => $item->stored_path,
            'existing_record_id' => $item->contact_record_id,
        ]);

        $normalized = $this->normalizeContactEntry($cardData);
        $existingRecord = $item->contact_record_id ? ContactRecord::find($item->contact_record_id) : null;

        $normalized['card_hash'] = $this->computeCardHash($item, $existingRecord?->card_hash);
        $duplicates = $this->findDuplicateContacts($normalized, $existingRecord?->id);

        $this->logEntry($batch, 'duplicate_check', 'Duplicate candidates evaluated.', [
            'item_id' => $item->id,
            'card_hash' => $normalized['card_hash'],
            'duplicates' => $duplicates,
        ]);

        $item->card_hash = $normalized['card_hash'];
        $item->duplicate_candidates = $duplicates;
        $item->needs_review = !empty($duplicates);
        $item->save();

        $cardPath = $this->promoteCardImage($batch, $item, $existingRecord?->card_path);

        $html = Str::markdown($markdown);

        $attributes = [
            'name' => $normalized['name'] ?? null,
            'company_name' => $normalized['company_name'] ?? null,
            'department' => $normalized['department'] ?? null,
            'position' => $normalized['position'] ?? null,
            'address' => $normalized['address'] ?? null,
            'phone' => $normalized['phone'] ?? null,
            'email' => $normalized['email'] ?? null,
            'fax' => $normalized['fax'] ?? null,
            'url' => $normalized['url'] ?? null,
            // Leave description (メモ) empty — it is a user field, not a place for
            // system markers like "名刺画像から自動登録".
            'description' => null,
            'data' => $html,
            'enrichment_status' => !empty($html) ? 'completed' : null,
            'contact_type_id' => $batch->contact_type_id,
            'strategy' => null,
            'card_path' => $cardPath,
            'card_hash' => $normalized['card_hash'] ?? null,
            'is_duplicate' => !empty($duplicates),
            'duplicate_of' => $duplicates[0]['id'] ?? null,
        ];
        if ($existingRecord) {
            $existingRecord->fill($attributes);
            if ($batch->user_id) {
                $existingRecord->updated_by = $batch->user_id;
            }
            $existingRecord->save();

            $this->syncCollaborator($existingRecord, $batch->user_id);
            $this->syncBatchTypes($existingRecord, $batch);

            return $existingRecord;
        }

        $record = ContactRecord::create(array_merge($attributes, [
            'created_by' => $batch->user_id,
            'updated_by' => $batch->user_id,
        ]));

        $this->syncCollaborator($record, $batch->user_id);
        $this->syncBatchTypes($record, $batch);

        return $record;
    }

    protected function syncBatchTypes(ContactRecord $record, ContactBatch $batch): void
    {
        $typeIds = $batch->type_ids ?: ($batch->contact_type_id ? [$batch->contact_type_id] : []);
        if (!empty($typeIds)) {
            $record->types()->syncWithoutDetaching($typeIds);
        }
    }

    protected function computeCardHash(ContactBatchItem $item, ?string $existingHash = null): ?string
    {
        if ($existingHash) {
            return $existingHash;
        }

        $path = $item->stored_path;
        if (!$path || !Storage::disk('local')->exists($path)) {
            return null;
        }

        return hash_file('sha256', Storage::disk('local')->path($path));
    }

    protected function findDuplicateContacts(array $normalized, ?int $currentId = null): array
    {
        $query = ContactRecord::query();

        if (!empty($normalized['card_hash'])) {
            $query->where('card_hash', $normalized['card_hash']);
        } elseif (!empty($normalized['name']) && !empty($normalized['company_name'])) {
            $query->where(function ($q) use ($normalized) {
                $q->where('name', $normalized['name'])
                    ->where('company_name', $normalized['company_name']);

                if (!empty($normalized['email'])) {
                    $q->where('email', $normalized['email']);
                }
            });
        } else {
            return [];
        }

        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }

        return $query->limit(5)->get(['id', 'name', 'company_name', 'email', 'card_hash'])
            ->map(fn ($contact) => $contact->toArray())
            ->all();
    }

    protected function syncCollaborator(ContactRecord $record, ?int $userId): void
    {
        if (!$userId) {
            return;
        }

        $record->collaborators()->syncWithoutDetaching([$userId => ['role' => 'owner']]);
    }

    protected function decodeJsonText(string $text): ?array
    {
        $clean = trim($text);
        $clean = preg_replace('/^json\s+/i', '', $clean);
        $clean = preg_replace('/^```json\s*/', '', $clean);
        $clean = preg_replace('/```$/', '', $clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    protected function enrichmentInstruction(array $cardData): string
    {
        $name = $cardData['company_name'] ?? '';
        $url = $cardData['url'] ?? '';
        $address = $cardData['address'] ?? '';

        return <<<EOD
            会会社情報:
            会社名: $name
            ホームページのURL: $url
            住所: $address

            上記の企業情報を利用し、会社名や会社のホームページを利用して情報をを取得し、各カテゴリを整理してください。情報はユーザーが企業の基本情報や最新の動向を素早く把握できるよう、簡潔かつ直感的に表示できる形式で提供してください。

            出力形式

            Markdown形式で順番とサブテキスト形を守り、データを取得・整理してください。
            各カラムはわかりやすい見出しに統一し、重複を避けてください。
            カラムの情報が不明や取得出来なかった場合はカラムを消してください。
            情報のソースURLを付記することで、情報の信頼性を担保してください。
            情報が不明なカラムをレスポンスに入れないでください。
            レスポンス例:

            - **基本情報**
                1. 会社名 : {{company_name}}
                2. ロゴ（画像URL） : {{logo_url}}
                ...
            - **事業概要**
            ...

            ---

            注意事項
            1. 情報は必ず、会社名でwebから取得します。名称情報から適当に作成しません
            2. 取得した情報は簡潔かつ正確にまとめてください。
            3. 不明な情報や取得できなかった場合はカラムはいりません。
            4. カテゴリごとに情報を整理し、ユーザーが即座に理解できるようにしてください。
            5. 機密性の高い情報（例: 非公開情報）は取得対象から除外してください。

            取得する情報のカテゴリとカラム
            ※情報が不明な場合カラムを削除してください。
            1. 基本情報
                会社名
                ロゴ（画像URLまたはホームページのfavicon URL大きめの）
                所在地（本社住所、支店情報）
                設立年月日
                代表者名
                従業員数
                資本金
                売上高
                株式情報（上場/未上場、証券コード）
            2. 事業概要
                事業内容（簡潔な概要）
                主な製品・サービス（リスト形式）
                業種分類（例: IT、製造、飲食）
                顧客層（例: 法人向け、個人向け）
                主な取引先
            3. 事業戦略
                ミッション・ビジョン（企業理念や目標）
                戦略目標（例: SDGs、DX推進）
                競争優位性（例: 特許、技術力、ブランド力）
                現在進行中のプロジェクトや取り組み
            4. 最新情報
                最新ニュース（プレスリリース、イベント情報）
                受賞歴や認定（例: ISO認証、業界賞）
                提携・コラボ情報（他社との協業内容）
                株主や取引先の動向
            5. 財務情報
                年度別業績（売上、利益など）
                成長率
                資金調達の履歴や状況
            6. 人事情報
                採用情報
                福利厚生の特徴
                求める人物像
            7. ウェブ・SNS情報
                公式サイトのURL
                SNSアカウント情報（LinkedIn, Twitter, Facebookなど）
                問い合わせ窓口（メールアドレス、電話番号）
            8. その他
                CSR活動（社会貢献活動の内容）
                サステナビリティ情報
                特許や認定技術の詳細
            ---
            ---
        EOD;
    }

    protected function buildScanRequests(ContactBatch $batch, string $instruction, array $generationConfig): array
    {
        $requests = [];
        $storageDisk = Storage::disk('local');

        foreach ($batch->items as $item) {
            $path = $item->stored_path;
            $absolutePath = $storageDisk->path($path);

            if (!is_readable($absolutePath)) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Stored file could not be read.',
                ]);
                continue;
            }

            $mime = mime_content_type($absolutePath) ?: 'image/jpeg';
            $base64 = base64_encode(file_get_contents($absolutePath));

            $requests[] = [
                'request' => [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $instruction],
                                [
                                    'inlineData' => [
                                        'mimeType' => $mime,
                                        'data' => $base64,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => $generationConfig,
                ],
                'metadata' => [
                    'batch_item_id' => $item->id,
                    'index' => $item->index,
                    'original_filename' => $item->original_filename,
                ],
            ];

            $item->update([
                'status' => ContactBatchItem::STATUS_SCANNING,
                'error' => null,
            ]);
        }

        return $requests;
    }

    protected function buildEnrichmentRequests(ContactBatch $batch): array
    {
        $requests = [];
        $items = $batch->items()->where('status', ContactBatchItem::STATUS_SCANNED)->get();

        foreach ($items as $item) {
            $scanResult = $item->scan_result ?? [];
            $parsed = Arr::get($scanResult, 'parsed.0', []);

            if (empty($parsed)) {
                $item->update([
                    'status' => ContactBatchItem::STATUS_FAILED,
                    'error' => 'Scan result was empty or invalid.',
                ]);
                continue;
            }

            $instruction = $this->enrichmentInstruction($parsed);

            $requests[] = [
                'request' => [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $instruction],
                            ],
                        ],
                    ],
                    'tools' => [
                        [
                            'google_search' => (object)[],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.0,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 8192,
                        'responseMimeType' => 'text/plain',
                    ],
                ],
                'metadata' => [
                    'batch_item_id' => $item->id,
                    'index' => $item->index,
                    'original_filename' => $item->original_filename,
                ],
            ];

            $item->update([
                'status' => ContactBatchItem::STATUS_ENRICHING,
                'error' => null,
            ]);
        }

        return $requests;
    }

    protected function normalizeParsedContacts(array $parsed): array
    {
        $normalized = [];
        $isList = array_keys($parsed) === range(0, count($parsed) - 1);

        if ($isList) {
            foreach ($parsed as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $normalized[] = $this->normalizeContactEntry($entry);
            }
        } else {
            if (is_array($parsed)) {
                $normalized[] = $this->normalizeContactEntry($parsed);
            }
        }

        return array_values(array_filter($normalized));
    }

    protected function normalizeContactEntry(array $entry): array
    {
        if (isset($entry['url'])) {
            $entry['url'] = $this->sanitizeUrl($entry['url']);
        }

        if (isset($entry['email']) && is_string($entry['email'])) {
            $entry['email'] = mb_strtolower(trim($entry['email']));
        }

        return $entry;
    }

    protected function sanitizeUrl($url): string
    {
        if (!is_string($url)) {
            return '';
        }

        $trimmed = trim($url);
        if ($trimmed === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $trimmed)) {
            $trimmed = 'https://' . ltrim($trimmed, '/');
        }

        $parts = parse_url($trimmed);
        if ($parts === false || empty($parts['host'])) {
            return Str::limit($trimmed, 255, '');
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        if (!in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'https';
        }

        $host = $parts['host'];
        $path = $parts['path'] ?? '';
        $queryString = '';

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
            foreach ($params as $key => $value) {
                if ($key === 'redirect_from') {
                    unset($params[$key]);
                    continue;
                }
                if (str_starts_with($key, 'utm_')) {
                    unset($params[$key]);
                    continue;
                }
                if (in_array($key, ['gclid', 'fbclid', 'yclid', 'mc_cid', 'mc_eid'], true)) {
                    unset($params[$key]);
                    continue;
                }
                if (is_array($value)) {
                    $params[$key] = reset($value);
                }
            }
            if (!empty($params)) {
                $queryString = http_build_query($params);
            }
        }

        $fragment = $parts['fragment'] ?? null;

        $cleanUrl = $scheme . '://' . $host . $path;
        if ($queryString !== '') {
            $cleanUrl .= '?' . $queryString;
        }
        if ($fragment) {
            $cleanUrl .= '#' . $fragment;
        }

        return Str::limit($cleanUrl, 255, '');
    }

    protected function promoteCardImage(ContactBatch $batch, ContactBatchItem $item, ?string $currentPath = null): ?string
    {
        $this->logEntry($batch, 'promote_image_start', 'Attempting to promote card image.', [
            'item_id' => $item->id,
            'stored_path' => $item->stored_path,
            'existing_card_path' => $currentPath,
        ]);

        if ($currentPath) {
            $this->logEntry($batch, 'promote_image_skip', 'Existing card image found; skipping.', [
                'item_id' => $item->id,
                'card_path' => $currentPath,
            ]);
            return $currentPath;
        }

        $source = $item->stored_path;
        if (!$source) {
            $this->logEntry($batch, 'promote_image_missing_source', 'No stored_path on batch item.', [
                'item_id' => $item->id,
            ]);
            return null;
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($source)) {
            $this->logEntry($batch, 'promote_image_not_found', 'Stored image not found on disk.', [
                'item_id' => $item->id,
                'source' => $source,
            ]);
            return null;
        }

        $targetDir = 'card_files/' . now()->format('Y/m');
        if (!$disk->exists($targetDir) && !$disk->makeDirectory($targetDir)) {
            $this->logEntry($batch, 'promote_image_dir_fail', 'Failed to create target directory.', [
                'item_id' => $item->id,
                'target_dir' => $targetDir,
            ]);
            return null;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'webp';
        $target = $targetDir . '/' . Str::uuid()->toString() . '.' . $extension;

        if (!$disk->move($source, $target)) {
            $this->logEntry($batch, 'promote_image_move_fail', 'Failed to move image to final destination.', [
                'item_id' => $item->id,
                'source' => $source,
                'target' => $target,
            ]);
            return null;
        }

        $item->stored_path = $target;
        $item->save();

        $this->logEntry($batch, 'promote_image_success', 'Card image promoted successfully.', [
            'item_id' => $item->id,
            'target' => $target,
        ]);

        return $target;
    }

    protected function cleanupBatchDirectory(ContactBatch $batch): void
    {
        $path = 'contact_batches/' . $batch->id;
        $disk = Storage::disk('local');

        if ($disk->exists($path)) {
            $disk->deleteDirectory($path);
        }
    }
}
