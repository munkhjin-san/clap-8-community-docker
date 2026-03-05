<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use App\Models\attendanceRecord;
use App\Models\timecardRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AttendanceClose implements ShouldQueue
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
        $target = Carbon::now()->subMonthNoOverflow();
        $year = $target->year;
        $month = $target->month;
        $yearMonth = $target->format('Y-m');
        $runId = (string) Str::uuid();
        $start = microtime(true);
        // 1) Users who should be checked
        $users = User::query()
            ->where('position_id', 15)
            ->where('retire', 0)
            ->select('id', 'name', 'user_code', 'work_type')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $userIds = $users->pluck('id');

        // 2) Find who already has timecards in that month (single query)
        $hasTimecardIds = timecardRecord::query()
            ->whereYear('day', $year)
            ->whereMonth('day', $month)
            ->whereIn('user_id', $userIds)
            ->distinct()
            ->pluck('user_id')
            ->all();

        $missingTimecardUsers = $users->whereNotIn('id', $hasTimecardIds);

        if ($missingTimecardUsers->isEmpty()) {
            return;
        }

        $missingIds = $missingTimecardUsers->pluck('id');

        // 3) Who already has attendance record for that year-month (single query)
        $alreadyAttendanceIds = attendanceRecord::query()
            ->where('date_year_month', $yearMonth)
            ->whereIn('user_id', $missingIds)
            ->pluck('user_id')
            ->all();

        $toCreate = $missingTimecardUsers->whereNotIn('id', $alreadyAttendanceIds);

        if ($toCreate->isEmpty()) {
            return;
        }

        $nowTs = now();

        // 4) Prepare bulk rows
        $rows = $toCreate->map(function ($user) use ($yearMonth, $nowTs) {
            $workTypeLabel = ((int) $user->work_type === 0) ? 'フレックス' : '通常';

            return [
                'user_id' => $user->id,
                'confirmed_by' => 608,
                'name' => $user->name,
                'user_code' => $user->user_code,
                'date_year_month' => $yearMonth,
                'work_type' => $workTypeLabel,
                'created_at' => $nowTs,
                'updated_at' => $nowTs,
            ];
        })->all();

        // 5) Upsert: create missing rows, ignore existing
        attendanceRecord::query()->upsert(
            $rows,
            uniqueBy: ['user_id', 'date_year_month'],
            update: [] // don’t update existing rows
        );

        Log::info('attendance:ensure-monthly end', [
            'run_id' => $runId,
            'target_year_month' => $yearMonth,
            'users_total' => $users->count(),
            'users_missing_timecard' => $missingTimecardUsers->count(),
            'attendance_already_exists' => count($alreadyAttendanceIds),
            'attendance_to_create' => is_array($rows) ? count($rows) : $rows->count(),
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
        ]);
    }
}
