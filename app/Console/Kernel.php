<?php

namespace App\Console;

use App\Jobs\GenerateWelcomeMessage;
use App\Jobs\SendReport;
use App\Jobs\RemoveFile;
use App\Jobs\ResetCharge;
use App\Jobs\ProcessMessage;
use App\Models\messageRecord;
use App\Jobs\RemoveTempSchedule;
use App\Jobs\CreateDepartureAlert;
use App\Jobs\CheckUserEvaluation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
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
        
        $schedule->job(new RemoveFile('temp'))->cron('15 9 * * *');
        $schedule->job(new RemoveFile('timecard_orphaned'))->dailyAt('03:15');
        $schedule->job(new ResetCharge())->cron('15 9 10 3,6,9,12 *'); 
        // $schedule->job(new RemoveFile('cost'))->cron('15 9 20 * *');
        $schedule->job(new SendReport(610, 3532, 'incident'))->cron('15 9 * * *');
        // $schedule->job(new SendReport(610, 3633, 'weekly_staff'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 1283, 'weekly_legal'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 1303, 'weekly_balance'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 3599, 'weekly_officer'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 1056, 'monthly_performance'))->cron('15 9 15 * *'); 
        $schedule->job(new SendReport(610, 1056, 'monthly_shift'))->cron('15 9 20 * *');
        $schedule->job(new SendReport(610, 1056, 'monthly_mailing'))->cron('15 9 20 * *');
        // $schedule->job(new ProcessMessage())->hourly();

        // $schedule->job(new GenerateWelcomeMessage())->cron('0 * * * *');
        $schedule->job(new RemoveTempSchedule())->cron('15 9 * * *');
        $schedule->job(new CheckUserEvaluation())->dailyAt('01:00');

        $schedule->command('posts:close-expired')->dailyAt('02:00');
        $schedule->command('alerts:variance --period='.now()->toDateString())->monthlyOn(20, '18:00');
        $schedule->command('logs:prune-activity-logs')->quarterly();
        $schedule->command('goals:check-alert-streak')->dailyAt('02:00');
        $schedule->command('refresh:expire')->monthlyOn(2, '08:00');
        $schedule->command('app:auto-attendance-confirm')->monthlyOn(3, '08:00');
        $schedule->command('app:refresh-automation')->monthlyOn(3, '08:00');
        $schedule->command('contact-batches:poll')->everyFifteenMinutes();
        $schedule->command('app:seal-audit-daily-digest')->dailyAt('02:40');
        $schedule->command('app:verify-timecard-audit-integrity --date='.now()->subDay()->toDateString())->dailyAt('03:00');
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
