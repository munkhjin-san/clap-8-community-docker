<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class messageFile extends Model
{   
    use SoftDeletes;
    public function message_records(){
        return $this->belongsTo(MessageRecord::class);
    }
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id');
    }
    public function signUsers()
    {
        return $this->belongsToMany(User::class, 'message_sign_users')->withPivot(['signed'])->select('users.id', 'users.name', 'users.icon_id', 'users.deleted_at');
    }
    public function signedUsers()
    {
        return $this->belongsToMany(User::class, 'message_sign_users')
                    ->using(messageSignUser::class)
                    ->withPivot(['signed'])
                    ->wherePivot('signed', true)
                    ->select('users.id', 'users.name', 'users.icon_id', 'users.deleted_at');
    }

    public function unsignedUsers()
    {
        return $this->belongsToMany(User::class, 'message_sign_users')
                    ->using(messageSignUser::class)
                    ->withPivot(['signed'])
                    ->wherePivot('signed', false)
                    ->select('users.id', 'users.name', 'users.icon_id', 'users.deleted_at');
    }
    protected $casts = [
        'message_id' => 'int',     
        'size' => 'int',      
        
    ];
    protected $fillable = [
        'removed_at'
    ];
}
