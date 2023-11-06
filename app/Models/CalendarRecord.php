<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function calendar_users(){
        return $this->belongsToMany(User::class, 'calendar_users', 'record_id', 'user_id')->select(['users.id as id', 'users.name','users.icon_id'])->distinct();
    }
    public function updated_by(){
        return $this->hasOne(User::class, 'id', 'updated_user')->select('id', 'name', 'icon_id', 'icon_id');
    }
    public function created_by(){
        return $this->hasOne(User::class, 'id', 'created_user')->select('id', 'name', 'icon_id', 'icon_id');
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'calendar_use_files', 'record_id', 'file_id')->where('file_records.deleted_flag', 0);
    }
    protected $hidden = [
        'color', 
        'comp_flag', 
        'date_end_text', 
        'date_start_text', 
        'day_end_text', 
        'day_start_text', 
        'h_height', 
        'h_top', 
        'qualified_users', 
        'task_date_end_text', 
        'task_end', 
        'task_h_top', 
        'task_time_end_text', 
        'time_end_text', 
        'time_start_text',
        'type',

    ];
    protected $fillable = [
        'user_id', 
        'title', 
        'date_start',
        'date_end',
        "title",
        "remarks",
        "referrer",
        "release_flag",
        "edit_all",
        "repetition_type",
        "created_user",
        "updated_user",
        "user_id",
        "r_group_id",
        "expiration_start",
        "expiration_end",
        "qualified_institution",
        "zoom_value",
        "qualified_car",
        "repeat_week",
        "repeat_days",
        "repeat_month",
        "zoom_url",
        "zoom_id",
        "zoom_pass",
        "zoom_account",
        "zoom_account_pass",
        "created_at"
    ];
}
