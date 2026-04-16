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
            ->when($requestedShiftType === 3, fn ($q) => $q->where('shift_type', 3))
            ->with([
                'shiftType:id,name,abbreviation,value,full_day',
                'old_shift' => fn ($q) =>
                    $q->withTrashed()
                      ->select('id', 'shift_day', 'shift_type')
                      ->with(['shiftType:id,name,abbreviation,value'])
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    private function hasOdaShift(int $userId, int $year): bool
    {
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->whereYear('shift_day', $year)
            ->where('shift_type', 16)
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
        
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->whereYear('shift_day', $year)
            ->where('shift_type', 27)
            ->count();
    }

    private function getAvailableShiftTypes(User $user)
    {
        return shiftType::query()
            ->when(
                $user->position_id == 15,
                fn ($q) => $q->whereIn('id', [5, 1]),
                fn ($q) => $this->applyPositionRules($q, $user)
            )
            ->when(
                $user->work_type == 0,
                fn ($q) => $q->where('id', '!=', shiftType::LEGAL_HOLIDAY_ID)
            )
            ->when(
                $user->position_id == 12,
                fn ($q) => $q->whereNotIn('id', [19,20,21,22,23,24,25,26])
            )
            ->get();
    }

    private function applyPositionRules($query, User $user)
    {
        return $query->when(
            $user->position_id <= 11 || $user->position_id == 16,
            function ($q) use ($user) {
                if ($this->isPrivilegedPosition($user)) {
                    return $q;
                }

                return $q->whereNotIn('id', [17, 27]);
            },
            fn ($q) => $q->whereNotIn('id', [14, 15, 16, 27])
        );
    }

    private function isPrivilegedPosition(User $user): bool
    {
        return in_array($user->general_position, ['C', 'D', 'E', 'F', 'G'], true);
    }

    private function calculateTotalHolidays(int $userId, int $year, int $month, int $workMinutesPerDay): int
    {
        $shifts = shiftRecord::query()
            ->where('user_id', $userId)
            ->whereYear('shift_day', $year)
            ->whereMonth('shift_day', '!=', $month)
            ->whereIn('shift_type', [0, 18, 19, 20, 21, 22, 23, 24, 25, 26])
            ->with('shiftType:id,value,full_day')
            ->get();

        return $shifts->sum(function ($shift) use ($workMinutesPerDay) {
            $type = $shift->shiftType;

            if (! $type) return 0;

            if ($type->full_day == 2 || $type->id == 0) {
                return $workMinutesPerDay;
            }

            if ($type->full_day == 1) {
                return $workMinutesPerDay / 2;
            }

            return $type->value ?? 0;
        });
    }
}