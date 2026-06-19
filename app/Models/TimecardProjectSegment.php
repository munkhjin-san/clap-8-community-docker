<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimecardProjectSegment extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const TYPE_WORK = 'work';
    public const TYPE_TRAINING = 'training';
    public const APPROVAL_SOURCE_AUTO = 'auto';
    public const APPROVAL_SOURCE_USER = 'user';

    protected $fillable = [
        'project_id',
        'segment_type',
        'start_time',
        'end_time',
        'minutes',
        'details',
        'detail_values',
        'comment',
        'status',
        'approved_by',
        'approved_at',
        'approval_source',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'minutes' => 'integer',
        'details' => 'array',
        'detail_values' => 'array',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function timecardRecord()
    {
        return $this->belongsTo(timecardRecord::class, 'timecard_record_id');
    }
}
