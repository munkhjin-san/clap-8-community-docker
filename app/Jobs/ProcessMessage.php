<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\messageRecord;
use App\Services\DraftMessageSender;
class ProcessMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(DraftMessageSender $sender): void
    {
        $startTime = Carbon::now()->startOfMinute();
        $messages = messageRecord::where('reserved_at', $startTime)->with('user')->get();

        Log::info('Messages to process:', ['count' => $messages->count(), 'startTime' => $startTime]);

        foreach ($messages as $message) {
            try {
                $new = $sender->send($message);
                Log::info('Scheduled draft sent', [
                    'old_id' => $message->id,
                    'new_id' => $new->id,
                    'record_id' => $new->record_id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Scheduled draft send failed', [
                    'id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Scheduled message processing completed.');
        
        
    }
}
