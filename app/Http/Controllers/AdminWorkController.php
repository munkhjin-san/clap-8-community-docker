<?php

namespace App\Http\Controllers;

use App\Models\User;

use App\Models\attendanceRecord;
use App\Models\workGroup;


use App\Models\shiftType;


use App\Models\customFieldDataRecord;

use DateTime;

use App\Models\shiftRecord;
use App\Models\timecardRecord;
use App\Models\positionRecord;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use Carbon\Carbon;

use Symfony\Component\HttpFoundation\StreamedResponse;


class AdminWorkController extends Controller{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
    }

    public function index(){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        if($auth_user_id == 608 || $auth_user_id == 610 || $auth_user_id == 676 || $auth_user_id == 540 || $auth_user_id == 517 || $auth_user_id == 524 || $auth_user_id == 611 || $auth_user_id == 612 || $auth_user_id == 516 || $auth_user_id == 519 || $auth_user_id == 518 || $auth_user_id == 494 || $auth_user_id == 765){

        }else{
            return;
        }
        
        return;
    }

    public function getAllMessage(Request $request) {

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $dateToMonth = Carbon::createFromFormat('Y-m', $request->month)->subMonths(1);
        $prevMonth = $dateToMonth->format('Y-m');
        $month = $request->month;
        $today = Carbon::now();
        [$currentYear, $currentMonth] = explode('-', $request->month);
        // $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?app=96';
        // $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?app=928';

        // $headers = [
        //     'Authorization' => 'Basic', // Example custom header
        //     'X-Cybozu-API-Token' => 'ejYioBZZgazR5xDJKuilRadcdeo5uCeJRbvN16HF'
        // ];
        $recieve = [];
        // $response = Http::withHeaders($headers)->get($url);
        // $responseContent = $response->body();
        // $responseData = $response->json();
        // foreach ($responseData['records'] as $data){
        //     $recieve[] = ['user_code'=>$data['社員コード']['value'], 'general_position'=>$data['職位']['value']];

        // }
        $attendance_record = attendanceRecord::where('date_year_month', $month)->orderBy('created_at', 'desc')->get();
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $pos_list = [1, 2, 3, 4, 5];    
        $all_users = User::where('partner_flag', '=', 0)->where('hide_flag', '=', 0)->where('retire', 0)->whereNotIn('name', $ng_list)
        ->with(['shift_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('shift_day', $currentYear)->whereMonth('shift_day', $currentMonth)->orderBy('created_at', 'desc');
        }])
        ->with(['time_card_records' => function($q) use($currentYear, $currentMonth){
            $q->whereYear('day', $currentYear)->whereMonth('day', $currentMonth)->orderBy('created_at', 'desc')->with(['custom_field_data_records' => function($q) {
                $q->where('type_id', 40)->where('value_int', '=' , 1);
            }]);
        }])
        ->with(['attendance_records' => function($q) use($month){
            $q->where('date_year_month', $month);
        }])->get([
            'id',
            'name',
            'user_code'
        ]);
        $userIds = $all_users->pluck('id');
        
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
                // Initialize streak data
                $streakDataPerUser[$user_id] = [
                    'current_streak' => 0,
                    'max_streak' => 0,
                ];
            }
        
            // Check if the value is 3, 4, or 5
            if (in_array($value_int, [3, 4, 5])) {
                $streakData = &$streakDataPerUser[$user_id];
                $streakData['current_streak']++;
                $streakData['max_streak'] = max($streakData['max_streak'], $streakData['current_streak']);
        
                // Check if the current streak is 3 or more
                if ($streakData['current_streak'] >= 3) {
                    $mostCommonValuesPerUser[$user_id] = [
                        'current_streak' => $streakData['current_streak'],
                        'max_streak' => $streakData['max_streak'],
                        'current_value' => $value_int,
                    ];
                }
            } else {
                // Reset the streak if the value is not 3, 4, or 5
                $streakDataPerUser[$user_id]['current_streak'] = 0;
            }
        }
        $query_date = $request->month;
        $first_date = date("Y-m-01",strtotime($query_date));
        $last_date = date("Y-m-t",strtotime($query_date));
        $attendance = [$first_date,$last_date];
            
            $new_shift_record_array = [];
            $month_work_time_array = [];
            $month_work_time_array2 = [];

            
            foreach ($all_users as $user) {
                $shiftTypes = range(3, 15); // Shift types from 5 to 15
                $shiftTypeCounts = array_fill_keys($shiftTypes, 0);
            
                if (count($user->shift_records) > 0) {
                    foreach ($user->shift_records as $record) {
                        $user_id = $record->user_id;
                        $shift_day = $record->shift_day;
                        $shift_type = $record->shift_type;
                        // Only consider shift types from 5 to 15
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
                        if($shift_type == 5 || $shift_type == 14 || $shift_type == 15){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 8;
                        }else if($shift_type == 6){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 4;
                        }else if($shift_type == 7){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 1;
                        }else if($shift_type == 8){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 2;
                        }else if($shift_type == 9){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 3;
                        }else if($shift_type == 10){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 4;
                        }else if($shift_type == 11){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 5;
                        }else if($shift_type == 12){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 6;
                        }else if($shift_type == 13){
                            $totalPaidHours += $shiftTypeCounts[$shift_type] * 7;
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
            );

        return response()->json($responseArray);

    }
}