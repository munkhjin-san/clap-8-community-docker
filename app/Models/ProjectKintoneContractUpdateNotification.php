<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectKintoneContractUpdateNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
