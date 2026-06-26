<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearPaidLeaveLedgerData extends Command
{
    protected $signature = 'paid-leave:clear-data
        {--user_id= : Clear paid-leave data for one Glowd user id.}
        {--user_code= : Clear paid-leave data for one employee code.}
        {--force : Actually delete data. Without this option the command is a dry run.}
        {--allow-production : Allow destructive delete when APP_ENV=production.}';

    protected $description = 'Clear paid-leave ledger data so imports and reconciliation can be tested again.';

    public function handle(): int
    {
        if ($this->option('user_id') && $this->option('user_code')) {
            $this->error('Use either --user_id or --user_code, not both.');

            return self::FAILURE;
        }

        if ($this->option('force') && app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Refusing to clear paid-leave data in production. Add --allow-production only if you are absolutely sure.');

            return self::FAILURE;
        }

        $accountIds = $this->targetAccountIds();
        $grantIds = $this->grantIds($accountIds);
        $usageIds = $this->usageIds($accountIds);
        $counts = $this->counts($accountIds, $grantIds, $usageIds);

        $this->table(
            ['table', 'rows'],
            collect($counts)->map(fn ($count, $table) => [$table, $count])->values()
        );

        if (! $this->option('force')) {
            $this->warn('Dry run only. Re-run with --force to delete these paid-leave rows.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($accountIds, $grantIds, $usageIds) {
            $this->deleteAllocations($grantIds, $usageIds);
            DB::table('paid_leave_adjustments')->whereIn('paid_leave_account_id', $accountIds)->delete();
            DB::table('paid_leave_usages')->whereIn('paid_leave_account_id', $accountIds)->delete();
            DB::table('paid_leave_grants')->whereIn('paid_leave_account_id', $accountIds)->delete();
            DB::table('paid_leave_accounts')->whereIn('id', $accountIds)->delete();
        });

        $this->info('Paid-leave ledger data cleared.');

        return self::SUCCESS;
    }

    private function targetAccountIds(): array
    {
        $query = DB::table('paid_leave_accounts')->select('id');

        if ($this->option('user_id')) {
            $query->where('user_id', (int) $this->option('user_id'));
        }

        if ($this->option('user_code')) {
            $userCode = trim((string) $this->option('user_code'));
            $userId = DB::table('users')->where('user_code', $userCode)->value('id');
            $query->where(function ($inner) use ($userCode, $userId) {
                $inner->where('user_code', $userCode);
                if ($userId) {
                    $inner->orWhere('user_id', $userId);
                }
            });
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function grantIds(array $accountIds): array
    {
        return DB::table('paid_leave_grants')
            ->whereIn('paid_leave_account_id', $accountIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function usageIds(array $accountIds): array
    {
        return DB::table('paid_leave_usages')
            ->whereIn('paid_leave_account_id', $accountIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function counts(array $accountIds, array $grantIds, array $usageIds): array
    {
        return [
            'paid_leave_usage_allocations' => $this->allocationQuery($grantIds, $usageIds)->count(),
            'paid_leave_adjustments' => DB::table('paid_leave_adjustments')->whereIn('paid_leave_account_id', $accountIds)->count(),
            'paid_leave_usages' => count($usageIds),
            'paid_leave_grants' => count($grantIds),
            'paid_leave_accounts' => count($accountIds),
        ];
    }

    private function deleteAllocations(array $grantIds, array $usageIds): void
    {
        $this->allocationQuery($grantIds, $usageIds)->delete();
    }

    private function allocationQuery(array $grantIds, array $usageIds)
    {
        return DB::table('paid_leave_usage_allocations')
            ->where(function ($query) use ($grantIds, $usageIds) {
                if ($usageIds !== []) {
                    $query->whereIn('paid_leave_usage_id', $usageIds);
                }

                if ($grantIds !== []) {
                    $method = $usageIds === [] ? 'whereIn' : 'orWhereIn';
                    $query->{$method}('paid_leave_grant_id', $grantIds);
                }

                if ($usageIds === [] && $grantIds === []) {
                    $query->whereRaw('1 = 0');
                }
            });
    }
}
