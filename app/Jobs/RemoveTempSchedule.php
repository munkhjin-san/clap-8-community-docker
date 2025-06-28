<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\CalendarRecord;
use Carbon\Carbon;
use App\Http\Controllers\CalendarController;
use Illuminate\Http\Request;

class RemoveTempSchedule implements ShouldQueue
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
    public function handle(CalendarController $calendarController): void
    {
        $now = Carbon::now();
        
        $records = CalendarRecord::where('temp_flag', 1)->where('created_at', '<', $now->subHours(24))->get();
        foreach ($records as $record) {
            $calendarController->calendar_delete_record(new Request([
                'id' => $record->id,
            ]));
        }


    }
}
