<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Email one-time-password for 2FA (Sanctum migration Phase 7).
 *
 * A 6-digit code is generated, its sha256 hash cached (short-lived, single-use) keyed by
 * user id, and the plaintext emailed. The same mechanism backs both enrollment confirmation
 * (in settings) and the login challenge. Only the hash is stored, never the plaintext code.
 */
class EmailOtpService
{
    public const TTL_MINUTES = 10;

    public function send(User $user): bool
    {
        $email = $user->email ?: $user->work_email;
        if (! $email) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($user->getKey()), hash('sha256', $code), now()->addMinutes(self::TTL_MINUTES));

        Mail::raw(
            "GLOWD ログイン認証コード: {$code}\n\nこのコードは ".self::TTL_MINUTES."分間有効です。\n心当たりがない場合はこのメールを無視してください。",
            function ($message) use ($email) {
                $message->to($email)->subject('【GLOWD】ログイン認証コード');
            }
        );

        return true;
    }

    public function verify(int|string $userId, string $code): bool
    {
        $stored = Cache::get($this->key($userId));
        if (! is_string($stored)) {
            return false;
        }

        if (! hash_equals($stored, hash('sha256', trim($code)))) {
            return false;
        }

        Cache::forget($this->key($userId));

        return true;
    }

    private function key(int|string $userId): string
    {
        return "email-otp:{$userId}";
    }
}
