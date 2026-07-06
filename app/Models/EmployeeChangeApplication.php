<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeChangeApplication extends Model
{
    use BelongsToCommunity;

    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $appends = [
        'status_label',
        'type_label',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'reviewed_at' => 'datetime',
        'status' => ApplicationStatus::class,
        'type' => ApplicationType::class,
    ];

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function profileDetail()
    {
        return $this->hasOne(EmployeeProfileChangeDetail::class, 'employee_change_application_id', 'id');
    }

    public function leaveDetail()
    {
        return $this->hasOne(EmployeeLeaveApplicationDetail::class, 'employee_change_application_id', 'id');
    }

    public function commuteDetail()
    {
        return $this->hasOne(EmployeeCommuteChangeDetail::class, 'employee_change_application_id', 'id');
    }

    public function fileAttachments()
    {
        return $this->morphMany(FileAttachment::class, 'attachable');
    }

    public function files()
    {
        return $this->belongsToMany(FileRecord::class, 'file_attachments', 'attachable_id', 'file_id')
            ->wherePivot('attachable_type', self::class)
            ->where('file_records.deleted_flag', 0);
    }

    public function detail()
    {
        return match ($this->type) {
            ApplicationType::LeaveRequest => $this->leaveDetail,
            ApplicationType::CommuteChange => $this->commuteDetail,
            default => $this->profileDetail,
        };
    }
}
