<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stamp extends Model
{
    protected $fillable = [
        'stampable_type',
        'stampable_id',
        'emote_name',
        'user_id',
    ];

    public function stampable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg', 'deleted_at');
    }
}
