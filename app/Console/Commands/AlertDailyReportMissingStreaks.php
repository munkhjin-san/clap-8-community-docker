<?php

namespace App\Console\Commands;

use App\Models\IncidentCandidate;
use App\Models\TimecardMissingOccurrence;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AlertDailyReportMissingStreaks extends Command
{
    protected $signature = 'timesheet:daily-report-missing-streaks';

    protected $description = 'Alert PMs when a user has three missed daily report occurrences within 30 days';

    private const BOARD_ID = 3532;
    private const OVERRIDE_USER_ID = 610;
    private const ALERT_THRESHOLD = 3;
    private const WINDOW_DAYS = 30;

    public function handle(ReportService $reportService): int
    {
        $now = Carbon::now();
        $alertGroups = $this->alertableGroups($now);

        if ($alertGroups->isEmpty()) {
            $this->info('No daily report missing streaks to alert.');

            return self::SUCCESS;
        }

        $this->upsertStreakCandidates($alertGroups);

        $message = $this->buildBoardMessage($alertGroups);
        $reportService->sendRawMessage(self::OVERRIDE_USER_ID, self::BOARD_ID, $message);
        $this->markPmAlertsSent($alertGroups, $now);

        $this->info(sprintf(
            'Sent daily report missing streak PM alert for %d user(s).',
            $alertGroups->count()
        ));
        Log::info('Sent daily report missing streak PM alert', [
            'count' => $alertGroups->count(),
        ]);
        return self::SUCCESS;
    }

    private function alertableGroups(Carbon $now): Collection
    {
        $windowStart = $now->copy()->subDays(self::WINDOW_DAYS)->toDateString();

        return TimecardMissingOccurrence::query()
            ->with([
                'user:id,name,position_id',
                'shiftRecord:id,department_id,shift_day',
            ])
            ->whereNull('pm_alerted_at')
            ->whereDate('counted_date', '>=', $windowStart)
            ->orderBy('user_id')
            ->orderBy('counted_date')
            ->get()
            ->groupBy('user_id')
            ->filter(fn (Collection $occurrences) => $occurrences->count() >= self::ALERT_THRESHOLD)
            ->map(function (Collection $occurrences) {
                return [
                    'user' => $occurrences->first()?->user,
                    'occurrences' => $occurrences->values(),
                    'managers' => $this->projectManagersFor($occurrences),
                ];
            })
            ->values();
    }

    private function projectManagersFor(Collection $occurrences): Collection
    {
        $projectIds = $occurrences
            ->pluck('shiftRecord.department_id')
            ->filter()
            ->unique()
            ->values();

        if ($projectIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->select('users.id', 'users.name')
            ->join('project_members', 'project_members.user_id', '=', 'users.id')
            ->whereIn('project_members.project_id', $projectIds)
            ->where('project_members.authority', 1)
            ->where('users.retire', 0)
            ->whereNull('users.deleted_at')
            ->orderBy('users.name')
            ->get()
            ->unique('id')
            ->values();
    }

    private function buildBoardMessage(Collection $alertGroups): string
    {
        $mentions = $alertGroups
            ->flatMap(fn (array $group) => $group['managers'])
            ->unique('id')
            ->pluck('name')
            ->filter()
            ->map(fn (string $name) => '[To:' . $this->cleanText($name) . ':]')
            ->implode("\n");

        $lines = $alertGroups
            ->map(function (array $group) {
                $occurrences = $group['occurrences'];
                $managers = $group['managers'];
                $reportDates = $occurrences
                    ->map(fn (TimecardMissingOccurrence $occurrence) => $this->dateString($occurrence->report_date))
                    ->implode(', ');

                return sprintf(
                    "・対象者：%s\n  未申請回数：%d回\n  未申請日：%s\n  担当PM：%s",
                    $this->cleanText($group['user']?->name, '未設定'),
                    $occurrences->count(),
                    $reportDates,
                    $managers->pluck('name')->filter()->map(fn (string $name) => $this->cleanText($name))->implode('、') ?: '未設定'
                );
            })
            ->implode("\n\n");

        return trim(<<<EOT
{$mentions}
【日報未申請 3回アラート】
以下の社員は、過去30日以内に日報未申請が3回以上発生しています。
担当PMは本日中に、懲罰区分「指導」で指導インシデントレポートを作成し、未申請理由と再発防止を確認してください。

{$lines}
EOT);
    }

    private function upsertStreakCandidates(Collection $alertGroups): void
    {
        foreach ($alertGroups as $group) {
            $user = $group['user'];

            if (!$user) {
                continue;
            }

            $occurrences = $group['occurrences'];

            // Latest missed day drives the scope project and the dedup key.
            $ordered = $occurrences
                ->sortBy(fn (TimecardMissingOccurrence $occurrence) => $this->dateString($occurrence->report_date))
                ->values();
            $latest = $ordered->last();

            $projectId = optional($latest?->shiftRecord)->department_id;

            $projectIds = $occurrences
                ->pluck('shiftRecord.department_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            IncidentCandidate::firstOrCreate(
                [
                    'source_type' => IncidentCandidate::SOURCE_DAILY_REPORT_STREAK,
                    'subject_user_id' => $user->id,
                    'dedup_key' => $this->dateString($latest?->report_date),
                ],
                [
                    'project_record_id' => $projectId,
                    'audience' => $this->audienceForSubject($user),
                    'status' => IncidentCandidate::STATUS_PENDING,
                    'context' => [
                        'missed_dates' => $ordered
                            ->map(fn (TimecardMissingOccurrence $occurrence) => $this->dateString($occurrence->report_date))
                            ->all(),
                        'missed_count' => $occurrences->count(),
                        'shift_record_ids' => $occurrences->pluck('shift_record_id')->filter()->values()->all(),
                        'occurrence_ids' => $occurrences->pluck('id')->values()->all(),
                        'project_ids' => $projectIds,
                        'manager_names' => $group['managers']->pluck('name')->filter()->values()->all(),
                    ],
                ]
            );
        }
    }

    private function audienceForSubject(?User $user): string
    {
        $positionId = $user?->position_id;

        // position_id <= 6 means the subject is a PM (==6) or an executive (<6),
        // so the alert escalates to directors instead of a PM.
        return ($positionId !== null && (int) $positionId <= 6)
            ? IncidentCandidate::AUDIENCE_DIRECTOR
            : IncidentCandidate::AUDIENCE_PM;
    }

    private function markPmAlertsSent(Collection $alertGroups, Carbon $now): void
    {
        $occurrenceIds = $alertGroups
            ->flatMap(fn (array $group) => $group['occurrences']->pluck('id'))
            ->unique()
            ->values();

        if ($occurrenceIds->isEmpty()) {
            return;
        }

        TimecardMissingOccurrence::query()
            ->whereIn('id', $occurrenceIds)
            ->update([
                'pm_alerted_at' => $now,
                'updated_at' => $now,
            ]);
    }

    private function dateString(mixed $date): string
    {
        return $date instanceof Carbon
            ? $date->toDateString()
            : Carbon::parse($date)->toDateString();
    }

    private function cleanText(?string $text, string $fallback = ''): string
    {
        $cleaned = trim(strip_tags((string) $text));

        return $cleaned !== '' ? $cleaned : $fallback;
    }
}
