<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\shiftRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\DepartureNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class CreateDepartureAlert implements ShouldQueue
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
        $target_users = User::where('position_id', 15)->where('retire' , 0)->whereNotNull('email')
        ->whereHas('shift_records', function ($query) {
            $query->where('shift_day', Carbon::now()->toDateString())->where('shift_type', 1)->whereNull('departure_report');
        })
        ->get();
        $target_users_ids = [];
        foreach ($target_users as $user) {
            $valid_email = filter_var($user->email, FILTER_VALIDATE_EMAIL);
            if ($valid_email) {
                echo "send to ".$user->email."\n";
                $url = URL::temporarySignedRoute(
                    'departure_activate',
                    Carbon::now()->addHours(12),
                    ['user' => $user->id, 'date' => Carbon::now()->toDateString()]
                );
                $date = Carbon::now()->toDateString();
                Mail::to($user->email)->send(new DepartureNotification($url, $date, $user));
            }
            $target_users_ids[] = $user->id;
        }
        //write log
        $log_message = 'Departure alert sent to user IDs: ' . implode(', ', $target_users_ids);
        Log::info($log_message);
    }
}
