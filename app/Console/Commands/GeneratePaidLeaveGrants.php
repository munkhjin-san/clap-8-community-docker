<?php

namespace App\Console\Commands;

use App\Services\PaidLeaveLedgerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GeneratePaidLeaveGrants extends Command
{
    protected $signature = 'paid-leave:grant {--date= : Run date in YYYY-MM-DD. Defaults to today.} {--from= : Earliest grant date to create. Defaults to active policy effective date, or run date.}';

    protected $description = 'Generate due paid-leave grants from the active paid-leave policy.';

    public function handle(PaidLeaveLedgerService $paidLeaveLedger): int
    {
        $runDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : Carbon::today();
        $fromDate = $this->option('from')
            ? Carbon::parse((string) $this->option('from'))->startOfDay()
            : null;

        $summary = $paidLeaveLedger->generateDueGrants($runDate, $fromDate);

        $this->table(
            ['metric', 'value'],
            collect(['run_date' => $runDate->toDateString(), 'from' => $fromDate?->toDateString() ?? 'policy/default'] + $summary)
                ->map(fn ($value, $key) => [$key, $value])
                ->values()
        );

        return self::SUCCESS;
    }
}
