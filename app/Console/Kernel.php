<?php

namespace App\Console;

use App\Jobs\SendReport;
use App\Jobs\RemoveFile;
use App\Jobs\ResetCharge;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
        
        $schedule->job(new RemoveFile('temp'))->cron('15 9 * * *');
        $schedule->job(new ResetCharge())->cron('15 9 10 3,6,9,12 *'); 
        // $schedule->job(new RemoveFile('cost'))->cron('15 9 * * 1');
        $schedule->job(new SendReport(610, 3532, 'incident'))->cron('15 9 * * *');
        $schedule->job(new SendReport(610, 3599, 'weekly'))->cron('15 9 * * 1');
        $schedule->job(new SendReport(610, 1056, 'monthly_3S'))->cron('15 9 20 * *');
        $schedule->job(new SendReport(610, 1056, 'monthly_performance'))->cron('15 9 15 * *');
        $schedule->job(new SendReport(610, 1056, 'monthly_shift'))->cron('15 9 20 * *');
        $schedule->job(new SendReport(610, 1056, 'monthly_mailing'))->cron('15 9 20 * *');
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
