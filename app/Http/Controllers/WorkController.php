<?php

namespace App\Http\Controllers;
use DB;

use DateTime;
use App\Models\User;

use App\Models\shiftType;
use App\Models\shiftRecord;

use App\Models\timecardRecord;
use App\Models\timecardBreakRecord;

use App\Models\customFieldDataRecord;
use App\Models\customFieldPartsRecord;

use App\Models\workGroup;
use App\Models\workGroupUser;
use App\Models\workTemp;
use App\Models\attendanceRecord;
use App\Events\Message;
use App\Services\SharedService;
use Illuminate\Support\Facades\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;

use Log;

class WorkController extends Controller
{
    protected $sharedService;
    public function __construct(SharedService $sharedService) {
        $this->sharedService = $sharedService;
    }
    //
    public function index(Request $request){
        
    }
    public function getWorkData(Request $request) {
        $auth_user_id = Auth::id();
        
        if($request->work_group){
            $users_list = $request->work_group;
        }else{
            $users_list = [$auth_user_id];
        }
        
          
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
            ->with([
                'custom_field_data_records' => function ($q) use($users_list){
                    $q->whereIn('type_id', [37, 40, 39, 41])->whereIn('user_id', $users_list)->orderBy('created_at', 'desc');
                },
                'user' => function ($q) {
                    $q->select('name', 'id', 'work_type', 'work_time_day', 'work_authority');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $month_user_data_array = [];
        $month_achievement_time_array = [];
        $addition_work_time_array = [];
        $custom_field_data = [];
        $month_over_time = [];
        $month_work_time = [];
        $month_late_time = [];
        $grouped_reserved_dates = [];
        foreach ($time_card_record as $reserved_date) {
            $custom_field_data = $reserved_date['custom_field_data_records'] ? $reserved_date['custom_field_data_records']->groupBy('type_id') : [];
            if (!empty($custom_field_data)) {
                $allowance = $custom_field_data->has(37) ? $custom_field_data[37] : '';

                $incidents_group = $custom_field_data->has(40) ? $custom_field_data[40] : [];
                $incident = !empty($incidents_group) ? $incidents_group[0] : '';
            
                $manzoku_group = $custom_field_data->has(38) ? $custom_field_data[38] : [];
                $manzoku = !empty($manzoku_group) ? $manzoku_group[0] : '';
            
                $comment_group = $custom_field_data->has(39) ? $custom_field_data[39] : [];
                $comment = !empty($comment_group) ? $comment_group[0] : '';

                $achievement_group = $custom_field_data->has(41) ? $custom_field_data[41] : [];
                $achievement = !empty($achievement_group) ? $achievement_group[0] : '';
            }
            $user = $reserved_date['user'];
            $user_id = $reserved_date['user_id'];
            if (isset($reserved_date['over_time'])) {
                if (!isset($month_over_time[$user_id])) {
                    $month_over_time[$user_id] = 0;
                }
                $month_over_time[$user_id] += $reserved_date['over_time'];
            }
            if(isset($reserved_date['late_time'])){
                if(!isset($month_late_time[$user_id])){
                    $month_late_time[$user_id] = 0;
                }
                $month_late_time[$user_id] += $reserved_date['late_time'];
            }
            if (isset($reserved_date['work_time'])) {
                if (!isset($month_work_time[$user_id])) {
                    $month_work_time[$user_id] = 0;
                }
                $month_work_time[$user_id] += $reserved_date['work_time'];
            }
            $grouped_reserved_dates[$reserved_date['day']][$user_id] = array(
                'day' => $reserved_date['day'],
                'over_time' => $reserved_date['over_time'],
                'work_time' => $reserved_date['work_time'],
                'start_time' => $reserved_date['edit_start_time'] ? $reserved_date['edit_start_time'] : $reserved_date['start_time'],
                'end_time' => $reserved_date['edit_end_time'] ? $reserved_date['edit_end_time'] : $reserved_date['end_time'],
                'incident' => $incident,
                'manzoku' => $manzoku,
                'comment' => $comment,
                'break_time' => $reserved_date['break_time'],
                'allowance' => $allowance,
                'achievement' => $achievement,
                'status_flag' => $reserved_date['status_flag'],
                'stamp_flag' => $reserved_date['stamp_flag'],
                'work_time_edit_flag' => $reserved_date['work_time_edit_flag']
            );
        }
        $user_record = User::whereIn('id', $users_list)->select('name', 'id', 'work_type', 'work_time_day', 'work_authority', 'icon_id', 'position_id', 'user_code')->get();

        $custom_weather_data = customFieldDataRecord::whereIn('user_id', $users_list)->whereYear('date', $currentYear)->whereMonth('date', $currentMonth)->where('type_id', 43)->get()->groupBy('user_id')->map(function ($userRecords) {
            return $userRecords->keyBy('date'); // Key the records by date within each user group
        });
        $custom_achievement_data = customFieldDataRecord::whereIn('user_id', $users_list)->whereYear('date', $currentYear)->whereMonth('date', $currentMonth)->where('type_id', 41)->get()->groupBy('user_id')->map(function ($userRecords) {
            return $userRecords->keyBy('date'); // Key the records by date within each user group
        });
        $mostCommonAchievementPerUser = [];
        foreach($custom_achievement_data as $user_id => $userRecords) {
            $valueCounts = $userRecords->pluck('label')->countBy();
            $mostCommonValue = $valueCounts->sortDesc()->keys()->first();
            $mostCommonAchievementPerUser[$user_id] = $mostCommonValue;
        }
        $mostCommonWeatherPerUser = [];

        foreach ($custom_weather_data as $user_id => $userRecords) {
            $valueCounts = $userRecords->pluck('value_int')->countBy();
            $mostCommonValue = $valueCounts->sortDesc()->keys()->first();
            $mostCommonWeatherPerUser[$user_id] = $mostCommonValue;
        }
        
        $shift_record = shiftRecord::whereYear('shift_day', $currentYear)
                        ->whereMonth('shift_day', $currentMonth)
                        ->whereIn('user_id', $users_list)
                        ->with(['shiftType'])
                        ->orderBy('created_at', 'desc')
                        ->get();
        $grouped_reserved_shifts = [];
        $annual_leave = [];
        $shift_count = $shift_record->where('shift_type', '!=', 0)->count();
        foreach($shift_record as $reserved_date){
            $date = $reserved_date->shift_day;
            $shift_type = $reserved_date->shift_type;
            $userId = $reserved_date->user->id;
            $shiftTypeName = $reserved_date->shiftType->name;
            $shiftTypeAbbreviation = $reserved_date->shiftType->abbreviation;
            $shiftTypeColor = $reserved_date->shiftType->color;
            $value = $reserved_date->shiftType->value;
            if (!isset($annual_leave[$userId])) {
                $annual_leave[$userId] = 0;
            }
            $annual_leave[$userId] += $value;
            $grouped_reserved_shifts[$date][$userId] = [
                'shift_day' => $date,
                'shift_type' => $shift_type,
                'name' => $shiftTypeName,
                'abbreviation' => $shiftTypeAbbreviation,
                'shift_start_time' => $reserved_date->start_time,
                'shift_end_time' => $reserved_date->end_time
            ];                
        }
        $month_average_data = [];
        $lastDay = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth()->day;
        foreach($user_record as $user){
            if($user->position_id == 12){
                $holidayNum = 9;
            }else{
                if ($currentMonth == 12) {
                    $holidayNum = 10;
                } elseif ($currentMonth == 1) {
                    $holidayNum = 12;
                } else {
                    if ($lastDay >= 29) {
                        $holidayNum = 9;
                    } elseif ($lastDay <= 28) {
                        $holidayNum = 8;
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
            $month_average_data[$user->id] = array(
                'month_over_time' => (isset($month_over_time[$user->id]) && $month_over_time[$user->id] >= 0) ? $month_over_time[$user->id] : null,
                'month_work_time' => $month_work_time[$user->id] ?? null,
                'month_weather_average' => $mostCommonWeatherPerUser[$user->id] ?? null,
                'month_achievement_average' => $mostCommonAchievementPerUser[$user->id] ?? null,
                'month_should_work_time' => $shift_work_hours,
                'month_annual_leave' => $annual_leave[$user->id] ?? null
            );
        }
        $responseArray = array(
            'record_array' => $grouped_reserved_dates,
            'weather' => $custom_weather_data,
            'user_data' => $user_record,
            'shift_array' => $grouped_reserved_shifts,
            'month_average' => $month_average_data
        );

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
            if ($plannedDateCarbon->year === 2023) {
                $remaining_days = 0;
            } else {
                $remaining_days = $tempData->planned_days - $planned_shifts;
            }
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
    public function getShiftData(Request $request){
        [$currentYear, $currentMonth] = explode('-', $request->current_date);
        $user = User::select('user_code')->findOrFail($request->work_group[0]);
        $user_code = $user->user_code;
        $queryParams = [
            'app' => '605',
            "query" => '年 = '. $currentYear . ' and 社員ｺｰﾄﾞ = ' . $user_code,
            // "query" => "レコード番号 =" . $request->id,
            // 'fields' => ["レコード番号", "社員コード", "ステータス", "氏名", "管理番号", "日時"]
        ];
        
        $queryString = http_build_query($queryParams);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;

        $headers = [
            'Authorization' => 'Basic',
            'X-Cybozu-API-Token' => 'Nxwn6FfbuQ7fcBX9Hi3rjyoEpdlLNUHyWYrEBWKZ'
        ];
        $recieve = [];
        $response = Http::withHeaders($headers)->get($url);
        $responseContent = $response->body();
        $responseData = $response->json();
        
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        // if($responseData){
        //     foreach ($responseData['records'] as $data){
        //         if($data['社員ｺｰﾄﾞ']['value'] == $user->user_code){
        //             foreach($data['計画付与テーブル']['value'] as $subtable){
        //                 if($subtable['value']['区分']['value'] == '計画消化'){
        //                     $recieve[] = $subtable['value']['計画付与日']['value'];
        //                 }
        //             }
        //         }
        //     }
        // }
        // if($recieve){
        //     foreach($recieve as $date){
        //         $shiftbydate = shiftRecord::where('shift_day', $date)->where('user_id', Auth::id())->first();
        //         if($shiftbydate){
        //             $shiftbydate->shift_type = 3;
        //             $shiftbydate->shift_day = $date;
        //             $shiftbydate->status_flag = 1;
        //             $shiftbydate->user_id = Auth::id();
        //             $shiftbydate->save();
        //         }else{
        //             $shiftbydate = new shiftRecord;
        //             $shiftbydate->shift_type = 3;
        //             $shiftbydate->shift_day = $date;
        //             $shiftbydate->status_flag = 1;
        //             $shiftbydate->user_id = Auth::id();
        //             $shiftbydate->save();   
        //         }
        //     }
        // }
        
        $shift_record = shiftRecord::whereYear('shift_day', $currentYear)
                        ->whereMonth('shift_day', $currentMonth)
                        ->whereIn('user_id', $request->work_group)
                        ->with(['shiftType'])
                        ->orderBy('created_at', 'desc')
                        ->get();
        $between_records = 0;
        $remaining_days = 0;
        $work_temp = workTemp::where('user_code', $user_code)->first();
        $planned_record = shiftRecord::whereIn('user_id', $request->work_group)
                            ->where('shift_type', 3)
                            ->orderBy('created_at', 'desc')
                            ->select('shift_day AS date', 'shift_type AS type', 'status_flag')
                            ->get();
        if($work_temp){
            $planned_date = $work_temp->date;
            $until_next = Carbon::parse($planned_date)->addYear()->format('Y-m-d');
            $between_records = shiftRecord::whereBetween('shift_day', [$planned_date, $until_next])->where('shift_type', 3)->where('user_id', $request->work_group[0])->count();
            $plannedDateCarbon = Carbon::createFromFormat('Y-m-d', $planned_date);
            if ($plannedDateCarbon->year === 2023) {
                $remaining_days = 0;
            } else {
                $remaining_days = $work_temp->planned_days - $between_records; 
            }
        }
        if($auth_user->position_id <= 11){
            $shift_type = shiftType::where('deleted_flag', 0)->get();
        }else{
            $shift_type = shiftType::where('id','!=', 14)->where('id','!=', 15)->get();
        }
        $data = [
            "shift_record" => $shift_record,
            "planned_record" => $planned_record,
            "shift_type" => $shift_type,
            "kintone_data" => $responseData,
            "workTemp" => $work_temp ? $work_temp : null,
            "consumed_days" => $remaining_days > 0 ? $between_records : 0,
            "remaining_days" => $remaining_days > 0 ? $remaining_days : 0,
        ];
        

        return response()->json(
            $data
        );
    }
    public function shift_manipulation(Request $request){
        $users = User::where('deleted_flag', 0)->where('retire', 0)->where('partner_flag', 0)->select('id', 'user_code')->get();
        foreach($users as $user){
            $user_code = $user->user_code;
            if($user_code){
                $queryParams = [
                    'app' => '605',
                    "query" => '年 = '. 2023 . ' and 社員ｺｰﾄﾞ = ' . $user_code,
                    // "query" => "レコード番号 =" . $request->id,
                    // 'fields' => ["レコード番号", "社員コード", "ステータス", "氏名", "管理番号", "日時"]
                ];
                
                $queryString = http_build_query($queryParams);
                $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;
        
                $headers = [
                    'Authorization' => 'Basic',
                    'X-Cybozu-API-Token' => 'Nxwn6FfbuQ7fcBX9Hi3rjyoEpdlLNUHyWYrEBWKZ'
                ];
                $recieve = [];
                $response = Http::withHeaders($headers)->get($url);
                $responseContent = $response->body();
                $responseData = $response->json();
                if($responseData && $responseData['records']){
                    foreach ($responseData['records'] as $data){
                        if($data['社員ｺｰﾄﾞ']['value'] == $user->user_code){
                            foreach($data['計画付与テーブル']['value'] as $subtable){
                                if($subtable['value']['区分']['value'] == '計画消化'){
                                    if($subtable['value']['日付']['value'] != null){
                                        $recieve[] = $subtable['value']['日付']['value'];
                                    }else{
                                        $recieve[] = $subtable['value']['計画付与日']['value'];
                                    }
                                }
                            }
                        }
                    }
                }
                if($recieve){
                    foreach($recieve as $date){
                        $date1 = Carbon::create(2024, 1, 20);
                        $date2 = Carbon::parse($date);
                        $date3 = Carbon::create(2024, 1, 1);
                        if($date2->lessThan($date1) && $date2->greaterThanOrEqualTo($date3)){
                            $shiftbydate = shiftRecord::where('shift_day', $date)->where('user_id', $user->id)->first();
                            if($shiftbydate){
                                $shiftbydate->shift_type = 3;
                                $shiftbydate->shift_day = $date;
                                $shiftbydate->status_flag = 1;
                                $shiftbydate->planned_year = 2023;
                                $shiftbydate->user_id = $user->id;
                                $shiftbydate->save();
                            }else{
                                $shiftbydate = new shiftRecord;
                                $shiftbydate->shift_type = 3;
                                $shiftbydate->shift_day = $date;
                                $shiftbydate->start_time = "09:00:00";
                                $shiftbydate->end_time = "18:00:00";
                                $shiftbydate->status_flag = 1;
                                $shiftbydate->planned_year = 2023;
                                $shiftbydate->user_id = $user->id;
                                $shiftbydate->save();   
                            }
                        }
                    }
                }
            }   
            
        }
        return response()->json($users);
        
    }
    public function shiftAdd(Request $request)
    {
        $auth_user = Auth::user();
        $user_id = $request->userId;
        $shift_array = $request->shift_array;
        $start_time_val = $request->shiftTimeStart;
        $end_time_val = $request->shiftEndStart;
        $shift_days = collect($shift_array)->pluck('date')->toArray();
        $shift_record_check = shiftRecord::where('user_id', $user_id)
            ->whereIn('shift_day', $shift_days)
            ->get()
            ->keyBy('shift_day');
        if ($request->deleted) {
            $deleted_days = array_column($request->deleted, 'date');
            shiftRecord::where('user_id', $user_id)
                        ->whereIn('shift_day', $deleted_days)
                        ->delete();
        }
        $this->sharedService->syncShiftToCalendar($user_id, $request->year, $request->month, $shift_array);
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
                $shift_record->status_flag = $status_flag;
                $shift_record->planned_year = $planned_year;
                $shift_record->update();
            } else {
                shiftRecord::create([
                    'user_id' => $user_id,
                    'shift_day' => $shift['date'],
                    'shift_type' => $shift['type'],
                    'start_time' => $start_time_val,
                    'end_time' => $end_time_val,
                    'status_flag' => $status_flag,
                    'planned_year' => $planned_year,
                ]);
            }
        }
        

        return response()->json($request);
    }
    public function getWorkGroup(Request $request){
        $auth_user_id = Auth::id();
        $work_group_list = workGroup::whereHas('work_group_user', function ($q) use($auth_user_id) {
            $q->whereIn('user_id', [$auth_user_id]);
        })->with(['user.icons', 'work_group_user.user'])->get();
        
        $work_group_users = $work_group_list->flatMap(function ($work_group_list_value) {
            return $work_group_list_value->work_group_user->map(function ($work_group_user_value) {
                return [
                    'id' => $work_group_user_value->user ? $work_group_user_value->user->id : null,
                    'name' => $work_group_user_value->user ? $work_group_user_value->user->name : null,
                    'icon_id' => $work_group_user_value->user ? $work_group_user_value->user->icon_id : null,
                    'name_kana' => $work_group_user_value->user ? $work_group_user_value->user->name_kana : null,
                ];
            });
        })->unique('id')->values()->all();
        $authUserIndex = array_search($auth_user_id, array_column($work_group_users, 'id'));

        if ($authUserIndex !== false) {
            // Remove the authenticated user from its current position
            $authUser = array_splice($work_group_users, $authUserIndex, 1);

            // Add the authenticated user back to the beginning of the array
            array_unshift($work_group_users, $authUser[0]);
        }

        return response()->json($work_group_users);
    }
    public function dailyReportAdd(Request $request){
        $auth_user_id = Auth::id();
        
        $exist_timecard = timecardRecord::where('day', $request->day)->where('user_id', $auth_user_id)->first();
        if($exist_timecard && !empty($exist_timecard->start_time)){
            $exist_timecard->end_time = $request->end_time;
            $exist_timecard->stamp_flag = 1;
            $exist_timecard->save();
            return response()->json($exist_timecard);
        }else{
            $timecard = new timecardRecord;
            $timecard->user_id = $auth_user_id;
            $timecard->day = $request->day;
            $timecard->start_time = $request->start_time;
            $timecard->stamp_flag = 0;
            $timecard->save();
            return response()->json($timecard);
        }
    }
    private function roundToNearest15Minutes($time, $ceil = true) {
        $roundedTime = Carbon::parse($time);
        
        if ($ceil) {
            $roundedMinutes = (int)ceil($roundedTime->minute / 15) * 15;
        } else {
            $roundedMinutes = (int)floor($roundedTime->minute / 15) * 15;
        }
        $roundedTime->setSeconds(0);

        return $roundedTime->setMinute($roundedMinutes)->format('H:i:s');
    }
    public function addData(Request $request){
        $time_card = timecardRecord::where('deleted_flag', 0)->whereYear('day', 2023)->whereMonth('day', 10)->get();
        foreach($time_card as $card){
            $user = User::select('work_time_day', 'work_type', 'id', 'name')->findOrFail($card->user_id);
            
            // $nightOvertimeStart = Carbon::createFromFormat('H:i:s', '22:00:00')->subDay();
            // $nightOvertimeEnd = Carbon::createFromFormat('H:i:s', '05:00:00');
            // $todayNightOverTime = Carbon::createFromFormat('H:i:s', '22:00:00');
            // if($end->lt($start)){
            //     $start->subDay();
            // }
            if($card->edit_start_time && $card->edit_end_time){
                $start = Carbon::parse($card->day . ' ' . $card->edit_start_time);
                $end = Carbon::parse($card->day . ' ' . $card->edit_end_time);
            }else if($card->start_time && $card->end_time){
                $start = Carbon::parse($card->day . ' ' . $card->start_time);
                $end = Carbon::parse($card->day . ' ' . $card->end_time);
            }
            
            if($start && $end){
                // Round the start and end times accordingly
                $roundedStartTime = $this->roundToNearest15Minutes($start->format('H:i:s'), true);
                $roundedEndTime = $this->roundToNearest15Minutes($end->format('H:i:s'), false);
                $card->start_time = $roundedStartTime;
                $card->end_time = $roundedEndTime;
                $shift_time_difference_seconds = ($user->work_time_day * 60);
                $shift_time_difference_seconds = max(0, $shift_time_difference_seconds);
                
                $time_difference_seconds = Carbon::parse($roundedEndTime)->diffInSeconds($roundedStartTime);
                $time_difference_seconds -= $card->break_time * 60;
                $time_difference_seconds = max(0, $time_difference_seconds);
                
            
                
                // $night_difference_seconds = 0;
                // if ($start->between($nightOvertimeStart, $nightOvertimeEnd)) {
                //     if ($end->between($nightOvertimeStart, $nightOvertimeEnd)) {
                //         $night_difference_seconds = $end->diffInSeconds($start);
                //     } else {
                //         $night_difference_seconds = $nightOvertimeEnd->diffInSeconds($start);
                //     }
                // } else if ($end->between($nightOvertimeStart, $nightOvertimeEnd)) {
                //     $night_difference_seconds = $end->diffInSeconds($nightOvertimeStart);
                // } else if ($end->greaterThan($todayNightOverTime)){
                //     $night_difference_seconds = $end->diffInSeconds($todayNightOverTime);
                // } else {
                //     $night_difference_seconds = 0;
                // }
                // if($night_difference_seconds >= 360 * 60 || ($night_difference_seconds >= 180 * 60 && $night_difference_seconds < 360 * 60)){
                //     $night_difference_seconds -= $request->breakTime * 60;
                // }
                $card->over_time = null;
                if ($time_difference_seconds >= $shift_time_difference_seconds) {                
                    $overtimeSeconds = $time_difference_seconds - $shift_time_difference_seconds;
                    $overtimeMinutes = floor($overtimeSeconds / 60);
                    $card->over_time = $overtimeMinutes;
                    dd($overtimeMinutes);
                } else {
                    $latetimeSeconds = $shift_time_difference_seconds - $time_difference_seconds;
                    $latetimeMinutes = floor($latetimeSeconds / 60);
                    $card->late_time = $latetimeMinutes;
                }
                
                // if (isset($night_difference_seconds) && $night_difference_seconds > 0) {
                //     $nighttimeMinutes = floor($night_difference_seconds / 60);
                //     $card->night_over_time = $nighttimeMinutes;
                // }else{
                //     $card->night_over_time = 0;
                // }
                $minutes = floor($time_difference_seconds / 60);
                $card->work_time = $minutes;
                $card->save();
            }
            
            
        }
        return 'saved';
    }
    public function saveTimeCard(Request $request){
        $today = Carbon::now()->isoFormat('YYYY-MM-DD');
        $is_exist = timecardRecord::where('day', $request->day)->where('user_id', $request->userId)->first();
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
        
        if ($start->between($nightOvertimeStart, $nightOvertimeEnd)) {
            if ($end->between($nightOvertimeStart, $nightOvertimeEnd)) {
                $night_difference_seconds = $end->diffInSeconds($start);
            } else {
                $night_difference_seconds = $nightOvertimeEnd->diffInSeconds($start);
            }
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
         
        if($is_exist){
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
            if($request->status_flag == 1){
                $is_exist->status_flag = 1;
            }
            if($today != $request->day){
                $is_exist->work_time_edit_flag = 1;
            }
            $is_exist->save();
            

            foreach ($fields as $field) {
                if ($request->$field) {
                    $fieldData = $request->$field;
                    $customFieldData = customFieldDataRecord::where('table_record_id', $is_exist->id)
                        ->where('user_id', $request->userId)
                        ->where('type_id', $fieldData['field_type_id'])
                        ->get();
                    if($customFieldData){
                        $customFieldData->each->delete();
                    }
                    if ($field == 'allowance') {
                        foreach ($fieldData['value'] as $val) {
                            $this->saveCustomData($request->day, $is_exist->id, $request->userId, $val, $fieldData['field_type_id']);
                        }
                    } else {
                        $this->saveCustomData($request->day, $is_exist->id, $request->userId, $fieldData['value'], $fieldData['field_type_id']);
                    }
                }
            }
        }else{
            $new_time_card = new timecardRecord;
            $new_time_card->start_time = $request->start_time;
            $new_time_card->day = $request->day;
            $new_time_card->user_id = $request->userId;
            $new_time_card->end_time = $request->end_time;
            if ($time_difference_seconds >= $shift_time_difference_seconds) {                
                $overtimeSeconds = $time_difference_seconds - $shift_time_difference_seconds;
                $overtimeMinutes = floor($overtimeSeconds / 60);
                $new_time_card->over_time = $overtimeMinutes;
            } else {
                $latetimeSeconds = $shift_time_difference_seconds - $time_difference_seconds;
                $latetimeMinutes = floor($latetimeSeconds / 60);
                $new_time_card->late_time = $latetimeMinutes;
            }
            if (isset($night_difference_seconds) && $night_difference_seconds > 0) {
                $nighttimeMinutes = floor($night_difference_seconds / 60);
                $new_time_card->night_over_time = $nighttimeMinutes;
            }else{
                $new_time_card->night_over_time = 0;
            }
            $minutes = floor($time_difference_seconds / 60);
            $new_time_card->work_time = $minutes;
            $new_time_card->edit_start_time = $request->start_time;
            $new_time_card->edit_end_time = $request->end_time;
            $new_time_card->work_time_edit_flag = 1;
            $new_time_card->break_time = $request->breakTime;
            $new_time_card->stamp_flag = 1;
            if($request->status_flag == 1){
                $new_time_card->status_flag = 1;
            }
            $new_time_card->save();
            
    
            foreach ($fields as $field) {
                if ($request->$field) {
                    $fieldData = $request->$field;
                    if ($field == 'allowance') {
                        foreach ($fieldData['value'] as $val) {
                            $this->saveCustomData($request->day, $new_time_card->id, $request->userId, $val, $fieldData['field_type_id']);
                        }
                    } else {
                        $this->saveCustomData($request->day, $new_time_card->id, $request->userId, $fieldData['value'], $fieldData['field_type_id']);
                    }
                }
            }
        }
    }
    private function saveCustomData($date, $table_record_id, $user_id, $value, $type_id){
        $new_custom_data = new customFieldDataRecord;
        $new_custom_data->date = $date;
        $new_custom_data->table_record_id = $table_record_id;
        $new_custom_data->user_id = $user_id;
        $new_custom_data->type_id = $type_id;
        if($type_id == 39){ // comment
            $new_custom_data->value_text = $value;
        } else { // incident, achievement, allowance
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

            return 'deleted';
        }

        return 'not exist';
    }
    public function getAttendanceData(Request $request){
        $user_list = $request->work_group;
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
                    ->select('user_id', 'day', 'work_time', 'over_time', 'status_flag', 'late_time', 'night_over_time');
            },
            'custom_field_data_records' => function ($query) use ($currentYear, $currentMonth) {
                $query->where('type_id', 37)
                    ->whereYear('date', $currentYear)
                    ->whereMonth('date', $currentMonth)
                    ->select('value_int', 'user_id');
            }
        ])->select('id','name','work_type', 'work_time_day', 'user_code', 'position_id')->findOrFail($user_list[0]);        
        $monthNum = (int)$currentMonth;

        // Calculate the last day of the current month
        $lastDay = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth()->day;
        if($user->position_id == 12){
            $holidayNum = 9;
        }else{
            if ($monthNum == 12) {
                $holidayNum = 10;
            } elseif ($monthNum == 1) {
                $holidayNum = 12;
            } else {
                if ($lastDay >= 29) {
                    $holidayNum = 9;
                } elseif ($lastDay <= 28) {
                    $holidayNum = 8;
                }
            }
        }
        
        $workdayNum = $lastDay - $holidayNum;
        $userData = $user->makeHidden('attendance_records', 'shift_records', 'time_card_records', 'custom_field_data_records');
        $attendance = $user->attendance_records->first();
        $shift_count = $user->shift_records->where('shift_type', '!=', 0)->count();
        $shift_holidays = $user->shift_records->where('shift_type', 0)->pluck('shift_day');
        $worked_holiday_count = $user->time_card_records->whereIn('day', $shift_holidays)->count();
        $workedday_count = $user->time_card_records->count();
        $worked_time = $user->time_card_records->sum('work_time');
        $holiday_worked_time = $user->time_card_records->whereIn('day', $shift_holidays)->sum('work_time');
        $approved_count = $user->time_card_records->where('status_flag', 2)->count();
        $unapproved_count = $user->time_card_records->where('status_flag', 1)->count();
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
            if($user->work_type == 1){
                $over_time = $over_time;
            }else{
                $over_time = $over_time - $late_time;
            }
        }else{
            if($user->work_type == 1){
                $over_time = $over_time;
            }else{
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
        
        if(!empty($attendance)){
            $attendance_flag = true;
        }else{
            $attendance_flag = false;
        }
        $responseArray = array(
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
        );

        return response()->json($responseArray);
    }
    public function remandTimeCard(Request $request){

        $time_card_record = timecardRecord::where('user_id', $request->user_id )->where('day', '=' , $request->record_day )->first();

        if(!empty($time_card_record)){
                $time_card_record->status_flag = 10;
                $time_card_record->save();
        }

        return response()->json($time_card_record);

    }
    public function approveTimeCard(Request $request){

        $time_card_record = timecardRecord::where('user_id', $request->user_id )->where('day', $request->record_day )->first();

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
            $half_day_hours = ($user_work_time_day / 2) * $half_day_holiday;
            $condolence_hours = $user_work_time_day * $request->condolence_leave;
            $transfer_hours = $user_work_time_day * $request->transfer_leave;
            $closed_hours = $user_work_time_day * $closed_day;
            $absence_days = ($working_hour_low - $request->worked_days) + $request->holiday_worked_days;
            $attendance_record = new attendanceRecord;
            $attendance_record->half_day_holiday = $half_day_holiday;
            $attendance_record->petitionType8_count = $petitionType8_count;
            $attendance_record->petitionType7_count = $petitionType7_count;
            $attendance_record->petitionType6_count = $petitionType6_count;
            $attendance_record->petitionType5_count = $petitionType5_count;
            $attendance_record->petitionType4_count = $petitionType4_count;
            $attendance_record->petitionType3_count = $petitionType3_count;
            $attendance_record->petitionType2_count = $petitionType2_count;
            $attendance_record->petitionType1_count = $petitionType1_count;
            $attendance_record->closed_day = $closed_day;
            if($absence_days >= 0){
                $attendance_record->absence_days = $absence_days;
            }else{
                $attendance_record->absence_days = 0;
            }
            $absence_hours = $request->shift_working_hours - (($request->annual_leave * 60) + $condolence_hours + $transfer_hours + $closed_hours + $request->worked_hours);
            if($absence_hours >= 0){
                $attendance_record->absence_hour = $absence_hours;
            }else{
                $attendance_record->absence_hour = 0;
            }
            $attendance_record->date_year_month = $request->date_year_month;
            $attendance_record->user_id = $request->user['id'];
            $attendance_record->user_code = $request->user['user_code'];
            $attendance_record->name = $request->user['name'];
            $attendance_record->pay_day = 20;
            $attendance_record->month_petition = '済';
            $attendance_record->prescribed_working_hours = $request->shift_working_hours / 60;
            if($request->user['work_type'] == 0){
                $attendance_record->work_type = 'フレックス';
            }else{
                $attendance_record->work_type = '通常';
            }
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
        
        $notificationUser = User::where('deleted_flag', 0)->where('id', 610)->select('name', 'id', 'icon_id')->orderBy('created_at', 'desc')->first();
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $today = date("Y-m");
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        if($auth_user_id == 610 || $auth_user_id == 608){
            $resposnsArray = array(
                'debug' => [],
                'yesterday' => $yesterday,
                'shiftNotSubmittedList' => [],
                'timecardNotSubmittedList' => []
            );
            return response()->json($resposnsArray);
        }
        if(in_array($auth_user->position_id, [1, 2, 3, 4, 5, 14, null])){
            $resposnsArray = array(
                'debug' => [],
                'yesterday' => $yesterday,
                'shiftNotSubmittedList' => [],
                'timecardNotSubmittedList' => []
            );
            return response()->json($resposnsArray);
        }
        $attendancePrevMonth = date("Y-m",strtotime('-1 month'));
        $attendanceThisMonth = date("Y-m");
        $attendance_prev_record = attendanceRecord::where('user_id', '=' , $auth_user_id )->where('date_year_month', '=' , $attendancePrevMonth )->first();
        $attendance_this_record = attendanceRecord::where('user_id', '=' , $auth_user_id )->where('date_year_month', '=' , $attendanceThisMonth )->first();
        if(empty($attendance_prev_record)){
            $prevMonth = $month - 1;
            $prev_shift_record = shiftRecord::where('user_id','=',$auth_user_id)->whereYear('shift_day','=',$year)->whereMonth('shift_day', $prevMonth)->orderBy('created_at', 'desc')->get();
        }
        $shift_record = shiftRecord::where('user_id','=',$auth_user_id)->whereYear('shift_day','=',$year)->whereMonth('shift_day', $month)->orderBy('created_at', 'desc')->get();
    
        $shiftNotSubmittedList = [];
        $shiftSubmittedList = [];
        $timecardNotSubmittedList = [];
        if(count($shift_record) == 0){
            $shiftNotSubmittedList[] = array('year' => $year, 'month' => $month , 'value' => $today , 'month_flag' => 0 ,'notification_user' => $notificationUser);
        }else{
            if(empty($attendance_this_record)){
                foreach($shift_record as $key => $value){
                    $shiftSubmittedList[$value->shift_day] = $value->shift_type;
                }
                if(!empty($prev_shift_record)){
                    foreach($prev_shift_record as $keyPrev => $valuePrev){
                        $shiftSubmittedList[$valuePrev->shift_day] = $valuePrev->shift_type;        
                    }
                }
                foreach($shiftSubmittedList as $key2 => $value2){
                    if($value2 == "1"){
                        if($key2 <= $yesterday){
                            $timecard = timecardRecord::where('deleted_flag','=', 0)->where('user_id', '=' , $auth_user_id )->where('day' , '=' , $key2)->first();
                            if($timecard === null){
                                $dateExplode = explode("-",$key2);
                                $timecardNotSubmittedList[] = array(
                                    'year' => (int) $dateExplode[0],
                                    'month' => (int) $dateExplode[1],
                                    'day' =>  (int) $dateExplode[2],
                                    'value' => $key2,
                                    'shiftType' => $auth_user->work_type,
                                    'notification_user' => $notificationUser,
                                    'shiftEndTime' => $shift_record && count($shift_record) > 0 ? $shift_record[0]->end_time : '18:00:00',
                                    'shiftStartTime' => $shift_record && count($shift_record) > 0 ? $shift_record[0]->start_time : '09:00:00'
                                );

                            }

                        }

                    }
                }
            }
        }
        if(!empty($shift_record)){
            if(!empty($timecardNotSubmittedList)){
                foreach ($timecardNotSubmittedList as $key => $Detail) {
                    $ArrDate[] = $Detail['value'];
                }
                array_multisort($ArrDate, SORT_DESC, SORT_NUMERIC, $timecardNotSubmittedList);
            }
        }
        $nextShiftSubmittedList = [];
        $resposnsArray = array(
            'debug' => $attendanceThisMonth,
            'yesterday' => $yesterday,
            'shiftNotSubmittedList' => $shiftNotSubmittedList,
            'timecardNotSubmittedList' => $timecardNotSubmittedList
        );
        return response()->json($resposnsArray);
    }
    public function attendanceClose(Request $request){
        $user_id = $request->user['id'];

        $attendance_record = attendanceRecord::where('user_id', '=' , $user_id )->where('date_year_month', '=' , $request->date_year_month )->first();

        $work_type_flag = $request->user['work_type'];

        if($work_type_flag == 0){
            $work_type = 'フレックス';
        }else{
            $work_type = '通常';
        }
        $month_petition = '済';
        if($request->user['user_code'] != null){
            $user_code = $request->user['user_code'];
        }else{
            $user_code = 99999999;
        }
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
}

