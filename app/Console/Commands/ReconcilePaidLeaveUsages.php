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
        $this->line('Paid leave shifts: ' . $summary['paid_leave_shifts']);
        $this->line('User-months: ' . $summary['reconciled_user_months']);

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
