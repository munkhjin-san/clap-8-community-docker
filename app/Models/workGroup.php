<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class workGroup extends Model
{
    use BelongsToCommunity;

    use SoftDeletes;

    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function work_group_user(){
        return $this->hasMany(workGroupUser::class, 'record_id');
    }
    public function members(){
        return $this->belongsToMany(User::class, 'work_group_users', 'record_id', 'user_id')->select(['users.id as id', 'users.name','users.icon_path', 'users.icon_bg','users.name_kana', 'users.work_authority'])->withPivot(['authority']);
    }
}
