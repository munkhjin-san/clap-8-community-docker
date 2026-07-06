<?php

namespace App\Actions\Fortify;

use App\Services\Auth\EmailOtpService;
use App\Services\Auth\TrustedDeviceManager;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Extends Fortify's 2FA gate (Sanctum migration Phase 6) to honor trusted devices.
 *
 * Bound to the RedirectsIfTwoFactorAuthenticatable contract in FortifyServiceProvider,
 * so the login pipeline resolves this instead of Fortify's default. If the request
 * carries a valid "remember this device" cookie for the authenticating user, we skip
 * the 2FA challenge and let authentication proceed; otherwise we replicate Fortify's
 * default decision exactly.
 */
class RedirectIfTwoFactorAuthenticatable extends FortifyRedirectIfTwoFactorAuthenticatable
{
    public function handle($request, $next)
    {
        // validateCredentials() (parent, protected) verifies the password and throws on
        // failure, exactly as Fortify does — called once here for the gate decision.
        $user = $this->validateCredentials($request);

        // Trusted browser → skip any second factor.
        if (app(TrustedDeviceManager::class)->isTrusted($request, $user)) {
            return $next($request);
        }

        // Authenticator app (TOTP) takes precedence when configured.
        if ($this->userHasTotp($user)) {
            return $this->twoFactorChallengeResponse($request, $user);
        }

        // Email OTP — emailed one-time code.
        if ($this->userHasEmailOtp($user)) {
            return $this->emailOtpChallengeResponse($request, $user);
        }

        return $next($request);
    }

    protected function userHasTotp($user): bool
    {
        if (! $user || ! in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user))) {
            return false;
        }

        if (empty($user->two_factor_secret)) {
            return false;
        }

        return Fortify::confirmsTwoFactorAuthentication()
            ? ! is_null($user->two_factor_confirmed_at)
            : true;
    }

    protected function userHasEmailOtp($user): bool
    {
        return $user
            && ! is_null($user->email_otp_enabled_at)
            && ($user->email || $user->work_email);
    }

    protected function emailOtpChallengeResponse($request, $user)
    {
        app(EmailOtpService::class)->send($user);

        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);

        return $request->wantsJson()
            ? response()->json(['email_otp' => true])
            : redirect()->route('email-otp.challenge');
    }
}
