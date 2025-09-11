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
    public function total_break_time(){
        return $this->hasMany(timecardBreakRecord::class, 'record_id')
            ->select('record_id')
            ->selectRaw('SUM(break_by_minute) as total_break_minute')
            ->groupBy('record_id');
    }
    public function department(){
        return $this->hasOne(ProjectRecord::class, 'id', 'work_group_id');
    }
    public function department_members(){
        return $this->hasMany(ProjectMember::class, 'record_id', 'work_group_id');
    }
    public function vehicle_data(){
        return $this->hasOne(timecardVehicle::class, 'record_id', 'id');
    }
    public function car_project(){
        return $this->hasOne(ProjectRecord::class, 'id', 'car_used_project');
    }
    protected $casts = [
        'record_id' => 'int',
        'deleted_flag' => 'int',
        'car_mileage' => 'int',
        'gas_full_price' => 'int'
    ];

    protected $guarded = [];
}