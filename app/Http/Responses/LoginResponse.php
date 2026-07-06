<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\InteractsWithCommunityLogin;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Post-login response for password authentication (Sanctum migration, Phase 2).
 * Side-effects (community resolve + account-chooser cookie) live in the shared
 * InteractsWithCommunityLogin trait. See docs/sanctum_migration_footprint.md.
 */
class LoginResponse implements LoginResponseContract
{
    use InteractsWithCommunityLogin;

    public function toResponse($request)
    {
        return $this->communityLoginResponse($request);
    }
}
