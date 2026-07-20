<?php

namespace App\Services\Community;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunityResolver
{
    private static ?bool $communityTablesExist = null;

    private static function communityTablesExist(): bool
    {
        return self::$communityTablesExist ??= (Schema::hasTable('communities') && Schema::hasTable('community_user'));
    }

    public function __construct(private CommunityContext $context)
    {
    }

    public function resolveFor(User $user): ?CommunityMembership
    {
        if (!self::communityTablesExist()) {
            $this->context->setMembership(null);

            return null;
        }

        $this->ensureDefaultMembership($user);

        $membership = $this->membershipFor($user);

        if ($membership) {
            $membership->forceFill(['last_active_at' => now()])->save();
        }

        $this->context->setMembership($membership);

        return $membership;
    }

    /**
     * Resolve a user's active membership WITHOUT any side-effects — no DB write,
     * no CommunityContext mutation, no membership creation. Safe to call for any
     * user (incl. non-acting users, in loops), which is what per-user permission
     * checks need.
     *
     * The active community is stored GLOBALLY on the pivot (community_user.is_default),
     * not in the session — so the same account resolves to the same community in
     * every browser/device. Resolution order: the user's default membership, else
     * their lowest-id membership. Returns null if the user has none.
     */
    public function membershipFor(User $user): ?CommunityMembership
    {
        if (!self::communityTablesExist()) {
            return null;
        }

        return $this->membershipQuery($user)
            ->orderByDesc('is_default')
            ->orderBy('community_id')
            ->first();
    }

    public function switch(User $user, int $communityId): CommunityMembership
    {
        $membership = $this->membershipQuery($user)
            ->where('community_id', $communityId)
            ->firstOrFail();

        // Persist the choice globally: exactly one default per user. Any browser
        // this account opens next will resolve to this community.
        DB::transaction(function () use ($user, $membership) {
            $user->communityMemberships()
                ->where('is_default', true)
                ->where('community_id', '!=', $membership->community_id)
                ->update(['is_default' => false]);
            $membership->forceFill(['is_default' => true, 'last_active_at' => now()])->save();
        });

        $this->context->setMembership($membership);

        return $membership;
    }

    public function ensureDefaultMembership(User $user): void
    {
        if (!self::communityTablesExist()) {
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
