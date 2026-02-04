<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'temp_flag' => 'boolean',
    ];

    public function checklist(){
        return $this->hasMany(EvaluationSkill::class, 'evaluation_record_id', 'id');
    }

    public function candidate(){
        return $this->hasMany(EvaluationCandidate::class, 'evaluation_record_id', 'id');
    }
    public function outcome_goals(){
        return $this->hasMany(ProjectGoal::class, 'target_period', 'target_period');
    }

    public function salary_issues(){
        return $this->hasMany(SalaryIssue::class, 'date', 'date');
    }
    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id')->select('id', 'name', 'icon_path', 'icon_bg', 'position_id');
    }
    public function mentor() {
        return $this->hasOne(User::class, 'id', 'mentor_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
