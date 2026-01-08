<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPlanAmount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(ProjectRecord::class, 'project_record_id');
    }

    public function planYear()
    {
        return $this->belongsTo(ProjectPlanYear::class, 'project_plan_year_id');
    }

    public function account()
    {
        return $this->belongsTo(ProjectAccount::class, 'project_account_id');
    }

    public function scenario()
    {
        return $this->belongsTo(ProjectPlanScenario::class, 'project_plan_scenario_id');
    }
}
