<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class timecardRecord extends Model
{
    use SoftDeletes;
    
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function timecard_break_records(){
        return $this->hasMany(timecardBreakRecord::class,'record_id');
    }
    public function shift_records(){
        return $this->hasOne(shiftRecord::class, 'shift_day', 'day');
    }
    public function timecard_costs(){
        return $this->hasMany(timecardCostRecord::class, 'record_id');
    }
    public function timecard_incentives(){
        return $this->hasMany(timecardIncentive::class, 'record_id');
    }
    public function custom_field_data_records(){
        return $this->hasMany(customFieldDataRecord::class, 'table_record_id','id');
    }
    protected $casts = [
        'record_id' => 'int',
        'deleted_flag' => 'int',
    ];

    protected $fillable = [
        'day', 'start_time', 'end_time', 'stamp_flag', 'user_id'
    ];
}