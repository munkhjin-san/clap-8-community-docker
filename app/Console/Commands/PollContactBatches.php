<?php

namespace App\Console\Commands;

use App\Models\ContactBatch;
use App\Services\ContactBatchMonitorService;
use App\Services\ContactBatchNotificationService;
use Illuminate\Console\Command;

class PollContactBatches extends Command
{
    protected $signature = 'contact-batches:poll {--limit=100}';
    protected $description = 'Poll in-progress contact batches and create completion notifications.';

    public function __construct(
        private ContactBatchMonitorService $monitorService,
        private ContactBatchNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $batches = ContactBatch::query()
            ->whereIn('status', [ContactBatch::STATUS_SCANNING, ContactBatch::STATUS_ENRICHING])
            ->where(function ($query) {
                $query->whereNotNull('scan_operation')
                    ->orWhereNotNull('enrich_operation');
            })
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $processed = 0;
        $completed = 0;
        $failed = 0;
        $notified = 0;

        foreach ($batches as $batch) {
            $processed++;
            $batch = $this->monitorService->refresh($batch);

            if ($batch->status === ContactBatch::STATUS_COMPLETED) {
                $completed++;
            }

            if ($batch->status === ContactBatch::STATUS_FAILED) {
                $failed++;
            }

            if ($this->notificationService->notifyIfNeeded($batch)) {
                $notified++;
            }
        }

        $this->info("Processed {$processed} contact batch(es); completed={$completed}, failed={$failed}, notifications={$notified}.");

        return self::SUCCESS;
    }
}
