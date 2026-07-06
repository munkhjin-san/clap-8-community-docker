<?php

namespace App\Services\Auth;

use App\Models\UserTrustedDevice;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Remember this device" for 2FA (Sanctum migration Phase 6).
 *
 * After a successful 2FA challenge, a browser can be remembered: a random token
 * is set in a cookie and its sha256 hash stored in `user_trusted_devices`. On the
 * next login, a valid, unexpired token lets the login pipeline skip the challenge
 * (see App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable).
 *
 * Only the hash is persisted, so a DB leak can't reconstruct usable cookies.
 */
class TrustedDeviceManager
{
    public const COOKIE = 'glowd_trusted_device';
    public const LIFETIME_DAYS = 30;

    public function isTrusted(Request $request, ?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || $token === '') {
            return false;
        }

        $device = UserTrustedDevice::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('token_hash', $this->hash($token))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if (! $device) {
            return false;
        }

        $device->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    public function remember(Authenticatable $user, Request $request): void
    {
        $token = Str::random(64);

        UserTrustedDevice::create([
            'user_id' => $user->getAuthIdentifier(),
            'token_hash' => $this->hash($token),
            'device_name' => Str::limit((string) $request->userAgent(), 250, ''),
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);

        cookie()->queue(cookie(
            self::COOKIE,
            $token,
            60 * 24 * self::LIFETIME_DAYS,
            null,
            null,
            $request->isSecure(),
            true,   // httpOnly
            false,
            'lax'
        ));
    }

    /** Hash of the trusted-device token on this request, if any (for "current device" marking). */
    public function currentTokenHash(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && $token !== '' ? $this->hash($token) : null;
    }

    /** Revoke a single device by its id for the given user. */
    public function forget(Authenticatable $user, int $deviceId): void
    {
        UserTrustedDevice::where('user_id', $user->getAuthIdentifier())
            ->whereKey($deviceId)
            ->delete();
    }

    /** Revoke every trusted device for the user (e.g. "log out everywhere"). */
    public function forgetAll(Authenticatable $user): void
    {
        UserTrustedDevice::where('user_id', $user->getAuthIdentifier())->delete();
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
