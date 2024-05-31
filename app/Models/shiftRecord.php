<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class shiftRecord extends Model
{
    use SoftDeletes;

    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function shiftType(){
        return $this->belongsTo(shiftType::class, 'shift_type');
    }
    public function time_card_records(){
        return $this->belongsTo(timecardRecord::class, 'day', 'shift_day');
    }
    public function old_shift(){
        return $this->hasOne(shiftRecord::class, 'id', 'descendant_of');
    }
    public function overtime_request(){
        return $this->hasOne(ShiftOvertimeRequest::class, 'record_id', 'id');
    }
    protected $fillable = ['user_id', 'shift_day', 'shift_type', 'start_time', 'end_time', 'status_flag', 'planned_year', 'descendant_of'];

}
