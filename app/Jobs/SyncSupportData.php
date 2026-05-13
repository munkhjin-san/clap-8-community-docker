<?php

namespace App\Jobs;

use App\Models\RegulationFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncSupportData implements ShouldQueue
{
    use Queueable;

    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';
    private const UPLOAD_URL = 'https://generativelanguage.googleapis.com/upload/v1beta';
    private const STORE_DATA_PATH = 'regulation_store/store.json';

    public function __construct() {}

    public function handle(): void
    {
        $apiKey = config('app.gemini_api_key');

        $this->deletePreviousStore($apiKey);

        $store = $this->createStore($apiKey);
        $storeName = $store['name'];

        Log::info("SyncSupportData: created FileSearchStore [{$storeName}]");

        $files = RegulationFile::where('chat_supported', 1)->where('extension', 'pdf')->get();

        foreach ($files as $file) {
            $localPath = "regulation_files/{$file->path}.{$file->extension}";

            if (!Storage::disk('local')->exists($localPath)) {
                Log::warning("SyncSupportData: file not found in storage [{$localPath}]");
                continue;
            }

            $this->uploadFileToStore($apiKey, $storeName, $localPath, $file);
        }

        Storage::disk('local')->put(self::STORE_DATA_PATH, json_encode([
            'name'        => $storeName,
            'displayName' => $store['displayName'] ?? null,
            'createTime'  => $store['createTime'] ?? now()->toISOString(),
            'syncedAt'    => now()->toISOString(),
        ], JSON_PRETTY_PRINT));

        Log::info("SyncSupportData: sync complete. Store [{$storeName}], files [{$files->count()}]");
    }

    private function deletePreviousStore(string $apiKey): void
    {
        if (!Storage::disk('local')->exists(self::STORE_DATA_PATH)) {
            return;
        }

        $data = json_decode(Storage::disk('local')->get(self::STORE_DATA_PATH), true);
        $storeName = $data['name'] ?? null;

        if (!$storeName) {
            return;
        }

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->delete(self::BASE_URL . "/{$storeName}?key={$apiKey}&force=true");

        if ($response->successful()) {
            Storage::disk('local')->delete(self::STORE_DATA_PATH);
            Log::info("SyncSupportData: deleted previous FileSearchStore [{$storeName}]");
        } else {
            Log::error("SyncSupportData: failed to delete FileSearchStore [{$storeName}]: " . $response->body());
        }
    }

    private function createStore(string $apiKey): array
    {
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post(self::BASE_URL . "/fileSearchStores?key={$apiKey}", [
                'displayName' => 'Regulation Files Store',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('SyncSupportData: failed to create FileSearchStore: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Upload a local file to the FileSearchStore using a multipart/related request
     * as required by the Gemini media upload protocol.
     */
    private function uploadFileToStore(string $apiKey, string $storeName, string $localPath, RegulationFile $file): void
    {
        $fileContents = Storage::disk('local')->get($localPath);
        $mimeType     = 'application/pdf';
        $boundary     = '-------' . bin2hex(random_bytes(12));

        $metadata = json_encode([
            'displayName' => $file->name,
            'mimeType'    => $mimeType,
        ]);

        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . $metadata . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: {$mimeType}\r\n\r\n"
            . $fileContents . "\r\n"
            . "--{$boundary}--";

        $endpoint = self::UPLOAD_URL . "/{$storeName}:uploadToFileSearchStore"
            . "?key={$apiKey}&uploadType=multipart";

        $response = Http::withBody($body, "multipart/related; boundary={$boundary}")
            ->post($endpoint);

        if ($response->successful()) {
            Log::info("SyncSupportData: uploaded [{$file->path}] to store");
        } else {
            Log::error("SyncSupportData: failed to upload [{$file->path}]: " . $response->body());
        }
    }
}
