<?php

namespace App\Services;

use App\Jobs\DownloadZoomTranscript;
use App\Models\CalendarMeetingTranscript;
use App\Models\ZoomAccount;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ZoomTranscriptIngestService
{
    public function __construct(
        private readonly ZoomTranscriptMatcher $matcher,
    ) {}

    public function ingest(ZoomAccount $zoomAccount, array $payload): CalendarMeetingTranscript
    {
        $payloadAccountId = data_get($payload, 'account_id');

        if (! is_string($payloadAccountId)
            || ! hash_equals((string) $zoomAccount->account_id, $payloadAccountId)) {
            throw ValidationException::withMessages([
                'message' => 'Webhook account does not match the configured Zoom account.',
            ]);
        }

        $object = data_get($payload, 'object');

        if (! is_array($object)) {
            throw ValidationException::withMessages([
                'message' => 'Zoom transcript payload is missing its object.',
            ]);
        }

        foreach (['meeting_id', 'meeting_uuid', 'file_id', 'meeting_start_time'] as $field) {
            if (! filled($object[$field] ?? null)) {
                throw ValidationException::withMessages([
                    'message' => "Zoom transcript payload is missing {$field}.",
                ]);
            }
        }

        $transcript = CalendarMeetingTranscript::firstOrNew([
            'zoom_account_id' => $zoomAccount->id,
            'file_id' => (string) $object['file_id'],
        ]);

        $transcript->fill([
            'meeting_id' => (string) $object['meeting_id'],
            'meeting_uuid' => (string) $object['meeting_uuid'],
            'attach_type' => (string) ($object['attach_type'] ?? 'durable_transcript'),
            'meeting_start_time' => Carbon::parse((string) $object['meeting_start_time'])
                ->setTimezone(config('app.timezone', 'Asia/Tokyo')),
        ]);

        if (! $transcript->exists) {
            $transcript->status = CalendarMeetingTranscript::STATUS_PENDING;
        }

        if (! $transcript->calendar_record_id) {
            $transcript->calendar_record_id = $this->matcher->match($transcript)?->id;
        }

        $transcript->save();

        if (in_array($transcript->status, [
            CalendarMeetingTranscript::STATUS_PENDING,
            CalendarMeetingTranscript::STATUS_FAILED,
        ], true)) {
            DownloadZoomTranscript::dispatch($transcript->id)->afterResponse();
        }

        return $transcript;
    }
}
