<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshUsage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'used_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function refreshAccount()
    {
        return $this->belongsTo(RefreshAccount::class);
    }

    public function post()
    {
        return $this->belongsTo(PostRecord::class, 'post_record_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function allocations()
    {
        return $this->hasMany(RefreshUsageAllocation::class, 'refresh_usage_id');
    }
}
