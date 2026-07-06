# Sanctum Migration Footprint

> **Purpose:** A tracing log for dropping `laravel/ui` and standardizing auth on
> **Sanctum (session/SPA) + Fortify (auth actions & MFA)**. This is the "future me & you"
> reference — read this FIRST before touching any auth file. Update the trace table and
> decision log as you go. Goal is incremental and reversible, not a big-bang rewrite.

- **Branch:** `community_logic`
- **Started:** 2026-06-26
- **Status:** PLANNING — recon complete, no code changed yet
- **Laravel:** 13.17.0 · PHP/Sanctum 4.0 · still on legacy `App\Http\Kernel`
- **Auth transport decision:** session/cookie SPA mode (see §6) — NOT api.php tokens.

---

## 1. Why / End State

| | Now | Target |
|---|---|---|
| SPA session auth | Sanctum (stateful, guard `web`) ✅ | keep |
| Login/logout/reset/verify | `laravel/ui` `Auth::routes()` + custom controllers | **Fortify** (headless endpoints, our Vue/Blade screens) |
| 2FA / recovery codes | none | **Fortify TwoFactorAuthentication** |
| Native/mobile auth | `/sanctum/token` personal access tokens | keep (unchanged) |
| `laravel/ui` package | present | **removed (last step)** |

Final stack: **Vue/Blade UI → Fortify endpoints → Sanctum SPA session → `auth:sanctum` / policies**.

---

## 2. Audited Current State (file:line — verify before editing)

- `routes/web.php:116` — `Auth::routes(['register' => false]);` ← the laravel/ui entrypoint to replace.
- `app/Http/Controllers/Auth/` — custom controllers using framework traits:
  - `LoginController.php` (`use AuthenticatesUsers`) — **has critical overrides** (see §5).
  - `RegisterController`, `ResetPasswordController`, `ForgotPasswordController`,
    `ConfirmPasswordController`, `VerificationController`, `PhoneVerificationController`.
- **Username field = `login`** (not email) → `LoginController::username()` returns `'login'`.
- `LoginController::authenticated()` does TWO things we must preserve:
  1. `app(CommunityResolver::class)->resolveFor($user)` — community context bootstrap.
  2. Account-chooser cookie (`AccountChooserController::COOKIE_NAME`, multi-account list).
- `config/auth.php` — guard `web` = session, provider `users` = `App\Models\User`.
- `config/sanctum.php` — `stateful` from `SANCTUM_STATEFUL_DOMAINS`, `guard => ['web']`.
- `app/Models/User.php` — `use HasApiTokens, Notifiable, SoftDeletes;` (NO TwoFactor trait yet).
- `routes/api.php:26` — `POST /sanctum/token` issues personal access tokens to native client
  (validates `login` + `password` + `device_name`). **Out of scope — do not break.**
- Google Socialite login (`GoogleController`) at `routes/web.php:129-130`.
- `bootstrap/app.php` — legacy kernel structure (`App\Http\Kernel`). Middleware lives in
  `app/Http/Kernel.php`, NOT in `bootstrap/app.php` (matters for `guest`/`auth` aliases).

### Recon findings (2026-06-26 — the details that make this a drop-in)

- **Login is a classic HTML form POST**, not XHR: `resources/js/components/Auth/LoginComponent.vue`
  → `<form action="/login" method="post">` with `name="login"`, `name="password"`, hidden
  `_token` (CSRF from `<meta name="csrf-token">` in `resources/views/layouts/app.blade.php:10`).
  The Blade `resources/views/auth/login.blade.php` just mounts `<login>`.
- **Logout is also a form POST**: hidden `#logout-form` → `route('logout')` with `@csrf`
  (`resources/views/layouts/app.blade.php:57`). ⇒ Fortify's redirect responses fit both as-is.
- **Everything else = axios session XHR**: `resources/js/bootstrap.ts` sets
  `X-Requested-With: XMLHttpRequest`; on HTTP 401 it redirects to `/login`. No bearer tokens.
  Same-origin, so cookie + CSRF "just works" (axios auto-sends `X-XSRF-TOKEN`).
