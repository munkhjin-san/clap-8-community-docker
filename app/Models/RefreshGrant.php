<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshGrant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'granted_at' => 'date',
        'expires_at' => 'date',
    ];

    public function refreshAccount()
    {
        return $this->belongsTo(RefreshAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function expirations()
    {
        return $this->hasMany(RefreshExpiration::class);
    }

    public function usageAllocations()
    {
        return $this->hasMany(RefreshUsageAllocation::class, 'refresh_grant_id');
    }
}
