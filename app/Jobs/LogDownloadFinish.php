<?php

namespace App\Jobs;

use App\Models\DriveDownloadLog; 
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LogDownloadFinish implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = DriveDownloadLog::find($this->logId);
        if (!$log) return;

        $end = now();
        $log->fill(array_merge([
            'ended_at'    => $end,
            'duration_ms' => $end->diffInMilliseconds($log->started_at),
            'success'     => true,
        ], $this->overrides))->save();
    }
}
