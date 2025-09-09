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
}
