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
                    ->withPivot('id', 'comp_flag', 'late_answer', 'late_answer_custom', 'status_flag', 'comment')
                    ->select(['users.id as id', 'users.name','users.icon_id']);
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'task_use_files', 'record_id', 'file_id');
    } 
    public function supervisors() {
        return $this->belongsToMany(User::class, 'task_users', 'record_id', 'user_id')
                    ->where('users.retire', 0)
                    ->wherePivot('supervisor', 1)
                    ->wherePivotNull('deleted_at')
                    ->withPivot('id', 'comp_flag', 'late_answer', 'late_answer_custom', 'status_flag', 'comment')
                    ->select(['users.id as id', 'users.name','users.icon_id']);
    }
    public function repeat(){
        return $this->hasOne(TaskRepeat::class, 'record_id', 'repeat_id');
    }
}
