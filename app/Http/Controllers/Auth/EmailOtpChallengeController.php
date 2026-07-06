<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\Concerns\InteractsWithCommunityLogin;
use App\Services\Auth\EmailOtpService;
use App\Services\Auth\TrustedDeviceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Email-OTP login challenge (Sanctum migration Phase 7).
 *
 * Reached after the password step for users with email OTP enabled (see
 * App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable). The user is identified by the
 * pending 'login.id' session key set during that step; a valid code completes the login and
 * runs the SAME post-login side-effects as every other path (community resolve, account-chooser
 * cookie, optional trusted-device), via InteractsWithCommunityLogin.
 */
class EmailOtpChallengeController extends Controller
{
    use InteractsWithCommunityLogin;

    public function __construct(private EmailOtpService $service)
    {
    }

    public function create(Request $request)
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.email-otp-challenge');
    }

    public function store(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get('login.id');
        if (! $userId) {
            return redirect()->route('login');
        }

        if (! $this->service->verify($userId, $request->code)) {
            throw ValidationException::withMessages(['code' => ['コードが正しくありません。']]);
        }

        $remember = (bool) $request->session()->get('login.remember', false);
        Auth::guard('web')->loginUsingId($userId, $remember);
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        if ($request->boolean('remember_device')) {
            app(TrustedDeviceManager::class)->remember(Auth::user(), $request);
        }

        return $this->communityLoginResponse($request);
    }

    public function resend(Request $request)
    {
        $userId = $request->session()->get('login.id');
        if ($userId && ($user = \App\Models\User::find($userId))) {
            $this->service->send($user);
        }

        return back();
    }
}
