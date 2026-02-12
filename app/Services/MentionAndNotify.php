<?php

namespace App\Services;

use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\boardToUser;
use App\Models\boardRecord;
use App\Models\User;
use App\Jobs\SendNotification;
use App\Jobs\SendEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MentionAndNotify
{
    public function mention(boardRecord $boardRecord, User $user, messageRecord $chat)
    {
        $boardRecord->touch();
        if($boardRecord->private_flag == 1){
            $restoreUsers = boardToUser::where('record_id','=', $boardRecord->id)->where('deleted_status', '=', 1)->get();
            if(!empty($restoreUsers)){
                foreach($restoreUsers as $restoreUser){
                    $restoreUser->deleted_status = 0;
                    $restoreUser->created_at = now();
                    $restoreUser->save();
                }
                $chat->touch();
            }
            
        }
        $syntax = '/\[To:(.*?)\:\]/';
        preg_match_all($syntax, $chat->message, $matches);
        $mentioned_targets = $matches[1];
        $mentioned_all = in_array('全員', $mentioned_targets);

        $query = $boardRecord->members()
        ->whereNot('users.id', $user->id)
        ->where('users.on_leave', 0)
        ->when(!$mentioned_all, function($q) use($mentioned_targets) {
            $q->whereIn('users.name', $mentioned_targets);
        });
        $mentioned_users = $query->get();
        $notified_users = $query->wherePivot('notification', 1)->get();
        if(!empty($mentioned_users)){     
            $emails = collect($mentioned_users)->filter(function($user){
                return filter_var($user->email, FILTER_VALIDATE_EMAIL);
            })->pluck('email')->toArray();             
            $board = $boardRecord;              
            
            if(!empty($board) && $board->private_flag == 1){
                $b_title = $user->name;
                
            }else{
                $b_title = $board->title;
            }                                    
            $content = $chat->message_text;
            $block_flag = false;
            $blocked_words = ['password', 'PASSWORD', 'PW', 'pw','pass','PASS', 'パスワード','ﾊﾟｽﾜｰﾄﾞ', 'パス', 'ﾊﾟｽ'];
            foreach($blocked_words as $word){
                if (str_contains($chat->message_text, $word)) { 
                    $block_flag = true;
                }
            }         
            
            $mail_payload = array(
                "b_title" => $b_title,
                "content" => $content,
                "block_flag" => $block_flag,
                "board_id" => $board->id,
                "chat_id" => $chat->id,
                "mails" => $emails,
            );                
            SendEmail::dispatchAfterResponse($mail_payload);               

            $notify_ids = $notified_users->pluck('id')->toArray();
            $members = array_map(function ($userId) {
                return (string) $userId;
            }, $notify_ids);
            if(!empty($members)){
                $deep_link = url('board/' . $boardRecord->id);
               
                if(!empty($boardRecord) && $boardRecord->private_flag == 1){
                    $push_title = $user->name;
                    $body = 'メッセージが届きました';
                }else{
                    $push_title = $boardRecord->title;
                    $body = $user->name . 'さんからメッセージが届きました';
                }
                $this->notify($members, $push_title, $body, $deep_link, $user);
            }
            return null;
            
        }
        return null;
    }
    public function notify(array $notify_ids, string $push_title, string $body, string $link, User $user) {
        $icon = $user->icon_path 
            ? url("content_api/profile_icon_migrated/$user->icon_path.webp") 
            : url("user_default_thumbnail/" . urlencode(mb_substr($user->name, 0, 1)) . "/30/000000");
        
        $badge = url('/notification-favicon.png');
        $payload = [
            "body" => $body,
            "title" => $push_title,
            "link" => $link,
            "members" => $notify_ids,
            "icon" => $icon,
            "badge" => $badge,
            "user_id" => $user->id,
            "user_name" => $user->name,
        ];
        SendNotification::dispatchAfterResponse($payload);
        return $payload;
    }
}
