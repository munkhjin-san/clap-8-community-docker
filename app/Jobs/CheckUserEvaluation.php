<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use App\Jobs\CalculateMonthlyGoalSlot;

class CheckUserEvaluation implements ShouldQueue
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
        $userList = User::where('retire', 0)
        ->where('partner_flag', 0)
        ->whereNotNull('user_code')
        ->where('hide_flag', 0)
        ->where('position_id', '>', 6)
        ->where('position_id', '<', 13)
        ->select('id', 'name', 'position_id', 'icon_path', 'icon_bg', 'user_code', 'general_position')
        ->get();
        foreach($userList as $user){
            CalculateMonthlyGoalSlot::dispatch($user);
        }
    }
}
