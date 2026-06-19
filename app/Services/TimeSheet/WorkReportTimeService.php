<?php

namespace App\Services\TimeSheet;

use App\Models\shiftRecord;
use App\Models\TimecardProjectSegment;
use App\Models\timecardRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class WorkReportTimeService
{
    private const ALLOWED_PROJECT_DETAILS = [
        'expenses',
        'mileage',
        'allowance',
        'vehicle',
        'incident',
        'comment',
        'overtime',
        'actual',
    ];

    public function timeToMinutes(?string $time): ?int
    {
        if (blank($time)) {
            return null;
        }

        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return null;
        }

        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }

    public function normalizeTime(?string $time): ?string
    {
        $minutes = $this->timeToMinutes($time);
        if ($minutes === null) {
            return null;
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function minutesBetweenTimes(?string $startTime, ?string $endTime): int
    {
        $start = $this->timeToMinutes($startTime);
        $end = $this->timeToMinutes($endTime);

        if ($start === null || $end === null) {
            return 0;
        }

        return $end >= $start ? $end - $start : $end + 1440 - $start;
    }

    public function sortProjectSegmentsByTime(iterable $segments, ?string $requestedStartTime = null, ?string $requestedEndTime = null): array
    {
        $segments = collect($segments)->values();
        $anchorMinutes = $this->projectSegmentSortAnchor($segments, $requestedStartTime, $requestedEndTime);

        return $segments
            ->sort(function ($first, $second) use ($anchorMinutes) {
                $firstStart = $this->projectSegmentStartOffset($first, $anchorMinutes);
                $secondStart = $this->projectSegmentStartOffset($second, $anchorMinutes);

                if ($firstStart !== $secondStart) {
                    return $firstStart <=> $secondStart;
                }

                $firstEnd = $this->projectSegmentEndOffset($first, $anchorMinutes);
                $secondEnd = $this->projectSegmentEndOffset($second, $anchorMinutes);

                if ($firstEnd !== $secondEnd) {
                    return $firstEnd <=> $secondEnd;
                }

                return ((int) ($first['project_id'] ?? 0)) <=> ((int) ($second['project_id'] ?? 0));
            })
            ->values()
            ->all();
    }

    public function buildProjectSegments(Request $request, bool $hasWorkHours): array
    {
        $hasTrainingHours = $request->filled('training_start_time') && $request->filled('training_end_time');
        $hasProjectHours = $hasWorkHours || $hasTrainingHours;

        if (!$hasProjectHours) {
            return [];
        }

        $customValues = $request->input('customValues', []);
        if (!is_array($customValues)) {
            $customValues = [];
        }

        $incomingSegments = collect($request->input('project_time_entries', []))
            ->filter(fn ($segment) => !empty($segment['project_id']) && !empty($segment['start_time']) && !empty($segment['end_time']))
            ->map(function ($segment) use ($customValues) {
                $details = $this->sanitizeProjectDetails($segment['details'] ?? []);
                $detailValues = $this->sanitizeProjectDetailValues($segment['detail_values'] ?? []);

                return [
                    'id' => filled($segment['id'] ?? null) ? (int) $segment['id'] : null,
                    'project_id' => (int) $segment['project_id'],
                    'segment_type' => $this->sanitizeSegmentType($segment['segment_type'] ?? null),
                    'start_time' => $this->normalizeTime($segment['start_time']),
                    'end_time' => $this->normalizeTime($segment['end_time']),
                    'details' => $details,
                    'detail_values' => $this->hydrateProjectDetailValuesFromCustomValues($details, $detailValues, $customValues),
                    'comment' => $this->sanitizeProjectComment($segment['comment'] ?? null),
                ];
            })
            ->filter(fn ($segment) => $segment['start_time'] !== null && $segment['end_time'] !== null)
            ->values();

        if ($incomingSegments->isEmpty() && $hasWorkHours && filled($request->department)) {
            $incomingSegments = collect([[
                'project_id' => (int) $request->department,
                'segment_type' => TimecardProjectSegment::TYPE_WORK,
                'start_time' => $this->normalizeTime($request->start_time),
                'end_time' => $this->normalizeTime($request->end_time),
                'details' => ['comment'],
                'detail_values' => [],
                'comment' => null,
            ]])->filter(fn ($segment) => $segment['start_time'] !== null && $segment['end_time'] !== null)->values();
        }

        $incomingSegments = collect($this->sortProjectSegmentsByTime($incomingSegments, $request->start_time, $request->end_time));

        $gapMinutes = 0;
        $workSegmentsForGap = $incomingSegments
            ->where('segment_type', TimecardProjectSegment::TYPE_WORK)
            ->values();
        $trainingSegmentsForGap = $incomingSegments
            ->where('segment_type', TimecardProjectSegment::TYPE_TRAINING)
            ->values();
        $workSegmentsForGap->each(function ($segment, $index) use ($workSegmentsForGap, $trainingSegmentsForGap, &$gapMinutes) {
            if ($index === 0) {
                return;
            }

            $previous = $workSegmentsForGap[$index - 1];
            $gap = $this->minutesBetweenTimes($previous['end_time'], $segment['start_time']);
            $trainingInGap = $trainingSegmentsForGap->sum(function ($trainingSegment) use ($previous, $segment) {
                return $this->overlapMinutesForTimes($previous['end_time'], $segment['start_time'], $trainingSegment['start_time'], $trainingSegment['end_time']);
            });
            $gapMinutes += max(0, $gap - $trainingInGap);
        });

        $breakMinutes = max(0, (int) ($request->breakTime ?? 0));
        $breakFromGaps = min($breakMinutes, $gapMinutes);
        $remainingBreakDeduction = max(0, $breakMinutes - $breakFromGaps);
        $rawWorkMinutesBefore = 0;
        $status = ((int) $request->status_flag) === timecardRecord::STATUS_SUBMITTED
            ? TimecardProjectSegment::STATUS_SUBMITTED
            : TimecardProjectSegment::STATUS_DRAFT;

        $segments = $incomingSegments
            ->map(function ($segment) use (&$rawWorkMinutesBefore, $remainingBreakDeduction, $status) {
                $rawMinutes = $this->minutesBetweenTimes($segment['start_time'], $segment['end_time']);
                $deduction = 0;

                if ($segment['segment_type'] === TimecardProjectSegment::TYPE_WORK) {
                    $deduction = min($rawMinutes, max(0, $remainingBreakDeduction - $rawWorkMinutesBefore));
                    $rawWorkMinutesBefore += $rawMinutes;
                }

                return [
                    'id' => $segment['id'],
                    'project_id' => $segment['project_id'],
                    'segment_type' => $segment['segment_type'],
                    'start_time' => $segment['start_time'],
                    'end_time' => $segment['end_time'],
                    'minutes' => max(0, $rawMinutes - $deduction),
                    'details' => $segment['details'],
                    'detail_values' => $segment['detail_values'],
                    'comment' => $segment['comment'],
                    'status' => $status,
                    'approved_by' => null,
                    'approved_at' => null,
                ];
            })
            ->values()
            ->all();

        return $segments;
    }

    private function projectSegmentSortAnchor(Collection $segments, ?string $requestedStartTime, ?string $requestedEndTime): ?int
    {
        $requestedStart = $this->timeToMinutes($requestedStartTime);
        $requestedEnd = $this->timeToMinutes($requestedEndTime);

        if ($requestedStart !== null && $requestedEnd !== null && $requestedStart !== $requestedEnd) {
            return $requestedStart;
        }

        $starts = $segments
            ->map(fn ($segment) => $this->timeToMinutes($segment['start_time'] ?? null))
            ->filter(fn ($minutes) => $minutes !== null)
            ->values();

        if ($starts->isEmpty()) {
            return null;
        }

        $lateStarts = $starts->filter(fn ($minutes) => $minutes >= 18 * 60);
        $hasEarlyMorningStart = $starts->filter(fn ($minutes) => $minutes < 5 * 60)->isNotEmpty();

        if ($hasEarlyMorningStart && $lateStarts->isNotEmpty()) {
            return (int) $lateStarts->min();
        }

        return null;
    }

    private function projectSegmentStartOffset(array $segment, ?int $anchorMinutes): int
    {
        $start = $this->timeToMinutes($segment['start_time'] ?? null);

        if ($start === null) {
            return PHP_INT_MAX;
        }

        if ($anchorMinutes === null) {
            return $start;
        }

        return $start >= $anchorMinutes ? $start - $anchorMinutes : $start + 1440 - $anchorMinutes;
    }

    private function projectSegmentEndOffset(array $segment, ?int $anchorMinutes): int
    {
        $startOffset = $this->projectSegmentStartOffset($segment, $anchorMinutes);

        if ($startOffset === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        return $startOffset + $this->minutesBetweenTimes($segment['start_time'] ?? null, $segment['end_time'] ?? null);
    }

    public function projectSegmentsTotal(array $projectSegments, string $segmentType): int
    {
        return collect($projectSegments)
            ->where('segment_type', $segmentType)
            ->sum('minutes');
    }

    public function hasSpecialProjectItems(Request $request, array $customValues, int $overtimeMinutes): bool
    {
        $allowance = $customValues[37] ?? [];

        return $this->hasMeaningfulCosts($request)
            || (int) ($request->car_mileage ?? 0) > 0
            || (is_array($allowance) && count(array_filter($allowance, fn ($value) => filled($value))) > 0)
            || filled($customValues[40] ?? null)
            || (int) ($customValues[44] ?? 0) === 1
            || collect($request->input('project_time_entries', []))->contains(fn ($segment) => in_array('vehicle', (array) ($segment['details'] ?? []), true))
            || $this->hasSubmittedActualResults($request)
            || $overtimeMinutes > 0;
    }

    public function shouldAutoApproveCleanSingleProject(
        Request $request,
        ?shiftRecord $shift,
        array $projectSegments,
        array $customValues,
        bool $hasTrainingHours,
        int $overtimeMinutes
    ): bool {
        $workSegments = collect($projectSegments)
            ->where('segment_type', TimecardProjectSegment::TYPE_WORK)
            ->values();

        if ((int) $request->status_flag !== timecardRecord::STATUS_SUBMITTED || $hasTrainingHours || $workSegments->count() !== 1 || !$shift?->department_id) {
            return false;
        }

        $segment = $workSegments[0];
        $shiftStart = $this->normalizeTime($shift->start_time);
        $shiftEnd = $this->normalizeTime($shift->end_time);
        $segmentMatchesShift = (int) $segment['project_id'] === (int) $shift->department_id
            && $segment['start_time'] === $shiftStart
            && $segment['end_time'] === $shiftEnd;

        return $segmentMatchesShift && !$this->hasSpecialProjectItems($request, $customValues, $overtimeMinutes);
    }

    private function hasMeaningfulCosts(Request $request): bool
    {
        return collect($request->input('costsValues', []))->contains(function ($cost) {
            return filled(Arr::get($cost, 'content'))
                || filled(Arr::get($cost, 'expenses'))
                || filled(Arr::get($cost, 'file_path'))
                || filled(Arr::get($cost, 'departure_place'))
                || filled(Arr::get($cost, 'arrival_place'))
                || filled(Arr::get($cost, 'merchant_name'))
                || filled(Arr::get($cost, 'receipt_date'));
        });
    }

    private function hasSubmittedActualResults(Request $request): bool
    {
        return collect($request->input('actual_results', []))->contains(function ($row) {
            return filled(Arr::get($row, 'value')) && (float) Arr::get($row, 'value') > 0;
        });
    }

    private function sanitizeProjectDetails(mixed $details): array
    {
        if (!is_array($details)) {
            return [];
        }

        return collect($details)
            ->filter(fn ($detail) => in_array($detail, self::ALLOWED_PROJECT_DETAILS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function sanitizeProjectComment(mixed $comment): ?string
    {
        if (blank($comment)) {
            return null;
        }

        return trim((string) $comment);
    }

    private function sanitizeProjectDetailValues(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $sanitized = [];

        if (isset($values['allowance']) && is_array($values['allowance'])) {
            $sanitized['allowance'] = collect($values['allowance'])
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values()
                ->all();
        }

        if (isset($values['allowance_labels']) && is_array($values['allowance_labels'])) {
            $sanitized['allowance_labels'] = collect($values['allowance_labels'])
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => trim((string) $value))
                ->unique()
                ->values()
                ->all();
        }

        if (filled($values['incident'] ?? null)) {
            $sanitized['incident'] = trim((string) $values['incident']);
        }

        if (filled($values['overtime'] ?? null)) {
            $sanitized['overtime'] = trim((string) $values['overtime']);
        }

        if (isset($values['mileage']) && is_array($values['mileage'])) {
            $mileage = (int) ($values['mileage']['mileage'] ?? 0);
            $gasFullPrice = (int) ($values['mileage']['gas_full_price'] ?? 0);
            $gasConsumption = $values['mileage']['gas_consumption'] ?? null;
            $gasUnitPrice = $values['mileage']['gas_unit_price'] ?? null;

            if ($mileage > 0 || $gasFullPrice > 0) {
                $sanitized['mileage'] = [
                    'mileage' => max(0, $mileage),
                    'gas_full_price' => max(0, $gasFullPrice),
                    'gas_consumption' => is_numeric($gasConsumption) ? (float) $gasConsumption : null,
                    'gas_unit_price' => is_numeric($gasUnitPrice) ? (float) $gasUnitPrice : null,
                ];
            }
        }

        if (isset($values['vehicle']) && is_array($values['vehicle'])) {
            $vehicle = $this->sanitizeVehicleData($values['vehicle']);
            if ($vehicle !== null) {
                $sanitized['vehicle'] = $vehicle;
            }
        }

        return $sanitized;
    }

    private function sanitizeVehicleData(array $values): ?array
    {
        if (!filled($values['vehicle'] ?? null)) {
            return null;
        }

        $beforeValue = $values['alcohol_before_value'] ?? null;
        $afterValue = $values['alcohol_after_value'] ?? null;

        return [
            'id' => filled($values['id'] ?? null) ? (int) $values['id'] : null,
            'vehicle' => (int) $values['vehicle'],
            'alcohol_before_time' => $this->normalizeTime($values['alcohol_before_time'] ?? null),
            'alcohol_after_time' => $this->normalizeTime($values['alcohol_after_time'] ?? null),
            'alcohol_before_value' => is_numeric($beforeValue) ? (float) $beforeValue : null,
            'alcohol_after_value' => is_numeric($afterValue) ? (float) $afterValue : null,
            'confirm_before_user' => filled($values['confirm_before_user'] ?? null) ? (int) $values['confirm_before_user'] : null,
            'confirm_after_user' => filled($values['confirm_after_user'] ?? null) ? (int) $values['confirm_after_user'] : null,
        ];
    }

    private function hydrateProjectDetailValuesFromCustomValues(array $details, array $detailValues, array $customValues): array
    {
        if (in_array('allowance', $details, true) && empty($detailValues['allowance'])) {
            $allowances = $this->customValue($customValues, 37);
            if (is_array($allowances)) {
                $detailValues['allowance'] = collect($allowances)
                    ->filter(fn ($value) => filled($value))
                    ->map(fn ($value) => (int) $value)
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        if (in_array('incident', $details, true) && blank($detailValues['incident'] ?? null)) {
            $incident = $this->customValue($customValues, 40);
            if (filled($incident)) {
                $detailValues['incident'] = trim((string) $incident);
            }
        }

        if (in_array('overtime', $details, true) && blank($detailValues['overtime'] ?? null)) {
            $overtime = $this->customValue($customValues, 42);
            if (filled($overtime)) {
                $detailValues['overtime'] = trim((string) $overtime);
            }
        }

        return $detailValues;
    }

    private function customValue(array $customValues, int $typeId): mixed
    {
        return $customValues[$typeId] ?? $customValues[(string) $typeId] ?? null;
    }

    private function sanitizeSegmentType(?string $segmentType): string
    {
        return in_array($segmentType, [TimecardProjectSegment::TYPE_WORK, TimecardProjectSegment::TYPE_TRAINING], true)
            ? $segmentType
            : TimecardProjectSegment::TYPE_WORK;
    }

    private function overlapMinutesForTimes(?string $firstStartTime, ?string $firstEndTime, ?string $secondStartTime, ?string $secondEndTime): int
    {
        $firstStart = $this->timeToMinutes($firstStartTime);
        $firstEnd = $this->timeToMinutes($firstEndTime);
        $secondStart = $this->timeToMinutes($secondStartTime);
        $secondEnd = $this->timeToMinutes($secondEndTime);

        if ($firstStart === null || $firstEnd === null || $secondStart === null || $secondEnd === null) {
            return 0;
        }

        $firstEnd = $firstEnd >= $firstStart ? $firstEnd : $firstEnd + 1440;
        $secondEnd = $secondEnd >= $secondStart ? $secondEnd : $secondEnd + 1440;

        if ($secondEnd <= $firstStart) {
            $secondStart += 1440;
            $secondEnd += 1440;
        } elseif ($secondStart >= $firstEnd) {
            $secondStart -= 1440;
            $secondEnd -= 1440;
        }

        return max(0, min($firstEnd, $secondEnd) - max($firstStart, $secondStart));
    }
}
