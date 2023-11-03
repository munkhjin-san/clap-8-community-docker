<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarRecord;
use App\Models\User;
use App\Models\MyGroup;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Mail\Calendar;
use Illuminate\Support\Facades\Storage;
use Auth;
use DB;
class CalendarController extends Controller
{
    public function urlsafe_base64_encode($str){
        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
    }
    private function zoom_account($index){
        $account_information = [
            [
                'accountId' => '_ubCKWJlRVCgbgb8UdiG1w',
                'clientId' => 'zWxB7QVSKa6cFHOi5W1BQ',
                'clientSecret' => 'LN7dmUOI6odCli14m9s723dBZIlnS0UF',
                'accountMail' => 'zoom1@glowd.co.jp',
            ],
            [
                'accountId' => '_ubCKWJlRVCgbgb8UdiG1w',
                'clientId' => 'WPER8r40QVWwa52loOCRzQ',
                'clientSecret' => 'WWEJX8YHPh2gnxJgggZB6Y47LVnPaNE8',
                'accountMail' => 'zoom2@glowd.co.jp',
            ],
            [
                'accountId' => 'pjE0gLynQu-qFakQHtdFew',
                'clientId' => 'I1s5DepQRs2umQuuqNO2mg',
                'clientSecret' => 'P9gydADDvOsyi4HcAOXMNSz8RPA3Mb6o',
                'accountMail' => 'zoom3@glowd.co.jp',
            ]
        ];
        return $account_information[$index];
    }
    public function zoomToken($zoomValue){
        
        $account = $this->zoom_account($zoomValue);    
        $baseUri = 'https://zoom.us/oauth/token';    
        $token = $this->urlsafe_base64_encode($account['clientId'].':'.$account['clientSecret']);
        $url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id='.$account['accountId'];
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $token,
            'Content-type' => 'application/json'
        ])->post($url);
        $data = $response->json();
        return $data['access_token'];
    }
    private function today_meetings($index, $token, $date){
        $account = $this->zoom_account($index); 
        $meetings_url = 'https://api.zoom.us/v2/users/' . $account['accountMail'] . '/meetings';
        $list = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-type' => 'application/json'
        ])->get($meetings_url, [
            'page_size' => 300,
            'from' => $date, 
            'to' => $date
        ]);       
        $meetings = $list->json();
        return $meetings['meetings'];
    }
    private function check_meeting_overlap($list, $start_check, $end_check, $exclude_id){
        if(!empty($list)){
            foreach($list as $meeting){
                if($meeting['id'] !== (int)$exclude_id){
                    $start = $meeting['start_time'];
                    $carbonDate = Carbon::parse($start);
                    $instance = $carbonDate->setTimezone('Asia/Tokyo');
                    $start_at_mt = $instance->copy();
                    $end_at_mt = $instance->copy()->add($meeting['duration'], 'minutes');
                    $start_at_ck = Carbon::parse($start_check);
                    $end_at_ck = Carbon::parse($end_check);
                    $overlap = !($end_at_ck <= $start_at_mt || $start_at_ck >= $end_at_mt);
                    if($overlap){
                        return 'overlap';
                    }
                }
                
            }
        }
        return 'ok';
    }
    private function delete_zoom_meeting($params){
        $account = $this->zoom_account($params['zoom_id']); 
        $meetings_url = 'https://api.zoom.us/v2/meetings' . '/' . $params['meetingId'];
        
        $data_to_zoom_api = array(
            'meetingId' => $params['meetingId'],
        );        

        $delete = Http::withHeaders([
            'Authorization' => 'Bearer ' . $params['token'],
            'Content-type' => 'application/json'
        ])
        ->withBody(json_encode($data_to_zoom_api))
        ->delete($meetings_url);  
        return $delete->successful();
        // if($delete->successful()){
        //     $result = $delete->json();
        //     return $result;
        // }else{
        //     throw ValidationException::withMessages(['message' => 'Zoom予約に失敗しました。']);
        // }
    }
    private function update_zoom_meeting($params){
        $account = $this->zoom_account($params['zoom_id']); 
        $meetings_url = 'https://api.zoom.us/v2/meetings' . '/' . $params['meetingId'];
        
        $data_to_zoom_api = array(
            'meetingId' => $params['meetingId'],
            'duration' => $params['duration'],
            'start_time' => $params['start_time'],
            'timezone' => 'Asia/Tokyo',
        );        

        $create = Http::withHeaders([
            'Authorization' => 'Bearer ' . $params['token'],
            'Content-type' => 'application/json'
        ])
        ->withBody(json_encode($data_to_zoom_api))
        ->patch($meetings_url); 
        $result = $create->json(); 
        return $result;
        // if($create->successful()){
        //     $result = $create->json();
        //     return $result;
        // }else{
        //     throw ValidationException::withMessages(['message' => 'Zoom予約に失敗しました。']);
        // }
    }
    private function create_zoom_meeting($params){
        
        $account = $this->zoom_account($params['zoom_id']); 
        $meetings_url = 'https://api.zoom.us/v2/users/' . $account['accountMail'] . '/meetings';
        $settings = array(
            'use_pmi' => 'false'
        );
        if($params['waiting_room']){
            $settings['waiting_room'] = true;
            $settings['join_before_host'] = false;
        }else{
            $settings['waiting_room'] = false;
            $settings['join_before_host'] = true;
            $settings['jbh_time'] = 0;
        }
        $data_to_zoom_api = array(
            'topic' => $params['title'],
            'type' => $params['type'],
            'duration' => $params['duration'],
            'start_time' => $params['start_time'],
            'timezone' => 'Asia/Tokyo',
            'default_password' => true,
            'settings' => $settings
        );        

        $create = Http::withHeaders([
            'Authorization' => 'Bearer ' . $params['token'],
            'Content-type' => 'application/json'
        ])
        ->withBody(json_encode($data_to_zoom_api))
        ->post($meetings_url);  

        if($create->successful()){
            $result = $create->json();
            return $result;
        }else{
            throw ValidationException::withMessages(['message' => 'Zoom予約に失敗しました。']);
        }
        
    }
    public function get_calendar_data(Request $request){    
        
        
        $validatedData = $request->validate([
            'day' => 'required',
        ]);

        $myGroupCheck = MyGroup::where('user_id', Auth::id())->exists();
        
        if(!$myGroupCheck){
            
            $newMyGroup = MyGroup::create([
                'user_id' => Auth::id(),
                'name' =>  Auth::user()->name . 'カレンダーグループ'
            ]);
            $newMyGroup->users()->syncWithPivotValues(Auth::id(), ['selected_as_calendar_member' => 1, "created_at" => now()]); 
        }        
        $gr = MyGroup::where('user_id', Auth::id())->latest()->first();
        $list = $gr->selected_users()->pluck('id')->toArray();
        $date = $request["day"];

        $carbonDate = Carbon::parse($date);
        $startOfMonth = $carbonDate->copy()->startOfMonth();
        $previousMonday = $startOfMonth->startOfWeek()->startOfWeek();
        
        $endOfMonth = $carbonDate->copy()->endOfMonth();
        $nextSunday = $endOfMonth->endOfWeek()->addWeek()->endOfWeek();
        $year = $carbonDate->year;
        $month = $carbonDate->month;
        $facility_check = !empty($request->facilities);
        $facilities = $request->facilities;
        
        $records = CalendarRecord::query();
        $filter = $records->when($facility_check, function ($query) use ($facilities, $list) {
            $query->where(function ($query) use ($facilities, $list) {
                foreach ($facilities as $index => $value) {
                    $query->orWhereIn($index, $value)
                        ->orWhereHas('calendar_users', function ($query) use ($list) {
                            $query->whereIn('users.id', $list);
                        });
                }
            });
        })
        ->when(!$facility_check, function ($query) use ($list) {           
            $query->whereHas('calendar_users', function ($query) use ($list) {
                $query->whereIn('users.id', $list);
            });
            
        })->whereBetween('date_start', [$previousMonday, $nextSunday])    
        ->with('calendar_users')
        ->with('updated_by')
        ->with('created_by')
        ->with('files')
        ->get();

        return response()->json($filter);
        
    }
    public function calendar_add_record(Request $request){



        
      
        $data = $request;
        $has_prev_date = null;
        if($request->editId){
            $target = CalendarRecord::findOrFail($request->editId);
            $has_prev_date = $target;
            if($request->edit_repeat){
                
                $all_recs = CalendarRecord::whereNotNull('r_group_id')->where('r_group_id', $target->r_group_id)->delete();
                if($target['zoom_value'] && $target['zoom_id']){
                    $token = $this->zoomToken($target['zoom_value']);
                    $params = [
                        "zoom_id" => $target['zoom_value'],
                        "meetingId" => $target['zoom_id'],
                        "token" => $token
                    ];
                    $delete_zoom = $this->delete_zoom_meeting($params);
                }
                $target->delete();
                $data['editId'] = null;
            }else{
                $record = $this->edit_single_record($request, $target,true);
                return response()->json('gggg');
            }
            
        }
        $facility_check = $this->facility_validate($request, true);
        // NO_REPEAT
        if($request->repetition_type == 0){
            $from_once = Carbon::parse($request->once_date);            
            $record = $this->execute_second_data_or_validate($data, $from_once, null, true);
            $ids[] = $record->id;            
            $update_main_values = $this->execute_main_data($ids, $data, null, $has_prev_date);
            return response()->json($update_main_values);
        }
        // WEEKLY_REPEAT
        if($request->repetition_type == 1){
            $record_ids = $this->execute_weekly_record($data, [], true);
            $r_group_id = $request['repeat_span']['weekly']['repeat_date_from'] . Auth::id() . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $data, $r_group_id, $has_prev_date);
            return response()->json($record_ids);
        }
        // MONTHLY_REPEAT
        if($request->repetition_type == 2){
            $record_ids = $this->execute_monthly_record($data, [], true);
            $r_group_id = $request['repeat_span']['monthly']['repeat_date_from'] . Auth::id() . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $data, $r_group_id, $has_prev_date);
            return response()->json($record_ids);
        }
        // YEARLY_REPEAT
        if($request->repetition_type == 3){
            $record_ids = $this->execute_yearly_record($data, [], true);
            $r_group_id = $request['repeat_span']['yearly']['year_from'] . $request['repeat_span']['yearly']['selected_month'] . Auth::id() . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $data, $r_group_id, $has_prev_date);
            return response()->json($record_ids);
        }
    }
    public function calendar_delete_record(Request $request){
        // return 'ff';
        $record = CalendarRecord::findOrFail($request->id);
        
        if($record->repetition_type > 0 && $record->r_group_id){
            if($request->all_delete){
                $allRecords = CalendarRecord::where('r_group_id', $record->r_group_id)->get();
                foreach($allRecords as $r_record){
                    $r_record->calendar_users()->detach();
                    $r_record->files()->detach();
                    $r_record->delete();                    
                }
                if($record->zoom_value && $record->zoom_id){
                    $token = $this->zoomToken($record['zoom_value']);
                    $params = [
                        "zoom_id" => $record['zoom_value'],
                        "meetingId" => $record['zoom_id'],
                        "token" => $token
                    ];
                    $delete_zoom = $this->delete_zoom_meeting($params);
                }
            }else{
                $record->calendar_users()->detach();
                $record->delete();
            }           
        }else{
            if($record->zoom_value && $record->zoom_id){
                $token = $this->zoomToken($record['zoom_value']);
                $params = [
                    "zoom_id" => $record['zoom_value'],
                    "meetingId" => $record['zoom_id'],
                    "token" => $token
                ];
                $delete_zoom = $this->delete_zoom_meeting($params);
            }
            $record->calendar_users()->detach();
            $record->delete();
        }
        

        return response()->json($request);
    }
    private function edit_single_record($request, $record, $keep_repeat){
        $indexes = [];
        $instance = Carbon::parse($request['once_date']); 
        foreach ($request['facility'] as $index => $value) {
            if ($value !== null) {
                $date_start_ready = $this->time_parser($instance, $request['time_start']);
                $date_end_ready = $this->time_parser($instance, $request['time_end']);
                $inst = $this->check_duplicate_facility($index, $value, $date_start_ready, $date_end_ready, true);
            }           
        }       
        
        $date_start_ready = $this->time_parser($instance, $request['time_start']);
        $date_end_ready = $this->time_parser($instance, $request['time_end']);

        $new_record = $record->replicate()->fill([
            "title" => $request['title'],
            "remarks" => $request['remarks'],
            "referrer" => $request['referrer'],
            "release_flag" => $request['release_flag'],
            "edit_all" => $request['edit_all'],
            "updated_user" => Auth::id(),
            "date_start" => $date_start_ready,
            "date_end" => $date_end_ready
        ]);

        $new_record->save();
        $new_record->calendar_users()->syncWithPivotValues($request['users'], ["created_at" => now(),"created_at" => now()]);
        if($request['facility']['qualified_institution'] !== null){            
            $new_record->update([
                "qualified_institution" => $request['facility']['qualified_institution']
            ]);
        }
        if($request['facility']['zoom_value'] !== null){
            $new_record->update([
                "zoom_value" => $request['facility']['zoom_value']
            ]);
        }
        if($request['facility']['qualified_car'] !== null){
            $new_record->update([
                "qualified_car" => $request['facility']['qualified_car']
            ]);
        }
        if($record['zoom_value'] && $record['zoom_id']){
            $token = $this->zoomToken($target['zoom_value']);
            $params = [
                "zoom_id" => $target['zoom_value'],
                "meetingId" => $target['zoom_id'],
                "token" => $token
            ];
            $delete_zoom = $this->delete_zoom_meeting($params);
        }
        $record->delete();
        return $new_record;
    }
    private function facility_validate($request, $throw){ 
        $indexes = [];
        $inst = null;
        foreach ($request['facility'] as $index => $value) {
            if ($value !== null) {
                $indexes[] = $index;
            }
        }      
        if(empty($indexes) || !count($indexes)){
            return true;
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
        if($request['repetition_type'] == 1){
            $selected_days = $request['repeat_span']['weekly']['selected_days'];        
            $selected_days_indexes = array_keys($selected_days, true);
            $record->update([
                "repeat_week" => implode(',', $selected_days_indexes),                
                "expiration_start" => $request['repeat_span']['weekly']['repeat_date_from'] . ' 00:00:00',
                "expiration_end" => $request['repeat_span']['weekly']['repeat_date_to'] . ' 00:00:00'
            ]);
        }else if($request['repetition_type'] == 2){
            $selectedDay = $request['repeat_span']['monthly']['selected_day'];            
            $record->update([
                "repeat_days" => $selectedDay,                
                "expiration_start" => $request['repeat_span']['monthly']['repeat_date_from'] . ' 00:00:00',
                "expiration_end" => $request['repeat_span']['monthly']['repeat_date_to'] . ' 00:00:00',
                "repeat_days" => $selectedDay
            ]);
        }
        else if($request['repetition_type'] == 3){
            $selectedDay = $request['repeat_span']['yearly']['selected_day']; 
            $selectedMonth = $request['repeat_span']['yearly']['selected_month']; 
            $record->update([
                "repeat_days" => $selectedDay,
                "repeat_month" => $selectedMonth
            ]);
        }
        $record->calendar_users()->syncWithPivotValues($request['users'], ["created_at" => now(),"created_at" => now()]);
        $record->files()->syncWithPivotValues($request['file_ids'], ["created_at" => now(),"created_at" => now()]);
        if($request['facility']['qualified_institution'] !== null){
            
            $record->update([
                "qualified_institution" => $request['facility']['qualified_institution']
            ]);
        }
        if($request['facility']['zoom_value'] !== null){
            $record->update([
                "zoom_value" => $request['facility']['zoom_value']
            ]);            
        }
        if($request['facility']['qualified_car'] !== null){
            $record->update([
                "qualified_car" => $request['facility']['qualified_car']
            ]);
        }
        return $record;
    }
    private function execute_main_data($ids, $request, $r_group_id, $has_prev_date){
        
        $zoom_values = array(
            "zoom_url" => null,
            "zoom_id" => null,
            "zoom_pass" => null,
            "zoom_account" => null,
            "zoom_account_pass" => null
        );

        if($request['facility']['zoom_value'] !== null){
            $instance = $request['repetition_type'] == 0 ? Carbon::parse($request['once_date']) : Carbon::now() ;
            $date_start_ready = $this->time_parser($instance, $request['time_start']);
            $date_end_ready = $this->time_parser($instance, $request['time_end']);

            $index = $request['facility']['zoom_value'];
            $token = $this->zoomToken($index);
            $s_date = Carbon::parse($date_start_ready);
            $e_date = Carbon::parse($date_end_ready);
            $day = $s_date->format('Y-m-d H:i:s');
            $meetings = $request['repetition_type'] == 0 ? $this->today_meetings($index, $token, $day) : [];

            $s1 = $s_date->copy()->format('Y-m-d H:i:s');
            $s2 = $e_date->copy()->format('Y-m-d H:i:s');

            $check_overlap = $request['repetition_type'] == 0 ? $this->check_meeting_overlap($meetings, $s1, $s2, null) : 'ok';
            if($check_overlap == 'ok'){

                $start = Carbon::parse($s1);
                $end = Carbon::parse($s2);
                $record_duration = $start->diff($end);
                $record_duration_minute = ($record_duration->h * 60 + $record_duration->i);
                $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $s1);
                $carbonDate->setTimezone('UTC');
                $formattedDate = $carbonDate->format('Y-m-d\TH:i:s\Z');
                $params = [
                    "token" => $token ,
                    "duration" => $record_duration_minute,
                    "title" => $request['title'],
                    "start_time" => $formattedDate,
                    "waiting_room" => $request['zoom_waiting_room'],
                    "zoom_id" => $request['facility']['zoom_value'],
                    "type" => $request['repetition_type'] == 0 ? 2 : 3            
                    
                ];
                $json_result = $this->create_zoom_meeting($params);
                $z_index = (int) $request['facility']['zoom_value'] + 1; 

                $zoom_values = array(
                    "zoom_url" => $json_result['join_url'],
                    "zoom_id" => $json_result['id'],
                    "zoom_pass" => $json_result['password'],
                    "zoom_account" => 'zoom'.$z_index.'@glowd.co.jp',
                    "zoom_account_pass" => 'Glowd0802'
                );
                // $record->update([
                //     "zoom_url" => $json_result['join_url'],
                //     "zoom_id" => $json_result['id'],
                //     "zoom_pass" => $json_result['password'],
                //     "zoom_account" => 'zoom'.$z_index.'@glowd.co.jp',
                //     "zoom_account_pass" => 'Glowd0802'
                // ]);
                
                

            }
        }
        $records = CalendarRecord::whereIn('id', $ids)->update([
            "title" => $request['title'],
            "remarks" => $request['remarks'],
            "referrer" => $request['referrer'],
            "release_flag" => $request['release_flag'],
            "edit_all" => $request['edit_all'],
            "repetition_type" => $request['repetition_type'],
            "updated_user" => Auth::id(),
            "user_id" => Auth::id(),
            "r_group_id" => $r_group_id,
            "zoom_url" => $zoom_values['zoom_url'],
            "zoom_id" => $zoom_values['zoom_id'],
            "zoom_pass" => $zoom_values['zoom_pass'],
            "zoom_account" => $zoom_values['zoom_account'],
            "zoom_account_pass" => $zoom_values['zoom_account_pass'],
            "created_at" => $has_prev_date ? $has_prev_date['created_at'] : now(),
            "created_user" => $has_prev_date ? $has_prev_date['created_user'] : Auth::id(),
        ]);

        $targetIds = $request['users'];
        $targetUsersMail = User::where('retire', 0)->whereNotNull('email')->whereIn('id', $targetIds)->where('id', '!=', Auth::id())->pluck('email')->toArray();
        $type = $has_prev_date ? '更新' : '作成';
        $c_records = CalendarRecord::whereIn('id', $ids)->get();
        $title = $c_records[0]['title'];
        $details = [];
        $recursion_types = ["1回のみ", "毎週", "毎月", "毎年"];
        foreach($c_records as $rec){            
            $d = [
                "id" => $rec['id'],
                "start_at" => Carbon::parse($rec['date_start'])->format('Y/m/d H:i'),
                "recursion" => $recursion_types[$rec['repetition_type']],
                "content" => $rec['remarks']
            ];
            $details[] = $d;
        }
        foreach($targetUsersMail as $to){
            Mail::to($to)->send(new Calendar( $details, $title, $type));
        }
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
        $from = $request['repeat_span']['monthly']['repeat_date_from'] . '00:00:00';
        $to = $request['repeat_span']['monthly']['repeat_date_to'] . '23:59:59';
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);
        if ($from->isAfter($to)) {
            $temp = $from;
            $from = $to;
            $to = $temp;
        }
        $selectedDay = $request['repeat_span']['monthly']['selected_day']; 
        $period = CarbonPeriod::create($from, '1 month', $to);
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
        $to = $request['repeat_span']['weekly']['repeat_date_to'] . '23:59:59';

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
    public function get_all_facilities(Request $request){
        $list = $this->avialable_items('all');
        return response()->json($list); 
    }
    private function avialable_items($type){
        
        $list = [
            'qualified_institution' => [
                [ 'label' =>  '本社会議室', 'value' =>  0, 'selected' => false ],
                [ 'label' =>  '本社休憩室', 'value' =>  1, 'selected' => false ],
                [ 'label' =>  '大阪会議室', 'value' =>  2, 'selected' => false ],
                [ 'label' =>  '東京会議室', 'value' =>  3, 'selected' => false ],
                [ 'label' =>  '仙台会議室', 'value' =>  4, 'selected' => false ],
                [ 'label' =>  '青森会議室', 'value' =>  5, 'selected' => false ]
            ],
            'zoom_value' => [
                [ 'label' => 'Zoom1', 'value' => 0, 'selected' => false ],
                [ 'label' => 'Zoom2', 'value' => 1, 'selected' => false ],
                [ 'label' => 'Zoom3', 'value' => 2, 'selected' => false ]
            ],
            'qualified_car' => [
                [ 'label' => '福岡582く5617 ホンダライフ', 'value' => 0, 'selected' => false ],
                [ 'label' => '福岡582え8686 ダイハツミラ', 'value' => 1, 'selected' => false ],
                [ 'label' => '福岡580と5654 オッティ', 'value' => 2, 'selected' => false ],
                [ 'label' => '福岡480わ3206 クリッパー', 'value' => 3, 'selected' => false ],
                [ 'label' => '福岡480ね5019 バン', 'value' => 4, 'selected' => false ],
                [ 'label' => '福岡480ね5020 バン', 'value' => 5, 'selected' => false ]
            ]
        ];
        if( $type == 'all' ){
            return $list;
        }else{
            return $list[$type];
        }
        
    }
    public function get_possible_facilities(Request $request){        

        $list = $this->avialable_items($request->target);
        
        $id_list = collect($list)->pluck('value')->toArray();
        
        $items = [];
        foreach($id_list as $id){
            $rec = [
                "time_start" => $request->time_start,
                "time_end" => $request->time_end,
                "once_date" => $request->once_date,
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
    public function get_my_groups(Request $request){
        $myGroupCheck = MyGroup::where('user_id', Auth::id())->exists();        
        if(!$myGroupCheck){            
            $newMyGroup = MyGroup::create([
                'user_id' => Auth::id(),
                'name' =>  Auth::user()->name . 'カレンダーグループ'
            ]);
            $newMyGroup->users()->syncWithPivotValues(Auth::id(), ['selected_as_calendar_member' => 1, "created_at" => now()]); 
        }

        $user = MyGroup::where('user_id', Auth::id())->latest()->first();
        $user_list = $user->users()->get();
        
        return response()->json($user_list); 
    }
    public function update_selected_calendar_members(Request $request){
        if($request->id == -1){
            $user = MyGroup::where('user_id', Auth::id())->latest()->first();
            $rec = $user->users()->update([
                'updated_at' => now(),
                'selected_as_calendar_member' => $request->value
            ]);
            return response()->json($rec);
        }else{
            $user = MyGroup::where('user_id', Auth::id())->latest()->first();
            $rec = $user->users()->where('user_id', $request->id)->update([
                'updated_at' => now(),
                'selected_as_calendar_member' => $request->value
            ]);
            return response()->json($rec); 
        }
        
    } 
    public function calendar_more_users(Request $request){
        $user = MyGroup::where('user_id', Auth::id())->latest()->first();
        $rec = $user->users()->pluck('id')->toArray();        
        $close_users = User::whereIn('id', $rec)->where('retire', 0)->where('deleted_flag', 0)->where('id', '>', 105)->select('id', 'name', 'icon_id')->get();
        $other_users = User::whereNotIn('id', $rec)->where('retire', 0)->where('deleted_flag', 0)->where('id', '>', 105)->select('id', 'name', 'icon_id')->get();
        $merged_users = $close_users->concat($other_users)->toArray();
        return response()->json($merged_users); 
    }
    public function set_more_members(Request $request){
        $user = MyGroup::where('user_id', Auth::id())->latest()->first();
        $user->users()->syncWithPivotValues($request->users, ['selected_as_calendar_member' => 1, "created_at" => now()]);  
        return response()->json($request->users); 
    }
    public function get_calendar_search(Request $request){
        $gr = MyGroup::where('user_id', Auth::id())->latest()->first();
        $list = $gr->selected_users()->pluck('id')->toArray();
        $key = $request->key;

        $records = CalendarRecord::where(DB::raw("CONCAT_WS('', title, ' ', remarks, ' ', referrer)"), 'LIKE', '%' . $key . '%')
        ->whereHas('calendar_users', function ($query) use ($list) {
            $query->whereIn('users.id', $list);
        })
        ->where(function ($query) {
            $query->where('release_flag', 0)
            ->orWhereHas('calendar_users', function ($query) {
                $query->whereIn('users.id', [Auth::id()]);
            });
        })
        ->select('id', 'title', 'remarks', 'referrer', 'date_start', 'date_end', 'zoom_value', 'qualified_institution', 'qualified_car')
        ->orderBy('date_start', 'desc')
        ->get();
        return response()->json($records); 
    }
    public function calendar_drop(Request $request){
        $record = CalendarRecord::findOrFail($request->id);
        $start = Carbon::parse($record->date_start);
        $end = Carbon::parse($record->date_end);
        $record_duration = $start->diff($end);
        $record_duration_minute = ($record_duration->h * 60 + $record_duration->i);

        $new_start = Carbon::parse($request->date);
        $end_day = Carbon::now()->endOfDay();
        $new_diff = $new_start->diff($end_day);
        $new_diff_minute = ($new_diff->h * 60 + $new_diff->i);

        $new_end_time = $new_start->copy()->add($record_duration_minute, 'minutes');
        if($new_diff_minute < $record_duration_minute){
            $new_end_time = Carbon::createFromFormat('Y-m-d H:i:s', $end_day);
        }

        $new_start_time = Carbon::createFromFormat('Y-m-d H:i:s', $new_start);
        

        if($record->repetition_flag == 0 && $record->zoom_value !== null){
            $instance_start = Carbon::parse($new_start_time);
            $instance_end = Carbon::parse($new_end_time);
            $index = $record->zoom_value;
            $token = $this->zoomToken($index);
            $s_date = $instance_start->copy();
            $e_date = $instance_end->copy();
            $day = $s_date->format('Y-m-d H:i:s');
            $meetings = $this->today_meetings($index, $token, $day);

            $s1 = $s_date->copy()->format('Y-m-d H:i:s');
            $s2 = $e_date->copy()->format('Y-m-d H:i:s');

            $check_overlap = $this->check_meeting_overlap($meetings, $s1, $s2, $record->zoom_id);
            
            if($check_overlap == 'ok'){
                
                $start = Carbon::parse($s1);
                $end = Carbon::parse($s2);
                $record_duration = $start->diff($end);
                $record_duration_minute = ($record_duration->h * 60 + $record_duration->i);
                $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $s1);
                $carbonDate->setTimezone('UTC');
                $formattedDate = $carbonDate->format('Y-m-d\TH:i:s\Z');
                $params = [
                    "meetingId" => $record->zoom_id,
                    "token" => $token,
                    "duration" => $record_duration_minute,
                    "start_time" => $formattedDate,    
                    "zoom_id" => $record->zoom_value      
                    
                ];
                $json_result = $this->update_zoom_meeting($params);
                // return response()->json($json_result); 
                // return $json_result;
                // return response()->json('hehe'); 
                // return response()->json($json_result); 
                // $z_index = (int) $request['facility']['zoom_value'] + 1; 

                // $zoom_values = array(
                //     "zoom_url" => $json_result['join_url'],
                //     "zoom_id" => $json_result['id'],
                //     "zoom_pass" => $json_result['password'],
                //     "zoom_account" => 'zoom'.$z_index.'@glowd.co.jp',
                //     "zoom_account_pass" => 'Glowd0802'
                // );
            }else{
                throw ValidationException::withMessages(['message' => '他のWEBミーティングにかぶっています。', "val" => $check_overlap]);
            }
        }
        $record->update([
            "date_start" => $new_start_time,
            "date_end" => $new_end_time,
            "updated_user" => Auth::id()
        ]);
        $res = CalendarRecord::where('id', $record->id)
        ->with('calendar_users')
        ->with('updated_by')
        ->with('files')
        ->with('created_by')
        ->first();
        return response()->json($res); 
    }
    public function export_ical(Request $request){

        $id = $request->id;
        $token = $request->token;
        $user = User::where('id', $request->id)->where('ical_key', $token)->first();
        if(!$user){
            abort(404);
        }

        $content = <<<EOD
        BEGIN:VCALENDAR
        PRODID:-//" . $user->email . "//CLAP 1.0//EN
        VERSION:2.0
        CALSCALE:GREGORIAN
        METHOD:PUBLISH
        X-WR-CALNAME:CLAP:カレンダー
        X-WR-TIMEZONE:Asia/Tokyo
        BEGIN:VTIMEZONE
        TZID:Asia/Tokyo
        X-LIC-LOCATION:Asia/Tokyo
        BEGIN:STANDARD
        TZOFFSETFROM:+0900
        TZOFFSETTO:+0900
        TZNAME:JST
        DTSTART:19700101T000000
        END:STANDARD
        END:VTIMEZONE
        EOD;
        $events = CalendarRecord::whereHas('calendar_users', function ($query) use($user){
            $query->where('users.id', $user->id);
        })
        ->orderBy('date_start', 'asc')->get();
        $now = Carbon::now();
        foreach($events as $event){
            $start = Carbon::parse($event->date_start)->format('Ymd\THis');
            $end = Carbon::parse($event->date_end)->format('Ymd\THis');
            $now = Carbon::now()->format('Ymd\THis');
            $uid = $event->id . "/" . $user->email;
            $array = array( ' ', '　', "\r\n", "\r", "\n", "\t" );
            $desc = str_replace($array, '', $event->remarks . $event->referrer);
            $content = <<<EOD
            $content
            BEGIN:VEVENT
            DTSTART:$start
            DTEND:$end
            DTSTAMP:$now
            UID:$uid
            CREATED:$now
            DESCRIPTION:$desc
            URL:$event->referrer
            LAST-MODIFIED:$now
            SEQUENCE:0
            STATUS:CONFIRMED
            SUMMARY:$event->title
            TRANSP:OPAQUE
            END:VEVENT
            EOD;
        }
        $content = <<<EOD
        $content
        END:VCALENDAR
        EOD;
        $headers = [
            'Content-type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename='.$user->id.'.ics',
        ];
        return response($content, 200, $headers);
        // Storage::disk('local')->put('ical/test.ics',  $content);
        // echo($content);
    }
    public function ical_url_generate(){ 
        $key = $u_id = uniqid();
        $update = Auth::user()->update([
            "ical_key" => $key
        ]);
        $res = [
            "success" => $update,
            "key" => $key,
            "url" => url('/export_ical?id='.Auth::id().'&token='.$key)
        ];

        return response()->json($res); 

        
    }

    
}
