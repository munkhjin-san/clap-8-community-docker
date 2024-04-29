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
        $user_list = User::where('retire', 0)->where('deleted_flag', 0)->whereNotNull('user_code')->pluck('user_code')->toArray();
        $strings = array_map('strval', $user_list);
        $result = '(' . implode(', ', $strings) . ')';
        $queryParams = [
            'app' => '9',
            "query" => "社員コード in $result limit 200",
            'fields' => ['社員コード', '文字列__1行__15']
        ];
        
        $queryString = http_build_query($queryParams);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-API-Token' => 'BH1geaWExPVVIaa48izBjDzCilqRslkNlcZgNvp4'
        ];
        $recieve = [];
        $response = Http::withHeaders($headers)->get($url);
        $responseData = $response->json();
        foreach ($responseData['records'] as $data){
            $recieve[] = ['user_code'=>$data['社員コード']['value'], 'general_position'=>$data['文字列__1行__15']['value']];

        }
        $attendance_record = attendanceRecord::where('date_year_month', $month)->get();
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント', '研修サポート'];
        $ids = [608, 610];
        $all_users = User::where('partner_flag', '=', 0)
        ->where('retire', 0)
        ->whereNotIn('name', $ng_list)
        ->whereNotIn('id', $ids)
        ->orWhere('retire_date', '>=', Carbon::now())
        ->with(['shift_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('shift_day', $currentYear)
              ->whereMonth('shift_day', $currentMonth)
              ->select('shift_day', 'shift_type', 'user_id')
              ->orderBy('shift_day', 'asc');
        }])
        ->with(['time_card_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('day', $currentYear)
              ->whereMonth('day', $currentMonth)
              ->select('work_time', 'day', 'id', 'user_id')
              ->orderBy('day', 'asc')
              ->with(['custom_field_data_records' => function($q) {
                $q->where('type_id', 40)
                ->where('value_int', '=' , 1)
                ->select('type_id', 'value_int', 'date', 'table_record_id');
                }]);
        }])
        ->with(['attendance_records' => function($q) use($month){
            $q->where('date_year_month', $month)->select('month_petition', 'user_id');
        }])->get([
            'id',
            'name',
            'user_code'
        ]);
        $userIds = $all_users->pluck('id');
        
        
        $expenses = timecardCostRecord::whereIn('user_id', $userIds)->where('date_month', $request->month)->get();
        $monthly_expenses = $expenses->groupBy('user_id')->map(function ($records) {
            return $records->sum('expenses');
        });
        $incentives = timecardIncentive::whereIn('user_id', $userIds)->where('date_month', $request->month)->get();
        $monthly_incentive = $incentives->groupBy('user_id')->map(function ($records) {
            return $records->sum('count');
        });
        $sevenDaysAgo = now()->subDays(7);
        if($today->day == 1 || $today->day == 2 || $today->day == 3 || $today->day == 4 || $today->day == 5 || $today->day == 6){
            $currentMonth -= 1;
        }
        $custom_weather_data = customFieldDataRecord::whereIn('user_id', $userIds)
            ->where('date', '>=', $sevenDaysAgo) // Filter by the last 7 days
            ->whereYear('date', $currentYear)
            ->whereMonth('date', $currentMonth)
            ->where('type_id', 43)
            ->where('deleted_flag', 0)
            ->orderBy('user_id')
            ->orderBy('date')
            ->get();

        $streakDataPerUser = [];
        $mostCommonValuesPerUser = [];
        foreach ($custom_weather_data as $record) {
            $user_id = $record->user_id;
            $value_int = $record->value_int;
            if (!isset($streakDataPerUser[$user_id])) {
                $streakDataPerUser[$user_id] = [
                    'current_streak' => 0,
                    'max_streak' => 0,
                ];
            }
            if (in_array($value_int, [3, 4, 5])) {
                $streakData = &$streakDataPerUser[$user_id];
                $streakData['current_streak']++;
                $streakData['max_streak'] = max($streakData['max_streak'], $streakData['current_streak']);
        
                if ($streakData['current_streak'] >= 3) {
                    $mostCommonValuesPerUser[$user_id] = [
                        'current_streak' => $streakData['current_streak'],
                        'max_streak' => $streakData['max_streak'],
                        'current_value' => $value_int,
                    ];
                }
            } else {
                $streakDataPerUser[$user_id]['current_streak'] = 0;
            }
        }
            
            $new_shift_record_array = [];
            $month_work_time_array2 = [];

            
            foreach ($all_users as $user) {
                $shiftTypes = range(3, 15);
                $shiftTypeCounts = array_fill_keys($shiftTypes, 0);
            
                if (count($user->shift_records) > 0) {
                    foreach ($user->shift_records as $record) {
                        $user_id = $record->user_id;
                        $shift_day = $record->shift_day;
                        $shift_type = $record->shift_type;
                        if (in_array($shift_type, $shiftTypes)) {
                            $shiftTypeCounts[$shift_type]++;
                            $new_shift_record_array[$user_id][] = [
                                'day' => $shift_day,
                                'type' => $shift_type,
                            ];
                        }
                    }
                }
            
                if (count($user->time_card_records) > 0) {
                    $work_time_array = $user->time_card_records->sum('work_time');
                    $workTimeInSeconds = $work_time_array;
                    
                    $totalPaidHours = 0;
                    foreach ($shiftTypes as $shift_type) {
                        switch($shift_type) {
                            case 6:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 4;
                                break;
                            case 7:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 1;
                                break;
                            case 8:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 2;
                                break;
                            case 9:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 3;
                                break;
                            case 10:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 4;
                                break;
                            case 11:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 5;
                                break;
                            case 12: 
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 6;
                                break;
                            case 13:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 7;
                                break;
                            default:
                                $totalPaidHours += $shiftTypeCounts[$shift_type] * 8;
                        }  
                    }
                    
                    $month_work_time_array2[$user->id] = $workTimeInSeconds + ($totalPaidHours * 60);
                }
            }
            $responseArray = array(
                'attendance_record' => $attendance_record,
                'paid_holiday_record' => $new_shift_record_array,
                'month_work_time' => $month_work_time_array2,
                'users' => $all_users,
                'weather_average' => $mostCommonValuesPerUser,
                'kintone_data' => $recieve,
                'monthly_expenses' => $monthly_expenses,
                'monthly_incentive' => $monthly_incentive 
            );

        return response()->json($responseArray);

    }
    

    public function get_planned_shifts(Request $request){
        $year = $request->year;
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $pos_list = [1, 2, 3, 4, 5];    
        $all_users = User::where('partner_flag', 0)->where('hide_flag', 0)
            ->where('retire', 0)
            ->whereNotIn('name', $ng_list)
            ->whereNotIn('position_id', $pos_list)
            ->with(['shift_records' => function($q) use($year){
                $q->where('planned_year', $year)->where('shift_type', 3)->with('old_shift')
                ->select('shift_type', 'shift_day', 'user_id', 'planned_year', 'id', 'descendant_of')
                ->orderBy('shift_day', 'asc');
            }])->with('workTemps')->select('id', 'name', 'position_id', 'user_code')->get();
        
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