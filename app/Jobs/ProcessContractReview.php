<?php

namespace App\Jobs;

use App\Models\ContractReviewJob;
use App\Services\Contracts\ContractReviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessContractReview implements ShouldQueue
{
    use Queueable;

    // OCR-heavy PDFs can legitimately take several minutes end to end.
    public int $timeout = 1800;

    // The service records failures on the job row; re-running the whole
    // pipeline automatically would double OCR/AI spend.
    public int $tries = 1;

    public function __construct(public int $contractReviewJobId)
    {
    }

    public function handle(ContractReviewService $service): void
    {
        $job = ContractReviewJob::find($this->contractReviewJobId);
        if (!$job || $job->status !== ContractReviewJob::STATUS_QUEUED) {
            return;
        }

        $service->execute($job);
    }
}
