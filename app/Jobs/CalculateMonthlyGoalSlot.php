<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use App\Models\EvaluationRecord;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class CalculateMonthlyGoalSlot implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        $now = Carbon::now();
        $month = $now->month;
        $day = $now->day;
        $year = $now->year;
        $span = $now->betweenIncluded(
            $now->copy()->month(4)->day(1)->startOfDay(),   // Apr 1
            $now->copy()->month(9)->day(30)->endOfDay()     // Sep 30
        ) ? 'first' : 'second';
        $year = $span === 'first' ? $year : $year - 1;
        $evaluation = EvaluationRecord::where('user_id', $this->user->id)
            ->where('year', $year)
            ->where('which_half', $span)
            ->first();
        if(!$evaluation){
            $new_evaluation = new EvaluationRecord();
            $new_evaluation->user_id = $this->user->id;
            $new_evaluation->year = $year;
            $new_evaluation->which_half = $span;
            $new_evaluation->monthly_goal_slot = 6; // default value
            $new_evaluation->save();
            $log_message = "Created new EvaluationRecord for user ID {$this->user->id} for {$year} {$span} half. EvaluationRecord ID: {$new_evaluation->id}";
            Log::info($log_message);
            return;
        }
        // $evaluation = EvaluationRecord::where('user_id', $this->user->id)
    }
}
