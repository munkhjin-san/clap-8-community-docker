<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyGroup extends Model
{
    use SoftDeletes;
    use HasFactory;
    public function users(){
        return $this->belongsToMany(User::class, 'my_group_users', 'record_id', 'user_id')->wherePivot('deleted_at', null)->withPivot('selected_as_calendar_member')->select(['users.id as id', 'users.name','users.icon_id']);
    }
    public function selected_users(){
        return $this->belongsToMany(User::class, 'my_group_users', 'record_id', 'user_id')->wherePivot('selected_as_calendar_member', 1)->withPivot('selected_as_calendar_member')->select(['users.id as id', 'users.name','users.icon_id']);
    }
    public function unselected_users(){
        return $this->belongsToMany(User::class, 'my_group_users', 'record_id', 'user_id')->wherePivot('selected_as_calendar_member', 0)->withPivot('selected_as_calendar_member')->select(['users.id as id', 'users.name','users.icon_id']);
    }
    protected $fillable = [
        'name', 'user_id','selected'
    ];
}
