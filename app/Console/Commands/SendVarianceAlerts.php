<?php

namespace App\Console\Commands;

use App\Mail\VarianceAlertMail;
use App\Models\ProjectRecord;
use App\Models\VarianceAlertLog;
use App\Domain\Contracts\{PlanProvider,ActualProvider};
use App\Services\VarianceService;
use App\Models\User;
use App\Models\boardRecord;
use App\Services\ProjectNotifyService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendVarianceAlerts extends Command
{
    protected $signature = 'alerts:variance {--period=}';
    protected $description = 'Send monthly variance alerts for previous month (JST)';
     public function __construct(
        private PlanProvider $plans,
        private ActualProvider $actuals,
    ) {
        parent::__construct();
    }
    public function handle()
    {
        $threshold = (float) config('app.variance_threshold', 10);
        $period = $this->resolvePeriod();
        $projects = ProjectRecord::query()
            ->select('id', 'name')
            ->with(['manager:id,name,email']) // clean shorthand
            ->get();

        $projectNames = $projects->pluck('name')->all();

        $P = $this->plans->fetchMonthlyPlans($period, $projectNames);
        $A = $this->actuals->fetchMonthlyActuals($period, $projectNames);
        $byName = $projects->keyBy('name');
        $override_user = User::select('id', 'name', 'icon_path', 'icon_bg')->findOrFail(610);

        $settlementResponse = [];
        $sent = 0;
        
        foreach ($byName as $name => $project) {
            $plan = $P[$name] ?? null;
            $act  = $A[$name] ?? null;
            if (!$act) continue;

            $v = [
                'sales'   => VarianceService::achToVar(VarianceService::pct($act['sales']??null,   $plan['sales']??null)),
                'expenses' => VarianceService::achToVar(VarianceService::pct($act['expenses']??null, $plan['expenses']??null)),
                'profit'  => VarianceService::achToVar(VarianceService::pct($act['profit']??null,  $plan['profit']??null)),
            ];
            
             $settlementResponse[$name] = [
                'plan'     => $plan,
                'actual'   => $act,
                'variance' => $v,
            ];
            if (!VarianceService::anyOverThreshold($v, $threshold)) continue;
            $exists = VarianceAlertLog::query()
                ->where('project_record_id', $project->id)
                ->whereDate('period', $period)
                ->exists();
            if ($exists) continue;
            $rows = [];

            foreach (['sales'=>'売上','expenses'=>'販管費','profit'=>'利益'] as $k=>$label) {
                $var = $v[$k];
                if ($var === null || abs($var) < $threshold) continue;
                $rows[] = [
                    'metric_label' => $label,
                    'plan'   => $plan[$k] ?? null,
                    'actual' => $act[$k] ?? null,
                    'variance' => $var,
                ];
            }
            
            if (!$rows) continue;
        
            if ($project->manager->isEmpty()) {
                $this->warn("Project {$project->name} has no manager; skipping alert.");
                continue;
            }
            foreach ($project->manager as $pm) {
                if ($pm->id === $override_user->id) {
                    $this->warn("PM {$pm->name} is HQ; skipping alert.");
                    continue;
                }
                $boardId = boardRecord::query()
                ->where('private_flag', 1)
                ->whereHas('members', fn($q) => $q->where('users.id', $pm->id))
                ->whereHas('members', fn($q) => $q->where('users.id', $override_user->id))
                ->value('id');
                if (!$boardId) {
                    $this->warn("Project {$project->name} manager {$pm->name} has no shared private board with override user; skipping alert.");
                    continue;
                }
                app(ProjectNotifyService::class)
                ->notifyManagersAboutPeriod($project, $pm->name, $period, $rows, $override_user, $boardId);

            }
            
            VarianceAlertLog::create([
                'project_record_id' => $project->id,
                'period'     => $period,
                'hash'       => hash('sha256', $project->id.$period->toDateString().json_encode($rows)),
                'sent_at'    => now(),
            ]);
            $sent++;
        }
        $this->info("Variance alerts for ".$period->format('Y-m').": {$sent} sent.");

    }
    private function resolvePeriod(): \Carbon\CarbonImmutable
    {
        $tz = config('app.timezone', 'Asia/Tokyo');
        // If you pass --period=2025-08-01 it uses that month, but returns the *end* of that month.
        if ($p = $this->option('period')) {
            return \Carbon\CarbonImmutable::parse($p, $tz)
            ->subMonth()        // shift to previous month
            ->endOfMonth()      // last day of that month
            ->startOfDay();
        }

        // Otherwise: go back one month from "now" (in JST), then return that month’s end.
        return now($tz)
            ->setTimezone(config('app.timezone', 'Asia/Tokyo'))
            ->subMonth()
            ->endOfMonth()
            ->toImmutable();
    }
}
