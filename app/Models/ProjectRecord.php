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
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->wherePivot('authority', 0)->select(['users.id as id', 'users.name','users.icon_id', 'users.user_code', 'users.work_authority'])->withPivot(['authority', 'id']);
    }

    public function manager(){
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')->wherePivot('authority', 1)->select(['users.id as id', 'users.name','users.icon_id', 'users.user_code', 'users.work_authority'])->withPivot(['authority', 'id']);
    }

    public function director(){
        return $this->hasOne(User::class, 'id', 'director_id')->select('id', 'name', 'icon_id');
    }

    protected $guarded = [];
}
