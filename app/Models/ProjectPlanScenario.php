<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPlanScenario extends Model
{
    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }

    public function amounts()
    {
        return $this->hasMany(ProjectPlanAmount::class, 'project_plan_scenario_id');
    }
}
