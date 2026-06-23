<?php

namespace App\Services;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\PaidLeaveAccount;
use App\Models\PaidLeaveGrant;
use App\Models\PaidLeaveGrantRule;
use App\Models\PaidLeavePolicy;
use App\Models\PaidLeaveUsage;
use App\Models\shiftRecord;
use App\Models\shiftType;
use App\Models\timecardRecord;
use App\Models\User;
use App\Models\workTemp;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaidLeaveLedgerService
{
    private const DEFAULT_HOURLY_LEAVE_CAP_DAYS = 5;

    private const PART_TIME_POSITION_ID = 15;

    private const PLANNED_LEAVE_PLANNING_OPEN_DAY = 20;

    private const PART_TIME_GRANT_TABLE = [
        ['min_annual_days' => 169, 'max_annual_days' => 216, 'days' => [6 => 7, 18 => 8, 30 => 9, 42 => 10, 54 => 12, 66 => 13, 78 => 15]],
        ['min_annual_days' => 121, 'max_annual_days' => 168, 'days' => [6 => 5, 18 => 6, 30 => 6, 42 => 8, 54 => 9, 66 => 10, 78 => 11]],
        ['min_annual_days' => 73, 'max_annual_days' => 120, 'days' => [6 => 3, 18 => 4, 30 => 4, 42 => 5, 54 => 6, 66 => 6, 78 => 7]],
        ['min_annual_days' => 48, 'max_annual_days' => 72, 'days' => [6 => 1, 18 => 2, 30 => 2, 42 => 2, 54 => 3, 66 => 3, 78 => 3]],
    ];

    private const DEFAULT_RULES = [
        ['service_months' => 6, 'legal_min_days' => 10, 'grant_days' => 10, 'label' => '6ヶ月'],
        ['service_months' => 18, 'legal_min_days' => 11, 'grant_days' => 11, 'label' => '1年6ヶ月'],
        ['service_months' => 30, 'legal_min_days' => 12, 'grant_days' => 12, 'label' => '2年6ヶ月'],
        ['service_months' => 42, 'legal_min_days' => 14, 'grant_days' => 14, 'label' => '3年6ヶ月'],
        ['service_months' => 54, 'legal_min_days' => 16, 'grant_days' => 16, 'label' => '4年6ヶ月'],
        ['service_months' => 66, 'legal_min_days' => 18, 'grant_days' => 18, 'label' => '5年6ヶ月'],
        ['service_months' => 78, 'legal_min_days' => 20, 'grant_days' => 20, 'label' => '6年6ヶ月以上'],
    ];

    public function __construct(private KintoneClient $kintone)
    {
    }

    public function activePolicy(): PaidLeavePolicy
    {
        return DB::transaction(function () {
            $policy = PaidLeavePolicy::query()
                ->where('active', true)
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->with('rules')
                ->first();

            if (! $policy) {
                $policy = PaidLeavePolicy::firstOrCreate(
                    ['name' => 'default'],
                    [
                        'active' => true,
                        'first_grant_after_months' => 6,
                        'annual_grant_interval_months' => 12,
                        'expires_after_months' => 24,
                        'minimum_attendance_rate' => 80,
                        'carryover_enabled' => true,
                        'hourly_leave_enabled' => true,
                        'hourly_deduction_unit_minutes' => 60,
                        'minutes_per_leave_day' => 480,
                        'max_hourly_leave_days_per_year' => 5,
                        'allow_negative_balance' => false,
                    ],
                );
            }

            if (! $policy->rules()->exists()) {
                foreach (self::DEFAULT_RULES as $index => $rule) {
                    $policy->rules()->create([
                        ...$rule,
                        'active' => true,
                        'sort_order' => $index + 1,
                    ]);
                }
            }

            return $policy->fresh('rules');
        });
    }

    public function ensureAccount(User $user): PaidLeaveAccount
    {
        $account = PaidLeaveAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_code' => $user->user_code,
                'joined_date' => $user->joined_date ?: null,
                'active' => ! (bool) ($user->retire ?? false),
            ],
        );

        $account->setRelation('user', $user);

        return $account;
    }

    public function balanceForUserCode(string $userCode): ?array
    {
        $user = User::query()
            ->where('user_code', $userCode)
            ->with('paidLeaveAccount.grants')
            ->first();

        if (! $user || ! $user->paidLeaveAccount || ! $this->accountHasAuthoritativeBalance($user->paidLeaveAccount)) {
            return null;
        }

        return $this->balancePayload($user->paidLeaveAccount);
    }

    public function balancePayload(PaidLeaveAccount $account): array
    {
        $policy = $this->activePolicy();
        $minutesPerDay = $this->minutesPerLeaveDayForAccount($account, $policy);
        $grantMinutes = (int) $account->grants()->sum('remaining_minutes');
        $floatingAdjustments = (int) $account->adjustments()->whereNull('paid_leave_grant_id')->sum('amount_minutes');
        $minutes = $grantMinutes + $floatingAdjustments;

        return [
            'account_id' => $account->id,
            'user_id' => $account->user_id,
            'user_code' => $account->user_code,
            'minutes' => $minutes,
            'days' => $this->minutesToDays($minutes, $minutesPerDay),
            'minutes_per_day' => $minutesPerDay,
            'status' => 'success',
            'source' => 'glowd',
        ];
    }

    public function generateDueGrants(Carbon $runDate, ?Carbon $fromDate = null): array
    {
        $policy = $this->activePolicy();
        $rules = $policy->rules->where('active', true)->sortBy('service_months')->values();
        $fromDate = $fromDate ?: ($policy->effective_from ?: $runDate);
        $summary = ['checked' => 0, 'created' => 0, 'skipped_existing' => 0, 'skipped_no_rule' => 0, 'skipped_no_joined_date' => 0, 'skipped_attendance' => 0];

        User::query()
            ->where('partner_flag', 0)
            ->whereNotNull('joined_date')
            ->where(function ($query) {
                $query->where('retire', 0)
                    ->orWhere('retire_date', '>=', Carbon::today());
            })
            ->select('id', 'name', 'user_code', 'joined_date', 'retire', 'position_id', 'work_time_day')
            ->chunkById(100, function ($users) use ($policy, $rules, $runDate, $fromDate, &$summary) {
                foreach ($users as $user) {
                    $summary['checked']++;
                    $joined = $user->joined_date ? Carbon::parse($user->joined_date)->startOfDay() : null;
                    if (! $joined) {
                        $summary['skipped_no_joined_date']++;
                        continue;
                    }

                    $account = $this->ensureAccount($user);
                    $grantDate = $joined->copy()->addMonthsNoOverflow((int) $policy->first_grant_after_months);
                    
                    while ($grantDate->lessThanOrEqualTo($runDate)) {
                        if ($grantDate->greaterThanOrEqualTo($fromDate)) {
                            $serviceMonths = $this->serviceMonths($joined, $grantDate);
                            $attendance = $this->attendanceSummaryForGrant($user, $joined, $grantDate, $policy);
                            $grantPlan = $this->grantPlanForUser($user, $policy, $rules, $joined, $grantDate, $serviceMonths, $attendance);

                            if (! $attendance['eligible']) {
                                $summary['skipped_attendance']++;
                            } elseif (! $grantPlan) {
                                $summary['skipped_no_rule']++;
                            } else {
                                $created = $this->createAnnualGrantIfMissing($account, $policy, $grantPlan, $grantDate, $serviceMonths, $attendance);
                                $summary[$created ? 'created' : 'skipped_existing']++;
                            }
                        }

                        $grantDate->addMonthsNoOverflow((int) $policy->annual_grant_interval_months);
                    }
                }
            });

        return $summary;
    }

    public function importKintoneCurrentBalances(bool $dryRun = true): array
    {
        $employeeCodeField = '社員ｺｰﾄﾞ';
        $nameField = '氏名';
        $remainingDaysField = '残日数';
        $joinedDateField = '入社日';
        $retiredDateField = '退社日';
        $fields = ['$id', $employeeCodeField, $nameField, $remainingDaysField, $joinedDateField, $retiredDateField];
        $records = $this->kintone->getAllRecords(794, '', $fields);
        $policy = $this->activePolicy();
        $summary = [
            'dry_run' => $dryRun,
            'fetched' => count($records),
            'matched_users' => 0,
            'created_accounts' => 0,
            'created_opening_grants' => 0,
            'skipped_no_user' => 0,
            'skipped_blank_code' => 0,
            'skipped_existing_grant' => 0,
        ];

        foreach ($records as $record) {
            $userCode = trim((string) ($record[$employeeCodeField]['value'] ?? ''));
            if ($userCode === '') {
                $summary['skipped_blank_code']++;
                continue;
            }

            $user = User::query()->where('user_code', $userCode)->first();
            if (! $user) {
                $summary['skipped_no_user']++;
                continue;
            }

            $summary['matched_users']++;
            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($user, $record, $policy, $userCode, $remainingDaysField, $joinedDateField, $retiredDateField, &$summary) {
                $account = $this->ensureAccount($user);
                $wasRecentlyCreated = $account->wasRecentlyCreated;
                $recordId = (string) ($record['$id']['value'] ?? '');
                $sourceKey = "kintone:794:{$recordId}:opening-balance";

                if (PaidLeaveGrant::where('source_system', 'kintone')->where('source_key', $sourceKey)->exists()) {
                    $summary['skipped_existing_grant']++;
                    return;
                }

                $days = (float) ($record[$remainingDaysField]['value'] ?? 0);
                $joinedDate = $record[$joinedDateField]['value'] ?? null;
                $retiredDate = $record[$retiredDateField]['value'] ?? null;

                $account->fill([
                    'user_code' => $userCode,
                    'joined_date' => $user->joined_date ?: $joinedDate,
                    'last_synced_at' => now(),
                    'source_system' => 'kintone',
                    'source_app_id' => 794,
                    'source_record_id' => $recordId ?: null,
                    'source_payload' => [
                        'remaining_days' => $days,
                        'joined_date' => $joinedDate,
                        'retired_date' => $retiredDate,
                    ],
                ])->save();
                $minutes = $this->daysToMinutesForAccount($days, $account, $policy);

                PaidLeaveGrant::create([
                    'paid_leave_account_id' => $account->id,
                    'paid_leave_policy_id' => null,
                    'grant_type' => PaidLeaveGrant::TYPE_OPENING_BALANCE,
                    'granted_at' => Carbon::today()->toDateString(),
                    'expires_at' => null,
                    'grant_days' => $days,
                    'amount_minutes' => $minutes,
                    'remaining_minutes' => $minutes,
                    'planned_required_minutes' => 0,
                    'source_system' => 'kintone',
                    'source_key' => $sourceKey,
                    'source_app_id' => 794,
                    'source_record_id' => $recordId ?: null,
                    'note' => 'Kintone app 794 current remaining balance imported as opening balance.',
                ]);

                if ($wasRecentlyCreated) {
                    $summary['created_accounts']++;
                }
                $summary['created_opening_grants']++;
            });
        }

        return $summary;
    }

    public function expireElapsedGrants(Carbon $runDate): array
    {
        $summary = ['expired_grants' => 0, 'expired_minutes' => 0];

        PaidLeaveGrant::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $runDate->toDateString())
            ->where('remaining_minutes', '>', 0)
            ->chunkById(100, function ($grants) use ($runDate, &$summary) {
                foreach ($grants as $grant) {
                    DB::transaction(function () use ($grant, $runDate, &$summary) {
                        $amount = (int) $grant->remaining_minutes;
                        $sourceKey = "expire:grant:{$grant->id}:{$runDate->toDateString()}";

                        $grant->account->adjustments()->firstOrCreate(
                            ['source_system' => 'glowd', 'source_key' => $sourceKey],
                            [
                                'paid_leave_grant_id' => $grant->id,
                                'adjusted_on' => $runDate->toDateString(),
                                'amount_minutes' => -$amount,
                                'adjustment_type' => 'expiration',
                                'note' => 'Expired remaining paid leave.',
                            ],
                        );

                        $grant->update(['remaining_minutes' => 0]);
                        $summary['expired_grants']++;
                        $summary['expired_minutes'] += $amount;
                    });
                }
            });

        return $summary;
    }

    public function reconcileShiftUsagesForUserMonth(int $userId, int $year, int $month, ?int $createdByUserId = null): array
    {
        $summary = $this->emptyUsageReconcileSummary();
        $user = User::query()
            ->select('id', 'user_code', 'joined_date', 'retire')->find($userId);
        if (! $user) {
            $summary['skipped_no_user']++;

            return $summary;
        }

        $account = $this->ensureAccount($user);
        if (! $this->accountHasAuthoritativeBalance($account)) {
            $summary['skipped_no_authoritative_balance']++;

            return $summary;
        }

        $policy = $this->activePolicy();
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $externallyReflectedPlannedLeaveCutoff = $this->externallyReflectedPlannedLeaveCutoff($account);

        return DB::transaction(function () use ($account, $policy, $start, $end, $createdByUserId, $externallyReflectedPlannedLeaveCutoff, $summary) {
            $activePaidLeaveShifts = shiftRecord::query()
                ->where('user_id', $account->user_id)
                ->whereBetween('shift_day', [$start->toDateString(), $end->toDateString()])
                ->with('shiftType')
                ->orderBy('shift_day')
                ->orderBy('id')
                ->get()
                ->filter(fn (shiftRecord $shift) => $this->isPaidLeaveShift($shift));
            $summary['active_paid_leave_shifts'] = $activePaidLeaveShifts->count();

            $activeSourceKeys = $activePaidLeaveShifts->map(fn (shiftRecord $shift) => "shift:{$shift->id}")->all();

            $staleUsages = PaidLeaveUsage::query()
                ->where('paid_leave_account_id', $account->id)
                ->where('source_system', 'glowd')
                ->whereBetween('used_on', [$start->toDateString(), $end->toDateString()])
                ->where('source_key', 'like', 'shift:%')
                ->whereNotIn('source_key', $activeSourceKeys ?: ['__none__'])
                ->with('allocations.grant')
                ->get();
            $summary['deleted_stale_usages'] += $staleUsages->count();
            $staleUsages->each(fn (PaidLeaveUsage $usage) => $this->deleteUsageAndRestoreGrants($usage));

            foreach ($activePaidLeaveShifts as $shift) {
                $amount = $this->paidLeaveMinutesForShift($shift, $policy, $account);
                if ($amount <= 0) {
                    $summary['skipped_zero_amount']++;
                    continue;
                }

                $sourceKey = "shift:{$shift->id}";
                $usageType = $this->usageTypeForShift($shift);
                $existing = PaidLeaveUsage::query()
                    ->where('source_system', 'glowd')
                    ->where('source_key', $sourceKey)
                    ->with('allocations.grant')
                    ->first();

                // Kintone opening balances are already net of planned leaves that existed at import time.
                if ($this->isExternallyReflectedPlannedLeaveShift($shift, $externallyReflectedPlannedLeaveCutoff)) {
                    $summary['skipped_externally_reflected_planned']++;
                    if ($existing) {
                        $this->deleteUsageAndRestoreGrants($existing);
                        $summary['removed_externally_reflected_usages']++;
                    }

                    continue;
                }

                if ($existing && (int) $existing->amount_minutes === $amount && (string) $existing->usage_type === $usageType) {
                    $summary['skipped_existing']++;
                    continue;
                }

                if ($existing) {
                    $this->deleteUsageAndRestoreGrants($existing);
                    $summary['replaced_usages']++;
                }

                $this->createUsage($account, $shift, $amount, $policy, $createdByUserId);
                $summary['created_usages']++;
            }

            return $summary;
        });
    }

    public function reconcileShiftUsages(?Carbon $from = null, ?Carbon $to = null, ?int $userId = null): array
    {
        $shifts = shiftRecord::query()
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($from, fn ($query) => $query->whereDate('shift_day', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('shift_day', '<=', $to->toDateString()))
            ->where(function ($query) {
                $query->where('shift_type', 3)
                    ->orWhereHas('shiftType', function ($inner) {
                        $inner->where('name', 'like', '%有給%')
                            ->orWhere('name', 'like', '%年休%')
                            ->orWhere('name', 'like', '%時間休日%');
                    });
            })
            ->select('id', 'user_id', 'shift_day')
            ->orderBy('user_id')
            ->orderBy('shift_day')
            ->get();

        $periods = $shifts
            ->map(function (shiftRecord $shift) {
                $day = Carbon::parse($shift->shift_day);

                return [
                    'key' => "{$shift->user_id}:{$day->format('Y-m')}",
                    'user_id' => (int) $shift->user_id,
                    'year' => (int) $day->year,
                    'month' => (int) $day->month,
                ];
            })
            ->unique('key')
            ->values();

        $usageSummary = $this->emptyUsageReconcileSummary();

        foreach ($periods as $period) {
            $usageSummary = $this->mergeUsageReconcileSummary(
                $usageSummary,
                $this->reconcileShiftUsagesForUserMonth($period['user_id'], $period['year'], $period['month']),
            );
        }

        return [
            'paid_leave_shifts' => $shifts->count(),
            'reconciled_user_months' => $periods->count(),
            ...$usageSummary,
        ];
    }

    public function plannedLeaveUsers(int $year): Collection
    {
        $policy = $this->activePolicy();
        $hiddenNames = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $excludedPositions = [1, 2, 3, 4, 5, 14, 15];

        return User::query()
            ->where('partner_flag', 0)
            ->where('hide_flag', 0)
            ->where('retire', 0)
            ->whereNotIn('name', $hiddenNames)
            ->whereNotIn('position_id', $excludedPositions)
            ->with(['paidLeaveAccount.grants' => function ($query) {
                $query->where(function ($inner) {
                        $inner->where('planned_required_minutes', '>', 0)
                            ->orWhere('grant_type', PaidLeaveGrant::TYPE_ANNUAL);
                    })
                    ->orderBy('granted_at');
            }])
            ->select('id', 'name', 'position_id', 'user_code', 'joined_date')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($policy, $year) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'user_code' => $user->user_code,
                    'joined_date' => $user->joined_date,
                    'grant_periods' => $this->plannedLeavePeriodsForUserModel($user, $policy, $year, Carbon::today(), true),
                ];
            });
    }

    public function plannedLeavePeriodsForUser(User|int $user, ?int $year = null, ?Carbon $asOf = null, bool $includeExpected = true): Collection
    {
        $policy = $this->activePolicy();
        $user = $user instanceof User
            ? $user
            : User::query()
                ->where('id', $user)
                ->with('paidLeaveAccount.grants')
                ->first();

        if (! $user) {
            return collect();
        }

        return $this->plannedLeavePeriodsForUserModel($user, $policy, $year, $asOf ?: Carbon::today(), $includeExpected);
    }

    public function plannedLeaveReminderPeriodsForUser(User|int $user, ?Carbon $asOf = null): Collection
    {
        $asOf = ($asOf ?: Carbon::today())->copy()->startOfDay();

        return $this->plannedLeavePeriodsForUser($user, null, $asOf, true)
            ->filter(fn (array $period) => (bool) $period['planning_allowed'] && (float) $period['planned_remaining_days'] > 0)
            ->map(fn (array $period) => [
                'shift_count' => $period['shift_count'],
                'tempData' => $period['workTemp'],
                'remaining_days' => $period['planned_remaining_days'],
            ])
            ->values();
    }

    public function plannedLeaveWindowForUser(int $userId, int $plannedYear): ?array
    {
        $periods = $this->plannedLeavePeriodsForUser($userId, $plannedYear);
        $period = $periods->first(fn (array $period) => (int) $period['planned_year'] === $plannedYear)
            ?: $periods->first();
        if (! $period) {
            return null;
        }

        return [
            'workTemp' => $period['workTemp'],
            'consumed_days' => $period['planned_days'],
            'remaining_days' => $period['planned_remaining_days'],
            'period_start' => $period['period_start'],
            'period_end' => $period['period_end'],
            'planning_allowed_from' => $period['planning_allowed_from'],
            'planning_allowed' => $period['planning_allowed'],
            'source' => $period['source'],
            'period' => $period,
        ];
    }

    public function adminLedgerUsers(?string $search = null): Collection
    {
        $policy = $this->activePolicy();
        $hiddenNames = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];

        return User::query()
            ->where('partner_flag', 0)
            ->where('hide_flag', 0)
            ->whereNotIn('name', $hiddenNames)
            ->where(function ($query) {
                $query->where('retire', 0)
                    ->orWhere('retire_date', '>=', Carbon::today());
            })
            ->when($search, function ($query, string $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('user_code', 'like', "%{$search}%");
                });
            })
            ->with(['paidLeaveAccount.grants', 'paidLeaveAccount.usages', 'paidLeaveAccount.adjustments'])
            ->select('id', 'name', 'user_code', 'joined_date', 'position_id', 'retire', 'work_time_day')
            ->orderBy('name')
            ->limit(300)
            ->get()
            ->map(function (User $user) use ($policy) {
                $account = $this->ensureAccount($user);
                $account->loadMissing('grants', 'usages', 'adjustments');
                $minutesPerDay = $this->minutesPerLeaveDayForAccount($account, $policy);
                $grantMinutes = (int) $account->grants->sum('remaining_minutes');
                $floatingAdjustments = (int) $account->adjustments->whereNull('paid_leave_grant_id')->sum('amount_minutes');
                $balanceMinutes = $grantMinutes + $floatingAdjustments;
                $currentGrant = $this->currentGrantSummary($account, $policy, Carbon::today());

                return [
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'user_code' => $user->user_code,
                    'joined_date' => $this->dateString($user->joined_date),
                    'current_grant_date' => $currentGrant['date'],
                    'current_grant_period_end' => $currentGrant['period_end'],
                    'current_grant_days' => $currentGrant['grant_days'],
                    'current_grant_status' => $currentGrant['status'],
                    'balance_days' => $this->minutesToDays($balanceMinutes, $minutesPerDay),
                    'balance_minutes' => $balanceMinutes,
                    'minutes_per_day' => $minutesPerDay,
                    'grant_count' => $account->grants->count(),
                    'usage_count' => $account->usages->count(),
                    'adjustment_count' => $account->adjustments->count(),
                    'last_synced_at' => optional($account->last_synced_at)->toIso8601String(),
                    'source_system' => $account->source_system,
                    'authoritative' => $this->accountHasAuthoritativeBalance($account),
                ];
            });
    }

    public function adminLedgerHistory(PaidLeaveAccount $account): array
    {
        $policy = $this->activePolicy();
        $account->loadMissing('user', 'grants.creator', 'grants.usageAllocations.usage', 'usages.creator', 'usages.allocations.grant', 'adjustments.creator', 'adjustments.grant');
        $minutesPerDay = $this->minutesPerLeaveDayForAccount($account, $policy);
        $events = collect();

        foreach ($account->grants as $grant) {
            $events->push([
                'type' => 'grant',
                'date' => optional($grant->granted_at)->toDateString(),
                'grant_year' => optional($grant->granted_at)->format('Y'),
                'amount_days' => $this->minutesToDays((int) $grant->amount_minutes, $minutesPerDay),
                'amount_minutes' => (int) $grant->amount_minutes,
                'remaining_days' => $this->minutesToDays((int) $grant->remaining_minutes, $minutesPerDay),
                'label' => $grant->grant_type === PaidLeaveGrant::TYPE_OPENING_BALANCE ? '開始残高' : '自動付与',
                'note' => $grant->note,
                'source' => $grant->source_system,
                'actor' => $this->actorPayload($grant->creator),
                'expires_at' => optional($grant->expires_at)->toDateString(),
            ]);
        }

        foreach ($account->usages as $usage) {
            $allocations = $this->usageAllocationPayload($usage, $minutesPerDay);
            $events->push([
                'type' => 'usage',
                'date' => optional($usage->used_on)->toDateString(),
                'usage_type' => $usage->usage_type,
                'grant_year' => collect($allocations)->pluck('grant_year')->filter()->unique()->implode(', '),
                'grant_allocations' => $allocations,
                'amount_days' => -$this->minutesToDays((int) $usage->amount_minutes, $minutesPerDay),
                'amount_minutes' => -(int) $usage->amount_minutes,
                'label' => match ($usage->usage_type) {
                    'planned_shift' => '計画有給使用',
                    'hourly_shift' => '時間有休使用',
                    default => '有休使用',
                },
                'note' => $usage->note,
                'source' => $usage->source_system,
                'actor' => $this->actorPayload($usage->creator),
                'shift_record_id' => $usage->shift_record_id,
            ]);
        }

        foreach ($account->adjustments as $adjustment) {
            $events->push([
                'type' => 'adjustment',
                'date' => optional($adjustment->adjusted_on)->toDateString(),
                'grant_year' => optional($adjustment->grant?->granted_at)->format('Y'),
                'amount_days' => $this->minutesToDays((int) $adjustment->amount_minutes, $minutesPerDay),
                'amount_minutes' => (int) $adjustment->amount_minutes,
                'label' => $this->adjustmentLabel((string) $adjustment->adjustment_type),
                'note' => $adjustment->note,
                'source' => $adjustment->source_system,
                'actor' => $this->actorPayload($adjustment->creator),
                'grant_id' => $adjustment->paid_leave_grant_id,
            ]);
        }

        $events = $events
            ->sortByDesc(fn (array $event) => ($event['date'] ?? '') . ':' . $event['type'])
            ->values();

        return [
            'account' => [
                'id' => $account->id,
                'user_id' => $account->user_id,
                'name' => $account->user?->name,
                'user_code' => $account->user_code,
                'joined_date' => $this->dateString($account->joined_date),
                'source_system' => $account->source_system,
                'last_synced_at' => optional($account->last_synced_at)->toIso8601String(),
            ],
            'balance' => $this->balancePayload($account),
            'grants' => $account->grants->sortByDesc('granted_at')->map(fn (PaidLeaveGrant $grant) => [
                'id' => $grant->id,
                'grant_type' => $grant->grant_type,
                'granted_at' => optional($grant->granted_at)->toDateString(),
                'grant_year' => optional($grant->granted_at)->format('Y'),
                'expires_at' => optional($grant->expires_at)->toDateString(),
                'grant_days' => $grant->grant_days,
                'amount_days' => $this->minutesToDays((int) $grant->amount_minutes, $minutesPerDay),
                'amount_minutes' => (int) $grant->amount_minutes,
                'remaining_days' => $this->minutesToDays((int) $grant->remaining_minutes, $minutesPerDay),
                'remaining_minutes' => (int) $grant->remaining_minutes,
                'planned_required_days' => $this->minutesToDays($this->plannedRequiredMinutesForGrant($grant), $minutesPerDay),
                'source_system' => $grant->source_system,
                'note' => $grant->note,
            ])->values(),
            'events' => $events,
        ];
    }

    public function createManualAdjustment(PaidLeaveAccount $account, float $amountDays, string $adjustedOn, ?string $note, ?int $createdByUserId = null): array
    {
        $policy = $this->activePolicy();
        $amountMinutes = $this->daysToMinutesForAccount($amountDays, $account, $policy);

        $account->adjustments()->create([
            'adjusted_on' => Carbon::parse($adjustedOn)->toDateString(),
            'amount_minutes' => $amountMinutes,
            'adjustment_type' => 'manual',
            'source_system' => 'glowd',
            'source_key' => 'manual:' . uniqid('', true),
            'note' => $note,
            'created_by_user_id' => $createdByUserId,
        ]);

        return $this->adminLedgerHistory($account->fresh());
    }

    private function plannedShiftsForPeriod(int $userId, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->where('shift_type', 3)
            ->whereBetween('shift_day', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with(['old_shift' => function ($query) {
                $query->select('id', 'shift_day', 'shift_type')->with('shiftType')->withTrashed();
            }])
            ->select('shift_type', 'shift_day', 'user_id', 'planned_year', 'id', 'descendant_of')
            ->orderBy('shift_day')
            ->get();
    }

    private function plannedRequiredMinutesForGrant(PaidLeaveGrant $grant): int
    {
        $storedMinutes = (int) ($grant->planned_required_minutes ?? 0);
        if ($storedMinutes > 0) {
            return $storedMinutes;
        }

        if ($grant->grant_type === PaidLeaveGrant::TYPE_ANNUAL && (int) $grant->amount_minutes > 0) {
            return (int) round(((int) $grant->amount_minutes) / 2);
        }

        return 0;
    }

    private function dateString(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function actorPayload(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'user_code' => $user->user_code,
        ];
    }

    private function plannedLeavePeriodsForUserModel(User $user, PaidLeavePolicy $policy, ?int $year, Carbon $asOf, bool $includeExpected): Collection
    {
        $asOf = $asOf->copy()->startOfDay();
        $targetYears = collect($year ? [$year] : [$asOf->year - 1, $asOf->year, $asOf->year + 1])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $user->loadMissing('paidLeaveAccount.grants');
        $account = $user->paidLeaveAccount ?: $this->ensureAccount($user);
        $account->setRelation('user', $user);
        $account->loadMissing('grants');

        $periods = collect();
        $actualGrantDates = collect();

        foreach ($account->grants as $grant) {
            if ($grant->grant_type !== PaidLeaveGrant::TYPE_ANNUAL || ! $grant->granted_at) {
                continue;
            }

            $requiredMinutes = $this->plannedRequiredMinutesForGrant($grant);
            if ($requiredMinutes <= 0) {
                continue;
            }

            $periodStart = $grant->granted_at->copy()->startOfDay();
            $periodEnd = $periodStart->copy()->addYear()->subDay();
            if (! $targetYears->contains(fn (int $targetYear) => $this->periodOverlapsYear($periodStart, $periodEnd, $targetYear))) {
                continue;
            }

            $actualGrantDates->push($periodStart->toDateString());
            $periods->push($this->plannedLeavePeriodPayload(
                user: $user,
                policy: $policy,
                periodStart: $periodStart,
                requiredMinutes: $requiredMinutes,
                grantDays: (float) $grant->grant_days,
                source: 'glowd',
                asOf: $asOf,
                grant: $grant,
            ));
        }

        if ($includeExpected) {
            $expectedYears = $year
                ? collect([$year - 1, $year, $year + 1])
                : $targetYears;

            foreach ($expectedYears->unique()->values() as $targetYear) {
                $expectedGrantDate = $this->expectedGrantDateForYear($account, $policy, (int) $targetYear);
                if (! $expectedGrantDate || $actualGrantDates->contains($expectedGrantDate->toDateString())) {
                    continue;
                }

                $grantDays = $this->expectedGrantDaysForPlanning($user, $account, $policy, $expectedGrantDate);
                if (! $grantDays || $grantDays <= 0) {
                    continue;
                }

                $minutesPerDay = $this->minutesPerLeaveDayForAccount($account, $policy);
                $requiredMinutes = (int) round($this->daysToMinutes((float) $grantDays, $minutesPerDay) / 2);
                if ($requiredMinutes <= 0) {
                    continue;
                }

                $periodStart = $expectedGrantDate->copy()->startOfDay();
                $periodEnd = $periodStart->copy()->addYear()->subDay();
                if (! $targetYears->contains(fn (int $targetYear) => $this->periodOverlapsYear($periodStart, $periodEnd, $targetYear))) {
                    continue;
                }

                $periods->push($this->plannedLeavePeriodPayload(
                    user: $user,
                    policy: $policy,
                    periodStart: $periodStart,
                    requiredMinutes: $requiredMinutes,
                    grantDays: (float) $grantDays,
                    source: 'expected',
                    asOf: $asOf,
                ));
            }
        }

        foreach ($targetYears as $targetYear) {
            if ($periods->contains(fn (array $period) => $this->periodOverlapsYear(
                Carbon::parse($period['period_start'])->startOfDay(),
                Carbon::parse($period['period_end'])->endOfDay(),
                (int) $targetYear
            ))) {
                continue;
            }

            $legacyPeriod = $this->legacyPlannedLeavePeriodForYear($user, $policy, (int) $targetYear, $asOf);
            if ($legacyPeriod) {
                $periods->push($legacyPeriod);
            }
        }

        return $periods
            ->sortBy('period_start')
            ->values();
    }

    private function expectedGrantDaysForPlanning(User $user, PaidLeaveAccount $account, PaidLeavePolicy $policy, Carbon $grantDate): ?float
    {
        if (! $account->joined_date) {
            return null;
        }

        if ((int) ($user->position_id ?? 0) === self::PART_TIME_POSITION_ID) {
            return $this->expectedGrantDaysFor($account, $policy, $grantDate);
        }

        $rules = $policy->rules->where('active', true)->sortBy('service_months')->values();
        $joined = Carbon::parse($account->joined_date)->startOfDay();
        $serviceMonths = $this->serviceMonths($joined, $grantDate);

        return $this->ruleForServiceMonths($rules, $serviceMonths)?->grant_days;
    }

    private function legacyPlannedLeavePeriodForYear(User $user, PaidLeavePolicy $policy, int $year, Carbon $asOf): ?array
    {
        if (! $user->user_code) {
            return null;
        }

        $legacyTemp = workTemp::query()
            ->where('user_code', $user->user_code)
            ->whereYear('date', $year)
            ->first();

        if (! $legacyTemp) {
            return null;
        }

        $minutesPerDay = $this->minutesPerLeaveDayForUser($user, $policy);
        $requiredDays = (float) ($legacyTemp->planned_days ?? 0);

        return $this->plannedLeavePeriodPayload(
            user: $user,
            policy: $policy,
            periodStart: Carbon::parse($legacyTemp->date)->startOfDay(),
            requiredMinutes: $this->daysToMinutes($requiredDays, $minutesPerDay),
            grantDays: $requiredDays * 2,
            source: 'legacy',
            asOf: $asOf,
            legacyTemp: $legacyTemp,
        );
    }

    private function plannedLeavePeriodPayload(
        User $user,
        PaidLeavePolicy $policy,
        Carbon $periodStart,
        int $requiredMinutes,
        ?float $grantDays,
        string $source,
        Carbon $asOf,
        ?PaidLeaveGrant $grant = null,
        ?workTemp $legacyTemp = null,
    ): array {
        $periodStart = $periodStart->copy()->startOfDay();
        $periodEnd = $periodStart->copy()->addYear()->subDay();
        $minutesPerDay = $this->minutesPerLeaveDayForUser($user, $policy);
        $plannedShifts = $this->plannedShiftsForPeriod((int) $user->id, $periodStart, $periodEnd);
        $plannedMinutes = $plannedShifts->count() * $minutesPerDay;
        $planningAllowedFrom = $this->plannedLeavePlanningAllowedFrom($periodStart);
        $plannedRequiredDays = $this->minutesToDays($requiredMinutes, $minutesPerDay);
        $plannedDays = $this->minutesToDays($plannedMinutes, $minutesPerDay);
        $plannedRemainingDays = $this->minutesToDays(max(0, $requiredMinutes - $plannedMinutes), $minutesPerDay);
        $periodId = $grant
            ? "grant:{$grant->id}"
            : ($legacyTemp ? "legacy:{$legacyTemp->id}" : "expected:{$user->id}:{$periodStart->toDateString()}");

        $workTemp = [
            'id' => $periodId,
            'date' => $periodStart->toDateString(),
            'endDate' => $periodEnd->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'planned_days' => $plannedRequiredDays,
            'granted_days' => $grantDays,
            'grant_id' => $grant?->id,
            'source' => $source,
            'planning_allowed_from' => $planningAllowedFrom->toDateString(),
            'planning_allowed' => $asOf->greaterThanOrEqualTo($planningAllowedFrom),
            'user_code' => $user->user_code,
            'user_name' => $user->name,
        ];

        return [
            'id' => $periodId,
            'grant_id' => $grant?->id,
            'source' => $source,
            'legacy' => $source === 'legacy',
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'planning_allowed_from' => $planningAllowedFrom->toDateString(),
            'planning_allowed' => $asOf->greaterThanOrEqualTo($planningAllowedFrom),
            'planned_year' => (int) $periodStart->year,
            'granted_days' => $grantDays,
            'remaining_days' => $grant ? $this->minutesToDays((int) $grant->remaining_minutes, $minutesPerDay) : null,
            'planned_required_days' => $plannedRequiredDays,
            'planned_days' => $plannedDays,
            'planned_remaining_days' => $plannedRemainingDays,
            'shift_count' => $plannedShifts->count(),
            'status' => $plannedMinutes >= $requiredMinutes ? 'ok' : 'short',
            'shift_records' => $plannedShifts,
            'workTemp' => $workTemp,
        ];
    }

    private function plannedLeavePlanningAllowedFrom(Carbon $periodStart): Carbon
    {
        $previousMonth = $periodStart->copy()->subMonthNoOverflow()->startOfMonth();
        $day = min(self::PLANNED_LEAVE_PLANNING_OPEN_DAY, $previousMonth->daysInMonth);

        return $previousMonth->day($day)->startOfDay();
    }

    private function periodOverlapsYear(Carbon $periodStart, Carbon $periodEnd, int $year): bool
    {
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        return $periodStart->lessThanOrEqualTo($yearEnd)
            && $periodEnd->greaterThanOrEqualTo($yearStart);
    }

    private function currentGrantSummary(PaidLeaveAccount $account, PaidLeavePolicy $policy, Carbon $asOf): array
    {
        $intervalMonths = max(1, (int) $policy->annual_grant_interval_months);
        $annualGrants = $account->grants
            ->filter(fn (PaidLeaveGrant $grant) => $grant->grant_type === PaidLeaveGrant::TYPE_ANNUAL && $grant->granted_at)
            ->sortByDesc('granted_at')
            ->values();

        $targetYear = (int) $asOf->year;
        $currentGrant = $annualGrants->first(
            fn (PaidLeaveGrant $grant) => (int) $grant->granted_at->format('Y') === $targetYear
        );

        if ($currentGrant) {
            $periodStart = $currentGrant->granted_at->copy()->startOfDay();

            return [
                'date' => $periodStart->toDateString(),
                'period_end' => $periodStart->copy()->addMonthsNoOverflow($intervalMonths)->subDay()->toDateString(),
                'grant_days' => $currentGrant->grant_days,
                'status' => 'generated',
            ];
        }

        $expectedGrantDate = $this->expectedGrantDateForYear($account, $policy, $targetYear);
        if (! $expectedGrantDate) {
            return [
                'date' => null,
                'period_end' => null,
                'grant_days' => null,
                'status' => 'none',
            ];
        }

        return [
            'date' => $expectedGrantDate->toDateString(),
            'period_end' => $expectedGrantDate->copy()->addMonthsNoOverflow($intervalMonths)->subDay()->toDateString(),
            'grant_days' => $this->expectedGrantDaysFor($account, $policy, $expectedGrantDate),
            'status' => 'expected',
        ];
    }

    private function expectedGrantDateForYear(PaidLeaveAccount $account, PaidLeavePolicy $policy, int $targetYear): ?Carbon
    {
        if (! $account->joined_date) {
            return null;
        }

        $grantDate = Carbon::parse($account->joined_date)
            ->startOfDay()
            ->addMonthsNoOverflow((int) $policy->first_grant_after_months);
        $intervalMonths = max(1, (int) $policy->annual_grant_interval_months);

        while ((int) $grantDate->format('Y') < $targetYear) {
            $grantDate->addMonthsNoOverflow($intervalMonths);
        }

        return (int) $grantDate->format('Y') === $targetYear ? $grantDate : null;
    }

    private function expectedGrantDaysFor(PaidLeaveAccount $account, PaidLeavePolicy $policy, Carbon $grantDate): ?float
    {
        if (! $account->joined_date) {
            return null;
        }

        $rules = $policy->rules->where('active', true)->sortBy('service_months')->values();
        $joined = Carbon::parse($account->joined_date)->startOfDay();
        $serviceMonths = $this->serviceMonths($joined, $grantDate);
        $user = $account->relationLoaded('user') ? $account->user : $account->user()->first();

        if (! $user) {
            return $this->ruleForServiceMonths($rules, $serviceMonths)?->grant_days;
        }

        $attendance = $this->attendanceSummaryForGrant($user, $joined, $grantDate, $policy);
        if (! $attendance['eligible']) {
            return null;
        }

        return $this->grantPlanForUser($user, $policy, $rules, $joined, $grantDate, $serviceMonths, $attendance)['grant_days'] ?? null;
    }

    private function grantPlanForUser(User $user, PaidLeavePolicy $policy, Collection $rules, Carbon $joined, Carbon $grantDate, int $serviceMonths, array $attendance): ?array
    {
        if ((int) ($user->position_id ?? 0) === self::PART_TIME_POSITION_ID) {
            return $this->partTimeGrantPlanForUser($user, $policy, $rules, $joined, $grantDate, $serviceMonths, $attendance);
        }

        $rule = $this->ruleForServiceMonths($rules, $serviceMonths);
        if (! $rule) {
            return null;
        }

        return [
            'basis' => 'regular',
            'rule' => $rule,
            'legal_min_days' => (float) $rule->legal_min_days,
            'grant_days' => (float) $rule->grant_days,
        ];
    }

    private function partTimeGrantPlanForUser(User $user, PaidLeavePolicy $policy, Collection $rules, Carbon $joined, Carbon $grantDate, int $serviceMonths, array $attendance): ?array
    {
        $annualWorkDays = $this->annualizedValidWorkDaysForGrant($user, $joined, $grantDate, $policy);
        $minutesPerDay = $this->minutesPerLeaveDayForUser($user, $policy);
        $weeklyWorkMinutes = ($annualWorkDays['annual_work_days'] / 52) * $minutesPerDay;

        if ($annualWorkDays['annual_work_days'] >= 217 || $weeklyWorkMinutes >= 30 * 60) {
            $rule = $this->ruleForServiceMonths($rules, $serviceMonths);
            if (! $rule) {
                return null;
            }

            return [
                'basis' => 'part_time_regular',
                'rule' => $rule,
                'legal_min_days' => (float) $rule->legal_min_days,
                'grant_days' => (float) $rule->grant_days,
                ...$annualWorkDays,
            ];
        }

        $bucket = $this->serviceMonthBucket($serviceMonths);
        foreach (self::PART_TIME_GRANT_TABLE as $row) {
            if ($annualWorkDays['annual_work_days'] < $row['min_annual_days'] || $annualWorkDays['annual_work_days'] > $row['max_annual_days']) {
                continue;
            }

            $grantDays = (float) ($row['days'][$bucket] ?? 0);
            if ($grantDays <= 0) {
                return null;
            }

            return [
                'basis' => 'part_time_proportional',
                'rule' => null,
                'legal_min_days' => $grantDays,
                'grant_days' => $grantDays,
                'part_time_min_annual_days' => $row['min_annual_days'],
                'part_time_max_annual_days' => $row['max_annual_days'],
                ...$annualWorkDays,
            ];
        }

        return null;
    }

    private function attendanceSummaryForGrant(User $user, Carbon $joined, Carbon $grantDate, PaidLeavePolicy $policy): array
    {
        $period = $this->grantLookbackPeriod($joined, $grantDate, $policy);
        $validWorkDates = $this->validTimecardWorkDates((int) $user->id, $period['start'], $period['end']);
        $paidLeaveDates = $this->paidLeaveShiftDates((int) $user->id, $period['start'], $period['end']);
        $attendedDates = $validWorkDates->merge($paidLeaveDates)->unique()->values();
        $scheduledDates = $this->scheduledWorkDates((int) $user->id, $period['start'], $period['end'])
            ->merge($paidLeaveDates)
            ->unique()
            ->values();

        $scheduledDays = max($scheduledDates->count(), $attendedDates->count());
        $attendedDays = $attendedDates->count();
        $rate = $scheduledDays > 0 ? round(($attendedDays / $scheduledDays) * 100, 2) : 0.0;
        $minimumRate = (float) $policy->minimum_attendance_rate;

        return [
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
            'months' => $period['months'],
            'scheduled_days' => $scheduledDays,
            'attended_days' => $attendedDays,
            'rate' => $rate,
            'minimum_rate' => $minimumRate,
            'eligible' => $rate >= $minimumRate,
        ];
    }

    private function annualizedValidWorkDaysForGrant(User $user, Carbon $joined, Carbon $grantDate, PaidLeavePolicy $policy): array
    {
        $period = $this->grantLookbackPeriod($joined, $grantDate, $policy);
        $workedDays = $this->validTimecardWorkDates((int) $user->id, $period['start'], $period['end'])->count();
        $annualWorkDays = (int) round($workedDays * 12 / max(1, $period['months']));

        return [
            'worked_days' => $workedDays,
            'annual_work_days' => $annualWorkDays,
            'work_days_period_start' => $period['start']->toDateString(),
            'work_days_period_end' => $period['end']->toDateString(),
        ];
    }

    private function grantLookbackPeriod(Carbon $joined, Carbon $grantDate, PaidLeavePolicy $policy): array
    {
        $serviceMonths = $this->serviceMonths($joined, $grantDate);
        $firstGrantMonths = max(1, (int) $policy->first_grant_after_months);
        $intervalMonths = max(1, (int) $policy->annual_grant_interval_months);
        $months = $serviceMonths <= $firstGrantMonths ? $firstGrantMonths : $intervalMonths;
        $start = $grantDate->copy()->subMonthsNoOverflow($months)->startOfDay();
        if ($start->lessThan($joined)) {
            $start = $joined->copy()->startOfDay();
        }

        return [
            'start' => $start,
            'end' => $grantDate->copy()->subDay()->endOfDay(),
            'months' => $months,
        ];
    }

    private function validTimecardWorkDates(int $userId, Carbon $start, Carbon $end): Collection
    {
        return timecardRecord::query()
            ->where('user_id', $userId)
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->where('status_flag', timecardRecord::STATUS_APPROVED)
            ->where(function ($query) {
                $query->where('work_time', '>', 0)
                    ->orWhereNotNull('start_time')
                    ->orWhereNotNull('end_time');
            })
            ->pluck('day')
            ->map(fn ($day) => Carbon::parse($day)->toDateString())
            ->unique()
            ->values();
    }

    private function scheduledWorkDates(int $userId, Carbon $start, Carbon $end): Collection
    {
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->whereBetween('shift_day', [$start->toDateString(), $end->toDateString()])
            ->with('shiftType')
            ->get()
            ->filter(fn (shiftRecord $shift) => $this->isScheduledWorkShift($shift))
            ->pluck('shift_day')
            ->map(fn ($day) => Carbon::parse($day)->toDateString())
            ->unique()
            ->values();
    }

    private function paidLeaveShiftDates(int $userId, Carbon $start, Carbon $end): Collection
    {
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->whereBetween('shift_day', [$start->toDateString(), $end->toDateString()])
            ->with('shiftType')
            ->get()
            ->filter(fn (shiftRecord $shift) => $this->isPaidLeaveShift($shift))
            ->pluck('shift_day')
            ->map(fn ($day) => Carbon::parse($day)->toDateString())
            ->unique()
            ->values();
    }

    private function serviceMonthBucket(int $serviceMonths): int
    {
        return collect([6, 18, 30, 42, 54, 66, 78])
            ->filter(fn (int $months) => $months <= $serviceMonths)
            ->max() ?? 6;
    }

    private function isScheduledWorkShift(shiftRecord $shift): bool
    {
        $type = $shift->shiftType;
        if (! $type) {
            return false;
        }

        if ($this->isPaidLeaveShift($shift)) {
            return true;
        }

        if (in_array((int) $type->id, [0, shiftType::LEGAL_HOLIDAY_ID], true)) {
            return false;
        }

        return (int) $type->full_day !== 2;
    }

    private function usageAllocationPayload(PaidLeaveUsage $usage, int $minutesPerDay): array
    {
        return $usage->allocations
            ->map(fn ($allocation) => [
                'grant_id' => $allocation->paid_leave_grant_id,
                'grant_year' => optional($allocation->grant?->granted_at)->format('Y'),
                'amount_days' => $this->minutesToDays((int) $allocation->amount_minutes, $minutesPerDay),
                'amount_minutes' => (int) $allocation->amount_minutes,
            ])
            ->values()
            ->all();
    }

    private function emptyUsageReconcileSummary(): array
    {
        return [
            'active_paid_leave_shifts' => 0,
            'created_usages' => 0,
            'replaced_usages' => 0,
            'skipped_existing' => 0,
            'skipped_externally_reflected_planned' => 0,
            'removed_externally_reflected_usages' => 0,
            'skipped_zero_amount' => 0,
            'deleted_stale_usages' => 0,
            'skipped_no_user' => 0,
            'skipped_no_authoritative_balance' => 0,
        ];
    }

    private function mergeUsageReconcileSummary(array $base, array $addition): array
    {
        foreach ($this->emptyUsageReconcileSummary() as $key => $value) {
            $base[$key] = (int) ($base[$key] ?? 0) + (int) ($addition[$key] ?? 0);
        }

        return $base;
    }

    private function accountHasAuthoritativeBalance(PaidLeaveAccount $account): bool
    {
        return $account->grants()->exists()
            || $account->usages()->exists()
            || $account->adjustments()->exists();
    }

    private function externallyReflectedPlannedLeaveCutoff(PaidLeaveAccount $account): ?Carbon
    {
        $openingGrant = PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('grant_type', PaidLeaveGrant::TYPE_OPENING_BALANCE)
            ->where('source_system', 'kintone')
            ->orderBy('created_at')
            ->first(['created_at']);

        if (! $openingGrant) {
            return null;
        }

        $cutoff = $account->last_synced_at ? Carbon::parse($account->last_synced_at) : null;
        $grantCreatedAt = $openingGrant->created_at ? Carbon::parse($openingGrant->created_at) : null;

        if ($grantCreatedAt && (! $cutoff || $grantCreatedAt->greaterThan($cutoff))) {
            return $grantCreatedAt;
        }

        return $cutoff;
    }

    private function isExternallyReflectedPlannedLeaveShift(shiftRecord $shift, ?Carbon $cutoff): bool
    {
        if (! $cutoff || (int) $shift->shift_type !== 3) {
            return false;
        }

        $shiftDay = $shift->shift_day ? Carbon::parse($shift->shift_day)->startOfDay() : null;
        if (! $shiftDay || $shiftDay->greaterThan($cutoff->copy()->startOfDay())) {
            return false;
        }

        if ($this->shiftCreatedNoLaterThan($shift, $cutoff)) {
            return true;
        }

        return $this->hasExternallyReflectedPlannedLeaveAncestor($shift, $cutoff);
    }

    private function hasExternallyReflectedPlannedLeaveAncestor(shiftRecord $shift, Carbon $cutoff): bool
    {
        $ancestorId = (int) ($shift->descendant_of ?? 0);
        $seen = [];

        while ($ancestorId > 0) {
            if (isset($seen[$ancestorId])) {
                return false;
            }
            $seen[$ancestorId] = true;

            $ancestor = shiftRecord::withTrashed()
                ->select('id', 'shift_type', 'descendant_of', 'created_at', 'updated_at')
                ->find($ancestorId);

            if (! $ancestor) {
                return false;
            }

            if ((int) $ancestor->shift_type === 3 && $this->shiftCreatedNoLaterThan($ancestor, $cutoff)) {
                return true;
            }

            $ancestorId = (int) ($ancestor->descendant_of ?? 0);
        }

        return false;
    }

    private function shiftCreatedNoLaterThan(shiftRecord $shift, Carbon $cutoff): bool
    {
        $createdAt = $shift->created_at ?: $shift->updated_at;

        return $createdAt && Carbon::parse($createdAt)->lessThanOrEqualTo($cutoff);
    }

    private function adjustmentLabel(string $type): string
    {
        return match ($type) {
            'expiration' => '失効',
            'negative_usage' => 'マイナス使用',
            'manual' => '手動調整',
            default => $type,
        };
    }

    private function createAnnualGrantIfMissing(
        PaidLeaveAccount $account,
        PaidLeavePolicy $policy,
        array $grantPlan,
        Carbon $grantDate,
        int $serviceMonths,
        array $attendance
    ): bool {
        $sourceKey = "auto:user:{$account->user_id}:{$grantDate->toDateString()}";
        if (PaidLeaveGrant::where('paid_leave_account_id', $account->id)->where('source_system', 'glowd')->where('source_key', $sourceKey)->exists()) {
            return false;
        }

        /** @var PaidLeaveGrantRule|null $rule */
        $rule = $grantPlan['rule'] ?? null;
        $grantDays = (float) $grantPlan['grant_days'];
        $minutesPerDay = $this->minutesPerLeaveDayForAccount($account, $policy);
        $amountMinutes = $this->daysToMinutes($grantDays, $minutesPerDay);

        PaidLeaveGrant::create([
            'paid_leave_account_id' => $account->id,
            'paid_leave_policy_id' => $policy->id,
            'grant_type' => PaidLeaveGrant::TYPE_ANNUAL,
            'granted_at' => $grantDate->toDateString(),
            'expires_at' => $grantDate->copy()->addMonthsNoOverflow((int) $policy->expires_after_months)->subDay()->toDateString(),
            'service_months' => $serviceMonths,
            'grant_days' => $grantDays,
            'amount_minutes' => $amountMinutes,
            'remaining_minutes' => $amountMinutes,
            'planned_required_minutes' => (int) round($amountMinutes / 2),
            'policy_snapshot' => [
                'policy_id' => $policy->id,
                'rule_id' => $rule?->id,
                'service_months' => $serviceMonths,
                'legal_min_days' => $grantPlan['legal_min_days'] ?? null,
                'grant_days' => $grantDays,
                'grant_basis' => $grantPlan['basis'] ?? 'regular',
                'annual_work_days' => $grantPlan['annual_work_days'] ?? null,
                'worked_days' => $grantPlan['worked_days'] ?? null,
                'minutes_per_leave_day' => $minutesPerDay,
                'expires_after_months' => $policy->expires_after_months,
                'minimum_attendance_rate' => $policy->minimum_attendance_rate,
                'attendance_rate' => $attendance['rate'],
                'attendance_days' => $attendance['attended_days'],
                'scheduled_days' => $attendance['scheduled_days'],
                'attendance_period_start' => $attendance['period_start'],
                'attendance_period_end' => $attendance['period_end'],
            ],
            'source_system' => 'glowd',
            'source_key' => $sourceKey,
        ]);

        $account->update(['last_granted_at' => now()]);

        return true;
    }

    private function createUsage(PaidLeaveAccount $account, shiftRecord $shift, int $amount, PaidLeavePolicy $policy, ?int $createdByUserId = null): void
    {
        $usageType = $this->usageTypeForShift($shift);
        if ($usageType === 'hourly_shift') {
            $this->assertHourlyLeaveWithinCap($account, $shift, $amount, $policy);
        }

        $remaining = $amount;
        $usage = PaidLeaveUsage::create([
            'paid_leave_account_id' => $account->id,
            'shift_record_id' => $shift->id,
            'used_on' => Carbon::parse($shift->shift_day)->toDateString(),
            'amount_minutes' => $amount,
            'usage_type' => $usageType,
            'status' => 'confirmed',
            'source_system' => 'glowd',
            'source_key' => "shift:{$shift->id}",
            'created_by_user_id' => $createdByUserId,
        ]);

        $grants = PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('remaining_minutes', '>', 0)
            ->whereDate('granted_at', '<=', $usage->used_on)
            ->where(function ($query) use ($usage) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $usage->used_on);
            })
            ->orderByRaw('expires_at is null')
            ->orderBy('expires_at')
            ->orderBy('granted_at')
            ->lockForUpdate()
            ->get();

        foreach ($grants as $grant) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (int) $grant->remaining_minutes);
            $grant->decrement('remaining_minutes', $take);
            $usage->allocations()->create([
                'paid_leave_grant_id' => $grant->id,
                'amount_minutes' => $take,
            ]);
            $remaining -= $take;
        }
        
        if ($remaining > 0 && ! $policy->allow_negative_balance) {
            $this->deleteUsageAndRestoreGrants($usage->fresh('allocations.grant'));
            throw ValidationException::withMessages(['message' => '有休残数が不足しています。']);
        }

        if ($remaining > 0) {
            $account->adjustments()->create([
                'adjusted_on' => Carbon::parse($shift->shift_day)->toDateString(),
                'amount_minutes' => -$remaining,
                'adjustment_type' => 'negative_usage',
                'source_system' => 'glowd',
                'source_key' => "usage-negative:{$usage->id}",
                'note' => 'Negative paid-leave balance allowed by policy.',
            ]);
        }
    }

    private function deleteUsageAndRestoreGrants(PaidLeaveUsage $usage): void
    {
        $usage->loadMissing('allocations.grant');
        foreach ($usage->allocations as $allocation) {
            $allocation->grant?->increment('remaining_minutes', (int) $allocation->amount_minutes);
        }

        $usage->account?->adjustments()
            ->where('source_system', 'glowd')
            ->where('source_key', "usage-negative:{$usage->id}")
            ->delete();

        $usage->delete();
    }

    private function isPaidLeaveShift(shiftRecord $shift): bool
    {
        $type = $shift->shiftType;
        if (! $type) {
            return false;
        }

        $name = (string) ($type->name ?? '');

        return (int) $type->id === 3
            || str_contains($name, '有給')
            || str_contains($name, '年休')
            || str_contains($name, '時間休日');
    }

    private function usageTypeForShift(shiftRecord $shift): string
    {
        if ((int) $shift->shift_type === 3) {
            return 'planned_shift';
        }

        return $this->isHourlyPaidLeaveShift($shift) ? 'hourly_shift' : 'shift';
    }

    private function isHourlyPaidLeaveShift(shiftRecord $shift): bool
    {
        $type = $shift->shiftType;
        if (! $type) {
            return false;
        }

        $name = (string) ($type->name ?? '');

        return str_contains($name, '時間休日')
            || ((int) $type->full_day === 0
                && (int) ($type->value ?? 0) > 0
                && (str_contains($name, '有給') || str_contains($name, '年休')));
    }

    private function paidLeaveMinutesForShift(shiftRecord $shift, PaidLeavePolicy $policy, PaidLeaveAccount $account): int
    {
        $type = $shift->shiftType;
        if (! $type) {
            return 0;
        }

        $minutesPerDay = $this->minutesPerLeaveDayForAccount($account, $policy);
        if ((int) $type->id === 3 || (int) $type->full_day === 2) {
            return $minutesPerDay;
        }

        if ((int) $type->full_day === 1) {
            return (int) round($minutesPerDay / 2);
        }

        $minutes = max(0, (int) ($type->value ?? 0));
        if ($this->isHourlyPaidLeaveShift($shift) && (int) $policy->hourly_deduction_unit_minutes > 0) {
            $unit = max(1, (int) $policy->hourly_deduction_unit_minutes);

            return (int) ceil($minutes / $unit) * $unit;
        }

        return $minutes;
    }

    private function assertHourlyLeaveWithinCap(PaidLeaveAccount $account, shiftRecord $shift, int $amount, PaidLeavePolicy $policy): void
    {
        if (! (bool) $policy->hourly_leave_enabled) {
            throw ValidationException::withMessages(['message' => '時間単位有休は現在利用できません。']);
        }

        $limitMinutes = $this->hourlyLeaveLimitMinutes($policy, $account);

        $usedOn = Carbon::parse($shift->shift_day)->startOfDay();
        $window = $this->hourlyLeaveWindowForDate($account, $usedOn, $policy);
        $usedMinutes = $this->hourlyLeaveMinutesUsedInWindow($account, $window['start'], $window['end']);

        if ($usedMinutes + $amount <= $limitMinutes) {
            return;
        }

        $limitHours = round($limitMinutes / 60, 2);
        $usedHours = round($usedMinutes / 60, 2);
        $requestedHours = round($amount / 60, 2);

        throw ValidationException::withMessages([
            'message' => "時間単位有休は{$window['start']->toDateString()}から{$window['end']->toDateString()}の期間で最大{$limitHours}時間までです。現在{$usedHours}時間、今回{$requestedHours}時間です。",
        ]);
    }

    private function hourlyLeaveWindowForDate(PaidLeaveAccount $account, Carbon $usedOn, PaidLeavePolicy $policy): array
    {
        $grant = PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('grant_type', PaidLeaveGrant::TYPE_ANNUAL)
            ->whereDate('granted_at', '<=', $usedOn->toDateString())
            ->orderByDesc('granted_at')
            ->get()
            ->first(function (PaidLeaveGrant $grant) use ($usedOn) {
                $periodStart = $grant->granted_at->copy()->startOfDay();
                $periodEnd = $periodStart->copy()->addYear()->subDay()->endOfDay();

                return $usedOn->greaterThanOrEqualTo($periodStart)
                    && $usedOn->lessThanOrEqualTo($periodEnd);
            });

        if ($grant) {
            $start = $grant->granted_at->copy()->startOfDay();

            return [
                'start' => $start,
                'end' => $start->copy()->addYear()->subDay()->endOfDay(),
            ];
        }

        if ($account->joined_date) {
            $start = Carbon::parse($account->joined_date)
                ->startOfDay()
                ->addMonthsNoOverflow((int) $policy->first_grant_after_months);
            $intervalMonths = max(1, (int) $policy->annual_grant_interval_months);

            while ($start->copy()->addYear()->subDay()->lt($usedOn)) {
                $start->addMonthsNoOverflow($intervalMonths);
            }

            if ($usedOn->greaterThanOrEqualTo($start)) {
                return [
                    'start' => $start,
                    'end' => $start->copy()->addYear()->subDay()->endOfDay(),
                ];
            }
        }

        return [
            'start' => $usedOn->copy()->startOfYear(),
            'end' => $usedOn->copy()->endOfYear(),
        ];
    }

    private function hourlyLeaveMinutesUsedInWindow(PaidLeaveAccount $account, Carbon $start, Carbon $end): int
    {
        return (int) PaidLeaveUsage::query()
            ->where('paid_leave_account_id', $account->id)
            ->whereBetween('used_on', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) {
                $query->where('usage_type', 'hourly_shift')
                    ->orWhereHas('shiftRecord.shiftType', function ($inner) {
                        $inner->where('name', 'like', '%時間休日%')
                            ->orWhere(function ($nested) {
                                $nested->where('full_day', 0)
                                    ->where('value', '>', 0)
                                    ->where(function ($names) {
                                        $names->where('name', 'like', '%有給%')
                                            ->orWhere('name', 'like', '%年休%');
                                    });
                            });
                    });
            })
            ->sum('amount_minutes');
    }

    private function hourlyLeaveLimitMinutes(PaidLeavePolicy $policy, ?PaidLeaveAccount $account = null): int
    {
        $days = (float) $policy->max_hourly_leave_days_per_year;
        if ($days <= 0) {
            $days = self::DEFAULT_HOURLY_LEAVE_CAP_DAYS;
        }

        if ($account) {
            return $this->daysToMinutesForAccount($days, $account, $policy);
        }

        return $this->daysToMinutes($days, $policy);
    }

    private function ruleForServiceMonths(Collection $rules, int $serviceMonths): ?PaidLeaveGrantRule
    {
        return $rules
            ->filter(fn (PaidLeaveGrantRule $rule) => (int) $rule->service_months <= $serviceMonths)
            ->sortByDesc('service_months')
            ->first();
    }

    private function serviceMonths(Carbon $joined, Carbon $grantDate): int
    {
        return (($grantDate->year - $joined->year) * 12) + ($grantDate->month - $joined->month);
    }

    private function daysToMinutesForAccount(float $days, PaidLeaveAccount $account, ?PaidLeavePolicy $policy = null): int
    {
        $policy = $policy ?: $this->activePolicy();

        return $this->daysToMinutes($days, $this->minutesPerLeaveDayForAccount($account, $policy));
    }

    private function minutesToDaysForAccount(int $minutes, PaidLeaveAccount $account, ?PaidLeavePolicy $policy = null): float
    {
        $policy = $policy ?: $this->activePolicy();

        return $this->minutesToDays($minutes, $this->minutesPerLeaveDayForAccount($account, $policy));
    }

    private function minutesPerLeaveDayForAccount(PaidLeaveAccount $account, PaidLeavePolicy $policy): int
    {
        $user = $account->relationLoaded('user') ? $account->user : null;
        if ($user instanceof User) {
            return $this->minutesPerLeaveDayForUser($user, $policy);
        }

        $minutes = $account->user_id
            ? (int) User::query()->whereKey($account->user_id)->value('work_time_day')
            : 0;

        return $minutes > 0 ? $minutes : max(1, (int) $policy->minutes_per_leave_day);
    }

    private function minutesPerLeaveDayForUser(User $user, PaidLeavePolicy $policy): int
    {
        $minutes = (int) ($user->work_time_day ?? 0);

        return $minutes > 0 ? $minutes : max(1, (int) $policy->minutes_per_leave_day);
    }

    private function daysToMinutes(float $days, PaidLeavePolicy|int|null $policyOrMinutes = null): int
    {
        $minutesPerDay = $this->resolveMinutesPerDay($policyOrMinutes);

        return (int) round($days * $minutesPerDay);
    }

    private function minutesToDays(int $minutes, PaidLeavePolicy|int|null $policyOrMinutes = null): float
    {
        $minutesPerDay = $this->resolveMinutesPerDay($policyOrMinutes);

        return round($minutes / $minutesPerDay, 2);
    }

    private function resolveMinutesPerDay(PaidLeavePolicy|int|null $policyOrMinutes = null): int
    {
        if ($policyOrMinutes instanceof PaidLeavePolicy) {
            return max(1, (int) $policyOrMinutes->minutes_per_leave_day);
        }

        if (is_int($policyOrMinutes) && $policyOrMinutes > 0) {
            return $policyOrMinutes;
        }

        $policy = $this->activePolicy();
        $minutesPerDay = max(1, (int) $policy->minutes_per_leave_day);

        return $minutesPerDay;
    }
}
