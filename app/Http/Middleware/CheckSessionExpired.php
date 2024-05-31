<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
class CheckSessionExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = Session::get('last_activity');
            $sessionLifetime = config('session.lifetime');

            if ($lastActivity && now()->diffInMinutes($lastActivity) > $sessionLifetime) {
                Auth::logout();
                Session::flush();
                $request->session()->flash('error', 'セキュリティ保護のためもう一度ログインしてください。');
                return redirect()->route('login');
            }

            Session::put('last_activity', now());
        }
        return $next($request);
    }
}
