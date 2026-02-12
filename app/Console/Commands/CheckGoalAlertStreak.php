<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\EvaluationRecord;

class CheckGoalAlertStreak extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goals:check-alert-streak';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update evaluation alert streak based on grace-overdue goals (end_date + 7 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $updated = 0;
        $data = [];
        $evals = EvaluationRecord::query()
            ->select(['id','user_id','year','which_half','alert_streak','last_alert_goal_month','last_processed_goal_month'])
            ->get();

        foreach ($evals as $ev) {

            // Process at most a few months per run to avoid long cron time
            for ($i = 0; $i < 6; $i++) {

                // 1) Find NEXT scheduled goal-month after last_processed_goal_month
                // that is decidable (grace deadline passed).
                $nextMonth = DB::table('project_goals')
                    ->where('user_id', $ev->user_id)
                    ->where('year', $ev->year)
                    ->where('which_half', $ev->which_half)
                    ->whereNotNull('end_date')
                    ->when($ev->last_processed_goal_month, function ($q) use ($ev) {
                        $q->whereRaw("DATE_FORMAT(end_date, '%Y-%m') > ?", [$ev->last_processed_goal_month]);
                    })
                    ->whereRaw("DATE_ADD(CONCAT(end_date,' 23:59:59'), INTERVAL 7 DAY) < ?", [$now->toDateTimeString()])
                    ->selectRaw("DATE_FORMAT(end_date, '%Y-%m') as ym")
                    ->groupBy('ym')
                    ->orderBy('ym') // earliest next month
                    ->value('ym');
                
                if (!$nextMonth) break; // nothing new to process
                
                // 2) Determine if that month is alerting (any unfinished goals in that month)
                $unfinishedCount = DB::table('project_goals')
                    ->where('user_id', $ev->user_id)
                    ->where('year', $ev->year)
                    ->where('which_half', $ev->which_half)
                    ->whereNotNull('end_date')
                    ->whereRaw("DATE_FORMAT(end_date, '%Y-%m') = ?", [$nextMonth])
                    ->where('status', '!=', 9)
                    ->count();
                
                $isAlerting = $unfinishedCount > 0;

                // 3) Find previous scheduled goal-month before $nextMonth (may have gaps)
                $prevScheduled = DB::table('project_goals')
                    ->where('user_id', $ev->user_id)
                    ->where('year', $ev->year)
                    ->where('which_half', $ev->which_half)
                    ->whereNotNull('end_date')
                    ->whereRaw("DATE_FORMAT(end_date, '%Y-%m') < ?", [$nextMonth])
                    ->selectRaw("DATE_FORMAT(end_date, '%Y-%m') as ym")
                    ->groupBy('ym')
                    ->orderByDesc('ym')
                    ->value('ym');

                if ($isAlerting) {
                    $continued = $prevScheduled && ($ev->last_alert_goal_month === $prevScheduled);
                    $ev->alert_streak = $continued ? ((int)$ev->alert_streak + 1) : 1;
                    $ev->last_alert_goal_month = $nextMonth;

                } else {
                    // clean month breaks streak
                    $ev->alert_streak = 0;
                    $ev->last_alert_goal_month = null;
                }

                // 4) Mark month processed (this is the KEY)
                $ev->last_processed_goal_month = $nextMonth;

                $ev->save();
                $updated++;

                if (!isset($data[$ev->user_id])) {
                    $data[$ev->user_id] = [
                        "count" => 0,
                        "ev_ids" => [],
                    ];
                }

                $data[$ev->user_id]["count"]++;
                $data[$ev->user_id]["ev_ids"][] = $ev->id;
                
            }
        }

        $this->info("Updated {$updated} evaluation records.");
        $this->info('Per-user breakdown: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

}
