<?php

namespace App\Services\Faq;

use App\Models\questionAndAnswerRecord;
use App\Models\QuestionAndAnswerVectorDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files as AiFiles;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Store;
use Laravel\Ai\Stores;
use RuntimeException;
use Throwable;

class OpenAiFaqSyncService
{
    public const PROVIDER = 'openai';
    public const STORE_NAME = 'FAQ Store';
    public const STORE_DATA_PATH = 'faq_openai_store/store.json';
    public const STORE_DIRECTORY = 'faq_openai_store';
    public const DOCUMENT_COPY_DIRECTORY = 'faq_openai_store/markdown_copies';
    public const DOCUMENT_DIRECTORY = 'faq_openai_documents';

    private const MAX_REFRESH_ATTEMPTS = 60;
    private const REFRESH_SLEEP_SECONDS = 2;

    public function currentOrNewStore(): Store
    {
        $storeId = $this->previousStoreId();

        if ($storeId) {
            try {
                return Stores::get($storeId, provider: self::PROVIDER);
            } catch (Throwable $exception) {
                Log::warning("OpenAiFaqSyncService: failed to retrieve vector store [{$storeId}], creating a new store: {$exception->getMessage()}");
            }
        }

        $store = Stores::create(self::STORE_NAME, provider: self::PROVIDER);
        $this->writeStoreData([
            'vector_store_id' => $store->id,
            'name' => $store->name,
            'created_at' => now()->toISOString(),
            'synced_at' => null,
        ]);
        Log::info("OpenAiFaqSyncService: created vector store [{$store->id}]");

        return $store;
    }

    public function previousStoreId(): ?string
    {
        if (! Storage::disk('local')->exists(self::STORE_DATA_PATH)) {
            return null;
        }

        $data = json_decode(Storage::disk('local')->get(self::STORE_DATA_PATH), true);

        return $data['vector_store_id'] ?? null;
    }

    public function resetDocumentDirectory(): void
    {
        Storage::disk('local')->deleteDirectory(self::DOCUMENT_DIRECTORY);
        Storage::disk('local')->makeDirectory(self::DOCUMENT_DIRECTORY);
        Storage::disk('local')->makeDirectory(self::STORE_DIRECTORY);
        Storage::disk('local')->makeDirectory(self::DOCUMENT_COPY_DIRECTORY);
    }

