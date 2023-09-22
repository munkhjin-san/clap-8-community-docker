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
use Carbon\CarbonPeriod;
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

        $facility_check = $this->facility_validate($request, true);
        // NO_REPEAT
        if($request->repetition_type == 0){
            $from_once = Carbon::parse($request->once_date);            
            $record = $this->execute_second_data_or_validate($request, $from_once, null, true);
            $ids[] = $record->id;
            $update_main_values = $this->execute_main_data($ids, $request, null);
            return response()->json($update_main_values);
        }
        // WEEKLY_REPEAT
        if($request->repetition_type == 1){
            $record_ids = $this->execute_weekly_record($request, [], true);
            $r_group_id = $request['repeat_span']['weekly']['repeat_date_from'] . Auth::id() . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $request, $r_group_id);
            return response()->json($update_main_values);
        }
        // MONTHLY_REPEAT
        if($request->repetition_type == 2){
            $record_ids = $this->execute_monthly_record($request, [], true);
            $r_group_id = $request['repeat_span']['monthly']['year_from'] . $request['repeat_span']['monthly']['selected_day'] . Auth::id() . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $request, $r_group_id);
            return response()->json($record_ids);
        }
        // YEARLY_REPEAT
        if($request->repetition_type == 3){
            $record_ids = $this->execute_yearly_record($request, [], true);
            $r_group_id = $request['repeat_span']['yearly']['year_from'] . $request['repeat_span']['yearly']['selected_month'] . Auth::id() . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $request, $r_group_id);
            return response()->json($record_ids);
        }
    }
    private function facility_validate($request, $throw){        
        $indexes = [];
        $inst = null;
        foreach ($request['facility'] as $index => $value) {
            if ($value !== null) {
                $indexes[] = $index;
            }
        }        
        if($request['repetition_type'] == 0){            
            $from_once = Carbon::parse($request['once_date']);
            foreach($indexes as $index){                         
                $inst = $this->execute_second_data_or_validate($request, $from_once, $index, $throw);                               
            }          
        }
        else if($request['repetition_type'] == 1){   
            $inst = $this->execute_weekly_record($request, $indexes, $throw);                        
        }
        else if($request['repetition_type'] == 2){   
            $inst = $this->execute_monthly_record($request, $indexes, $throw);                        
        }
        else if($request['repetition_type'] == 3){   
            $inst = $this->execute_yearly_record($request, $indexes, $throw);                        
        }
        return $inst;
    }
    private function check_duplicate_facility($index, $value, $start, $end, $throw){
        $exists = CalendarRecord::where($index, $value)
        ->where(function ($query) use ($start, $end) {
            $query->where(function ($subquery) use ($start, $end) {
                $subquery->where('date_start', '>=', $start)
                    ->where('date_start', '<', $end);
            })->orWhere(function ($subquery) use ($start, $end) {
                $subquery->where('date_end', '>', $start)
                    ->where('date_end', '<=', $end);
            });
        })->exists();
        if($exists == true){
            $name = $this->avialable_items($index);
            $result = collect($name)->where('value', $value)->pluck('label')->first();
            if($throw){
                throw ValidationException::withMessages(['message' => $start . ' - ' . $end . 'この時間帯' . $result . 'は既に予約されています。']);
            }
            
        } 
        return $exists;
        
    }
    private function execute_second_data_or_validate($request, $instance, $validate_index, $throw){
        if($validate_index){
            $date_start_ready = $this->time_parser($instance, $request['time_start']);
            $date_end_ready = $this->time_parser($instance, $request['time_end']);
            $inst = $this->check_duplicate_facility($validate_index, $request['facility'][$validate_index], $date_start_ready, $date_end_ready, $throw);
            return $inst;
        }
        
        if($request['editId']){
            $record = CalendarRecord::findOrFail($request['editId']);
        }else{
            $record = new CalendarRecord;
        }
        $record->title = $request['title'];
        $record->save();
        $date_start_ready = $this->time_parser($instance, $request['time_start']);
        $date_end_ready = $this->time_parser($instance, $request['time_end']);
        $record->update([
            "date_start" => $date_start_ready,
            "date_end" => $date_end_ready
        ]);
        $record->calendar_users()->syncWithPivotValues($request['users'], ["created_at" => now()]);
        if($request['facility']['qualified_institution'] !== null){
            
            $record->update([
                "qualified_institution" => $request['facility']['qualified_institution']
            ]);
        }
        if($request['facility']['qualified_zoom'] !== null){
            $record->update([
                "qualified_zoom" => $request['facility']['qualified_zoom']
            ]);
        }
        if($request['facility']['qualified_car'] !== null){
            $record->update([
                "qualified_car" => $request['facility']['qualified_car']
            ]);
        }
        return $record;
    }
    private function execute_main_data($ids, $request, $r_group_id){
        $records = CalendarRecord::whereIn('id', $ids)->update([
                "title" => $request['title'],
                "remarks" => $request['remark'],
                "referrer" => $request['referrer'],
                "release_flag" => $request['release_flag'],
                "edit_all" => $request['edit_all'],
                "repetition_type" => $request['repetition_type'],
                "created_user" => Auth::id(),
                "updated_user" => Auth::id(),
                "user_id" => Auth::id(),
                "r_group_id" => $r_group_id,
                "expiration_start" => $request['repeat_span']['weekly']['repeat_date_from'] . ' 00:00:00',
                "expiration_end" => $request['repeat_span']['weekly']['repeat_date_to'] . ' 00:00:00'
        ]);
        return $records;
    }
    private function time_parser($instance, $time){               
        list($hour, $minute) = explode(':', $time);
        $combined = $instance->hour($hour)->minute($minute)->second('00');
        $cooked = Carbon::createFromFormat('Y-m-d H:i:s', $combined);
        return $cooked;
    }
    private function execute_yearly_record($request, $validate_indexes, $throw){
        $selectedDay = $request['repeat_span']['yearly']['selected_day']; 
        $selectedMonth = $request['repeat_span']['yearly']['selected_month']; 
        $yearFrom = $request['repeat_span']['yearly']['year_from'];
        $yearTo = $request['repeat_span']['yearly']['year_to'];

        $ids = [];

        $currentDate = Carbon::now();
        $startDate = Carbon::create($yearFrom, 1, 1); 
        $endDate = Carbon::create($yearTo, 12, 31);    

        $period = CarbonPeriod::create($startDate, '1 year', $endDate);

        if(!count($period) && $throw){
            throw ValidationException::withMessages(['message' => '選択した日は有効期間に含まれていません。']);
        }
        foreach ($period as $date) {
            $dateToCheck = $date->setMonth($selectedMonth)->setDay($selectedDay);
            if ($dateToCheck->greaterThanOrEqualTo($currentDate) || $dateToCheck->isSameDay($currentDate)) {
                if(count($validate_indexes)){
                    foreach($validate_indexes as $index){                        
                        $record = $this->execute_second_data_or_validate($request, $dateToCheck, $index, $throw);      
                        if(!$throw && $record == true){
                            return $record;
                        }              
                    }                        
                }else{
                    $record = $this->execute_second_data_or_validate($request, $dateToCheck, null, false);
                    $ids[] = $record->id;
                }
            }
        }
        return $ids;
    }
    private function execute_monthly_record($request, $validate_indexes, $throw){
        $selectedDay = $request['repeat_span']['monthly']['selected_day']; 
        $yearFrom = $request['repeat_span']['monthly']['year_from'];
        $yearTo = $request['repeat_span']['monthly']['year_to'];
   
        $startDate = Carbon::create($yearFrom, 1, 1); 
        $endDate = Carbon::create($yearTo, 12, 31);   

        $period = CarbonPeriod::create($startDate, '1 month', $endDate);
        $currentDate = Carbon::now();
        $ids = [];

        foreach ($period as $date) {
            $lastDayOfMonth = $date->endOfMonth();
            $dayToUse = (int) min($selectedDay, $lastDayOfMonth->day);
            $dateToCompare = $date->setDay($dayToUse);
            if ($dateToCompare->greaterThanOrEqualTo($currentDate)) {
                if(count($validate_indexes)){
                    foreach($validate_indexes as $index){                        
                        $record = $this->execute_second_data_or_validate($request, $dateToCompare, $index, $throw); 
                        if(!$throw && $record == true){
                            return $record;
                        }                      
                    }                        
                }else{
                    $record = $this->execute_second_data_or_validate($request, $dateToCompare, null, false);
                    $ids[] = $record->id;
                }
                
            }
        }
        return $ids;
    }
    private function execute_weekly_record($request, $validate_indexes, $throw){
        $from = $request['repeat_span']['weekly']['repeat_date_from'] . '00:00:00';
        $to = $request['repeat_span']['weekly']['repeat_date_to'] . '00:00:00';

        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        if ($from->isAfter($to)) {
            $temp = $from;
            $from = $to;
            $to = $temp;
        }

        $selected_days = $request['repeat_span']['weekly']['selected_days'];
        
        $selected_days_indexes = array_keys($selected_days, true);

        $daysWithinRange = array_filter($selected_days_indexes, function ($dayOfWeek) use ($from, $to) {
            $startOfWeek = $to->copy()->startOfWeek()->addDay($dayOfWeek); 
            $endOfWeek = $from->copy()->startOfWeek()->addDay($dayOfWeek); 
            return $startOfWeek->between($to, $from) || $endOfWeek->between($from, $to);
        });
        
        if(!count($daysWithinRange) && $throw){
            throw ValidationException::withMessages(['message' => '選択した日は有効期間に含まれていません。']);
        }
        
        $ids = [];
        while ($from <= $to) {
            if (in_array($from->dayOfWeek, $selected_days_indexes)) {
                if(count($validate_indexes)){
                    foreach($validate_indexes as $index){                        
                        $record = $this->execute_second_data_or_validate($request, $from, $index, $throw); 
                        if(!$throw && $record == true){
                            return $record;
                        }                       
                    }                        
                }
                else{
                    $record = $this->execute_second_data_or_validate($request, $from, null, false);
                    $ids[] = $record->id;
                }
            }                
            $from->addDay();
        }

        return $ids;
    }
    private function avialable_items($type){
        
        $list = [
            'qualified_institution' => [
                [ 'label' =>  '本社会議室', 'value' =>  0 ],
                [ 'label' =>  '本社休憩室', 'value' =>  1 ],
                [ 'label' =>  '大阪会議室', 'value' =>  2 ],
                [ 'label' =>  '東京会議室', 'value' =>  3 ],
                [ 'label' =>  '仙台会議室', 'value' =>  4 ],
                [ 'label' =>  '青森会議室', 'value' =>  5 ]
            ],
            'qualified_zoom' => [
                [ 'label' => 'Zoom1', 'value' => 0 ],
                [ 'label' => 'Zoom2', 'value' => 1 ],
                [ 'label' => 'Zoom3', 'value' => 2 ]
            ],
            'qualified_car' => [
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

        
        $id_list = collect($list)->pluck('value')->toArray();
        
        $items = [];
        foreach($id_list as $id){
            $rec = [
                "time_start" => $request->time_start,
                "time_end" => $request->time_end,
                "once_date" => $request->once_time,
                "repetition_type" => $request->repeat,
                "repeat_span" => $request->repeat_span,
                "facility" => [$request->target => $id]
            ];
            $facility_check = $this->facility_validate($rec, false);
            // $unavialable_items[] = $facility_check;
            $item = [
                "label" => !$facility_check ? $list[$id]['label'] : $list[$id]['label'] . '（選択不可）' ,
                "value" => $list[$id]['value'],
                "availablity" => !$facility_check
            ];
            $items[] = $item;

        }

        return response()->json($items);

       
    }
    
}
