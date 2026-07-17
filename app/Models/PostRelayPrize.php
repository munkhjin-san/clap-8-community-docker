<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostRelayPrize extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'prize' => 'integer',
        'try_flag' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name', 'icon_path', 'icon_bg');
    }

    public function rootPost()
    {
        return $this->belongsTo(PostRecord::class, 'root_post_id');
    }
}
