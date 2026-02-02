<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use App\Services\SharedService;
use App\Services\BoardControllerProxy;
use GuzzleHttp\Client as Http;
use App\Domain\Contracts\{PlanProvider,ActualProvider};
use App\Infrastructure\Kintone\{KintoneClient,KintonePlanProvider};
use App\Infrastructure\Sheets\{GoogleSheetsClient,GoogleSheetsActualProvider};
use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
        $this->app->singleton(Http::class, function() {return new Http(
            [
                'base_uri' => rtrim(config('app.kintone_base_url'), '/') . '/',
                'timeout' => 15
            ]);
        });
        $this->app->singleton(KintoneClient::class, fn($app) => new KintoneClient($app->make(Http::class)));
        $this->app->singleton(GoogleSheetsClient::class);

        $this->app->bind(PlanProvider::class, KintonePlanProvider::class);
        $this->app->bind(ActualProvider::class, GoogleSheetsActualProvider::class);
        $this->app->bind(BoardControllerProxy::class, fn($app) =>
            new BoardControllerProxy($app->make(\App\Http\Controllers\BoardController::class))
        );
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
        Gate::define('viewPulse', function (User $user) {
            return true;
        });
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
