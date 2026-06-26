<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CommunityMembership extends Pivot
{
    public const SCOPE_INTERNAL = 'internal';
    public const SCOPE_PARTNER = 'partner';
    public const SCOPE_REGISTERED = 'registered';
    public const SCOPE_EXTERNAL = 'external';

    protected $table = 'community_user';

    public $incrementing = true;

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(CommunityRole::class, 'community_role_id');
    }

    public function hasCapability(string $capability): bool
    {
        return $this->role?->hasCapability($capability) ?? false;
    }
}
