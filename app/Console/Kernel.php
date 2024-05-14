<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\AutoJobController;
use App\Models\tempUser;
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->call('\App\Http\Controllers\AutoJobController@removeOldBoards');
        // $schedule->call('\App\Http\Controllers\AutoJobController@removeOldFiles');
        // $schedule->call('\App\Http\Controllers\AutoJobController@removeTempUsers');
        // $schedule->call('\App\Http\Controllers\AutoJobController@removePasswordResets');
        $schedule->call('\App\Http\Controllers\AutoJobController@removeTemprorayFiles')->cron('15 9 * * *');
        // pause until March data revision
        $schedule->call('\App\Http\Controllers\MemberController@reset_charge')->cron('15 9 1 3,6,9,12 *'); 
        $schedule->call('\App\Http\Controllers\AutoJobController@incident_report')->cron('15 9 * * *');
        $schedule->call('\App\Http\Controllers\AutoJobController@weekly_report')->cron('15 9 * * 1');
        $schedule->call('\App\Http\Controllers\AutoJobController@monthly_report1')->cron('15 9 20 * *');
        $schedule->call('\App\Http\Controllers\AutoJobController@monthly_report2')->cron('15 9 20 * *');
        $schedule->call('\App\Http\Controllers\AutoJobController@monthly_report3S')->cron('15 9 20 * *');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
