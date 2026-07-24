<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowNotificationPref extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
