<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class messageRecord extends Model
{   
    use SoftDeletes; 
    public function checkUsers()
    {
        return $this->belongsToMany(User::class, 'message_check_users')->withPivot(['checked'])->select('users.id', 'users.name', 'users.icon_path', 'users.deleted_at');
    }
    public function reactedUsers()
    {
        return $this->belongsToMany(User::class, 'message_reacted_users')->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg', 'users.deleted_at');
    }   
    public function checkedUsers()
    {
        return $this->belongsToMany(User::class, 'message_check_users')
                    ->using(messageCheckUser::class)
                    ->withPivot(['checked'])
                    ->wherePivot('checked', true)
                    ->select('users.id', 'users.name', 'users.icon_path','users.icon_bg', 'users.deleted_at');
    }
    public function emotedUsers()
    {
        return $this->belongsToMany(User::class, 'message_emote_users')
                    ->withPivot(['emote_id'])
                    ->select('users.id', 'users.name', 'users.icon_path','users.icon_bg', 'users.deleted_at');
    }

    public function uncheckedUsers()
    {
        return $this->belongsToMany(User::class, 'message_check_users')
                    ->using(messageCheckUser::class)
                    ->withPivot(['checked'])
                    ->wherePivot('checked', false)
                    ->select('users.id', 'users.name', 'users.icon_path','users.icon_bg', 'users.deleted_at');
    }
    public function user(){
        return $this->belongsTo(User::class)->withTrashed()->select('id', 'icon_path', 'icon_bg', 'name', 'deleted_at');
    }
    public function actual_sender(){
        return $this->belongsTo(User::class, 'actual_sender_id')->withTrashed()->select('id', 'icon_path', 'icon_bg', 'name', 'deleted_at');
    }
    public function board(){
        return $this->belongsTo(boardRecord::class, 'id', 'record_id');
    }   
    public function message_files(){
        return $this->hasMany(messageFile::class, 'message_id');
    }
    public function message_reply(){
        return $this->belongsTo(self::class, 'reply_id', 'id')->with('message_files')->with('user');
    }
    public function board_record(){
        return $this->hasOne(boardRecord::class, 'id', 'record_id');
    }
    public function message_quot(){
        return $this->belongsTo(self::class, 'quot_id', 'id')->with('message_files')->with('user');
    }
    public function message_forward(){
        return $this->belongsTo(self::class, 'forward_id', 'id')->with('message_files')->with('user');
    }
    public function board_users(){
        return $this->hasMany(boardToUser::class, 'record_id', 'record_id');
    }
    public function messageRemindUsers(){
        return $this->hasMany(messageRemindUser::class, 'message_id')->where('reminded', 1);
    }
    public function memo(){
        return $this->hasOne(memoRecord::class, 'message_id', 'id');
    }
    public function task(){
        return $this->hasOne(taskRecord::class, 'message_id', 'id')->withTrashed();
    }
    protected $fillable = [
        'message'
    ];
    protected $casts = [
        'check_flag'    => 'int', 
        'record_id' => 'int',
        'user_id' => 'int',       
        'reply_flag' => 'int',
        'reply_id' => 'int',
        'quot_flag' => 'int',
        'quot_id' => 'int',    
        'emoji_flag' => 'int',    
        'info_flag' => 'int',
    ];
    protected $hidden = [
        'message_text',
        'reacted_users',
        'checked_users',
        'unchecked_users',
        'app_export'
    ];

}
