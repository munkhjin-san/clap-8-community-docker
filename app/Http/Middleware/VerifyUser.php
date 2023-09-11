<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if($user->email && $user->phone){
            if (!$user || (!$user->hasVerifiedEmail() && !$user->phone_isVerified)) {
                return redirect()->route('verification.notice');
            }
        }else if($user->email){
            if (!$user || !$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
        }else if($user->phone){
            if (!$user || !$user->phone_isVerified) {
                $encoded_phone_prefix = urlencode($user->phone_prefix);
                $url = $encoded_phone_prefix . '&phone=' . $user->phone;
                return redirect('/phone/verify?phone_prefix=' . $url);            
            }
        }
        

        return $next($request);
    }
}
