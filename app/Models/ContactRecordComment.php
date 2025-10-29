<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRecordComment extends Model
{
    protected $guarded = [];
    
    public function user() {
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function files() {
        return $this->hasMany(messageFile::class, 'comment_record_id');
    }
}
