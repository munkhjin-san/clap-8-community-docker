<?php

namespace App\Http\Controllers;

use App\Jobs\BumpCalendarFavourites;
use App\Jobs\GenerateTranscriptAiSummary;
use App\Jobs\SendCalendarMails;
use App\Models\CalendarMeetingSummary;
use App\Models\CalendarMeetingTranscript;
use App\Models\CalendarMeetingTranscriptSummary;
use App\Models\CalendarFacility;
use App\Models\ProjectRecord;
use App\Models\ZoomAccount;
use App\Services\ZoomApiService;
use App\Services\ZoomVttParser;
use Illuminate\Http\Request;
use App\Models\CalendarRecord;
use App\Models\CalendarGroup;
use App\Models\User;
use App\Models\CalendarExtraUser;
use App\Models\MyGroup;
use App\Models\MyWorkGroup;
use App\Models\boardRecord;
use App\Models\workGroup;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\GoogleController;
class CalendarController extends Controller
{

    protected $googleController;
    public function __construct(
        GoogleController $googleController,
        private readonly ZoomApiService $zoomApi,
        private readonly ZoomVttParser $zoomVttParser,
    ) {
        $this->googleController = $googleController;
    }
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    private function authorizeMeetingRecordView(CalendarRecord $record): User
    {
        $activeUser = $this->active_user();
        $members = $record->calendar_users()->pluck('id')->toArray();
        $viewUsers = $record->calendar_view_users()->pluck('id')->toArray();
        $override = [608, 610];
        $allowedUsers = array_merge($members, $viewUsers, $override);

        if (! in_array($activeUser->id, $allowedUsers)) {
            throw ValidationException::withMessages(['message' => '閲覧権限がありません。']);
        }

        return $activeUser;
    }
    private function zoom_account($index){
        $account = $this->zoomApi->accountForSlot((int) $index);

        return [
            'model' => $account,
            'accountMail' => $account->host_email,
        ];
    }
    public function zoomToken($zoomValue){
        $account = $this->zoom_account($zoomValue);

        return $this->zoomApi->accessToken($account['model']);
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
                    $end_at_mt = $instance->copy()->add('minutes', $meeting['duration'], );
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
    /**
     * ゲストがホストを待たずに入室できる設定。待機室はUIから外したので常にこれを使う。
     * jbh_time は 0 = 開始時刻前でもいつでも入室可（Zoom APIは 0 / 5 / 10 のみ）。
     */
    private function zoom_open_join_settings(): array
    {
        return [
            'waiting_room' => false,
            'join_before_host' => true,
            'jbh_time' => 0,
        ];
    }
    private function update_zoom_meeting($params){
        $account = $this->zoom_account($params['zoom_id']); 
        $meetings_url = 'https://api.zoom.us/v2/meetings' . '/' . $params['meetingId'];
        $settings = array(
            'use_pmi' => false,
            'auto_start_meeting_summary' => $params['auto_start_meeting_summary'],
            'auto_start_ai_companion_questions' => false
        );
        // 待機室は常に無効。ホスト不在でもゲストがいつでも入室できるようにする
        $settings = array_merge($settings, $this->zoom_open_join_settings());
        $data_to_zoom_api = array(
            'meetingId' => $params['meetingId'],
            'duration' => $params['duration'],
            'start_time' => $params['start_time'],
            'timezone' => 'Asia/Tokyo',
            'type' => $params['type'] ? $params['type'] : 2,
            'topic' => $params['title'],
            'settings' => $settings
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
            'use_pmi' => false,
            'auto_start_meeting_summary' => $params['auto_start_meeting_summary'],
        );
        // 待機室は常に無効。ホスト不在でもゲストがいつでも入室できるようにする
        $settings = array_merge($settings, $this->zoom_open_join_settings());
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
    public function genertate_my_groups(){
        $groups = CalendarGroup::where('deleted_flag', 0)->whereNotNull('member_list')->get();
        foreach($groups as $group){
            echo($group->member_list);
            $list = explode(',', $group->member_list);
            $myGroupCheck = MyGroup::where('user_id', $group->user_id)->exists();
            if(!empty($list) && $myGroupCheck){

                $userExist = User::pluck('id')->toArray();
                $modelCollection = collect($userExist);
                    $gr = MyGroup::where('user_id', $group->user_id)->latest()->first();
                    
                    $filteredSecondArray = collect($list)->filter(function ($item) use ($modelCollection) {
                        return $modelCollection->contains($item);
                    })->toArray();
                    // $message->uncheckedUsers()->sync($filteredSecondArray);                
                              
                    $gr->users()->syncWithPivotValues($filteredSecondArray, ['selected_as_calendar_member' => 1, "created_at" => now()]); 
                // foreach($list as $selected_user_id){
                //     $exists = User::where('id', $selected_user_id)->exists();
                //     if($exists){
                //         $myGroupCheck = MyGroup::where('user_id', $group->user_id)->exists();
                //         if($myGroupCheck){
                //             $gr = MyGroup::where('user_id', $group->user_id)->latest()->first();
                //             $my_group_user

                //         }

                //     }
                // }
            }
            // echo('<br>');
        }
    }
    public function get_calendar_data(Request $request){    
        
        
        $validatedData = $request->validate([
            'day' => 'required',
        ]);
        $active_user = $this->active_user();
        $this->ensure_active_my_group($active_user);

        $gr = $this->my_groups_query($active_user)
                    ->where('selected', true)
                    ->with('selected_users')
                    ->get()
                    ->pluck('selected_users.*.id')
                    ->flatten()
                    ->toArray();
        // $myWorkGroups = MyWorkGroup::where('user_id', $this->active_user()->id)->pluck('work_group_id')->toArray();

        $work_group_users_id = [];
        
        $my_group_ids = $gr ?? [];

        // 保存済みの追加ユーザーとリクエスト分を合わせる。
        // （トグル直後の再取得がPOST完了前に走っても表示が欠けないように両方見る）
        $requested_extras = is_array($request->extra_users) ? array_map('intval', $request->extra_users) : [];
        $extra_users = array_unique(array_merge($this->calendar_extra_user_ids($active_user), $requested_extras));

        $list = array_merge($my_group_ids, $work_group_users_id, $extra_users);
        $date = $request["day"];

        $carbonDate = Carbon::parse($date);
        $startOfMonth = $carbonDate->copy()->startOfMonth();
        $previousMonday = $startOfMonth->startOfWeek()->startOfWeek();
        
        $endOfMonth = $carbonDate->copy()->endOfMonth();
        $nextSunday = $endOfMonth->endOfWeek()->addWeek()->endOfWeek();
        $year = $carbonDate->year;
        $month = $carbonDate->month;
        $facility_check = !empty($request->facilities);
        $department_check = !empty($request->departments);
        $facilities = $request->facilities;
        $departments = $request->departments;
        $records = CalendarRecord::query();
        $filter = $records
        ->where(function ($query) use ($facility_check, $facilities, $department_check, $departments, $list) {
            if ($facility_check || $department_check) {
                $query->where(function ($query) use ($facilities, $facility_check, $department_check, $departments, $list) {
                    if ($facility_check) {
                        foreach ($facilities as $index => $value) {
                            $query->orWhereIn($index, $value)
                                ->orWhereHas('calendar_users', function ($query) use ($list) {
                                    $query->whereIn('user_id', $list);
                                });
                        }
                    }
                    if ($department_check) {
                        foreach ($departments as $value) {
                            $query->orWhere('department_id', $value)
                                ->orWhereHas('calendar_users', function ($query) use ($list) {
                                    $query->whereIn('user_id', $list);
                                });
                        }
                    }
                });
            } else {
                $query->whereHas('calendar_users', function ($query) use ($list) {
                    $query->whereIn('user_id', $list);
                });
            }
        })
        ->whereBetween('date_start', [$previousMonday, $nextSunday])
        ->with([
            'calendar_users',
            'department',
            'task',
            'updated_by',
            'created_by',
            'files',
            'calendar_view_users',
   
        ])
        ->withCount([
            'summaries',
            'transcripts as transcripts_count' => fn ($query) => $query
                ->where('status', CalendarMeetingTranscript::STATUS_DOWNLOADED),
        ])
        ->get();


        
        


        return response()->json($filter);
        
    }
    public function calendar_add_temp_record(Request $request){
        $request->validate([
            'dateData' => 'required|array',
        ]);
        $mainData = $request->mainData;
        $dateData = $request->dateData ?? [];
        $temp_unique_id = uniqid();
        foreach($dateData as $date){
            $once_date = $date['once_date'];
            [ $startHour, $startMinute ] = $date['time_start'] ? explode(':', $date['time_start']) : [0, 0];
            [ $endHour, $endMinute ] = $date['time_end'] ? explode(':', $date['time_end']) : [0, 0];
            $startHourWithSeconds = "{$startHour}:{$startMinute}:00";
            $endHourWithSeconds = "{$endHour}:{$endMinute}:00";

            $data = $mainData;
            $data['once_date'] = $once_date;
            $data['time_start'] = $startHourWithSeconds;
            $data['time_end'] = $endHourWithSeconds;
            $data['repetition_type'] = 0; // NO_REPEAT
            $data['temp_flag'] = 1;
            $data['temp_unique_id'] = $temp_unique_id;

            $newRequest = new Request($data);
            $create = $this->calendar_add_record($newRequest);

        }
        return response('Temporary records created successfully.', 201);
    }
    public function calendar_add_record(Request $request){



        
      
        $data = $request;
        $has_prev_date = null;
        if($request->editId){
            $target = CalendarRecord::findOrFail($request->editId);
            $facility_check = $this->facility_validate($request, true);
            $has_prev_date = $target;
            if($request->edit_repeat){
                
                $all_recs = CalendarRecord::whereNotNull('r_group_id')->where('r_group_id', $target->r_group_id)->delete();
                // if($target['zoom_value'] && $target['zoom_id']){
                //     $token = $this->zoomToken($target['zoom_value']);
                //     $params = [
                //         "zoom_id" => $target['zoom_value'],
                //         "meetingId" => $target['zoom_id'],
                //         "token" => $token
                //     ];
                //     $delete_zoom = $this->delete_zoom_meeting($params);
                // }
                $target->delete();
                $data['editId'] = null;
            }else{
                $record = $this->edit_single_record($request, $target,true);
                return response()->json('gggg');
            }
            
        }else{
            $facility_check = $this->facility_validate($request, true);
        }
        
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
            $r_group_id = $request['repeat_span']['weekly']['repeat_date_from'] . $this->active_user()->id . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $data, $r_group_id, $has_prev_date);
            return response()->json($record_ids);
        }
        // MONTHLY_REPEAT
        if($request->repetition_type == 2){
            $record_ids = $this->execute_monthly_record($data, [], true);
            $r_group_id = $request['repeat_span']['monthly']['repeat_date_from'] . $this->active_user()->id . uniqid();
            $update_main_values = $this->execute_main_data($record_ids, $data, $r_group_id, $has_prev_date);
            return response()->json($record_ids);
        }
        // YEARLY_REPEAT
        if($request->repetition_type == 3){
            $record_ids = $this->execute_yearly_record($data, [], true);
            $r_group_id = $request['repeat_span']['yearly']['year_from'] . $request['repeat_span']['yearly']['selected_month'] . $this->active_user()->id . uniqid();
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
                if($record->zoom_id){
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
            if($record->zoom_id){
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
                $inst = $this->check_duplicate_facility($index, $value, $date_start_ready, $date_end_ready, true, [$request['editId']]);
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
            "members_only" => $request['members_only'],
            "updated_user" => $this->active_user()->id,
            "date_start" => $date_start_ready,
            "date_end" => $date_end_ready,
            "department_id" => $request['department_id']
        ]);

        $new_record->save();
        $new_record->calendar_users()->syncWithPivotValues($request['users'], ["created_at" => now(),"updated_at" => now()]);
        $new_record->calendar_view_users()->sync($request['view_users']);
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
        // if($record['zoom_id']){
        //     $token = $this->zoomToken($record['zoom_value']);
        //     $params = [
        //         "zoom_id" => $record['zoom_value'],
        //         "meetingId" => $record['zoom_id'],
        //         "token" => $token
        //     ];
        //     $this->delete_zoom_meeting($params);
        // }
        $ids[] = $new_record->id;            
        $this->execute_main_data($ids, $request, null, $record, true);
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
    private function check_duplicate_facility($index, $value, $start, $end, $throw, $exclude){
        $exists = CalendarRecord::where($index, $value)->whereNotIn('id', $exclude)
        ->where(function ($query) use ($start, $end) {
            $query->where(function ($subquery) use ($start, $end) {
                $subquery->where('date_start', '<', $end)
                         ->where('date_end', '>', $start);
            })->orWhere(function ($subquery) use ($start, $end) {
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
            $exclude = [];
            if($request['editId']){
                $check = CalendarRecord::findOrFail($request['editId']);                
                if($check && $check->r_group_id && $request['edit_repeat']){
                    $repeats = CalendarRecord::where('r_group_id', $check->r_group_id)->pluck('id')->toArray();
                    $exclude = $repeats;
                }
                array_push($exclude, $check->id);
            }
            $date_start_ready = $this->time_parser($instance, $request['time_start']);
            $date_end_ready = $this->time_parser($instance, $request['time_end']);
            $inst = $this->check_duplicate_facility($validate_index, $request['facility'][$validate_index], $date_start_ready, $date_end_ready, $throw, $exclude);
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
        $record->calendar_users()->syncWithPivotValues($request['users'], ["created_at" => now(), "updated_at" => now()]);
        $record->calendar_view_users()->sync($request['view_users']);
        $record->files()->syncWithPivotValues($request['file_ids'], ["created_at" => now(), "updated_at" => now()]);
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
    private function execute_main_data($ids, $request, $r_group_id, $has_prev_date, $force_create = false){

        
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
            // $meetings = $request['repetition_type'] == 0 ? $this->today_meetings($index, $token, $day) : [];

            $s1 = $s_date->copy()->format('Y-m-d H:i:s');
            $s2 = $e_date->copy()->format('Y-m-d H:i:s');

            // $check_overlap = $request['repetition_type'] == 0 ? $this->check_meeting_overlap($meetings, $s1, $s2, $has_prev_date['zoom_id']) : 'ok';
            // if($check_overlap == 'ok'){


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
                    "auto_start_meeting_summary" => $request['zoom_ai_companion'],
                    "zoom_id" => $request['facility']['zoom_value'],
                    "type" => $request['repetition_type'] == 0 ? 2 : 3            
                    
                ];
                $zoom_account = $this->zoom_account((int) $request['facility']['zoom_value']);
                if($has_prev_date && $has_prev_date['zoom_id'] && !$force_create){
                    $params['meetingId'] = $has_prev_date['zoom_id'];
                    $json_result = $this->update_zoom_meeting($params);
                    
                    $zoom_values = [
                        "zoom_url" => $has_prev_date['zoom_url'],
                        "zoom_id" => $has_prev_date['zoom_id'],
                        "zoom_pass" => $has_prev_date['zoom_pass'],
                        "zoom_account" => $zoom_account['accountMail'],
                        "zoom_account_pass" => null
                    ];
                }else{
                    $json_result = $this->create_zoom_meeting($params);
                    $zoom_values = [
                        "zoom_url" => $json_result['join_url'],
                        "zoom_id" => $json_result['id'],
                        "zoom_pass" => $json_result['password'],
                        "zoom_account" => $zoom_account['accountMail'],
                        "zoom_account_pass" => null
                    ];
                }                

               
                
                
                
                

            // }
        }
        $active_user = $this->active_user();
        $records = CalendarRecord::whereIn('id', $ids)->update([
            "title" => $request['title'],
            "temp_flag" => $request['temp_flag'] ?? 0,
            "temp_unique_id" => $request['temp_unique_id'] ?? null,
            "remarks" => $request['remarks'],
            "referrer" => $request['referrer'],
            "release_flag" => $request['release_flag'],
            "edit_all" => $request['edit_all'],
            "members_only" => $request['members_only'],
            "repetition_type" => $request['repetition_type'],
            "department_id" => $request['department_id'],
            "updated_user" => $active_user->id,
            "user_id" => $active_user->id,
            "r_group_id" => $r_group_id,
            "zoom_url" => $zoom_values['zoom_url'],
            "zoom_id" => $zoom_values['zoom_id'],
            "zoom_pass" => $zoom_values['zoom_pass'],
            "zoom_account" => $zoom_values['zoom_account'],
            "zoom_account_pass" => $zoom_values['zoom_account_pass'],
            "created_at" => $has_prev_date ? $has_prev_date['created_at'] : now(),
            "created_user" => $has_prev_date ? $has_prev_date['created_user'] : $active_user->id,
            "descendant_of" => $has_prev_date ? $has_prev_date['id'] : null,
            "zoom_waiting_room" => false,
            "zoom_ai_companion" => $request['zoom_ai_companion'],
            "real_created_at" => now()
        ]);

        if($request['facility']['zoom_value'] !== null && $zoom_values['zoom_url'] == null){
            throw ValidationException::withMessages(['message' => 'zoom予約に失敗しました。']);
        }

        $targetIds = is_array($request['users']) ? array_map('intval', $request['users']) : [];

        // 通知メールと並び順スコアはどちらも予定の保存には関係しないので、
        // レスポンスを返した後に実行する（失敗してもログだけで main flow は通す）
        SendCalendarMails::dispatchAfterResponse(
            array_map('intval', $ids),
            $targetIds,
            (int) $active_user->id,
            $has_prev_date ? '更新' : '作成'
        );

        // 新規作成のときだけ「よく一緒の人」スコアを進める（編集・削除は追わない）
        if(!$has_prev_date){
            BumpCalendarFavourites::dispatchAfterResponse($targetIds);
        }

        return $records;
    }
    private function time_parser($instance, $time){               
        list($hour, $minute) = explode(':', $time);
        $hour = (int) $hour;
        $minute = (int) $minute;

        $combined = $instance->hour($hour)->minute($minute)->second(0);
        $cooked = $combined->format('Y-m-d H:i:s');
        $cooked = Carbon::createFromFormat('Y-m-d H:i:s', $cooked);
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
            
            $startOfWeek = $to->copy()->startOfWeek()->addDays($dayOfWeek);
            $endOfWeek = $from->copy()->startOfWeek()->addDays($dayOfWeek);
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
    public function all_facility_items(Request $request){
        $list = $this->avialable_items('all');
        return response()->json($list); 
    }
    private function avialable_items($type){
        $roomItems = CalendarFacility::query()
            ->where('type', CalendarFacility::TYPE_ROOM)
            ->orderBy('slot')
            ->get()
            ->map(fn (CalendarFacility $facility) => $facility->calendarOption())
            ->all();
        $carItems = CalendarFacility::query()
            ->where('type', CalendarFacility::TYPE_CAR)
            ->orderBy('slot')
            ->get()
            ->map(fn (CalendarFacility $facility) => $facility->calendarOption())
            ->all();
        $zoomItems = ZoomAccount::query()
            ->orderBy('slot')
            ->get()
            ->map(fn (ZoomAccount $account) => $account->calendarOption())
            ->all();

        $list = [
            'qualified_institution' => $roomItems,
            'zoom_value' => $zoomItems,
            'qualified_car' => $carItems,
        ];
        if( $type == 'all' ){
            return $list;
        }else{
            return $list[$type];
        }
        
    }
    public function get_possible_facilities(Request $request){        

        $list = $this->avialable_items($request->target);
        
        $items = [];
        foreach($list as $facilityItem){
            $rec = [
                "editId" => $request->editId,
                "edit_repeat" => $request->edit_repeat,
                "time_start" => $request->time_start,
                "time_end" => $request->time_end,
                "once_date" => $request->once_date,
                "repetition_type" => $request->repeat,
                "repeat_span" => $request->repeat_span,
                "facility" => [$request->target => $facilityItem['value']]
            ];
            $facility_check = $this->facility_validate($rec, false);
            $isAvailable = !$facility_check && $facilityItem['selectable'];
            $item = [
                "label" => $facilityItem['label'],
                "id" => (string) $facilityItem['value'],
                "availablity" => $isAvailable,
                "unavailable_reason" => $isAvailable
                    ? null
                    : ($facilityItem['selectable'] ? '予約済み' : '利用停止中'),
            ];
            $items[] = $item;

        }

        return response()->json($items);       
    }
    private function my_groups_query($active_user){
        return MyGroup::where('user_id', $active_user->id)->where('deleted_flag', 0);
    }
    /**
     * カレンダーのマイグループは常に1つだけが選択中。
     * グループが無ければ既定グループを作り、選択中が0件/2件以上なら1件に正す。
     */
    private function ensure_active_my_group($active_user){
        $groups = $this->my_groups_query($active_user)->orderBy('id')->get();

        if($groups->isEmpty()){
            $newMyGroup = MyGroup::create([
                'user_id' => $active_user->id,
                'name' =>  'マイグループ',
                'selected' => true
            ]);
            $newMyGroup->users()->attach([$active_user->id], ['selected_as_calendar_member' => 1, 'created_at' => now(), 'updated_at' => now()]);
            return $newMyGroup;
        }

        $selected = $groups->where('selected', 1);

        // 既に1件だけ選択されていれば何も書き換えない（読み取り系から呼ばれても副作用なし）
        if($selected->count() === 1){
            return $selected->first();
        }

        // 0件/2件以上のときだけ修復する。updated_at が新しい方＝ユーザーが最後に選んだ物を残すので、
        // 同時リクエストが選択を書き換えても、それを id 順で踏み潰さない。
        $candidates = $selected->isNotEmpty() ? $selected : $groups;
        $active = $candidates->sortByDesc(fn($group) => $group->updated_at)->first();

        $this->my_groups_query($active_user)->whereNot('id', $active->id)->update(['selected' => false]);
        if(!$active->selected){
            $active->selected = true;
            $active->save();
        }

        return $active;
    }
    public function get_my_groups(Request $request){
        $active_user = $this->active_user();
        $this->ensure_active_my_group($active_user);

        // よく一緒に予定を入れる人を先に出す（全ユーザータブが数百件あって探しづらいため）
        $members = User::where('users.retire', 0)
                        ->where('users.deleted_flag', 0)
                        ->where('users.id', '>', 105)
                        ->leftJoin('calendar_favourite_users as fav', function($join) use ($active_user) {
                            $join->on('fav.member_id', '=', 'users.id')
                                 ->where('fav.owner_id', '=', $active_user->id);
                        })
                        ->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg')
                        ->orderByDesc('fav.score')
                        // スコアが無い人は社員→協力会社の順、その中は名前順にする
                        ->orderBy('users.partner_flag')
                        ->orderBy('users.name')
                        ->get();
        $groups = $this->my_groups_query($active_user)->orderBy('id')->with('users')->get();
        $res = [
            "my_groups" => $groups,
            "all_members" => $members,
            "extra_users" => $this->calendar_extra_user_ids($active_user),
        ];

        return response()->json($res);
    }
    private function calendar_extra_user_ids($active_user){
        return CalendarExtraUser::where('user_id', $active_user->id)
                ->pluck('member_id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();
    }
    /**
     * マイグループ外から一時的に足した表示メンバーの追加・削除。
     * member_id = -1 かつ value=false で全解除。
     */
    public function update_calendar_extra_users(Request $request){
        $active_user = $this->active_user();
        $add = filter_var($request->value, FILTER_VALIDATE_BOOLEAN);
        $member_id = (int) $request->member_id;

        if($member_id === -1){
            if(!$add){
                CalendarExtraUser::where('user_id', $active_user->id)->delete();
            }
            return response()->json($this->calendar_extra_user_ids($active_user));
        }

        if($add){
            $exists = User::where('id', $member_id)->where('retire', 0)->where('deleted_flag', 0)->exists();
            if(!$exists){
                return response()->json(['message' => '対象のユーザーが見つかりません。'], 422);
            }
            CalendarExtraUser::firstOrCreate([
                'user_id' => $active_user->id,
                'member_id' => $member_id
            ]);
        }else{
            CalendarExtraUser::where('user_id', $active_user->id)->where('member_id', $member_id)->delete();
        }

        return response()->json($this->calendar_extra_user_ids($active_user));
    }
    public function select_my_group(Request $request){
        $active_user = $this->active_user();
        $group = $this->my_groups_query($active_user)->findOrFail($request->id);

        $this->my_groups_query($active_user)->update(['selected' => false]);
        $group->selected = true;
        $group->save();

        return response()->json($group);
    }
    public function select_work_group(Request $request){
        $active_user = $this->active_user();
        $my_work_groups = MyWorkGroup::where('user_id', $active_user->id)->delete();
        $create = MyWorkGroup::create([
            'user_id' => $active_user->id,
            'work_group_id' => $request->work_group_id
        ]);
        $groups = MyGroup::where('user_id', $active_user->id)->update(['selected' => false]);
        return response()->json($create); 
    }
    public function update_selected_calendar_members(Request $request){
        $active_user = $this->active_user();
        $group = $this->my_groups_query($active_user)->findOrFail($request->group_id);
        $value = filter_var($request->value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $pivots = DB::table('my_group_users')
                    ->where('record_id', $group->id)
                    ->whereNull('deleted_at');

        // user_id = -1 はグループ全員が対象
        if($request->user_id != -1){
            $pivots->where('user_id', (int) $request->user_id);
        }

        $rec = $pivots->update([
            'selected_as_calendar_member' => $value,
            'updated_at' => now()
        ]);

        return response()->json($rec);
    }
    public function delete_my_group(Request $request){
        $active_user = $this->active_user();
        $group = $this->my_groups_query($active_user)->findOrFail($request->id);
        $was_selected = (bool) $group->selected;

        $group->users()->detach();
        $group->deleted_flag = 1;
        $group->save();
        $group->delete();

        // 選択中グループを消した場合は別のグループへ選択を移す（0件なら既定グループを再作成）
        if($was_selected){
            $this->ensure_active_my_group($active_user);
        }

        return response()->json($group);
    }
    public function calendar_more_users(Request $request){
        $active_user = $this->active_user();
        $user = $this->my_groups_query($active_user)->where('selected', 1)->first()
                ?? $this->my_groups_query($active_user)->latest()->first();
        $rec = $user ? $user->users()->pluck('id')->toArray() : [];
        $close_users = User::whereIn('id', $rec)->where('retire', 0)->where('deleted_flag', 0)->where('id', '>', 105)->select('id', 'name', 'icon_path', 'icon_bg')->get();
        $other_users = User::whereNotIn('id', $rec)->where('retire', 0)->where('deleted_flag', 0)->where('id', '>', 105)->select('id', 'name', 'icon_path', 'icon_bg')->get();
        $merged_users = $close_users->concat($other_users)->toArray();
        return response()->json($merged_users); 
    }
    public function set_more_members(Request $request){
        $active_user = $this->active_user();
        if($request->id){
            $group = $this->my_groups_query($active_user)->findOrFail($request->id);
        }else{
            $group = new MyGroup;
            $group->user_id = $active_user->id;
        }
        $group->selected = true;
        $group->name = $request->title;
        $group->save();

        $requested = collect($request->users ?? [])->map(fn($id) => (int) $id)->unique()->values();
        $current = collect(
                        DB::table('my_group_users')
                            ->where('record_id', $group->id)
                            ->whereNull('deleted_at')
                            ->pluck('selected_as_calendar_member', 'user_id')
                    )->mapWithKeys(fn($selected, $user_id) => [(int) $user_id => (int) $selected]);

        $removed = $current->keys()->reject(fn($id) => $requested->contains($id))->values();
        $added   = $requested->reject(fn($id) => $current->has($id))->values();

        if($removed->isNotEmpty()){
            $group->users()->detach($removed->all());
        }
        // 新しく追加したメンバーだけ表示ONで始める。
        // 既存メンバーには触らないので、個別に外した表示設定はそのまま残る。
        if($added->isNotEmpty()){
            $group->users()->attach($added->all(), ['selected_as_calendar_member' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->my_groups_query($active_user)->whereNot('id', $group->id)->update(['selected' => false]);

        return response()->json($group->load('users'));
    }
    public function get_calendar_search(Request $request){
        $active_user = $this->active_user();
        $gr_list = MyGroup::where('user_id', $active_user->id)->where('selected', 1)->with('selected_users')->get();
        $all_ids = $gr_list->pluck('selected_users')->flatten()->pluck('id')->toArray();
        $list = array_unique($all_ids);
        $key = $request->key;
        $records = CalendarRecord::whereRaw("CONCAT_WS('', title, ' ', remarks, ' ', referrer) LIKE ?", ['%' . $key . '%'])
        ->whereHas('calendar_users', function ($query) use ($list) {
            $query->whereIn('users.id', $list);
        })
        ->where(function ($query) use($active_user) {
            $query->where('release_flag', 0)
            ->orWhereHas('calendar_users', function ($query) use($active_user){
                $query->whereIn('users.id', [$active_user->id]);
            });
        })
        ->select('id', 'title', 'remarks', 'referrer', 'date_start', 'date_end', 'zoom_value', 'qualified_institution', 'qualified_car')
        ->with('calendar_users')
        ->orderBy('date_start', 'desc')
        ->get();
        return response()->json($records); 
    }
    public function calendar_drop(Request $request){
        $record = CalendarRecord::findOrFail($request->id);
        if($record->qualified_zoom !== null || $record->zoom_value !== null || $record->qualified_institution !== null || $record->qualified_car !== null){
            throw ValidationException::withMessages(['message' => '施設予約のスケジュールはドラッグアンドドロップで移動できません。編集画面で編集してください。']);
        } 
        $start = Carbon::parse($record->date_start);
        $end = Carbon::parse($record->date_end);
        $record_duration = $start->diff($end);
        $record_duration_minute = ($record_duration->h * 60 + $record_duration->i);

        $new_start = Carbon::parse($request->date);
        $end_day = Carbon::parse($request->date)->endOfDay();
        $new_diff = $new_start->diff($end_day);
        $new_diff_minute = ($new_diff->h * 60 + $new_diff->i);

        $new_end_time = $new_start->copy()->add( 'minutes', $record_duration_minute,);
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
                    "zoom_id" => $record->zoom_value,
                    "title" => $record['title'],
                    "auto_start_meeting_summary" => $request['zoom_ai_companion'],
                    
                ];
                $json_result = $this->update_zoom_meeting($params);
                // return response()->json($json_result); 
                // return $json_result;
                // return response()->json('hehe'); 
                // return response()->json($json_result); 
            }else{
                throw ValidationException::withMessages(['message' => '他のWEBミーティングにかぶっています。', "val" => $check_overlap]);
            }
        }
        $record->update([
            "date_start" => $new_start_time,
            "date_end" => $new_end_time,
            "updated_user" => $this->active_user()->id
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
        PRODID:-//" . $user->email . "//GLOWD 1.0//EN
        VERSION:2.0
        CALSCALE:GREGORIAN
        METHOD:PUBLISH
        X-WR-CALNAME:GLOWD:スケジュール
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
            "url" => url('/export_ical?id='.$this->active_user()->id.'&token='.$key)
        ];

        return response()->json($res); 

        
    }
    public function get_possible_groups(Request $request){
        $active_user = $this->active_user();
        $list = boardRecord::where('private_flag', 0)->whereHas('board_to_users', function($q) use($active_user){
            $q->where('user_id', $active_user->id);
        })->with(['board_to_users' => function($q){
            $q->whereHas('user')
            ->with('user')
            ->select('user_id', 'record_id');
        }])
        ->with(['icons' => function($q){
            $q->select('id','extension');
        }])
        ->orderBy('updated_at', 'desc')
        ->get();
        $projects = ProjectRecord::with(['members', 'manager', 'director'])->get();

        $projects = $projects->map(function ($project) {
            $users = collect()
                ->merge($project->members->all())
                ->merge($project->manager->all())
                ->when($project->director, function ($collection) use ($project) {
                    return $collection->push($project->director);
                });

            $project->users = $users;

            return $project;
        });
        $groups = MyGroup::where('user_id', $active_user->id)->where('deleted_flag', 0)->with('users')->get();
        return response()->json([
            "project" => $projects,
            "group" => $groups
        ]); 

    }
    public function get_departments_calendar(){
        $departments = ProjectRecord::whereHas('members')
                                    ->orWhereHas('manager')
                                    ->whereNull('completed_at')
                                    ->with(['members', 'manager', 'director'])
                                    ->select('id', 'name')
                                    ->get();
        $sortedProjects = $departments->sortByDesc(function ($project) {
            $isMember = in_array(Auth::id(), $project->members->pluck('id')->toArray());
            $isManager = in_array(Auth::id(), $project->manager->pluck('id')->toArray());
            $isDirector = $project->director && $project->director->id == Auth::id();
            if ($isMember) {
                return 3;
            } elseif ($isManager) {
                return 2;
            } elseif ($isDirector) {
                return 1;
            }
            return 0;
        })->values();
        return response()->json($sortedProjects);
    }
    public function get_schedule_summaries(Request $request){
        $record = CalendarRecord::findOrFail($request->id);
        $this->authorizeMeetingRecordView($record);
        $recordDate = Carbon::parse($record->date_start, config('app.timezone'));
        $summaryDayStart = $recordDate->copy()->startOfDay();
        $summaryDayEnd = $recordDate->copy()->endOfDay();
        $summaries = $record->summaries()
            ->whereBetween('calendar_meeting_summaries.created_at', [$summaryDayStart, $summaryDayEnd])
            ->with(['details', 'steps'])
            ->orderBy('created_at', 'desc')
            ->get();

        $transcripts = $record->transcripts()
            ->where('status', CalendarMeetingTranscript::STATUS_DOWNLOADED)
            ->whereNotNull('storage_path')
            ->with('aiSummary')
            ->orderBy('meeting_start_time')
            ->get()
            ->map(function (CalendarMeetingTranscript $transcript): array {
                $disk = Storage::disk('local');
                $content = $disk->exists($transcript->storage_path)
                    ? $disk->get($transcript->storage_path)
                    : '';

                return [
                    'id' => $transcript->id,
                    'meeting_id' => $transcript->meeting_id,
                    'meeting_uuid' => $transcript->meeting_uuid,
                    'meeting_start_time' => $transcript->meeting_start_time?->toISOString(),
                    'downloaded_at' => $transcript->downloaded_at?->toISOString(),
                    'cues' => $transcript->applySpeakerOverrides($this->zoomVttParser->parse($content)),
                    'ai_summary' => $this->serializeTranscriptAiSummary($transcript->aiSummary),
                ];
            })
            ->values();

        return response()->json([
            'summaries' => $summaries,
            'transcripts' => $transcripts,
        ]);
    }

    /**
     * 文字起こしの話者名を手直しする。
     * scope=cue  … その行だけ
     * scope=all  … この文字起こし内で元が同じ名前の行すべて
     * VTT 本体は書き換えず、上書き分だけを保存する。
     */
    public function update_transcript_speaker(Request $request)
    {
        $validated = $request->validate([
            'transcript_id' => ['required', 'integer'],
            'cue_index' => ['required', 'integer', 'min:0'],
            'name' => ['required', 'string', 'max:100'],
            'scope' => ['required', 'in:cue,all'],
        ]);

        $transcript = CalendarMeetingTranscript::query()
            ->with('calendarRecord')
            ->findOrFail($validated['transcript_id']);
        $record = $transcript->calendarRecord;

        if (! $record) {
            throw ValidationException::withMessages([
                'message' => 'この文字起こしに対応するスケジュールがありません。',
            ]);
        }

        $this->authorizeMeetingRecordView($record);

        if (
            $transcript->status !== CalendarMeetingTranscript::STATUS_DOWNLOADED
            || ! $transcript->storage_path
            || ! Storage::disk('local')->exists($transcript->storage_path)
        ) {
            throw ValidationException::withMessages([
                'message' => '編集できる文字起こしファイルがありません。',
            ]);
        }

        $name = trim($validated['name']);

        if ($name === '') {
            throw ValidationException::withMessages(['message' => '話者名を入力してください。']);
        }

        // 上書きは「VTTの元の名前」を基準に持つので、毎回パースし直して元の名前を引く
        $cues = $this->zoomVttParser->parse(Storage::disk('local')->get($transcript->storage_path));
        $cueIndex = $validated['cue_index'];

        if (! array_key_exists($cueIndex, $cues)) {
            throw ValidationException::withMessages(['message' => '対象の発言が見つかりません。']);
        }

        $originalName = $cues[$cueIndex]['speaker'];
        $overrides = $transcript->speaker_overrides ?? [];
        $byName = $overrides['all'] ?? [];
        $byCue = $overrides['cues'] ?? [];

        if ($validated['scope'] === 'all') {
            if ($originalName === null) {
                throw ValidationException::withMessages([
                    'message' => '話者名のない発言はまとめて変更できません。',
                ]);
            }

            // 元の名前に戻すだけなら上書きを消す
            if ($name === $originalName) {
                unset($byName[$originalName]);
            } else {
                $byName[$originalName] = $name;
            }

            // まとめて変更したのに一部だけ残ると分かりづらいので、
            // 同じ元名を持つ行の個別指定は外す
            foreach ($cues as $index => $cue) {
                if ($cue['speaker'] === $originalName) {
                    unset($byCue[(string) $index]);
                }
            }
        } else {
            $inheritedName = ($originalName !== null && array_key_exists($originalName, $byName))
                ? $byName[$originalName]
                : $originalName;

            // まとめ指定と同じ結果になるなら個別指定は持たない
            if ($name === $inheritedName) {
                unset($byCue[(string) $cueIndex]);
            } else {
                $byCue[(string) $cueIndex] = $name;
            }
        }

        $transcript->speaker_overrides = ($byName === [] && $byCue === [])
            ? null
            : ['all' => $byName, 'cues' => $byCue];
        $transcript->save();

        return response()->json([
            'cues' => $transcript->applySpeakerOverrides($cues),
        ]);
    }
    public function generate_transcript_ai_summary(Request $request)
    {
        $validated = $request->validate([
            'transcript_id' => ['required', 'integer'],
            'regenerate' => ['sometimes', 'boolean'],
        ]);

        $transcript = CalendarMeetingTranscript::query()
            ->with('calendarRecord')
            ->findOrFail($validated['transcript_id']);
        $record = $transcript->calendarRecord;

        if (! $record) {
            throw ValidationException::withMessages([
                'message' => 'この文字起こしに対応するスケジュールがありません。',
            ]);
        }

        $activeUser = $this->authorizeMeetingRecordView($record);
        if (
            $transcript->status !== CalendarMeetingTranscript::STATUS_DOWNLOADED
            || ! $transcript->storage_path
            || ! Storage::disk('local')->exists($transcript->storage_path)
        ) {
            throw ValidationException::withMessages([
                'message' => '要約できる文字起こしファイルがありません。',
            ]);
        }

        $regenerate = (bool) ($validated['regenerate'] ?? false);
        $transcriptHash = hash('sha256', Storage::disk('local')->get($transcript->storage_path));

        $result = DB::transaction(function () use (
            $transcript,
            $activeUser,
            $regenerate,
            $transcriptHash,
        ): array {
            $summary = CalendarMeetingTranscriptSummary::query()
                ->where('calendar_meeting_transcript_id', $transcript->id)
                ->lockForUpdate()
                ->first();

            if (
                $summary
                && in_array($summary->status, [
                    CalendarMeetingTranscriptSummary::STATUS_PENDING,
                    CalendarMeetingTranscriptSummary::STATUS_PROCESSING,
                ], true)
            ) {
                return ['summary' => $summary, 'dispatch' => false, 'status' => 202];
            }

            if ($summary?->content && ! $regenerate) {
                return ['summary' => $summary, 'dispatch' => false, 'status' => 409];
            }

            if (! $summary) {
                $summary = new CalendarMeetingTranscriptSummary([
                    'calendar_meeting_transcript_id' => $transcript->id,
                    'generation' => 0,
                ]);
            }

            $summary->forceFill([
                'requested_by' => $activeUser->id,
                'status' => CalendarMeetingTranscriptSummary::STATUS_PENDING,
                'prompt_version' => (string) config(
                    'services.openai.transcript_summary_prompt_version',
                    'v1'
                ),
                'transcript_hash' => $transcriptHash,
                'generation' => $summary->generation + 1,
                'last_error' => null,
                'requested_at' => now(),
            ])->save();

            return ['summary' => $summary, 'dispatch' => true, 'status' => 202];
        });

        /** @var CalendarMeetingTranscriptSummary $summary */
        $summary = $result['summary'];
        if ($result['dispatch']) {
            GenerateTranscriptAiSummary::dispatch($summary->id, $summary->generation)->afterResponse();
        }

        return response()->json([
            'ai_summary' => $this->serializeTranscriptAiSummary($summary),
            'message' => $result['status'] === 409
                ? '既存のAI要約を再生成するには確認が必要です。'
                : 'AI要約の作成を開始しました。',
        ], $result['status']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeTranscriptAiSummary(
        ?CalendarMeetingTranscriptSummary $summary,
    ): ?array {
        if (! $summary) {
            return null;
        }

        return [
            'status' => $summary->status,
            'content' => $summary->content,
            'model' => $summary->model,
            'prompt_version' => $summary->prompt_version,
            'last_error' => $summary->last_error,
            'requested_at' => $summary->requested_at?->toISOString(),
            'completed_at' => $summary->completed_at?->toISOString(),
        ];
    }

    public function save_edited_summary(Request $request){
        $summary = CalendarMeetingSummary::find($request->id)->update([
            'edited_version' => $request->html,
            'updated_at' => now()
        ]);

        return response()->json($summary);
    }
    public function delete_schedule_summary(Request $request){
        $request->validate([
            'id' => 'required'
        ]);
        $summary = CalendarMeetingSummary::find($request->id)->delete();
        return response()->json($summary);
    }
    public function calendar_temp_reserve(Request $request){
        $request->validate([
            'start_date' => 'required|date',
        ]);

        $buffer = $request->buffer ?? 15; 
        $zoom = $request->zoom ?? null;
        $room = $request->room ?? null;
        $usersData = collect($request->users);
        $userIds = $usersData->pluck('id')->toArray();

        $from = Carbon::parse($request->start_date)->startOfDay();
        $to = $from->copy()->add('day', 6)->endOfDay();

        $records = CalendarRecord::whereHas('calendar_users', fn($q) => $q->whereIn('user_id', $userIds))
        ->where('date_start', '>=', $from)
        ->where('date_end', '<=', $to)
        ->get();

        $roomsReserved = [];
        if($room !== null){
            $roomsReserved = CalendarRecord::where('qualified_institution', $room)
            ->where('date_start', '>=', $from)
            ->where('date_end', '<=', $to)
            ->get();
        }

        $zoomReserved = [];
        if($zoom !== null){
            $zoomReserved = CalendarRecord::where('zoom_value', $zoom)
            ->where('date_start', '>=', $from)
            ->where('date_end', '<=', $to)
            ->get();
        }
        $busySlots = [];
        $availability = [];
        foreach ($records as $rec) {

            $period = CarbonPeriod::create(
                Carbon::parse($rec->date_start)->copy()->subtract('minutes', $buffer)->floorMinute(15),
                '15 minutes',
                Carbon::parse($rec->date_end)->copy()->add('minutes', $buffer)->subtract('minutes', 15)->subMinute()->ceilMinute(15)
            );
                // dd($period);
            foreach ($rec->calendar_users as $cu) {
                $user_name = $cu->name;                

                foreach ($period as $slot) {
                    $d = $slot->format('Y-m-d');
                    $t = $slot->format('H:i');
                    $busySlots[$user_name][$d][$t] = true;
                }
            }
        }

        // 2) Mark busy slots for rooms
        foreach ($roomsReserved as $rec) {
            $period = CarbonPeriod::create(
                Carbon::parse($rec->date_start)->copy()->subtract('minutes', $buffer)->floorMinute(15),
                '15 minutes',
                Carbon::parse($rec->date_end)->copy()->add('minutes', $buffer)->subtract('minutes', 15)->subMinute()->ceilMinute(15)
            );
            
            foreach ($period as $slot) {
                $d = $slot->format('Y-m-d');
                $t = $slot->format('H:i');
      
                $room_name = "room_$room";
                $busySlots[$room_name][$d][$t] = true;
                
            }
        }
        foreach ($zoomReserved as $rec) {
            $period = CarbonPeriod::create(
                Carbon::parse($rec->date_start)->copy()->subtract('minutes', $buffer)->floorMinute(15),
                '15 minutes',
                Carbon::parse($rec->date_end)->copy()->add('minutes', $buffer)->subtract('minutes', 15)->subMinute()->ceilMinute(15)
            );
            
            foreach ($period as $slot) {
                $d = $slot->format('Y-m-d');
                $t = $slot->format('H:i');
                $index = (int) $zoom + 1;
                $room_name = "zoom_$index";
                $busySlots[$room_name][$d][$t] = true;
                
            }
        }

        // 3) Generate the full grid of days × slots × users, marking free/busy
        
        $dayPeriod = CarbonPeriod::create($from, '1 day', $to);

        foreach ($dayPeriod as $day) {
            $dateKey = $day->format('Y-m-d');

            // all 96 quarter-hours in a day
            $slotPeriod = CarbonPeriod::create(
                $day->copy()->setTime(7, 0, 0),
                '15 minutes',
                $day->copy()->setTime(21, 0, 0)->subMinute()
            );

            foreach ($slotPeriod as $slot) {
                $timeKey = $slot->format('H:i');

                foreach ($request->users as $user) {
                    $user_name = $user['name'];
                    $isBusy = $busySlots[$user_name][$dateKey][$timeKey] ?? false;
                    $availability[$dateKey][$timeKey][$user_name] = $isBusy
                        ? false
                        : true;
                }
                if ($room !== null) {
                    $room_name = "room_$room";
                    $isBusy = $busySlots[$room_name][$dateKey][$timeKey] ?? false;
                    $availability[$dateKey][$timeKey][$room_name] = $isBusy
                        ? false
                        : true;
                }   
                if ($zoom !== null) {
                    $index = (int) $zoom + 1;
                    $room_name = "zoom_$index";
                    $isBusy = $busySlots[$room_name][$dateKey][$timeKey] ?? false;
                    $availability[$dateKey][$timeKey][$room_name] = $isBusy
                        ? false
                        : true;
                }
            }
        }

        return response()->json($availability);
    }
    public function calendar_temp_confirm(Request $request){
        $request->validate([
            'id' => 'required',
            'status' => 'required|in:0,1',
        ]);

        $active_user = $this->active_user();
        $record = CalendarRecord::findOrFail($request->id);
        switch ($request->status) {
            case 1:
                $record->update(['temp_flag' => 0, 'updated_user' => $active_user->id]);
                $record->related_temp_records()->delete();
                // メールはレスポンス後に送る（確定処理そのものを待たせない）
                SendCalendarMails::dispatchAfterResponse(
                    [(int) $record->id],
                    $record->calendar_users()->pluck('id')->map(fn($id) => (int) $id)->toArray(),
                    (int) $active_user->id,
                    '確定'
                );

                return response('予約を確定しました。', 200);
            case 0:
                $record->calendar_users()->detach($active_user->id);
                $relatedTempRecords = $record->related_temp_records;
                foreach ($relatedTempRecords as $tempRecord) {
                    $tempRecord->calendar_users()->detach($active_user->id);
                }
                return response('予約をキャンセルしました。', 200);
            default:
                return response('無効なステータスです。', 400);
        }

    }
}
