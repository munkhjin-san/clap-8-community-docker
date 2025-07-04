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
    protected $guarded = [];
}