    public function syncRecord(questionAndAnswerRecord $record, ?Store $store = null, ?string $copyDirectory = null, bool $force = false): array
    {
        $record->refresh();

        if (! $this->isActiveFaq($record)) {
            $removed = $this->deleteTrackedDocuments($record);
            $this->markRecordNotSynced($record);

            return ['status' => 'not_synced', 'removed_files' => $removed];
        }

        if (! $force && ! $this->needsSync($record)) {
            return ['status' => 'skipped'];
        }

        $this->markRecordSyncing($record);

        try {
            $preparedDocument = $this->prepareRecordDocument(
                $record,
                $copyDirectory ?? $this->newMarkdownCopyDirectory(),
            );

            $store ??= $this->currentOrNewStore();
            $removed = $this->deleteTrackedDocuments($record);
            $uploaded = $this->uploadPreparedDocument($record, $preparedDocument, $store);
            $store = $this->waitForStoreProcessing($store);
            $this->markRecordSynced($record);

            return [
                'status' => 'synced',
                'uploaded_files' => [$uploaded],
                'generated_documents' => [$preparedDocument],
                'removed_files' => $removed,
                'store' => $store,
            ];
        } catch (Throwable $exception) {
            $this->markRecordError($record, $exception->getMessage());
            Log::error("OpenAiFaqSyncService: failed to sync FAQ record [{$record->id}]: {$exception->getMessage()}");

            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function prepareRecordDocument(questionAndAnswerRecord $record, string $copyDirectory): array
    {
        $generatedPath = $this->writeMarkdownDocument($record);
        $copyPath = $this->copyMarkdownDocument($generatedPath, $copyDirectory);

        return [
            'question_and_answer_record_id' => $record->id,
            'question' => $record->question,
            'generated_path' => $generatedPath,
            'copy_path' => $copyPath,
        ];
    }

    public function uploadPreparedDocument(questionAndAnswerRecord $record, array $preparedDocument, Store $store): array
    {
        $upload = $store->add(
            Document::fromStorage($preparedDocument['generated_path'], 'local')->as(basename($preparedDocument['generated_path'])),
            metadata: [
                'question_and_answer_record_id' => $preparedDocument['question_and_answer_record_id'],
                'question' => $preparedDocument['question'],
                'generated_file_name' => basename($preparedDocument['generated_path']),
            ],
        );

        QuestionAndAnswerVectorDocument::create([
            'question_and_answer_record_id' => $record->id,
            'markdown_path' => $preparedDocument['generated_path'],
            'markdown_copy_path' => $preparedDocument['copy_path'],
            'openai_file_id' => $upload->fileId(),
            'vector_store_file_id' => $upload->id(),
        ]);

        return [
            'question_and_answer_record_id' => $preparedDocument['question_and_answer_record_id'],
            'generated_path' => $preparedDocument['generated_path'],
            'copy_path' => $preparedDocument['copy_path'],
            'document_id' => $upload->id(),
            'file_id' => $upload->fileId(),
        ];
    }

    public function clearStoreFiles(string $storeId): array
    {
        $removed = [];
        $after = null;

        do {
            $response = Http::withToken($this->openAiApiKey())
                ->acceptJson()
                ->get("https://api.openai.com/v1/vector_stores/{$storeId}/files", array_filter([
                    'limit' => 100,
                    'after' => $after,
                ]));

            if ($response->failed()) {
                throw new RuntimeException("Failed to list vector store files for [{$storeId}]: ".$response->body());
            }

            foreach ($response->json('data') ?? [] as $item) {
                $fileId = $item['id'] ?? null;

                if ($fileId) {
                    $removed[] = $this->deleteVectorStoreFile($storeId, $fileId, $fileId);
                }
            }

            $after = $response->json('last_id');
        } while ($response->json('has_more') === true && $after);

        return $removed;
    }

    public function deleteTrackedDocuments(questionAndAnswerRecord $record): array
    {
        $storeId = $this->previousStoreId();
        $removed = [];

        foreach ($record->vectorDocuments()->get() as $document) {
            if ($storeId && ($document->vector_store_file_id || $document->openai_file_id)) {
                $removed[] = $this->deleteVectorStoreFile(
                    $storeId,
                    $document->vector_store_file_id ?: $document->openai_file_id,
                    $document->openai_file_id,
                );
            }

            $document->delete();
        }

        return $removed;
    }

    public function markRecordSynced(questionAndAnswerRecord $record): void
    {
        $record->update([
            'ai_sync_status' => questionAndAnswerRecord::AI_SYNC_STATUS_SYNCED,
            'ai_sync_error' => null,
            'ai_synced_at' => now(),
            'ai_sync_hash' => $this->syncHash($record),
        ]);
    }

    public function markRecordError(questionAndAnswerRecord $record, string $message): void
    {
        $record->update([
            'ai_sync_status' => questionAndAnswerRecord::AI_SYNC_STATUS_ERROR,
            'ai_sync_error' => $message,
        ]);
    }

    public function markRecordSyncing(questionAndAnswerRecord $record): void
    {
        $record->update([
            'ai_sync_status' => questionAndAnswerRecord::AI_SYNC_STATUS_SYNCING,
            'ai_sync_error' => null,
        ]);
    }

    public function markRecordNotSynced(questionAndAnswerRecord $record): void
    {
        $record->update([
            'ai_sync_status' => questionAndAnswerRecord::AI_SYNC_STATUS_NOT_SYNCED,
            'ai_sync_error' => null,
            'ai_synced_at' => null,
            'ai_sync_hash' => $this->syncHash($record),
        ]);
    }

    public function writeStoreData(array $data): void
    {
        Storage::disk('local')->put(self::STORE_DATA_PATH, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function newMarkdownCopyDirectory(): string
    {
        return self::DOCUMENT_COPY_DIRECTORY.'/'.now()->format('YmdHis');
    }

    public function markdownFileName(questionAndAnswerRecord $record): string
    {
        return $this->safeFileName($record->question ?: 'faq').'.md';
    }

    public function markdownContent(questionAndAnswerRecord $record): string
    {
        return trim(implode("\n", [
            '# '.$record->question,
            '',
            '- FAQ record ID: '.$record->id,
            '- Tags: '.($record->tag_text ?: ''),
            '',
            '---',
            '',
            '## Question',
            '',
            $record->question,
            '',
            '## Answer',
            '',
            $record->answer,
            '',
            '## Detail',
            '',
            strip_tags((string) $record->content),
        ]))."\n";
    }

    public function syncHash(questionAndAnswerRecord $record): string
    {
        return hash('sha256', implode('|', [
            $record->id,
            $record->question,
            $record->answer,
            $record->content,
            $record->tag_text,
            (string) $record->deleted_flag,
        ]));
    }

    public function needsSync(questionAndAnswerRecord $record): bool
    {
        return $record->ai_sync_status !== questionAndAnswerRecord::AI_SYNC_STATUS_SYNCED
            || $record->ai_sync_hash !== $this->syncHash($record)
            || ! $record->vectorDocuments()->exists();
    }

    public function isActiveFaq(questionAndAnswerRecord $record): bool
    {
        return (int) $record->deleted_flag === 0;
    }

    public function waitForStoreProcessing(Store $store): Store
    {
        for ($attempt = 0; $attempt < self::MAX_REFRESH_ATTEMPTS; $attempt++) {
            $store = $store->refresh();

            if ($store->fileCounts->pending === 0) {
                return $store;
            }

            sleep(self::REFRESH_SLEEP_SECONDS);
        }

        return $store;
    }

    private function writeMarkdownDocument(questionAndAnswerRecord $record): string
    {
        $generatedPath = self::DOCUMENT_DIRECTORY.'/'.$this->markdownFileName($record);

        if (Storage::disk('local')->exists($generatedPath)) {
            $generatedPath = self::DOCUMENT_DIRECTORY.'/'.sprintf(
                '%s__record%s.md',
                $this->safeFileName($record->question ?: 'faq'),
                $record->id,
            );
        }

        Storage::disk('local')->put($generatedPath, $this->markdownContent($record));

        return $generatedPath;
    }

    private function copyMarkdownDocument(string $generatedPath, string $copyDirectory): string
    {
        Storage::disk('local')->makeDirectory($copyDirectory);

        $copyPath = $copyDirectory.'/'.basename($generatedPath);
        Storage::disk('local')->copy($generatedPath, $copyPath);

        return $copyPath;
    }

    private function deleteVectorStoreFile(string $storeId, string $vectorStoreFileId, ?string $openAiFileId): array
    {
        $deleteResponse = Http::withToken($this->openAiApiKey())
            ->acceptJson()
            ->delete("https://api.openai.com/v1/vector_stores/{$storeId}/files/{$vectorStoreFileId}");

        if ($deleteResponse->failed()) {
            Log::error("OpenAiFaqSyncService: failed to remove vector store file [{$vectorStoreFileId}] from [{$storeId}]: ".$deleteResponse->body());

            return [
                'file_id' => $vectorStoreFileId,
                'openai_file_id' => $openAiFileId,
                'deleted' => false,
                'message' => $deleteResponse->body(),
            ];
        }

        if ($openAiFileId) {
            try {
                AiFiles::delete($openAiFileId, provider: self::PROVIDER);
            } catch (Throwable $exception) {
                Log::warning("OpenAiFaqSyncService: removed vector store file [{$vectorStoreFileId}] but failed to delete OpenAI file [{$openAiFileId}]: {$exception->getMessage()}");
            }
        }

        return [
            'file_id' => $vectorStoreFileId,
            'openai_file_id' => $openAiFileId,
            'deleted' => true,
        ];
    }

    private function openAiApiKey(): string
    {
        $apiKey = config('services.openai.api_key') ?: config('ai.providers.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        return $apiKey;
    }

    private function safeFileName(string $value): string
    {
        $value = trim(preg_replace('/[\/\\\\:\*\?"<>\|\x00-\x1F]/u', '_', $value) ?? $value, " \t\n\r\0\x0B.");
        $value = mb_substr($value, 0, 180, 'UTF-8');

        return $value !== '' ? $value : 'faq';
    }
}
