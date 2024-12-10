<?php

namespace App\Http\Controllers;

use App\Models\timecardCostRecord;
use App\Models\timecardIncentive;
use App\Models\User;

use App\Models\attendanceRecord;

use App\Models\customFieldDataRecord;


use App\Models\shiftRecord;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

use Carbon\Carbon;



class AdminWorkController extends Controller{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
    }

    public function index(){

        
    }

    public function get_admin_work(Request $request) {

        $month = $request->month;
        $today = Carbon::now();
        [$currentYear, $currentMonth] = explode('-', $request->month);
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント', '研修サポート'];
        $ids = [608, 610];
        $all_users = User::where('partner_flag', 0)
        ->whereNotIn('name', $ng_list)
        ->whereNotIn('id', $ids)
        ->where(function ($query) {
            $query->where('retire', 0)
                  ->orWhere('retire_date', '>=', Carbon::now());
        })
        ->with(['shift_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('shift_day', $currentYear)
              ->whereMonth('shift_day', $currentMonth)
              ->select('shift_day', 'shift_type', 'user_id', 'department_id')
              ->orderBy('shift_day', 'asc')
              ->with('shiftType')
              ->with('department');
        }])
        ->with(['time_card_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('day', $currentYear)
              ->whereMonth('day', $currentMonth)
              ->select('work_time', 'day', 'id', 'user_id', 'work_group_id')
              ->orderBy('day', 'asc')
              ->with(['custom_field_data_records' => function($q) {
                $q->where('type_id', 40)
                ->where('value_int', '=' , 1)
                ->select('type_id', 'value_int', 'date', 'table_record_id');
              }])
              ->with('department');
        }])
        ->with(['attendance_records' => function($q) use($month){
            $q->where('date_year_month', $month)->select('month_petition', 'user_id');
        }])->get([
            'id',
            'name',
            'user_code',
            'general_position'
        ]);
        $time_card_costs = timecardCostRecord::where('date_month', $request->month)
                                                ->with(['user' => function ($q) {
                                                    $q->select('id', 'name');
                                                }])
                                                ->with(['timecard' => function ($q) {
                                                    $q->select('id', 'day');
                                                }])
                                                ->select('id', 'date_month', 'department', 'type', 'expenses', 'user_id', 'record_id')
                                                ->get();
        $userIds = $all_users->pluck('id');
        
        
        $attendance_record = attendanceRecord::where('date_year_month', $month)->get();
        
        $expenses = timecardCostRecord::selectRaw(
            'user_id,
            SUM(expenses) as totalExpenses'
        )->whereIn('user_id', $userIds)
        ->where('date_month', $request->month)
        ->groupBy('user_id')
        ->get();
        $monthly_expenses = $expenses->pluck('totalExpenses', 'user_id');
        $incentives = timecardIncentive::selectRaw(
            'user_id,
            SUM(count) as totalCount'
        )->whereIn('user_id', $userIds)
        ->where('date_month', $request->month)
        ->groupBy('user_id')
        ->get();
        $monthly_incentive = $incentives->pluck('totalCount', 'user_id');

        $sevenDaysAgo = now()->subDays(3);
        now()->day >= 1 && now()->day <= 6 ? $previousMonth = $currentMonth - 1 : $previousMonth = null;  
        $custom_weather_data = customFieldDataRecord::whereIn('user_id', $userIds)
        ->whereYear('date', $currentYear)
        ->where(function ($query) use ($currentMonth, $previousMonth) {
            $query->whereMonth('date', $currentMonth);
            if ($previousMonth) {
                $query->orWhereMonth('date', $previousMonth);
            }
        })
        ->where('type_id', 43)
        ->where('deleted_flag', 0)
        ->orderBy('user_id')
        ->orderBy('date')
        ->get(['user_id', 'value_int', 'date'])
        ->groupBy('user_id')
        ->map(function ($userRecords) {
            return $userRecords->sortByDesc('date')->take(3);
        });


        $streakDataPerUser = [];
        $mostCommonValuesPerUser = [];
        $custom_weather_data->each(function ($records, $userId) use (&$streakDataPerUser, &$mostCommonValuesPerUser) {
            foreach ($records as $record) {
                $value_int = $record->value_int;
                if (!isset($streakDataPerUser[$userId])) {
                    $streakDataPerUser[$userId] = [
                        'current_streak' => 0,
                        'max_streak' => 0,
                    ];
                }
                if (in_array($value_int, [3, 4, 5])) {
                    $streakData = &$streakDataPerUser[$userId];
                    $streakData['current_streak']++;
                    $streakData['max_streak'] = max($streakData['max_streak'], $streakData['current_streak']);
        
                    if ($streakData['current_streak'] >= 3) {
                        $mostCommonValuesPerUser[$userId] = [
                            'current_streak' => $streakData['current_streak'],
                            'max_streak' => $streakData['max_streak'],
                            'current_value' => $value_int,
                        ];
                    }
                } else {
                    $streakDataPerUser[$userId]['current_streak'] = 0;
                }
            }
        });
            
            $new_shift_record_array = [];
            $month_work_time_array2 = [];
            $allDepartmentCounts = collect();
            foreach ($all_users as $user) {
                $shiftTypes = range(3, 16);
                $totalPaidHours = 0;
                if (count($user->shift_records) > 0) {
                    $shiftRecords = $user->shift_records->map(function ($record) {
                        $record['month'] = Carbon::parse($record['shift_day'])->format('Y-m');
                        return $record;
                    });
                    $shiftRecords = $shiftRecords->filter(function ($record) {
                        return isset($record['department']['name']);
                    });
                    $departmentCounts = $shiftRecords->groupBy(function ($record) use($user) {
                        return $record['department']['name'] . '|' . $user->name . '|' . $record['month'];
                    })->map(function ($records, $key) use($user){
                        return [
                            'count' => $records->count(),
                            'department' => $records->first()['department']['name'],
                            'username' => $user->name,
                            'month' => $records->first()['month']
                        ];
                    });
                    $allDepartmentCounts = $allDepartmentCounts->merge($departmentCounts);
                    foreach ($user->shift_records as $record) {
                        $user_id = $record->user_id;
                        $shift_day = $record->shift_day;
                        $shift_type = $record->shiftType;
                        if (in_array($shift_type->id, $shiftTypes)) {
                            $totalPaidHours += $shift_type->value;
                            $new_shift_record_array[$user_id][] = [
                                'day' => $shift_day,
                                'type' => $shift_type->id,
                            ];
                        }
                    }
                }
                $workTimeInSeconds = 0;
                if (count($user->time_card_records) > 0) {
                    $workTimeInSeconds = $user->time_card_records->sum('work_time');
                    $timeCardRecords = $user->time_card_records->map(function ($record) {
                        $record['month'] = Carbon::parse($record['day'])->format('Y-m');
                        return $record;
                    });
                    $timeCardRecords = $timeCardRecords->filter(function ($record) {
                        return isset($record['department']['name']);
                    });
                    $departmentCounts = $timeCardRecords->groupBy(function ($record) use($user) {
                        return $record['department']['name'] . '|' . $user->name . '|' . $record['month'];
                    })->map(function ($records, $key) use($user){
                        return [
                            'count' => $records->count(),
                            'department' => $records->first()['department']['name'],
                            'username' => $user->name,
                            'month' => $records->first()['month']
                        ];
                    });
                    $allDepartmentCounts = $allDepartmentCounts->merge($departmentCounts);
                }
                $month_work_time_array2[$user->id] = $workTimeInSeconds + $totalPaidHours;
            }
            $allDepartmentCountsArray = $allDepartmentCounts->values()->all();
            $responseArray = [
                'attendance_record' => $attendance_record,
                'paid_holiday_record' => $new_shift_record_array,
                'month_work_time' => $month_work_time_array2,
                'users' => $all_users,
                'weather_average' => $mostCommonValuesPerUser,
                'monthly_expenses' => $monthly_expenses,
                'monthly_incentive' => $monthly_incentive,
                'timecard_costs' => $time_card_costs,
                'departments' => $allDepartmentCountsArray,
            ];

        return response()->json($responseArray);

    }
    

    public function get_planned_shifts(Request $request){
        $year = $request->year;
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $pos_list = [1, 2, 3, 4, 5, 14, 15];    
        $all_users = User::where('partner_flag', 0)->where('hide_flag', 0)
            ->where('retire', 0)
            ->whereNotIn('name', $ng_list)
            ->whereNotIn('position_id', $pos_list)
            ->with(['shift_records' => function($q) use($year){
                $q->where('planned_year', $year)->where('shift_type', 3)->with(['old_shift' => function ($query) {
                    $query->select('id', 'shift_day', 'shift_type')->with('shiftType')->withTrashed();
                }])
                ->select('shift_type', 'shift_day', 'user_id', 'planned_year', 'id', 'descendant_of')
                ->orderBy('shift_day', 'asc');
            }])->with(['workTemps' => function ($q) use($year){
                $q->whereYear('date', $year);
            }])->select('id', 'name', 'position_id', 'user_code')->get();
        
        return response()->json($all_users);
    }

    public function change_planned_shifts(Request $request){
        $changedShifts = $request->shifts;
        $shiftDays = collect($request->shifts)->pluck('shift_day');
        if(!empty((array)$changedShifts)){
            $updatedShifts = [];
            $existingShift = shiftRecord::whereIn('shift_day', $shiftDays)
                                        ->where('user_id', $request->userId)
                                        ->where('shift_type', 3)
                                        ->get()->pluck('shift_day');
            if(count($existingShift) > 0){
                $string = '';
                foreach($existingShift as $day){
                    $string = $string . $day . ' ';
                }
                throw ValidationException::withMessages(['message' => $string . '日はすでに計画された計画有給のため、変更することはできません。']);
            }
            $startDate = Carbon::parse($request->startDate);
            $endDate = $startDate->copy()->addYear()->subDay();
            $allShiftsValid = collect($changedShifts)->every(function ($shift) use ($startDate, $endDate) {
                $shiftDay = Carbon::parse($shift['shift_day']);
                return $shiftDay->between($startDate, $endDate);
            });
        
            if (!$allShiftsValid) {
                throw ValidationException::withMessages(['message' => "{$startDate->format('Y-m-d')}から{$endDate->format('Y-m-d')}の間で選択してください。"]);

            }
            shiftRecord::whereIn('shift_day', $shiftDays)
                        ->where('user_id', $request->userId)
                        ->whereNot('shift_type', 3)
                        ->delete();
            foreach($changedShifts as $shift){
                $shiftRecord = shiftRecord::findOrFail($shift['id']);

                $newShift = shiftRecord::create([
                    "user_id" => $shiftRecord->user_id,
                    "start_time" => $shiftRecord->start_time,
                    "end_time" => $shiftRecord->end_time,
                    "status_flag" => 1,
                    "shift_day" => $shift['shift_day'],
                    "descendant_of" => $shiftRecord->id,
                    "shift_type" => 3,
                    "planned_year" => $shiftRecord->planned_year
                ]);
                $shiftRecord->delete();
                $updatedShifts[] = $newShift;
            }
            return response()->json(['updated_shifts' => $updatedShifts]);
        }
        return 'changed shifts empty';
    }
}