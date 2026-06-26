<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCommunity;
use Carbon\Carbon;

class timecardRecord extends Model
{
    use SoftDeletes;
    use BelongsToCommunity;

    public const STATUS_DRAFT = 0;
    public const STATUS_SUBMITTED = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 10;
    
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
    public function vehicle_records(){
        return $this->hasMany(timecardVehicle::class, 'record_id', 'id');
    }
    public function car_project(){
        return $this->hasOne(ProjectRecord::class, 'id', 'car_used_project');
    }
    public function project_case()
    {
        return $this->hasMany(ProjectCase::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function project_segments()
    {
        return $this->hasMany(TimecardProjectSegment::class, 'timecard_record_id');
    }
    protected $appends = ['training_minutes'];

    public function getTrainingMinutesAttribute(): int
    {
        if ($this->relationLoaded('project_segments')) {
            $segmentMinutes = $this->project_segments
                ->where('segment_type', TimecardProjectSegment::TYPE_TRAINING)
                ->sum('minutes');

            if ($segmentMinutes > 0) {
                return (int) $segmentMinutes;
            }
        }
        if ($this->exists) {
            $segmentMinutes = $this->project_segments()
                ->where('segment_type', TimecardProjectSegment::TYPE_TRAINING)
                ->sum('minutes');

            if ($segmentMinutes > 0) {
                return (int) $segmentMinutes;
            }
        }

        if (!$this->training_start_time || !$this->training_end_time) {
            return 0;
        }
        $start = Carbon::parse("{$this->day} {$this->training_start_time}");
        $end   = Carbon::parse("{$this->day} {$this->training_end_time}");
        if ($end->lt($start)) {
            $end->addDay(); // overnight
        }
        return $start->diffInMinutes($end);
    }
    protected $casts = [
        'record_id' => 'int',
        'deleted_flag' => 'int',
        'car_mileage' => 'int',
        'gas_full_price' => 'int'
    ];

    protected $guarded = [];
}
