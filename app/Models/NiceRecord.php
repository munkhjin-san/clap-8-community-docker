<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NiceRecord extends Model
{
    use HasFactory;
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_id', 'a_path', 'a_version');
    }
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'nice_use_files', 'record_id', 'file_id')->where('file_records.deleted_flag', 0);
    }
    public function tags()
    {
        return $this->belongsToMany(TagRecord::class, 'nice_use_tags', 'record_id', 'tag_id')->where('tag_records.deleted_flag', 0);
    }
    public function comment_records(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'nice')->with('user');
    }
    public function to_users(){
        return $this->belongsToMany(User::class, 'nice_to_users', 'user_id', 'record_id')->withPivot('id')->select(['users.id as id', 'users.name', 'users.a_version', 'users.a_path']);
    }
}
