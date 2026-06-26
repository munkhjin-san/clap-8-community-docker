<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCommunity;

class attendanceRecord extends Model
{
    use SoftDeletes;
    use BelongsToCommunity;

    use HasFactory;
    public function user(){
        return $this->belongsTo(User::class);
    }

    protected $attributes = [
        'prescribed_working_hours' => 0,
        'working_days_shift' => 0,
        'normal_working_days' => 0,
        'month_petition' => '済',
        'status_flag' => 1,
        'pay_day' => 20,
        'holiday_working_days' => 0,
        'paid_holiday_hours' => 0,
        'planned_paid_holiday' => 0,
        'petitionType8_count' => 0,
        'petitionType7_count' => 0,
        'petitionType6_count' => 0,
        'petitionType5_count' => 0,
        'petitionType4_count' => 0,
        'petitionType3_count' => 0,
        'petitionType2_count' => 0,
        'petitionType1_count' => 0,
        'working_hours' => 0,
        'over_time' => 0,
        'night_work_time' => 0,
        'working_hours_no_over' => 0,
        'stay_pay' => 0,
        'move_pay' => 0,
        'closed_day' => 0,
        'half_day_holiday' => 0,
        'condolence_holiday' => 0,
        'special_holiday' => 0,
        'oda_holiday' => 0,
        'absence_days' => 0,
        'absence_hour' => 0,
        'expenses' => 0,
        'incentive' => 0,
    ];
}
