<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectMember extends Pivot
{
    protected $table = 'project_members';

    protected $casts = [
        'assign_data' => 'array',
        'overall_assign_score' => 'float',
    ];

    public function user(){
        return $this->belongsTo(User::class)->select('name', 'user_code', 'id');
    }
    public function projectRole(){
        return $this->belongsTo(ProjectMemberRole::class);
    }
    public function project(){
        return $this->belongsTo(ProjectRecord::class, 'project_id', 'id');
    }

    protected $guarded = [];
}
