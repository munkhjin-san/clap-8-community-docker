<?php

namespace App\Console\Commands;

use App\Services\PaidLeaveLedgerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportKintonePaidLeaveHistory extends Command
{
    protected $signature = 'paid-leave:import-kintone-history
        {--write : Write app 605 grant lots and history. Without this option the command is a dry run.}
        {--as-of= : Import only Kintone grants whose actual grant date is on or before this date. Defaults to today.}
        {--user_code= : Limit import to one employee code.}
        {--allow-existing-opening-balance : Allow import even when the account already has the old app 794 opening-balance grant.}';

    protected $description = 'Import Kintone app 605 paid-leave year lots, usage history, and expiration history into the Glowd ledger.';

    public function handle(PaidLeaveLedgerService $paidLeaveLedger): int
    {
        $asOf = $this->option('as-of')
            ? Carbon::parse((string) $this->option('as-of'))->endOfDay()
            : Carbon::today()->endOfDay();

        $summary = $paidLeaveLedger->importKintoneAnnualLeaveHistory(
            dryRun: ! $this->option('write'),
            asOf: $asOf,
            userCode: $this->option('user_code') ? (string) $this->option('user_code') : null,
            allowExistingOpeningBalance: (bool) $this->option('allow-existing-opening-balance'),
        );

        $this->table(
            ['metric', 'value'],
            collect($summary)->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : $value])->values()
        );

        if ($summary['dry_run']) {
            $this->warn('Dry run only. Re-run with --write to import Kintone app 605 history.');
        }

        if (! $this->option('allow-existing-opening-balance') && ($summary['skipped_existing_opening_balance'] ?? 0) > 0) {
            $this->warn('Some rows were skipped because app 794 opening-balance grants already exist. Importing both would double the balance.');
        }

        return self::SUCCESS;
    }
}
