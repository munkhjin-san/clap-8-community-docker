<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class messageFile extends Model
{   
    use SoftDeletes;
    public function message_records(){
        return $this->belongsTo(messageRecord::class, 'message_id', 'id');
    }
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function signUsers()
    {
        return $this->belongsToMany(User::class, 'message_sign_users')->withPivot(['signed', 'cancel_flag'])->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg', 'users.deleted_at');
    }
    public function signedUsers()
    {
        return $this->belongsToMany(User::class, 'message_sign_users')
                    ->using(messageSignUser::class)
                    ->withPivot(['signed', 'cancel_flag'])
                    ->wherePivot('signed', true)
                    ->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg', 'users.deleted_at');
    }

    public function unsignedUsers()
    {
        return $this->belongsToMany(User::class, 'message_sign_users')
                    ->using(messageSignUser::class)
                    ->withPivot(['signed', 'cancel_flag'])
                    ->wherePivot('signed', false)
                    ->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg', 'users.deleted_at');
    }
    protected $casts = [
        'message_id' => 'int',     
        'size' => 'int',      
        
    ];
    protected $guarded = [];
}
