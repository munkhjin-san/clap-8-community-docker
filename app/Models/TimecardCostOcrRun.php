<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimecardCostOcrRun extends Model
{
    protected $fillable = [
        'timecard_record_id',
        'timecard_cost_record_id',
        'draft_uuid',
        'source_file_path',
        'source_file_sha256',
        'provider',
        'model',
        'status',
        'normalized_result',
        'raw_response',
        'error_message',
        'executed_by_user_id',
        'applied_by_user_id',
        'applied_at',
    ];

    protected $casts = [
        'normalized_result' => 'array',
        'raw_response' => 'array',
        'applied_at' => 'datetime',
    ];

    public function timecard()
    {
        return $this->belongsTo(timecardRecord::class, 'timecard_record_id');
    }

    public function timecardCost()
    {
        return $this->belongsTo(timecardCostRecord::class, 'timecard_cost_record_id');
    }

    public function executedBy()
    {
        return $this->belongsTo(User::class, 'executed_by_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
