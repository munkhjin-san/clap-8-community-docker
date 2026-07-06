<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A browser/device a user has chosen to trust, so it can skip the 2FA challenge
 * until `expires_at`. Only the sha256 hash of the cookie token is stored.
 */
class UserTrustedDevice extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'device_name',
        'ip_address',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
