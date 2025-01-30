<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ReportService;
class SendReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $override_user_id;
    protected $board_id;
    protected $type;
    public function __construct($override_user_id, $board_id, $type)
    {
        $this->override_user_id = $override_user_id;
        $this->board_id = $board_id;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        $chat = $reportService->sendMessage($this->override_user_id, $this->board_id, $this->type);

        $types = ['weekly_staff', 'weekly_legal', 'weekly_balance', 'weekly_officer'];
        if(in_array($this->type, $types)){
            $reportService->checkRequest($chat, $this->override_user_id);
        }
        if($this->type == 'monthly_performance'){
            $reportService->createTask($this->override_user_id, $this->board_id, $this->type);
        }
    }
}
