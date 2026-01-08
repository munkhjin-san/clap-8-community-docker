<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPlanLock extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];
}

