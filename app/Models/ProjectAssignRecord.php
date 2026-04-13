<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectAssignRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'assign_data' => 'array',
    ];

    public function projectRecord()
    {
        return $this->belongsTo(ProjectRecord::class);
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectAssignStatusHistory::class, 'project_assign_record_id')
            ->orderByDesc('changed_at')
            ->orderByDesc('id');
    }

    public function questions()
    {
        return $this->hasMany(CustomFormBlock::class, 'project_assign_record_id')
            ->orderBy('order_number')
            ->orderBy('id');
    }

    /**
     * Get the actions for this assign record.
     */
    public function actions()
    {
        return $this->hasMany(ProjectAssignAction::class);
    }
}
