<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveActivityLog extends Model
{
    protected $fillable = [
        'item_id',
        'item_type',
        'item_name',
        'project_id',
        'user_id',
        'action',
        'from_path',
        'to_path',
        'size_bytes',
        'client_ip',
        'user_agent',
        'referer',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
