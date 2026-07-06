<?php

namespace App\Services\TimeSheet;

use App\Models\User;
use App\Models\shiftRecord;
use App\Models\shiftType;

class ShiftService
{
    public function getShiftData(int $userId, int $year, int $month, int $requestedShiftType = null): array
    {
        $evaluationDate = $this->resolveEvaluationDate($year, $month);

        $user = $this->getUser($userId, $evaluationDate);

        $shiftRecords = $this->getShiftRecords($userId, $year, $month, $requestedShiftType);

        $usedSpecialHoliday = $this->countSpecialHoliday($userId, $year);
        return [
            'shift_record' => $shiftRecords,
            'shift_type' => $this->getAvailableShiftTypes($user),
            'odaCheck' => $this->hasOdaShift($userId, $year),
            'used_special_holiday' => $usedSpecialHoliday,
            'remaining_special_holiday' => $this->remainingSpecialHoliday(
                $user->general_position,
                $usedSpecialHoliday
            ),
            'user_work_minutes_per_day' => $user->work_time_day,
            'total_holidays' => $this->calculateTotalHolidays($userId, $year, $month, $user->work_time_day),
            'work_time_data' => app()->make(\App\Services\SharedService::class)
                ->work_days_calculator($year, $month, $user),
        ];
    }

    private function resolveEvaluationDate(int $year, int $month): string
    {
        return ($month >= 2 && $month <= 7)
            ? "{$year}-02-01"
            : "{$year}-08-01";
    }

    private function getUser(int $userId, string $evaluationDate): User
    {
        return User::with([
                'evaluation' => fn ($q) => $q->where('date', $evaluationDate)
            ])
            ->select([
                'id',
                'user_code',
                'position_id',
                'general_position',
                'work_type',
                'work_time_day',
            ])
            ->findOrFail($userId);
    }

    private function getShiftRecords(int $userId, int $year, int $month, ?int $requestedShiftType)
    {
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->whereYear('shift_day', $year)
            ->whereMonth('shift_day', $month)
            ->when($requestedShiftType === shiftType::idFor(shiftType::CATEGORY_PLANNED_PAID_LEAVE), fn ($q) => $q->whereIn('shift_type', shiftType::idsFor(shiftType::CATEGORY_PLANNED_PAID_LEAVE)))
            ->with([
                'shiftType:id,name,abbreviation,value,full_day,category,hours',
                'department:id,name',
                'old_shift' => fn ($q) =>
                    $q->withTrashed()
                      ->select('id', 'shift_day', 'shift_type', 'department_id')
                      ->with(['shiftType:id,name,abbreviation,value,full_day,category,hours'])
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    private function hasOdaShift(int $userId, int $year): bool
    {
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->whereYear('shift_day', $year)
            ->where('shift_type', shiftType::idFor(shiftType::CATEGORY_SPECIAL_LEAVE_ODA))
            ->exists();
    }
    private function remainingSpecialHoliday(?string $userPosition, int $usedSpecialHoliday): int
    {
        $specialHolidayLimits = [
            '一般職' => 0,
            'A' => 0,
            'B' => 0,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'F' => 6,
            'G' => 7,
        ];

        $limit = $specialHolidayLimits[$userPosition] ?? 0;

        return max(0, $limit - $usedSpecialHoliday);
    }
    private function countSpecialHoliday(int $userId, int $year): int
    {
        // 特別休暇 (special_holiday, glowd id 27) taken this year — feeds the
        // general_position quota in remainingSpecialHoliday(). idsFor returns []
        // in envs without a special_holiday shift type, so this yields 0 there.
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->whereYear('shift_day', $year)
            ->whereIn('shift_type', shiftType::idsFor(shiftType::CATEGORY_SPECIAL_HOLIDAY))
            ->count();
    }

    private function getAvailableShiftTypes(User $user)
    {
        // Selectable shift types are now configured per community role
        // (community_role_shift_type), replacing the old position_id rules. The
        // old general_position (C-G) branch gated special_holiday (id 27) — per
        // product decision that gate is buried for now; 27 is assigned to every
        // role via migration and the remainingSpecialHoliday quota still limits
        // bookings. work_type and UNUSED_IDS remain orthogonal code-side filters.
        return shiftType::query()
            ->where('active', 1) // inactive types are hidden from selection (still counted in calc)
            ->whereIn('id', $this->selectableShiftTypeIds($user))
            ->when(
                $user->work_type == 0,
                fn ($q) => $q->where('id', '!=', shiftType::idFor(shiftType::CATEGORY_LEGAL_HOLIDAY))
            )
            ->whereNotIn('id', shiftType::UNUSED_IDS)
            ->get();
    }

    /**
     * Shift type ids the user's community role may select. Falls back to all
     * shift types if the user has no resolvable membership (edge: never in a
     * normal authenticated request, where ResolveActiveCommunity has run).
     */
    private function selectableShiftTypeIds(User $user): array
    {
        $roleId = app(\App\Services\Community\CommunityResolver::class)->membershipFor($user)?->community_role_id;

        if (!$roleId) {
            return shiftType::query()->pluck('id')->all();
        }

        return \Illuminate\Support\Facades\DB::table('community_role_shift_type')
            ->where('community_role_id', $roleId)
            ->pluck('shift_type_id')
            ->all();
    }

    private function calculateTotalHolidays(int $userId, int $year, int $month, int $workMinutesPerDay): int
    {
        $shifts = shiftRecord::query()
            ->where('user_id', $userId)
            ->whereYear('shift_day', $year)
            ->whereMonth('shift_day', '!=', $month)
            ->whereIn('shift_type', shiftType::idsFor([shiftType::CATEGORY_DAY_OFF, shiftType::CATEGORY_LEGAL_HOLIDAY, shiftType::CATEGORY_HOLIDAY_WORK]))
            ->with('shiftType:id,value,full_day,category')
            ->get();

        return $shifts->sum(function ($shift) use ($workMinutesPerDay) {
            $type = $shift->shiftType;

            if (! $type) return 0;

            if ($type->full_day == 2 || $type->category === shiftType::CATEGORY_DAY_OFF) {
                return $workMinutesPerDay;
            }

            if ($type->full_day == 1) {
                return $workMinutesPerDay / 2;
            }

            return $type->value ?? 0;
        });
    }
}
