<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\TwoFactorLoginResponse;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // PHASE 2 (Sanctum/Fortify migration): Fortify now owns POST /login + POST /logout.
        // (Phase 1's Fortify::ignoreRoutes() has been removed so Fortify registers routes.)
        // The colliding laravel/ui POST routes were dropped from routes/web.php; the GET
        // login page + password reset/confirm stay on the legacy controllers until Phase 3.
        // Our post-login side-effects live in App\Http\Responses\LoginResponse, bound in boot().
        // See docs/sanctum_migration_footprint.md.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Post-login redirect + side-effects (community resolve, account-chooser cookie).
        // Bound in boot() so it overrides Fortify's default response bindings. Both the
        // password-login and the 2FA-challenge responses run identical side-effects.
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        // Passkey login runs the same community side-effects as every other path (Phase 8).
        $this->app->singleton(
            \Laravel\Passkeys\Contracts\PasskeyLoginResponse::class,
            \App\Http\Responses\PasskeyLoginResponse::class
        );

        // Trusted devices (Phase 6): define the login pipeline explicitly so it uses OUR 2FA gate
        // (App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable), which skips the challenge when
        // the request carries a valid "remember this device" cookie. authenticateThrough() takes
        // precedence over the default pipeline + the contract binding, so it's order-independent.
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                config('fortify.limiters.login') ? null : \Laravel\Fortify\Actions\EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? \Laravel\Fortify\Actions\CanonicalizeUsername::class : null,
                Features::enabled(Features::twoFactorAuthentication()) ? \App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable::class : null,
                \Laravel\Fortify\Actions\AttemptToAuthenticate::class,
                \Laravel\Fortify\Actions\PrepareAuthenticatedSession::class,
            ]);
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
