<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckSessionExpired;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\ServerTimingMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\VerifyUser;
use App\Jobs\CheckUserEvaluation;
use App\Jobs\RemoveFile;
use App\Jobs\ResetCharge;
use App\Jobs\SendReport;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ---- Global stack (customizations carried over from App\Http\Kernel) ----
        $middleware->trustProxies(at: '*', headers:
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        // Custom global middleware (appended after the framework defaults).
        $middleware->append(SetLocaleMiddleware::class);

        // ---- web group ----
        // CSRF exemptions for external webhooks (Zoom + contract-update callbacks).
        // These endpoints are POSTed to by third parties with no CSRF token.
        $middleware->validateCsrfTokens(except: [
            'zoom1_event',
            'zoom2_event',
            'zoom3_event',
            'contract_updated',
        ]);

        $middleware->web(append: [
            ServerTimingMiddleware::class,
        ]);

        // ---- api group ----
        // Prepend the "api" rate limiter (defined in AppServiceProvider::boot).
        $middleware->throttleApi();

        // ---- route middleware aliases (custom + overrides of framework defaults) ----
        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'signed' => ValidateSignature::class,
            'verified.user' => VerifyUser::class,
            'session.expired' => CheckSessionExpired::class,
            // (community_logic) multi-community RBAC middleware
            'community.active' => \App\Http\Middleware\ResolveActiveCommunity::class,
            'community.glowd' => \App\Http\Middleware\EnsureGlowdCommunity::class,
            'capability' => \App\Http\Middleware\EnsureCommunityCapability::class,
            'app.capability' => \App\Http\Middleware\EnforceAppCapability::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // NOTE: render callbacks run AFTER the framework's prepareException(), which
        // wraps several exceptions in an HttpException and keeps the original as the
        // "previous" exception. We therefore unwrap getPrevious() where relevant.

        // Expired session / CSRF token -> bounce back to login with a notice.
        // TokenMismatchException is wrapped into an HttpException(419) before we see it.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! ($e instanceof TokenMismatchException || $e->getPrevious() instanceof TokenMismatchException)) {
                return null;
            }

            $request->session()->flash('error', 'セキュリティ保護のためもう一度ログインしてください。');

            return redirect()->route('login');
        });

        // Tampered / expired signed URLs (thrown by the custom "signed" middleware).
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            return response()->view('errors.invalid-signature', [], 403);
        });

        // Not authenticated (401). JSON callers get a localized message; web requests
        // fall through to the framework's redirect-to-login behaviour.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => __('errors.unauthenticated')], 401);
        });

        // Missing record (404) and forbidden record (403) are collapsed into a single
        // 404 + message so callers cannot infer whether a record exists.
        // ModelNotFoundException is wrapped into a NotFoundHttpException, and an
        // AuthorizationException into an AccessDeniedHttpException(403), before we see them.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $previous = $e->getPrevious();

            $model = match (true) {
                $e instanceof ModelNotFoundException => $e,
                $previous instanceof ModelNotFoundException => $previous,
                default => null,
            };

            $forbidden = $e instanceof AuthorizationException
                || $previous instanceof AuthorizationException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403);

            if (! $model && ! $forbidden) {
                return null;
            }

            if ($model && ! empty($model->getIds())) {
                $message = __('errors.record_not_found', ['id' => implode(', ', (array) $model->getIds())]);
            } else {
                $message = __('errors.record_forbidden_or_missing');
            }

            return response()->json(['message' => $message], 404);
        });

        // Rate limited (429).
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if (! ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 429)) {
                return null;
            }

            return response()->json(['message' => __('errors.too_many_requests')], 429);
        });

        // Fallback for server errors (5xx) on JSON requests. Registered last so the
        // specific handlers above win first. Skipped when debug is on, so local/staging
        // still surface the real error detail. Framework-special exceptions (validation,
        // auth, explicit responses) are left to their own handling.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson() || config('app.debug')) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if ($status < 500 || $status === 503) {
                return null;
            }

            return response()->json(['message' => __('errors.server_error')], $status);
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new RemoveFile('temp'))->cron('15 9 * * *');
        $schedule->job(new RemoveFile('timecard_orphaned'))->dailyAt('03:15');
        $schedule->job(new ResetCharge())->cron('15 9 10 3,6,9,12 *');
        // $schedule->job(new RemoveFile('cost'))->cron('15 9 20 * *');
        $schedule->job(new SendReport(610, 3532, 'incident'))->cron('15 9 * * *');
        // $schedule->job(new SendReport(610, 3633, 'weekly_staff'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 1283, 'weekly_legal'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 1303, 'weekly_balance'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 3599, 'weekly_officer'))->cron('15 9 * * 1');
        // $schedule->job(new SendReport(610, 1056, 'monthly_performance'))->cron('15 9 15 * *');
        $schedule->job(new SendReport(610, 1056, 'monthly_shift'))->cron('15 9 20 * *');
        $schedule->job(new SendReport(610, 1056, 'monthly_mailing'))->cron('15 9 20 * *');
        // $schedule->job(new ProcessMessage())->hourly();

        // $schedule->job(new GenerateWelcomeMessage())->cron('0 * * * *');
        // $schedule->job(new RemoveTempSchedule())->cron('15 9 * * *');
        $schedule->job(new CheckUserEvaluation())->dailyAt('01:00');

        $schedule->command('posts:close-expired')->dailyAt('02:00');
        // カスタムアプリ：未保存のまま残った添付ファイルの掃除（保存済みには触らない）
        $schedule->command('flow:purge-pending-files')->dailyAt('03:50');
        $schedule->command('app:sync-public-holidays')->monthlyOn(1, '01:00');
        $schedule->command('alerts:variance --period='.now()->toDateString())->monthlyOn(20, '18:00');
        $schedule->command('logs:prune-activity-logs')->quarterly();
        $schedule->command('goals:check-alert-streak')->dailyAt('02:00');
        // $schedule->command('goals:freeze')->dailyAt('02:30');
        $schedule->command('goals:report-outcome-incidents')->weeklyOn(3, '08:00')->withoutOverlapping()->appendOutputTo(storage_path('logs/incidents/outcome-goal-incidents.log'));
        $schedule->command('refresh:expire')->monthlyOn(2, '08:00');
        $schedule->command('paid-leave:grant')->dailyAt('02:00');
        $schedule->command('paid-leave:expire')->dailyAt('03:00');
        $schedule->command('paid-leave:reconcile-usages')->dailyAt('04:00');
        $schedule->command('app:auto-attendance-confirm')->monthlyOn(3, '08:00');
        $schedule->command('app:refresh-automation')->monthlyOn(1, '08:00');
        $schedule->command('contact-batches:poll')->everyFifteenMinutes();
        // freeeのリフレッシュトークンは更新のたびに90日延びる。使われていなくても毎日温めて連鎖を切らさない。
        $schedule->command('freee:refresh-tokens')->dailyAt('03:40')->withoutOverlapping();
        $schedule->command('app:seal-audit-daily-digest')->dailyAt('03:00')->appendOutputTo(storage_path('logs/timecard-audit-seal.log'));
        $schedule->command('app:verify-timecard-audit-integrity --require-digest --date='.now()->subDay()->toDateString())->dailyAt('04:00')->appendOutputTo(storage_path('logs/timecard-audit-integrity.log'));
        $schedule->command('app:approve-daily-report')->dailyAt('04:00');
        // Finance weekly digest — every Monday at 08:00 JST
        $schedule->command('timesheet:daily-report-confirmation')->dailyAt('08:00')->appendOutputTo(storage_path('logs/incidents/daily-report-confirmation.log'));
        $schedule->command('timesheet:daily-report-missing-streaks')->dailyAt('08:00');
    })
    ->create();
