<?php

namespace App\Services;

use App\Infrastructure\Kintone\KintoneClient;
use App\Models\RefreshAccount;
use App\Models\RefreshAnnualReview;
use App\Models\RefreshExpiration;
use App\Models\RefreshGrant;
use App\Models\RefreshUsage;
use App\Models\RefreshUsageAllocation;
use App\Models\PostRecord;
use App\Models\User;
use App\Models\UserLeaveRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefreshService
{
    private const KINTONE_APP_ID = 782;

    /**
     * @var int[]
     */
    private const ELIGIBLE_POSITION_IDS = [6, 11, 12, 16];

    public function __construct(
        private KintoneClient $api,
    ) {
    }

    public function fetchKintoneRows(): array
    {
        $offset = 0;
        $limit = 500;
        $result = [];

        do {
            $records = $this->api->getRecords(self::KINTONE_APP_ID, "limit {$limit} offset {$offset}", []);

            foreach ($records as $record) {
                if (!empty($record['退社日']['value'])) {
                    continue;
                }

                $result[] = $this->normalizeKintoneRecord($record);
            }

            $offset += $limit;
        } while (count($records) === $limit);

        return $result;
    }

    public function getApplicationData(array $statuses = [], int $perPage = 30)
    {
        $posts = PostRecord::query()
            ->where('app_type', 6)
            ->where('refresh_amount', '>', 0)
            ->when(! empty($statuses), function ($query) use ($statuses) {
                $query->whereIn('status_flag', $statuses);
            })
            ->with('receipts')
            ->with('files')
            ->with('refreshUsage')
            ->with([
                'user',
                'user.refreshAccount' => function ($query) {
                    $query->with([
                        'grants',
                        'expirations',
                        'usages',
                    ]);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->orderBy('status_flag', 'asc')
            ->paginate($perPage);

        $posts->getCollection()->transform(function (PostRecord $post) {
            $currentBalance = $this->calculateCurrentBalance($post->user?->refreshAccount);

            $post->setAttribute('current_balance', $currentBalance);
            $post->setAttribute('approved_refresh_amount', $post->refreshUsage?->status === 'approved' ? $post->refreshUsage?->amount : null);

            return $post;
        });

        return $posts;
    }

    public function getUserSummary(int $userId): array
    {
        $user = User::query()
            ->select('id', 'name')
            ->with([
                'refreshAccount' => function ($query) {
                    $query->with([
                        'grants',
                        'expirations',
                        'usages',
                    ]);
                },
            ])
            ->findOrFail($userId);

        $account = $user->refreshAccount;
        $grants = $account?->grants ?? collect();
        $expirations = $account?->expirations ?? collect();
        $usages = $account?->usages ?? collect();

        $nativeGrantTotal = $grants
            ->filter(fn (RefreshGrant $grant) => $grant->source_system !== 'kintone')
            ->sum('amount');

        $approvedUsages = $usages
            ->filter(fn (RefreshUsage $usage) => in_array($usage->status, ['approved', 'imported'], true));
        $pendingUsages = $usages
            ->filter(fn (RefreshUsage $usage) => $usage->status === 'pending');

        $nativeUsageTotal = $approvedUsages
            ->filter(fn (RefreshUsage $usage) => $usage->source_system !== 'kintone')
            ->sum('amount');

        $nativeExpirationTotal = $expirations
            ->filter(fn (RefreshExpiration $expiration) => $expiration->source_system !== 'kintone')
            ->sum('amount');

        $importedExpirationTotal = $expirations
            ->filter(fn (RefreshExpiration $expiration) => $expiration->source_system === 'kintone')
            ->sum('amount');

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'current_balance' => $this->calculateCurrentBalance($account),
            'total_granted' => (int) ($account?->opening_total_granted ?? 0) + (int) $nativeGrantTotal,
            'total_used' => (int) ($account?->opening_total_used ?? 0) + (int) $nativeUsageTotal,
            'total_expired' => (int) $importedExpirationTotal + (int) $nativeExpirationTotal,
        ];
    }

    public function getUserHistory(int $userId): array
    {
        $user = User::query()
            ->select('id', 'name')
            ->with([
                'refreshAccount' => function ($query) {
                    $query->with([
                        'grants',
                        'expirations',
                        'usages',
                    ]);
                },
            ])
            ->findOrFail($userId);

        $account = $user->refreshAccount;
        $summary = $this->getUserSummary($userId);

        if (! $account) {
            return [
                'summary' => $summary,
                'entries' => [],
            ];
        }

        $approvedUsages = $account->usages
            ->filter(fn (RefreshUsage $usage) => in_array($usage->status, ['approved', 'imported'], true));

        $ledgerBalances = $this->buildLedgerBalanceMap(
            $account->grants,
            $account->expirations,
            $approvedUsages,
            $summary['current_balance'],
        );

        $entries = collect()
            ->merge($account->grants->map(function (RefreshGrant $grant) use ($ledgerBalances) {
                return [
                    'id' => 'grant-' . $grant->id,
                    'date' => $this->formatDate($grant->granted_at),
                    'type' => 'grant',
                    'title' => $this->grantLabel($grant->grant_type),
                    'amount' => (int) $grant->amount,
                    'balance_after' => $ledgerBalances['grant:' . $grant->id] ?? $grant->remaining_amount,
                    'expires_at' => $this->formatDate($grant->expires_at),
                    'note' => $grant->note ?? '',
                ];
            }))
            ->merge($approvedUsages->map(function (RefreshUsage $usage) use ($ledgerBalances) {
                return [
                    'id' => 'usage-' . $usage->id,
                    'date' => $this->formatDate($usage->used_at),
                    'type' => 'use',
                    'title' => '利用',
                    'amount' => (int) $usage->amount,
                    'balance_after' => $ledgerBalances['usage:' . $usage->id] ?? null,
                    'expires_at' => '-',
                    'note' => $usage->note ?? '',
                ];
            }))
            ->merge($account->expirations->map(function (RefreshExpiration $expiration) use ($ledgerBalances) {
                return [
                    'id' => 'expiration-' . $expiration->id,
                    'date' => $this->formatDate($expiration->expired_at),
                    'type' => 'expire',
                    'title' => '失効',
                    'amount' => (int) $expiration->amount,
                    'balance_after' => $ledgerBalances['expiration:' . $expiration->id] ?? null,
                    'expires_at' => $this->formatDate($expiration->expired_at),
                    'note' => $expiration->note ?? '',
                ];
            }))
            ->sort(function (array $left, array $right) {
                $leftTime = $left['date'] === '-' ? 0 : Carbon::parse(str_replace('.', '-', $left['date']))->timestamp;
                $rightTime = $right['date'] === '-' ? 0 : Carbon::parse(str_replace('.', '-', $right['date']))->timestamp;

                return $rightTime <=> $leftTime;
            })
            ->values()
            ->all();

        return [
            'summary' => $summary,
            'entries' => $entries,
        ];
    }

    public function deleteApplication(int $postId): array
    {
        return DB::transaction(function () use ($postId) {
            $post = PostRecord::query()->findOrFail($postId);

            $usage = RefreshUsage::query()
                ->with('allocations')
                ->where('post_record_id', $post->id)
                ->first();

            if ($usage) {
                foreach ($usage->allocations as $allocation) {
                    $grant = RefreshGrant::query()->find($allocation->refresh_grant_id);
                    if (! $grant) {
                        continue;
                    }

                    $grant->remaining_amount = (int) ($grant->remaining_amount ?? 0) + (int) $allocation->amount;
                    $grant->save();
                }

                $usage->delete();
            }

            $post->delete();

            return ['deleted' => true, 'post_id' => $postId];
        });
    }

    public function syncFromKintone(): array
    {
        $rows = $this->fetchKintoneRows();
        $summary = [
            'fetched_rows' => count($rows),
            'matched_users' => 0,
            'synced_accounts' => 0,
            'synced_grants' => 0,
            'synced_expirations' => 0,
            'skipped_missing_user_code' => 0,
            'skipped_user_not_found' => 0,
            'skipped_ineligible_position' => 0,
            'unmatched_user_codes' => [],
        ];

        foreach ($rows as $row) {
            $userCode = $this->normalizeUserCode($row['user_code'] ?? null);

            if ($userCode === null) {
                $summary['skipped_missing_user_code']++;
                continue;
            }

            $user = User::query()
                ->select('id', 'user_code', 'position_id')
                ->where('retire', 0)
                ->where('user_code', $userCode)
                ->first();

            if (! $user) {
                $summary['skipped_user_not_found']++;
                $summary['unmatched_user_codes'][] = $userCode;
                continue;
            }

            if (! in_array((int) $user->position_id, self::ELIGIBLE_POSITION_IDS, true)) {
                $summary['skipped_ineligible_position']++;
                continue;
            }

            DB::transaction(function () use ($row, $user, &$summary) {
                $account = RefreshAccount::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'joined_date' => $this->normalizeDate($row['joined_date'] ?? null),
                        'opening_total_granted' => $this->toInt($row['total_granted'] ?? 0),
                        'opening_total_used' => $this->toInt($row['usage_amount'] ?? 0),
                        'opening_remaining_amount' => $this->toInt($row['available_balance'] ?? 0),
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ],
                );

                $summary['matched_users']++;
                $summary['synced_accounts']++;

                foreach (($row['table'] ?? []) as $grantRow) {
                    if ($this->isEmptyGrantRow($grantRow)) {
                        continue;
                    }

                    $note = $this->buildGrantNote($grantRow['remarks'] ?? null);
                    $grantSourceKey = $this->buildSourceKey([
                        $user->id,
                        $grantRow['grant_year'] ?? '',
                        $grantRow['grant_date'] ?? '',
                        $grantRow['expiration_date'] ?? '',
                        $grantRow['grant_amount'] ?? '',
                        $note,
                    ]);

                    $grant = RefreshGrant::query()->updateOrCreate(
                        [
                            'refresh_account_id' => $account->id,
                            'source_system' => 'kintone',
                            'source_key' => $grantSourceKey,
                        ],
                        [
                            'grant_type' => $this->inferGrantType($note),
                            'grant_year' => $this->parseGrantYear($grantRow['grant_year'] ?? null),
                            'granted_at' => $this->normalizeDate($grantRow['grant_date'] ?? null) ?? now()->toDateString(),
                            'expires_at' => $this->normalizeDate($grantRow['expiration_date'] ?? null),
                            'amount' => $this->toInt($grantRow['grant_amount'] ?? 0),
                            'remaining_amount' => null,
                            'note' => $note,
                            'created_by_user_id' => null,
                        ],
                    );

                    $summary['synced_grants']++;

                    $expirationAmount = $this->toInt($grantRow['expiration_amount'] ?? 0);
                    if ($expirationAmount <= 0) {
                        continue;
                    }

                    $expirationSourceKey = $this->buildSourceKey([
                        $grantSourceKey,
                        $grantRow['expiration_date'] ?? '',
                        $expirationAmount,
                    ]);

                    RefreshExpiration::query()->updateOrCreate(
                        [
                            'refresh_account_id' => $account->id,
                            'source_system' => 'kintone',
                            'source_key' => $expirationSourceKey,
                        ],
                        [
                            'refresh_grant_id' => $grant->id,
                            'expired_at' => $this->normalizeDate($grantRow['expiration_date'] ?? null) ?? now()->toDateString(),
                            'amount' => $expirationAmount,
                            'note' => $note,
                        ],
                    );

                    $summary['synced_expirations']++;
                }
            });
        }

        $summary['unmatched_user_codes'] = array_values(array_unique($summary['unmatched_user_codes']));

        return $summary;
    }

    public function getManagementData(?int $year = null): array
    {
        $targetYear = $year ?: (int) now()->year;

        $users = User::query()
            ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id', 'user_code', 'general_position', 'joined_date')
            ->whereIn('position_id', self::ELIGIBLE_POSITION_IDS)
            ->where('retire', 0)
            ->with([
                'refreshAccount' => function ($query) {
                    $query->with([
                        'grants',
                        'expirations',
                        'usages.post',
                        'annualReviews',
                    ]);
                },
                'activeLeaveRecord',
            ])
            ->orderBy('name')
            ->get();

        $employees = $users->map(fn (User $user) => $this->buildManagementEmployee($user, $targetYear))->values();

        return [
            'year' => $targetYear,
            'employees' => $employees,
            'stats' => [
                'eligible_count' => $employees->count(),
                'ready_count' => $employees->where('status', 'ready')->count(),
                'review_count' => $employees->where('status', 'review')->count(),
                'done_count' => $employees->where('status', 'done')->count(),
            ],
        ];
    }

    public function saveManagementGrant(array $payload, int $actorId): array
    {
        $user = User::query()
            ->select('id', 'position_id')
            ->whereKey($payload['user_id'])
            ->firstOrFail();

        if (! in_array((int) $user->position_id, self::ELIGIBLE_POSITION_IDS, true)) {
            abort(422, '対象外の社員です。');
        }

        $grantedAt = Carbon::parse($payload['grant_date'])->startOfDay();
        $grantYear = (int) $grantedAt->year;
        $registrationStatus = (string) $payload['registration_status'];
        $grantType = (string) $payload['grant_type'] === 'adjustment' ? 'manual' : 'annual';
        $amount = $this->toInt($payload['amount'] ?? 0);
        $note = $this->buildGrantNote($payload['judgement_note'] ?? null);

        return DB::transaction(function () use (
            $user,
            $payload,
            $actorId,
            $grantedAt,
            $grantYear,
            $registrationStatus,
            $grantType,
            $amount,
            $note,
        ) {
            $account = RefreshAccount::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'is_active' => true,
                    'opening_total_granted' => 0,
                    'opening_total_used' => 0,
                    'opening_remaining_amount' => 0,
                ],
            );

            $review = RefreshAnnualReview::query()->firstOrNew([
                'refresh_account_id' => $account->id,
                'grant_year' => $grantYear,
            ]);
            $linkedGrant = $review->exists
                ? RefreshGrant::query()
                    ->where('refresh_account_id', $account->id)
                    ->where('source_system', 'glowd')
                    ->where('source_key', $this->reviewGrantSourceKey($review->id))
                    ->first()
                : null;

            if ($registrationStatus !== 'ready' && $linkedGrant) {
                throw ValidationException::withMessages([
                    'registration_status' => ['登録済みの付与は下書き・保留に戻せません。'],
                ]);
            }

            $review->grant_type = $grantType;
            $review->grant_date = $grantedAt->toDateString();
            $review->base_amount = isset($payload['annual_base_amount'])
                ? $this->toInt($payload['annual_base_amount'])
                : ($review->base_amount ?? 0);
            $review->adjusted_amount = $amount;
            $review->attendance_status = $payload['attendance_status'] ?? $review->attendance_status;
            $review->leave_status = $payload['leave_status'] ?? $review->leave_status;
            $review->service_years = isset($payload['service_years'])
                ? $this->toInt($payload['service_years'])
                : $review->service_years;
            $review->decision_note = $note;

            if ($registrationStatus === 'ready') {
                $review->status = 'done';
                $review->reviewed_by_user_id = $actorId;
                $review->reviewed_at = now();
            } else {
                $review->status = $registrationStatus;
                $review->reviewed_by_user_id = null;
                $review->reviewed_at = null;
            }

            $review->save();

            if ($registrationStatus === 'ready') {
                $sourceKey = $this->reviewGrantSourceKey($review->id);
                $existingGrant = RefreshGrant::query()
                    ->where('refresh_account_id', $account->id)
                    ->where('source_system', 'glowd')
                    ->where('source_key', $sourceKey)
                    ->first();

                $consumedAmount = $existingGrant
                    ? max(0, (int) $existingGrant->amount - (int) ($existingGrant->remaining_amount ?? 0))
                    : 0;

                RefreshGrant::query()->updateOrCreate(
                    [
                        'refresh_account_id' => $account->id,
                        'source_system' => 'glowd',
                        'source_key' => $sourceKey,
                    ],
                    [
                        'grant_type' => $grantType,
                        'grant_year' => $grantYear,
                        'granted_at' => $grantedAt->toDateString(),
                        'expires_at' => $grantedAt->copy()->addYear()->toDateString(),
                        'amount' => $amount,
                        'remaining_amount' => max(0, $amount - $consumedAmount),
                        'note' => $note,
                        'created_by_user_id' => $actorId,
                    ]
                );
            }

            return [
                'saved' => true,
                'registered' => $registrationStatus === 'ready',
                'grant_year' => $grantYear,
                'user_id' => $user->id,
            ];
        });
    }

    public function deleteManagementReview(int $userId, int $grantYear): array
    {
        return DB::transaction(function () use ($userId, $grantYear) {
            $account = RefreshAccount::query()
                ->where('user_id', $userId)
                ->firstOrFail();

            $review = RefreshAnnualReview::query()
                ->where('refresh_account_id', $account->id)
                ->where('grant_year', $grantYear)
                ->firstOrFail();

            $linkedGrantExists = RefreshGrant::query()
                ->where('refresh_account_id', $account->id)
                ->where('source_system', 'glowd')
                ->where('source_key', $this->reviewGrantSourceKey($review->id))
                ->exists();

            if ($linkedGrantExists || $review->status === 'done') {
                throw ValidationException::withMessages([
                    'grant_year' => ['登録済みの付与はここから破棄できません。'],
                ]);
            }

            $review->delete();

            return [
                'deleted' => true,
                'user_id' => $userId,
                'grant_year' => $grantYear,
            ];
        });
    }

    public function confirmLeaveReview(int $userId, int $grantYear, int $actorId): array
    {
        $user = User::query()
            ->select('id', 'position_id')
            ->whereKey($userId)
            ->with('activeLeaveRecord')
            ->firstOrFail();

        if (! in_array((int) $user->position_id, self::ELIGIBLE_POSITION_IDS, true)) {
            abort(422, '対象外の社員です。');
        }

        if (! $user->activeLeaveRecord) {
            throw ValidationException::withMessages([
                'user_id' => ['確認対象の休職・育休がありません。'],
            ]);
        }

        return DB::transaction(function () use ($user, $grantYear, $actorId) {
            $account = RefreshAccount::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'is_active' => true,
                    'opening_total_granted' => 0,
                    'opening_total_used' => 0,
                    'opening_remaining_amount' => 0,
                ],
            );

            $review = RefreshAnnualReview::query()->firstOrNew([
                'refresh_account_id' => $account->id,
                'grant_year' => $grantYear,
            ]);

            if (! in_array($review->status, ['draft', 'hold'], true)) {
                $review->status = $review->status ?: 'done';
            }

            $review->leave_status = $this->formatLeaveStatus($user->activeLeaveRecord);
            $review->leave_review_confirmed_at = now();
            $review->leave_review_confirmed_by_user_id = $actorId;
            $review->reviewed_by_user_id = $actorId;
            $review->reviewed_at = now();
            $review->save();

            return [
                'confirmed' => true,
                'user_id' => $user->id,
                'grant_year' => $grantYear,
            ];
        });
    }

    public function approveApplication(int $postId, int $actorId, bool $forceUseRemaining = false): array
    {
        return DB::transaction(function () use ($postId) {
            $post = PostRecord::query()
                ->with([
                    'user.refreshAccount' => function ($query) {
                        $query->with([
                            'grants',
                            'expirations',
                            'usages',
                        ]);
                    },
                ])
                ->findOrFail($postId);

            $existingUsage = RefreshUsage::query()
                ->where('post_record_id', $post->id)
                ->first();

            if ($existingUsage?->status === 'approved') {
                return [
                    'post_id' => $post->id,
                    'requested_amount' => $this->toInt($post->refresh_amount),
                    'approved_amount' => (int) $existingUsage->amount,
                    'current_balance_before' => $this->calculateCurrentBalance($post->user?->refreshAccount),
                    'current_balance_after' => $this->calculateCurrentBalance($post->user?->refreshAccount),
                    'capped' => false,
                    'usage_id' => $existingUsage->id,
                    'staged' => false,
                ];
            }

            $account = RefreshAccount::query()->firstOrCreate(
                ['user_id' => $post->user_id],
                [
                    'is_active' => true,
                    'opening_total_granted' => 0,
                    'opening_total_used' => 0,
                    'opening_remaining_amount' => 0,
                ],
            );

            $requestedAmount = $this->toInt($post->refresh_amount);
            $usage = RefreshUsage::query()->updateOrCreate(
                ['post_record_id' => $post->id],
                [
                    'refresh_account_id' => $account->id,
                    'used_at' => $post->created_at ?? now(),
                    'amount' => $requestedAmount,
                    'status' => 'pending',
                    'note' => null,
                    'source_system' => 'glowd',
                    'source_key' => null,
                    'approved_by_user_id' => null,
                    'approved_at' => null,
                ],
            );

            $post->status_flag = 2;
            $post->save();

            return [
                'post_id' => $post->id,
                'requested_amount' => $requestedAmount,
                'approved_amount' => null,
                'current_balance_before' => $this->calculateCurrentBalance($post->user?->refreshAccount),
                'current_balance_after' => $this->calculateCurrentBalance($post->user?->refreshAccount),
                'capped' => false,
                'usage_id' => $usage->id,
                'staged' => true,
            ];
        });
    }

    public function confirmPendingUsage(int $usageId, int $amount, int $actorId): array
    {
        return DB::transaction(function () use ($usageId, $amount, $actorId) {
            $usage = RefreshUsage::query()
                ->with(['post', 'refreshAccount.grants', 'refreshAccount.expirations', 'refreshAccount.usages'])
                ->findOrFail($usageId);

            if ($usage->status !== 'pending') {
                throw ValidationException::withMessages([
                    'usage_id' => ['確認待ちの利用申請ではありません。'],
                ]);
            }

            $account = $usage->refreshAccount;
            if (! $account) {
                throw ValidationException::withMessages([
                    'usage_id' => ['リフレッシュ口座が見つかりません。'],
                ]);
            }

            $account->loadMissing(['grants', 'expirations', 'usages']);
            $this->ensureOpeningBalanceGrant($account);
            $account->load(['grants', 'expirations', 'usages']);

            $currentBalance = $this->calculateCurrentBalance($account);
            if ($currentBalance <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['現在保有額がありません。'],
                ]);
            }

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['利用確定額を入力してください。'],
                ]);
            }

            if ($amount > $currentBalance) {
                throw ValidationException::withMessages([
                    'amount' => ["現在保有額 {$currentBalance}円 を超えています。"],
                ]);
            }

            $requestedAmount = (int) $usage->amount;
            $usage->amount = $amount;
            $usage->status = 'approved';
            $usage->note = $amount !== $requestedAmount
                ? "申請額 {$requestedAmount}円 / 確定利用額 {$amount}円"
                : null;
            $usage->approved_by_user_id = $actorId;
            $usage->approved_at = now();
            $usage->save();

            $this->allocateUsageToGrants($account, $usage, $amount);

            if ($usage->post) {
                $usage->post->status_flag = 1;
                $usage->post->save();
            }

            return [
                'usage_id' => $usage->id,
                'requested_amount' => $requestedAmount,
                'approved_amount' => $amount,
                'current_balance_before' => $currentBalance,
                'current_balance_after' => $currentBalance - $amount,
            ];
        });
    }

    public function expireElapsedGrants(?Carbon $runDate = null): array
    {
        $runDate = ($runDate ?? now())->copy()->startOfDay();

        $grantIds = RefreshGrant::query()
            ->whereNotNull('expires_at')
            ->whereNotNull('remaining_amount')
            ->where('remaining_amount', '>', 0)
            ->whereDate('expires_at', '<', $runDate->toDateString())
            ->orderBy('expires_at')
            ->orderBy('id')
            ->pluck('id');

        $expiredGrantCount = 0;
        $expiredAmountTotal = 0;

        foreach ($grantIds as $grantId) {
            DB::transaction(function () use ($grantId, $runDate, &$expiredGrantCount, &$expiredAmountTotal) {
                /** @var RefreshGrant|null $grant */
                $grant = RefreshGrant::query()
                    ->lockForUpdate()
                    ->find($grantId);

                if (! $grant || $grant->remaining_amount === null || (int) $grant->remaining_amount <= 0) {
                    return;
                }

                if (! $grant->expires_at || ! $grant->expires_at->lt($runDate)) {
                    return;
                }

                $expiredAmount = (int) $grant->remaining_amount;

                RefreshExpiration::query()->updateOrCreate(
                    [
                        'refresh_grant_id' => $grant->id,
                        'expired_at' => $grant->expires_at->toDateString(),
                    ],
                    [
                        'refresh_account_id' => $grant->refresh_account_id,
                        'amount' => $expiredAmount,
                        'note' => '月次自動失効',
                        'source_system' => 'glowd',
                        'source_key' => $this->buildSourceKey([
                            'monthly_expiration',
                            $grant->id,
                            $grant->expires_at->toDateString(),
                        ]),
                    ]
                );

                $grant->remaining_amount = 0;
                $grant->save();

                $expiredGrantCount++;
                $expiredAmountTotal += $expiredAmount;
            });
        }

        return [
            'run_date' => $runDate->toDateString(),
            'expired_grants' => $expiredGrantCount,
            'expired_amount_total' => $expiredAmountTotal,
        ];
    }

    private function normalizeKintoneRecord(array $record): array
    {
        $tableRows = [];

        foreach (($record['テーブル']['value'] ?? []) as $tableRow) {
            $tableRows[] = [
                'expiration_date' => $tableRow['value']['消滅日']['value'] ?? null,
                'remarks' => $tableRow['value']['備考']['value'] ?? null,
                'expiration_amount' => $tableRow['value']['消滅金額']['value'] ?? null,
                'grant_amount' => $tableRow['value']['付与金額']['value'] ?? null,
                'grant_date' => $tableRow['value']['付与日']['value'] ?? null,
                'grant_year' => $tableRow['value']['付与年度']['value'] ?? null,
            ];
        }

        return [
            'user_code' => $record['文字列__1行_']['value'] ?? null,
            'total_granted' => $record['付与金額合計']['value'] ?? null,
            'position' => $record['雇用形態']['value'] ?? null,
            'joined_date' => $record['入社日']['value'] ?? null,
            'usage_amount' => $record['利用金額']['value'] ?? null,
            'user_name' => $record['ルックアップ']['value'] ?? null,
            'available_balance' => $record['利用可能残高']['value'] ?? null,
            'table' => $tableRows,
        ];
    }

    private function buildManagementEmployee(User $user, int $targetYear): array
    {
        $account = $user->refreshAccount;
        $activeLeaveRecord = $user->activeLeaveRecord;
        $grants = $account?->grants ?? collect();
        $expirations = $account?->expirations ?? collect();
        $usages = $account?->usages ?? collect();
        $reviews = $account?->annualReviews ?? collect();

        $currentReview = $this->resolveCurrentReview($reviews, $targetYear);
        $latestAnnualGrant = $grants
            ->filter(fn (RefreshGrant $grant) => in_array($grant->grant_type, ['annual', 'manual'], true))
            ->sortByDesc(fn (RefreshGrant $grant) => $grant->granted_at?->timestamp ?? 0)
            ->first();

        $nativeGrantTotal = $grants
            ->filter(fn (RefreshGrant $grant) => $grant->source_system !== 'kintone')
            ->sum('amount');

        $approvedUsages = $usages
            ->filter(fn (RefreshUsage $usage) => in_array($usage->status, ['approved', 'imported'], true));
        $pendingUsages = $usages
            ->filter(fn (RefreshUsage $usage) => $usage->status === 'pending');
        $nativeUsageTotal = $approvedUsages
            ->filter(fn (RefreshUsage $usage) => $usage->source_system !== 'kintone')
            ->sum('amount');

        $nativeExpirationTotal = $expirations
            ->filter(fn (RefreshExpiration $expiration) => $expiration->source_system !== 'kintone')
            ->sum('amount');

        $importedExpirationTotal = $expirations
            ->filter(fn (RefreshExpiration $expiration) => $expiration->source_system === 'kintone')
            ->sum('amount');

        $currentBalance = $this->calculateCurrentBalance($account);

        $totalGranted = (int) ($account?->opening_total_granted ?? 0) + (int) $nativeGrantTotal;
        $totalUsed = (int) ($account?->opening_total_used ?? 0) + (int) $nativeUsageTotal;
        $totalExpired = (int) $importedExpirationTotal + (int) $nativeExpirationTotal;

        $ledgerBalances = $this->buildLedgerBalanceMap($grants, $expirations, $approvedUsages, $currentBalance);

        $grantRows = $grants->map(function (RefreshGrant $grant) use ($ledgerBalances) {
            return [
                'id' => (string) $grant->id,
                'source' => $this->grantLabel($grant->grant_type),
                'grantedAt' => $this->formatDate($grant->granted_at),
                'expiresAt' => $this->formatDate($grant->expires_at),
                'amount' => (int) $grant->amount,
                'remaining' => $ledgerBalances['grant:' . $grant->id] ?? null,
                'note' => $grant->note ?? '',
            ];
        })->values();

        $activityRows = collect()
            ->merge($approvedUsages->map(function (RefreshUsage $usage) use ($ledgerBalances) {
                return [
                    'id' => 'use-' . $usage->id,
                    'type' => 'use',
                    'title' => 'リフレッシュ利用申請',
                    'happenedAt' => $this->formatDate($usage->used_at),
                    'amount' => (int) $usage->amount,
                    'balanceAfter' => $ledgerBalances['usage:' . $usage->id] ?? null,
                    'relatedExpiry' => '',
                    'note' => $usage->note ?? '',
                ];
            }))
            ->merge($expirations->map(function (RefreshExpiration $expiration) use ($ledgerBalances) {
                return [
                    'id' => 'expire-' . $expiration->id,
                    'type' => 'expire',
                    'title' => '失効',
                    'happenedAt' => $this->formatDate($expiration->expired_at),
                    'amount' => (int) $expiration->amount,
                    'balanceAfter' => $ledgerBalances['expiration:' . $expiration->id] ?? null,
                    'relatedExpiry' => $this->formatDate($expiration->expired_at),
                    'note' => $expiration->note ?? '',
                ];
            }))
            ->sortByDesc('happenedAt')
            ->values();

        $suggestedAmount = $currentReview?->adjusted_amount ?: ($currentReview?->base_amount ?: ((int) ($latestAnnualGrant?->amount ?? 0)));
        $annualBaseAmount = $currentReview?->base_amount ?: ((int) ($latestAnnualGrant?->amount ?? 0));

        $hasRegisteredGrant = $currentReview
            ? $grants->contains(function (RefreshGrant $grant) use ($currentReview) {
                return $grant->source_system === 'glowd'
                    && $grant->source_key === $this->reviewGrantSourceKey($currentReview->id);
            })
            : false;

        return [
            'id' => (int) $user->id,
            'user' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'icon_path' => $user->icon_path,
                'icon_bg' => $user->icon_bg,
                'positions' => null,
                'evaluation' => null,
                'general_position' => $user->general_position ?? '',
                'joined_date' => $user->joined_date ?? '',
            ],
            'positionId' => (int) $user->position_id,
            'hireDate' => $account?->joined_date?->toDateString() ?? '',
            'attendanceStatus' => '',
            'leaveStatus' => $this->formatLeaveStatus($activeLeaveRecord),
            'annualBaseAmount' => (int) $annualBaseAmount,
            'currentBalance' => $currentBalance,
            'totalGranted' => $totalGranted,
            'totalUsed' => $totalUsed,
            'totalExpired' => $totalExpired,
            'suggestedAmount' => (int) $suggestedAmount,
            'judgementNote' => $currentReview?->decision_note ?? '',
            'status' => $this->resolveEmployeeStatus($currentReview, $grants, $targetYear, $activeLeaveRecord, $pendingUsages),
            'statusReason' => $this->resolveEmployeeStatusReason($currentReview, $grants, $targetYear, $activeLeaveRecord, $pendingUsages),
            'leaveReview' => [
                'hasActiveLeave' => (bool) $activeLeaveRecord,
                'confirmed' => (bool) $currentReview?->leave_review_confirmed_at,
                'canMoveToReady' => (bool) $activeLeaveRecord
                    && ! $currentReview?->leave_review_confirmed_at
                    && (($pendingUsages?->count() ?? 0) === 0)
                    && ! in_array($currentReview?->status, ['draft', 'hold'], true),
            ],
            'reviewDraft' => [
                'hasSavedReview' => (bool) $currentReview,
                'hasRegisteredGrant' => $hasRegisteredGrant,
                'grantType' => $currentReview?->grant_type === 'manual' ? 'adjustment' : 'annual',
                'grantDate' => $currentReview?->grant_date?->toDateString()
                    ?? ($latestAnnualGrant?->granted_at?->toDateString() ?? Carbon::create($targetYear, 1, 1)->toDateString()),
                'amount' => (int) ($currentReview?->adjusted_amount ?? $suggestedAmount),
                'registrationStatus' => match ($currentReview?->status) {
                    'hold' => 'hold',
                    'done' => 'ready',
                    default => 'draft',
                },
                'judgementNote' => $currentReview?->decision_note ?? '',
            ],
            'pendingUsages' => $pendingUsages->map(function (RefreshUsage $usage) {
                return [
                    'id' => (int) $usage->id,
                    'postId' => (int) $usage->post_record_id,
                    'requestedAmount' => (int) $usage->amount,
                    'appliedAt' => $this->formatDate($usage->used_at),
                    'title' => $usage->post?->title ?? 'リフレッシュ利用申請',
                    'note' => $usage->post?->content ?? '',
                    'receipts' => $usage->post?->receipts ?? [],
                ];
            })->values(),
            'grants' => $grantRows,
            'activities' => $activityRows,
        ];
    }

    private function resolveCurrentReview(Collection $reviews, int $targetYear): ?RefreshAnnualReview
    {
        return $reviews
            ->filter(fn (RefreshAnnualReview $review) => (int) $review->grant_year === $targetYear)
            ->sortByDesc('id')
            ->first();
    }

    private function resolveEmployeeStatus(
        ?RefreshAnnualReview $review,
        Collection $grants,
        int $targetYear,
        ?UserLeaveRecord $activeLeaveRecord,
        ?Collection $pendingUsages = null
    ): string
    {
        if (($pendingUsages?->count() ?? 0) > 0) {
            return 'review';
        }

        $hasCurrentYearAnnualGrant = $grants->contains(function (RefreshGrant $grant) use ($targetYear) {
            return in_array($grant->grant_type, ['annual', 'manual'], true)
                && (int) $grant->grant_year === $targetYear;
        });

        if (in_array($review?->status, ['draft', 'hold'], true)) {
            return 'review';
        }

        if ($activeLeaveRecord && ! $review?->leave_review_confirmed_at) {
            return 'review';
        }

        if ($hasCurrentYearAnnualGrant) {
            return 'done';
        }

        return 'ready';
    }

    private function resolveEmployeeStatusReason(
        ?RefreshAnnualReview $review,
        Collection $grants,
        int $targetYear,
        ?UserLeaveRecord $activeLeaveRecord,
        ?Collection $pendingUsages = null
    ): string
    {
        if (($pendingUsages?->count() ?? 0) > 0) {
            return '利用申請あり';
        }

        $hasCurrentYearAnnualGrant = $grants->contains(function (RefreshGrant $grant) use ($targetYear) {
            return in_array($grant->grant_type, ['annual', 'manual'], true)
                && (int) $grant->grant_year === $targetYear;
        });

        if ($review?->status === 'hold') {
            return $hasCurrentYearAnnualGrant
                ? '年次付与済み / 保留あり'
                : '保留中';
        }

        if ($review?->status === 'draft') {
            return $hasCurrentYearAnnualGrant
                ? '年次付与済み / 下書きあり'
                : '下書きあり';
        }

        if ($activeLeaveRecord && ! $review?->leave_review_confirmed_at) {
            return '休職・育休あり';
        }

        if ($hasCurrentYearAnnualGrant) {
            return '今年分を登録済み';
        }

        if ($activeLeaveRecord && $review?->leave_review_confirmed_at) {
            return '休職・育休確認済み';
        }

        if ($review?->status === 'done') {
            return '判定保存済み';
        }

        return 'そのまま付与可能';
    }

    private function buildLedgerBalanceMap(Collection $grants, Collection $expirations, Collection $usages, int $currentBalance): array
    {
        $events = collect()
            ->merge($grants->map(function (RefreshGrant $grant) {
                return [
                    'key' => 'grant:' . $grant->id,
                    'date' => $grant->granted_at ? Carbon::instance($grant->granted_at)->startOfDay() : null,
                    'delta' => (int) $grant->amount,
                    'priority' => 1,
                ];
            }))
            ->merge($usages->map(function (RefreshUsage $usage) {
                return [
                    'key' => 'usage:' . $usage->id,
                    'date' => $usage->used_at ? Carbon::instance($usage->used_at) : null,
                    'delta' => -1 * (int) $usage->amount,
                    'priority' => 2,
                ];
            }))
            ->merge($expirations->map(function (RefreshExpiration $expiration) {
                return [
                    'key' => 'expiration:' . $expiration->id,
                    'date' => $expiration->expired_at ? Carbon::instance($expiration->expired_at)->startOfDay() : null,
                    'delta' => -1 * (int) $expiration->amount,
                    'priority' => 3,
                ];
            }))
            ->filter(fn (array $event) => $event['date'] !== null)
            ->sort(function (array $left, array $right) {
                $leftTime = $left['date']->timestamp;
                $rightTime = $right['date']->timestamp;

                if ($leftTime === $rightTime) {
                    return $right['priority'] <=> $left['priority'];
                }

                return $rightTime <=> $leftTime;
            })
            ->values();

        $runningBalance = $currentBalance;
        $map = [];

        foreach ($events as $event) {
            $map[$event['key']] = $runningBalance;
            $runningBalance -= $event['delta'];
        }

        return $map;
    }

    private function calculateCurrentBalance(?RefreshAccount $account): int
    {
        if (! $account) {
            return 0;
        }

        $grantRemaining = $account->grants
            ->filter(fn (RefreshGrant $grant) => $grant->remaining_amount !== null)
            ->sum(fn (RefreshGrant $grant) => (int) $grant->remaining_amount);

        return (int) ($account->opening_remaining_amount ?? 0) + (int) $grantRemaining;
    }

    private function ensureOpeningBalanceGrant(RefreshAccount $account): void
    {
        $openingRemaining = (int) ($account->opening_remaining_amount ?? 0);

        if ($openingRemaining <= 0) {
            return;
        }

        $existing = $account->grants()
            ->where('grant_type', 'opening_balance')
            ->where('source_system', 'kintone')
            ->first();

        if ($existing) {
            return;
        }

        $account->grants()->create([
            'grant_type' => 'opening_balance',
            'grant_year' => null,
            'granted_at' => ($account->last_synced_at ?? now())->toDateString(),
            'expires_at' => null,
            'amount' => $openingRemaining,
            'remaining_amount' => $openingRemaining,
            'note' => 'Kintone移行時残高',
            'source_system' => 'kintone',
            'source_key' => sha1("opening_balance|{$account->id}|{$openingRemaining}"),
            'created_by_user_id' => null,
        ]);

        $account->opening_remaining_amount = 0;
        $account->save();
    }

    private function allocateUsageToGrants(RefreshAccount $account, RefreshUsage $usage, int $amount): void
    {
        $remainingToAllocate = $amount;

        $grants = $account->grants()
            ->whereNotNull('remaining_amount')
            ->where('remaining_amount', '>', 0)
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_at')
            ->orderBy('granted_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($grants as $grant) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $available = (int) $grant->remaining_amount;
            if ($available <= 0) {
                continue;
            }

            $allocated = min($available, $remainingToAllocate);

            RefreshUsageAllocation::query()->create([
                'refresh_usage_id' => $usage->id,
                'refresh_grant_id' => $grant->id,
                'amount' => $allocated,
            ]);

            $grant->remaining_amount = $available - $allocated;
            $grant->save();

            $remainingToAllocate -= $allocated;
        }

        if ($remainingToAllocate > 0) {
            throw ValidationException::withMessages([
                'refresh_amount' => ['利用額を付与残高へ割り当てできませんでした。'],
            ]);
        }
    }

    private function inferGrantType(?string $note): string
    {
        $value = mb_strtolower((string) $note);

        if (str_contains($value, 'グラウドナイン') || str_contains($value, 'glowd')) {
            return 'glowd_nine';
        }

        if (str_contains($value, 'チャレンジ') || str_contains($value, 'challenge')) {
            return 'challenge';
        }

        if (str_contains($value, '調整')) {
            return 'manual';
        }

        return 'annual';
    }

    private function grantLabel(string $grantType): string
    {
        return match ($grantType) {
            'glowd_nine' => 'グラウドナイン',
            'challenge' => 'チャレンジ',
            'challenge_award' => 'チャレンジチャージ',
            'challenge_grant' => 'チャレンジ必要経費',
            'manual' => '手動調整',
            'opening_balance' => '移行時残高',
            default => '年次付与',
        };
    }

    private function parseGrantYear(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/(\d{4})/', $value, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate($value): string
    {
        if (! $value) {
            return '-';
        }

        try {
            if ($value instanceof Carbon) {
                return $value->format('Y.m.d');
            }

            return Carbon::parse($value)->format('Y.m.d');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatLeaveStatus(?UserLeaveRecord $leaveRecord): string
    {
        if (! $leaveRecord) {
            return 'なし';
        }

        $start = $this->formatDate($leaveRecord->leave_start);
        $end = $leaveRecord->leave_end ? $this->formatDate($leaveRecord->leave_end) : '継続中';

        return "あり ({$start} - {$end})";
    }

    private function toInt($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) preg_replace('/[^\d-]/', '', (string) $value);
    }

    private function normalizeUserCode($value): ?int
    {
        $normalized = $this->toInt($value);

        return $normalized > 0 ? $normalized : null;
    }

    private function isEmptyGrantRow(array $grantRow): bool
    {
        return empty($grantRow['grant_date'])
            && empty($grantRow['grant_amount'])
            && empty($grantRow['expiration_date'])
            && empty($grantRow['expiration_amount'])
            && empty($grantRow['remarks']);
    }

    private function buildGrantNote(?string $remarks): ?string
    {
        $note = trim((string) $remarks);

        return $note !== '' ? $note : null;
    }

    private function buildSourceKey(array $parts): string
    {
        return sha1(implode('|', array_map(static fn ($value) => (string) $value, $parts)));
    }

    private function reviewGrantSourceKey(int $reviewId): string
    {
        return sha1('annual_review|' . $reviewId);
    }
}
