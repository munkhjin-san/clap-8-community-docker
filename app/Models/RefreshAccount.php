<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Model;

class RefreshAccount extends Model
{
    use BelongsToCommunity;

    protected $guarded = [];

    protected $casts = [
        'joined_date' => 'date',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grants()
    {
        return $this->hasMany(RefreshGrant::class)->orderByDesc('granted_at')->orderByDesc('id');
    }

    public function expirations()
    {
        return $this->hasMany(RefreshExpiration::class)->orderByDesc('expired_at')->orderByDesc('id');
    }

    public function usages()
    {
        return $this->hasMany(RefreshUsage::class)->orderByDesc('used_at')->orderByDesc('id');
    }

    public function annualReviews()
    {
        return $this->hasMany(RefreshAnnualReview::class)->orderByDesc('grant_year')->orderByDesc('id');
    }
}
