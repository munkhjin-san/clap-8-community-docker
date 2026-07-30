<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function definition()
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id', 'id');
    }

    public function currentStatus()
    {
        return $this->belongsTo(FlowStatus::class, 'current_status_id', 'id');
    }

    public function values()
    {
        return $this->hasMany(FlowRecordValue::class, 'flow_record_id', 'id');
    }

    public function logs()
    {
        return $this->morphMany(UpdateLog::class, 'loggable')
            ->with('user')
            ->orderByDesc('created_at');
    }

    public function comments()
    {
        return $this->morphMany(AppComment::class, 'commentable')
            ->with(['user', 'files'])
            ->orderBy('created_at');
    }

    public function readHistories()
    {
        return $this->morphMany(UserReadHistory::class, 'readable');
    }

    public function fileAttachments()
    {
        return $this->morphMany(FileAttachment::class, 'attachable');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
