<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PaidLeaveLedgerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcilePaidLeaveUsages extends Command
{
    protected $signature = 'paid-leave:reconcile-usages
        {--from= : Start date YYYY-MM-DD, defaults to today}
        {--to= : End date YYYY-MM-DD, defaults to one year from today}
        {--user_id= : Limit to one Glowd user id}
        {--user_code= : Limit to one employee code}';

    protected $description = 'Reconcile paid-leave shift records into the paid-leave ledger.';

    public function handle(PaidLeaveLedgerService $paidLeaveLedger): int
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : Carbon::today()->startOfDay();
        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : Carbon::today()->addYear()->endOfDay();
        $userId = $this->resolveUserId();
        if ($userId === 0) {
            return self::FAILURE;
        }

        $summary = $paidLeaveLedger->reconcileShiftUsages($from, $to, $userId);

        $this->info('Paid leave usages reconciled.');
        $this->table(['Metric', 'Count'], [
            ['Candidate paid-leave shifts', $summary['paid_leave_shifts']],
            ['User-months scanned', $summary['reconciled_user_months']],
            ['Active paid-leave shifts in scanned months', $summary['active_paid_leave_shifts']],
            ['Usages created', $summary['created_usages']],
            ['Usages replaced', $summary['replaced_usages']],
            ['Existing usages skipped', $summary['skipped_existing']],
            ['Kintone-reflected planned leaves skipped', $summary['skipped_externally_reflected_planned']],
            ['Kintone-reflected usages removed', $summary['removed_externally_reflected_usages']],
            ['Zero-amount shifts skipped', $summary['skipped_zero_amount']],
            ['Stale usages deleted', $summary['deleted_stale_usages']],
            ['User-months skipped: user not found', $summary['skipped_no_user']],
            ['User-months skipped: no authoritative balance', $summary['skipped_no_authoritative_balance']],
        ]);

        return self::SUCCESS;
    }

    private function resolveUserId(): ?int
    {
        if ($this->option('user_id')) {
            return (int) $this->option('user_id');
        }

        if (! $this->option('user_code')) {
            return null;
        }

        $user = User::query()
            ->where('user_code', (string) $this->option('user_code'))
            ->first(['id']);

        if (! $user) {
            $this->error('User not found for user_code: ' . $this->option('user_code'));

            return 0;
        }

        return (int) $user->id;
    }
}
