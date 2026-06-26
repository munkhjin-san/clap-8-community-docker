<?php

namespace App\Http\Middleware;

use App\Services\Community\CommunityResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveCommunity
{
    public function __construct(private CommunityResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->resolver->resolveFor($request->user());
        }

        return $next($request);
    }
}
