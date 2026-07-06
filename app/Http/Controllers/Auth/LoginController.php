<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

/**
 * Renders the login page only.
 *
 * Authentication itself is handled by Laravel Fortify (POST /login → POST /logout);
 * post-login side-effects (community resolution + account-chooser cookie) live in
 * App\Http\Responses\LoginResponse. Fortify serves no login view (fortify.views=false),
 * so this GET /login route renders our Vue-backed Blade. The legacy laravel/ui
 * AuthenticatesUsers trait was removed with the package. See docs/sanctum_migration_footprint.md.
 */
class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }
}
