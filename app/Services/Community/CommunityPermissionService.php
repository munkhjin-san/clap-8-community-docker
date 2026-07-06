<?php

namespace App\Services\Community;

use App\Models\User;

class CommunityPermissionService
{
    public function __construct(
        private CommunityContext $context,
        private CommunityResolver $resolver
    ) {
    }

    // Per-user checks resolve the user's membership WITHOUT side-effects
    // (resolver->membershipFor), so checking another user — e.g. in a loop —
    // never mutates the acting user's session/active community. Role predicates
    // are membership-authoritative: the legacy position_id/partner_flag fallbacks
    // were retired (2026-07-04) now that every user is guaranteed a membership —
    // a membership-less user has no role, hence no privilege. The ONLY remaining
    // fallback is the admin id break-glass (ADMIN_USER_IDS) below/in isAdmin(),
    // kept to prevent admin lockout if a membership is ever missing/corrupted.
    // With no $user, fall back to the active CommunityContext as before.

    public function can(string $capability, ?User $user = null): bool
    {
        if ($user) {
            $membership = $this->resolver->membershipFor($user);

            if (!$membership) {
                return in_array((int) $user->id, User::ADMIN_USER_IDS, true);
            }

            return $membership->isAdmin() || in_array($capability, $membership->capabilities(), true);
        }

        return $this->context->can($capability);
    }

    public function isAdmin(?User $user = null): bool
    {
        if ($user) {
            $membership = $this->resolver->membershipFor($user);

            if (!$membership) {
                return in_array((int) $user->id, User::ADMIN_USER_IDS, true);
            }

            return $membership->isAdmin();
        }

        return $this->context->isAdmin();
    }

    public function isBoss(?User $user = null): bool
    {
        if ($user) {
            // Role-authoritative. Every user is guaranteed a membership (created
            // on account creation + ensured by resolveFor); a membership-less user
            // has no role, so no privilege. (Position fallback retired 2026-07-04.)
            return $this->resolver->membershipFor($user)?->isBoss() ?? false;
        }

        return $this->context->isBoss();
    }

    public function isPM(?User $user = null): bool
    {
        if ($user) {
            return $this->resolver->membershipFor($user)?->isPM() ?? false;
        }

        return $this->context->isPM();
    }

    public function isPartner(?User $user = null): bool
    {
        if ($user) {
            return $this->resolver->membershipFor($user)?->isPartner() ?? false;
        }

        return $this->context->isPartner();
    }

    public function isRegistered(?User $user = null): bool
    {
        if ($user) {
            return $this->resolver->membershipFor($user)?->isRegistered() ?? false;
        }

        return $this->context->isRegistered();
    }
}
