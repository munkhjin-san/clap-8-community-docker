<?php

namespace App\Services\Community;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class CommunityContext
{
    private ?CommunityMembership $membership = null;

    public function setMembership(?CommunityMembership $membership): void
    {
        $this->membership = $membership?->loadMissing(['community', 'role']);
    }

    public function membership(): ?CommunityMembership
    {
        return $this->membership;
    }

    public function community(): ?Community
    {
        return $this->membership?->community;
    }

    public function communityId(): ?int
    {
        return $this->membership?->community_id;
    }

    /**
     * The user ids that belong to the active community (via the community_user
     * pivot). Returns null when there is no active community, so callers can tell
     * "no context, do not confine" apart from "active community has no members".
     *
     * Use this as the single source of truth whenever a user-id list is built to
     * filter community-scoped data through an UNSCOPED base/join. `User` is not
     * community-scoped (membership lives in the pivot, not a column), so a list
     * from `User::where(...)` or from request input would otherwise span every
     * community.
     */
    public function userIds(): ?array
    {
        $communityId = $this->communityId();

        if (!$communityId) {
            return null;
        }

        return CommunityMembership::query()
            ->where('community_id', $communityId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Narrow a candidate list of user ids to those in the active community.
     * Pass-through (returns the input unchanged) when there is no active
     * community, matching the global scope's fail-open behaviour.
     *
     * @param  array<int|string>  $ids
     * @return array<int>
     */
    public function confineUserIds(array $ids): array
    {
        $candidates = array_values(array_unique(array_map('intval', $ids)));

        $communityUserIds = $this->userIds();

        if ($communityUserIds === null) {
            return $candidates;
        }

        return array_values(array_intersect($candidates, $communityUserIds));
    }

    public function roleKey(): ?string
    {
        return $this->membership?->role?->key;
    }

    public function scope(): ?string
    {
        return $this->membership?->scope;
    }

    public function capabilities(): array
    {
        return $this->membership?->capabilities() ?? [];
    }

    public function can(string $capability): bool
    {
        // Admin is the fixed super role: it bypasses every capability gate.
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($capability, $this->capabilities(), true);
    }

    public function isPartner(): bool
    {
        return $this->membership?->isPartner() ?? false;
    }

    public function isRegistered(): bool
    {
        return $this->membership?->isRegistered() ?? false;
    }

    public function isAdmin(): bool
    {
        return $this->membership?->isAdmin() ?? false;
    }

    public function isBoss(): bool
    {
        return $this->membership?->isBoss() ?? false;
    }

    public function isPM(): bool
    {
        return $this->membership?->isPM() ?? false;
    }

    public function authPayload(User $user): array
    {
        if (!Schema::hasTable('communities') || !Schema::hasTable('community_user')) {
            return [
                'active_community' => null,
                'active_membership' => null,
                'communities' => [],
                'community_scope' => null,
                'community_role' => null,
                'community_capabilities' => [],
            ];
        }

        $memberships = $user->communityMemberships()
            ->with(['community:id,name,slug,status,config', 'role:id,key,name,capabilities'])
            ->get();

        return [
            'active_community' => $this->community(),
            'active_membership' => $this->membership,
            'communities' => $memberships->map(fn (CommunityMembership $membership) => [
                'id' => $membership->community?->id,
                'name' => $membership->community?->name,
                'slug' => $membership->community?->slug,
                'status' => $membership->community?->status,
                'config' => $membership->community?->config ?? [],
                'scope' => $membership->scope,
                'is_default' => $membership->is_default,
                'role' => $membership->role ? [
                    'id' => $membership->role->id,
                    'key' => $membership->role->key,
                    'name' => $membership->role->name,
                    'capabilities' => $membership->role->capabilities ?? [],
                    'scopes' => $membership->role->scopes ?? [],
                ] : null,
            ])->values(),
            'community_scope' => $this->scope(),
            'community_role' => $this->membership?->role,
            'community_capabilities' => $this->capabilities(),
        ];
    }
}
