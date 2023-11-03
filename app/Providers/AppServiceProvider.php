<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use App\Services\SharedService;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(SharedService::class, function ($app) {
            return new SharedService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
        // ResetPassword::createUrlUsing(function ($user, string $token) {
        //     return url(route('password.reset', [
        //         'token' => $token,
        //         'email' => $user->getEmailForPasswordReset(),
        //     ], false));
        // });
    
        // ResetPassword::toMailUsing(function ($user, $token) {
        //     return (new MailMessage)
        //         ->subject('Reset Password')
        //         ->markdown('emails.password-reset', [
        //             'user' => $user,
        //             'actionUrl' => url(route('password.reset', [
        //                 'token' => $token,
        //                 'email' => $user->getEmailForPasswordReset(),
        //             ], false)),
        //         ]);
        // });
    }
}
