<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\taskUser;

class SetTaskProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-task-progress';

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
        $task_users = taskUser::all();
        foreach($task_users as $task_user) {
            if ($task_user->comp_flag === 1) {
                $task_user->progress_flag = 2;
                $task_user->save();
            }
        }
    }
}
