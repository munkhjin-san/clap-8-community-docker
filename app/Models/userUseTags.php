<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userUseTags extends Model
{
    use HasFactory;
    public function user_album(){
        return $this->belongsTo(UserAlbum::class, 'album_id', 'id');
    }
}