- **All Auth controllers except Login are vanilla trait shells** — `Register` & `PhoneVerification`
  are EMPTY, `ForgotPassword`/`ResetPassword`/`ConfirmPassword`/`Verification` only use trait shells
  + `redirectTo = BOARD`. The ONLY real custom logic to preserve is `LoginController::authenticated()`.
  ⚠️ CORRECTION (found in P5): those `Illuminate\Foundation\Auth\*` ACTION TRAITS
  (`AuthenticatesUsers`, `RegistersUsers`, `ResetsPasswords`, `SendsPasswordResetEmails`,
  `VerifiesEmails`, `ConfirmsPasswords`) ship with **laravel/ui**, NOT laravel/framework — so these
  controllers DID depend on laravel/ui after all. (The base `Illuminate\Foundation\Auth\User`,
  `Access\AuthorizesRequests`, and `EmailVerificationRequest` ARE framework — those are unaffected.)
- **`users` columns** (live schema): `id, login, name, email, email_verified_at, work_email,
  password, remember_token, phone_number, retire, retire_date, on_leave, deleted_flag,
  default_community_id, google_token, …`. ⇒ email + remember-me + email-verification all viable;
  retire/on_leave/deleted_flag enable the "block inactive users" policy.
- **Missing for MFA:** `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`
  (none exist → one migration in Phase 4).
- **`config/auth.php`** already has non-standard `providers.users.username = 'login'` and password
  reset `table => 'password_resets'` (older name, not `password_reset_tokens`).
- **No auth tests** anywhere under `tests/` → add a smoke test as the Phase 0 safety net.
- **`RouteServiceProvider::BOARD = '/'`** (post-login redirect target).

---

## 3. Phase Plan (implementation-ready)

Each phase = one commit, independently revertable. `laravel/ui` stays installed until Phase 5,
so any earlier revert restores the original `Auth::routes()` flow intact.

### Phase 0 — Safety net (do FIRST, no behavior change) — ✅ DONE 2026-06-26
- [x] Write `tests/Feature/Auth/LoginTest.php` baseline (GET /login renders; POST /login success →
      redirect `/` + authenticated + account-chooser cookie; bad creds → errors + guest; logout →
      guest). 4 tests PASS; full Feature suite 15 PASS. (CommunityResolver no-ops without community
      schema, so it's exercised separately by CommunityLogicTest — see test docblock.)
- [x] Record env: `APP_URL=http://localhost`, `SESSION_DRIVER=file`; `SESSION_DOMAIN` unset,
      `SANCTUM_STATEFUL_DOMAINS` unset (falls back to sanctum default localhost list).
- [ ] ~~Checkpoint commit/tag~~ — DEFERRED to user (working tree has unrelated uncommitted WIP;
      do not sweep into an auth checkpoint).
- [~] `php artisan route:list` — **BLOCKED by pre-existing bug**: `routes/web.php:21,318-325`
      reference `App\Http\Controllers\AdminCostMasterController` which does NOT exist (committed on
      branch, not from this work). Breaks `route:list` + route caching; live requests unaffected.
      Behavioral route baseline captured via the passing tests instead. **Must fix before Phase 1**
      because Phase 1 verifies Fortify routes with `route:list`. Flagged as a separate task.

### Phase 1 — Install Fortify headless (NO routes registered yet) — ✅ DONE 2026-06-26
- [x] `composer require laravel/fortify` (^1.37).
- [x] `php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"`.
- [x] Deleted the published 2FA migration (→ P4) + passkeys migration (out of scope) to keep
      P1 schema-neutral.
- [x] Register `App\Providers\FortifyServiceProvider` in `config/app.php` providers array.
- [x] `config/fortify.php`: `guard=web`, `passwords=users`, `username=login`, `email=email`,
      `lowercase_usernames=false`, `home=RouteServiceProvider::BOARD`, `views=false`;
      features trimmed → `resetPasswords` + `updatePasswords` kept (for P3); registration / 2FA /
      passkeys / updateProfileInformation OFF.
- [x] **CONFLICT GUARD resolved by `Fortify::ignoreRoutes()`** in the provider's `register()`:
      Phase 1 registers ZERO Fortify routes, so laravel/ui's `Auth::routes()` stays the sole auth
      owner and behavior is byte-identical. The cutover (remove `ignoreRoutes()` + retire the
      colliding ui routes) is Phase 2. (Chosen over prefixing — no artificial URIs, truly no-op.)
- [x] Verified: app boots, 0 Fortify routes, `login`/`logout` still → ui LoginController,
      Feature suite 15 green. Route:list still blocked by the unrelated AdminCostMasterController
      bug, so verification used a router-introspection script instead.

### Phase 2 — Cut login + logout over to Fortify — ✅ DONE 2026-06-26
- [x] Ported `LoginController::authenticated()` into `App\Http\Responses\LoginResponse`
      (CommunityResolver + account-chooser cookie + redirect to `fortify.home`), bound in
      `FortifyServiceProvider::boot()`.
- [x] Relies on Fortify's default pipeline (auths by `login`+`password`; `lowercase_usernames=false`
      so no canonicalization). Rate limiting via `throttle:login` route middleware + the `login`
      limiter already defined in the provider.
