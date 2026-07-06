<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityRole extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'capabilities' => 'array',
        'scopes' => 'array',
        'is_system' => 'boolean',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CommunityMembership::class);
    }

    /**
     * Selectable shift types for this role (configurable per community).
     */
    public function shiftTypes(): BelongsToMany
    {
        return $this->belongsToMany(shiftType::class, 'community_role_shift_type', 'community_role_id', 'shift_type_id');
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? [], true);
    }
}
