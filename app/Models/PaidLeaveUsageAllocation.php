<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidLeaveUsageAllocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function usage(): BelongsTo
    {
        return $this->belongsTo(PaidLeaveUsage::class, 'paid_leave_usage_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(PaidLeaveGrant::class, 'paid_leave_grant_id');
    }
}
