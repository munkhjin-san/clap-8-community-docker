<?php

namespace App\Console\Commands;

use App\Services\PaidLeaveLedgerService;
use Illuminate\Console\Command;

class ImportKintonePaidLeaveBalances extends Command
{
    protected $signature = 'paid-leave:import-kintone-current {--write : Write opening balances. Without this option the command is a dry run.}';

    protected $description = 'Import Kintone app 794 current paid-leave balances into the Glowd paid-leave ledger.';

    public function handle(PaidLeaveLedgerService $paidLeaveLedger): int
    {
        $summary = $paidLeaveLedger->importKintoneCurrentBalances(! $this->option('write'));

        $this->table(
            ['metric', 'value'],
            collect($summary)->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : $value])->values()
        );

        if ($summary['dry_run']) {
            $this->warn('Dry run only. Re-run with --write to create opening balances.');
        }

        return self::SUCCESS;
    }
}
