<?php

namespace App\Services\Community;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunityResolver
{
    public const SESSION_KEY = 'active_community_id';

    public function __construct(private CommunityContext $context)
    {
    }

    public function resolveFor(User $user): ?CommunityMembership
    {
        if (!Schema::hasTable('communities') || !Schema::hasTable('community_user')) {
            $this->context->setMembership(null);

            return null;
        }

        $this->ensureDefaultMembership($user);

        $membership = $this->membershipQuery($user)
            ->when(session()->has(self::SESSION_KEY), fn ($query) => $query->where('community_id', session(self::SESSION_KEY)))
            ->first();

        if (!$membership) {
            $membership = $this->membershipQuery($user)
                ->where('is_default', true)
                ->first()
                ?: $this->membershipQuery($user)->first();
        }

        if ($membership) {
            session([self::SESSION_KEY => $membership->community_id]);
            $membership->forceFill(['last_active_at' => now()])->save();
        }

        $this->context->setMembership($membership);

        return $membership;
    }

    public function switch(User $user, int $communityId): CommunityMembership
    {
        $membership = $this->membershipQuery($user)
            ->where('community_id', $communityId)
            ->firstOrFail();

        session([self::SESSION_KEY => $communityId]);
        $membership->forceFill(['last_active_at' => now()])->save();
        $this->context->setMembership($membership);

        return $membership;
    }

    public function ensureDefaultMembership(User $user): void
    {
        if (!Schema::hasTable('communities') || !Schema::hasTable('community_user')) {
            return;
        }

        if ($user->communityMemberships()->exists()) {
            return;
        }

        $community = Community::firstOrCreate(
            ['slug' => Community::DEFAULT_SLUG],
            [
                'name' => Community::DEFAULT_NAME,
                'status' => 'active',
                'config' => ['default' => true],
            ]
        );

        $role = $community->roles()->where('key', $this->legacyRoleKey($user))->first()
            ?: $community->roles()->where('key', 'member')->first();

        DB::table('community_user')->insertOrIgnore([
            'community_id' => $community->id,
            'user_id' => $user->id,
            'community_role_id' => $role?->id,
            'scope' => $this->legacyScope($user),
            'is_default' => true,
            'last_active_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function membershipQuery(User $user)
    {
        return $user->communityMemberships()->with(['community', 'role']);
    }

    private function legacyScope(User $user): string
    {
        return match (true) {
            (int) ($user->partner_flag ?? 0) === 1 => CommunityMembership::SCOPE_PARTNER,
            (int) ($user->position_id ?? 0) === 15 => CommunityMembership::SCOPE_REGISTERED,
            default => CommunityMembership::SCOPE_INTERNAL,
        };
    }

    private function legacyRoleKey(User $user): string
    {
        $positionId = $user->position_id === null ? null : (int) $user->position_id;

        return match (true) {
            in_array((int) $user->id, User::ADMIN_USER_IDS, true) => 'admin',
            (int) ($user->partner_flag ?? 0) === 1 => 'partner',
            $positionId !== null && $positionId < 6 => 'board',
            $positionId === 6 => 'pm',
            $positionId === 15 => 'registered',
            default => 'member',
        };
    }
}
