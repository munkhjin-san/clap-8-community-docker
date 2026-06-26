<?php

namespace App\Http\Middleware;

use App\Services\Community\CommunityPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route group on a community blade (rule). Admin bypasses via can().
 * Runs after `community.active`, which resolves the user's active membership.
 *
 * Usage: Route::middleware('blade:app.project')->group(...)
 */
class EnsureCommunityBlade
{
    public function __construct(private CommunityPermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string $blade): Response
    {
        abort_unless(
            $this->permissions->can($blade, $request->user()),
            403,
            'この機能へのアクセス権限がありません。'
        );

        return $next($request);
    }
}
