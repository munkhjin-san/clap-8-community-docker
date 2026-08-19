<?php

namespace App\Services\Regulations;

use App\Models\RegulationFile;
use App\Models\RegulationFileVectorPage;
use App\Services\Contracts\ContractExtractionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files as AiFiles;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Store;
use Laravel\Ai\Stores;
use RuntimeException;
use Throwable;

class OpenAiRegulationSyncService
{
    public const PROVIDER = 'openai';
    public const STORE_NAME = 'Regulation Page Markdown Store';
    public const STORE_DATA_PATH = 'regulation_openai_store/store.json';
    public const STORE_DIRECTORY = 'regulation_openai_store';
    public const PAGE_COPY_DIRECTORY = 'regulation_openai_store/markdown_copies';
    public const PAGE_DIRECTORY = 'regulation_openai_pages';
    public const SOURCE_DIRECTORY = 'regulation_files';

    private const MAX_REFRESH_ATTEMPTS = 60;
    private const REFRESH_SLEEP_SECONDS = 2;

    public function currentOrNewStore(): Store
    {
        $storeId = $this->previousStoreId();

        if ($storeId) {
            try {
                return Stores::get($storeId, provider: self::PROVIDER);
            } catch (Throwable $exception) {
                Log::warning("OpenAiRegulationSyncService: failed to retrieve vector store [{$storeId}], creating a new store: {$exception->getMessage()}");
            }
        }

        $store = Stores::create(self::STORE_NAME, provider: self::PROVIDER);
        $this->writeStoreData([
            'vector_store_id' => $store->id,
            'name' => $store->name,
            'created_at' => now()->toISOString(),
            'synced_at' => null,
        ]);
        Log::info("OpenAiRegulationSyncService: created vector store [{$store->id}]");

        return $store;
    }

