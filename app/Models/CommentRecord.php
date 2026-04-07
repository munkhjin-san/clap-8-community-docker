<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentRecord extends Model
{
    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function claps(){
        return $this->hasMany(ClapRecord::class, 'record_id')->where('app_id', 5)->where('deleted_flag', 0)->select('record_id', 'from_user')->with('user');
    }
    public function emotedUsers()
    {
        return $this->morphToMany(User::class, 'stampable', 'stamps', 'stampable_id', 'user_id')
                    ->withPivot(['emote_name'])
                    ->withTimestamps()
                    ->select('users.id', 'users.name', 'users.icon_path', 'users.icon_bg', 'users.deleted_at');
    }
    public function stamps()
    {
        return $this->morphMany(Stamp::class, 'stampable');
    }
    protected $guarded = [];
    protected $casts = [
        'user_id' => 'int',  
        'record_id' => 'int',
        'comment_id' => 'int',      
        
    ];
}
