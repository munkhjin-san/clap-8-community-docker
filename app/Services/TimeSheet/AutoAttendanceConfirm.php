<?php

namespace App\Services\TimeSheet;
use App\Services\SharedService;
use App\Models\User;
use App\Models\timecardCostRecord;
use App\Models\timecardIncentive;
use App\Models\shiftRecord;
use App\Models\attendanceRecord;
use App\Models\TimecardProjectSegment;
use Illuminate\Support\Facades\Auth;
class AutoAttendanceConfirm
{
    protected $sharedService;
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function confirm($user_list, $currentDate)
    {   
        $active_user = $this->active_user();
        $attendanceDatas = $this->build_attendance_data($user_list, $currentDate);
        [$currentYear, $currentMonth] = explode('-', $currentDate);
        foreach($attendanceDatas as $userid => $data){
            $workTime = $data['user']['position_id'] !== 15 ? $data['should_work'] : $data['planned_work'];
            $workType = $data['user']['work_type'] == 0 ? 'フレックス' : '通常';
            
            
            $shift_records = shiftRecord::whereYear('shift_day', $currentYear)
                        ->whereMonth('shift_day', $currentMonth)
                        ->where('user_id', $userid)->get();
            $half_day_holiday = $shift_records->where('shift_type', 6)->count();
            $planned_paid_holiday = $shift_records->where('shift_type', 3)->count();
            $petitionType8_count = $shift_records->where('shift_type', 5)->count();
            $petitionType7_count = $shift_records->where('shift_type', 13)->count();
            $petitionType6_count = $shift_records->where('shift_type', 12)->count();
            $petitionType5_count = $shift_records->where('shift_type', 11)->count();
            $petitionType4_count = $shift_records->where('shift_type', 10)->count();
            $petitionType3_count = $shift_records->where('shift_type', 9)->count();
            $petitionType2_count = $shift_records->where('shift_type', 8)->count();
            $petitionType1_count = $shift_records->where('shift_type', 7)->count();
            $comp_holiday = $shift_records->where('shift_type', 17)->count();
            $shiftTypes = [13, 12, 11, 10, 9, 8, 7, 6];
            $hours_count = 0;
            $working_hour_low = 0;
           
            $over_time = $data['month_over_time'];
            
            foreach ($shiftTypes as $type) {
                $count = $shift_records->where('shift_type', $type)->count();
                $hours_count += $type === 6 ? $count * 0.5 : $count;
                if ($type !== 6) {
                    $working_hour_low += $count;
                }
            }
            $closed_day = $shift_records->where('shift_type', 2)->count();
            $noOverTimeHours = 0;
            $user_work_time_day = $data['user']['work_time_day'] ?? 0;
            $condolence_hours = $user_work_time_day * $data['condolence_leave'];
            $oda_hours = $user_work_time_day * $data['oda_leave'];
            $transfer_hours = $user_work_time_day * $data['transfer_leave'];
            $closed_hours = $user_work_time_day * $closed_day;
            $special_hours = $user_work_time_day * $data['special_holiday'];
            
            
            $noOverTimeHours = $data['worked_time'] - $data['month_over_time'] - $data['night_over_time'];
            
            $special_holiday = $data['transfer_leave'] + $data['special_holiday'];
            $absence_days = ($working_hour_low - $data['workedday_count']) + $data['holiday_count'];
            $attendance_record = new attendanceRecord;
            $attendance_record->half_day_holiday = $half_day_holiday;
            $attendance_record->planned_paid_holiday = $planned_paid_holiday;
            $attendance_record->petitionType8_count = $petitionType8_count;
            $attendance_record->petitionType7_count = $petitionType7_count;
            $attendance_record->petitionType6_count = $petitionType6_count;
            $attendance_record->petitionType5_count = $petitionType5_count;
            $attendance_record->petitionType4_count = $petitionType4_count;
            $attendance_record->petitionType3_count = $petitionType3_count;
            $attendance_record->petitionType2_count = $petitionType2_count;
            $attendance_record->petitionType1_count = $petitionType1_count;
            $attendance_record->closed_day = $closed_day;
            $attendance_record->absence_days = $absence_days >= 0 ? $absence_days : 0;
            if ($workType == '通常') {
                $absence_hours = $workTime - (
                    $data['annual_leave'] + $condolence_hours + $transfer_hours + $closed_hours + $data['worked_time'] + $oda_hours + $special_hours - $over_time);
            } else {
                $absence_hours = $workTime - (
                    $data['annual_leave'] + $condolence_hours + $transfer_hours + $closed_hours + $data['worked_time'] + $oda_hours + $special_hours);
            }
            
            
            $attendance_record->absence_hour = $absence_hours >= 0 ? $absence_hours : 0;
            $attendance_record->date_year_month = $currentDate;
            $attendance_record->user_id = $userid;
            $attendance_record->confirmed_by = $active_user->id;
            $attendance_record->user_code = $data['user']['user_code'];
            $attendance_record->name = $data['user']['name'];
            $attendance_record->pay_day = 20;
            $attendance_record->month_petition = '済';
            $attendance_record->prescribed_working_hours = $workTime / 60;
            $attendance_record->work_type = $workType;
            $attendance_record->working_days_shift = $data['shift_count'];
            $attendance_record->normal_working_days = $data['workedday_count'];
            $attendance_record->holiday_working_days = $data['holiday_count'];
            $attendance_record->paid_holiday_hours = $data['annual_leave'] / 60;
            $attendance_record->condolence_holiday = $data['condolence_leave'];
            $attendance_record->special_holiday = $special_holiday;
            $attendance_record->oda_holiday = $data['oda_leave'];
            $attendance_record->comp_holiday = $comp_holiday;
            $attendance_record->working_hours = $data['worked_time'];
            $attendance_record->working_hours_no_over = $noOverTimeHours;
            $attendance_record->over_time = $over_time;
            $attendance_record->night_work_time = $data['night_over_time'];
            $attendance_record->stay_pay = $data['month_stay_allowance_count'];
            $attendance_record->move_pay = $data['month_move_allowance_count'];
            $attendance_record->waiting_pay = $data['month_waiting_allowance_count'];
            $attendance_record->vehicle_pay = $data['month_vehicle_allowance_count'];
            $attendance_record->special_commute_pay = $data['month_special_commute_allowance_count'];
            $attendance_record->remote_company_pay = $data['month_remote_personal_allowance_count'];
            $attendance_record->remote_personal_pay = $data['month_remote_company_allowance_count'];
            $attendance_record->expenses = $data['annual_costs'];
            $attendance_record->incentive = $data['annual_incentives'];
            $attendance_record->mileage = $data['mileage'];
            $attendance_record->training_time = $data['month_training_minutes'];
            $attendance_record->save();
        }

        return count($attendanceDatas);
    }

