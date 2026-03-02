<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryIssue extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function files(){
        return $this->belongsToMany(FileRecord::class, 'project_use_files', 'salary_issue_id', 'file_id')->where('file_records.deleted_flag', 0);
    }

    public function mentor() {
        return $this->hasOne(User::class, 'id', 'mentor_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function project_goal(){
        return $this->belongsTo(ProjectGoal::class, 'project_goal_id', 'id');
    }
    public function actions(){
        return $this->hasMany(SalaryIssueAction::class, 'salary_issue_id', 'id');
    }

    public function statusLogs()
    {
        return $this->hasMany(StatusLog::class, 'record_id', 'id')
            ->where('type', 'salary_issue')->orderBy('created_at', 'desc');
    }

    public function evaluation()
    {
        return $this->hasOneThrough(
            EvaluationRecord::class,
            ProjectGoal::class,
            'id', // Foreign key on ProjectGoal (matches SalaryIssue's project_goal_id)
            'user_id', // Foreign key on EvaluationRecord
            'project_goal_id', // Local key on SalaryIssue
            'user_id' // Local key on ProjectGoal
        )->whereColumn('project_goals.year', 'evaluation_records.year')
         ->whereColumn('project_goals.which_half', 'evaluation_records.which_half');
    }
    public function reports() {
        return $this->hasMany(SalaryIssueReport::class)->with(['user', 'files']);
    }
    public function scopeOverdue($q, $now)
    {
        return $q->where('status', '<', 9);
    }

    public function issue_notifications(){
        return $this->hasMany(ProjectMemberReportNotification::class);
    }

}
