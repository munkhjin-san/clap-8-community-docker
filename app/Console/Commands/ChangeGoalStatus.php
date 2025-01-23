<?php

namespace App\Console\Commands;

use App\Models\ProjectGoal;
use App\Models\SalaryIssue;
use Illuminate\Console\Command;

class ChangeGoalStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-goal-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        ProjectGoal::where('status', 7)
                    ->orWhere('status', 8)
                    ->update(['status' => 9]);
        SalaryIssue::where('status', 7)
                    ->orWhere('status', 8)
                    ->update(['status' => 9]);
    }
}
