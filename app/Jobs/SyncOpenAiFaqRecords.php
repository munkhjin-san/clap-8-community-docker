<?php

namespace App\Jobs;

use App\Models\questionAndAnswerRecord;
use App\Models\QuestionAndAnswerVectorDocument;
use App\Services\Faq\OpenAiFaqSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncOpenAiFaqRecords implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function handle(OpenAiFaqSyncService $syncService): void
    {
        $syncService->resetDocumentDirectory();

        $records = questionAndAnswerRecord::where('deleted_flag', 0)->get();
        $markdownCopyDirectory = $syncService->newMarkdownCopyDirectory();
        $preparedByRecord = [];
        $generatedDocuments = [];
        $uploadedFiles = [];
        $failedRecords = [];

        if ($records->isEmpty()) {
            $store = $syncService->currentOrNewStore();
            $removedFiles = $syncService->clearStoreFiles($store->id);
            QuestionAndAnswerVectorDocument::query()->delete();
            $store = $syncService->waitForStoreProcessing($store);

            $syncService->writeStoreData([
                'vector_store_id' => $store->id,
                'name' => $store->name,
                'synced_at' => now()->toISOString(),
                'markdown_copy_directory' => $markdownCopyDirectory,
                'source_record_count' => 0,
                'generated_document_count' => 0,
                'uploaded_files' => [],
                'generated_documents' => [],
                'removed_files' => $removedFiles,
                'failed_records' => [],
                'file_counts' => $store->fileCounts->toArray(),
                'ready' => $store->ready,
            ]);

            Log::info("SyncOpenAiFaqRecords: cleared empty FAQ store [{$store->id}]");

            return;
        }

        foreach ($records as $record) {
            $syncService->markRecordSyncing($record);

            try {
                $preparedDocument = $syncService->prepareRecordDocument($record, $markdownCopyDirectory);
                $preparedByRecord[$record->id] = [
                    'record' => $record,
                    'document' => $preparedDocument,
                ];
                $generatedDocuments[] = $preparedDocument;
            } catch (\Throwable $exception) {
                $syncService->markRecordError($record, $exception->getMessage());
                $failedRecords[] = [
                    'question_and_answer_record_id' => $record->id,
                    'message' => $exception->getMessage(),
                ];

                Log::error("SyncOpenAiFaqRecords: failed to prepare FAQ record [{$record->id}]: {$exception->getMessage()}");
            }
        }

        if ($generatedDocuments === []) {
            $syncService->writeStoreData([
                'vector_store_id' => $syncService->previousStoreId(),
                'name' => null,
                'synced_at' => now()->toISOString(),
                'markdown_copy_directory' => $markdownCopyDirectory,
                'source_record_count' => $records->count(),
                'generated_document_count' => 0,
                'uploaded_files' => [],
                'generated_documents' => [],
                'removed_files' => [],
                'failed_records' => $failedRecords,
                'file_counts' => null,
                'ready' => false,
                'status' => 'failed_before_upload',
            ]);

            Log::error('SyncOpenAiFaqRecords: no Markdown documents generated; previous vector store was not cleared.');

            return;
        }

        $store = $syncService->currentOrNewStore();
        $removedFiles = $syncService->clearStoreFiles($store->id);
        QuestionAndAnswerVectorDocument::query()->delete();

        Log::info("SyncOpenAiFaqRecords: syncing vector store [{$store->id}]");

        foreach ($preparedByRecord as $prepared) {
            /** @var questionAndAnswerRecord $record */
            $record = $prepared['record'];

            try {
                $uploadedFiles[] = $syncService->uploadPreparedDocument($record, $prepared['document'], $store);
                $syncService->markRecordSynced($record);
            } catch (\Throwable $exception) {
                $syncService->markRecordError($record, $exception->getMessage());
                $failedRecords[] = [
                    'question_and_answer_record_id' => $record->id,
                    'message' => $exception->getMessage(),
                ];

                Log::error("SyncOpenAiFaqRecords: failed to upload FAQ record [{$record->id}]: {$exception->getMessage()}");
            }
        }

        $store = $syncService->waitForStoreProcessing($store);

        $syncService->writeStoreData([
            'vector_store_id' => $store->id,
            'name' => $store->name,
            'synced_at' => now()->toISOString(),
            'markdown_copy_directory' => $markdownCopyDirectory,
            'source_record_count' => $records->count(),
            'generated_document_count' => count($generatedDocuments),
            'uploaded_files' => $uploadedFiles,
            'generated_documents' => $generatedDocuments,
            'removed_files' => $removedFiles,
            'failed_records' => $failedRecords,
            'file_counts' => $store->fileCounts->toArray(),
            'ready' => $store->ready,
        ]);

        Log::info("SyncOpenAiFaqRecords: sync complete. Store [{$store->id}], records [{$records->count()}]");
    }
}
