<?php

namespace App\Jobs;

use App\Models\RegulationFile;
use App\Models\RegulationFileVectorPage;
use App\Services\Contracts\ContractExtractionService;
use App\Services\Regulations\OpenAiRegulationSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncOpenAiRegulationPages implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function handle(
        ContractExtractionService $contractExtractionService,
        OpenAiRegulationSyncService $syncService,
    ): void {
        $syncService->resetPageDirectory();

        $files = RegulationFile::where('chat_supported', 1)
            ->where('extension', 'pdf')
            ->get();

        $markdownCopyDirectory = $syncService->newMarkdownCopyDirectory();
        $preparedByFile = [];
        $generatedPages = [];
        $uploadedFiles = [];
        $skippedFiles = [];
        $failedFiles = [];

        foreach ($files as $file) {
            $syncService->markFileSyncing($file);

            try {
                $preparedPages = $syncService->prepareFilePages($file, $contractExtractionService, $markdownCopyDirectory);
                $preparedByFile[$file->id] = [
                    'file' => $file,
                    'pages' => $preparedPages,
                ];
                $generatedPages = array_merge($generatedPages, $preparedPages);
            } catch (\Throwable $exception) {
                $syncService->markFileError($file, $exception->getMessage());
                $failedFiles[] = [
                    'regulation_file_id' => $file->id,
                    'path' => $syncService->sourcePath($file),
                    'message' => $exception->getMessage(),
                ];

                Log::error("SyncOpenAiRegulationPages: failed to prepare [{$syncService->sourcePath($file)}]: {$exception->getMessage()}");
            }
        }

        if ($generatedPages === []) {
            $syncService->writeStoreData([
                'vector_store_id' => $syncService->previousStoreId(),
                'name' => null,
                'synced_at' => now()->toISOString(),
                'markdown_copy_directory' => $markdownCopyDirectory,
                'source_file_count' => $files->count(),
                'generated_page_count' => 0,
                'uploaded_files' => [],
                'generated_pages' => [],
                'removed_files' => [],
                'skipped_files' => $skippedFiles,
                'failed_files' => $failedFiles,
                'file_counts' => null,
                'ready' => false,
                'status' => 'failed_before_upload',
            ]);

            Log::error('SyncOpenAiRegulationPages: no Markdown pages generated; previous vector store was not cleared.');

            return;
        }

        $store = $syncService->currentOrNewStore();
        $removedFiles = $syncService->clearStoreFiles($store->id);
        RegulationFileVectorPage::query()->delete();

        Log::info("SyncOpenAiRegulationPages: syncing vector store [{$store->id}]");

        foreach ($preparedByFile as $prepared) {
            /** @var RegulationFile $file */
            $file = $prepared['file'];

            try {
                $uploaded = $syncService->uploadPreparedPages($file, $prepared['pages'], $store);
                $syncService->markFileSynced($file);
                $uploadedFiles = array_merge($uploadedFiles, $uploaded);
            } catch (\Throwable $exception) {
                $syncService->markFileError($file, $exception->getMessage());
                $failedFiles[] = [
                    'regulation_file_id' => $file->id,
                    'path' => $syncService->sourcePath($file),
                    'message' => $exception->getMessage(),
                ];

                Log::error("SyncOpenAiRegulationPages: failed to upload [{$syncService->sourcePath($file)}]: {$exception->getMessage()}");
            }
        }

        $store = $syncService->waitForStoreProcessing($store);

        $syncService->writeStoreData([
            'vector_store_id' => $store->id,
            'name' => $store->name,
            'synced_at' => now()->toISOString(),
            'markdown_copy_directory' => $markdownCopyDirectory,
            'source_file_count' => $files->count(),
            'generated_page_count' => count($generatedPages),
            'uploaded_files' => $uploadedFiles,
            'generated_pages' => $generatedPages,
            'removed_files' => $removedFiles,
            'skipped_files' => $skippedFiles,
            'failed_files' => $failedFiles,
            'file_counts' => $store->fileCounts->toArray(),
            'ready' => $store->ready,
        ]);

        $syncService->pruneOrphanedMarkdownCopies();

        Log::info("SyncOpenAiRegulationPages: sync complete. Store [{$store->id}], files [{$files->count()}], pages [".count($generatedPages).']');
    }

    public function markdownFileName(RegulationFile $file, int $page): string
    {
        return app(OpenAiRegulationSyncService::class)->markdownFileName($file, $page);
    }

    public function markdownContent(RegulationFile $file, array $page, string $sourcePath): string
    {
        return app(OpenAiRegulationSyncService::class)->markdownContent($file, $page, $sourcePath);
    }
}
