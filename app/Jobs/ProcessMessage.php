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
    public function handle(): void
    {
        $startTime = Carbon::now()->startOfMinute();
        $messages = messageRecord::where('reserved_at', $startTime)->with('user')->get();

        Log::info('Messages to process:', ['count' => $messages->count(), 'startTime' => $startTime]);

        foreach ($messages as $message) {
            $requestData = [
                'id' => $message->id,
                'draft_flag' => 0,
                'user' => $message->user
            ];
            $request = new Request($requestData);
            app('App\Http\Controllers\BoardController')->draftSend($request);
            Log::info('Dispatched ProcessMessage job for message', [
                'id' => $message->id,
                'reserved_at' => $message->reserved_at,
            ]);
        }

        Log::info('Scheduled message processing completed.');
        
        
    }
}
