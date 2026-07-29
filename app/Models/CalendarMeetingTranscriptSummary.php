<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarMeetingTranscriptSummary extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
        'generation' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function transcript(): BelongsTo
    {
        return $this->belongsTo(CalendarMeetingTranscript::class, 'calendar_meeting_transcript_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
