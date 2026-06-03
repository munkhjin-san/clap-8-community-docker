<?php

namespace App\Console\Commands;

use App\Models\ProjectGoal;
use App\Models\ProjectGoalIncidentReport;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ReportOutcomeGoalIncidents extends Command
{
    protected $signature = 'goals:report-outcome-incidents {--dry-run : Show the incident message without posting or writing sent markers}';

    protected $description = 'Report overdue outcome goal result submissions and PM approvals to the incident board';

    private const INCIDENT_BOARD_ID = 3532;
    private const OVERRIDE_USER_ID = 610;
    private const TYPE_USER_SUBMISSION = 'user_submission';
    private const TYPE_PM_APPROVAL = 'pm_approval';

    public function handle(ReportService $reportService): int
    {
        $now = Carbon::now();
        $allowedPeriods = $this->currentAndPreviousHalves($now);
        $userSubmissionGoals = $this->userSubmissionIncidentGoals($now, $allowedPeriods);
        $pmApprovalGoals = $this->pmApprovalIncidentGoals($now, $allowedPeriods);

        if ($userSubmissionGoals->isEmpty() && $pmApprovalGoals->isEmpty()) {
            $this->info('No outcome goal incidents to report.');
            return self::SUCCESS;
        }

        $message = $this->buildIncidentBoardMessage($userSubmissionGoals, $pmApprovalGoals);

        if ($this->option('dry-run')) {
            $this->line($message);
            return self::SUCCESS;
        }

        $chat = $reportService->sendRawMessage(self::OVERRIDE_USER_ID, self::INCIDENT_BOARD_ID, $message);
        $messageRecordId = $chat->original['data']->id ?? null;

        $this->markIncidentsSent($userSubmissionGoals, self::TYPE_USER_SUBMISSION, $now, $messageRecordId);
        $this->markIncidentsSent($pmApprovalGoals, self::TYPE_PM_APPROVAL, $now, $messageRecordId);

        $this->info(sprintf(
            'Reported %d outcome goal incident(s).',
            $userSubmissionGoals->count() + $pmApprovalGoals->count()
        ));

        return self::SUCCESS;
    }

    private function currentAndPreviousHalves(Carbon $now): array
    {
        $fiscalYear = $now->month >= 4 ? $now->year : $now->year - 1;
        $currentHalf = $now->month >= 4 && $now->month <= 9 ? 'first' : 'second';

        return [
            ['year' => $fiscalYear, 'which_half' => $currentHalf],
        ];
    }

    private function userSubmissionIncidentGoals(Carbon $now, array $allowedPeriods): Collection
    {
        $reportedGoalIds = ProjectGoalIncidentReport::where('incident_type', self::TYPE_USER_SUBMISSION)
            ->pluck('project_goal_id');

        return ProjectGoal::query()
            ->inAllowedHalves($allowedPeriods)
            ->whereNotNull('end_date')
            ->whereNotIn('status', [7, 9])
            ->whereRaw("DATE_ADD(CONCAT(end_date, ' 23:59:59'), INTERVAL 7 DAY) < ?", [$now->toDateTimeString()])
            ->whereNotIn('id', $reportedGoalIds)
            ->whereHas('user', fn ($q) => $q->where('retire', 0))
            ->with([
                'user:id,name',
                'project:id,name',
            ])
            ->get();
    }

    private function pmApprovalIncidentGoals(Carbon $now, array $allowedPeriods): Collection
    {
        $reportedGoalIds = ProjectGoalIncidentReport::where('incident_type', self::TYPE_PM_APPROVAL)
            ->pluck('project_goal_id');
        $approvalDeadline = $now->copy()->subDays(7);

        return ProjectGoal::query()
            ->inAllowedHalves($allowedPeriods)
            ->where('status', 7)
            ->whereNotIn('id', $reportedGoalIds)
            ->whereHas('user', fn ($q) => $q->where('retire', 0))
            ->whereHas('project.members', function ($memberQuery) {
                $memberQuery->whereColumn('users.id', 'project_goals.user_id');
            })
            ->whereHas('project.manager')
            ->where(function ($query) use ($approvalDeadline) {
                $query->whereRaw(
                    "(select max(created_at) from status_logs where status_logs.record_id = project_goals.id and status_logs.type = 'project_goal' and status_logs.after_number = 7) <= ?",
                    [$approvalDeadline->toDateTimeString()]
                )->orWhere(function ($fallbackQuery) use ($approvalDeadline) {
                    $fallbackQuery->whereDoesntHave('statusLogs', function ($logQuery) {
                        $logQuery->where('after_number', 7);
                    })->where('updated_at', '<=', $approvalDeadline);
                });
            })
            ->with([
                'user:id,name',
                'project.manager',
                'statusLogs' => fn ($query) => $query->where('after_number', 7),
            ])
            ->get()
            ->filter(fn (ProjectGoal $goal) => $this->pmManagers($goal)->isNotEmpty())
            ->values();
    }

    private function buildIncidentBoardMessage(Collection $userSubmissionGoals, Collection $pmApprovalGoals): string
    {
        $userSubmissionSection = $userSubmissionGoals->isEmpty()
            ? ''
            : "【本人未申請】\n" . $userSubmissionGoals
                ->map(fn (ProjectGoal $goal) => sprintf(
                    '・氏名：%s　成果目標：%s　終了日：%s',
                    $this->cleanText($goal->user?->name, '不明'),
                    $this->goalTitle($goal),
                    $goal->end_date
                ))
                ->implode("\n");

        $pmApprovalSection = $pmApprovalGoals->isEmpty()
            ? ''
            : "【PM未承認】\n" . $pmApprovalGoals
                ->map(function (ProjectGoal $goal) {
                    $submittedAt = $this->resultSubmittedAt($goal);

                    return sprintf(
                        '・PM：%s　対象者：%s　成果目標：%s　申請日：%s',
                        $this->pmManagers($goal)->pluck('name')->map(fn ($name) => $this->cleanText($name))->implode('、'),
                        $this->cleanText($goal->user?->name, '不明'),
                        $this->goalTitle($goal),
                        $submittedAt ? $submittedAt->toDateString() : '不明'
                    );
                })
                ->implode("\n");

        $sections = collect([$userSubmissionSection, $pmApprovalSection])
            ->filter()
            ->implode("\n\n");

        return <<<EOT
        [To:全員:]
        【成果目標インシデント】

        以下の成果目標が期限内に処理されませんでした。

        {$sections}

        ご確認のうえ、対応をお願いいたします。
        EOT;
    }

    private function markIncidentsSent(Collection $goals, string $incidentType, Carbon $sentAt, ?int $messageRecordId): void
    {
        foreach ($goals as $goal) {
            ProjectGoalIncidentReport::firstOrCreate(
                [
                    'project_goal_id' => $goal->id,
                    'incident_type' => $incidentType,
                ],
                [
                    'responsible_user_id' => $this->responsibleUserId($goal, $incidentType),
                    'message_record_id' => $messageRecordId,
                    'sent_at' => $sentAt,
                ]
            );
        }
    }

    private function responsibleUserId(ProjectGoal $goal, string $incidentType): ?int
    {
        if ($incidentType === self::TYPE_USER_SUBMISSION) {
            return $goal->user_id;
        }

        return $this->pmManagers($goal)->first()?->id;
    }

    private function resultSubmittedAt(ProjectGoal $goal): ?Carbon
    {
        $statusLog = $goal->statusLogs
            ->where('after_number', 7)
            ->sortByDesc('created_at')
            ->first();

        if ($statusLog) {
            return Carbon::parse($statusLog->created_at);
        }

        return $goal->updated_at ? Carbon::parse($goal->updated_at) : null;
    }

    private function pmManagers(ProjectGoal $goal): Collection
    {
        return collect($goal->project?->manager ?? [])
            ->where('id', '!=', $goal->user_id)
            ->values();
    }

    private function goalTitle(ProjectGoal $goal): string
    {
        return $this->cleanText($goal->title ?: $goal->outcome_goal, '成果目標');
    }

    private function cleanText(?string $text, string $fallback = '未設定'): string
    {
        $cleaned = trim(strip_tags((string) $text));

        return $cleaned !== '' ? $cleaned : $fallback;
    }
}
