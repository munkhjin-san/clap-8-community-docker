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
        return $this->rememberExtractedIndex(
            $absolutePath,
            $extension,
            $allowOcr,
            function () use ($absolutePath, $extension, $allowOcr) {
                $documentIndex = $this->contractExtractionService->extractIndex($absolutePath, $extension, $allowOcr);

                return [
                    'document_index' => $documentIndex,
                    'extraction' => $this->contractExtractionService->lastExtractionMetadata(),
                ];
            }
        );
    }

    public function rememberExtractedIndex(
        string $absolutePath,
        string $extension,
        bool $allowOcr,
        callable $extractor,
    ): array {
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

        $result = $extractor();
        $documentIndex = is_array($result) && is_array($result['document_index'] ?? null)
            ? $result['document_index']
            : [];
        $extraction = is_array($result) && is_array($result['extraction'] ?? null)
            ? $result['extraction']
            : [];

        if (!$this->isValidDocumentIndex($documentIndex)) {
            throw new \RuntimeException('Contract extraction did not return a valid document index.');
        }

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

    public function buildIndexFromPages(array $pages): array
    {
        return $this->contractExtractionService->buildIndexFromPages($pages);
    }

    private function isValidCachedExtraction(mixed $cached): bool
    {
        return is_array($cached)
            && $this->isValidDocumentIndex($cached['document_index'] ?? null);
    }

    private function isValidDocumentIndex(mixed $documentIndex): bool
    {
        return is_array($documentIndex)
            && isset($documentIndex['pages'])
            && is_array($documentIndex['pages']);
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
