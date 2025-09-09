<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostGrant extends Model
{
    public function post(){
        return $this->belongsTo(PostRecord::class, 'post_record_id');
    }
    
    protected $guarded = [];
}
