<?php

namespace App\Http\Responses\Concerns;

use App\Http\Controllers\AccountChooserController;
use App\Services\Community\CommunityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Shared post-login side-effects for the Fortify cutover (Sanctum migration).
 *
 * Ported from the legacy LoginController::authenticated() and used by BOTH
 * LoginResponse (password login) and TwoFactorLoginResponse (after the TOTP /
 * recovery-code challenge) so a 2FA login resolves the active community and
 * appends to the account-chooser cookie exactly like a normal login.
 *
 * See docs/sanctum_migration_footprint.md.
 */
trait InteractsWithCommunityLogin
{
    protected function communityLoginResponse($request)
    {
        $user = Auth::user();

        app(CommunityResolver::class)->resolveFor($user);

        $cookieValue = $request->cookie(AccountChooserController::COOKIE_NAME, '[]');
        $ids = json_decode((string) $cookieValue, true);
        $ids = is_array($ids) ? $ids : [];
        $ids[] = (int) $user->id;

        cookie()->queue(cookie(
            AccountChooserController::COOKIE_NAME,
            json_encode(array_values(array_unique(array_map('intval', $ids)))),
            60 * 24 * 180,
            null,
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(config('fortify.home'));
    }
}
