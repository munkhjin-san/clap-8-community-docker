<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    public function calendarRecord(): BelongsTo
    {
        return $this->belongsTo(CalendarRecord::class);
    }

    public function zoomAccount(): BelongsTo
    {
        return $this->belongsTo(ZoomAccount::class);
    }
}