    public function build_attendance_data($user_list, $currentDate)
    {
        $formattedDate = date('Y-m', strtotime($currentDate));
        [$currentYear, $currentMonth] = explode('-', $currentDate);
        $users = User::with([
            'attendance_records' => function ($query) use ($formattedDate) {
                $query->where('date_year_month', $formattedDate);
            },
            'shift_records' => function ($query) use ($currentYear, $currentMonth) {
                $query->whereYear('shift_day', $currentYear)
                    ->whereMonth('shift_day', $currentMonth)
                    ->select('user_id', 'shift_day', 'shift_type', 'status_flag')->with([
                        'shiftType' => function ($query) {
                            $query->select('id', 'name', 'abbreviation', 'value', 'full_day');
                        }
                    ]);
            },
            'time_card_records' => function ($query) use ($currentYear, $currentMonth) {
                $query->whereYear('day', $currentYear)
                    ->whereMonth('day', $currentMonth)
                    ->with(['project_segments' => function ($q) {
                        $q->select('id', 'timecard_record_id', 'segment_type', 'minutes', 'detail_values');
                    }])
                    ->select('id', 'user_id', 'day', 'work_time', 'over_time', 'status_flag', 'late_time', 'night_over_time', 'stamp_flag', 'car_mileage', 'training_start_time', 'training_end_time');
            },
            'custom_field_data_records' => function ($query) use ($currentYear, $currentMonth) {
                $query->where('type_id', 37)
                    ->whereYear('date', $currentYear)
                    ->whereMonth('date', $currentMonth)
                    ->select('value_int', 'user_id', 'table_record_id');
            }
        ])->select('id','name','work_type', 'work_time_day', 'user_code', 'position_id', 'general_position')->whereIn('id', $user_list)->get();        
        $monthNum = (int)$currentMonth;
        $yearNum = (int)$currentYear;
        $responseArray = [];
        foreach ($users as $user) {
            $userWorkTimeData = $this->sharedService->work_days_calculator($yearNum, $monthNum, $user);
            $workdayNum = $userWorkTimeData['days'];
            $shift_work_hours = $userWorkTimeData['work_minutes'];

            $hiddenAttributes = ['attendance_records', 'shift_records', 'time_card_records', 'custom_field_data_records'];
            $userData = $user->makeHidden($hiddenAttributes);
            $attendance = $user->attendance_records->first();
            $working_shifts = [1, 6, 7, 8, 9, 10, 11, 12, 13];
            $should_calculate_month_hours = $user->position_id == 12 || $user->position_id == 15;
            $shift_count = $should_calculate_month_hours ? $user->shift_records->whereIn('shift_type', $working_shifts)->count() : $user->shift_records->whereNotIn('shift_type', [0, 18])->count();
            $planned_work_hours = $shift_count * $user->work_time_day;
            if($should_calculate_month_hours){
                // $planned_work_shifts = $user->shift_records->whereIn('shift_type', $working_shifts)->get();
                $planned_work_shifts = collect($user->shift_records->whereIn('shift_type', $working_shifts)->values());
                $calculated_planned_minutes = 0;
                $day_work_minute =  $user->work_time_day;
                foreach ($planned_work_shifts as $shift) {
                    switch ($shift['shift_type']) {
                        case 1:
                            $calculated_planned_minutes += $day_work_minute;
                            break;
                        case 6:
                            $calculated_planned_minutes += $day_work_minute / 2;
                            break;
                        default:
                            if ($shift['shift_type'] >= 7 && $shift['shift_type'] <= 13) {
                                $sub_time = $day_work_minute - (($shift['shift_type'] - 6) * 60);
                                if ($sub_time > 0) {
                                    $calculated_planned_minutes += $sub_time;
                                }
                            }
                            break;
                    }
                }
                $planned_work_hours = $calculated_planned_minutes;
            }
            $shift_holidays = $user->shift_records->where('shift_type', 0)->pluck('shift_day');
            $legal_holidays = $user->shift_records->where('shift_type', 18)->pluck('shift_day');
            $shift_workdays = $user->shift_records->whereIn('shift_type', [1, 6, 7, 8, 9, 10, 11, 12, 13, 19, 20, 21, 22, 23, 24, 26])->pluck('shift_day');
            $worked_holiday_count = $user->time_card_records->whereIn('day', $shift_holidays)->where('work_time', '>', 0)->count();
            $legal_holiday_worked_count = $user->time_card_records->whereIn('day', $legal_holidays)->where('work_time', '>', 0)->count();
            $workedday_count = $user->position_id === 15
            ? $user->time_card_records->where('work_time', '>', 0)->count()
            : $user->time_card_records->whereIn('day', $shift_workdays)->where('work_time', '>', 0)->count();
            $worked_time = $user->time_card_records->sum('work_time');
            $holiday_worked_time = $user->time_card_records->whereIn('day', $shift_holidays)->sum('work_time');
            $legal_holiday_worked_time = $user->time_card_records->whereIn('day', $legal_holidays)->sum('work_time');
            $approved_count = $user->time_card_records->where('status_flag', 2)->count();
            $unapproved_count = $user->time_card_records->where('status_flag', 1)->count();
            $unsaved_count = $user->time_card_records->where('stamp_flag', 1)->whereIn('status_flag', [0, 10])->count();
            $unapproved_shift_count = 0;
            if($user->position_id !== 15){
                $unapproved_shift_count = $user->shift_records->where('status_flag', 2)->count();
            }
            $night_over_time = $user->time_card_records->sum('night_over_time');
            $shiftRecords = $user->shift_records;

            $annual_leave = $shiftRecords
                ->filter(fn($record) =>
                    $record->shiftType?->full_day === 0 &&
                    in_array($record->shift_type, [7, 8, 9, 10, 11, 12, 13])
                )
                ->sum(fn($record) => $record->shiftType?->value ?? 0);


            $annual_full = $shiftRecords
                ->filter(fn($record) => 
                    $record->shiftType?->full_day === 2 &&
                    !in_array($record->shift_type, [14, 15, 16, 17, 18, 27])
                )
                ->count();

            $annual_half = $shiftRecords
                ->filter(fn($record) => $record->shiftType?->full_day === 1)
                ->count();
            $condolence_leave = $user->shift_records->where('shift_type', 14)->count();
            $transfer_leave = $user->shift_records->where('shift_type', 15)->count();
            $oda_leave = $user->shift_records->where('shift_type', 16)->count();
            $comp_holiday = $user->shift_records->where('shift_type', 17)->count();
            $spec_holiday = $user->shift_records->where('shift_type', 27)->count();
            $over_time = $user->time_card_records->sum('over_time');
            $mileage = $user->time_card_records->sum('car_mileage');
            $annual_costs = 0;
            $annual_incentive = 0;
            $annual_costs = timecardCostRecord::where('user_id', $user->id)
                                            ->where('date_month', $currentDate)
                                            ->select('expenses')
                                            ->sum('expenses');
            if($user->position_id == 15){
                $annual_incentive = timecardIncentive::where('user_id', $user->id)
                                            ->where('date_month', $currentDate)
                                            ->select('count')
                                            ->sum('count');
            }
            
            $month_over_time = 0;
            $annual_calc = $annual_full * $user->work_time_day + $annual_half * $user->work_time_day / 2;
            $annual_leave += $annual_calc;
            $all_worked_time = ($worked_time + $annual_leave) + ($condolence_leave + $transfer_leave + $oda_leave + $comp_holiday + $spec_holiday) * $user->work_time_day;
            if ($shift_work_hours < $all_worked_time) {
                $month_over_time = $all_worked_time - $shift_work_hours - $night_over_time;
            }
            if ($user->work_type == 1) {
                $month_over_time = $over_time + $holiday_worked_time; 
            }
            $month_stay_allowance_count = $this->monthlyAllowanceCount($user, 1);
            $month_move_allowance_count = $this->monthlyAllowanceCount($user, 0);
            $month_waiting_allowance_count = $this->monthlyAllowanceCount($user, 2);
            $month_remote_personal_allowance_count = $this->monthlyAllowanceCount($user, 5);
            $month_remote_company_allowance_count = $this->monthlyAllowanceCount($user, 4);
            $month_vehicle_allowance_count = $this->monthlyAllowanceCount($user, 6);
            $month_special_commute_allowance_count = $this->monthlyAllowanceCount($user, 7);
            $attendance_flag = !empty($attendance) ? true : false;
            $totalTrainingMinutes = $user->time_card_records->sum(function ($timecard) {
                $segmentTrainingMinutes = $timecard->project_segments
                    ->where('segment_type', TimecardProjectSegment::TYPE_TRAINING)
                    ->sum('minutes');

                return $segmentTrainingMinutes > 0 ? $segmentTrainingMinutes : $timecard->training_minutes;
            });
            $responseArray[$user->id] = [
                'user' => $userData,
                'attendance_flag' => $attendance_flag,
                'shift_count' => $shift_count,
                'should_work' => $shift_work_hours,
                'should_work_days' => $workdayNum,
                'planned_work' => $planned_work_hours,
                'shift_holidays' => $shift_holidays->count(),
                'legal_holidays' => $legal_holidays->count(),
                'holiday_count' => $worked_holiday_count,
                'legal_holiday_count' => $legal_holiday_worked_count,
                'workedday_count' => $workedday_count,
                'approved_count' => $approved_count,
                'unapproved_count' => $unapproved_count,
                'unsaved_count' => $unsaved_count,
                'annual_leave' => $annual_leave,
                'condolence_leave' => $condolence_leave,
                'transfer_leave' => $transfer_leave,
                'comp_holiday' => $comp_holiday,
                'special_holiday' => $spec_holiday,
                'oda_leave' => $oda_leave,
                'month_over_time' => $month_over_time > 0 ? $month_over_time : 0,
                'over_time' => $over_time,
                'month_stay_allowance_count' => $month_stay_allowance_count,
                'month_move_allowance_count' => $month_move_allowance_count,
                'month_waiting_allowance_count' => $month_waiting_allowance_count,
                'month_remote_personal_allowance_count' => $month_remote_personal_allowance_count,
                'month_remote_company_allowance_count' => $month_remote_company_allowance_count,
                'month_vehicle_allowance_count' => $month_vehicle_allowance_count,
                'month_special_commute_allowance_count' => $month_special_commute_allowance_count,
                'worked_time' => $worked_time,
                'holiday_worked_time' => $holiday_worked_time,
                'legal_holiday_worked_time' => $legal_holiday_worked_time,
                'night_over_time' => $night_over_time,
                'annual_costs' => $annual_costs,
                'annual_incentives' => $annual_incentive,
                'unapproved_shift_count' => $unapproved_shift_count,
                'mileage' => $mileage,
                'month_training_minutes' => $totalTrainingMinutes
            ];
        }
        return $responseArray;
    }

    private function monthlyAllowanceCount(User $user, int $allowanceValue): int
    {
        $legacyAllowancesByTimecard = $user->custom_field_data_records
            ->whereNotNull('table_record_id')
            ->groupBy('table_record_id');

        return $user->time_card_records->sum(function ($timecard) use ($allowanceValue, $legacyAllowancesByTimecard) {
            if ($timecard->project_segments->isNotEmpty()) {
                return $timecard->project_segments->sum(function (TimecardProjectSegment $segment) use ($allowanceValue) {
                    return collect($this->segmentAllowanceValues($segment))
                        ->filter(fn (int $value) => $value === $allowanceValue)
                        ->count();
                });
            }

            return ($legacyAllowancesByTimecard->get($timecard->id) ?? collect())
                ->where('value_int', $allowanceValue)
                ->count();
        });
    }

    private function segmentAllowanceValues(TimecardProjectSegment $segment): array
    {
        $detailValues = is_array($segment->detail_values) ? $segment->detail_values : [];
        $allowances = $detailValues['allowance'] ?? [];
        if (!is_array($allowances)) {
            return [];
        }

        return collect($allowances)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }
}
