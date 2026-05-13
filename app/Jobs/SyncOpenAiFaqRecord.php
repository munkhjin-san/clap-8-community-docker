<?php

namespace App\Jobs;

use App\Models\questionAndAnswerRecord;
use App\Services\Faq\OpenAiFaqSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncOpenAiFaqRecord implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $recordId,
        public bool $force = false,
    ) {}

    public function handle(OpenAiFaqSyncService $syncService): void
    {
        $record = questionAndAnswerRecord::find($this->recordId);

        if (! $record) {
            return;
        }

        $syncService->resetDocumentDirectory();
        $syncService->syncRecord($record, force: $this->force);
    }
}
