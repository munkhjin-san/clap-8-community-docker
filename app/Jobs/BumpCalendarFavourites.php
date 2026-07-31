<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ユーザー一覧の並び順に使うスコアを、新しく作られた予定の分だけ進める。
 * 正確さは求めない：窓（直近N ヶ月）の引き直しや編集・削除の反映は
 * calendar:rebuild-favourites の作り直しに任せる。
 *
 * 並び順のためだけの処理なので、失敗してもログだけ残して黙って終わる。
 */
class BumpCalendarFavourites implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** 1人だけの予定と、これより多い会議は並び順の参考にならないので無視する */
    private const MAX_ATTENDEES = 10;

    public function __construct(public array $user_ids)
    {
        //
    }

    public function handle(): void
    {
        try {
            $ids = User::whereIn('id', collect($this->user_ids)->map(fn($id) => (int) $id)->unique()->all())
                    ->where('retire', 0)
                    ->where('deleted_flag', 0)
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->all();

            $count = count($ids);
            if ($count < 2 || $count > self::MAX_ATTENDEES) {
                return;
            }

            $weight = 1 / ($count - 1);
            $now = now();

            // 参加者同士の全ペア（双方向）が対象。既にある行を先に調べる
            $existing = DB::table('calendar_favourite_users')
                        ->whereIn('owner_id', $ids)
                        ->whereIn('member_id', $ids)
                        ->get(['owner_id', 'member_id'])
                        ->mapWithKeys(fn($row) => [(int) $row->owner_id.'-'.(int) $row->member_id => true])
                        ->all();

            // 既存分は1文でまとめて加算する
            DB::table('calendar_favourite_users')
                ->whereIn('owner_id', $ids)
                ->whereIn('member_id', $ids)
                ->whereColumn('owner_id', '<>', 'member_id')
                ->incrementEach(
                    ['score' => $weight, 'shared_count' => 1],
                    ['last_together_at' => $now, 'updated_at' => $now]
                );

            // 無かったペアだけ作る。insertOrIgnore なので同時作成でユニーク衝突しても落ちない
            $fresh = [];
            foreach ($ids as $owner) {
                foreach ($ids as $member) {
                    if ($owner === $member || isset($existing[$owner.'-'.$member])) {
                        continue;
                    }
                    $fresh[] = [
                        'owner_id' => $owner,
                        'member_id' => $member,
                        'score' => $weight,
                        'shared_count' => 1,
                        'last_together_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if ($fresh) {
                DB::table('calendar_favourite_users')->insertOrIgnore($fresh);
            }
        } catch (\Throwable $e) {
            Log::warning('BumpCalendarFavourites failed', ['error' => $e->getMessage()]);
        }
    }
}
