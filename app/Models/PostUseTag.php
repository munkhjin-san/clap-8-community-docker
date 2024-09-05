<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostUseTag extends Model
{
    use HasFactory;

    public function app_record(){
        return $this->belongsTo(PostRecord::class, 'record_id', 'id');
    }
}
