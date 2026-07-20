<?php

namespace App\Http\Middleware;

use App\Models\Community;
use App\Services\Community\CommunityContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for glowd-exclusive admin features (グラウドナイン / リフレッシュ / 各種届出).
 * These are not part of the generic community feature set, so their endpoints
 * exist only inside the glowd community (Community::DEFAULT_SLUG). Any other
 * community gets a 404 — the feature effectively does not exist there.
 *
 * Runs after `community.active`, so CommunityContext is already resolved.
 */
class EnsureGlowdCommunity
{
    public function __construct(private CommunityContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->context->community()?->slug === Community::DEFAULT_SLUG, 404);

        return $next($request);
    }
}
