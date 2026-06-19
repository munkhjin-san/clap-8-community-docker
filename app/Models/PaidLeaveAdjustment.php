<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidLeaveAdjustment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'adjusted_on' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaidLeaveAccount::class, 'paid_leave_account_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(PaidLeaveGrant::class, 'paid_leave_grant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->select('id', 'name', 'user_code');
    }
}
