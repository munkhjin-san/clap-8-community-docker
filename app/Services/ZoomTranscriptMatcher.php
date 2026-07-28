<?php

namespace App\Services;

use App\Models\CalendarMeetingTranscript;
use App\Models\CalendarRecord;
use Carbon\Carbon;

class ZoomTranscriptMatcher
{
    public function match(CalendarMeetingTranscript $transcript): ?CalendarRecord
    {
        if (! $transcript->meeting_start_time) {
            return null;
        }

        $timezone = config('app.timezone', 'Asia/Tokyo');
        $meetingStart = Carbon::parse($transcript->meeting_start_time)->setTimezone($timezone);

        return CalendarRecord::query()
            ->where('zoom_id', (string) $transcript->meeting_id)
            ->whereDate('date_start', $meetingStart->toDateString())
            ->get()
            ->sortBy(function (CalendarRecord $record) use ($meetingStart, $timezone): int {
                return (int) abs(
                    Carbon::parse($record->date_start)
                        ->setTimezone($timezone)
                        ->diffInSeconds($meetingStart, false)
                );
            })
            ->first();
    }
}
