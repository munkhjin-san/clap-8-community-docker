<?php

namespace App\Services\Contracts;

use Illuminate\Support\Facades\Cache;

class CachedContractExtractionService
{
    private array $lastExtractionMetadata = [];

    public function __construct(
        private ContractExtractionService $contractExtractionService,
    ) {
    }

    public function inspectPdf(string $absolutePath): array
    {
        return $this->contractExtractionService->inspectPdf($absolutePath);
    }

    public function extractIndex(string $absolutePath, string $extension, bool $allowOcr = false): array
    {
        $extension = strtolower($extension);
        $cacheKey = $this->cacheKey($absolutePath, $extension, $allowOcr);
        $cacheTtl = $this->cacheTtl();

        if ($cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if ($this->isValidCachedExtraction($cached)) {
                $this->lastExtractionMetadata = $this->withCachedFlag(
                    is_array($cached['extraction'] ?? null) ? $cached['extraction'] : [],
                    true,
                );

                return $cached['document_index'];
            }
        }

        $documentIndex = $this->contractExtractionService->extractIndex($absolutePath, $extension, $allowOcr);
        $extraction = $this->contractExtractionService->lastExtractionMetadata();
        $this->lastExtractionMetadata = $this->withCachedFlag($extraction, false);

        if ($cacheTtl > 0) {
            Cache::put($cacheKey, [
                'document_index' => $documentIndex,
                'extraction' => $extraction,
            ], now()->addSeconds($cacheTtl));
        }

        return $documentIndex;
    }

    public function lastExtractionMetadata(): array
    {
        return $this->lastExtractionMetadata;
    }

    private function isValidCachedExtraction(mixed $cached): bool
    {
        return is_array($cached)
            && isset($cached['document_index'])
            && is_array($cached['document_index'])
            && isset($cached['document_index']['pages'])
            && is_array($cached['document_index']['pages']);
    }

    private function withCachedFlag(array $extraction, bool $cached): array
    {
        $extraction['cached'] = $cached;

        return $extraction;
    }

    private function cacheKey(string $absolutePath, string $extension, bool $allowOcr): string
    {
        $fileSize = is_file($absolutePath) ? (int) @filesize($absolutePath) : 0;
        $fileHash = is_file($absolutePath) ? @hash_file('sha1', $absolutePath) : false;

        if (!is_string($fileHash) || $fileHash === '') {
            $fileHash = sha1($absolutePath.'|'.$fileSize.'|'.(is_file($absolutePath) ? (int) @filemtime($absolutePath) : 0));
        }

        $fingerprint = implode('|', [
            'v2',
            $extension,
            $allowOcr ? 'ocr' : 'no-ocr',
            $fileSize,
            $fileHash,
        ]);

        return 'contract_extract:'.sha1($fingerprint);
    }

    private function cacheTtl(): int
    {
        return max(0, (int) config('services.google.contract_extract_cache_ttl', 60 * 60 * 24 * 30));
    }
}
