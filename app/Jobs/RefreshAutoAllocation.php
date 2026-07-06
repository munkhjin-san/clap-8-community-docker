<?php

namespace App\Jobs;

use App\Models\RefreshAccount;
use App\Models\RefreshGrant;
use App\Models\PostRecord;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\taskUser;
use App\Models\CustomFormUser;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RefreshAutoAllocation implements ShouldQueue
{
    use Queueable;

    /**
     * @var int[]
     */

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lastMonth = Carbon::now()->subMonthNoOverflow();
        $grantDate = Carbon::now()->startOfMonth();
        $expiresAt = $grantDate->copy()->addYear();
        $periodKey = $lastMonth->format('Y-m');
        $periodLabel = $lastMonth->format('Y年m月');
        $runId = (string) Str::uuid();
        $start = microtime(true);
        $taskQuery = taskUser::select(
                'user_id',
                DB::raw('SUM(prize) as total_prize')
            )
            ->where('glowd_nine', 1)
            ->whereYear('created_at', $lastMonth->year)
            ->whereMonth('created_at', $lastMonth->month)
            ->where('prize', '>', 0)
            ->groupBy('user_id');

        $formQuery = CustomFormUser::select(
                'user_id',
                DB::raw('SUM(prize) as total_prize')
            )
            ->whereYear('created_at', $lastMonth->year)
            ->whereMonth('created_at', $lastMonth->month)
            ->where('prize', '>', 0)
            ->groupBy('user_id');

        $totals = DB::query()
            ->fromSub(
                $taskQuery->unionAll($formQuery),
                'monthly_prizes'
            )
            ->select(
                'user_id',
                DB::raw('SUM(total_prize) as grand_total_prize')
            )
            ->groupBy('user_id')
            ->havingRaw('SUM(total_prize) > 0')
            ->get();
        $completed_posts = PostRecord::where('app_type', 2)
            ->whereYear('date_end', $lastMonth->year)
            ->whereMonth('date_end', $lastMonth->month)
            ->where('status_flag', 1)
            ->where('donatable', 0)
            ->with(['to_users:id,position_id', 'awards:id', 'grants:id,post_record_id,expenses'])
            ->get();
        
        $uncompleted_grants = PostRecord::where('app_type', 2)
            ->whereYear('date_end', $lastMonth->year)
            ->whereMonth('date_end', $lastMonth->month)
            ->whereNotIn('status_flag', [1, 2])
            ->whereHas('grants', function ($query) {
                $query->where('expenses', '>', 0);
            })
            ->with(['to_users:id,position_id', 'grants:id,post_record_id,expenses'])
            ->get();
        
        $challengeAwards = [];
        $challengeGrants = [];
        
        foreach ($completed_posts as $post) {
            $toUserIds = $post->to_users
                ->filter(fn ($user) => $user->hasCapability('benefit.refresh'))
                ->pluck('id')
                ->unique()
                ->values();

            if ($toUserIds->isEmpty()) {
                continue;
            }

            $recipientCount = $toUserIds->count();

            $awardsTotal = $post->awards->sum(function ($awardUser) {
                return (int) $awardUser->pivot->award_bet;
            });

            $grantsTotal = (int) $post->grants->sum('expenses');

            $awardPerUser = $recipientCount > 0 ? $awardsTotal / $recipientCount : 0;
            $grantPerUser = $recipientCount > 0 ? $grantsTotal / $recipientCount : 0;

            foreach ($toUserIds as $userId) {
                if ($awardPerUser > 0) {
                    if (!isset($challengeAwards[$userId])) {
                        $challengeAwards[$userId] = 0;
                    }

                    $challengeAwards[$userId] += $awardPerUser;
                }

                if ($grantPerUser > 0) {
                    if (!isset($challengeGrants[$userId])) {
                        $challengeGrants[$userId] = 0;
                    }

                    $challengeGrants[$userId] += $grantPerUser;
                }
            }
        }

        foreach ($uncompleted_grants as $post) {
            $toUserIds = $post->to_users
                ->filter(fn ($user) => $user->hasCapability('benefit.refresh'))
                ->pluck('id')
                ->unique()
                ->values();

            if ($toUserIds->isEmpty()) {
                continue;
            }

            $recipientCount = $toUserIds->count();
            $grantsTotal = (int) $post->grants->sum('expenses');
            $grantPerUser = $grantsTotal / $recipientCount;

            foreach ($toUserIds as $userId) {
                if (!isset($challengeGrants[$userId])) {
                    $challengeGrants[$userId] = 0;
                }

                $challengeGrants[$userId] += $grantPerUser;
            }
        }

        $glowdNineTotals = $totals
            ->filter(fn ($row) => (int) $row->grand_total_prize > 0)
            ->mapWithKeys(fn ($row) => [(int) $row->user_id => (int) $row->grand_total_prize])
            ->toArray();

        $challengeAwards = collect($challengeAwards)
            ->map(fn ($amount) => (int) round($amount))
            ->filter(fn ($amount) => $amount > 0)
            ->toArray();

        $challengeGrants = collect($challengeGrants)
            ->map(fn ($amount) => (int) round($amount))
            ->filter(fn ($amount) => $amount > 0)
            ->toArray();

        DB::transaction(function () use (
            $glowdNineTotals,
            $challengeAwards,
            $challengeGrants,
            $grantDate,
            $expiresAt,
            $periodKey,
            $periodLabel
        ) {
            foreach ($glowdNineTotals as $userId => $amount) {
                $this->upsertGrant(
                    (int) $userId,
                    'glowd_nine',
                    (int) $amount,
                    "{$periodLabel} グラウドナイン",
                    "glowd_nine|{$periodKey}|{$userId}",
                    $grantDate,
                    $expiresAt
                );
            }

            foreach ($challengeAwards as $userId => $amount) {
                $this->upsertGrant(
                    (int) $userId,
                    'challenge_award',
                    (int) $amount,
                    "{$periodLabel} チャレンジチャージ",
                    "challenge_award|{$periodKey}|{$userId}",
                    $grantDate,
                    $expiresAt
                );
            }

            foreach ($challengeGrants as $userId => $amount) {
                $this->upsertGrant(
                    (int) $userId,
                    'challenge_grant',
                    (int) $amount,
                    "{$periodLabel} チャレンジ必要経費",
                    "challenge_grant|{$periodKey}|{$userId}",
                    $grantDate,
                    $expiresAt
                );
            }
        });
        Log::info('refresh-auto-allocation:glowd-nine challenges', [
            'run_id' => $runId,
            'glowdNine' => $glowdNineTotals,
            'challengeAwards' => $challengeAwards,
            'challengeGrants' => $challengeGrants,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
        ]);
    }

    private function upsertGrant(
        int $userId,
        string $grantType,
        int $amount,
        string $note,
        string $sourceKey,
        Carbon $grantDate,
        Carbon $expiresAt
    ): void {
        if ($amount <= 0) {
            return;
        }

        $user = User::query()
            ->select('id', 'position_id')
            ->find($userId);

        if (! $user || ! $user->hasCapability('benefit.refresh')) {
            return;
        }

        $account = RefreshAccount::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'is_active' => true,
                'opening_total_granted' => 0,
                'opening_total_used' => 0,
                'opening_remaining_amount' => 0,
            ]
        );

        $grant = RefreshGrant::query()
            ->where('refresh_account_id', $account->id)
            ->where('source_system', 'glowd')
            ->where('source_key', sha1($sourceKey))
            ->first();

        if (! $grant) {
            RefreshGrant::query()->create([
                'refresh_account_id' => $account->id,
                'grant_type' => $grantType,
                'grant_year' => (int) $grantDate->year,
                'granted_at' => $grantDate->toDateString(),
                'expires_at' => $expiresAt->toDateString(),
                'amount' => $amount,
                'remaining_amount' => $amount,
                'note' => $note,
                'source_system' => 'glowd',
                'source_key' => sha1($sourceKey),
                'created_by_user_id' => null,
            ]);

            return;
        }

        $consumedAmount = max(0, (int) $grant->amount - (int) ($grant->remaining_amount ?? 0));
        $grant->grant_type = $grantType;
        $grant->grant_year = (int) $grantDate->year;
        $grant->granted_at = $grantDate->toDateString();
        $grant->expires_at = $expiresAt->toDateString();
        $grant->amount = $amount;
        $grant->remaining_amount = max(0, $amount - $consumedAmount);
        $grant->note = $note;
        $grant->save();
    }
}