- [x] Replaced `Auth::routes(['register=>false])` (all-or-nothing macro) with explicit routes:
      kept GET /login + ui password reset/confirm; **dropped** POST /login + POST /logout so Fortify
      owns them. No front-end change (form posts to `/login`; `route('logout')` → Fortify `logout`).
- [x] Removed `Fortify::ignoreRoutes()`.
- [x] Verified via router introspection + Phase 0 suite (15 passed). See trace + verification note.
- [ ] DEFERRED to a later step: "inactive user" login block (retire/on_leave/deleted_flag) — add as
      a Fortify `authenticateUsing`/pipeline check. Not done in P2 to keep the cutover behavior-pure.
- [ ] TODO before deploy: confirm Google Socialite path (`GoogleController`) still bypasses Fortify
      (no test coverage; verify manually) + real-browser CSRF/cookie roundtrip.

### Phase 3 — Retire dead password flows — ✅ DONE 2026-06-26
DECISION (user, 2026-06-26): the email "forgot password" flow was unreachable (no login-page link)
and broken (`email.blade` form had no action). **Retired** rather than rebuilt on Fortify.
Self-service password change already exists & is custom (`UserController@passChange`,
`POST /user_pass_change_api`); admin reset is in the user-management UI. Password confirmation
(`RequirePassword`) was applied to no route. So nothing was migrated TO Fortify here — the dead
laravel/ui-era scaffolding was removed instead.
- [x] Removed password reset (4) + confirm (2) routes from routes/web.php (kept GET /login).
- [x] Deleted `ForgotPasswordController`, `ResetPasswordController`, `ConfirmPasswordController`.
- [x] Deleted `auth/passwords/{email,reset,confirm}.blade.php` + the `passwords/` dir.
- [N/A] Email verification not migrated — it's custom (commented-out `VerificationController@show`
      + Twilio phone verification), not Fortify-shaped. `VerificationController` was deleted in P5
      (broke on ui removal, unrouted).

### Phase 5 — Remove laravel/ui — ✅ DONE 2026-06-26 (merged into P3)
- [x] Confirmed zero `Laravel\Ui` namespace refs; no `HomeController`; no laravel-mix/bootstrap.
- [x] `composer remove laravel/ui`.
- [x] ⚠️ This BROKE `LoginController`/`RegisterController`/`VerificationController` — the
      `Illuminate\Foundation\Auth\*` action traits are provided by laravel/ui, not the framework
      (corrected assumption; see §2). The P0 LoginTest caught it immediately.
- [x] Rewrote `LoginController` minimal (renders `auth.login`, no trait); deleted the unrouted
      `RegisterController` + `VerificationController`.
- [x] Full Feature suite 15 green; app boots; login/logout routes resolve.
- Kept `PhoneVerificationController` (empty, no trait).

### Phase 4 — Two-Factor Authentication (TOTP + recovery codes) — BACKEND ✅ DONE 2026-06-26
- [x] Migration adds `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`.
      ✅ RUN on this DB 2026-06-26 (`php artisan migrate`). Remember to run it on other envs/prod.
- [x] `User` model: `use Laravel\Fortify\TwoFactorAuthenticatable;` + hid the two secret columns.
- [x] Enabled `Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => false])`.
- [x] Custom `TwoFactorLoginResponse` (shares `InteractsWithCommunityLogin` with `LoginResponse`)
      so a 2FA login resolves community + sets the account-chooser cookie identically.
