<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectGoal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function project(){
        return $this->belongsTo(ProjectRecord::class);
    }

    public function salaryIssue(){
        return $this->hasOne(SalaryIssue::class);
    }

    public function evaluation() {
        return $this->hasOne(ProjectEvaluation::class, 'target_period', 'target_period')
                ->whereColumn('user_id', 'user_id');
    }
    public function files() {
        return $this->belongsToMany(FileRecord::class, 'project_use_files', 'project_goal_id', 'file_id');
    }
}
