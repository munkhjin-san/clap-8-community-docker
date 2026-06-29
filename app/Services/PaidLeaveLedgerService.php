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

    private const PART_TIME_PAID_LEAVE_USER_IDS = [545];

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

    public function generateDueGrants(Carbon $runDate, ?Carbon $fromDate = null, ?int $userId = null): array
    {
        $policy = $this->activePolicy();
        $rules = $policy->rules->where('active', true)->sortBy('service_months')->values();
        $fromDate = $fromDate ?: ($policy->effective_from ?: $runDate);
        $summary = [
            'checked' => 0,
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_kintone_synced' => 0,
            'skipped_no_rule' => 0,
            'skipped_no_joined_date' => 0,
            'skipped_attendance' => 0,
        ];

        User::query()
            ->when($userId, fn ($query) => $query->where('id', $userId))
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
                    $kintoneSyncedCutoff = $this->kintonePaidLeaveSyncedCutoff($account);
                    $grantDate = $joined->copy()->addMonthsNoOverflow((int) $policy->first_grant_after_months);
                    
                    while ($grantDate->lessThanOrEqualTo($runDate)) {
                        if ($grantDate->greaterThanOrEqualTo($fromDate)) {
                            if ($kintoneSyncedCutoff && $grantDate->lessThanOrEqualTo($kintoneSyncedCutoff->copy()->startOfDay())) {
                                $summary['skipped_kintone_synced']++;
                            } else {
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

    public function importKintoneAnnualLeaveHistory(
        bool $dryRun = true,
        ?Carbon $asOf = null,
        ?string $userCode = null,
        bool $allowExistingOpeningBalance = false
    ): array {
        $asOf = ($asOf ?: Carbon::today())->copy()->endOfDay();
        $employeeCodeField = '社員ｺｰﾄﾞ';
        $grantYearField = '付与年度';
        $grantDateField = '当年度有休付与日';
        $expiresAtField = '消滅日';
        $krewExpiresAtField = '日付_0';
        $grantDaysField = '付与日数';
        $usedDaysField = '消化日数合計';
        $usedHoursField = '消化時間合計';
        $remainingDaysField = '残日数1';
        $expiredDaysField = '消滅日数';
        $plannedTableField = '計画付与テーブル';
        $joinedDateField = '入社日';
        $retiredDateField = '退職日';

        $fields = [
            '$id',
            $employeeCodeField,
            '氏名',
            $grantYearField,
            $grantDateField,
            $expiresAtField,
            $krewExpiresAtField,
            $grantDaysField,
            $usedDaysField,
            $usedHoursField,
            $remainingDaysField,
            $expiredDaysField,
            $plannedTableField,
            $joinedDateField,
            $retiredDateField,
        ];
        $query = 'order by 付与年度 asc, 当年度有休付与日 asc';
        if ($userCode !== null && trim($userCode) !== '') {
            $query = $employeeCodeField . ' = "' . $this->escapeKintoneQueryString(trim($userCode)) . '" ' . $query;
        }

        $records = $this->kintone->getAllRecords(605, $query, $fields);
        $policy = $this->activePolicy();
        $summary = [
            'dry_run' => $dryRun,
            'as_of' => $asOf->toDateString(),
            'fetched' => count($records),
            'eligible_records' => 0,
            'matched_users' => 0,
            'created_accounts' => 0,
            'created_grants' => 0,
            'created_usages' => 0,
            'created_usage_allocations' => 0,
            'created_expiration_adjustments' => 0,
            'created_balance_adjustments' => 0,
            'expanded_usage_dates' => 0,
            'aggregate_usage_rows' => 0,
            'unexpanded_usage_rows' => 0,
            'target_balance_mismatches' => 0,
            'skipped_blank_code' => 0,
            'skipped_no_user' => 0,
            'skipped_no_grant_date' => 0,
            'skipped_future_grant' => 0,
            'skipped_existing_grant' => 0,
            'skipped_existing_opening_balance' => 0,
        ];
        $dryRunWouldCreateAccounts = [];

        foreach ($records as $record) {
            $recordId = (string) ($this->kintoneRecordValue($record, '$id') ?? '');
            $employeeCode = trim((string) ($this->kintoneRecordValue($record, $employeeCodeField) ?? ''));
            if ($employeeCode === '') {
                $summary['skipped_blank_code']++;
                continue;
            }

            $grantDate = $this->parseKintoneDate($this->kintoneRecordValue($record, $grantDateField));
            if (! $grantDate) {
                $summary['skipped_no_grant_date']++;
                continue;
            }

            if ($grantDate->greaterThan($asOf)) {
                $summary['skipped_future_grant']++;
                continue;
            }

            $summary['eligible_records']++;
            $user = User::query()
                ->where('user_code', $employeeCode)
                ->select('id', 'name', 'user_code', 'joined_date', 'retire', 'position_id', 'work_time_day')
                ->first();
            if (! $user) {
                $summary['skipped_no_user']++;
                continue;
            }

            $summary['matched_users']++;
            $account = PaidLeaveAccount::query()->where('user_id', $user->id)->first();
            if (! $allowExistingOpeningBalance && $account && $this->accountHasKintoneOpeningBalance($account)) {
                $summary['skipped_existing_opening_balance']++;
                continue;
            }

            $grantSourceKey = "kintone:605:{$recordId}:grant";
            if ($account && PaidLeaveGrant::query()
                ->where('paid_leave_account_id', $account->id)
                ->where('source_system', 'kintone')
                ->where('source_key', $grantSourceKey)
                ->exists()) {
                $summary['skipped_existing_grant']++;
                continue;
            }

            $minutesPerDay = $this->minutesPerLeaveDayForUser($user, $policy);
            $grantDays = $this->kintoneNumberValue($record, $grantDaysField);
            $amountMinutes = $this->daysToMinutes($grantDays, $minutesPerDay);
            $usageItems = $this->kintoneHistoryUsageItems($record, $grantDate, $minutesPerDay);
            $expiredMinutes = $this->daysToMinutes($this->kintoneNumberValue($record, $expiredDaysField), $minutesPerDay);
            $targetRemainingMinutes = $this->daysToMinutes($this->kintoneNumberValue($record, $remainingDaysField), $minutesPerDay);
            $calculatedRemainingMinutes = $amountMinutes
                - collect($usageItems)->sum('amount_minutes')
                - $expiredMinutes;
            $balanceAdjustmentMinutes = $targetRemainingMinutes - $calculatedRemainingMinutes;

            if ($dryRun) {
                if (! $account && ! isset($dryRunWouldCreateAccounts[$user->id])) {
                    $summary['created_accounts']++;
                    $dryRunWouldCreateAccounts[$user->id] = true;
                }

                $summary['created_grants']++;
                $summary['created_usages'] += count($usageItems);
                $summary['created_usage_allocations'] += count($usageItems);
                $summary['created_expiration_adjustments'] += $expiredMinutes > 0 ? 1 : 0;
                $summary['created_balance_adjustments'] += $balanceAdjustmentMinutes !== 0 ? 1 : 0;
                $summary['expanded_usage_dates'] += collect($usageItems)->where('expanded', true)->count();
                $summary['aggregate_usage_rows'] += collect($usageItems)->where('aggregate', true)->count();
                $summary['unexpanded_usage_rows'] += collect($usageItems)->where('unexpanded', true)->count();
                $summary['target_balance_mismatches'] += $balanceAdjustmentMinutes !== 0 ? 1 : 0;
                continue;
            }

            DB::transaction(function () use (
                $user,
                $record,
                $recordId,
                $employeeCode,
                $grantSourceKey,
                $policy,
                $grantDate,
                $grantYearField,
                $grantDateField,
                $expiresAtField,
                $krewExpiresAtField,
                $grantDaysField,
                $usedDaysField,
                $usedHoursField,
                $remainingDaysField,
                $expiredDaysField,
                $joinedDateField,
                $retiredDateField,
                $grantDays,
                $amountMinutes,
                $minutesPerDay,
                $usageItems,
                $expiredMinutes,
                $targetRemainingMinutes,
                $asOf,
                &$summary
            ) {
                $account = $this->ensureAccount($user);

                if (PaidLeaveGrant::query()
                    ->where('paid_leave_account_id', $account->id)
                    ->where('source_system', 'kintone')
                    ->where('source_key', $grantSourceKey)
                    ->exists()) {
                    $summary['skipped_existing_grant']++;
                    return;
                }

                $joinedDate = $user->joined_date ? Carbon::parse($user->joined_date)->startOfDay() : null;
                $serviceMonths = $joinedDate && $grantDate->greaterThanOrEqualTo($joinedDate)
                    ? $this->serviceMonths($joinedDate, $grantDate)
                    : null;
                $expiresAt = $this->kintoneHistoryExpiryDate($record, $grantDate, $policy);
                $grant = PaidLeaveGrant::create([
                    'paid_leave_account_id' => $account->id,
                    'paid_leave_policy_id' => null,
                    'grant_type' => PaidLeaveGrant::TYPE_ANNUAL,
                    'granted_at' => $grantDate->toDateString(),
                    'expires_at' => $expiresAt?->toDateString(),
                    'service_months' => $serviceMonths,
                    'grant_days' => $grantDays,
                    'amount_minutes' => $amountMinutes,
                    'remaining_minutes' => $amountMinutes,
                    'planned_required_minutes' => $this->plannedRequiredMinutesForGrantDays($grantDays, $minutesPerDay),
                    'policy_snapshot' => [
                        'source_app_id' => 605,
                        'source_record_id' => $recordId,
                        'kintone_grant_year' => $this->kintoneRecordValue($record, $grantYearField),
                        'kintone_grant_date' => $this->kintoneRecordValue($record, $grantDateField),
                        'kintone_expires_at' => $this->kintoneRecordValue($record, $expiresAtField)
                            ?: $this->kintoneRecordValue($record, $krewExpiresAtField),
                        'kintone_grant_days' => $this->kintoneRecordValue($record, $grantDaysField),
                        'kintone_used_days' => $this->kintoneRecordValue($record, $usedDaysField),
                        'kintone_used_hours' => $this->kintoneRecordValue($record, $usedHoursField),
                        'kintone_remaining_days' => $this->kintoneRecordValue($record, $remainingDaysField),
                        'kintone_expired_days' => $this->kintoneRecordValue($record, $expiredDaysField),
                        'minutes_per_leave_day' => $minutesPerDay,
                        'imported_as_of' => $asOf->toDateString(),
                    ],
                    'source_system' => 'kintone',
                    'source_key' => $grantSourceKey,
                    'source_app_id' => 605,
                    'source_record_id' => $recordId ?: null,
                    'note' => 'Kintone app 605 annual paid-leave lot imported.',
                ]);

                $summary['created_grants']++;
                foreach ($usageItems as $usageItem) {
                    if ($this->createKintoneHistoryUsage($account, $grant, $usageItem)) {
                        $summary['created_usages']++;
                        $summary['created_usage_allocations']++;
                        $summary['expanded_usage_dates'] += $usageItem['expanded'] ? 1 : 0;
                        $summary['aggregate_usage_rows'] += $usageItem['aggregate'] ? 1 : 0;
                        $summary['unexpanded_usage_rows'] += $usageItem['unexpanded'] ? 1 : 0;
                    }
                }

                if ($expiredMinutes > 0) {
                    $adjustedOn = $expiresAt ?: $grantDate;
                    if ($this->createKintoneHistoryGrantAdjustment(
                        $account,
                        $grant,
                        "kintone:605:{$recordId}:expiration",
                        $adjustedOn,
                        -$expiredMinutes,
                        'expiration',
                        'Kintone app 605 expired days imported.',
                        $recordId
                    )) {
                        $summary['created_expiration_adjustments']++;
                    }
                }

                $grant->refresh();
                $balanceAdjustmentMinutes = $targetRemainingMinutes - (int) $grant->remaining_minutes;
                if ($balanceAdjustmentMinutes !== 0) {
                    if ($this->createKintoneHistoryGrantAdjustment(
                        $account,
                        $grant,
                        "kintone:605:{$recordId}:balance-reconcile",
                        $asOf,
                        $balanceAdjustmentMinutes,
                        'kintone_balance_reconcile',
                        'Adjusted to Kintone app 605 remaining balance for this grant lot.',
                        $recordId
                    )) {
                        $summary['created_balance_adjustments']++;
                    }
                    $summary['target_balance_mismatches']++;
                }

                $account->fill([
                    'user_code' => $employeeCode,
                    'joined_date' => $user->joined_date ?: $this->kintoneRecordValue($record, $joinedDateField),
                    'last_synced_at' => now(),
                    'source_system' => 'kintone',
                    'source_app_id' => 605,
                    'source_record_id' => null,
                    'source_payload' => [
                        'source' => 'kintone_app_605_history',
                        'last_record_id' => $recordId,
                        'as_of' => $asOf->toDateString(),
                        'retired_date' => $this->kintoneRecordValue($record, $retiredDateField),
                    ],
                ])->save();

                if ($account->wasRecentlyCreated) {
                    $summary['created_accounts']++;
                }
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
            ->where('retire', 0)
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

        return DB::transaction(function () use ($account, $policy, $start, $end, $createdByUserId, $summary) {
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

                // Only skip when Kintone history has the same usage. Import timing alone is not proof.
                if ($this->hasImportedKintoneUsageForShift($account, $shift, $amount)) {
                    $summary['skipped_imported_kintone_usage']++;
                    if ($existing) {
                        $this->deleteUsageAndRestoreGrants($existing);
                        $summary['removed_imported_kintone_overlap_usages']++;
                    }

                    continue;
                }

                if ($existing && (int) $existing->amount_minutes === $amount && (string) $existing->usage_type === $usageType) {
                    $summary['skipped_existing']++;
                    continue;
                }

                if ($this->shouldDeferPlannedLeaveUntilFutureGrant($account, $shift, $policy, $amount)) {
                    $summary['skipped_pending_future_grant']++;
                    if ($existing) {
                        $this->deleteUsageAndRestoreGrants($existing);
                    }

                    continue;
                }

                if ($this->shouldSkipPlannedLeaveWithoutMatchingGrantYear($account, $shift, $amount)) {
                    $summary['skipped_missing_planned_year_grant']++;
                    if ($existing) {
                        $this->deleteUsageAndRestoreGrants($existing);
                    }

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

    public function plannedLeaveYearForShiftDate(int $userId, string $shiftDay, ?int $preferredYear = null): ?int
    {
        $day = Carbon::parse($shiftDay)->startOfDay();
        $candidateYears = collect([$preferredYear, (int) $day->year - 1, (int) $day->year, (int) $day->year + 1])
            ->filter(fn ($year) => $year !== null && (int) $year > 0)
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->values();

        foreach ($candidateYears as $candidateYear) {
            $periods = $this->plannedLeavePeriodsForUser($userId, $candidateYear);
            $matchingPeriod = $periods->first(function (array $period) use ($day) {
                $start = Carbon::parse($period['shift_window_start'] ?? $period['period_start'])->startOfDay();
                $end = Carbon::parse($period['shift_window_end'] ?? $period['period_end'])->endOfDay();

                return $day->greaterThanOrEqualTo($start) && $day->lessThanOrEqualTo($end);
            });

            if ($matchingPeriod) {
                return (int) $matchingPeriod['planned_year'];
            }
        }

        return $preferredYear && $preferredYear > 0 ? (int) $preferredYear : null;
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
                'planned_required_days' => $this->plannedRequiredDaysForGrant($grant, $minutesPerDay),
                'source_system' => $grant->source_system,
                'note' => $grant->note,
            ])->values(),
            'events' => $events,
        ];
    }

    public function createManualAdjustment(
        PaidLeaveAccount $account,
        float $amountDays,
        string $adjustedOn,
        ?string $note,
        ?int $createdByUserId = null,
        ?int $grantId = null,
        string $adjustmentType = 'manual',
    ): array
    {
        $policy = $this->activePolicy();
        $amountMinutes = $this->daysToMinutesForAccount($amountDays, $account, $policy);

        DB::transaction(function () use ($account, $adjustedOn, $amountMinutes, $adjustmentType, $note, $createdByUserId, $grantId) {
            $grant = null;
            if ($grantId) {
                $grant = PaidLeaveGrant::query()
                    ->where('paid_leave_account_id', $account->id)
                    ->whereKey($grantId)
                    ->lockForUpdate()
                    ->first();

                if (! $grant) {
                    throw ValidationException::withMessages(['paid_leave_grant_id' => '指定された付与年度が見つかりません。']);
                }

                $nextRemainingMinutes = (int) $grant->remaining_minutes + $amountMinutes;
                if ($nextRemainingMinutes < 0) {
                    throw ValidationException::withMessages(['amount_days' => '指定された付与年度の残数が不足しています。']);
                }

                $grant->update(['remaining_minutes' => $nextRemainingMinutes]);
            }

            $account->adjustments()->create([
                'paid_leave_grant_id' => $grant?->id,
                'adjusted_on' => Carbon::parse($adjustedOn)->toDateString(),
                'amount_minutes' => $amountMinutes,
                'adjustment_type' => $adjustmentType,
                'source_system' => 'glowd',
                'source_key' => 'manual:' . uniqid('', true),
                'note' => $note,
                'created_by_user_id' => $createdByUserId,
            ]);
        });

        return $this->adminLedgerHistory($account->fresh());
    }

    private function plannedShiftsForPeriod(int $userId, Carbon $periodStart, Carbon $periodEnd, int $plannedYear): Collection
    {
        return shiftRecord::query()
            ->where('user_id', $userId)
            ->where('shift_type', 3)
            ->where('planned_year', $plannedYear)
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('shift_records as newer_shift_records')
                    ->whereColumn('newer_shift_records.descendant_of', 'shift_records.id')
                    ->whereNull('newer_shift_records.deleted_at');
            })
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
        if ($grant->grant_type === PaidLeaveGrant::TYPE_ANNUAL && (float) $grant->grant_days > 0) {
            $minutesPerDay = (int) round(((int) $grant->amount_minutes) / max(1, (float) $grant->grant_days));

            return $this->daysToMinutes(
                $this->plannedRequiredDaysForGrantDays((float) $grant->grant_days),
                max(1, $minutesPerDay)
            );
        }

        $storedMinutes = (int) ($grant->planned_required_minutes ?? 0);
        if ($storedMinutes > 0) {
            return $storedMinutes;
        }

        if ($grant->grant_type === PaidLeaveGrant::TYPE_ANNUAL && (int) $grant->amount_minutes > 0) {
            return (int) floor(((int) $grant->amount_minutes) / 2);
        }

        return 0;
    }

    private function plannedRequiredMinutesForGrantDays(float $grantDays, int $minutesPerDay): int
    {
        $requiredDays = $this->plannedRequiredDaysForGrantDays($grantDays);

        return $requiredDays > 0 ? $this->daysToMinutes($requiredDays, $minutesPerDay) : 0;
    }

    private function plannedRequiredDaysForGrant(PaidLeaveGrant $grant, int $minutesPerDay): float
    {
        if ($grant->grant_type === PaidLeaveGrant::TYPE_ANNUAL && (float) $grant->grant_days > 0) {
            return $this->plannedRequiredDaysForGrantDays((float) $grant->grant_days);
        }

        $storedMinutes = (int) ($grant->planned_required_minutes ?? 0);
        if ($storedMinutes > 0) {
            return $this->minutesToDays($storedMinutes, $minutesPerDay);
        }

        if ((int) $grant->amount_minutes > 0) {
            return $this->minutesToDays((int) floor(((int) $grant->amount_minutes) / 2), $minutesPerDay);
        }

        return 0;
    }

    private function plannedRequiredDaysForGrantDays(float $grantDays): int
    {
        return match (true) {
            $grantDays <= 0 => 0,
            $grantDays <= 12 => 5,
            $grantDays <= 16 => 7,
            default => 10,
        };
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
        $requestedYear = $year ? (int) $year : null;
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
        $kintonePlanningCarryoverWindows = $this->kintonePlanningCarryoverWindows($account, $policy);

        foreach ($account->grants as $grant) {
            if ($grant->grant_type !== PaidLeaveGrant::TYPE_ANNUAL || ! $grant->granted_at) {
                continue;
            }

            $minutesPerDay = $this->minutesPerLeaveDayForUser($user, $policy);
            $requiredMinutes = $this->daysToMinutes(
                $this->plannedRequiredDaysForGrant($grant, $minutesPerDay),
                $minutesPerDay
            );
            if ($requiredMinutes <= 0) {
                continue;
            }

            $periodStart = $grant->granted_at->copy()->startOfDay();
            $periodEnd = $periodStart->copy()->addYear()->subDay();
            if ($requestedYear !== null && (int) $periodStart->year !== $requestedYear) {
                continue;
            }

            if ($requestedYear === null && ! $targetYears->contains(fn (int $targetYear) => $this->periodOverlapsYear($periodStart, $periodEnd, $targetYear))) {
                continue;
            }

            if (! $this->shouldUseActualGrantForPlannedLeave($grant, $account, $policy)) {
                continue;
            }

            $carryoverWindow = $kintonePlanningCarryoverWindows[(int) $periodStart->year] ?? null;
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
                shiftWindowStart: $carryoverWindow['start'] ?? null,
                shiftWindowEnd: $carryoverWindow['end'] ?? null,
            ));
        }

        if ($includeExpected) {
            $expectedYears = $year
                ? collect([$year])
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
                $requiredMinutes = $this->plannedRequiredMinutesForGrantDays((float) $grantDays, $minutesPerDay);
                if ($requiredMinutes <= 0) {
                    continue;
                }

                $periodStart = $expectedGrantDate->copy()->startOfDay();
                $periodEnd = $periodStart->copy()->addYear()->subDay();
                if ($requestedYear !== null && (int) $periodStart->year !== $requestedYear) {
                    continue;
                }

                if ($requestedYear === null && ! $targetYears->contains(fn (int $targetYear) => $this->periodOverlapsYear($periodStart, $periodEnd, $targetYear))) {
                    continue;
                }

                $carryoverWindow = $kintonePlanningCarryoverWindows[(int) $periodStart->year] ?? null;
                $periods->push($this->plannedLeavePeriodPayload(
                    user: $user,
                    policy: $policy,
                    periodStart: $periodStart,
                    requiredMinutes: $requiredMinutes,
                    grantDays: (float) $grantDays,
                    source: 'expected',
                    asOf: $asOf,
                    shiftWindowStart: $carryoverWindow['start'] ?? null,
                    shiftWindowEnd: $carryoverWindow['end'] ?? null,
                ));
            }
        }

        foreach ($targetYears as $targetYear) {
            $hasPeriod = $requestedYear !== null
                ? $periods->contains(fn (array $period) => (int) $period['planned_year'] === (int) $targetYear)
                : $periods->contains(fn (array $period) => $this->periodOverlapsYear(
                    Carbon::parse($period['period_start'])->startOfDay(),
                    Carbon::parse($period['period_end'])->endOfDay(),
                    (int) $targetYear
                ));

            if ($hasPeriod) {
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

    private function kintonePlanningCarryoverWindows(PaidLeaveAccount $account, PaidLeavePolicy $policy): array
    {
        $windows = [];

        foreach ($account->grants as $grant) {
            if ($grant->grant_type !== PaidLeaveGrant::TYPE_ANNUAL || ! $grant->granted_at) {
                continue;
            }

            if ($this->shouldUseActualGrantForPlannedLeave($grant, $account, $policy)) {
                continue;
            }

            $expectedGrantDate = $this->expectedGrantDateForYear($account, $policy, (int) $grant->granted_at->format('Y'));
            if (! $expectedGrantDate) {
                continue;
            }

            $actualStart = $grant->granted_at->copy()->startOfDay();
            $actualEnd = $actualStart->copy()->addYear()->subDay()->endOfDay();
            $expectedStart = $expectedGrantDate->copy()->startOfDay();
            $expectedEnd = $expectedStart->copy()->addYear()->subDay()->endOfDay();
            $this->mergePlanningCarryoverWindow(
                $windows,
                (int) $expectedStart->year,
                $actualStart->lessThan($expectedStart) ? $actualStart : $expectedStart,
                $actualEnd->greaterThan($expectedEnd) ? $actualEnd : $expectedEnd,
            );
        }

        return $windows;
    }

    private function mergePlanningCarryoverWindow(array &$windows, int $year, Carbon $start, Carbon $end): void
    {
        if (! isset($windows[$year])) {
            $windows[$year] = ['start' => $start->copy(), 'end' => $end->copy()];

            return;
        }

        if ($start->lessThan($windows[$year]['start'])) {
            $windows[$year]['start'] = $start->copy();
        }

        if ($end->greaterThan($windows[$year]['end'])) {
            $windows[$year]['end'] = $end->copy();
        }
    }

    private function shouldUseActualGrantForPlannedLeave(PaidLeaveGrant $grant, PaidLeaveAccount $account, PaidLeavePolicy $policy): bool
    {
        if ((string) $grant->source_system !== 'kintone' || (int) $grant->source_app_id !== 605 || ! $grant->granted_at) {
            return true;
        }

        $expectedGrantDate = $this->expectedGrantDateForYear($account, $policy, (int) $grant->granted_at->format('Y'));
        if (! $expectedGrantDate) {
            return true;
        }

        return $grant->granted_at->isSameDay($expectedGrantDate);
    }

    private function expectedGrantDaysForPlanning(User $user, PaidLeaveAccount $account, PaidLeavePolicy $policy, Carbon $grantDate): ?float
    {
        if (! $account->joined_date) {
            return null;
        }

        if ($this->isPartTimePaidLeaveUser($user)) {
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
        ?Carbon $shiftWindowStart = null,
        ?Carbon $shiftWindowEnd = null,
    ): array {
        $periodStart = $periodStart->copy()->startOfDay();
        $periodEnd = $periodStart->copy()->addYear()->subDay();
        $shiftWindowStart = ($shiftWindowStart ?: $periodStart)->copy()->startOfDay();
        $shiftWindowEnd = ($shiftWindowEnd ?: $periodEnd)->copy()->endOfDay();
        $minutesPerDay = $this->minutesPerLeaveDayForUser($user, $policy);
        $plannedYear = (int) $periodStart->year;
        $plannedShifts = $this->plannedShiftsForPeriod((int) $user->id, $shiftWindowStart, $shiftWindowEnd, $plannedYear);
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
            'shift_window_start' => $shiftWindowStart->toDateString(),
            'shift_window_end' => $shiftWindowEnd->toDateString(),
            'planning_allowed_from' => $planningAllowedFrom->toDateString(),
            'planning_allowed' => $asOf->greaterThanOrEqualTo($planningAllowedFrom),
            'planned_year' => $plannedYear,
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
        if ($this->isPartTimePaidLeaveUser($user)) {
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

    private function isPartTimePaidLeaveUser(User $user): bool
    {
        return (int) ($user->position_id ?? 0) === self::PART_TIME_POSITION_ID
            || in_array((int) $user->id, self::PART_TIME_PAID_LEAVE_USER_IDS, true);
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
            'skipped_imported_kintone_usage' => 0,
            'skipped_pending_future_grant' => 0,
            'skipped_missing_planned_year_grant' => 0,
            'removed_imported_kintone_overlap_usages' => 0,
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

    private function kintoneHistoryUsageItems(array $record, Carbon $grantDate, int $minutesPerDay): array
    {
        $recordId = (string) ($this->kintoneRecordValue($record, '$id') ?? '');
        $items = [];
        $rows = (array) ($this->kintoneRecordValue($record, '計画付与テーブル') ?? []);

        foreach ($rows as $index => $row) {
            $cells = (array) ($row['value'] ?? []);
            $rowId = (string) ($row['id'] ?? $index);
            $kind = (string) ($this->kintoneTableCellValue($cells, '区分') ?? '');
            $usedDays = $this->kintoneTableCellNumberValue($cells, '消化日数');
            $usedHours = $this->kintoneTableCellNumberValue($cells, '消化時間');
            if ($usedDays <= 0 && $usedHours <= 0) {
                continue;
            }

            $baseDate = $this->parseKintoneDate(
                $this->kintoneTableCellValue($cells, '計画付与日')
                    ?: $this->kintoneTableCellValue($cells, '日付')
            ) ?: $grantDate->copy();
            $reason = (string) ($this->kintoneTableCellValue($cells, '変更事由') ?? '');
            $expectedDays = (int) round($usedDays);
            $canExpand = abs($usedDays - $expectedDays) < 0.0001 && $expectedDays > 1 && $usedHours <= 0;
            $expandedDates = $canExpand
                ? $this->expandKintoneUsageDates($baseDate, $reason, $expectedDays)
                : [];

            if ($canExpand && count($expandedDates) === $expectedDays) {
                foreach ($expandedDates as $part => $date) {
                    $items[] = [
                        'source_key' => "kintone:605:{$recordId}:usage:{$rowId}:{$date}:{$part}",
                        'used_on' => $date,
                        'amount_minutes' => $minutesPerDay,
                        'usage_type' => $kind === '計画消化' ? 'planned_shift' : 'shift',
                        'note' => $this->kintoneUsageNote($kind, $usedDays, $usedHours, $reason, $rowId, true),
                        'expanded' => true,
                        'aggregate' => false,
                        'unexpanded' => false,
                    ];
                }

                continue;
            }

            $amountMinutes = $this->daysToMinutes($usedDays, $minutesPerDay) + (int) round($usedHours * 60);
            $items[] = [
                'source_key' => "kintone:605:{$recordId}:usage:{$rowId}:aggregate",
                'used_on' => $baseDate->toDateString(),
                'amount_minutes' => $amountMinutes,
                'usage_type' => $usedHours > 0 && $usedDays <= 0
                    ? 'hourly_shift'
                    : ($kind === '計画消化' ? 'planned_shift' : 'shift'),
                'note' => $this->kintoneUsageNote($kind, $usedDays, $usedHours, $reason, $rowId, false),
                'expanded' => false,
                'aggregate' => true,
                'unexpanded' => $canExpand,
            ];
        }

        return $items;
    }

    private function expandKintoneUsageDates(Carbon $baseDate, string $reason, int $expectedDays): array
    {
        $dates = [$baseDate->toDateString() => true];
        $text = trim($reason);
        if ($text === '') {
            return array_keys($dates);
        }

        if (function_exists('mb_convert_kana')) {
            $text = mb_convert_kana($text, 'n', 'UTF-8');
        }

        $currentMonth = (int) $baseDate->month;
        if (preg_match_all('/(?:(\d{1,2})\s*[\/月]\s*)?(\d{1,2})(?:\s*日)?(?!\s*[\/月])/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $monthText = $match[1] ?? '';
                $day = (int) ($match[2] ?? 0);
                if ($monthText !== '') {
                    $currentMonth = (int) $monthText;
                }

                $month = $currentMonth;
                if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                    continue;
                }

                $year = (int) $baseDate->year;
                if ((int) $baseDate->month >= 10 && $month <= 3) {
                    $year++;
                } elseif ((int) $baseDate->month <= 3 && $month >= 10) {
                    $year--;
                }

                if (! checkdate($month, $day, $year)) {
                    continue;
                }

                $dates[Carbon::create($year, $month, $day)->toDateString()] = true;
            }
        }

        $dateList = array_keys($dates);
        sort($dateList);

        return count($dateList) === $expectedDays ? $dateList : [$baseDate->toDateString()];
    }

    private function createKintoneHistoryUsage(PaidLeaveAccount $account, PaidLeaveGrant $grant, array $usageItem): bool
    {
        $usage = PaidLeaveUsage::firstOrCreate(
            ['source_system' => 'kintone', 'source_key' => $usageItem['source_key']],
            [
                'paid_leave_account_id' => $account->id,
                'used_on' => $usageItem['used_on'],
                'amount_minutes' => $usageItem['amount_minutes'],
                'usage_type' => $usageItem['usage_type'],
                'status' => 'confirmed',
                'note' => $usageItem['note'],
            ],
        );

        if (! $usage->wasRecentlyCreated) {
            return false;
        }

        $usage->allocations()->create([
            'paid_leave_grant_id' => $grant->id,
            'amount_minutes' => $usageItem['amount_minutes'],
        ]);
        $this->applyGrantMinuteDelta($grant, -((int) $usageItem['amount_minutes']));

        return true;
    }

    private function createKintoneHistoryGrantAdjustment(
        PaidLeaveAccount $account,
        PaidLeaveGrant $grant,
        string $sourceKey,
        Carbon $adjustedOn,
        int $amountMinutes,
        string $type,
        string $note,
        string $recordId
    ): bool {
        $adjustment = $account->adjustments()->firstOrCreate(
            ['source_system' => 'kintone', 'source_key' => $sourceKey],
            [
                'paid_leave_grant_id' => $grant->id,
                'adjusted_on' => $adjustedOn->toDateString(),
                'amount_minutes' => $amountMinutes,
                'adjustment_type' => $type,
                'source_app_id' => 605,
                'source_record_id' => $recordId ?: null,
                'note' => $note,
            ],
        );

        if (! $adjustment->wasRecentlyCreated) {
            return false;
        }

        $this->applyGrantMinuteDelta($grant, $amountMinutes);

        return true;
    }

    private function kintoneHistoryExpiryDate(array $record, Carbon $grantDate, PaidLeavePolicy $policy): Carbon
    {
        $expiresAt = $this->parseKintoneDate(
            $this->kintoneRecordValue($record, '消滅日')
                ?: $this->kintoneRecordValue($record, '日付_0')
        );

        if ($expiresAt) {
            return $expiresAt;
        }

        $months = (int) ($policy->expires_after_months ?: 24);

        return $grantDate->copy()->addMonthsNoOverflow($months);
    }

    private function kintoneUsageNote(string $kind, float $usedDays, float $usedHours, string $reason, string $rowId, bool $expanded): string
    {
        $parts = [
            'Kintone app 605 usage imported.',
            "row={$rowId}",
            "kind={$kind}",
            "days={$usedDays}",
            "hours={$usedHours}",
        ];
        if ($reason !== '') {
            $parts[] = "reason={$reason}";
        }
        if ($expanded) {
            $parts[] = 'multi-day row expanded from reason dates';
        }

        return implode('; ', $parts);
    }

    private function applyGrantMinuteDelta(PaidLeaveGrant $grant, int $delta): void
    {
        $grant->remaining_minutes = (int) $grant->remaining_minutes + $delta;
        $grant->save();
    }

    private function kintoneRecordValue(array $record, string $field, $default = null)
    {
        return $record[$field]['value'] ?? $default;
    }

    private function kintoneNumberValue(array $record, string $field): float
    {
        $value = $this->kintoneRecordValue($record, $field);

        return $value === null || $value === '' ? 0.0 : (float) $value;
    }

    private function kintoneTableCellValue(array $cells, string $field, $default = null)
    {
        return $cells[$field]['value'] ?? $default;
    }

    private function kintoneTableCellNumberValue(array $cells, string $field): float
    {
        $value = $this->kintoneTableCellValue($cells, $field);

        return $value === null || $value === '' ? 0.0 : (float) $value;
    }

    private function parseKintoneDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function escapeKintoneQueryString(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function accountHasKintoneOpeningBalance(PaidLeaveAccount $account): bool
    {
        return PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('grant_type', PaidLeaveGrant::TYPE_OPENING_BALANCE)
            ->where('source_system', 'kintone')
            ->where('source_key', 'like', 'kintone:794:%:opening-balance')
            ->exists();
    }

    private function accountHasAuthoritativeBalance(PaidLeaveAccount $account): bool
    {
        return $account->grants()->exists()
            || $account->usages()->exists()
            || $account->adjustments()->exists();
    }

    private function hasImportedKintoneUsageForShift(PaidLeaveAccount $account, shiftRecord $shift, int $amount): bool
    {
        if ($amount <= 0 || ! $shift->shift_day) {
            return false;
        }

        return PaidLeaveUsage::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('source_system', 'kintone')
            ->where('source_key', 'like', 'kintone:605:%:usage:%')
            ->whereDate('used_on', Carbon::parse($shift->shift_day)->toDateString())
            ->where('amount_minutes', $amount)
            ->exists();
    }

    private function kintonePaidLeaveSyncedCutoff(PaidLeaveAccount $account): ?Carbon
    {
        $cutoffs = collect([
            $this->kintoneOpeningBalanceCutoff($account),
            $this->kintoneHistoryImportCutoff($account),
        ])->filter();

        if ($cutoffs->isEmpty()) {
            return null;
        }

        return $cutoffs
            ->sortBy(fn (Carbon $cutoff) => $cutoff->getTimestamp())
            ->last();
    }

    private function kintoneOpeningBalanceCutoff(PaidLeaveAccount $account): ?Carbon
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

    private function kintoneHistoryImportCutoff(PaidLeaveAccount $account): ?Carbon
    {
        $payload = is_array($account->source_payload) ? $account->source_payload : [];
        $payloadAsOf = ($payload['source'] ?? null) === 'kintone_app_605_history'
            ? ($payload['as_of'] ?? null)
            : null;
        $cutoff = $this->parseKintoneDate($payloadAsOf);
        if ($cutoff) {
            return $cutoff->endOfDay();
        }

        $historyGrant = PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('source_system', 'kintone')
            ->where('source_app_id', 605)
            ->where('source_key', 'like', 'kintone:605:%:grant')
            ->orderBy('created_at')
            ->first(['created_at', 'policy_snapshot']);

        if (! $historyGrant) {
            return null;
        }

        $snapshot = is_array($historyGrant->policy_snapshot) ? $historyGrant->policy_snapshot : [];
        $snapshotAsOf = $this->parseKintoneDate($snapshot['imported_as_of'] ?? null);
        if ($snapshotAsOf) {
            return $snapshotAsOf->endOfDay();
        }

        return $historyGrant->created_at ? Carbon::parse($historyGrant->created_at) : null;
    }

    private function adjustmentLabel(string $type): string
    {
        return match ($type) {
            'expiration' => '失効',
            'negative_usage' => 'マイナス使用',
            'manual' => '手動調整',
            'manual_deduction' => '手動控除',
            'manual_restore' => '手動戻し',
            'kintone_balance_reconcile' => 'Kintone残高調整',
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
            'planned_required_minutes' => $this->plannedRequiredMinutesForGrantDays($grantDays, $minutesPerDay),
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

    private function shouldDeferPlannedLeaveUntilFutureGrant(PaidLeaveAccount $account, shiftRecord $shift, PaidLeavePolicy $policy, int $amount): bool
    {
        if ((int) $shift->shift_type !== 3 || $amount <= 0 || ! $shift->shift_day) {
            return false;
        }

        $usedOn = Carbon::parse($shift->shift_day)->startOfDay();
        $today = Carbon::today()->startOfDay();
        $plannedYear = $this->plannedUsageYear($shift);
        if ($plannedYear && $this->plannedYearGrantIsFuture($account, $policy, $plannedYear, $today)) {
            return true;
        }

        if ($usedOn->lessThanOrEqualTo($today)) {
            return false;
        }

        if ($plannedYear && $this->availableGrantMinutesForPlannedYear($account, $plannedYear, $usedOn) >= $amount) {
            return false;
        }

        if ($this->availableGrantMinutesForDate($account, $usedOn) >= $amount) {
            return false;
        }

        $nextGrantDate = $this->nextExpectedGrantDateAfter($account, $policy, $today);

        return $nextGrantDate && $nextGrantDate->lessThanOrEqualTo($usedOn);
    }

    private function shouldSkipPlannedLeaveWithoutMatchingGrantYear(PaidLeaveAccount $account, shiftRecord $shift, int $amount): bool
    {
        $plannedYear = $this->plannedUsageYear($shift);
        if (! $plannedYear || $amount <= 0 || ! $shift->shift_day) {
            return false;
        }

        $usedOn = Carbon::parse($shift->shift_day)->startOfDay();

        return ! $this->hasGrantForPlannedYear($account, $plannedYear, $usedOn);
    }

    private function availableGrantMinutesForDate(PaidLeaveAccount $account, Carbon $usedOn): int
    {
        return (int) PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('remaining_minutes', '>', 0)
            ->whereDate('granted_at', '<=', $usedOn->toDateString())
            ->where(function ($query) use ($usedOn) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $usedOn->toDateString());
            })
            ->sum('remaining_minutes');
    }

    private function availableGrantMinutesForPlannedYear(PaidLeaveAccount $account, int $plannedYear, Carbon $usedOn): int
    {
        $today = Carbon::today()->toDateString();

        return (int) PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('grant_type', PaidLeaveGrant::TYPE_ANNUAL)
            ->where('remaining_minutes', '>', 0)
            ->whereYear('granted_at', $plannedYear)
            ->whereDate('granted_at', '<=', $today)
            ->where(function ($query) use ($usedOn) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $usedOn->toDateString());
            })
            ->sum('remaining_minutes');
    }

    private function hasGrantForPlannedYear(PaidLeaveAccount $account, int $plannedYear, Carbon $usedOn): bool
    {
        $today = Carbon::today()->toDateString();

        return PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('grant_type', PaidLeaveGrant::TYPE_ANNUAL)
            ->whereYear('granted_at', $plannedYear)
            ->whereDate('granted_at', '<=', $today)
            ->where(function ($query) use ($usedOn) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $usedOn->toDateString());
            })
            ->exists();
    }

    private function plannedYearGrantIsFuture(PaidLeaveAccount $account, PaidLeavePolicy $policy, int $plannedYear, Carbon $today): bool
    {
        $actualGrantDate = PaidLeaveGrant::query()
            ->where('paid_leave_account_id', $account->id)
            ->where('grant_type', PaidLeaveGrant::TYPE_ANNUAL)
            ->whereYear('granted_at', $plannedYear)
            ->orderBy('granted_at')
            ->value('granted_at');

        if ($actualGrantDate) {
            return Carbon::parse($actualGrantDate)->startOfDay()->greaterThan($today);
        }

        $expectedGrantDate = $this->expectedGrantDateForYear($account, $policy, $plannedYear);

        return $expectedGrantDate && $expectedGrantDate->greaterThan($today);
    }

    private function nextExpectedGrantDateAfter(PaidLeaveAccount $account, PaidLeavePolicy $policy, Carbon $after): ?Carbon
    {
        if (! $account->joined_date) {
            return null;
        }

        $grantDate = Carbon::parse($account->joined_date)
            ->startOfDay()
            ->addMonthsNoOverflow((int) $policy->first_grant_after_months);
        $intervalMonths = max(1, (int) $policy->annual_grant_interval_months);
        $effectiveFrom = $policy->effective_from
            ? Carbon::parse($policy->effective_from)->startOfDay()
            : null;

        while (
            $grantDate->lessThanOrEqualTo($after)
            || ($effectiveFrom && $grantDate->lessThan($effectiveFrom))
        ) {
            $grantDate->addMonthsNoOverflow($intervalMonths);
        }

        return $grantDate;
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

        $grants = $this->grantsForUsageAllocation($account, $usage, $shift);

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

    private function grantsForUsageAllocation(PaidLeaveAccount $account, PaidLeaveUsage $usage, shiftRecord $shift): Collection
    {
        $plannedYear = $this->plannedUsageYear($shift);
        if ($plannedYear) {
            $plannedYearGrants = PaidLeaveGrant::query()
                ->where('paid_leave_account_id', $account->id)
                ->where('grant_type', PaidLeaveGrant::TYPE_ANNUAL)
                ->where('remaining_minutes', '>', 0)
                ->whereYear('granted_at', $plannedYear)
                ->whereDate('granted_at', '<=', Carbon::today()->toDateString())
                ->where(function ($query) use ($usage) {
                    $query->whereNull('expires_at')
                        ->orWhereDate('expires_at', '>=', $usage->used_on);
                })
                ->orderByRaw('expires_at is null')
                ->orderBy('expires_at')
                ->orderBy('granted_at')
                ->lockForUpdate()
                ->get();

            return $plannedYearGrants;
        }

        return PaidLeaveGrant::query()
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
    }

    private function plannedUsageYear(shiftRecord $shift): ?int
    {
        if ((int) $shift->shift_type !== 3 || ! $shift->planned_year) {
            return null;
        }

        $plannedYear = (int) $shift->planned_year;

        return $plannedYear > 0 ? $plannedYear : null;
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
