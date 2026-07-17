<?php

use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Fortify will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    |
    | Here you may specify which password broker Fortify can use when a user
    | is resetting their password. This configured value should match one
    | of your password brokers setup in your "auth" configuration file.
    |
    */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | This value defines which model attribute should be considered as your
    | application's "username" field. Typically, this might be the email
    | address of the users but you are free to change this value here.
    |
    | Out of the box, Fortify expects forgot password and reset password
    | requests to have a field named 'email'. If the application uses
    | another name for the field you may define it below as needed.
    |
    */

    // MISO logs users in by the `login` column, not email. Password reset
    // brokers still key off `email` (users have one). See sanctum_migration_footprint.md.
    'username' => 'login',

    'email' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    |
    | This value defines whether usernames should be lowercased before saving
    | them in the database, as some database system string fields are case
    | sensitive. You may disable this for your application if necessary.
    |
    */

    // Keep false: the legacy laravel/ui flow never lowercased `login`, so
    // lowercasing here would break sign-in for users with mixed-case logins.
    'lowercase_usernames' => false,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    |
    | Here you may configure the path where users will get redirected during
    | authentication or password reset when the operations are successful
    | and the user is authenticated. You are free to change this value.
    |
    */

    'home' => '/', // was RouteServiceProvider::BOARD; that provider was removed in main's Laravel-skeleton modernization (routing folded into bootstrap/app.php)

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    |
    | Here you may specify which prefix Fortify will assign to all the routes
    | that it registers with the application. If necessary, you may change
    | subdomain under which all of the Fortify routes will be available.
    |
    */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify which middleware Fortify will assign to the routes
    | that it registers with the application. If necessary, you may change
    | these middleware but typically this provided default is preferred.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | By default, Fortify will throttle logins to five requests per minute for
    | every email and IP address combination. However, if you would like to
    | specify a custom rate limiter to call then you may specify it here.
    |
    */

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    |
    | Here you may specify if the routes returning views should be disabled as
    | you may not need them when building your own application. This may be
    | especially true if you're writing a custom single-page application.
    |
    */

    // We keep our own Vue/Blade auth screens; Fortify is headless here.
    'views' => false,

    /*
    |--------------------------------------------------------------------------
    | Passkeys
    |--------------------------------------------------------------------------
    |
    | These settings configure Fortify's passkey (WebAuthn) support. Passkeys
    | allow users to sign in without needing to remember credentials since
    | they use public-key cryptography - making them immune to breaches.
    |
    */

    'passkeys' => [
        // The relying-party ID is the registrable host (no scheme/port), e.g. "localhost" or
        // "app.example.com". Defaults to APP_URL's host; override with PASSKEYS_RP_ID if needed.
        'relying_party_id' => env('PASSKEYS_RP_ID', parse_url(config('app.url'), PHP_URL_HOST)),

        // WebAuthn verifies the browser's EXACT origin (scheme + host + port) against this list.
        // Defaults to APP_URL; set PASSKEYS_ALLOWED_ORIGINS to a comma-separated list to allow the
        // exact URL(s) you open the app at (e.g. "http://localhost:8000,https://app.example.com").
        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PASSKEYS_ALLOWED_ORIGINS', (string) config('app.url')))
        ))),

        'timeout' => 60000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Some of the Fortify features are optional. You may disable the features
    | by removing them from this array. You're free to only remove some of
    | these features or you can even remove all of these if you need to.
    |
    */

    // Sanctum/Fortify migration — features are introduced per phase.
    // NOTE: in Phase 1 all Fortify routes are suppressed via Fortify::ignoreRoutes()
    // in App\Providers\FortifyServiceProvider, so nothing here is wired to the app yet.
    'features' => [
        // Registration is intentionally disabled app-wide (Auth::routes(['register' => false])).
        // Features::registration(),

        // Phase 3 — enable these together with retiring the legacy laravel/ui
        // password routes. Enabling now would register Fortify routes named
        // `password.email` / `password.update` that collide with the ui ones
        // still active in routes/web.php during Phase 2.
        // Features::resetPasswords(),
        // Features::emailVerification(),
        // Features::updatePasswords(),

        // App already owns profile editing; leave Fortify's out to avoid overlap.
        // Features::updateProfileInformation(),

        // Phase 4 — TOTP + recovery codes. `confirm` => user must verify a code before 2FA
        // activates. `confirmPassword` => false for now: true would attach the `password.confirm`
        // middleware which redirects to the (removed) password.confirm view route on non-JSON
        // requests. Re-enable once a password-confirmation step exists in the SPA.
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]),

        // Phase 8 — passkeys / WebAuthn (laravel/passkeys via Fortify). confirmPassword=false
        // keeps `password.confirm` (a removed view route) off the management endpoints; the SPA
        // is session-authed. Relying-party id + origins come from the `passkeys` config block above
        // (derived from APP_URL) — make sure APP_URL matches the host the SPA is served from.
        Features::passkeys([
            'confirmPassword' => false,
        ]),
    ],

];
