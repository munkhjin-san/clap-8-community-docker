<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\shiftRecord;
use App\Models\TimecardProjectSegment;
use App\Models\timecardRecord;
use App\Services\TimeSheet\WorkReportTimeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
#[Signature('app:approve-daily-report')]
#[Description('Approve daily reports for the previous day')]
class ApproveDailyReport extends Command
{
    public function __construct(private WorkReportTimeService $workReportTimeService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDay = Carbon::yesterday()->toDateString();

        $records = timecardRecord::query()
            ->where('day', $targetDay)
            ->where('status_flag', timecardRecord::STATUS_SUBMITTED)
            ->whereHas('project_segments')
            ->whereDoesntHave('timecard_costs')
            ->whereDoesntHave('timecard_incentives')
            ->whereDoesntHave('vehicle_records')
            ->whereDoesntHave('project_case')
            ->where(function ($query) {
                $query->whereNull('car_used_project')
                    ->orWhere('car_used_project', 0);
            })
            ->where(function ($query) {
                $query->whereNull('car_mileage')
                    ->orWhere('car_mileage', 0);
            })
            ->where(function ($query) {
                $query->whereNull('gas_full_price')
                    ->orWhere('gas_full_price', 0);
            })
            ->with('project_segments')
            ->get();
        
        $approvedCount = 0;

        foreach ($records as $record) {
            $approved = DB::transaction(function () use ($record) {
                $timecard = timecardRecord::query()
                    ->with('project_segments')
                    ->whereKey($record->id)
                    ->lockForUpdate()
                    ->first();

                if (!$timecard) {
                    return false;
                }

                $shift = shiftRecord::query()
                    ->where('shift_day', $timecard->day)
                    ->where('user_id', $timecard->user_id)
                    ->first();
                
                if (!$this->canAutoApprove($timecard, $shift)) {
                    return false;
                }

                $segment = $timecard->project_segments->first();
                $segment->update([
                    'status' => TimecardProjectSegment::STATUS_APPROVED,
                    'approved_by' => null,
                    'approved_at' => now(),
                    'approval_source' => TimecardProjectSegment::APPROVAL_SOURCE_AUTO,
                ]);
                
                $timecard->update([
                    'status_flag' => timecardRecord::STATUS_APPROVED,
                    'approved_by' => null,
                ]);

                return true;
            });

            if ($approved) {
                $approvedCount++;
            }
        }

        $this->info('Daily reports for ' . $targetDay . ' (' . $approvedCount . ') have been approved.');
    }

    private function canAutoApprove(timecardRecord $timecard, ?shiftRecord $shift): bool
    {
        if (
            (int) $timecard->status_flag !== timecardRecord::STATUS_SUBMITTED
            || !$shift
            || (int) $shift->shift_type !== 1
            || blank($shift->department_id)
            || blank($shift->start_time)
            || blank($shift->end_time)
        ) {
            return false;
        }

        $segments = $timecard->project_segments;
        
        if ($segments->count() !== 1) {
            return false;
        }

        $segment = $segments->first();

        if (
            (int) $segment->project_id !== (int) $shift->department_id
            || ($segment->segment_type ?? TimecardProjectSegment::TYPE_WORK) !== TimecardProjectSegment::TYPE_WORK
            || !in_array($segment->status, [TimecardProjectSegment::STATUS_SUBMITTED, TimecardProjectSegment::STATUS_APPROVED], true)
            || $this->hasReportDetails($segment)
            || $this->hasLegacyReportDetails($timecard)
        ) {
            return false;
        }

        $shiftStart = $this->workReportTimeService->normalizeTime($shift->start_time);
        $shiftEnd = $this->workReportTimeService->normalizeTime($shift->end_time);

        return $this->sameTime($segment->start_time, $shiftStart)
            && $this->sameTime($segment->end_time, $shiftEnd)
            && $this->sameTime($timecard->edit_start_time ?: $timecard->start_time, $shiftStart)
            && $this->sameTime($timecard->edit_end_time ?: $timecard->end_time, $shiftEnd);
    }

    private function hasLegacyReportDetails(timecardRecord $timecard): bool
    {
        return (int) ($timecard->car_mileage ?? 0) > 0
            || (int) ($timecard->gas_full_price ?? 0) > 0
            || (int) ($timecard->car_used_project ?? 0) > 0
            || filled($timecard->training_start_time)
            || filled($timecard->training_end_time)
            || (int) ($timecard->over_time ?? 0) > 0
            || (int) ($timecard->late_time ?? 0) > 0
            || (int) ($timecard->night_over_time ?? 0) > 0
            || $timecard->timecard_costs()->exists()
            || $timecard->timecard_incentives()->exists()
            || $timecard->vehicle_records()->exists()
            || $timecard->project_case()->exists();
    }

    private function hasReportDetails(TimecardProjectSegment $segment): bool
    {
        $details = collect($segment->details ?? [])
            ->filter(fn ($detail) => $detail !== 'comment')
            ->values();

        if ($details->isNotEmpty()) {
            return true;
        }

        return $this->hasMeaningfulDetailValue($segment->detail_values ?? []);
    }

    private function hasMeaningfulDetailValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $childValue) {
                if ($this->hasMeaningfulDetailValue($childValue)) {
                    return true;
                }
            }

            return false;
        }

        if (is_numeric($value)) {
            return (float) $value > 0;
        }

        return filled($value);
    }

    private function sameTime(?string $left, ?string $right): bool
    {
        return $this->workReportTimeService->normalizeTime($left) === $this->workReportTimeService->normalizeTime($right);
    }
}
