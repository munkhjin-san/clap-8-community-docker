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
        return $this->membership?->role?->capabilities ?? [];
    }

    public function can(string $blade): bool
    {
        // Admin is the fixed super role: it bypasses every blade gate.
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($blade, $this->capabilities(), true);
    }

    public function isPartner(): bool
    {
        return $this->roleKey() === 'partner' || $this->scope() === CommunityMembership::SCOPE_PARTNER;
    }

    public function isRegistered(): bool
    {
        return $this->roleKey() === 'registered' || $this->scope() === CommunityMembership::SCOPE_REGISTERED;
    }

    public function isAdmin(): bool
    {
        return $this->roleKey() === 'admin';
    }

    public function isBoss(): bool
    {
        return $this->roleKey() === 'board' || in_array('project.approve', $this->capabilities(), true);
    }

    public function isPM(): bool
    {
        return $this->roleKey() === 'pm';
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
