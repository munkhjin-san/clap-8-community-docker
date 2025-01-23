<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoticeRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function files(){
        return $this->hasMany(NoticeFile::class, 'record_id', 'id');
    }
    public function readers()
    {
        return $this->belongsToMany(User::class, 'notice_readers', 'notice_id', 'user_id')->select(['users.id as id', 'users.name','users.icon_path', 'users.icon_bg']);;
    }
    protected $hidden = [
        'read_users', 'unread_users'
    ];
}
