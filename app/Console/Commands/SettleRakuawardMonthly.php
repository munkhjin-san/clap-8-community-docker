<?php

namespace App\Console\Commands;

use App\Services\RefreshService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SettleRakuawardMonthly extends Command
{
    protected $signature = 'rakuaward:settle-monthly {--month= : Target month YYYY-MM (defaults to last month)}';

    protected $description = "Auto-grant the previous month's top-5 rakuaward nominations by director score (no refunds).";

    public function handle(RefreshService $refreshService): int
    {
        $monthOption = $this->option('month');
        $target = $monthOption
            ? Carbon::createFromFormat('Y-m', $monthOption)->startOfMonth()
            : Carbon::now()->subMonthNoOverflow();

        $result = $refreshService->settleRakuawardMonth((int) $target->year, (int) $target->month);

        $this->info('rakuaward:settle-monthly ' . json_encode($result));

        return self::SUCCESS;
    }
}
