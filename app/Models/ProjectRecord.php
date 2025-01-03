<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function members(){
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->wherePivot('authority', 0)->select(['users.id as id', 'users.name','users.icon_id', 'users.user_code', 'users.work_authority', 'users.position_id'])->withPivot(['authority', 'id'])->with('positions');
    }

    public function manager(){
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->wherePivot('authority', 1)->select(['users.id as id', 'users.name','users.icon_id', 'users.user_code', 'users.work_authority', 'users.position_id'])->withPivot(['authority', 'id'])->with('positions');
    }

    public function director(){
        return $this->hasOne(User::class, 'id', 'director_id')->select('id', 'name', 'icon_id');
    }
    public function goals() {
        return $this->hasMany(ProjectGoal::class, 'project_id', 'id');
    }
    public function tasks(){
        return $this->hasMany(taskRecord::class);
    }
    public function project_conditions() {
        return $this->hasMany(ProjectCondition::class);
    }
    protected $guarded = [];
}
