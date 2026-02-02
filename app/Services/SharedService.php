<?php

namespace App\Services;
use Carbon\Carbon;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\User;
use App\Models\Icons;
use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\appRememberRecord;
use App\Models\searchHistoryRecord;
use App\Models\taskRecord;
use App\Models\taskUser;
use App\Models\CalendarRecord;
use App\Models\shiftRecord;
use App\Models\shiftType;
use App\Events\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
class SharedService
{
    public function syncShiftToCalendar($userId, $year, $month){
        CalendarRecord::join('calendar_users', 'calendar_records.id', '=', 'calendar_users.record_id')
        ->where('calendar_users.user_id', $userId)
        ->whereRaw("DATE_FORMAT(date_start, '%Y-%m') = ?", ["$year-$month"])
        ->where('shift', 1)
        ->delete();
        $shift_array = shiftRecord::whereMonth('shift_day', $month)->whereYear('shift_day', $year)->where('user_id', $userId)->get();
        foreach($shift_array as $shift){
            if(in_array( $shift['shift_type'], [0, 2, 3, 5, 14, 15, 16])){
                $shiftType = shiftType::find($shift['shift_type']);
                $instance = Carbon::parse($shift['shift_day']); 
                $start_instance = $instance->clone()->hour(00)->minute(00)->second(00);
                $end_instance = $instance->clone()->hour(23)->minute(59)->second(00);
                $start = Carbon::createFromFormat('Y-m-d H:i:s', $start_instance);
                $end = Carbon::createFromFormat('Y-m-d H:i:s', $end_instance);
                $record = CalendarRecord::create([
                    "title" => $shiftType['name'],
                    "updated_user" => $userId,
                    "user_id" => $userId,
                    "created_user" => $userId,
                    "date_start" => $start,
                    "date_end" => $end,
                    "release_flag" => 0,
                    "shift" => 1
                ]);
                $record->calendar_users()->syncWithPivotValues([$userId], ["created_at" => now(),"updated_at" => now()]);
            }
            
        }
        return 'success';
        
    }
    public function syncTaskToCalendar($task, $executors){
        $instance = Carbon::parse($task->end_at); 
        $start_instance = $instance->clone()->hour(00)->minute(00)->second(00);
        $end_instance = $instance->clone()->hour(23)->minute(59)->second(00);
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $start_instance);
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $end_instance); 
        $this->deleteTaskFromCalendar($task);
        $params = [
            "title" => $task->title,
            "updated_user" => $task->updated_user,
            "user_id" => $task->user_id,
            "created_user" => $task->user_id,
            "date_start" => $start,
            "date_end" => $end,
            "release_flag" => 0,
            "task" => $task->id,
            "remarks" => $task->remarks,
        ];
        $record = CalendarRecord::create($params);
        $record->calendar_users()->syncWithPivotValues($executors, ["created_at" => now(),"updated_at" => now()]);
        return 'success';
    }

    public function deleteTaskFromCalendar($task){
        $record = CalendarRecord::where('task', $task->id)->first();
        if($record){
            $record->calendar_users()->detach();
            $record->delete();
            return 'deleted';
        }
    }
    public function getUserState($target_id, $self){
        
        $data = User::where('id', $target_id)
        ->select(
            'id', 
            'name', 
            'created_at',
            'icon_path', 'icon_bg'
        )
        ->with('user_detail')
        ->first();
        if(empty($data)){
            return null;
        }
        return $data;
    }
    public function createUserDefaultIcon($user){
                
                                 
        $unique_number = rand(1000, 9999); 
        $current_timestamp = time(); 
        $new_a_path = $current_timestamp . $unique_number;     
        $userName = $user->name;
        $firstChar = mb_strtoupper(mb_substr($userName, 0, 1, "UTF-8"));        
        $input = array("#000");
        $random = $input[array_rand($input, 1)];
        $img = Image::create(200, 200)->fill($random);
        $regex = '/[А-Яа-яЁёөү]/u';
        $is_mn = preg_match($regex, $firstChar);
        $font_path = $is_mn ? 'fonts/NotoSans-Bold.ttf' : 'fonts/Noto_Sans_CJK-Bold.otf';
        $img->text($firstChar, 100, 100, function ($font) use ($font_path) {
            $font->file(resource_path($font_path));
            $font->size(130);
            $font->color('#fff');
            $font->align('center');
            $font->valign('middle');
            
        });

        $size_variants = [200, 120, 80, 45, 30, 25, 20, 15];
        if (!Storage::disk('local')->exists('profile_icon')) {
            Storage::disk('local')->makeDirectory('profile_icon');
        }
        $icon = new Icons;
                
        $icon->mime_type = 'image';
        $icon->extension = 'jpg';       
        $icon->user_id = $user->id;
        $icon->profile_id = $user->id;
        $icon->use_of = "profile_default";
        $icon->save();
        foreach($size_variants as $size){
            $img_rsz = $img->resize($size, $size);
            $set_path = $icon->id . '_' . $user->id . '_' . $size . '.jpg';
            $temp_path = storage_path('app/profile_icon/'.$set_path);
            $img_rsz->save($temp_path);
        }
        $user->update(['icon_path' => $icon->id]);
        return true;
    }
    public function removeBoard($target){
        $board = $target;
        $board->board_to_users()->delete();
        $tasks = taskRecord::where('board_id', $board->id)->get();
        $tasks->each(function ($task) {
            $task->delete();
            $task->executors()->detach();
        });
        $board->delete();
        messageRecord::where('record_id', $board->id)->delete();
        messageFile::where('board_id', $board->id)->delete();
        if($board->icon_path){
            Storage::disk('local')->delete("board_icon_migrated/$board->icon_path");
        }  
        return "respondDeleted";
    }
    public function createBoardDefaultIcon($board, $user_id){
        
        if($board->id){
            $rmv = Icons::where('record_id', '=', $board->id)->where('use_of', '=', 'board')->get();
            if($rmv){
                foreach($rmv as $del){
                    Storage::disk('local')->delete('board_icon/board_' . $del->id . '.' . $del->extension);
                    $del->delete();
                    $del->save();
                }
                        
            }   
        }      
        
        $icon = new Icons;    
        $icon->user_id =  $user_id;            
        $icon->mime_type = 'image';
        $icon->extension = 'png';       
        $icon->record_id = $board->id;                
        $icon->use_of = "board";
        $icon->save();
        $board->timestamps = false;
        $board->update(['icon_path' => $icon->id]);
        $board->timestamps = true;
        
        
        $boardname_no_space = preg_replace('/\s+/', '', $board->icon_text);
        
        $img = Image::create(200, 200)->fill($board->icon_bg);   
        $length = mb_strlen($boardname_no_space);
        $font_path = 'fonts/Noto_Sans_CJK-Bold.otf';        

        $bucket = array(
            array(),
            array( array('y' => 100, 'size' => 100, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8"))),
            array( array('y' => 100, 'size' => 80, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8"))),
            array( array('y' => 100, 'size' => 60, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8"))),
            array( array('y' => 70, 'size' => 60, 'text' => mb_substr($boardname_no_space, 0, 2, "UTF-8")),  array('y' => 130, 'size' => 60, 'text' => mb_substr($boardname_no_space, 2, 2, "UTF-8"))),
            array( array('y' => 75, 'size' => 50, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8")), array('y' => 135, 'size' => 50, 'text' => mb_substr($boardname_no_space, 3, 2, "UTF-8"))),
            array( array('y' => 70, 'size' => 50, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8")), array('y' => 130, 'size' => 50, 'text' => mb_substr($boardname_no_space, 3, 3, "UTF-8"))),           
        );

        $index = $length >= 6 ? 6 : $length;        
        $pot = $bucket[$index];
        foreach($pot as $plate){
            $img->text($plate['text'], 100, $plate['y'], function ($font) use($font_path, $plate) {
                $font->file(resource_path($font_path));
                $font->size($plate['size']);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
        }
                 
        $set_path = 'board' . '_' . $icon->id . '.' . 'png';
        if (!Storage::disk('local')->exists('board_icon')) {
            Storage::disk('local')->makeDirectory('board_icon');
        }
        $temp_path = storage_path('app/board_icon/'.$set_path);
        $img->save($temp_path);
        
        return true;
    }
    public function createInfoMessage ($userName, $boardId, $type, $userId, $extra = ''){
        $patterns = [
            "added_members" => "がチャットメンバーに追加されました。",
            "removed_members" => "がチャットを退出しました。",
            "left_members" => "がチャットを退出しました。",
            "new_task" => "が作られました。",
            "glowd_nine_task" => "が作られました。"
        ];
        $addedMembers = ' <span class="addedMembers">' . $userName . '</span>' . $patterns[$type];
        if ($type == 'new_task' || $type == 'glowd_nine_task') {
            $addedMembers = match ($type) {
                'new_task' => "<strong>新しい{$userName}{$patterns[$type]}</strong>\n{$extra}",
                'glowd_nine_task' => "<strong>グラウドナインの新しい{$userName}{$patterns[$type]}</strong>\n{$extra}"
            };
        }
        
        $first_message = new messageRecord;
        $first_message->info_flag = $type == 'new_task' || $type == 'glowd_nine_task' ? 2 : 1;
        $first_message->message = $addedMembers;
        $first_message->record_id = $boardId;
        $first_message->user_id = $userId;
        $first_message->save();
        return true;
    }
    public function path_generator(){
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
    public function work_days_calculator(int $year, int $month, User $user) {
        $instance = Carbon::createFromDate($year, $month, 1);
        $lastDay = $instance->endOfMonth()->day;
        $user_work_hour_per_day = $user->work_time_day ?? 480;
        $user_position = $user->position_id;

        $holidayNum = match (true) {
            $user_position == 12 => 9,
            $user_position == 13 => 9,
            $month == 12 => 10,
            $month == 1 => 12,
            $lastDay == 29 => 8.5,
            $lastDay == 28 => 8,
            default => 9,
        };

        $workDays = $lastDay - $holidayNum;
        $convertIntoMinutes = $workDays * $user_work_hour_per_day;
        return [
            'days' => $workDays,
            'work_minutes' => $convertIntoMinutes,
        ];
    }
    public function planned_shift_calculator(iterable $shifts): array
    {
        $holidayDays = 0;
        $workDays = 0;
        $workMinutes = 0;
        $paidLeaveDays = 0.0;
        $paidLeaveMinutes = 0;

        // normalize to Collection
        $shifts = collect($shifts);

        if ($shifts->isEmpty()) {
            return [
                'holidayDays' => 0,
                'workDays' => 0,
                'workMinutes' => 0,
                'paidLeaveDays' => 0,
                'paidLeaveMinutes' => 0,
                'accountedMinutes' => 0,
            ];
        }

        // Take start/end time from the first shift (same for the month)
        $first = $shifts->first();
        $minutesPerDay = $this->calcNetWorkMinutesPerDay(
            $first->start_time,
            $first->end_time
        );

        foreach ($shifts as $shift) {
            // Prefer current shift type, fallback to old_shift if needed
            $type = $shift->shiftType
                ?? $shift->old_shift?->shiftType;

            $typeId = $type?->id;

            // Holiday (0 / 18)
            if (in_array($typeId, [0, 18], true)) {
                $holidayDays++;
                continue;
            }

            $typeValue = $type?->value; // minutes or null

            // Work day
            if ($typeValue === null) {
                $workDays++;
                $workMinutes += $minutesPerDay;
                continue;
            }

            // Leave
            $mins = (int) $typeValue;
            $paidLeaveMinutes += $mins;

            if (isset($type->full_day)) {
                if ((int)$type->full_day === 2) $paidLeaveDays += 1.0;
                elseif ((int)$type->full_day === 1) $paidLeaveDays += 0.5;
            } else {
                if ($mins >= 480) $paidLeaveDays += 1.0;
                elseif ($mins >= 240) $paidLeaveDays += 0.5;
            }
        }

        return [
            'holidayDays'      => $holidayDays,
            'workDays'         => $workDays,
            'workMinutes'      => $workMinutes,
            'paidLeaveDays'    => $paidLeaveDays,
            'paidLeaveMinutes' => $paidLeaveMinutes,
            'accountedMinutes' => $workMinutes + $paidLeaveMinutes,
        ];
    }
    private function calcNetWorkMinutesPerDay(string $startTime, string $endTime): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $startTime));
        [$eh, $em] = array_map('intval', explode(':', $endTime));

        $start = $sh * 60 + $sm;
        $end = $eh * 60 + $em;

        if ($end < $start) {
            $end += 24 * 60;
        }

        $gross = max(0, $end - $start);
        $break = $this->calcBreakMinutes($gross);

        return max(0, $gross - $break);
    }

    private function calcBreakMinutes(int $grossMinutes): int
    {
        if ($grossMinutes > 6 * 60) return 60;
        if ($grossMinutes >= 3 * 60) return 30;
        return 0;
    }
    public function createDepartureReport($user, $date){
        $shift = shiftRecord::where('user_id', $user->id)
            ->where('shift_day', $date)
            ->where('shift_type', 1)
            ->first();
        if(!$shift){
            return [
                "status" => "error",
                "message" => "本日のシフトが見つかりません。"
            ];
        }

        if($shift->departure_report){
            return [
                "status" => "error",
                "message" => "既に出発報告がされています。"
            ];
        }
        //departure_report is timestamp field
        $shift->departure_report = Carbon::now();
        $shift->save();
        return [
            "status" => "success",
            "message" => "出発報告を受け付けました。"
        ];
    }
}