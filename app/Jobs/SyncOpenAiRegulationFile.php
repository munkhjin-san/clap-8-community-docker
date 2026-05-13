<?php

namespace App\Jobs;

use App\Models\RegulationFile;
use App\Services\Contracts\ContractExtractionService;
use App\Services\Regulations\OpenAiRegulationSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncOpenAiRegulationFile implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $regulationFileId,
        public bool $force = false,
    ) {}

    public function handle(
        ContractExtractionService $contractExtractionService,
        OpenAiRegulationSyncService $syncService,
    ): void {
        $file = RegulationFile::find($this->regulationFileId);

        if (! $file) {
            return;
        }

        $syncService->resetPageDirectory();
        $syncService->syncFile($file, $contractExtractionService, force: $this->force);
    }
}
