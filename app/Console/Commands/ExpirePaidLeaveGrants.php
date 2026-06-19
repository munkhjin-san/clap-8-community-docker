<?php

namespace App\Console\Commands;

use App\Services\PaidLeaveLedgerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpirePaidLeaveGrants extends Command
{
    protected $signature = 'paid-leave:expire {--date= : Run date in YYYY-MM-DD. Defaults to today.}';

    protected $description = 'Expire paid-leave grant balances after their expiration date.';

    public function handle(PaidLeaveLedgerService $paidLeaveLedger): int
    {
        $runDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : Carbon::today();

        $summary = $paidLeaveLedger->expireElapsedGrants($runDate);

        $this->table(
            ['metric', 'value'],
            collect(['run_date' => $runDate->toDateString()] + $summary)
                ->map(fn ($value, $key) => [$key, $value])
                ->values()
        );

        return self::SUCCESS;
    }
}
