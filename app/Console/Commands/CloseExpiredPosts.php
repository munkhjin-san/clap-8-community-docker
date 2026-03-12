<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PostRecord;
use App\Services\PostRefundService;

class CloseExpiredPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark grantable posts as failed if 14 days passed and awards < expenses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $awardsSub = DB::table('post_awards')
            ->select('record_id', DB::raw('COALESCE(SUM(award_bet),0) as awards_sum'))
            ->groupBy('record_id');
        
        $expensesSub = DB::table('post_grants')
            ->select('post_record_id', DB::raw('COALESCE(SUM(expenses),0) as expenses_sum'))
            ->groupBy('post_record_id');

        $posts = PostRecord::query()
            ->where('grantable', 1)
            ->where('created_at', '<=', now()->subDays(14))
            ->leftJoinSub($awardsSub, 'aw', 'aw.record_id', '=', 'post_records.id')
            ->leftJoinSub($expensesSub, 'ex', 'ex.post_record_id', '=', 'post_records.id')
            ->whereRaw('COALESCE(aw.awards_sum,0) < COALESCE(ex.expenses_sum,0)')
            ->select('post_records.*')
            ->get();

        $failed = 0;
        foreach ($posts as $post) {
            DB::transaction(function () use ($post, &$failed) {
                $post->lockForUpdate();
                if ($post->status_flag === 0) {
                    $post->update(['status_flag' => 4]);
                }
                PostRefundService::refundAwardsFor($post);
                $failed++;
            });
        }

        $this->info("Closed and refunded {$failed} post(s).");
        return self::SUCCESS;
    }
}
