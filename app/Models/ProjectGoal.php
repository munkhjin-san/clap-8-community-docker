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
    public function files() {
        return $this->belongsToMany(FileRecord::class, 'project_use_files', 'project_goal_id', 'file_id');
    }
    public function steps() {
        return $this->hasMany(ProjectGoalStep::class);
    }
    public function reports() {
        return $this->hasMany(ProjectGoalReport::class)->with(['files']);
    }
    public function scopeOverdue($q, $now)
    {
        return $q->where('status', '!=', 9)
                ->where('end_date', '<', $now);
    }

    public function scopeRelevantToViewer($q, $userId)
    {
        return $q->where(function ($s) use ($userId) {
            $s->where('user_id', $userId)
            ->orWhereHas('project.manager', fn($m) => $m->where('users.id', $userId))
            ->orWhereHas('salaryIssue', fn($si) => $si->where('mentor_id', $userId));
        });
    }

}
