<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostEntry extends Model
{
    public function files(){
        return $this->belongsToMany(FileRecord::class, 'post_entry_use_files', 'record_id', 'file_id')->where('file_records.deleted_flag', 0);
    }
    public function user(){
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }
    public function comments(){
        return $this->hasMany(CommentRecord::class, 'record_id')->where('app_name', 'post_entry')->where('deleted_flag', 0);
    }
    public function claps(){
        return $this->hasMany(ClapRecord::class, 'record_id')->where('app_name', 'post_entry')->where('deleted_flag', 0)->select('record_id', 'from_user');;
    }
    public function post(){
        return $this->belongsTo(PostRecord::class, 'post_record_id');
    }

    protected $guarded = [];
}
