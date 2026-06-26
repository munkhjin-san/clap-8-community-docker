<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Community extends Model
{
    use HasFactory, SoftDeletes;

    public const DEFAULT_SLUG = 'glowd';
    public const DEFAULT_NAME = 'グラウド株式会社';

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(CommunityRole::class)->orderBy('sort_order');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CommunityMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_user')
            ->using(CommunityMembership::class)
            ->withPivot(['community_role_id', 'scope', 'is_default', 'last_active_at'])
            ->withTimestamps();
    }
}
