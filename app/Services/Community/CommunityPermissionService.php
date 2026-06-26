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

    public function can(string $capability, ?User $user = null): bool
    {
        if ($user) {
            $this->resolver->resolveFor($user);
        }

        return $this->context->can($capability);
    }

    public function isAdmin(?User $user = null): bool
    {
        if ($user) {
            $this->resolver->resolveFor($user);

            if (!$this->context->membership()) {
                return in_array((int) $user->id, User::ADMIN_USER_IDS, true);
            }
        }

        return $this->context->isAdmin();
    }

    public function isBoss(?User $user = null): bool
    {
        if ($user) {
            $this->resolver->resolveFor($user);

            if (!$this->context->membership()) {
                return $user->position_id !== null && (int) $user->position_id < 6;
            }
        }

        return $this->context->isBoss();
    }

    public function isPM(?User $user = null): bool
    {
        if ($user) {
            $this->resolver->resolveFor($user);

            if (!$this->context->membership()) {
                return (int) $user->position_id === 6;
            }
        }

        return $this->context->isPM();
    }

    public function isPartner(?User $user = null): bool
    {
        if ($user) {
            $this->resolver->resolveFor($user);

            if (!$this->context->membership()) {
                return (int) $user->partner_flag === 1;
            }
        }

        return $this->context->isPartner();
    }

    public function isRegistered(?User $user = null): bool
    {
        if ($user) {
            $this->resolver->resolveFor($user);

            if (!$this->context->membership()) {
                return (int) $user->position_id === 15;
            }
        }

        return $this->context->isRegistered();
    }
}
