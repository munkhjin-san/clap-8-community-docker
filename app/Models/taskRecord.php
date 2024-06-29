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

    public function task_users(){
        return $this->hasMany(taskUser::class, 'record_id', 'id')->with('user');
    }
    public function to_users(){
        return $this->belongsToMany(User::class, 'task_users', 'record_id', 'user_id')->withPivot('id', 'comp_flag', 'late_answer', 'late_answer_custom', 'status_flag', 'comment')->select(['users.id as id', 'users.name','users.icon_id']);
    }

    public function approve_user(){
        return $this->hasOne(User::class, 'id', 'approver_id')->select(['id', 'name','icon_id']);
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'task_use_files', 'record_id', 'file_id');
    } 
}
