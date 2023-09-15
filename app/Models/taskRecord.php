<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class taskRecord extends Model
{   
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'end_at', 'updated_user'
    ];

    public function task_users(){
        return $this->hasMany(taskUser::class, 'record_id', 'id')->with('user');
    }
    public function to_users(){
        return $this->belongsToMany(User::class, 'task_users', 'record_id', 'user_id')->withPivot('id', 'comp_flag')->select(['users.id as id', 'users.name','users.icon_id']);
    }
}
