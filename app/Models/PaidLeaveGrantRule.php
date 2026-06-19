<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidLeaveGrantRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'legal_min_days' => 'float',
        'grant_days' => 'float',
        'active' => 'boolean',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PaidLeavePolicy::class, 'paid_leave_policy_id');
    }
}
