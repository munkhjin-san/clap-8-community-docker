<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaidLeaveAccount extends Model
{
    use BelongsToCommunity;

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'joined_date' => 'date',
        'active' => 'boolean',
        'last_granted_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'source_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(PaidLeaveGrant::class)->orderBy('granted_at');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PaidLeaveUsage::class)->orderByDesc('used_on');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PaidLeaveAdjustment::class)->orderByDesc('adjusted_on');
    }
}