    public function previousStoreId(): ?string
    {
        return $this->storeData()['vector_store_id'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function storeData(): array
    {
        if (! Storage::disk('local')->exists(self::STORE_DATA_PATH)) {
            return [];
        }

        $data = json_decode(Storage::disk('local')->get(self::STORE_DATA_PATH), true);

        return is_array($data) ? $data : [];
    }

    public function resetPageDirectory(): void
    {
        Storage::disk('local')->deleteDirectory(self::PAGE_DIRECTORY);
        Storage::disk('local')->makeDirectory(self::PAGE_DIRECTORY);
        Storage::disk('local')->makeDirectory(self::STORE_DIRECTORY);
        Storage::disk('local')->makeDirectory(self::PAGE_COPY_DIRECTORY);
    }

    /**
     * Discard only the generated Markdown owned by one file, so a single-file
     * sync leaves the pages belonging to every other regulation file intact.
     */
    public function resetGeneratedPagesForFile(RegulationFile $file): void
    {
        $disk = Storage::disk('local');

        $disk->makeDirectory(self::PAGE_DIRECTORY);
        $disk->makeDirectory(self::STORE_DIRECTORY);
        $disk->makeDirectory(self::PAGE_COPY_DIRECTORY);

        foreach ($file->vectorPages()->pluck('markdown_path') as $path) {
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    public function syncFile(
        RegulationFile $file,
        ContractExtractionService $contractExtractionService,
        ?Store $store = null,
        ?string $copyDirectory = null,
        bool $force = false,
    ): array {
        $file->refresh();

        if (! $this->isSupportedPdf($file)) {
            $removed = $this->deleteTrackedPages($file);
            $file->update([
                'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_NOT_SYNCED,
                'ai_sync_error' => null,
                'ai_synced_at' => null,
                'ai_sync_hash' => $this->syncHash($file),
            ]);

            return ['status' => 'not_synced', 'removed_files' => $removed];
        }

        if (! $force && ! $this->needsSync($file)) {
            return ['status' => 'skipped'];
        }

        $file->update([
            'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_SYNCING,
            'ai_sync_error' => null,
        ]);

        try {
            $preparedPages = $this->prepareFilePages(
                $file,
                $contractExtractionService,
                $copyDirectory ?? $this->newMarkdownCopyDirectory(),
            );

            if ($preparedPages === []) {
                throw new RuntimeException('No extractable PDF pages were found.');
            }

            $store ??= $this->currentOrNewStore();
            $removed = $this->deleteTrackedPages($file);
            $uploaded = $this->uploadPreparedPages($file, $preparedPages, $store);
            $store = $this->waitForStoreProcessing($store);

            $file->update([
                'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_SYNCED,
                'ai_sync_error' => null,
                'ai_synced_at' => now(),
                'ai_sync_hash' => $this->syncHash($file),
            ]);

            return [
                'status' => 'synced',
                'uploaded_files' => $uploaded,
                'generated_pages' => $preparedPages,
                'removed_files' => $removed,
                'store' => $store,
            ];
        } catch (Throwable $exception) {
            $file->update([
                'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_ERROR,
                'ai_sync_error' => $exception->getMessage(),
            ]);

            Log::error("OpenAiRegulationSyncService: failed to sync regulation file [{$file->id}]: {$exception->getMessage()}");

            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function prepareFilePages(
        RegulationFile $file,
        ContractExtractionService $contractExtractionService,
        string $copyDirectory,
    ): array {
        $sourcePath = $this->sourcePath($file);

        if (! Storage::disk('local')->exists($sourcePath)) {
            throw new RuntimeException("Source file not found in storage [{$sourcePath}].");
        }

        $pages = $contractExtractionService->extractPdfPages(Storage::disk('local')->path($sourcePath));
        $prepared = [];

        foreach ($pages as $page) {
            $generatedPath = $this->writeMarkdownPage($file, $page, $sourcePath);
            $copyPath = $this->copyMarkdownPage($generatedPath, $copyDirectory);

            $prepared[] = [
                'regulation_file_id' => $file->id,
                'original_file_name' => $file->name,
                'source_path' => $sourcePath,
                'generated_path' => $generatedPath,
                'copy_path' => $copyPath,
                'page' => (int) ($page['page'] ?? 1),
            ];
        }

        return $prepared;
    }

    public function uploadPreparedPages(RegulationFile $file, array $preparedPages, Store $store): array
    {
        $uploaded = [];

        foreach ($preparedPages as $preparedPage) {
            $upload = $store->add(
                Document::fromStorage($preparedPage['generated_path'], 'local')->as(basename($preparedPage['generated_path'])),
                metadata: [
                    'regulation_file_id' => $preparedPage['regulation_file_id'],
                    'original_file_name' => $preparedPage['original_file_name'],
                    'source_path' => $preparedPage['source_path'],
                    'generated_file_name' => basename($preparedPage['generated_path']),
                    'page' => $preparedPage['page'],
                ],
            );

            RegulationFileVectorPage::create([
                'regulation_file_id' => $file->id,
                'page_number' => $preparedPage['page'],
                'markdown_path' => $preparedPage['generated_path'],
                'markdown_copy_path' => $preparedPage['copy_path'],
                'openai_file_id' => $upload->fileId(),
                'vector_store_file_id' => $upload->id(),
            ]);

            $uploaded[] = [
                'regulation_file_id' => $preparedPage['regulation_file_id'],
                'source_path' => $preparedPage['source_path'],
                'generated_path' => $preparedPage['generated_path'],
                'copy_path' => $preparedPage['copy_path'],
                'document_id' => $upload->id(),
                'file_id' => $upload->fileId(),
                'page' => $preparedPage['page'],
            ];
        }

        return $uploaded;
    }

    public function markFileSynced(RegulationFile $file): void
    {
        $file->update([
            'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_SYNCED,
            'ai_sync_error' => null,
            'ai_synced_at' => now(),
            'ai_sync_hash' => $this->syncHash($file),
        ]);
    }

    public function markFileError(RegulationFile $file, string $message): void
    {
        $file->update([
            'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_ERROR,
            'ai_sync_error' => $message,
        ]);
    }

    public function markFileSyncing(RegulationFile $file): void
    {
        $file->update([
            'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_SYNCING,
            'ai_sync_error' => null,
        ]);
    }

    public function markFileNotSynced(RegulationFile $file): void
    {
        $file->update([
            'ai_sync_status' => RegulationFile::AI_SYNC_STATUS_NOT_SYNCED,
            'ai_sync_error' => null,
            'ai_synced_at' => null,
            'ai_sync_hash' => $this->syncHash($file),
        ]);
    }

    public function clearStoreFiles(string $storeId): array
    {
        $apiKey = $this->openAiApiKey();
        $removed = [];
        $after = null;

        do {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->get("https://api.openai.com/v1/vector_stores/{$storeId}/files", array_filter([
                    'limit' => 100,
                    'after' => $after,
                ]));

            if ($response->failed()) {
                throw new RuntimeException("Failed to list vector store files for [{$storeId}]: ".$response->body());
            }

            $items = $response->json('data') ?? [];

            foreach ($items as $item) {
                $fileId = $item['id'] ?? null;

                if (! $fileId) {
                    continue;
                }

                $removed[] = $this->deleteVectorStoreFile($storeId, $fileId, $fileId);
            }

            $after = $response->json('last_id');
        } while ($response->json('has_more') === true && $after);

        Log::info("OpenAiRegulationSyncService: removed [".count(array_filter($removed, fn ($item) => $item['deleted'] ?? false))."] files from vector store [{$storeId}]");

        return $removed;
    }

    public function deleteTrackedPages(RegulationFile $file): array
    {
        $storeId = $this->previousStoreId();
        $removed = [];

        foreach ($file->vectorPages()->get() as $page) {
            if ($storeId && ($page->vector_store_file_id || $page->openai_file_id)) {
                $removed[] = $this->deleteVectorStoreFile(
                    $storeId,
                    $page->vector_store_file_id ?: $page->openai_file_id,
                    $page->openai_file_id,
                );
            }

            $page->delete();
        }

        return $removed;
    }

    public function writeStoreData(array $data): void
    {
        Storage::disk('local')->put(self::STORE_DATA_PATH, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Rebuild store.json from the tracked vector pages.
     *
     * Single-file syncs only touch one regulation file, so the store data has
     * to be derived from the tracking table instead of a single run's results.
     * Copies may legitimately span several timestamped directories.
     */
    public function rebuildStoreData(?Store $store = null): void
    {
        $existing = $this->storeData();

        $pages = RegulationFileVectorPage::query()
            ->with('regulationFile')
            ->orderBy('regulation_file_id')
            ->orderBy('page_number')
            ->get();

        $uploadedFiles = $pages->map(fn (RegulationFileVectorPage $page) => [
            'regulation_file_id' => $page->regulation_file_id,
            'source_path' => $page->regulationFile ? $this->sourcePath($page->regulationFile) : null,
            'generated_path' => $page->markdown_path,
            'copy_path' => $page->markdown_copy_path,
            'document_id' => $page->vector_store_file_id,
            'file_id' => $page->openai_file_id,
            'page' => $page->page_number,
        ])->all();

        $copyDirectories = $pages
            ->pluck('markdown_copy_path')
            ->filter()
            ->map(fn (string $path) => dirname($path))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->writeStoreData([
            'vector_store_id' => $store?->id ?? ($existing['vector_store_id'] ?? null),
            'name' => $store?->name ?? ($existing['name'] ?? self::STORE_NAME),
            'synced_at' => now()->toISOString(),
            'markdown_copy_directory' => $copyDirectories === [] ? null : end($copyDirectories),
            'markdown_copy_directories' => $copyDirectories,
            'source_file_count' => $pages->pluck('regulation_file_id')->unique()->count(),
            'generated_page_count' => $pages->count(),
            'uploaded_files' => $uploadedFiles,
            'file_counts' => $store?->fileCounts->toArray() ?? ($existing['file_counts'] ?? null),
            'ready' => $store?->ready ?? ($existing['ready'] ?? null),
        ]);
    }

    /**
     * Delete Markdown copies that no longer back a tracked vector store page.
     *
     * @return list<string>
     */
    public function pruneOrphanedMarkdownCopies(): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::PAGE_COPY_DIRECTORY)) {
            return [];
        }

        $livePaths = RegulationFileVectorPage::query()
            ->whereNotNull('markdown_copy_path')
            ->pluck('markdown_copy_path')
            ->all();

        // With no tracked pages every copy looks orphaned; keep them rather
        // than wiping the directory on an empty or half-migrated table.
        if ($livePaths === []) {
            return [];
        }

        $live = array_flip($livePaths);
        $removed = [];

        foreach ($disk->allFiles(self::PAGE_COPY_DIRECTORY) as $path) {
            if (isset($live[$path])) {
                continue;
            }

            $disk->delete($path);
            $removed[] = $path;
        }

        foreach ($disk->directories(self::PAGE_COPY_DIRECTORY) as $directory) {
            if ($disk->allFiles($directory) === []) {
                $disk->deleteDirectory($directory);
            }
        }

        if ($removed !== []) {
            Log::info('OpenAiRegulationSyncService: pruned ['.count($removed).'] orphaned Markdown copies.');
        }

        return $removed;
    }

    public function newMarkdownCopyDirectory(): string
    {
        return self::PAGE_COPY_DIRECTORY.'/'.now()->format('YmdHis');
    }

    public function sourcePath(RegulationFile $file): string
    {
        return self::SOURCE_DIRECTORY."/{$file->path}.{$file->extension}";
    }

    public function markdownFileName(RegulationFile $file, int $page): string
    {
        $baseName = pathinfo($file->name, PATHINFO_FILENAME) ?: $file->path;
        $baseName = $this->safeFileName($baseName);

        return sprintf('%s__p%03d.md', $baseName, $page);
    }

    public function markdownContent(RegulationFile $file, array $page, string $sourcePath): string
    {
        $pageNumber = (int) ($page['page'] ?? 1);
        $text = trim((string) ($page['text'] ?? ''));

        return trim(implode("\n", [
            '# '.$file->name,
            '',
            '- Regulation file ID: '.$file->id,
            '- Source path: '.$sourcePath,
            '- Page: '.$pageNumber,
            '',
            '---',
            '',
            $text,
        ]))."\n";
    }

    public function syncHash(RegulationFile $file): string
    {
        return hash('sha256', implode('|', [
            $file->id,
            $file->path,
            $file->extension,
            $file->name,
            $file->size,
            $file->chat_supported ? '1' : '0',
        ]));
    }

    public function needsSync(RegulationFile $file): bool
    {
        return $file->ai_sync_status !== RegulationFile::AI_SYNC_STATUS_SYNCED
            || $file->ai_sync_hash !== $this->syncHash($file)
            || ! $file->vectorPages()->exists();
    }

    public function isSupportedPdf(RegulationFile $file): bool
    {
        return $file->chat_supported && strtolower((string) $file->extension) === 'pdf';
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

    private function writeMarkdownPage(RegulationFile $file, array $page, string $sourcePath): string
    {
        $generatedPath = self::PAGE_DIRECTORY.'/'.$this->markdownFileName($file, (int) ($page['page'] ?? 1));

        if (Storage::disk('local')->exists($generatedPath)) {
            $generatedPath = self::PAGE_DIRECTORY.'/'.sprintf(
                '%s__file%s__p%03d.md',
                $this->safeFileName(pathinfo($file->name, PATHINFO_FILENAME) ?: $file->path),
                $file->id,
                (int) ($page['page'] ?? 1),
            );
        }

        Storage::disk('local')->put($generatedPath, $this->markdownContent($file, $page, $sourcePath));

        return $generatedPath;
    }

    private function copyMarkdownPage(string $generatedPath, string $copyDirectory): string
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
            Log::error("OpenAiRegulationSyncService: failed to remove vector store file [{$vectorStoreFileId}] from [{$storeId}]: ".$deleteResponse->body());

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
                Log::warning("OpenAiRegulationSyncService: removed vector store file [{$vectorStoreFileId}] but failed to delete OpenAI file [{$openAiFileId}]: {$exception->getMessage()}");
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

        return $value !== '' ? $value : 'regulation';
    }
}
