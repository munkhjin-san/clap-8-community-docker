<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        $trim = strlen($data['text']) > 20 ? substr($data['text'], 0, 50) . '...' : $data['text'];
        $sharedService->createInfoMessage("タスク", $data['board_id'], 'new_task', $data['user_id'], $trim);  
    }
}