- [x] Interim standalone challenge page + `GET /two-factor-challenge` (name `two-factor.login`).
- [x] `tests/Feature/Auth/TwoFactorTest.php` (4 tests) — full Feature suite 19 green.
- [x] **Settings UI DONE 2026-06-26** — added a "二段階認証" section to
      `resources/js/components/Settings/Settings.vue` (new step 8): enable → shows QR
      (`GET /user/two-factor-qr-code`) + manual key (`/user/two-factor-secret-key`) + recovery codes
      (`/user/two-factor-recovery-codes`) → confirm (`POST /user/confirmed-two-factor-authentication`)
      → enabled state with disable (`DELETE /user/two-factor-authentication`) + show/regenerate
      recovery codes. Status read from `auth.user.two_factor_confirmed_at`; refreshed via the existing
      `updateUser()`. Uses the existing `useApi`/`useDialog`/`LoaderButton` conventions.
      Verified: `vite build` compiles cleanly (Settings chunk built, no errors). Safe to expose because
      the interim challenge page is live. ⚠️ Live click-through (login → settings → enable) still needs
      a real session — not exercised here; backend covered by TwoFactorTest.
- [ ] **Optional polish:** replace the interim standalone Blade challenge page with a Vue one
      matching the login screen aesthetic. Functional as-is.
- [ ] **Policy (deferred):** MFA required for admin/management/system roles — reuse the community
      RBAC role source ([[community-logic-blade-model]]), don't build a parallel check.
- [ ] **Hardening (deferred):** set `confirmPassword => true` once a password-confirmation step
      exists in the SPA (it attaches `password.confirm` middleware to the 2FA management routes).

### Phase 5 — Remove laravel/ui
- [ ] `grep -rn "Auth::routes\|laravel/ui"` → expect zero functional refs.
- [ ] `composer remove laravel/ui`.
- [ ] Delete `LoginController` + any remaining superseded Auth controllers.
- [ ] Final `route:list` + full Phase 0 test suite green.

### Additional 2FA methods (user requested all three, 2026-06-26) — build in sequence
- **Phase 6 — Trusted devices ✅ DONE (full).** "Remember this device" skips the 2FA challenge on a
  known browser for 30 days, with self-service list + revoke. Backend + UI + tests + migrated.
  - [x] Core remember-device behavior (see trace above).
  - [x] **Revoke gap CLOSED:** `TrustedDeviceController` (index/destroy/destroyAll, scoped to the
        acting user), routes `GET|DELETE /trusted-devices[/{id}]` under `auth`, and a list+revoke
        section in the Settings 2FA UI (marks 現在の端末, "すべて解除"). `currentTokenHash()` added to
        the manager. Feature suite 28 green; `vite build` clean.
  - [ ] Optional later: shorter TTL for admins; auto-clear devices when 2FA disabled / password changed.
- **Phase 7 — Email OTP ✅ DONE 2026-06-26.** Independent opt-in method, parallel to TOTP
  (TOTP wins if both somehow enabled). Backend + UI + tests + migrated.
  - User flag `email_otp_enabled_at` (migration `..._000030`); codes live hashed in the cache
    (`EmailOtpService`, sha256, 10-min, single-use), emailed via `Mail::raw`.
  - Login pipeline gate (`RedirectIfTwoFactorAuthenticatable`) gained an email-OTP branch after the
    TOTP check → emails a code, stashes `login.id`, redirects to `email-otp.challenge`.
  - `EmailOtpChallengeController` (create/store/resend, guest) logs the user in from `login.id`,
    runs the shared `InteractsWithCommunityLogin` side-effects + optional remember-device.
  - `EmailOtpController` (send/confirm/destroy, auth) for enrollment; Settings step 9 UI.
  - `tests/Feature/Auth/EmailOtpTest.php` (5 tests). Feature suite 33 green; `vite build` clean.
  - Caveats: not live-click-tested (needs real login + SMTP); weakest method (treat as fallback) —
    email password-reset is retired so email isn't also the reset channel here.
- **Phase 8 — Passkeys / WebAuthn ✅ DONE 2026-06-26.** Passwordless login + per-user passkey mgmt.
  - Re-added passkeys migration (`..._000040`); `User implements PasskeyUser use PasskeyAuthenticatable`.
  - Enabled `Features::passkeys(['confirmPassword' => false])` — Fortify suppresses the package's own
    routes (`LaravelPasskeys::ignoreRoutes()`), registers its own, and maps `fortify.passkeys.*` →
    `passkeys.*` config (relying-party id + origins from APP_URL).
  - Custom `App\Http\Responses\PasskeyLoginResponse` (shared `InteractsWithCommunityLogin`) so passkey
    login runs the same community side-effects.
  - Frontend: `resources/js/utils/webauthn.ts` (base64url↔ArrayBuffer ceremony helpers, package ships
    none), Settings step 10 (add/list/delete passkeys via `/user/passkeys[/options]`), and a
    「パスキーでログイン」 button on the login page (`/passkeys/login/options` → `/passkeys/login`).
  - `tests/Feature/Auth/PasskeyTest.php` (4 tests: options gen, auth-gating, listing). Feature suite
    37 green; `vite build` clean.
  - ⚠️ The create/verify CEREMONY can't be unit-tested or build-verified (needs a real authenticator
    + browser) — **must be tested live by the user**. Production needs `APP_URL` = the exact host the
    SPA is served from (relying-party id / allowed origin), else WebAuthn rejects.

