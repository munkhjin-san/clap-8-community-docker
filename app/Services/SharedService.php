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
        $deleteAll = CalendarRecord::whereHas('calendar_users', function ($query) use ($userId) {
            $query->where('users.id', $userId);
        })->whereYear('date_start', $year)->whereMonth('date_start', $month)->where('shift', 1)->delete();
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
            'icon_id'
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
        $user->update(['icon_id' => $icon->id]);
        return true;
    }
    public function removeBoard($target){
        $board = $target;
        $board->board_to_users()->delete();
        $tasks = taskRecord::where('board_id', $board->id)->get();
        $tasks->each(function ($task) {
            $task->delete();
            $task->task_users()->delete();
        });
        $board->delete();
        messageRecord::where('record_id', $board->id)->delete();
        messageFile::where('board_id', $board->id)->delete();
        $icon = Icons::findOrFail($board->icon_id);
        if($icon){
            $icon->delete();
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
        $board->update(['icon_id' => $icon->id]);
        $board->timestamps = true;
        
      
        $boardname_no_space = preg_replace('/\s+/', '', $board->title);
        
        $img = Image::create(200, 200)->fill('#000');   
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
            "added_members" => "がボードメンバーに追加されました。",
            "removed_members" => "がボードを退出しました。",
            "left_members" => "がボードを退出しました。",
            "new_task" => "が作られました。",
            "glowd_nine_task" => "が作られました。"
        ];
        $addedMembers = ' <span class="addedMembers">' . $userName . '</span>' . $patterns[$type];
        $addedMembers = match ($type) {
            'new_task' => "<strong>新しい{$userName}{$patterns[$type]}</strong>\n{$extra}",
            'glowd_nine_task' => "<strong>グラウドナインの新しい{$userName}{$patterns[$type]}</strong>\n{$extra}"
        };
        $first_message = new messageRecord;
        $first_message->info_flag = $type == 'new_task' || $type == 'glowd_nine_task' ? 2 : 1;
        $first_message->message = $addedMembers;
        $first_message->record_id = $boardId;
        $first_message->user_id = $userId;
        $first_message->save();
        return true;
    }
}