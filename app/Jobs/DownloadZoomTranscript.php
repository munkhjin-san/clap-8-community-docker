<?php

namespace App\Jobs;

use App\Models\CalendarMeetingTranscript;
use App\Services\ZoomApiService;
use App\Services\ZoomTranscriptMatcher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DownloadZoomTranscript implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 8;

    public int $timeout = 180;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $transcriptId,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600, 900, 1800];
    }

    public function uniqueId(): string
    {
        return (string) $this->transcriptId;
    }

    public function handle(
        ZoomApiService $zoomApi,
        ZoomTranscriptMatcher $matcher,
    ): void {
        $transcript = CalendarMeetingTranscript::query()
            ->with('zoomAccount')
            ->find($this->transcriptId);

        if (! $transcript || $transcript->status === CalendarMeetingTranscript::STATUS_DOWNLOADED) {
            return;
        }

        if (! $transcript->zoomAccount) {
            throw new RuntimeException('The Zoom account for this transcript no longer exists.');
        }

        $transcript->forceFill([
            'status' => CalendarMeetingTranscript::STATUS_PROCESSING,
            'download_attempts' => $transcript->download_attempts + 1,
            'last_error' => null,
        ])->save();

        $metadata = $zoomApi->meetingTranscriptMetadata(
            $transcript->zoomAccount,
            $transcript->meeting_uuid,
        );

        if (! ($metadata['can_download'] ?? false)) {
            $reason = (string) ($metadata['download_restriction_reason'] ?? 'UNKNOWN');

            if ($reason === 'NOT_READY') {
                $transcript->forceFill([
                    'status' => CalendarMeetingTranscript::STATUS_PENDING,
                    'last_error' => 'Zoom transcript is not ready.',
                ])->save();

                throw new RuntimeException('Zoom transcript is not ready.');
            }

            $transcript->forceFill([
                'status' => CalendarMeetingTranscript::STATUS_UNAVAILABLE,
                'last_error' => "Zoom transcript cannot be downloaded: {$reason}",
            ])->save();

            return;
        }

        $downloadUrl = $metadata['download_url'] ?? null;

        if (! is_string($downloadUrl) || $downloadUrl === '') {
            throw new RuntimeException('Zoom did not return a transcript download URL.');
        }

        $content = $zoomApi->downloadMeetingTranscript($transcript->zoomAccount, $downloadUrl);

        if (trim($content) === '') {
            throw new RuntimeException('Zoom returned an empty transcript file.');
        }

        $storagePath = sprintf(
            'zoom/transcripts/%d/%s.vtt',
            $transcript->zoom_account_id,
            hash('sha256', $transcript->file_id),
        );

        if (! Storage::disk('local')->put($storagePath, $content)) {
            throw new RuntimeException('The Zoom transcript could not be written to private storage.');
        }

        if (! $transcript->calendar_record_id) {
            $transcript->calendar_record_id = $matcher->match($transcript)?->id;
        }

        $transcript->forceFill([
            'status' => CalendarMeetingTranscript::STATUS_DOWNLOADED,
            'storage_path' => $storagePath,
            'downloaded_at' => now(),
            'last_error' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        CalendarMeetingTranscript::query()
            ->whereKey($this->transcriptId)
            ->update([
                'status' => CalendarMeetingTranscript::STATUS_FAILED,
                'last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
    }
}
