<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaidLeaveUsage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'used_on' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaidLeaveAccount::class, 'paid_leave_account_id');
    }

    public function shiftRecord(): BelongsTo
    {
        return $this->belongsTo(shiftRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->select('id', 'name', 'user_code');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaidLeaveUsageAllocation::class);
    }
}
