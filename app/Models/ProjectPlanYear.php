<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPlanYear extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_on' => 'date',
    ];

    public function amounts()
    {
        return $this->hasMany(ProjectPlanAmount::class, 'project_plan_year_id');
    }
}
