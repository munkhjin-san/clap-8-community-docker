<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusLog extends Model
{
    protected $fillable = [
        'type',
        'user_id',
        'record_id',
        'before_number',
        'after_number',
        'before_text',
        'after_text',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'icon_path', 'icon_bg');
    }
}
