<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaidLeavePolicy extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'effective_from' => 'date',
        'minimum_attendance_rate' => 'float',
        'carryover_enabled' => 'boolean',
        'hourly_leave_enabled' => 'boolean',
        'max_hourly_leave_days_per_year' => 'float',
        'allow_negative_balance' => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(PaidLeaveGrantRule::class)->orderBy('sort_order')->orderBy('service_months');
    }
}
