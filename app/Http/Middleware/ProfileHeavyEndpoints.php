<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileHeavyEndpoints
{
    /**
     * @var array<int, string>
     */
    protected array $paths = [
        'board_list',
        'board_badge',
        'get_messages',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! config('app.profile_heavy_endpoints', false)) {
            return $next($request);
        }

        if (! $this->shouldProfile($request)) {
            return $next($request);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = microtime(true);

        $response = $next($request);

        $durationMs = (microtime(true) - $start) * 1000;
        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();

        $totalQueryTime = $queries->sum('time');
        $slowQueries = $queries
            ->map(function ($query, $index) {
                return [
                    'index' => $index + 1,
                    'time_ms' => round($query['time'], 2),
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                ];
            })
            ->sortByDesc('time_ms')
            ->take(10)
            ->values();

        Log::info('heavy_endpoint_profile', [
            'path' => $request->path(),
            'request_ms' => round($durationMs, 2),
            'query_ms_total' => round($totalQueryTime, 2),
            'queries_run' => $queries->count(),
            'slow_queries' => $slowQueries,
        ]);

        return $response;
    }

    private function shouldProfile(Request $request): bool
    {
        $path = trim($request->path(), '/');
        return in_array($path, $this->paths, true);
    }
}
