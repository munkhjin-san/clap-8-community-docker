<?php

namespace App\Jobs;

use App\Models\questionAndAnswerRecord;
use App\Services\Faq\OpenAiFaqSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveOpenAiFaqRecordDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $recordId) {}

    public function handle(OpenAiFaqSyncService $syncService): void
    {
        $record = questionAndAnswerRecord::find($this->recordId);

        if (! $record) {
            return;
        }

        $syncService->deleteTrackedDocuments($record);
        $syncService->markRecordNotSynced($record);
    }
}
