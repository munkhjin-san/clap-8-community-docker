<?php

namespace App\Jobs;

use App\Models\RegulationFile;
use App\Services\Regulations\OpenAiRegulationSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveOpenAiRegulationFilePages implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $regulationFileId) {}

    public function handle(OpenAiRegulationSyncService $syncService): void
    {
        $file = RegulationFile::withTrashed()->find($this->regulationFileId);

        if (! $file) {
            return;
        }

        $syncService->deleteTrackedPages($file);
        $syncService->markFileNotSynced($file);
    }
}
