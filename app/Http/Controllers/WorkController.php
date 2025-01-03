<?php

namespace App\Http\Controllers;
use App\Models\ProjectRecord;
use App\Models\timecardBreakRecord;
use App\Models\timecardIncentive;
use DateTime;
use App\Models\User;

use App\Models\shiftType;
use App\Models\shiftRecord;

use App\Models\timecardRecord;
use App\Models\timecardCostRecord;
use App\Models\customFieldDataRecord;
use App\Models\customFieldPartsRecord;
use App\Models\timecardVehicle;
use App\Models\workGroup;
use App\Models\workTemp;
use App\Models\attendanceRecord;
use App\Models\ShiftOvertimeRequest;
use App\Services\SharedService;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $active_user = $this->active_user();
        
        $users_list = $request->work_group ?? [];
        $current_date = $request->current_date ?? Carbon::now()->format('Y-m');
        [$currentYear, $currentMonth] = explode('-', $current_date);

        $time_card_record = timecardRecord::selectRaw(
            'user_id,
            SUM(over_time) as total_over_time,
            SUM(work_time) as total_work_time'
        )
        ->whereYear('day', $currentYear)
        ->whereMonth('day', $currentMonth)
        ->whereIn('user_id', $users_list)
        ->where('deleted_flag', 0)
        ->groupBy('user_id')
        ->orderBy('user_id')
        ->get();

        $month_over_time = $time_card_record->pluck('total_over_time', 'user_id');

        $month_work_time = $time_card_record->pluck('total_work_time', 'user_id');

    
        $user_record = User::whereIn('id', $users_list)
                            ->select('name', 'id', 'work_type', 'work_time_day', 'work_authority', 'icon_id', 'position_id', 'user_code')
                            ->get();

        $custom_weather_data = customFieldDataRecord::selectRaw(
            'user_id,
            value_int,
            COUNT(*) as count'
        )
        ->whereIn('user_id', $users_list)
        ->whereYear('date', $currentYear)
        ->whereMonth('date', $currentMonth)
        ->where('type_id', 43)
        ->groupBy('user_id', 'value_int')
        ->orderBy('user_id')
        ->orderBy('count', 'desc')
        ->get();
        $custom_achievement_data = customFieldDataRecord::selectRaw(
            'user_id,
            label,
            COUNT(*) as count'
        )
        ->whereIn('user_id', $users_list)
        ->whereYear('date', $currentYear)
        ->whereMonth('date', $currentMonth)
        ->where('type_id', 41)
        ->groupBy('user_id', 'label')
        ->orderBy('user_id')
        ->orderBy('count', 'desc')
        ->get();

        $mostCommonAchievementPerUser = $custom_achievement_data->groupBy('user_id')->map(function ($userRecords) {
            return $userRecords->first()->label;
        });

        $mostCommonWeatherPerUser = $custom_weather_data->groupBy('user_id')->map(function ($userRecords) {
            return $userRecords->first()->value_int;
        });

        $shift_record = shiftRecord::selectRaw(
            'user_id,
            SUM(shift_types.value) as total_shift_value'
            )
            ->join('shift_types', 'shift_records.shift_type', '=', 'shift_types.id')
            ->whereYear('shift_day', $currentYear)
            ->whereMonth('shift_day', $currentMonth)
            ->whereIn('user_id', $users_list)
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->get();

        $annual_leave = $shift_record->pluck('total_shift_value', 'user_id')->map(function ($value) {
            return $value ?? 0;
        });
            
        $attendance_flag = attendanceRecord::where('date_year_month', $request->current_date)
                            ->whereIn('user_id', $users_list)
                            ->exists();
        $month_average_data = [];
        $lastDay = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth()->day;

        $annual_costs = timecardCostRecord::selectRaw(
            'user_id,
            SUM(expenses) as total_expenses'
        )
        ->whereIn('user_id', $users_list)
        ->where('date_month', $request->current_date)
        ->groupBy('user_id')
        ->orderBy('user_id')
        ->get()
        ->pluck('total_expenses', 'user_id');
        $annual_incentive = timecardIncentive::selectRaw(
            'user_id,
            SUM(count) as total_incentives'
        )
        ->whereIn('user_id', $users_list)
        ->where('date_month', $request->current_date)
        ->groupBy('user_id')
        ->orderBy('user_id')
        ->get()
        ->pluck('total_incentives', 'user_id');
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
            if (isset($annual_leave[$user->id])  && isset($month_work_time[$user->id])) {
                $month_work_time[$user->id] += $annual_leave[$user->id];
            }
            if ($shift_work_hours < (($month_work_time[$user->id] ?? 0) - ($month_over_time[$user->id] ?? 0))) {
                $month_over_time[$user->id] = ($month_work_time[$user->id] ?? 0) - $shift_work_hours;
            }
            
            $month_average_data[] = [
                'month_over_time' => (isset($month_over_time[$user->id]) && $month_over_time[$user->id] >= 0) ? $month_over_time[$user->id] : null,
                'month_work_time' => $month_work_time[$user->id] ?? null,
                'month_weather_average' => $mostCommonWeatherPerUser[$user->id] ?? null,
                'month_achievement_average' => $mostCommonAchievementPerUser[$user->id] ?? null,
                'month_should_work_time' => $shift_work_hours,
                'month_annual_leave' => $annual_leave[$user->id] ?? null,
                'mont_total_costs' => $annual_costs[$user->id] ?? null,
                'mont_total_incentive' => $annual_incentive[$user->id] ?? null,
                'user_name' => $user->name,
                'user_id' => $user->id,
                'work_type' => $user->work_type,
                'access_csv' => $active_user->id == 610 || $active_user->id == 608 || $active_user->position_id == 6
            ];
        }
        $responseArray = [
            'user_data' => $user_record,
            'month_average' => $month_average_data,
            "attendance_flag" => $attendance_flag,
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
        $active_user = $this->active_user();
        $users_list = $request->work_group ?? [];
        [$year, $month] = explode("-", $requestDateString);
        $users = User::whereIn('id', $users_list)
        ->with([
            'time_card_records' => function($q) use($year, $month) {
                $q->whereYear('day', $year)
                ->whereMonth('day', $month)
                ->with([
                    'custom_field_data_records' => function ($q) {
                        $q->whereIn('type_id', [37, 40, 39, 41, 42, 44])
                            ->orderBy('created_at', 'desc')
                            ->select('id', 'table_record_id', 'type_id', 'value_text', 'value_int', 'date', 'label', 'user_id');
                    },
                    'timecard_costs' => function ($q) {
                        $q->with('file')
                            ->select('content', 'type', 'expenses', 'record_id', 'file_path', 'id', 'department');
                    },
                    'timecard_incentives' => function ($q) {
                        $q->with('file')
                            ->select('count', 'id', 'record_id');
                    },
                    // 'timecard_break_records',
                    'total_break_time',
                    'department' => function ($q) {
                        $q->with('members')->with('manager');
                    },
                    'vehicle_data' => function ($q) {
                        $q->with('before_user')->with('after_user');
                    }
                ])
                ->select('id', 'break_time', 'end_time', 'day', 'over_time', 'stamp_flag', 'start_time', 'status_flag', 'work_time', 'user_id', 'work_group_id');
            },
            'shift_records' => function ($q) use($year, $month) {
                $q->whereYear('shift_day', $year)
                ->whereMonth('shift_day', $month)
                ->with([
                    'shiftType' => function ($query) {
                        $query->select('id', 'name', 'abbreviation', 'value');
                    },
                    'overtime_request',
                    'department',
                ])
                ->select('id', 'shift_day', 'shift_type', 'user_id', 'start_time', 'end_time', 'status_flag', 'department_id');
            },
            'custom_field_data_records' => function ($q) use($year, $month) {
                $q->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('type_id', 43);
            },
            'attendance_records' => function ($q) use($requestDateString) {
                $q->where('date_year_month', $requestDateString);
            }
        ])->get();
        $previousTimeCard = timecardRecord::where('user_id', $active_user->id)            
            ->orderBy('day', 'desc')
            ->first();
        $recordList = [];
        // $workGroups = workGroup::whereHas('members', function ($q) {
        //     $q->where('user_id', Auth::id())
        //       ->where('authority', 1);
        // })->with(['members' => function ($q) {
        //     $q->where('user_id', '<>', Auth::id());
        // }])->get();
        // $workGroupUserIds = $workGroups->flatMap(function ($workGroup) {
        //     return $workGroup->members->pluck('id');
        // })->unique()->values()->all();
        $timeCardRecords = $users->flatMap->time_card_records->groupBy('user_id');
        $shiftRecords = $users->flatMap->shift_records->groupBy('user_id')->map->keyBy('shift_day');
        $customFieldData = $users->flatMap->custom_field_data_records->groupBy('user_id')->map->keyBy('date');
        $attendanceRecords = $users->flatMap->attendance_records->groupBy('user_id')->map->keyBy('date_year_month');
        for ($day = 1; $day <= cal_days_in_month(CAL_GREGORIAN, $month, $year); $day++) {
            $date = Carbon::create($year, $month, $day);
            $targetShiftDay = $date->format('Y-m-d');
            $targetShiftMonth = $date->format('Y-m');
            foreach ($users as $index => $user) {
                $userId = $user->id;
                $attendance = $attendanceRecords[$userId][$targetShiftMonth]->id ?? false;
                $time_card = isset($timeCardRecords[$userId])
                            ? $timeCardRecords[$userId]->firstWhere('day', $targetShiftDay)
                            : null;
                $shift = $shiftRecords[$userId][$targetShiftDay] ?? null;
                $department = $time_card?->department;
                $authority = false;  
                if($department) {
                    $manager = $department->manager;
                    $currentUserAuthority = $manager->first(function ($member) {
                        return $member->id == Auth::id();
                    });
                    $members = $department->members;
                    if($currentUserAuthority){
                        $otherMembers = $members->filter(function ($member) {
                            return $member->id !== Auth::id();
                        })->pluck('id')->all();
                        $authority = in_array($user->id, $otherMembers);
                    }
                }
                
                
                
                $overtime_reason = $time_card ? $time_card->custom_field_data_records->firstWhere('type_id', 42) : '';
                $comment = $time_card ? $time_card->custom_field_data_records->firstWhere('type_id', 39) : '';
                $allowances = $time_card ? $time_card->custom_field_data_records->where('type_id', 37)->pluck('label')->toArray() : [];
                $allowances_value = implode(" ", $allowances);
                $incident = $time_card ? $time_card->custom_field_data_records->firstWhere('type_id', 40) : '';
                $satisfy = $time_card ? $time_card->custom_field_data_records->firstWhere('type_id', 41) : '';

                $daily_report_ability = $this->has_daily_report($shift, $time_card, $date, $user, $active_user, $attendance);
                $overtime_ability = $shift ? $this->has_overtime_access($shift, $user, $time_card, $date, $active_user) : false;
                $approve_ability = $this->has_approve_access($shift, $time_card, $authority, $attendance, $active_user);
                $department_creation = $this->has_department_create($shift, $time_card, $date, $active_user, $attendance, $user);
                $recordList[] = [
                    'day_full' => $date->format('Y-m-d'),
                    'day_show' => $index == 0 ? $date->format('Y-m-d') : '',
                    'user_name' => $user->name,
                    'user_id' => $userId,
                    'work_authority' => $user->work_authority,
                    'work_time_day' => $user->work_time_day,
                    'work_type' => $user->work_type,
                    'flex' => $user->work_type == 0,
                    'last' => end($users_list) == $userId,
                    'position_id' => $user->position_id,
                    'overtime_reason' => $overtime_reason ? $overtime_reason->value_text : '',
                    'comment' => $comment ? $comment->value_text : '',
                    'incident' => $incident ? $incident->label : '',
                    'satisfy' => $satisfy ? $satisfy->label : '',
                    'allowances' => $allowances_value,
                    'attendance' => $attendance,
                    'shift' => $shift,
                    'time_card' => $time_card,
                    'weather' => $customFieldData[$userId][$targetShiftDay]->value_int ?? null,
                    'authority' => $authority,
                    'force_authority' => $active_user->id == 610 || $active_user->id == 608,
                    'total_break_time' => $time_card?->total_break_time->first()->total_break_minute ?? 0,
                    'prev_vehicle_data' => $previousTimeCard?->prev_vehicle_data,
                    'ability' => [
                        'overtime_request' => $overtime_ability,
                        'daily_report_create' => $daily_report_ability[0],
                        'daily_report_modify' => $daily_report_ability[1],
                        'start_stamp' => $daily_report_ability[2],
                        'end_stamp' => $daily_report_ability[3],
                        'break_stamp' => $daily_report_ability[4],
                        'daily_report_approve' => $approve_ability[0],
                        'daily_report_cancel' => $approve_ability[1],
                        'overtime_approve' => $approve_ability[2],
                        'overtime_cancel' => $approve_ability[3],
                        'department_creation' => $department_creation,
                    ]
                ];
            }
        }
        
        return response()->json($recordList);
    }
    private function has_approve_access($shift, $time_card, $authority, $has_attendance, $active_user){
        $force = $active_user->id == 610 || $active_user->id == 608;
        $dailyReportStatus = $time_card->status_flag ?? -1;
        $overtimeStatus = $shift && $shift->overtime_request ? $shift->overtime_request->status : -1;
        $dailyReportApproveOrDeny = $dailyReportStatus == 1 && ($authority || $force) && !$has_attendance;
        $dailyReportCancel = $dailyReportStatus == 2 && ($authority || $force) && !$has_attendance;
        $overtimeApproveOrDeny = $overtimeStatus == 1 && ($authority || $force) && !$has_attendance;
        $overtimeCancel = $overtimeStatus == 2 && ($authority || $force) && !$has_attendance;
        return [
            $dailyReportApproveOrDeny,
            $dailyReportCancel,
            $overtimeApproveOrDeny,
            $overtimeCancel
        ];
    }
    private function has_overtime_access($shift, $user, $time_card, $date, $active_user){
        $today_or_future = empty($shift) ? false : $date->format('Y-m-d') >= date('Y-m-d');
        $possibleTypes = [1,6,7,8,9,10,11,12,13];
        $userMatch = $user->id == $active_user->id;       
        $timeCardCheck = empty($time_card) || $time_card->status_flag == 10 || $time_card->status_flag == 0;
        return $today_or_future && in_array($shift->shiftType->id, $possibleTypes) && $userMatch && $timeCardCheck && $active_user->position_id !== 15 && !$shift->overtime_request; 
    }
    private function has_daily_report($shift, $time_card, $day, $user, $active_user, $has_attendace){
        $timecardExist = $time_card !== null;
        $valid_shift = (!empty($shift) && $shift->shiftType->id !== 3) || $user->position_id == 15 || $user->position_id < 6;
        $isToday = date('Y-m-d') == $day->format('Y-m-d');
        $isTodayOrPast = date('Y-m-d') >= $day->format('Y-m-d');
        $create = !$timecardExist && !$has_attendace && $valid_shift && $isTodayOrPast && ($user->id == $active_user->id || $active_user->id == 610 || $active_user->id == 608);
        $status = $time_card->status_flag ?? -1;
        $modify = $timecardExist && !$has_attendace && (($status == 10 || $status == 0 && $user->id == $active_user->id) || (($active_user->id == 610 || $active_user->id == 608) && $status !== 2));
        $start_stamp = !$timecardExist && !$has_attendace && $valid_shift && $isToday && $user->id == $active_user->id; 
        $end_stamp = $timecardExist && !$has_attendace && ($time_card->stamp_flag == 0 || $time_card->stamp_flag == 2) && $valid_shift && $isToday && $user->id == $active_user->id;
        $break_stamp = $timecardExist && ($time_card->stamp_flag == 0 || $time_card->stamp_flag == 2) && $user->id == $active_user->id; 
        return [$create ,$modify, $start_stamp, $end_stamp, $break_stamp];
    }
    private function has_department_create($shift, $time_card, $day, $active_user, $has_attendace, $user){
        $valid_shift = !empty($shift) && $shift->shiftType->id !== 0 && $shift->shiftType->id !== 1;
        $timecardExist = $time_card !== null;
        $isTodayOrPast = date('Y-m-d') >= $day->format('Y-m-d');
        $access = $user->id == $active_user->id || ($active_user->id == 610 || $active_user->id == 608);
        return $valid_shift && !$timecardExist && $isTodayOrPast && $access && !$has_attendace;
    }
    // Shift Functions
    public function get_shift_data(Request $request){
        $users_list = $request->work_group ?? [Auth::id()];
        [$currentYear, $currentMonth] = explode('-', $request->current_date);

        $currentMonth >= 2 && $currentMonth <= 7 ? $evaluationDate = "$currentYear-02-01" : $evaluationDate = "$currentYear-08-01";

        $user = User::with(['evaluation' => function ($query) use($evaluationDate) {
                        $query->where('date', $evaluationDate);
                    }])
                    ->select('user_code', 'position_id', 'id')->findOrFail($users_list[0]);
        $user_code = $user->user_code;
        $general_position = $user->evaluation->general_position ?? null;
        $shift_record = shiftRecord::whereYear('shift_day', $currentYear)
                        ->whereMonth('shift_day', $currentMonth)
                        ->where('user_id', $users_list[0])
                        ->with([
                            'shiftType' => function ($query) {
                                $query->select('id', 'name', 'abbreviation', 'value');
                            },
                            'old_shift' => function ($query) {
                                $query->withTrashed()->select('id', 'shift_day', 'shift_type');
                                $query->with([
                                    'shiftType' => function ($subQuery) {
                                        $subQuery->select('id', 'name', 'abbreviation', 'value');
                                    }
                                ]);
                            }
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get();
        $odaCheck = shiftRecord::where('user_id', $users_list[0])
            ->where('shift_type', 16)
            ->whereYear('shift_day', $currentYear)
            ->exists();
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
        $shift_type = $user->position_id <= 11 || $user->position_id == 16
                      ? $general_position > 'B' && $general_position != '一般職' ? 
                      shiftType::where('deleted_flag', 0)->get()
                      : shiftType::where('deleted_flag', 0)->whereNot('id', 17)->get()
                      : shiftType::whereNotIn('id', [14, 15, 16, 17])->get();

        $data = [
            "shift_record" => $shift_record,
            "shift_type" => $shift_type,
            "workTemp" => $work_temp ? $work_temp : null,
            "consumed_days" => $remaining_days > 0 ? $between_records : 0,
            "remaining_days" => $remaining_days > 0 ? $remaining_days : 0,
            "odaCheck" => $odaCheck,
        ];
        

        return response()->json(
            $data
        );
    }
    public function get_shift_with_work_group(Request $request){
        [$year, $month] = explode('-', $request->current_date);
        $user = $this->active_user();
        $authenticatedUserId = Auth::id();
        if($user->id == 608 || $user->id == 610){
            $workGroups = ProjectRecord::whereHas('members', function ($q) use ($year, $month){
                                            $q->whereHas('shift_records', function ($q) use($year, $month) {
                                                $q->whereYear('shift_day', $year)
                                                    ->whereMonth('shift_day', $month);
                                            });
                                    })->orWhereHas('manager', function ($q) use ($year, $month){
                                        $q->whereHas('shift_records', function ($q) use($year, $month) {
                                            $q->whereYear('shift_day', $year)
                                                ->whereMonth('shift_day', $month);
                                        });
                                    })
                                    ->with(['manager' => function ($q) use ($year, $month) {
                                        $q->whereHas('shift_records', function ($q) use($year, $month) {
                                                $q->whereYear('shift_day', $year)
                                                    ->whereMonth('shift_day', $month);
                                            });
                                    }])
                                    ->with(['members' => function ($q) use ($year, $month) {
                                        $q->whereHas('shift_records', function ($q) use($year, $month) {
                                                $q->whereYear('shift_day', $year)
                                                    ->whereMonth('shift_day', $month);
                                            });
                                    }])->get();
        } else {
            $workGroups = ProjectRecord::whereHas('members', function ($q) use($year, $month){
                $q->whereNot('users.id', Auth::id())->whereHas('shift_records', function ($q) use($year, $month) {
                    $q->whereYear('shift_day', $year)
                        ->whereMonth('shift_day', $month);
                });
            })->whereHas('manager', function ($q) {
                $q->where('users.id', Auth::id());
            })->with(['members' => function ($q) use ($year, $month) {
                $q->whereNot('users.id', Auth::id())->whereHas('shift_records', function ($q) use($year, $month) {
                        $q->whereYear('shift_day', $year)
                            ->whereMonth('shift_day', $month);
                    });
            }])->get();
            
        }
        $members = $workGroups->flatMap(function ($work_group_list_value) {
            return $work_group_list_value->members;
        })->unique('id')->values();
        $manager = $workGroups->flatMap(function ($work_group_list_value) {
            return $work_group_list_value->manager;
        })->unique('id')->values();
        $work_group_users = $members->merge($manager)->unique('id')->values()->all();
        usort($work_group_users, function ($a, $b) use ($authenticatedUserId) {
            if ($a->id == $authenticatedUserId) {
                return -1;
            } elseif ($b->id == $authenticatedUserId) {
                return 1;
            } else {
                return $a->id - $b->id;
            }
        });
        $user_ids = collect($work_group_users)->pluck('id')->toArray();
        $userShifts = shiftRecord::whereIn('user_id', $user_ids)
                        ->whereYear('shift_day', $year)
                        ->whereMonth('shift_day', $month)
                        ->with([
                            'shiftType' => function ($query) {
                                $query->select('id', 'name', 'abbreviation', 'value');
                            },
                            'old_shift' => function ($query) {
                                $query->whereNot('status_flag', 1)->withTrashed()->select('id', 'shift_day', 'shift_type');
                                $query->with([
                                    'shiftType' => function ($subQuery) {
                                        $subQuery->select('id', 'name', 'abbreviation', 'value');
                                    }
                                ]);
                            }
                        ])
                        ->orderBy('shift_day', 'asc')
                        ->get();
        $shift_records = $userShifts->groupBy('shift_day')->map(function ($shifts) {
            return $shifts->keyBy('user_id');
        });
        $data = [
            'work_users' => $work_group_users,
            'shift_records' => $shift_records,
            'work_groups' => $workGroups
        ];
        return response()->json($data);
    }   
    public function shift_approve_all(Request $request){
        $user = $this->active_user();
        $request->validate([
            'user_ids' => 'required',
            'year_month' => 'required'
        ]);
        [$year, $month] = explode('-', $request->year_month);
        $shifts = shiftRecord::whereIn('user_id', $request->user_ids)
                                ->whereYear('shift_day', $year)
                                ->whereMonth('shift_day', $month)
                                ->whereNot('status_flag', 1)
                                ->whereNot('user_id', $user->id)
                                ->update([
                                    "status_flag" => 3,
                                    "approved_by" => $user->id
                                ]);
        return response()->json([
            'data' => $shifts ?? null
        ]);
    }
    public function shift_approve(Request $request){
        $user = $this->active_user();
        $request->validate([
            'shift_id' => 'required'
        ]);
        if($request->status){
            $shift = shiftRecord::findOrFail($request->shift_id)->update([
                "status_flag" => $request->status,
                "approved_by" => $user->id
            ]);
        } else {
            $shift = shiftRecord::findOrFail($request->shift_id);
            if ($shift->overtime_request) {
                $shift->overtime_request->delete();
            }
            $shift->delete();
        }
        
        return response()->json([
            'data' => $shift ?? null
        ]);
    }
    public function shiftAdd(Request $request)
    {
        $user_id = $request->userId;
        $shift_array = $request->shift_array;
        $start_time_val = $request->shiftTimeStart;
        $end_time_val = $request->shiftEndStart;
        $types = [0, 2, 3, 5, 14, 15, 16];
        [$year, $month] = explode('-', $request->yearMonth);
        $shift_days = collect($shift_array)->pluck('date')->toArray();
        $holidays = collect($shift_array)->filter(function ($shift) use($types) {
            return in_array($shift['type'], $types);
        })->pluck('date')->toArray();
    
        $overtimeCheck = shiftRecord::where('user_id', $user_id)
            ->whereIn('shift_day', $holidays)
            ->whereHas('overtime_request')
            ->exists();
        $holidays1 = collect($shift_array)->filter(function ($shift) {
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
            $status_flag = $shift['type'] === 3 ? 1 : 2;
            $planned_year = $shift['type'] === 3 ? $request->planned_year : $request->year;
            if ($shift_record_check->has($shift['date'])) {
                $shift_record = $shift_record_check[$shift['date']];
                if ($shift_record->shift_type !== $shift['type']) {
                    shiftRecord::create([
                        "user_id" => $shift_record->user_id,
                        "start_time" => $start_time_val,
                        "end_time" => $end_time_val,
                        "status_flag" => $status_flag,
                        "shift_day" => $shift['date'],
                        "descendant_of" => $shift_record->id,
                        "shift_type" => $shift['type'],
                        "planned_year" => $shift_record->planned_year
                    ]);
                    $shift_record->delete();
                }
                if ($shift_record->start_time !== $start_time_val || $shift_record->end_time !== $end_time_val) {
                    $shift_record->update([
                        "start_time" => $start_time_val,
                        "end_time" => $end_time_val
                    ]);
                }
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
        $this->sharedService->syncShiftToCalendar($user_id, $year, $month);

        return response()->json($request);
    }
    public function getWorkGroup(Request $request){
        $user = $this->active_user();
        $auth_user_id = $user->id;
        $ids = [608, 610];
        if($auth_user_id == 608 || $auth_user_id == 610){
            $work_group_users = ProjectRecord::whereHas('members')
                ->with(['members' => function($q) use($ids) {
                $q->whereNotIn('users.id', $ids)
                    ->where('users.partner_flag', 0)
                    ->where('users.retire', 0)
                    ->orWhere('users.retire_date', '>=', Carbon::now())
                    ->select([
                        'users.id as id', 
                        'users.name',
                        'users.icon_id', 
                        'users.name_kana', 
                        'users.work_authority', 
                        'users.position_id',
                        'users.on_leave'
                    ]);
            }])->with('manager', 'director')
            ->get();
        }else{
            $work_group_users = ProjectRecord::whereHas('members', function($q) use($auth_user_id) {
                                $q->whereIn('users.id', [$auth_user_id]);
                            })->orWhereHas('manager', function($q) use($auth_user_id) {
                                $q->whereIn('users.id', [$auth_user_id]);
                            })->orWhere('director_id', $auth_user_id)->with(['members' => function($q) use($ids) {
                                $q->whereNotIn('users.id', $ids)
                                    ->where('users.partner_flag', 0)
                                    ->where('users.retire', 0)
                                    ->select([
                                        'users.id as id', 
                                        'users.name',
                                        'users.icon_id', 
                                        'users.name_kana', 
                                        'users.work_authority', 
                                        'users.position_id',
                                        'users.on_leave'
                                    ]);
                            }])->with('manager', 'director')
                            ->get();
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
    public function daily_report_break(Request $request){
        $breakTime = $request->break_start;
        $time_card = $request->record;
        $exist_timecard = timecardRecord::find($time_card['id']);
        $timecard_break = $exist_timecard->timecard_break_records()->where('break_flag', 1)->first();
        if(!empty($timecard_break)){                       
             
            $start = Carbon::createFromFormat('H:i:s', $timecard_break->start_time);
            $end = Carbon::createFromFormat('H:i:s', $breakTime);
            $diffinMinutes = $start->diffInMinutes($end);

            $timecard_break->update([
                'break_by_minute' => ceil($diffinMinutes / 15) * 15,
                'end_time' => $breakTime,
                'break_flag' => 2
            ]);
            $exist_timecard->update(['stamp_flag' => 0]);
            return response()->json($timecard_break);
             
            
        }        
        $exist_timecard->timecard_break_records()->create([
            'user_id' => $time_card['user_id'],
            'day' => $time_card['day'],
            'start_time' => $breakTime,
        ]);
        $exist_timecard->update(['stamp_flag' => 2]);
        return response()->json($timecard_break);
        
        
    }

    public function check_break_time(){
        $active_user = $this->active_user();
        $today = Carbon::now()->format('Y-m-d');
        $inBreak = timecardBreakRecord::where('break_flag', 1)
                            ->where('user_id', $active_user->id)
                            ->where('day', $today)
                            ->exists();
        return response()->json($inBreak);
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
            throw ValidationException::withMessages(['message' => '3時間以上の勤務の場合、最低でも30分間の休憩を取る必要があります。']);
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
        
        $user = User::select('work_time_day', 'work_type', 'id', 'name', 'position_id')->findOrFail($request->userId);
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
        $shift_time_difference_seconds = $user->work_time_day * 60;
        $shift_time_difference_seconds = max(0, $shift_time_difference_seconds);
        
        $time_difference_seconds = $start->diffInSeconds($end);
        $time_difference_seconds -= $request->breakTime * 60;
        $time_difference_seconds = max(0, $time_difference_seconds);
        
        $night_difference_seconds = 0;
        $overtimeMinutes = 0;
        if ($start->between($nightOvertimeStart, $nightOvertimeEnd)) {
            $night_difference_seconds = $end->between($nightOvertimeStart, $nightOvertimeEnd) ? $start->diffInSeconds($end) : $start->diffInSeconds($nightOvertimeEnd);
        } else if ($end->between($nightOvertimeStart, $nightOvertimeEnd)) {
            $night_difference_seconds = $nightOvertimeStart->diffInSeconds($end);
        } else if ($end->greaterThan($todayNightOverTime)){
            $night_difference_seconds = $todayNightOverTime->diffInSeconds($end);
        } else {
            $night_difference_seconds = 0;
        }
        if($night_difference_seconds >= 360 * 60 || ($night_difference_seconds >= 180 * 60 && $night_difference_seconds < 360 * 60)){
            $night_difference_seconds -= $request->breakTime * 60;
        }
        if(array_key_exists(37, $request->customValues) && $request->customValues[37] && in_array(2, $request->customValues[37])){
            $this->checkWaitingAllowance($request);
        }
        DB::beginTransaction();
        try {
            $is_exist = timecardRecord::firstOrCreate([
                'day' => $request->day,
                'user_id' => $request->userId
            ]);
            $is_exist->work_group_id = $request->department;
            $is_exist->start_time = $request->start_time;
            $is_exist->end_time = $request->end_time;
            if ($user->work_type === 1) {
                if ($time_difference_seconds >= $shift_time_difference_seconds) {                
                    $overtimeSeconds = $time_difference_seconds - $shift_time_difference_seconds;
                    $overtimeMinutes = floor($overtimeSeconds / 60);
                    $is_exist->over_time = $overtimeMinutes;
                } else {
                    $latetimeSeconds = $shift_time_difference_seconds - $time_difference_seconds;
                    $latetimeMinutes = floor($latetimeSeconds / 60);
                    $is_exist->late_time = $latetimeMinutes;
                }
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
                
               customFieldDataRecord::where('table_record_id', $is_exist->id)
                    ->where('user_id', $request->userId)
                    ->where('type_id', $key)
                    ->delete();
                if ($key == 37) {
                    if(is_array($field)){
                        foreach ($field as $val) {
                            $this->saveCustomData($request->day, $is_exist->id, $request->userId, $val, $key, $request->vehicleData);
                        }
                    }
                    
                } else {
                    $this->saveCustomData($request->day, $is_exist->id, $request->userId, $field, $key, $request->vehicleData);
                }
                
            }
            
            $is_exist->save();
            if($request->shiftType !== 0 && $request->shiftType !== 1){
                $this->checkDepartment($request->day, $request->userId);
            }
            $this->saveWorkCost($request, $is_exist);
            $this->saveWorkIncentive($user, $request, $is_exist);
            if($request->overTimeMinute){
                $this->overTimeCheck($request, $overtimeMinutes);
            }
            DB::commit();
            return response()->json(['success' => 'success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    private function checkDepartment($day, $user_id){
        $shift = shiftRecord::where('shift_day', $day)
                            ->where('user_id', $user_id)
                            ->first();
        if($shift){
            $shift->department_id = null;
            $shift->save();
        }
    }
    private function saveWorkIncentive($user, $request, $is_exist){
        if($user->position_id === 15){
            [$currentYear, $currentMonth] = explode('-', $request->day);
            $yearMonth = $currentYear . '-' . $currentMonth;
            $filteredCosts = array_filter($request->incentiveValues, function ($incentive) {
                return !(
                    $incentive['count'] === null 
                );
            });
            foreach($filteredCosts as $incentive){
                $id = $incentive['id'] ?? null;
                $incentive_exist = $id ? timecardIncentive::findOrFail($id) : new timecardIncentive;
                $incentive_exist->record_id = $is_exist->id;
                $incentive_exist->user_id = $request->userId;
                $incentive_exist->date_month = $yearMonth;
                $incentive_exist->count = $incentive['count'];
                $incentive_exist->save();
            }
        }
    }
    private function saveWorkCost($request, $is_exist){
        
        [$currentYear, $currentMonth] = explode('-', $request->day);
        $yearMonth = $currentYear . '-' . $currentMonth;
        $filteredCosts = array_filter($request->costsValues, function ($cost) {
            return !(
                $cost['content'] === null &&
                $cost['expenses'] === null &&
                $cost['file_path'] === null
            );
        });
        $this->validateCost($filteredCosts);
        $is_exist->timecard_costs()->delete();
        $costRecords = array_map(function ($cost) use ($is_exist, $request, $yearMonth) {
            return [
                'record_id' => $is_exist->id,
                'user_id' => $request->userId,
                'file_path' => $cost['file_path'],
                'type' => $cost['type'],
                'date_month' => $yearMonth,
                'content' => $cost['content'],
                'expenses' => $cost['expenses'],
                'department' => $cost['department'],
                'created_at' => now(),
                'updated_at' => now()
            ];
        }, $filteredCosts);
        timecardCostRecord::insert($costRecords);
    }
    private function validateCost($costs){
        foreach($costs as $move){
            if($move['department'] == null ){
                throw ValidationException::withMessages(['message' => '部門に割り当ててください。']);
            }
            if($move['content'] !== null ){
                if($move['expenses'] === null){
                    throw ValidationException::withMessages(['message' => '経費必須です。']);
                }
            }
        }   
    }
    private function checkWaitingAllowance($request){
        [$currentYear, $currentMonth] = explode('-', $request->day);
        $count = customFieldDataRecord::where('type_id', 37)
                                    ->whereNotNull('table_record_id')
                                    ->where('user_id', $request->userId)
                                    ->where('value_int', 2)
                                    ->whereHas('time_card_records', function ($query) {
                                        $query->where('status_flag', '!=', 0);
                                    })
                                    ->whereYear('date', $currentYear)
                                    ->whereMonth('date', $currentMonth)
                                ->count();
        if($count >= 5){
            throw ValidationException::withMessages(['message' => '待機手当は1か月に5回以上の利用はできません。']);
        }
    }
    private function saveCustomData($date, $table_record_id, $user_id, $value, $type_id, $vehicleData){
        if ($type_id === 43 && $value == 1){
            $this->saveVehicleData($vehicleData, $table_record_id, $user_id);
        }
        $new_custom_data = new customFieldDataRecord;
        $new_custom_data->date = $date;
        $new_custom_data->table_record_id = $table_record_id;
        $new_custom_data->user_id = $user_id;
        $new_custom_data->type_id = $type_id;
        
        switch ($type_id) {
            case 39:
            case 42: 
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
    private function saveVehicleData($vehicleData, $table_record_id, $user_id){
        $new_vehicle_data = new timecardVehicle;
        $new_vehicle_data->user_id = $user_id;
        $new_vehicle_data->record_id = $table_record_id;
        $new_vehicle_data->vehicle = $vehicleData['vehicle'];
        $new_vehicle_data->confirm_before_user = $vehicleData['confirm_before_user'];
        $new_vehicle_data->confirm_after_user = $vehicleData['confirm_after_user'];
        $new_vehicle_data->alcohol_before_time = $vehicleData['alcohol_before_time'];
        $new_vehicle_data->alcohol_after_time = $vehicleData['alcohol_after_time'];
        $new_vehicle_data->alcohol_before_value = $vehicleData['alcohol_before_value'];
        $new_vehicle_data->alcohol_after_value = $vehicleData['alcohol_after_value'];
        $new_vehicle_data->save();
    }
    public function deleteTimeCard(Request $request){
        $is_exist = timecardRecord::where('day', $request->date)->where('user_id', $request->userId)->first();
        $over_time = ShiftOvertimeRequest::where('overtime_day', $request->date)->where('user_id', $request->userId)->first();
        if($is_exist){
            $is_exist->custom_field_data_records()->delete();
            $is_exist->timecard_costs()->delete();
            $is_exist->timecard_incentives()->delete();
            $is_exist->vehicle_data()->delete();
            $is_exist->delete();
            if($over_time){
                $over_time->delete();
            }
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
                    ->select('user_id', 'shift_day', 'shift_type', 'status_flag')->with([
                        'shiftType' => function ($query) {
                            $query->select('id', 'name', 'abbreviation', 'value');
                        }
                    ]);
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
                    ->select('value_int', 'user_id', 'table_record_id');
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
        $shift_workdays = $user->shift_records->where('shift_type', 1)->pluck('shift_day');
        $worked_holiday_count = $user->time_card_records->whereIn('day', $shift_holidays)->count();
        $user->position_id === 15 ? $workedday_count = $user->time_card_records->count() : $workedday_count = $user->time_card_records->whereIn('day', $shift_workdays)->count();
        $worked_time = $user->time_card_records->sum('work_time');
        $holiday_worked_time = $user->time_card_records->whereIn('day', $shift_holidays)->sum('work_time');
        $approved_count = $user->time_card_records->where('status_flag', 2)->count();
        $unapproved_count = $user->time_card_records->where('status_flag', 1)->count();
        $unsaved_count = $user->time_card_records->where('stamp_flag', 1)->whereIn('status_flag', [0, 10])->count();
        $unapproved_shift_count = 0;
        if($user->position_id !== 15){
            $unapproved_shift_count = $user->shift_records->where('status_flag', 2)->count();
        }
        $night_over_time = $user->time_card_records->sum('night_over_time');
        $annual_leave = $user->shift_records
        ->whereNotIn('shift_type', [0, 1, 2, 14, 15, 16, 17]) 
        ->sum(fn($shift_record) => $shift_record->shiftType->value);

        $shift_work_hours = $user->work_time_day * $workdayNum;
        
        $condolence_leave = $user->shift_records->where('shift_type', 14)->count();
        $transfer_leave = $user->shift_records->where('shift_type', 15)->count();
        $oda_leave = $user->shift_records->where('shift_type', 16)->count();
        $over_time = $user->time_card_records->sum('over_time');
        $annual_costs = 0;
        $annual_incentive = 0;
        $annual_costs = timecardCostRecord::where('user_id', $user->id)
                                        ->where('date_month', $request->current_date)
                                        ->select('expenses')
                                        ->sum('expenses');
        if($user->position_id == 15){
            $annual_incentive = timecardIncentive::where('user_id', $user->id)
                                        ->where('date_month', $request->current_date)
                                        ->select('count')
                                        ->sum('count');
        }
        
        $month_over_time = 0;
        
        $all_worked_time = $worked_time + $annual_leave 
            + ($condolence_leave + $transfer_leave) * $user->work_time_day;
        if ($shift_work_hours < $all_worked_time - $month_over_time) {
            $month_over_time = $all_worked_time - $shift_work_hours - $night_over_time;
        }
        if ($user->work_type == 1) {
            $month_over_time = $over_time;
        }
        $month_stay_allowance_count = $user->custom_field_data_records->whereNotNull('table_record_id')->where('value_int', 1)->count();
        $month_move_allowance_count = $user->custom_field_data_records->whereNotNull('table_record_id')->where('value_int', 0)->count();
        $month_waiting_allowance_count = $user->custom_field_data_records->whereNotNull('table_record_id')->where('value_int', 2)->count();
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
            'oda_leave' => $oda_leave,
            'month_over_time' => $month_over_time > 0 ? $month_over_time : 0,
            'over_time' => $over_time,
            'month_stay_allowance_count' => $month_stay_allowance_count,
            'month_move_allowance_count' => $month_move_allowance_count,
            'month_waiting_allowance_count' => $month_waiting_allowance_count,
            'worked_time' => $worked_time,
            'holiday_worked_time' => $holiday_worked_time,
            'night_over_time' => $night_over_time,
            'annual_costs' => $annual_costs,
            'annual_incentives' => $annual_incentive,
            'unapproved_shift_count' => $unapproved_shift_count,
        ];

        return response()->json($responseArray);
    }
    public function remandTimeCard(Request $request){
        $user = $this->active_user();
        $time_card_record = timecardRecord::where('user_id', $request->user_id )->where('day', '=' , $request->record_day )->first();
        // if($request->overTimeRequest){
        //     $data = [
        //         'id' => $request->overTimeRequest['id'],
        //         'status' => 0,
        //         'approved_by' => $user->id
        //     ];
        //     $this->respond_overtime(new Request ($data));
        // }
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
            $comp_holiday = $shift_records->where('shift_type', 17)->count();
            $shiftTypes = [13, 12, 11, 10, 9, 8, 7, 6];
            $hours_count = 0;
            $working_hour_low = 0;
            foreach ($shiftTypes as $type) {
                $count = $shift_records->where('shift_type', $type)->count();
                $hours_count += $type === 6 ? $count * 0.5 : $count;
                if ($type !== 6) {
                    $working_hour_low += $count;
                }
            }
            $closed_day = $shift_records->where('shift_type', 2)->count();
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
            $attendance_record->working_days_shift = $request->shift_working_days;
            $attendance_record->normal_working_days = $request->worked_days + $hours_count;
            $attendance_record->holiday_working_days = $request->holiday_worked_days;
            $attendance_record->paid_holiday_hours = $request->annual_leave;
            $attendance_record->condolence_holiday = $request->condolence_leave;
            $attendance_record->special_holiday = $request->transfer_leave;
            $attendance_record->oda_holiday = $request->oda_leave;
            $attendance_record->comp_holiday = $comp_holiday;
            $attendance_record->working_hours = $request->worked_hours;
            $attendance_record->working_hours_no_over = $request->worked_hours_no_over_time;
            $attendance_record->over_time = $request->over_time;
            $attendance_record->night_work_time = $request->night_work_time;
            $attendance_record->stay_pay = $request->stay_pay;
            $attendance_record->move_pay = $request->move_pay;
            $attendance_record->waiting_pay = $request->waiting_pay;
            $attendance_record->expenses = $request->expenses;
            $attendance_record->incentive = $request->incentive;
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
        $user_detail = $auth_user->user_detail;
        
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $today = date("Y-m");
        $year = date("Y");
        $month = date("m");
        $ids = [610, 608];
        if(in_array($auth_user_id, $ids) || in_array($auth_user->position_id, [1, 2, 3, 4, 5, 14, null])){
            
            return response()->json([
                'timecard_notSubmitted' => [],
                'shift_notSubmitted' => []
            ]);
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
        if($user_detail) {
            $leave_start = $user_detail->leave_start;
            $leave_end = $user_detail->leave_end;
        }
        if(count($shift_record) < $numberOfDays){
            $shiftNotSubmittedList[] = [
                'year' => $year, 
                'month' => (int) $month , 
                'value' => $today
            ];
        }else{
            if(empty($attendance_this_record)){
                foreach($shift_record as $value){
                    $shiftSubmittedList[$value->shift_day] = [
                        "type" => $value->shift_type,
                        "status" => $value->status_flag
                    ];
                }
                if(!empty($prev_shift_record)){
                    foreach($prev_shift_record as $valuePrev){
                        $shiftSubmittedList[$valuePrev->shift_day] = [
                            "type" => $valuePrev->shift_type,
                            "status" => $valuePrev->status_flag,  
                        ];
                    }
                }
                foreach($shiftSubmittedList as $date => $value2){
                    if (!is_array($value2) || $value2['type'] != 1) {
                        continue;
                    }
                    if (isset($leave_start, $leave_end) && $date >= $leave_start && $date <= $leave_end) {
                        continue;
                    }
                    if($date <= $yesterday){
                        $timecard = timecardRecord::where('deleted_at', null)
                                                    ->where('user_id', $auth_user_id )
                                                    ->where('day', $date)
                                                    ->with('timecard_costs')
                                                    ->with('custom_field_data_records')
                                                    ->with('department')
                                                    ->first();
                        if($timecard === null){
                            $dateExplode = explode("-",$date);
                            $timecardNotSubmittedList[] = [
                                'year' => (int) $dateExplode[0],
                                'month' => (int) $dateExplode[1],
                                'day' =>  (int) $dateExplode[2],
                                'value' => $date,
                                'shiftStatus' => $value2['status'],
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
                                'shiftStatus' => $value2['status'],
                                'costs' => $timecard->timecard_costs,
                                'customData' => $timecard->custom_field_data_records,
                                'department' => $timecard->department,
                                'user_id' => $timecard->user_id,
                                'work_group_id' => $timecard->work_group_id,
                                'shiftEndTime' => $timecard->edit_end_time,
                                'shiftStartTime' => $timecard->edit_start_time,
                                'shiftOverTimeRequest' => $shift_overtime_requests->where('overtime_day', $date)->first()
                            ];
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
        $data = [
            'timecard_notSubmitted' => $timecardNotSubmittedList,
            'shift_notSubmitted' => $shiftNotSubmittedList
        ];
        return response()->json($data);
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
            $attendance_record->oda_holiday = 0;
            $attendance_record->working_days_shift = 0;
            $attendance_record->pay_day = 20;
            $attendance_record->absence_days = 0;
            $attendance_record->absence_hour = 0;
            $attendance_record->expenses = 0;
            $attendance_record->incentive = 0;
            $attendance_record->save();

        }
        return response()->json($request);
    }
    public function not_approved(Request $request){
        $date = Carbon::now();
        $day = $date->day;
        $year = $date->year;
        $month = $date->month;
        $prev_month = $date->clone()->subMonth()->month;
        $shift_month = $day >= 25 ? $date->clone()->addMonthNoOverflow()->month : $month;
        $ids = [608, 610];
        $active_user = $this->active_user();
        $target_users = [];
        $workGroupIds = [];
        $list = [];
        if(in_array($active_user->id, $ids)){
            $pms = User::where('position_id', 6)
                        ->where('retire', 0)
                        ->where('partner_flag', 0)
                        ->where('deleted_flag', 0)
                        ->where('on_leave', 0)
                        ->pluck('id')->toArray();
            $target_users = $pms;
            $workGroupIds = ProjectRecord::pluck('id')->unique()->values()->toArray();
        }
        if($active_user->position_id == 6){
            $workGroups = ProjectRecord::whereHas('manager', function ($q) use($active_user, $ids) {
                $q->where('users.id', $active_user->id)->whereNotIn('users.id', $ids);
            })->with('members')->get();
            
            $workGroupIds = $workGroups->unique()->pluck('id')->toArray();
        
            $workGroups = $workGroups->flatMap(function ($workGroup) {
                return $workGroup->members;
            })->unique('id')->values()->pluck('id')->toArray();
            $target_users = array_merge($target_users, $workGroups);

        }
        $user_list = User::whereIn('id', $target_users)
                        ->with([
                            'time_card_records' => function ($q) use($year, $month, $workGroupIds, $prev_month) {
                                $q->whereYear('day', $year)
                                    ->where(function ($query) use ($month, $prev_month) {
                                        $query->whereMonth('day', $month)
                                            ->orWhereMonth('day', $prev_month);
                                    })
                                    ->where('status_flag', 1)
                                    ->whereIn('work_group_id', $workGroupIds);
                            },
                            'shift_overtime' => function ($q) use($year, $month) {
                                $q->where('status', 1)
                                    ->whereYear('overtime_day', $year)
                                    ->whereMonth('overtime_day', $month);
                            },
                            'shift_records' => function ($q) use ($year, $month, $prev_month, $shift_month) {
                                $q->whereYear('shift_day', $year)
                                    ->where('status_flag', 2)
                                    ->where(function ($innerQuery) use ($month, $prev_month, $shift_month) {
                                      $innerQuery->whereMonth('shift_day', $month)
                                                 ->orWhereMonth('shift_day', $prev_month)
                                                 ->orWhereMonth('shift_day', $shift_month);
                                    })->selectRaw('MONTH(shift_day) as month, COUNT(*) as count, user_id')
                                    ->groupByRaw('MONTH(shift_day), user_id');
                            }
                        ])->select('id', 'name', 'icon_id')->get();
        foreach($user_list as $user){
            $timeCardsCount = $user->time_card_records->count();
            $overtimeRequests = $user->shift_overtime->count();
            $shiftCount = $user->shift_records;
            
             $d = [
                "user" => $user,
                "timecard" => $timeCardsCount,
                "overtime" => $overtimeRequests,
                "shift" => $shiftCount,
            ];
            if($timeCardsCount || $overtimeRequests || count($shiftCount)){                    
                $list[] = $d;
            }
        }
        
        return response()->json($list);
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
            "content" => $request->overtime_content,
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
    private function path_generator(){
        $timestamp = time();
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $iconId = $timestamp . $randomString;
        if (strlen($iconId) > 15) {
            $iconId = substr($iconId, 0, 15);
        }    
        return $iconId;
    }
    public function work_file_upload(Request $request){
        $path = '/timecard_files';
        $fileContent = $request->file('file');
        $file_path = $this->path_generator();           
        $file_extension = $fileContent->getClientOriginalExtension();
            
        $mime_type = $fileContent->getMimeType();
        $mime_type_array = explode('/',$mime_type);
        $file_type = $mime_type_array[0];           
        
        if($file_type == 'image' && $file_extension !== 'svg'){
            $img = Image::read($fileContent);
            $file_extension = 'webp';
            $img->scale(640);
            $file_path .= '.webp';
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . $path, 0755, true, true);                      
            $img->toWebp(80)->save(storage_path('app') . $path .'/'. $file_path);  
        } else {
            $file_path .= ".{$file_extension}";
            Storage::disk('local')->putFileAs(
                $path, $fileContent, $file_path
            );
        }
        $data = [
            "file_path" => $file_path,
            "file_type" => $file_type,
            "file_extension" => $file_extension
        ];
        return response()->json($data); 
    }
    public function work_cost_delete(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $workCost = timecardCostRecord::findOrFail($request->id)->delete();

        return response()->json($workCost);
    }
    public function work_incentive_delete(Request $request){
        $request->validate([
            'id' => 'required',
        ]);
        $workCost = timecardIncentive::findOrFail($request->id)->delete();

        return response()->json($workCost);
    }
    public function next_month_shift(Request $request){
        $currentDate = Carbon::now();
        $dayOfMonth = $currentDate->day;
        if($dayOfMonth >= 25){
            $nextMonthDate = $currentDate->addMonthNoOverflow();
            $nextMonthYear = $nextMonthDate->year;
            $nextMonth = $nextMonthDate->month;
            $auth_user = Auth::user();
            $ids = [610, 608];
            $shiftNotSubmittedList = [];
            if(in_array(Auth::id(), $ids) || in_array($auth_user->position_id, [1, 2, 3, 4, 5, 14, null])){
                
                return response()->json();
            }
            $nextMonthShift = shiftRecord::whereYear('shift_day', $nextMonthYear)
                                        ->whereMonth('shift_day', $nextMonth)
                                        ->where('user_id', Auth::id())->get();
            $numberOfDays = cal_days_in_month(CAL_GREGORIAN, $nextMonth, $nextMonthYear);

            if(count($nextMonthShift) < $numberOfDays){
                $shiftNotSubmittedList[] = [
                    'year' => $nextMonthYear, 
                    'month' => (int) $nextMonth ,
                ];
            }
            return response()->json($shiftNotSubmittedList);
        }
        return response()->json([]);
    }         
    public function work_generate_csv(Request $request){
        $year = (int) $request->year;
        $month = (int) $request->month + 1;
        $users_list = explode(",", $request->users);
        foreach ($users_list as &$value) {
            $value = intval($value);
        }
        $users = User::whereIn('id', $users_list)->with(['time_card_records' => function($q) use($year, $month) {
            $q->whereYear('day', $year)->whereMonth('day', $month)
                ->with(['custom_field_data_records' => function ($q) {
                    $q->whereIn('type_id', [37, 40, 39, 41, 42])->orderBy('created_at', 'desc')->select('id', 'table_record_id', 'type_id', 'value_text', 'value_int', 'date', 'label', 'user_id');
                }])
                ->with(['timecard_costs', 'timecard_incentives'])
                ->select('id', 'break_time', 'end_time', 'day', 'over_time', 'stamp_flag', 'start_time', 'status_flag', 'work_time', 'user_id');
        }])->with(['shift_records' => function ($q) use($year, $month) {
            $q->whereYear('shift_day', $year)->whereMonth('shift_day', $month)
                ->with([
                    'shiftType' => function ($query) {
                        $query->select('id', 'name', 'abbreviation', 'value');
                    },
                    'overtime_request'
                ])
                ->select('id', 'shift_day', 'shift_type', 'user_id', 'start_time', 'end_time', 'status_flag');
        }])->with(['custom_field_data_records' => function ($q) use($year, $month) {
            $q->whereYear('date', $year)->whereMonth('date', $month)
                ->where('type_id', 43);
        }])->get();  
        $insentive_user = $users->where('position_id', 15)->first();
        $insentive_exists = !empty($insentive_user); 
        $recordList = [];
        $conditions = ['🌈','☀️','☁️','☂️','⚡','☃️'];
        for ($day = 1; $day <= cal_days_in_month(CAL_GREGORIAN, $month, $year); $day++) {
            $date = Carbon::create($year, $month, $day);
        
            foreach ($users as $user) {
                $targetShiftDay = $date->format('Y-m-d');
                $time_card_record = $user->time_card_records->where('day', $targetShiftDay)->first();                
                $shift = $user->shift_records->where('shift_day', $targetShiftDay)->first();
                $condition_index = $user->custom_field_data_records->where('date', $targetShiftDay)->first()?->value_int;
                $comment = empty($time_card_record) ? '' : $time_card_record->custom_field_data_records->where('type_id', 39)->first();
                $allowances = empty($time_card_record) ? [] : $time_card_record->custom_field_data_records->where('type_id', 37)->pluck('label')->toArray();    
                $allowances_value = implode(" ", $allowances); 
                $incident = empty($time_card_record) ? [] : $time_card_record->custom_field_data_records->where('type_id', 40)->first();      
                $costs = !empty($time_card_record) ? $time_card_record->timecard_costs : [];
                
                $satisfy = empty($time_card_record) ? [] : $time_card_record->custom_field_data_records->where('type_id', 41)->first();  
                $isRegistered = $user->position_id == 15;
                $costFormatted = '';
                if($isRegistered){
                    $transportCost = collect($costs)->where('type', 1)->sum('expenses');
                    $communicationCost = collect($costs)->where('type', 2)->sum('expenses');
                    $accommodationCost = collect($costs)->where('type', 3)->sum('expenses');
                    $costFormatted = ($transportCost ? "交通費 : $transportCost" . '円 ' : '') . ($communicationCost ? "通信費 : $communicationCost" . '円' : "") . ($accommodationCost ? "宿泊費 : $accommodationCost" . '円' : "");
                }else{
                    $travelCost = collect($costs)->where('type', 4)->sum('expenses');
                    $communicationCost = collect($costs)->where('type', 2)->sum('expenses');
                    $suppliesCost = collect($costs)->where('type', 5)->sum('expenses');
                    $entertainmentCost = collect($costs)->where('type', 6)->sum('expenses');
                    $commissionCosts = collect($costs)->where('type', 7)->sum('expenses');
                    $welfareExpense = collect($costs)->where('type', 8)->sum('expenses');
                    $costFormatted = ($travelCost ? "旅費交通費 : $travelCost'円'": '') . 
                                    ($communicationCost ? "通信費 : $communicationCost'円'" : "") . 
                                    ($suppliesCost ? "消耗品費 : $suppliesCost'円'" : "") . 
                                    ($entertainmentCost ? "交際費 : $entertainmentCost'円'" : "") . 
                                    ($commissionCosts ? "支払手数料 : $commissionCosts'円'" : "") . 
                                    ($welfareExpense ? "福利厚生費 : $welfareExpense'円'" : "" );
                }
               
                $data = [
                    '日付' => $date->format('Y-m-d'),
                    'メンバー' => $user->name,
                    '予定' => empty($shift) || $isRegistered ? '' : $shift->shiftType->name,
                    '出勤' => empty($time_card_record) ? '' : substr($time_card_record->start_time, 0, 5),
                    '退勤' => empty($time_card_record) ? '' : substr($time_card_record->end_time, 0, 5),
                    '労働時間' => (empty($time_card_record) ? '' : floor($time_card_record->work_time / 60).'時間') . (empty($time_card_record) ? '' : ($time_card_record->work_time % 60).'分')   ,
                    '時間外' => empty($time_card_record) ? '' : ($time_card_record->over_time).'分',
                    '休憩時間' => empty($time_card_record) ? '' : ($time_card_record->break_time).'分',
                    '諸手当' => $allowances_value, 
                    'インシデント' => empty($incident) ? '' : $incident->label,
                    '目標達成率' => empty($satisfy) ? '' : $satisfy->label,
                    'コンディション' => $condition_index ? $conditions[$condition_index] : '',
                    'コメント' => $comment ? $comment->value_text : '',
                    '経費' => $costFormatted,
                ];
                if($insentive_exists){
                    
                    $incentives = $isRegistered && !empty($time_card_record) ? $time_card_record->timecard_incentives : [];
                    $totalIncentive = collect($incentives)->sum('count');
                    $data['インセンティブ'] = $totalIncentive ? $totalIncentive . "件" : '';
                }
                array_push($recordList, $data);
            }
        }
        return response()->json($recordList);
    }    

    public function shift_add_department(Request $request) {
        $request->validate([
            'id' => 'required',
        ]);
        $update = shiftRecord::findOrFail($request->id)->update(['department_id' => $request->department_id]);
        return response()->json($update);
    }
}

