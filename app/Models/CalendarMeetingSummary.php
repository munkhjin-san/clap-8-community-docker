<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarMeetingSummary extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'calendar_meeting_summaries';

    protected $guarded = [];

    public function steps () {
        return $this->hasMany(CalendarMeetingSummaryStep::class);
    }
    public function details () {
        return $this->hasMany(CalendarMeetingSummaryDetail::class);
    }
}


