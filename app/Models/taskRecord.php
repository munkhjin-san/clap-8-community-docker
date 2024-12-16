<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class taskRecord extends Model
{   
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];
    public function executors(){
        return $this->belongsToMany(User::class, 'task_users', 'record_id', 'user_id')
                    ->where('users.retire', 0)
                    ->wherePivot('supervisor', 0)
                    ->wherePivotNull('deleted_at')
                    ->withPivot('id', 'comp_flag', 'late_answer', 'late_answer_custom', 'status_flag', 'comment', 'glowd_nine', 'try_flag', 'pin_flag', 'progress_flag')
                    ->select(['users.id as id', 'users.name','users.icon_id', 'users.position_id']);
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'task_use_files', 'record_id', 'file_id');
    } 
    public function supervisors() {
        return $this->belongsToMany(User::class, 'task_users', 'record_id', 'user_id')
                    ->where('users.retire', 0)
                    ->wherePivot('supervisor', 1)
                    ->wherePivotNull('deleted_at')
                    ->withPivot('id', 'comp_flag', 'late_answer', 'late_answer_custom', 'status_flag', 'comment', 'pin_flag', 'progress_flag')
                    ->select(['users.id as id', 'users.name','users.icon_id']);
    }
    public function repeat(){
        return $this->hasOne(TaskRepeat::class, 'record_id', 'repeat_id');
    }
    public function sub_tasks() {
        return $this->hasMany(taskRecord::class,  'parent_task_id')->orderBy('start_at', 'asc')->with(['executors', 'comments']);
    }
    public function main_task(){
        return $this->belongsTo(taskRecord::class, 'parent_task_id');
    }
    public function project(){
        return $this->belongsTo(ProjectRecord::class,  'project_record_id', 'id');
    }
    public function comments(){
        return $this->hasMany(TaskComment::class)->with('user');
    }
    public function taskUsers(){
        return $this->hasMany(taskUser::class, 'id', 'record_id');
    }
    public function unreadCommentsForUser($userId)
    {
        // Join with TaskUser to access the checked_at field
        return $this->comments()
            ->whereHas('taskUsers', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->whereColumn('task_comments.created_at', '>', 'task_user.checked_at');
            });
    }
}