### Later / deferred (explicitly NOT this migration)
- Risk-based MFA scoring (new device/IP/country/failed-attempts).
- MFA-required-by-role policy (reuse community RBAC).
- Optional cleanup: split the 967-line `routes/web.php` into domain files (see §6) — independent.

---

## 4. Trace Table (UPDATE AS YOU GO)

| Date | Phase | File | Change | Reversible? | Notes |
|------|-------|------|--------|-------------|-------|
| 2026-06-26 | plan | docs/sanctum_migration_footprint.md | created | — | this doc |
| 2026-06-26 | recon | (read-only audit) | mapped auth surface, no code changed | — | login/logout = form POST; only LoginController has custom logic; users has email+remember_token; needs 3 two_factor cols; no auth tests |
| 2026-06-26 | P0 | tests/Feature/Auth/LoginTest.php | created — 4 baseline tests, all PASS | delete file | in-memory sqlite, minimal users table; covers GET /login render, login success+redirect+account-chooser cookie, bad-creds error, logout. Full Feature suite green (15 passed). |
| 2026-06-26 | P1 | composer.json/lock | require laravel/fortify ^1.37 | `composer remove laravel/fortify` | also pulled fortify+passkeys auto-discovery; laravel/passkeys & laravel/sentinel already present in tree |
| 2026-06-26 | P1 | config/fortify.php | published + configured | delete file | username=login, email=email, lowercase_usernames=false, home=BOARD, views=false; features trimmed (registration/2FA/passkeys/profile OFF; resetPasswords+updatePasswords kept for P3) |
| 2026-06-26 | P1 | app/Providers/FortifyServiceProvider.php | published + `Fortify::ignoreRoutes()` in register() | delete file | suppresses ALL Fortify routes → zero behavior change; remove the call in P2 to enable cutover |
| 2026-06-26 | P1 | app/Actions/Fortify/* | published (5 stubs, untouched) | delete dir | CreateNewUser moot (registration off); ResetUserPassword/UpdateUserPassword need review in P3 (assume name/email — ours uses login) |
| 2026-06-26 | P1 | config/app.php | registered App\Providers\FortifyServiceProvider | revert line | placed before RouteServiceProvider |
| 2026-06-26 | P1 | (published migrations) | DELETED 2FA + passkeys migrations | re-publish | `2014_..add_two_factor_columns..` → re-add in P4 via `vendor:publish --tag=fortify-migrations`; passkeys migration deferred/out of scope |
| | | | | | |

**Phase 1 verification (2026-06-26):** router check — `login`/`logout` still resolve to
`App\Http\Controllers\Auth\LoginController` (laravel/ui), **0 Fortify-owned routes registered**,
`Fortify::$registersRoutes=false`. Full Feature suite **15 passed**. Behavior unchanged. ✅

| 2026-06-26 | P2 | app/Providers/FortifyServiceProvider.php | removed `ignoreRoutes()`; bind LoginResponse in boot() | re-add ignoreRoutes / unbind | Fortify now registers routes |
| 2026-06-26 | P2 | app/Http/Responses/LoginResponse.php | created | delete file | ports LoginController::authenticated() (CommunityResolver + account-chooser cookie) + redirect to fortify.home |
| 2026-06-26 | P2 | routes/web.php:116 | replaced `Auth::routes(['register'=>false])` with explicit GET /login + ui password reset/confirm routes (dropped POST /login + POST /logout) | restore the one-liner | Fortify owns POST /login & POST /logout |
| 2026-06-26 | P2 | config/fortify.php | commented resetPasswords + updatePasswords (defer to P3) | uncomment | avoids password.email/password.update name collision with surviving ui routes |
| | | | | | |

**Phase 2 verification (2026-06-26):** router check — `GET /login`→ui `LoginController@showLoginForm`
(name `login`); `POST /login`→Fortify `AuthenticatedSessionController@store` (`login.store`);
`POST /logout`→Fortify (`logout`); `route('logout')`=`/logout`; `LoginResponse` bound to
`App\Http\Responses\LoginResponse`; `password.update` still ui (no collision). Phase 0 baseline
suite **15 passed** against the Fortify-backed endpoints (login success+cookie+redirect, bad-creds
error on `login`, logout). Behavior equivalent. ✅
NOTE: LoginController's `login()/logout()/authenticated()/username()` are now dead code (no route
hits them) — removed in Phase 5. Real-browser CSRF/cookie roundtrip recommended before deploy.

| 2026-06-26 | P3 | routes/web.php | removed password reset (4) + confirm (2) routes; kept GET /login | restore routes | email forgot-password retired per user decision (unreachable+broken) |
| 2026-06-26 | P3 | app/Http/Controllers/Auth/{ForgotPassword,ResetPassword,ConfirmPassword}Controller.php | DELETED | git restore | dead; self-service pw change is UserController@passChange; admin reset in UI |
| 2026-06-26 | P3 | resources/views/auth/passwords/{email,reset,confirm}.blade.php + dir | DELETED | git restore | unused/broken forgot-password screens |
| 2026-06-26 | P5 | composer remove laravel/ui | removed package | `composer require laravel/ui` | ⚠️ CORRECTION: the `Illuminate\Foundation\Auth\*` ACTION TRAITS (AuthenticatesUsers/RegistersUsers/VerifiesEmails/…) are shipped by laravel/ui, NOT the framework. Removal broke Login/Register/Verification controllers — caught by the P0 test. |
| 2026-06-26 | P5 | app/Http/Controllers/Auth/LoginController.php | rewritten minimal (renders auth.login only, no trait) | git restore | still serves GET /login; auth handled by Fortify |
| 2026-06-26 | P5 | app/Http/Controllers/Auth/{Register,Verification}Controller.php | DELETED | git restore | unrouted + broken after ui removal (used RegistersUsers/VerifiesEmails) |
| | | | | | |

**Phase 3 + 5 verification (2026-06-26):** no remaining `Illuminate\Foundation\Auth\*` trait usage;
app boots; `route('login')=/login`, `route('logout')=/logout`; laravel/ui gone from composer.json &
vendor. Full Feature suite **15 passed**. ✅

| 2026-06-26 | P4 | database/migrations/2026_06_26_000010_add_two_factor_columns_to_users_table.php | created (NOT yet run on real DB) | delete file | adds two_factor_secret/recovery_codes/confirmed_at; **user must `php artisan migrate`** |
| 2026-06-26 | P4 | app/Models/User.php | + `TwoFactorAuthenticatable` trait; +2 hidden attrs | revert | required by Fortify 2FA |
| 2026-06-26 | P4 | config/fortify.php | enabled `twoFactorAuthentication(confirm=true, confirmPassword=false)` | comment out | confirmPassword=false avoids the deleted password.confirm view route |
| 2026-06-26 | P4 | app/Http/Responses/Concerns/InteractsWithCommunityLogin.php | created (shared side-effects) | delete | community resolve + account-chooser cookie; used by both login responses |
| 2026-06-26 | P4 | app/Http/Responses/LoginResponse.php | refactored to use shared trait | git restore | behavior unchanged |
| 2026-06-26 | P4 | app/Http/Responses/TwoFactorLoginResponse.php | created + bound in provider | delete + unbind | so 2FA login runs the SAME side-effects as password login |
| 2026-06-26 | P4 | resources/views/auth/two-factor-challenge.blade.php + routes/web.php | standalone challenge page + GET route named `two-factor.login` | delete | Fortify redirects 2FA users here; views=false means Fortify registers no GET page |
| 2026-06-26 | P4 | tests/Feature/Auth/TwoFactorTest.php | created — 4 tests | delete | enable+confirm, login→challenge→TOTP, recovery code, non-2FA regression |
| 2026-06-26 | P4-UI | resources/js/components/Settings/Settings.vue | added "二段階認証" section (step 8): enable/QR/confirm/disable/recovery | git restore | uses useApi/useDialog/LoaderButton; status from auth.user.two_factor_confirmed_at; `vite build` green |
| 2026-06-26 | P6 | migration `..._000020_create_user_trusted_devices_table.php` + UserTrustedDevice model | created + MIGRATED on dev DB | rollback/delete | stores sha256(token) only |
| 2026-06-26 | P6 | app/Services/Auth/TrustedDeviceManager.php | created | delete | isTrusted/remember/forget/forgetAll; cookie `glowd_trusted_device`, 30-day |
| 2026-06-26 | P6 | app/Actions/Fortify/RedirectIfTwoFactorAuthenticatable.php | created (extends Fortify's) | delete | skips challenge if trusted cookie valid; else mirrors Fortify default |
| 2026-06-26 | P6 | app/Providers/FortifyServiceProvider.php | `Fortify::authenticateThrough()` pipeline using our 2FA gate | revert | ⚠️ contract rebind did NOT work (Fortify `scoped` won); authenticateThrough is order-independent and takes precedence |
| 2026-06-26 | P6 | TwoFactorLoginResponse.php + two-factor-challenge.blade.php | remember device when `remember_device` checkbox set | revert | checkbox added to challenge page |
| 2026-06-26 | P6 | tests/Feature/Auth/TrustedDeviceTest.php | created — 5 tests | delete | trusted skips / missing+expired require / remember records / no-remember records nothing. Feature suite 24 green. |
| 2026-06-26 | P6 | app/Http/Controllers/TrustedDeviceController.php + routes/web.php | index/destroy/destroyAll + `auth` routes | delete | self-service list/revoke; user-scoped |
| 2026-06-26 | P6 | TrustedDeviceManager::currentTokenHash() | added | revert | marks 現在の端末 in the list |
| 2026-06-26 | P6 | Settings.vue (2FA section) | trusted-device list + revoke / すべて解除 | git restore | loads on open/enable; `vite build` clean |
| 2026-06-26 | P6 | TrustedDeviceTest.php | +4 management tests (list scope, destroy, can't-revoke-others, destroyAll) | delete | Feature suite 28 green |
| 2026-06-26 | P7 | migration `..._000030_add_email_otp_to_users_table.php` + User cast | created + MIGRATED on dev DB | rollback | `email_otp_enabled_at`; also added two_factor_confirmed_at cast |
| 2026-06-26 | P7 | app/Services/Auth/EmailOtpService.php | created | delete | sha256 code in cache (10-min, single-use), Mail::raw |
| 2026-06-26 | P7 | RedirectIfTwoFactorAuthenticatable.php | added email-OTP branch (after TOTP) | revert | userHasTotp/userHasEmailOtp/emailOtpChallengeResponse |
| 2026-06-26 | P7 | EmailOtpController + EmailOtpChallengeController + routes | created | delete | mgmt (auth) + challenge (guest); challenge reuses InteractsWithCommunityLogin |
| 2026-06-26 | P7 | resources/views/auth/email-otp-challenge.blade.php | created | delete | standalone code + resend + remember-device page |
| 2026-06-26 | P7 | Settings.vue | step 9 "メール二段階認証" (enable/confirm/disable/resend) | git restore | `vite build` clean |
| 2026-06-26 | P7 | tests/Feature/Auth/EmailOtpTest.php | created — 5 tests | delete | Feature suite 33 green |
| 2026-06-26 | P8 | migration `..._000040_create_passkeys_table.php` | created + MIGRATED on dev | rollback | re-creates the P1-deleted passkeys table |
| 2026-06-26 | P8 | app/Models/User.php | implements PasskeyUser + PasskeyAuthenticatable trait | revert | provides passkeys() relation + WebAuthn identity |
| 2026-06-26 | P8 | config/fortify.php | enabled `Features::passkeys(['confirmPassword'=>false])` | comment out | Fortify owns the passkey routes |
| 2026-06-26 | P8 | PasskeyLoginResponse.php + FortifyServiceProvider binding | created | delete + unbind | passkey login runs shared community side-effects |
| 2026-06-26 | P8 | PasskeyController.php + route GET /user/passkeys | created | delete | list endpoint (Fortify provides no list) |
| 2026-06-26 | P8 | resources/js/utils/webauthn.ts | created | delete | base64url↔buffer + create/get ceremony |
| 2026-06-26 | P8 | Settings.vue step 10 + LoginComponent.vue passkey button | added | git restore | add/list/delete + passwordless login; `vite build` clean |
| 2026-06-26 | P8 | tests/Feature/Auth/PasskeyTest.php | created — 4 tests | delete | Feature suite 37 green |
| | | | | | |

## 8. MIGRATION COMPLETE (2026-06-26)

laravel/ui dropped; session/cookie SPA auth on **Sanctum + Fortify**. 2FA available via **TOTP,
email OTP, and passkeys**, plus **trusted devices** ("remember this browser"). 37 Feature tests green;
all frontend compiles. Remaining (optional / pre-deploy): live-browser verification of every UI flow
(esp. the passkey ceremony — needs a real authenticator), run all migrations on prod, confirm Google
Socialite still bypasses Fortify, MFA-required-by-role policy, `confirmPassword=true` hardening, and
ensuring `APP_URL`/`SANCTUM_STATEFUL_DOMAINS` are correct per environment.

**Phase 4 verification (2026-06-26):** all 2FA routes registered (incl. our GET /two-factor-challenge
named `two-factor.login`); `TwoFactorLoginResponse` bound to ours; feature enabled. Full Feature suite
**19 passed**. ⚠️ BACKEND ONLY — see Phase 4 checklist for the remaining UI/policy work and the
**pending `php artisan migrate`** on the real DB. Gotcha learned: Fortify stores 2FA secret/recovery
via the SERIALIZING `encrypt()`/`decrypt()` (`Fortify::currentEncrypter()`), not the `*String` variants.
PhoneVerificationController kept (empty, no trait). Pre-existing tech debt NOT touched: the
`verify.blade`/`verify_phone_number.blade` + `VerifyUser` middleware reference `verification.*`
routes that aren't registered in web.php — broken before this work, out of scope.

---

## 5. Codebase-specific GOTCHAS (the demons)

1. **Username is `login`, not `email`.** Every Fortify config + reset flow must use `login`.
   Password-reset still needs a contactable field — confirm what users have (email? phone?).
2. **`authenticated()` side-effects must not be lost.** CommunityResolver + account-chooser
   cookie are load-bearing. Losing them = broken community context + multi-account UX.
3. **Socialite users.** Google login path must keep working and not be forced through
   Fortify password/MFA confirmation (some users may lack a usable password).
4. **Native token endpoint (`/sanctum/token`).** Mobile app depends on it. Fortify changes
   must not alter this. If MFA becomes mandatory, mobile token issuance needs its own plan.
5. **Legacy `App\Http\Kernel`.** `guest`/`auth` middleware aliases live there, not in
   `bootstrap/app.php`. Fortify expects standard aliases — verify they resolve.
6. **`register => false`.** Registration is intentionally disabled. Keep Fortify registration
   feature OFF.
7. **Community RBAC in flight.** Coordinate with `community_logic_*` docs — MFA policy by role
   should reuse the same role source, not invent a parallel one.

---

## 6. Decision Log

- **2026-06-26 — Fortify + Sanctum, not Sanctum-only.** Sanctum-only would require hand-rolling
  MFA/recovery/reset. Fortify is headless and coexists with the existing Vue/Blade UI.
- **2026-06-26 — Incremental cutover, run laravel/ui and Fortify side-by-side.** Remove ui only
  after every route is migrated and verified. "Ripping out auth first summons production demons."
- **2026-06-26 — Defer trusted-device / risk-MFA / passkeys.** TOTP + recovery codes deliver
  most of the value; the rest is higher-effort, lower-urgency.
- **2026-06-26 — Do NOT move web.php routes to api.php.** (Recurring question — answer is no.)
  The Vue SPA authenticates via **Sanctum SPA mode = session cookie + CSRF on the `web` group**,
  which is what already exists (web.php routes return `response()->json(...)`, e.g.
  `BoardController::board_create`). `api.php` is the **token** lane for the native/mobile app
  (`EnsureFrontendRequestsAreStateful` is COMMENTED OUT on the `api` group in `app/Http/Kernel.php`).
  Fortify only replaces login/logout/reset/MFA *actions* — it does not change the transport, so
  the ~730 JSON endpoints keep working on the session cookie unchanged. Moving them would drop
  CSRF protection, force bearer-token handling in JS, and require re-testing everything, for zero
  gain on a same-origin app. The real problem (967-line web.php monolith) is solved by **splitting
  into domain route files** (`routes/board.php`, etc.) loaded into the same `web` group — an
  organizational refactor, NOT an auth migration. Only move to api.php token auth if the SPA ever
  moves to a separate domain where cookies can't be shared.

---

## 7. Rollback

Each phase is its own commit. To revert: `git revert <phase commit>`. laravel/ui stays installed
until Phase 5, so reverting any earlier phase restores the original `Auth::routes()` flow intact.
