<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReadHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    public function readable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
