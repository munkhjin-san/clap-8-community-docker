<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssignStatusHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function projectAssignRecord()
    {
        return $this->belongsTo(ProjectAssignRecord::class, 'project_assign_record_id');
    }

    public function projectRecord()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
