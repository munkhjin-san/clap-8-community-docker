<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use App\Services\SharedService;
class TaskCreated implements ShouldQueue
{


    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $payload;
    /**
     * Create a new job instance.
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(SharedService $sharedService): void
    {
        $data = $this->payload;
        $trim = Str::limit($data['text'], 50, '...');
        $type = $data['glowd_nine'] ? 'glowd_nine_task' : 'new_task';
        $sharedService->createInfoMessage("タスク", $data['board_id'], $type, $data['user_id'], $trim);  
    }
}
