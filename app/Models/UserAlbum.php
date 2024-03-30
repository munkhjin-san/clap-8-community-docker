<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAlbum extends Model
{
    use SoftDeletes;
    use HasFactory;
    public function tags()
    {
        return $this->belongsToMany(TagRecord::class, 'user_use_tags', 'album_id', 'tag_id')->where('tag_records.deleted_flag', 0);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected $fillable = [
        'title'
    ];
}
