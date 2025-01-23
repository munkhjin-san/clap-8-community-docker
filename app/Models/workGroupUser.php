<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class workGroupUser extends Model
{
    use SoftDeletes;

    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class)->select('name', 'name_kana', 'id', 'icon_path', 'icon_bg', 'work_authority');
    }

    public function work_group(){
        return $this->belongsTo(workGroup::class, 'id');
    }

    public function timecard_records(){
        return $this->hasMany(timecardRecord::class, 'user_id', 'user_id')->select('day', 'user_id');
    }

    public function shift_overtime_requests(){
        return $this->hasMany(ShiftOvertimeRequest::class, 'created_by', 'user_id');
    }
}
