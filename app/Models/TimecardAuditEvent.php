<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimecardAuditEvent extends Model
{
    protected $fillable = [
        'timecard_record_id',
        'timecard_cost_record_id',
        'draft_uuid',
        'target_type',
        'event_type',
        'actor_user_id',
        'subject_user_id',
        'request_id',
        'client_ip',
        'user_agent',
        'before_state',
        'after_state',
        'metadata',
        'payload_hash',
        'previous_event_hash',
        'event_hash',
        'occurred_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function timecard()
    {
        return $this->belongsTo(timecardRecord::class, 'timecard_record_id');
    }

    public function timecardCost()
    {
        return $this->belongsTo(timecardCostRecord::class, 'timecard_cost_record_id');
    }

    public function projection()
    {
        return $this->hasOne(TimecardAuditEventProjection::class, 'timecard_audit_event_id');
    }
}
