<?php

// app/Services/PostRefundService.php
namespace App\Services;

use App\Models\PostRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostRefundService
{
    public static function refundAwardsFor(PostRecord $post): int
    {
        return DB::transaction(function () use ($post) {
            // pessimistic lock the post to avoid concurrent settle
            $post->lockForUpdate();

            if ($post->status_flag !== 4) {
                return 0;
            }

            $batch = (string) Str::uuid();

            // Group refundable awards by user
            $rows = DB::table('post_awards')
                ->select('user_id', DB::raw('SUM(award_bet) as amt'))
                ->where('record_id', $post->id)
                ->whereNull('refunded_at')
                ->groupBy('user_id')
                ->lockForUpdate()
                ->get();

            // credit users in bulk (one update per user)
            foreach ($rows as $row) {
                DB::table('users')
                    ->where('id', $row->user_id)
                    ->increment('award_charge', $row->amt);
            }

            // mark all awards as refunded in one shot
            DB::table('post_awards')
                ->where('record_id', $post->id)
                ->whereNull('refunded_at')
                ->update([
                    'refunded_at'    => now(),
                    'refund_batch_id'=> $batch,
                ]);

            return $rows->count(); // number of distinct users refunded
        });
    }

    /**
     * Return an unselected rakuaward nomination's charges to its chargers.
     *
     * @return array{users:int, amount:int}
     */
    public static function refundRakuawardCharges(int $postId): array
    {
        return DB::transaction(function () use ($postId) {
            $post = PostRecord::query()
                ->where('id', $postId)
                ->where('app_type', 0)
                ->where('rakuaward', 1)
                ->lockForUpdate()
                ->first();

            // Skip if already granted to the top 5 or already refunded.
            if (! $post || ! is_null($post->rakuaward_granted_at) || ! is_null($post->rakuaward_refunded_at)) {
                return ['users' => 0, 'amount' => 0];
            }

            $batch = (string) Str::uuid();

            $rows = DB::table('post_awards')
                ->select('user_id', DB::raw('SUM(award_bet) as amt'))
                ->where('record_id', $post->id)
                ->whereNull('refunded_at')
                ->groupBy('user_id')
                ->lockForUpdate()
                ->get();

            $amount = 0;
            foreach ($rows as $row) {
                DB::table('users')
                    ->where('id', $row->user_id)
                    ->increment('award_charge', $row->amt);
                $amount += (int) $row->amt;
            }

            DB::table('post_awards')
                ->where('record_id', $post->id)
                ->whereNull('refunded_at')
                ->update([
                    'refunded_at' => now(),
                    'refund_batch_id' => $batch,
                ]);

            $post->timestamps = false;
            $post->rakuaward_refunded_at = now();
            $post->save();

            return ['users' => $rows->count(), 'amount' => $amount];
        });
    }
}
