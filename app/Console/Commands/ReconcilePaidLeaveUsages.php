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
        {--user_code= : Limit to one employee code}
        {--grant-date= : Due-grant run date YYYY-MM-DD. Defaults to today and does not use --to}
        {--grant-from= : Earliest due grant date to create before reconciling. Defaults to active policy effective date, or grant-date}
        {--skip-grants : Do not generate due grants before reconciling usages}';

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

        if (! $this->option('skip-grants')) {
            $grantRunDate = $this->option('grant-date')
                ? Carbon::parse((string) $this->option('grant-date'))->startOfDay()
                : Carbon::today()->startOfDay();
            $grantFromDate = $this->option('grant-from')
                ? Carbon::parse((string) $this->option('grant-from'))->startOfDay()
                : null;
            $grantSummary = $paidLeaveLedger->generateDueGrants($grantRunDate, $grantFromDate, $userId);

            $this->info('Due paid leave grants checked.');
            $this->table(
                ['Metric', 'Count'],
                collect([
                    'grant_run_date' => $grantRunDate->toDateString(),
                    'grant_from' => $grantFromDate?->toDateString() ?? 'policy/default',
                    ...$grantSummary,
                ])
                    ->map(fn ($value, $key) => [$key, $value])
                    ->values()
            );
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
            ['Pending future grants skipped', $summary['skipped_pending_future_grant']],
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
