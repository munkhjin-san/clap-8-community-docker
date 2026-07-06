<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailOtpService;
use Illuminate\Http\Request;

/**
 * Email-OTP enrollment management (Sanctum migration Phase 7), called from Settings.
 * Enabling requires confirming a code emailed to the user, proving the address works.
 */
class EmailOtpController extends Controller
{
    public function __construct(private EmailOtpService $service)
    {
    }

    /** Send a confirmation code to the user's email so they can enable email OTP. */
    public function send(Request $request)
    {
        $user = $request->user();

        if (! ($user->email ?: $user->work_email)) {
            return response()->json(['message' => 'メールアドレスが登録されていません。'], 422);
        }

        $this->service->send($user);

        return response()->json(['sent' => true]);
    }

    /** Confirm the emailed code and turn email OTP on. */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        if (! $this->service->verify($request->user()->getKey(), $request->code)) {
            return response()->json(['message' => 'コードが正しくありません。'], 422);
        }

        $request->user()->forceFill(['email_otp_enabled_at' => now()])->save();

        return response()->json(['enabled' => true]);
    }

    /** Turn email OTP off. */
    public function destroy(Request $request)
    {
        $request->user()->forceFill(['email_otp_enabled_at' => null])->save();

        return response()->json(['disabled' => true]);
    }
}
