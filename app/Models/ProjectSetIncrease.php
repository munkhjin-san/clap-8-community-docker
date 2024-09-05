<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSetIncrease extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function checklist(){
        return $this->hasMany(ProjectIncreaseChecklist::class, 'increase_id', 'id');
    }

    public function candidate(){
        return $this->hasMany(ProjectIncreaseCandidate::class, 'increase_id', 'id');
    }
    public function evaluation(){
        return $this->hasOne(ProjectEvaluation::class, 'date', 'date');
    }

    public function outcome_goals(){
        return $this->hasMany(ProjectGoal::class, 'target_period', 'target_period');
    }

    public function salary_issues(){
        return $this->hasMany(SalaryIssue::class, 'date', 'date');
    }
}
