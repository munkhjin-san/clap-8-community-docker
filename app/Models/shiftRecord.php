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

    protected $fillable = ['user_id', 'shift_month', 'shift_day', 'shift_type', 'start_time', 'end_time', 'status_flag', 'planned_year', 'changed_day'];

}
