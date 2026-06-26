<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;
use App\Http\Controllers\AccountChooserController;
use App\Services\Community\CommunityResolver;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::BOARD;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
       
    }
    
    public function username()
    {
        return 'login';
    }

    protected function authenticated(Request $request, $user)
    {
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
    }
}
