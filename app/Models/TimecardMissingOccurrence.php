<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimecardMissingOccurrence extends Model
{
    protected $guarded = [];

    protected $casts = [
        'report_date' => 'date',
        'counted_date' => 'date',
        'resolved_at' => 'datetime',
        'pm_alerted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shiftRecord()
    {
        return $this->belongsTo(shiftRecord::class, 'shift_record_id');
    }
}
