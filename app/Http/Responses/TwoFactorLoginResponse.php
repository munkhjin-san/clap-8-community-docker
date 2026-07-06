<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\InteractsWithCommunityLogin;
use App\Services\Auth\TrustedDeviceManager;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Post-login response after a successful two-factor challenge (Sanctum migration, Phase 4).
 * Runs the SAME side-effects as a password login (community resolve + account-chooser cookie)
 * via the shared InteractsWithCommunityLogin trait, so 2FA users are not treated differently.
 * See docs/sanctum_migration_footprint.md.
 */
class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use InteractsWithCommunityLogin;

    public function toResponse($request)
    {
        // "Remember this device" — skip the 2FA challenge on this browser next time.
        if ($request->boolean('remember_device')) {
            app(TrustedDeviceManager::class)->remember(Auth::user(), $request);
        }

        return $this->communityLoginResponse($request);
    }
}
