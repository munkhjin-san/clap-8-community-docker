<?php

namespace App\Services;

use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\User;
use App\Models\Icons;
use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\memoRecord;
use App\Models\appRememberRecord;
use App\Models\searchHistoryRecord;
use App\Models\taskRecord;
use App\Models\taskUser;
use App\Events\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
class SharedService
{
    public function getUserState($target_id, $self){
        $is_friend = $target_id == $self->id ? "self" : $self->friends()->where('friend_id', $target_id)->where('status', 1)->exists();
        $is_waiting = $target_id == $self->id ? "self" : $self->friends()->where('friend_id', $target_id)->where('status', 0)->exists();
        $is_blocked = $target_id == $self->id ? "self" : $self->blockedUsers()->where('blocked_user_id', $target_id)->exists();
        $is_blocked_by = $self->usersWhoBlockedMe()->where('user_id', $target_id)->exists();
        
        $data = User::where('id', $target_id)
        ->select(
            'id', 
            'name', 
            'a_path', 
            'a_version', 
            // 'phone', 
            // 'email', 
            'q_token', 
            // 'phone_prefix', 
            'is_public', 
            // 'color', 
            // 'email_verified_at', 
            // 'phone_isVerified', 
            'created_at'
        )
        ->with('user_detail')
        ->first();
        if(empty($data)){
            return null;
        }
        $is_recieved_request = $data->friends()->where('friend_id', $self->id)->where('status', 0)->exists();
        $has_mutual_chat = boardRecord::where('private_flag', 0)->whereHas('board_to_users', function($q) use ($target_id){
            $q->where('user_id', $target_id)->where('deleted_status', 0);
        })->whereHas('board_to_users', function($q) use($self){
            $q->where('user_id', $self->id)->where('deleted_status', 0);
        })->exists();
        $data["is_blocked"] = $is_blocked;
        $data["is_friend"] = $is_friend;
        $data["is_waiting"] = $is_waiting;
        $data["is_blocked_by"] = $is_blocked_by;
        $data["is_recieved_request"] = $is_recieved_request;
        $data["has_mutual_chat"] = $has_mutual_chat;
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
        $img = Image::canvas(200, 200, $random);
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
        if (!Storage::disk('local')->exists('temp')) {
            Storage::disk('local')->makeDirectory('temp');
        }
        if (!Storage::disk('s3')->exists('profile_icon')) {
            Storage::disk('s3')->makeDirectory('profile_icon');
        }
        foreach($size_variants as $size){
            $img_rsz = $img->resize($size, $size);
            Storage::disk('s3')->delete('profile_icon/' . $user->id . '_' . $user->a_path . '_' . $size . '.jpg');   
            $set_path = $user->id . '_' . $new_a_path . '_' . $size . '.jpg';
            $temp_path = storage_path('app/temp/'.$set_path);
            $img_rsz->save($temp_path);            
            Storage::disk('s3')->put('profile_icon/' . $user->id . '_' . $new_a_path . '_' . $size . '.jpg', file_get_contents($temp_path));
            unlink($temp_path); 
        }
        $user->update(['a_path' => $new_a_path, 'a_version' => 0]);
        return true;
    }
    public function newUserQrCode ($path, $id, $current_token) {   
        if (empty($path) || empty($id)) {
            throw new InvalidArgumentException('All attributes are required.');
        }
        
        if (!Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->makeDirectory($path);
        }
        if($current_token){
            if (!Storage::disk('s3')->exists($path . '/' . $current_token . '_' . $id)) {
                Storage::disk('s3')->delete($path . '/' . $current_token . '_' . $id . '.png');
            }  
        }           
        
        $new_token = Str::random(8);
        $prefix = $path == 'user_qr_code' ? 'invite' : ($path == 'board_qr_code' ? 'join' : '');
        $client = new \GuzzleHttp\Client();        
        $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($url = url('/'. $prefix . '?token=' . $new_token . '&id=' . $id));           
        $response = $client->get($qrCodeUrl);    
        Storage::disk('s3')->put($path . '/'. $new_token . '_' . $id . '.png', $response->getBody());  
                    
        return $new_token;        
    }
    public function removeBoard($target){
        $board = $target;
        $board->board_to_users()->delete();
        $tasks = taskRecord::where('board_id', $board->id)->get();
        $tasks->each(function ($task) {
            $task->delete();
            $task->task_users()->delete();
        });
        memoRecord::where('board_id', $board->id)->delete();
        $board->delete();
        messageRecord::where('record_id', $board->id)->delete();
        messageFile::where('board_id', $board->id)->delete();
        $icon = Icons::findOrFail($board->icon_id);
        if($icon){
            Storage::disk('s3')->delete('board_icon/board_' . $board->icon_id . '.png');
            $icon->delete();
        }
        Storage::disk('s3')->delete('board_qr_code/' . $board->q_token . '_' . $board->id . '.png');   
        $directory = 'message_files/' . $board->id;
        // $contents = Storage::disk('s3')->listContents($directory, true);

        // foreach ($contents as $item) {
        //     if ($item['type'] == 'file') {
        //         Storage::disk('s3')->delete($item['path']);
        //     } else {
        //         Storage::disk('s3')->deleteDirectory($item['path']);
        //     }
        // }
        Storage::disk('s3')->deleteDirectory($directory);
        return "respondDeleted";
    }
    public function createBoardDefaultIcon($board, $user_id){
        if($board->id){
            $rmv = Icons::where('record_id', '=', $board->id)->where('use_of', '=', 'board')->get();
            if($rmv){
                foreach($rmv as $del){
                    Storage::disk('s3')->delete('board_icon/board_' . $del->id . '.' . $del->extension);
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
        $board->icon_id = $icon->id;
        
        
        
        $boardname = $board->title;
        $boardname_no_space = preg_replace('/\s+/', '', $board->title);
        $firstChar = mb_substr($boardname_no_space, 0, 3, "UTF-8");
        $lastChar = mb_substr($boardname_no_space, 3, 6, "UTF-8");
        
        $input = array("#000");
        $random = $input[array_rand($input, 1)];
        $img = Image::canvas(200, 200, $random);
        
        $length = mb_strlen($boardname_no_space);
        $font_size = '20';
        $pos_x;
        $pos_y;   
        $pos_x_lower;
        $pos_y_lower;         
        $regex = '/[А-Яа-яЁёөү]/u';
        $is_mn = preg_match($regex, $firstChar);
        $font_path = $is_mn ? 'fonts/NotoSans-Bold.ttf' : 'fonts/Noto_Sans_CJK-Bold.otf';
        switch(true){
        case $length == 1:
            $font_size = '100';
            $pos_x = 100;
            $pos_y = 100;
            $img->text($firstChar, 100, 100, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
        break;
        case $length == 2:
            $font_size = '80';
            $pos_x = 100;
            $pos_y = 100;
            $img->text($firstChar, 100, 100, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
        break;
        case $length == 3:
            $font_size = '60';
            $pos_x = 100;
            $pos_y = 100;
            $img->text($firstChar, 100, 100, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
        break;
        case $length == 4:
            $font_size = '60';
            $pos_x = 100;
            $pos_y = 70;                        
            $first2 = mb_substr($boardname_no_space, 0, 2, "UTF-8");
            $last2 = mb_substr($boardname_no_space, 2, 2, "UTF-8");
            $img->text($first2, 100, 70, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
            $img->text($last2, 100, 130, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
        break;
        case $length == 5:
            $font_size = '50';
            $pos_x = 100;
            $pos_y = 70;                        
            $first3 = mb_substr($boardname_no_space, 0, 3, "UTF-8");
            $last2 = mb_substr($boardname_no_space, 3, 2, "UTF-8");
            $img->text($first3, 100, 75, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
            $img->text($last2, 100, 135, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
        break;
        case $length >= 6:
            $font_size = '50';
            $pos_x = 100;
            $pos_y = 70;                        
            $first3 = mb_substr($boardname_no_space, 0, 3, "UTF-8");
            $last3 = mb_substr($boardname_no_space, 3, 3, "UTF-8");
            $img->text($first3, 100, 70, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
            $img->text($last3, 100, 130, function ($font) use($font_size, $font_path) {
                $font->file(resource_path($font_path));
                $font->size($font_size);
                $font->color('#fff');
                $font->align('center');
                $font->valign('middle');
                
            });
        break;                    
        default:
            $font_size = '70';
            $pos_x = 23;
            $pos_y = 22; 
        }                 
        $set_path = 'board' . '_' . $icon->id . '.' . 'png';
        if (!Storage::disk('s3')->exists('board_icon')) {
            Storage::disk('s3')->makeDirectory('board_icon');
        }
        if (!Storage::disk('local')->exists('temp')) {
            Storage::disk('local')->makeDirectory('temp');
        }
        $temp_path = storage_path('app/temp/'.$set_path);
        $img->save($temp_path);
        Storage::disk('s3')->put( 'board_icon/board_' . $icon->id . '.png', file_get_contents($temp_path));
        unlink($temp_path);   
        return true;
    }
    public function createInfoMessage ($userList, $boardId, $type, $userId){
        $body = [
            "members" => $userList,
            "type" => $type
        ];
        $encoded_body = json_encode($body);
        $first_message = new messageRecord;
        $first_message->info_flag = 1;
        $first_message->message = $encoded_body;
        $first_message->record_id = $boardId;
        $first_message->user_id = $userId;
        $first_message->save();
        return true;
    }
}