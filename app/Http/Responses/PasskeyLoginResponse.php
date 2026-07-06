<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\InteractsWithCommunityLogin;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

/**
 * Post-login response after a successful passkey (WebAuthn) login (Sanctum migration Phase 8).
 * Runs the same side-effects as every other login path (community resolve + account-chooser
 * cookie) via the shared trait. See docs/sanctum_migration_footprint.md.
 */
class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    use InteractsWithCommunityLogin;

    public function toResponse($request)
    {
        return $this->communityLoginResponse($request);
    }
}
