<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditDailyDigest extends Model
{
    protected $fillable = [
        'digest_date',
        'first_event_hash',
        'last_event_hash',
        'event_count',
        'digest_hash',
        'sealed_at',
    ];

    protected $casts = [
        'digest_date' => 'date',
        'sealed_at' => 'datetime',
        'event_count' => 'int',
    ];
}
