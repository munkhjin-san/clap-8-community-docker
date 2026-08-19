<?php

namespace App\Jobs;

use App\Models\CalendarMeetingTranscriptSummary;
use App\Services\TranscriptAiSummaryService;
use App\Services\ZoomVttParser;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateTranscriptAiSummary implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 90];

    public int $uniqueFor = 600;

    public function __construct(
        public readonly int $summaryId,
        public readonly int $generation,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->summaryId;
    }

    public function handle(
        TranscriptAiSummaryService $summaryService,
        ZoomVttParser $vttParser,
    ): void {
        $summary = CalendarMeetingTranscriptSummary::query()
            ->with('transcript')
            ->find($this->summaryId);

        if (! $summary || $summary->generation !== $this->generation) {
            return;
        }

        $transcript = $summary->transcript;
        if (! $transcript || ! $transcript->storage_path) {
            throw new RuntimeException('文字起こしファイルが見つかりません。');
        }

        $summary->forceFill([
            'status' => CalendarMeetingTranscriptSummary::STATUS_PROCESSING,
            'last_error' => null,
        ])->save();

        $disk = Storage::disk('local');
        if (! $disk->exists($transcript->storage_path)) {
            throw new RuntimeException('文字起こしファイルがストレージにありません。');
        }

        $cues = $vttParser->parse($disk->get($transcript->storage_path));
        $result = $summaryService->summarize($cues);

        $freshSummary = CalendarMeetingTranscriptSummary::find($this->summaryId);
        if (! $freshSummary || $freshSummary->generation !== $this->generation) {
            return;
        }

        $freshSummary->forceFill([
            'status' => CalendarMeetingTranscriptSummary::STATUS_COMPLETED,
            'content' => $result['content'],
            'model' => $result['model'],
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'last_error' => null,
            'completed_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $summary = CalendarMeetingTranscriptSummary::find($this->summaryId);
        if (! $summary || $summary->generation !== $this->generation) {
            return;
        }

        $message = $exception?->getMessage() ?: 'AI要約の作成に失敗しました。';
        $summary->forceFill([
            'status' => CalendarMeetingTranscriptSummary::STATUS_FAILED,
            'last_error' => mb_substr($message, 0, 2000),
        ])->save();

        Log::error('Transcript AI summary generation failed.', [
            'summary_id' => $this->summaryId,
            'transcript_id' => $summary->calendar_meeting_transcript_id,
            'generation' => $this->generation,
            'error' => $message,
        ]);
    }
}
