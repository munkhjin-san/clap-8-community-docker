<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimecardAuditEventProjection extends Model
{
    protected $fillable = [
        'timecard_audit_event_id',
        'timecard_record_id',
        'timecard_cost_record_id',
        'draft_uuid',
        'target_type',
        'event_type',
        'actor_user_id',
        'subject_user_id',
        'occurred_at',
        'timecard_day',
        'approval_state',
        'merchant_name',
        'receipt_date',
        'expenses',
        'currency',
        'department',
        'file_path',
        'ocr_run_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'timecard_day' => 'date',
        'receipt_date' => 'date',
        'expenses' => 'float',
        'approval_state' => 'int',
    ];

    public function auditEvent()
    {
        return $this->belongsTo(TimecardAuditEvent::class, 'timecard_audit_event_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
