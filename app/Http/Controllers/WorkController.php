<?php

namespace App\Http\Controllers;
use DateTime;
use App\Models\User;

use App\Models\shiftType;
use App\Models\shiftRecord;

use App\Models\timecardRecord;

use App\Models\customFieldDataRecord;
use App\Models\customFieldPartsRecord;

use App\Models\workGroup;
use App\Models\workTemp;
use App\Models\attendanceRecord;
use App\Models\ShiftOvertimeRequest;
// use App\Models\shiftApplyRequest;
use App\Services\SharedService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

use Carbon\Carbon;


class WorkController extends Controller
{
    protected $sharedService;
    public function __construct(SharedService $sharedService) {
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
    //
    public function index(Request $request){
        
    }
    public function getWorkData(Request $request) {
        
        $users_list = $request->work_group ?? [Auth::id()];
          
        if($request->current_date){
            [$currentYear, $currentMonth] = explode('-', $request->current_date);
        }else{
            $current_date = Carbon::now()->format('Y-m');
            [$currentYear, $currentMonth] = explode('-', $current_date);
        }

        $time_card_record = timecardRecord::whereYear('day', $currentYear)
            ->whereMonth('day', $currentMonth)
            ->whereIn('user_id', $users_list)
            ->where('deleted_flag', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $month_over_time = $time_card_record->groupBy('user_id')->map(function ($records) {
            return $records->sum('over_time');
        });

        $month_work_time = $time_card_record->groupBy('user_id')->map(function ($records) {
            return $records->sum('work_time');
        });

        $user_record = User::whereIn('id', $users_list)->select('name', 'id', 'work_type', 'work_time_day', 'work_authority', 'icon_id', 'position_id', 'user_code')->get();

        $custom_data = customFieldDataRecord::whereIn('user_id', $users_list)
        ->whereYear('date', $currentYear)
        ->whereMonth('date', $currentMonth)
        ->whereIn('type_id', [41, 43])
        ->get();

        $custom_weather_data = $custom_data->where('type_id', 43)->groupBy('user_id');
        $custom_achievement_data = $custom_data->where('type_id', 41)->groupBy('user_id');

        $mostCommonAchievementPerUser = $custom_achievement_data->map(function ($userRecords) {
            $valueCounts = $userRecords->pluck('label')->countBy();
            return $valueCounts->sortDesc()->keys()->first();
        });

        $mostCommonWeatherPerUser = $custom_weather_data->map(function ($userRecords) {
            $valueCounts = $userRecords->pluck('value_int')->countBy();
            return $valueCounts->sortDesc()->keys()->first();
        });

        $shift_record = shiftRecord::whereYear('shift_day', $currentYear)
            ->whereMonth('shift_day', $currentMonth)
            ->whereIn('user_id', $users_list)
            ->with(['shiftType'])
            ->orderBy('created_at', 'desc')
            ->get();

        $annual_leave = $shift_record->groupBy('user_id')->map(function ($records) {
            return $records->sum(function ($record) {
                return $record->shiftType->value;
            });
        });
        $attendance_flag = attendanceRecord::where('date_year_month', $request->current_date)
                            ->whereIn('user_id', $users_list)
                            ->exists();
        $month_average_data = [];
        $lastDay = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth()->day;
        foreach($user_record as $user){
            switch ($user->position_id) {
                case 12:
                    $holidayNum = 9;
                    break;
                default: 
                    switch ($currentMonth) {
                        case 12: 
                            $holidayNum = 10;
                            break;
                        case 1: 
                            $holidayNum = 12;
                            break;
                        default: 
                            switch ($lastDay) {
                                case 29: 
                                    $holidayNum = 8.5;
                                    break;
                                case 28: 
                                    $holidayNum = 8;
                                    break;
                                default:
                                    $holidayNum = 9;
                            }
                    }
            }
            $workdayNum = $lastDay - $holidayNum;
            $shift_work_hours = $workdayNum * $user->work_time_day;
            if($user->work_type == 0 && $month_over_time && $month_work_time){
                if(isset($month_work_time[$user->id]) && isset($month_over_time[$user->id]) && isset($annual_leave[$user->id])){
                    $all_work_hours = $annual_leave[$user->id] + $month_work_time[$user->id];
                    $month_over_time[$user->id] = $all_work_hours - $shift_work_hours; 
                }  
            }
            $month_average_data[$user->id] = [
                'month_over_time' => (isset($month_over_time[$user->id]) && $month_over_time[$user->id] >= 0) ? $month_over_time[$user->id] : null,
                'month_work_time' => $month_work_time[$user->id] ?? null,
                'month_weather_average' => $mostCommonWeatherPerUser[$user->id] ?? null,
                'month_achievement_average' => $mostCommonAchievementPerUser[$user->id] ?? null,
                'month_should_work_time' => $shift_work_hours,
                'month_annual_leave' => $annual_leave[$user->id] ?? null
            ];
        }
        $responseArray = [
            'user_data' => $user_record,
            'month_average' => $month_average_data,
            "attendance_flag" => $attendance_flag
        ];

        return response()->json($responseArray);
    }
    public function get_temp_data(Request $request){
        $notificationUser = User::select('name', 'id', 'icon_id')->findOrFail(610);
        $tempData = workTemp::where('user_code', $request->user_code)->first();
        
        
        if ($tempData) {
            $startDate = $tempData->date;
            $endDate = Carbon::parse($startDate)->addYear()->format('Y-m-d');
            $tempData['notification_user'] = $notificationUser;
            $tempData['endDate'] = $endDate;
            $planned_shifts = shiftRecord::whereBetween('shift_day', [$startDate, $endDate])->where('shift_type', 3)->where('user_id', Auth::id())->count();
            $plannedDateCarbon = Carbon::createFromFormat('Y-m-d', $startDate);
            $remaining_days = $plannedDateCarbon->year === 2023 ? 0 : $tempData->planned_days - $planned_shifts;
            if($remaining_days > 0){
                $data = [
                    "shift_count" => $planned_shifts,
                    "tempData" => $tempData,
                    "remaining_days" => $remaining_days,
                ];
                return response()->json($data);
            }
        }
        return response()->json('no data', 200);
    }
    public function get_shift_data_table(Request $request){
        $requestDateString = $request->current_date;
        $users_list = $request->work_group ?? [Auth::id()];
        list($year, $month) = explode("-", $requestDateString);
        $users = User::whereIn('id', $users_list)->with(['time_card_records' => function($q) use($year, $month) {
            $q->whereYear('day', $year)->whereMonth('day', $month)
                ->with(['custom_field_data_records' => function ($q) {
                    $q->whereIn('type_id', [37, 40, 39, 41])->orderBy('created_at', 'desc')->select('id', 'table_record_id', 'type_id', 'value_text', 'value_int', 'date', 'label', 'user_id');
                }])
                ->select('id', 'break_time', 'end_time', 'day', 'over_time', 'stamp_flag', 'start_time', 'status_flag', 'work_time', 'user_id');
        }])->with(['shift_records' => function ($q) use($year, $month) {
            $q->whereYear('shift_day', $year)->whereMonth('shift_day', $month)
                ->with(['shiftType', 'overtime_request'])
                ->select('id', 'shift_day', 'shift_type', 'user_id', 'start_time', 'end_time');
        }])->with(['custom_field_data_records' => function ($q) use($year, $month) {
            $q->whereYear('date', $year)->whereMonth('date', $month)
                ->where('type_id', 43);
        }])->get();        
        $recordList = [];
        
        for ($day = 1; $day <= Carbon::createFromDate($year, $month)->daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
        
            foreach ($users as $index => $user) {
                $data['day_full'] = $date->format('Y-m-d');
                $data['day_show'] = $index == 0 ? $date->format('Y-m-d') : '';
                $data['user_name'] = $user->name;
                $data['user_id'] = $user->id;
                $data['work_authority'] = $user->work_authority;
                $data['work_time_day'] = $user->work_time_day;
                $data['flex'] = $user->work_type == 0;
                $data['last'] = count($users_list) - 1 == $index;
                $targetShiftDay = $date->format('Y-m-d');
        
                $data['shift'] = $user->shift_records->where('shift_day', $targetShiftDay)->first();
                $data['time_card'] = $user->time_card_records->where('day', $targetShiftDay)->first();
                $data['weather'] = $user->custom_field_data_records->where('date', $targetShiftDay)->first()?->value_int;
                $recordList[] = $data;
            }
        }
        
        return response()->json($recordList);
    }
    public function getShiftData(Request $request){
        $users_list = $request->work_group ?? [Auth::id()];
        [$currentYear, $currentMonth] = explode('-', $request->current_date);
        $user = User::select('user_code')->findOrFail($users_list[0]);
        $user_code = $user->user_code;
        
        $auth_user = Auth::user();
       
        $shift_record = shiftRecord::whereYear('shift_day', $currentYear)
                        ->whereMonth('shift_day', $currentMonth)
                        ->where('user_id', $users_list[0])
                        ->with(['shiftType'])
                        ->orderBy('created_at', 'desc')
                        ->get();
        // $shift_apply = shiftApplyRequest::where('shift_month', $request->current_date)
        //                 ->where('user_id', $request->work_group[0])
        //                 ->first();
        $between_records = 0;
        $remaining_days = 0;
        $work_temp = workTemp::where('user_code', $user_code)->first();
        if($work_temp){
            $planned_date = $work_temp->date;
            $until_next = Carbon::parse($planned_date)->addYear()->format('Y-m-d');
            $between_records = shiftRecord::whereBetween('shift_day', [$planned_date, $until_next])->where('shift_type', 3)->where('user_id', $users_list[0])->count();
            $plannedDateCarbon = Carbon::createFromFormat('Y-m-d', $planned_date);
            $remaining_days = $plannedDateCarbon->year === 2023 ? 0 : $work_temp->planned_days - $between_records;
        }
        $shift_type = $auth_user->position_id <= 11
                      ? shiftType::where('deleted_flag', 0)->get()
                      : shiftType::where('id','!=', 14)->where('id','!=', 15)->get();
        $data = [
            "shift_record" => $shift_record,
            "shift_type" => $shift_type,
            "workTemp" => $work_temp ? $work_temp : null,
            "consumed_days" => $remaining_days > 0 ? $between_records : 0,
            "remaining_days" => $remaining_days > 0 ? $remaining_days : 0,
            // "shift_apply" => $shift_apply,
        ];
        

        return response()->json(
            $data
        );
    }
    public function get_shift_types(){
        $auth_user = Auth::user();
        $shift_type = $auth_user->position_id <= 11
        ? shiftType::where('deleted_at', null)->get()
        : shiftType::where('id', '!=', 14)->where('id', '!=', 15)->get();
        $planned_record = shiftRecord::where('user_id', Auth::id())
                            ->where('shift_type', 3)
                            ->orderBy('created_at', 'desc')
                            ->select('shift_day AS date', 'shift_type AS type', 'status_flag')
                            ->get();
        $data = [
            'shift_type' => $shift_type,
            'planned_record' => $planned_record
        ];
        return response()->json($data);
    }
    // public function shift_approve(Request $request){
    //     $user = $this->active_user();
    //     $request->validate([
    //         'id' => 'required',
    //     ]);
    //     $shiftApproved = shiftApplyRequest::findOrFail($request->id)->update([
    //         "status" => $request->status,
    //         "approved_by" => $user->id
    //     ]);
    //     return response()->json([
    //         'data' => $shiftApproved ?? null
    //     ]);
    // }
    // public function shift_remand(Request $request){
    //     $request->validate([
    //         'id' => 'required',
    //     ]);
    //     $shiftApproved = shiftApplyRequest::findOrFail($request->id)->delete();

    //     return response()->json($shiftApproved);
    // }
    public function shiftAdd(Request $request)
    {
        $user = $this->active_user();
        $auth_id = $user->id;
        $user_id = $request->userId;
        $shift_array = $request->shift_array;
        $start_time_val = $request->shiftTimeStart;
        $end_time_val = $request->shiftEndStart;
        $types = [0, 2, 3, 5, 14, 15];
        [$year, $month] = explode('-', $request->yearMonth);
        $shift_days = collect($shift_array)->pluck('date')->toArray();
        $holidays = collect($shift_array)->filter(function ($shift) use($types) {
            return in_array($shift['type'], $types);
        })->pluck('date')->toArray();
        $overtimeCheck = shiftRecord::where('user_id', $user_id)
            ->whereIn('shift_day', $holidays)
            ->whereHas('overtime_request')
            ->exists();
        $holidays1 = collect($shift_array)->filter(function ($shift) use($types) {
            return $shift['type'] !== 0;
        })->pluck('date')->toArray();
        $waitingAllowanceCheck = timecardRecord::where('user_id', $user_id)
            ->whereIn('day', $holidays1)
            ->whereHas('custom_field_data_records', function($q) {
                $q->where('type_id', 37)->where('value_int', 2);
            })
            ->exists();
        if($waitingAllowanceCheck){
            throw ValidationException::withMessages(['message' => '「待機手当」は休日のみ支給されます。']);
        }
        if($overtimeCheck){
            throw ValidationException::withMessages(['message' => '残業申請の日をお休みにすることができません。もう一回確認ください。']);
        }
        $shift_record_check = shiftRecord::where('user_id', $user_id)
            ->whereIn('shift_day', $shift_days)
            ->get()
            ->keyBy('shift_day');
        foreach ($shift_array as $shift) {
            $status_flag = $shift['type'] === 3 ? 1 : 0;
            $planned_year = $shift['type'] === 3 ? $request->planned_year : $request->year;
            if ($shift_record_check->has($shift['date'])) {
                $shift_record = $shift_record_check[$shift['date']];
                if ($shift_record->shift_type !== $shift['type']) {
                    $shift_record->shift_type = $shift['type'];
                }
                $shift_record->start_time = $start_time_val;
                $shift_record->end_time = $end_time_val;
                if($shift_record->status_flag !== 1 || $auth_id == 610){
                    $shift_record->status_flag = $status_flag;
                    $shift_record->update();
                }
            } else {
                $shift_record = shiftRecord::create([
                    'user_id' => $user_id,
                    'shift_day' => $shift['date'],
                    'shift_type' => $shift['type'],
                    'start_time' => $start_time_val,
                    'end_time' => $end_time_val,
                    'status_flag' => $status_flag,
                    'planned_year' => $planned_year,
                    // 'shift_month' => $request->yearMonth
                ]);
            }
            // $applyRequest = shiftApplyRequest::firstOrCreate([
            //     'shift_month' => $request->yearMonth,
            //     'user_id' => $user_id
            // ]);

            // $shift_record->apply_request()->associate($applyRequest);
            // $shift_record->save();
        }
        $this->sharedService->syncShiftToCalendar($user_id, $year, $month);

        return response()->json($request);
    }
    public function getWorkGroup(Request $request){
        $user = $this->active_user();
        $auth_user_id = $user->id;
        $ids = [608, 610];
        $authenticatedUserId = Auth::id();
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        if($auth_user_id == 608 || $auth_user_id == 610){
            $work_group_users = User::where('deleted_flag', 0)
                        ->where('partner_flag', 0)
                        ->where('hide_flag',0)
                        ->where('retire', 0)
                        ->whereNotIn('id', $ids)
                        ->whereNotIn('name', $ng_list)
                        ->select('id', 'name', 'icon_id', 'name_kana')
                        ->orderByRaw("id = $authenticatedUserId desc") // Sorting by whether the ID matches the authenticated user's ID
                        ->orderBy('id', 'asc')
                        ->get();
        }else{
            $work_group_list = workGroup::whereHas('members', function($q) use($auth_user_id) {
                                $q->whereIn('users.id', [$auth_user_id]);
                            })->with(['members' => function($q) use($ids) {
                                $q->whereNotIn('users.id', $ids)
                                    ->where('users.partner_flag', 0)
                                    ->where('users.hide_flag',0)
                                    ->where('users.retire', 0);
                            }])
                            ->get();
            $work_group_users = $work_group_list->flatMap(function ($work_group_list_value) {
                return $work_group_list_value->members;
            })->unique('id')->values()->all();

            usort($work_group_users, function ($a, $b) use ($authenticatedUserId) {
                if ($a->id == $authenticatedUserId) {
                    return -1; // $a comes first if it's the authenticated user
                } elseif ($b->id == $authenticatedUserId) {
                    return 1; // $b comes first if it's the authenticated user
                } else {
                    return $a->id - $b->id; // Sort by user ID if neither is the authenticated user
                }
            });

        }   
        
        

        return response()->json($work_group_users);
    }
    public function dailyReportAdd(Request $request){
        $exist_timecard = timecardRecord::where('day', $request->day)->where('user_id', Auth::id())->first();
        if($exist_timecard && !empty($exist_timecard->start_time)){
            $exist_timecard->end_time = $request->end_time;
            $exist_timecard->stamp_flag = 1;
            $exist_timecard->save();
            return response()->json($exist_timecard);
        }else{
            $timecard = new timecardRecord;
            $timecard->user_id = Auth::id();
            $timecard->day = $request->day;
            $timecard->start_time = $request->start_time;
            $timecard->stamp_flag = 0;
            $timecard->save();
            return response()->json($timecard);
        }
    }
    private function breakTimeCheck($request){
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        $breakTime = $request->breakTime;

        $startDateTime = new DateTime($startTime);
        $endDateTime = new DateTime($endTime);

        $workTimeMinutes = ($endDateTime->format('H') * 60 + $endDateTime->format('i')) - ($startDateTime->format('H') * 60 + $startDateTime->format('i')) - $breakTime;

        if ($workTimeMinutes >= 360 && $breakTime < 60) {
            throw ValidationException::withMessages(['message' => '6時間以上の勤務の場合、最低でも60分間の休憩を取る必要があります。']);
        } elseif ($workTimeMinutes >= 180 && $workTimeMinutes < 360 && $breakTime < 30) {
            throw ValidationException::withMessages(['message' => '30時間以上の勤務の場合、最低でも30分間の休憩を取る必要があります。']);
        }
    }
    private function overTimeCheck($request, $calculatedMinute){
        $overTimeRequest = ShiftOvertimeRequest::where('overtime_day', $request->day)->where('user_id', $request->userId)->first();
        if($calculatedMinute > $overTimeRequest->minutes){
            $overTimeRequest->status = 1;
        } 
        $overTimeRequest->minutes = $calculatedMinute;
        $overTimeRequest->save();
    }
    public function saveTimeCard(Request $request){
        $today = Carbon::now()->isoFormat('YYYY-MM-DD');
        $this->breakTimeCheck($request);
        
        $user = User::select('work_time_day', 'work_type', 'id', 'name')->findOrFail($request->userId);
        $fields = ['comment', 'incident', 'achievement', 'allowance'];
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);
        $nightOvertimeStart = Carbon::createFromFormat('H:i', '22:00')->subDay();
        $nightOvertimeEnd = Carbon::createFromFormat('H:i', '05:00');
        $todayNightOverTime = Carbon::createFromFormat('H:i', '22:00');
        if($end->lt($start)){
            $start->subDay();
        }
        $shift_time_difference_seconds = ($user->work_time_day * 60);
        $shift_time_difference_seconds = max(0, $shift_time_difference_seconds);
        
        $time_difference_seconds = $end->diffInSeconds($start);
        $time_difference_seconds -= $request->breakTime * 60;
        $time_difference_seconds = max(0, $time_difference_seconds);
        
        $night_difference_seconds = 0;
        $overtimeMinutes = 0;
        if ($start->between($nightOvertimeStart, $nightOvertimeEnd)) {
            $night_difference_seconds = $end->between($nightOvertimeStart, $nightOvertimeEnd) ? $end->diffInSeconds($start) : $nightOvertimeEnd->diffInSeconds($start);
        } else if ($end->between($nightOvertimeStart, $nightOvertimeEnd)) {
            $night_difference_seconds = $end->diffInSeconds($nightOvertimeStart);
        } else if ($end->greaterThan($todayNightOverTime)){
            $night_difference_seconds = $end->diffInSeconds($todayNightOverTime);
        } else {
            $night_difference_seconds = 0;
        }
        if($night_difference_seconds >= 360 * 60 || ($night_difference_seconds >= 180 * 60 && $night_difference_seconds < 360 * 60)){
            $night_difference_seconds -= $request->breakTime * 60;
        }
        $this->checkWaitingAllowance($request);
        $is_exist = timecardRecord::firstOrCreate([
            'day' => $request->day,
            'user_id' => $request->userId
        ]);
        $is_exist->start_time = $request->start_time;
        $is_exist->end_time = $request->end_time;
        if ($time_difference_seconds >= $shift_time_difference_seconds) {                
            $overtimeSeconds = $time_difference_seconds - $shift_time_difference_seconds;
            $overtimeMinutes = floor($overtimeSeconds / 60);
            $is_exist->over_time = $overtimeMinutes;
        } else {
            $latetimeSeconds = $shift_time_difference_seconds - $time_difference_seconds;
            $latetimeMinutes = floor($latetimeSeconds / 60);
            $is_exist->late_time = $latetimeMinutes;
        }
        if (isset($night_difference_seconds) && $night_difference_seconds > 0) {
            $nighttimeMinutes = floor($night_difference_seconds / 60);
            $is_exist->night_over_time = $nighttimeMinutes;
        }else{
            $is_exist->night_over_time = 0;
        }
        $minutes = floor($time_difference_seconds / 60);
        $is_exist->work_time = $minutes;
        $is_exist->edit_start_time = $request->start_time;
        $is_exist->edit_end_time = $request->end_time;
        
        $is_exist->break_time = $request->breakTime;
        $is_exist->stamp_flag = 1;
        
        $is_exist->status_flag = $request->status_flag;
        
        if($today != $request->day){
            $is_exist->work_time_edit_flag = 1;
        }
        foreach ($request->customValues as $key => $field) {
            
            $customFieldData = customFieldDataRecord::where('table_record_id', $is_exist->id)
                ->where('user_id', $request->userId)
                ->where('type_id', $key)
                ->get();
            if($customFieldData){
                $customFieldData->each->delete();
            }
            if ($key == 37) {
                if(is_array($field)){
                    foreach ($field as $val) {
                        $this->saveCustomData($request->day, $is_exist->id, $request->userId, $val, $key);
                    }
                }
                
            } else {
                $this->saveCustomData($request->day, $is_exist->id, $request->userId, $field, $key);
            }
            
        }
        $is_exist->save();
        if($request->overTimeMinute){
            $this->overTimeCheck($request, $overtimeMinutes);
        }

        response()->json(['success' => 'success'], 200); 
    }
    private function checkWaitingAllowance($request){
        [$currentYear, $currentMonth] = explode('-', $request->day);
            $count = customFieldDataRecord::where('type_id', 37)
                                        ->where('user_id', $request->userId)
                                        ->where('value_int', 2)
                                        ->whereYear('date', $currentYear)
                                        ->whereMonth('date', $currentMonth)
                                    ->count();
        if($count >= 3){
            throw ValidationException::withMessages(['message' => '待機手当は1か月に3回以上の利用はできません。']);
        }
    }
    private function saveCustomData($date, $table_record_id, $user_id, $value, $type_id){
        $new_custom_data = new customFieldDataRecord;
        $new_custom_data->date = $date;
        $new_custom_data->table_record_id = $table_record_id;
        $new_custom_data->user_id = $user_id;
        $new_custom_data->type_id = $type_id;
        
        switch ($type_id) {
            case 39:
                $new_custom_data->value_text = $value;
                break;
            default: 
                $new_custom_data->value_int = $value;
                $partsRecord = customFieldPartsRecord::where('record_id', $type_id)
                                                    ->where('parts_value', $value)
                                                    ->select('parts_lavel')
                                                    ->first();
                $new_custom_data->label	= $partsRecord->parts_lavel;
        }
        $new_custom_data->save();
    }
    public function deleteTimeCard(Request $request){
        $is_exist = timecardRecord::where('day', $request->date)->where('user_id', $request->userId)->first();
        if($is_exist){
            $custom_field = customFieldDataRecord::where('table_record_id', $is_exist->id)
            ->where('user_id', $request->userId)
            ->where('date', $request->date)
            ->get();
            if($custom_field){
                $custom_field->each->delete();
            }
            $is_exist->delete();

            response()->json(['success' => 'success'], 200);
        }

        response()->json(['not found' => 'not found'], 404);
    }
    public function getAttendanceData(Request $request){
        $user_list = $request->work_group ?? [Auth::id()];
        [$currentYear, $currentMonth] = explode('-', $request->current_date);
        $formattedDate = date('Y-m', strtotime($request->current_date));
        $user = User::with([
            'attendance_records' => function ($query) use ($formattedDate) {
                $query->where('date_year_month', $formattedDate);
            },
            'shift_records' => function ($query) use ($currentYear, $currentMonth) {
                $query->whereYear('shift_day', $currentYear)
                    ->whereMonth('shift_day', $currentMonth)
                    ->select('user_id', 'shift_day', 'shift_type')->with('shiftType');
            },
            'time_card_records' => function ($query) use ($currentYear, $currentMonth) {
                $query->whereYear('day', $currentYear)
                    ->whereMonth('day', $currentMonth)
                    ->select('user_id', 'day', 'work_time', 'over_time', 'status_flag', 'late_time', 'night_over_time', 'stamp_flag');
            },
            'custom_field_data_records' => function ($query) use ($currentYear, $currentMonth) {
                $query->where('type_id', 37)
                    ->whereYear('date', $currentYear)
                    ->whereMonth('date', $currentMonth)
                    ->select('value_int', 'user_id');
            }
        ])->select('id','name','work_type', 'work_time_day', 'user_code', 'position_id')->findOrFail($user_list[0]);        
        $monthNum = (int)$currentMonth;
        $lastDay = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth()->day;
        switch ($user->position_id) {
            case 12:
                $holidayNum = 9;
                break;
            default: 
                switch ($monthNum) {
                    case 12: 
                        $holidayNum = 10;
                        break;
                    case 1: 
                        $holidayNum = 12;
                        break;
                    default: 
                        switch ($lastDay) {
                            case 29: 
                                $holidayNum = 8.5;
                                break;
                            case 28: 
                                $holidayNum = 8;
                                break; 
                            default:
                                $holidayNum = 9;
                        }
                }
        }
        $workdayNum = $lastDay - $holidayNum;
        $hiddenAttributes = ['attendance_records', 'shift_records', 'time_card_records', 'custom_field_data_records'];
        $userData = $user->makeHidden($hiddenAttributes);
        $attendance = $user->attendance_records->first();
        $shift_count = $user->shift_records->where('shift_type', '!=', 0)->count();
        $shift_holidays = $user->shift_records->where('shift_type', 0)->pluck('shift_day');
        $worked_holiday_count = $user->time_card_records->whereIn('day', $shift_holidays)->count();
        $workedday_count = $user->time_card_records->count();
        $worked_time = $user->time_card_records->sum('work_time');
        $holiday_worked_time = $user->time_card_records->whereIn('day', $shift_holidays)->sum('work_time');
        $approved_count = $user->time_card_records->where('status_flag', 2)->count();
        $unapproved_count = $user->time_card_records->where('status_flag', 1)->count();
        $unsaved_count = $user->time_card_records->where('stamp_flag', 1)->whereIn('status_flag', [0, 10])->count();
        $night_over_time = $user->time_card_records->sum('night_over_time');
        $annual_leave = 0;
        foreach ($user->shift_records as $shift_record) {
            $shiftType = $shift_record->shiftType;
            $annual_leave += $shiftType->value;
        }
        $shift_work_hours = ($user->work_time_day * $workdayNum);
        
        $condolence_leave = $user->shift_records->where('shift_type', 14)->count();
        $transfer_leave = $user->shift_records->where('shift_type', 15)->count();
        $over_time = $user->time_card_records->sum('over_time');
        $late_time = $user->time_card_records->sum('late_time');
        if($over_time >= $late_time){
            switch($user->work_type) {
                case 1:
                    break;
                default:
                    $over_time -= $late_time;
            }
        }else{
            switch($user->work_type) {
                case 1:
                    break;
                default:
                    $over_time = 0;
            } 
        }
        $month_over_time = 0;
        if($user->work_type == 0){
            $all_worked_time = $worked_time + $annual_leave;
            $month_over_time = $all_worked_time - $shift_work_hours - $night_over_time;
        }else{
            $month_over_time = $over_time;
        }
        
        $month_stay_allowance_count = $user->custom_field_data_records->where('value_int', 1)->count();
        $month_move_allowance_count = $user->custom_field_data_records->where('value_int', 0)->count();
        $attendance_flag = !empty($attendance) ? true : false;
        $responseArray = [
            'user' => $userData,
            'attendance_flag' => $attendance_flag,
            'shift_count' => $shift_count,
            'should_work' => $shift_work_hours,
            'should_work_days' => $workdayNum,
            'shift_holidays' => $shift_holidays->count(),
            'holiday_count' => $worked_holiday_count,
            'workedday_count' => $workedday_count,
            'approved_count' => $approved_count,
            'unapproved_count' => $unapproved_count,
            'unsaved_count' => $unsaved_count,
            'annual_leave' => $annual_leave,
            'condolence_leave' => $condolence_leave,
            'transfer_leave' => $transfer_leave,
            'month_over_time' => $month_over_time > 0 ? $month_over_time : 0,
            'over_time' => $over_time,
            'month_stay_allowance_count' => $month_stay_allowance_count,
            'month_move_allowance_count' => $month_move_allowance_count,
            'worked_time' => $worked_time,
            'holiday_worked_time' => $holiday_worked_time,
            'night_over_time' => $night_over_time,
        ];

        return response()->json($responseArray);
    }
    public function remandTimeCard(Request $request){
        $user = $this->active_user();
        $time_card_record = timecardRecord::where('user_id', $request->user_id )->where('day', '=' , $request->record_day )->first();
        if($request->overTimeRequest){
            $data = [
                'id' => $request->overTimeRequest['id'],
                'status' => 0,
                'approved_by' => $user->id
            ];
            $this->respond_overtime(new Request ($data));
        }
        if(!empty($time_card_record)){
            $time_card_record->status_flag = 10;
            $time_card_record->save();
        }

        return response()->json($time_card_record);

    }
    public function approveTimeCard(Request $request){
        $user = $this->active_user();
        $time_card_record = timecardRecord::where('user_id', $request->user_id )->where('day', $request->record_day )->first();
        if($request->overTimeRequest){
            $data = [
                'id' => $request->overTimeRequest['id'],
                'status' => 2,
                'approved_by' => $user->id
            ];
            $this->respond_overtime(new Request ($data));
        }
        if(!empty($time_card_record)){
            $time_card_record->status_flag = 2;
            $time_card_record->save();
        }

        return response()->json($time_card_record);

    }


    public function cancelTimeCard(Request $request){

        $time_card_record = timecardRecord::where('user_id', $request->user_id )->where('day', $request->record_day )->first();

        if(!empty($time_card_record)){
            $time_card_record->status_flag = 1;
            $time_card_record->save();
        }
        

        return response()->json($time_card_record);

    }
    public function attendanceConfirm(Request $request){
        if(!empty($request)){
            [$currentYear, $currentMonth] = explode('-', $request->date_year_month);
            $shift_records = shiftRecord::whereYear('shift_day', $currentYear)
                            ->whereMonth('shift_day', $currentMonth)
                            ->where('user_id', $request->user['id'])->get();
            $user_work_time_day = $request->user['work_time_day'];
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
            $closed_day = $shift_records->where('shift_type', 2)->count();
            $working_hour_low = $shift_records->whereIn('shift_type', [13, 12, 11, 10, 9, 8, 7])->count();
            $condolence_hours = $user_work_time_day * $request->condolence_leave;
            $transfer_hours = $user_work_time_day * $request->transfer_leave;
            $closed_hours = $user_work_time_day * $closed_day;
            $absence_days = ($working_hour_low - $request->worked_days) + $request->holiday_worked_days;
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
            $absence_hours = $request->shift_working_hours - ($request->annual_leave * 60 + $condolence_hours + $transfer_hours + $closed_hours + $request->worked_hours);
            $attendance_record->absence_hour = $absence_hours >= 0 ? $absence_hours : 0;
            $attendance_record->date_year_month = $request->date_year_month;
            $attendance_record->user_id = $request->user['id'];
            $attendance_record->user_code = $request->user['user_code'];
            $attendance_record->name = $request->user['name'];
            $attendance_record->pay_day = 20;
            $attendance_record->month_petition = '済';
            $attendance_record->prescribed_working_hours = $request->shift_working_hours / 60;
            $attendance_record->work_type = $request->user['work_type'] == 0 ? 'フレックス' : '通常';
            $attendance_record->month_petition = '済';
            $attendance_record->working_days_shift = $request->shift_working_days;
            $attendance_record->normal_working_days = $request->worked_days;
            $attendance_record->holiday_working_days = $request->holiday_worked_days;
            $attendance_record->paid_holiday_hours = $request->annual_leave;
            $attendance_record->condolence_holiday = $request->condolence_leave;
            $attendance_record->special_holiday = $request->transfer_leave;
            $attendance_record->closed_day = 0;
            $attendance_record->working_hours = $request->worked_hours;
            $attendance_record->working_hours_no_over = $request->worked_hours_no_over_time;
            $attendance_record->over_time = $request->over_time;
            $attendance_record->night_work_time = $request->night_work_time;
            $attendance_record->stay_pay = $request->stay_pay;
            $attendance_record->move_pay = $request->move_pay;
            $attendance_record->save();

            return response()->json($attendance_record);
        }
        
    }
    public function attendanceDelete(Request $request){
        
        $attendance_record = attendanceRecord::where('user_id', $request->user_id)->where('date_year_month', $request->date_year_month)->first();
        if(!empty($attendance_record)){
            $attendance_record->delete();
        }

        return 'deleted';
    }
    public function notSubmitted(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $today = date("Y-m");
        $year = date("Y");
        $month = date("m");
        if($auth_user_id == 610 || $auth_user_id == 608 || in_array($auth_user->position_id, [1, 2, 3, 4, 5, 14, null])){
            $resposnsArray = [
                'yesterday' => $yesterday,
                'shiftNotSubmittedList' => [],
                'timecardNotSubmittedList' => []
            ];
            return response()->json($resposnsArray);
        }
        $previousMonth = date("Y-m",strtotime('-1 month'));
        $attendanceRecords = attendanceRecord::where('user_id', $auth_user_id)
                                     ->whereIn('date_year_month', [$previousMonth, $today])
                                     ->get();
        $attendance_prev_record = $attendanceRecords->where('date_year_month', $previousMonth)->first();
        $attendance_this_record = $attendanceRecords->where('date_year_month', $today)->first();
        
        $prev_shift_record = empty($attendance_prev_record)
                            ? shiftRecord::where('user_id', $auth_user_id)
                                        ->whereYear('shift_day', $year)
                                        ->whereMonth('shift_day', $month - 1)
                                        ->get()
                            : [];
        
        $shift_record = shiftRecord::where('user_id',$auth_user_id)
                                    ->whereYear('shift_day',$year)
                                    ->whereMonth('shift_day', $month)
                                    ->get();
        $shift_overtime_requests = ShiftOvertimeRequest::where('created_by', $auth_user_id)
                                                        ->whereYear('overtime_day', $year)
                                                        ->whereMonth('overtime_day', $month)
                                                        ->select('overtime_day', 'created_by', 'minutes')
                                                        ->get();
        $shiftNotSubmittedList = [];
        $shiftSubmittedList = [];
        $timecardNotSubmittedList = [];
        $numberOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        if(count($shift_record) < $numberOfDays){
            $shiftNotSubmittedList[] = [
                'year' => $year, 
                'month' => (int) $month , 
                'value' => $today
            ];
        }else{
            if(empty($attendance_this_record)){
                foreach($shift_record as $value){
                    $shiftSubmittedList[$value->shift_day] = $value->shift_type;
                }
                if(!empty($prev_shift_record)){
                    foreach($prev_shift_record as $valuePrev){
                        $shiftSubmittedList[$valuePrev->shift_day] = $valuePrev->shift_type;        
                    }
                }
                foreach($shiftSubmittedList as $date => $value2){
                    if($value2 == "1"){
                        if($date <= $yesterday){
                            $timecard = timecardRecord::where('deleted_at', null)
                                                      ->where('user_id', $auth_user_id )
                                                      ->where('day', $date)->first();
                            if($timecard === null){
                                $dateExplode = explode("-",$date);
                                $timecardNotSubmittedList[] = [
                                    'year' => (int) $dateExplode[0],
                                    'month' => (int) $dateExplode[1],
                                    'day' =>  (int) $dateExplode[2],
                                    'value' => $date,
                                    'shiftType' => $auth_user->work_type,
                                    'shiftEndTime' => $shift_record && count($shift_record) > 0 ? $shift_record[0]->end_time : '18:00:00',
                                    'shiftStartTime' => $shift_record && count($shift_record) > 0 ? $shift_record[0]->start_time : '09:00:00',
                                    'shiftOverTimeRequest' => $shift_overtime_requests->where('overtime_day', $date)->first()
                                ];

                            }else if($timecard->status_flag == 0){
                                $dateExplode = explode("-",$date);
                                $timecardNotSubmittedList[] = [
                                    'year' => (int) $dateExplode[0],
                                    'month' => (int) $dateExplode[1],
                                    'day' =>  (int) $dateExplode[2],
                                    'value' => $date,
                                    'shiftType' => $auth_user->work_type,
                                    'shiftEndTime' => $timecard->edit_end_time,
                                    'shiftStartTime' => $timecard->edit_start_time,
                                    'shiftOverTimeRequest' => $shift_overtime_requests->where('overtime_day', $date)->first()
                                ];
                            }

                        }

                    }
                }
            }
        }
        if(!empty($shift_record)){
            if(!empty($timecardNotSubmittedList)){
                foreach ($timecardNotSubmittedList as $Detail) {
                    $ArrDate[] = $Detail['value'];
                }
                array_multisort($ArrDate, SORT_DESC, SORT_NUMERIC, $timecardNotSubmittedList);
            }
        }
        $resposnsArray = [
            'yesterday' => $yesterday,
            'shiftNotSubmittedList' => $shiftNotSubmittedList,
            'timecardNotSubmittedList' => $timecardNotSubmittedList
        ];
        return response()->json($resposnsArray);
    }
    public function attendanceClose(Request $request){
        $user_id = $request->user['id'];

        $attendance_record = attendanceRecord::where('user_id', '=' , $user_id )->where('date_year_month', '=' , $request->date_year_month )->first();

        $work_type_flag = $request->user['work_type'];
        $work_type = $work_type_flag == 0 ? 'フレックス' : '通常';
        $month_petition = '済';
        $user_code = $request->user->user_code ?? 99999999;
        if(empty($attendance_record)){
            $attendance_record = new attendanceRecord;
            $attendance_record->user_id = $user_id;
            $attendance_record->name = $request->user['name'];
            $attendance_record->user_code = $user_code;
            $attendance_record->date_year_month = $request->date;
            $attendance_record->prescribed_working_hours = 0;
            $attendance_record->work_type = $work_type;
            $attendance_record->month_petition = $month_petition;
            $attendance_record->working_days_shift = 0;
            $attendance_record->normal_working_days = 0;
            $attendance_record->holiday_working_days = 0;
            $attendance_record->paid_holiday_hours = 0;
            $attendance_record->planned_paid_holiday = 0;
            $attendance_record->petitionType8_count = 0;
            $attendance_record->petitionType7_count = 0;
            $attendance_record->petitionType6_count = 0;
            $attendance_record->petitionType5_count = 0;
            $attendance_record->petitionType4_count = 0;
            $attendance_record->petitionType3_count = 0;
            $attendance_record->petitionType2_count = 0;
            $attendance_record->petitionType1_count = 0;
            $attendance_record->working_hours = 0;
            $attendance_record->over_time = 0;
            $attendance_record->status_flag = 1;
            $attendance_record->night_work_time = 0;
            $attendance_record->working_hours_no_over = 0;
            $attendance_record->stay_pay = 0;
            $attendance_record->move_pay = 0;
            $attendance_record->closed_day = 0;
            $attendance_record->half_day_holiday = 0;
            $attendance_record->condolence_holiday = 0;
            $attendance_record->special_holiday = 0;
            $attendance_record->working_days_shift = 0;
            $attendance_record->pay_day = 20;
            $attendance_record->absence_days = 0;
            $attendance_record->absence_hour = 0;
            $attendance_record->save();

        }
        return response()->json($request);
    }

    public function not_approved(Request $request){
        $authUser = Auth::user();
        $year = date('Y');
        $month = date('m');
        $ids = [608, 610];
        if($authUser->position_id == 6){
            $workGroups = workGroup::whereHas('work_group_user', function ($q) use($authUser, $ids) {
                $q->whereIn('user_id', [$authUser->id])->whereNotIn('user_id', $ids);
            })->with(['work_group_user' => function($q) use ($ids, $authUser, $year, $month) {
                $q->whereNotIn('user_id', [$authUser->id])->whereNotIn('user_id', $ids)
                ->whereHas('user', function ($q) {
                    $q->where('work_authority', 0);
                });
                // ->whereHas('timecard_records', function ($q) use ($year, $month) {
                //     $q->whereYear('day', $year)->whereMonth('day', $month)->where('status_flag', 1);
                // })
                
                // ->with(['user' => function($q) {
                //     $q->where('work_authority', 0);
                // }]);
            }])->get();

            $list = [];

            
            // $workGroups = $workGroups->filter(function ($workGroup) {
            //     return $workGroup->work_group_user->isNotEmpty();
            // });
            $workGroups = $workGroups->flatMap(function ($workGroup) {
                return $workGroup->work_group_user;
            })->unique('user_id')->values()->all();
            foreach($workGroups as $user){
                $timeCardsCount = timecardRecord::where('user_id', $user->user_id)->whereYear('day', $year)->whereMonth('day', $month)->where('status_flag', 1)->count();
                $overtimeRequests = ShiftOvertimeRequest::where('user_id', $user->user_id)->where('status', 1)->whereYear('overtime_day', $year)->whereMonth('overtime_day', $month)->count();
                $d = [
                    "user" => $user->user,
                    "timecard" => $timeCardsCount,
                    "overtime" => $overtimeRequests
                ];
                if($timeCardsCount || $overtimeRequests){                    
                    $list[] = $d;
                }
            }
            
            return response()->json($list);
        }
    }
    public function request_overtime(Request $request){
        $request->validate([
            'record_id' => 'required',
        ]);
        $shift = shiftRecord::findOrFail($request->record_id);
        
        $rec = ShiftOvertimeRequest::firstOrCreate([
            "record_id" => $request->record_id
        ])->update([
            "minutes" => $request->minutes,
            "content" => $request->content,
            "status" => $request->status,
            "user_id" => $shift->user_id,
            "created_by" => $request->created_by ? $request->created_by : null,
            "approved_by" => null,
            "overtime_day" => $request->overtime_day,
        ]);
       
        

        return response()->json($rec);
    }
    public function delete_overtime(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $exec = ShiftOvertimeRequest::findOrFail($request->id)->delete();

        return response()->json($exec);
    }
    public function respond_overtime(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        if($request->status == 0){
            ShiftOvertimeRequest::findOrFail($request->id)->delete();
        }else{
            $exec = ShiftOvertimeRequest::findOrFail($request->id)->update([
                "status" => $request->status,
                "approved_by" => $request->approved_by
            ]);
        }   
        

        return response()->json([
            'data' => $exec ?? null
        ]);
    }
    public function work_badge(Request $request){
        $user = $this->active_user();
        if($user->work_authority == 1){
            $ids = [608, 610, $user->id];
            $work_group_list = workGroup::whereHas('members', function($q) use($user) {
                $q->whereIn('users.id', [$user->id]);
            })->with(['members' => function($q) use($ids) {
                $q->whereNotIn('users.id', $ids);
            }])
            ->get();
            $work_group_users = $work_group_list->flatMap(function ($work_group_list_value) {
                return $work_group_list_value->members;
            })->unique('id')->pluck('id')->toArray();
            $today = Carbon::now()->format('Y-m-d');
            $overtime = shiftRecord::where('shift_day', '>=', $today)->whereIn('user_id', $work_group_users)->
            whereHas('overtime_request', function ($q){
                $q->where('status', 1);
            })
            ->with('overtime_request')
            ->get();
            return response()->json($overtime);
        }
        return response()->json([]);
    }
}

