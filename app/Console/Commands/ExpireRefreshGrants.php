<?php

namespace App\Console\Commands;

use App\Services\RefreshService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
class ExpireRefreshGrants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire refresh grants that passed their expiration date and still have remaining balance.';

    /**
     * Execute the console command.
     */
    public function handle(RefreshService $refreshService): int
    {
        $summary = $refreshService->expireElapsedGrants();

        Log::info('Refresh expiration check completed.', [
            'run_date' => $summary['run_date'],
            'expired_grants' => $summary['expired_grants'],
            'expired_amount_total' => $summary['expired_amount_total'],
        ]);

        return self::SUCCESS;
    }
}
