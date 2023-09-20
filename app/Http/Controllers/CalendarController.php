<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarRecord;
use App\Models\CalendarGroup;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Auth;
class CalendarController extends Controller
{
    public function get_calendar_data(Request $request){
        $validatedData = $request->validate([
            'day' => 'required',
        ]);

        $groupCheck = CalendarGroup::where('user_id', Auth::id())->exists();

        if(!$groupCheck){
            
            $newGroup = CalendarGroup::create([
                'user_id' => Auth::id(),
                'member_list' =>  Auth::id()
            ]);
            return response()->json($newGroup);
        }

        $group = CalendarGroup::where('user_id', Auth::id())->first();
        $list = array_map('intval', explode(',', $group->member_list));

        // return response()->json($list);



        $date = $request["day"];

        $carbonDate = Carbon::parse($date);
        $year = $carbonDate->year;
        $month = $carbonDate->month;
        $records = CalendarRecord::whereHas('calendar_users', function ($query) use ($list) {
            $query->whereIn('users.id', $list);
        })->whereYear('date_start', $year)
        ->whereMonth('date_start', $month)
        ->with('calendar_users')
        ->with('updated_by')
        ->get();

        $res = [
            $year . '-'. $month => $records
        ];
        return response()->json($records);
        
    }
    public function calendar_add_record(Request $request){

        // if($request->editId){
        //     $record = CalendarRecord::findOrFail($request->editId);
        // }else{
        //     $record = new CalendarRecord;
        // }
        
        // $record->title = $request->title;
        // $record->remarks = $request->remark;
        // $record->referrer = $request->referrer;
        // $record->release_flag = $request->release_flag;
        // $record->edit_all = $request->edit_all;
        // $record->repetition_type = $request->repetition_type;
        // $record->created_user = Auth::id();
        // $record->updated_user = Auth::id();
        // $record->user_id = Auth::id();

        

        // $record->save();

        // $record->calendar_users()->sync($request->users);

        

        // $rDate = date( 'Ymd' , strtotime($request->expiration_start));
        // $uId = uniqid();
        // $rGroupId = $rDate . $auth_user_id . $uId;
            // $record = [];
        if($request->repetition_type == 1){
            $repeat_record = $this->execute_weekly_record($request);
            return response()->json($repeat_record);
        }


        // return response()->json($record);
    }
    private function time_parser($instance, $time){               
        list($hour, $minute) = explode(':', $time);
        $combined = $instance->hour($start_hours)->minute($start_minutes);
        $cooked = Carbon::createFromFormat('Y-m-d H:i:s', $combined);
        return $cooked;
    }
    private function execute_weekly_record($request){

        // Carbon::setTimezone('Asia/Tokyo');
        $from = $request['repeat_span']['weekly']['repeat_date_from'] . '00:00:00';
        $to = $request['repeat_span']['weekly']['repeat_date_to'] . '00:00:00';

        $carbonFrom = Carbon::parse($from);
        $carbonTo = Carbon::parse($to);

        if ($carbonFrom->isAfter($carbonTo)) {
            // Swap $from and $to
            $temp = $from;
            $from = $to;
            $to = $temp;
        }

        $selected_days = $request['repeat_span']['weekly']['selected_days'];
        
        $selected_days_indexes = array_keys($selected_days, true);

        $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $from, 'Asia/Tokyo');
        $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $to, 'Asia/Tokyo');

        $daysWithinRange = array_filter($selected_days_indexes, function ($dayOfWeek) use ($startDate, $endDate) {
            $startOfWeek = $startDate->copy()->startOfWeek()->addDay($dayOfWeek); // Get the date of the selected day
            $endOfWeek = $endDate->copy()->startOfWeek()->addDay($dayOfWeek); // Get the date of the selected day
            return $startOfWeek->between($startDate, $endDate) || $endOfWeek->between($startDate, $endDate);
        });
        
        if(!count($daysWithinRange)){
            throw ValidationException::withMessages(['message' => '選択した日は有効期間に含まれていません。']);
        }
        // if($request['editId']){
        //     $record = CalendarRecord::findOrFail($request['editId']);
        // }else{
        //     $record = new CalendarRecord;
        // }
        // $record->save();

        
        
        // $test =  Carbon::now()->timezoneName;
        return Carbon::now()->shiftTimezone('Asia/Tokyo');;
        while ($startDate <= $endDate) {
            if (in_array($startDate->dayOfWeek, $selected_days_indexes)) {
                // $date_start_ready = $this->time_parser($startDate, $request['time_start']);
                // $date_end_ready = $this->time_parser($startDate, $request['time_end']);
                // $record->update([
                //     "date_start" => $date_start_ready,
                //     "date_end" => $date_end_ready
                // ]);
                $test[] = '55';
            }
                
            $startDate->addDay();
        }

        return $test;
    }
    private function avialable_items($type){
        
        $list = [
            'facilities' => [
                [ 'label' =>  '本社会議室', 'value' =>  0 ],
                [ 'label' =>  '本社休憩室', 'value' =>  1 ],
                [ 'label' =>  '大阪会議室', 'value' =>  2 ],
                [ 'label' =>  '東京会議室', 'value' =>  3 ],
                [ 'label' =>  '仙台会議室', 'value' =>  4 ],
                [ 'label' =>  '青森会議室', 'value' =>  5 ]
            ],
            'zooms' => [
                [ 'label' => 'Zoom1', 'value' => 0 ],
                [ 'label' => 'Zoom2', 'value' => 1 ],
                [ 'label' => 'Zoom3', 'value' => 2 ]
            ],
            'cars' => [
                [ 'label' => '福岡582く5617 ホンダライフ', 'value' => 0 ],
                [ 'label' => '福岡582え8686 ダイハツミラ', 'value' => 1 ],
                [ 'label' => '福岡580と5654 オッティ', 'value' => 2 ],
                [ 'label' => '福岡480わ3206 クリッパー', 'value' => 3 ],
                [ 'label' => '福岡480ね5019 バン', 'value' => 4 ],
                [ 'label' => '福岡480ね5020 バン', 'value' => 5 ]
            ]
        ];
        return $list[$type];
    }
    public function get_possible_facilities(Request $request){

        $list = $this->avialable_items($request->target);

        

        return response()->json($list);
    }
}
