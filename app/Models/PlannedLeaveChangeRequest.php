<?php

namespace App\Models;

use App\Enums\PlannedLeaveChangeRequestStatus;
use Illuminate\Database\Eloquent\Model;

class PlannedLeaveChangeRequest extends Model
{
    protected $guarded = [];

    protected $appends = [
        'status_label',
    ];

    protected $casts = [
        'original_date' => 'date',
        'requested_date' => 'date',
        'pm_approval_required' => 'boolean',
        'pm_approval_date' => 'datetime',
        'approval_date' => 'datetime',
        'status' => PlannedLeaveChangeRequestStatus::class,
    ];

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
    public function pmApprover()
    {
        return $this->belongsTo(User::class, 'pm_id');
    }
    public function shift_record()
    {
        return $this->belongsTo(shiftRecord::class, 'shift_record_id');
    }
    public function project_record(){
        return $this->belongsTo(projectRecord::class, 'project_id');
    }
}
