<?php
namespace App\Http\Middleware;

use Closure;

class ServerTimingMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);

        // Let Laravel handle the request
        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000;

        // Add Server-Timing header
        $response->headers->set(
            'Server-Timing',
            'app;desc="Laravel App";dur=' . number_format($duration, 2)
        );

        return $response;
    }
}
