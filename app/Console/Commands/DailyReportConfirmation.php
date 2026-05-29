<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\timecardRecord;
use App\Models\shiftRecord;
use App\Models\User;
use App\Mail\DailyReportReminder;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

#[Signature('timesheet:daily-report-confirmation')]
#[Description('Find working shifts (shift_type=1) in the current and last month that have no submitted timecard record (null or status_flag != 1), notify users by email, and raise an incident for 2+ missing days')]
class DailyReportConfirmation extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ReportService $reportService)
    {
        $yesterday         = Carbon::now()->subDay()->toDateString();
        $dayBeforeYesterday = Carbon::now()->subDays(2)->toDateString();

        $shifts = shiftRecord::query()
            ->where('shift_records.shift_type', 1)
            ->whereIn('shift_records.shift_day', [$yesterday, $dayBeforeYesterday])
            ->leftJoin('timecard_records', function ($join) {
                $join->on('timecard_records.day', '=', 'shift_records.shift_day')
                     ->on('timecard_records.user_id', '=', 'shift_records.user_id')
                     ->whereNull('timecard_records.deleted_at');
            })
            ->where(function ($query) {
                $query->whereNull('timecard_records.id')
                      ->orWhereNotIn('timecard_records.status_flag', [1, 2]);
            })
            ->select('shift_records.user_id', 'shift_records.shift_day')
            ->get();

        $byDay = $shifts->groupBy('shift_day');

        $warningUsers  = collect($byDay->get($yesterday,        collect()))->pluck('user_id');
        $incidentUsers = collect($byDay->get($dayBeforeYesterday, collect()))->pluck('user_id');

        $this->info("Yesterday ({$yesterday}): {$warningUsers->count()} warning user(s).");
        $this->info("Day before yesterday ({$dayBeforeYesterday}): {$incidentUsers->count()} incident user(s).");

        // Warning: yesterday missing — send email to each user
        foreach ($warningUsers as $userId) {
            $user = User::select('id', 'name', 'email')->find($userId);
            if (!$user) continue;

            $validEmail = filter_var($user->email, FILTER_VALIDATE_EMAIL);
            if ($validEmail) {
                Mail::to($user->email)->send(
                    new DailyReportReminder($user, 1, [$yesterday], false)
                );
            }
            $this->line("  [WARNING]  user_id={$userId}  name={$user->name}  shift_day={$yesterday}");
        }

        // Incident: day before yesterday missing — send email + collect for board
        $incidentLines = [];
        foreach ($incidentUsers as $userId) {
            // $user = User::select('id', 'name', 'email')->find($userId);
            // if (!$user) continue;

            // $validEmail = filter_var($user->email, FILTER_VALIDATE_EMAIL);
            // if ($validEmail) {
            //     Mail::to($user->email)->send(
            //         new DailyReportReminder($user, 2, [$dayBeforeYesterday], true)
            //     );
            // }

            $incidentLines[] = "{$user->name} さんが {$dayBeforeYesterday} の日報を未申請です。";
            $this->line("  [INCIDENT] user_id={$userId}  name={$user->name}  shift_day={$dayBeforeYesterday}");
        }

        if (!empty($incidentLines)) {
            $boardMessage = $this->buildIncidentBoardMessage($incidentLines);
            $reportService->sendRawMessage(610, 3532, $boardMessage);
        }

        $this->info('Daily report confirmation complete.');

        return self::SUCCESS;
    }

    private function buildIncidentBoardMessage(array $lines): string
    {
        $body = implode("\n", $lines);

        return <<<EOT
        [To:全員:]
        【日報未申請インシデント】

        {$body}

        ご確認のうえ、対応をお願いいたします。
        EOT;
    }
}
