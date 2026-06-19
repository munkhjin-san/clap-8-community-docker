<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaidLeaveGrant extends Model
{
    use HasFactory;

    public const TYPE_ANNUAL = 'annual';
    public const TYPE_OPENING_BALANCE = 'opening_balance';

    protected $guarded = [];

    protected $casts = [
        'granted_at' => 'date',
        'expires_at' => 'date',
        'grant_days' => 'float',
        'policy_snapshot' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaidLeaveAccount::class, 'paid_leave_account_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(PaidLeavePolicy::class, 'paid_leave_policy_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->select('id', 'name', 'user_code');
    }

    public function usageAllocations(): HasMany
    {
        return $this->hasMany(PaidLeaveUsageAllocation::class);
    }
}
