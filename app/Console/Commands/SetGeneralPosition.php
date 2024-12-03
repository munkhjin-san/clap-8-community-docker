<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectEvaluation;
use App\Models\User;
class SetGeneralPosition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-general-position';

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
        $evaluations = ProjectEvaluation::all();
        foreach ($evaluations as $evaluation) {
            User::find($evaluation->user_id)->update(['general_position' => $evaluation->general_position]);
        }
    }
}
