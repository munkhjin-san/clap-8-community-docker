<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CalendarMeetingTranscript extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DOWNLOADED = 'downloaded';

    public const STATUS_UNAVAILABLE = 'unavailable';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'meeting_start_time' => 'datetime',
        'downloaded_at' => 'datetime',
        'download_attempts' => 'integer',
        'speaker_overrides' => 'array',
    ];

    /**
     * 保存した手直しを VTT 由来の cue に当てる。
     * その行だけの指定を、同名まとめての指定より優先する。
     *
     * @param  array<int, array{start: string, end: string, speaker: ?string, text: string}>  $cues
     * @return array<int, array{start: string, end: string, speaker: ?string, text: string}>
     */
    public function applySpeakerOverrides(array $cues): array
    {
        $overrides = $this->speaker_overrides ?? [];
        $byName = $overrides['all'] ?? [];
        $byCue = $overrides['cues'] ?? [];

        if ($byName === [] && $byCue === []) {
            return $cues;
        }

        foreach ($cues as $index => $cue) {
            $original = $cue['speaker'];

            if (array_key_exists((string) $index, $byCue)) {
                $cues[$index]['speaker'] = $byCue[(string) $index];
            } elseif ($original !== null && array_key_exists($original, $byName)) {
                $cues[$index]['speaker'] = $byName[$original];
            }
        }

        return $cues;
    }

    public function calendarRecord(): BelongsTo
    {
        return $this->belongsTo(CalendarRecord::class);
    }

    public function zoomAccount(): BelongsTo
    {
        return $this->belongsTo(ZoomAccount::class);
    }

    public function aiSummary(): HasOne
    {
        return $this->hasOne(CalendarMeetingTranscriptSummary::class);
    }
}
