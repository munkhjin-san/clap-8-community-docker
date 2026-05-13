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
            ->where('app_type', 2)
            ->where(function ($q) {
                $q->where(fn ($q) =>
                    $q->where('mini', 1)
                    ->whereDate('created_at', '<=', now()->subDays(8))
                )->orWhere(fn ($q) =>
                    $q->where('mini', 0)
                    ->whereDate('created_at', '<=', now()->subDays(15))
                );
            });
            
        $grantable_posts = (clone $posts)
            ->where('grantable', 1)
            ->where(function ($q) {
                $q->where('status_flag', 0)
                    ->orWhere('status_flag', 5);
            })
            ->leftJoinSub($awardsSub, 'aw', 'aw.record_id', '=', 'post_records.id')
            ->leftJoinSub($expensesSub, 'ex', 'ex.post_record_id', '=', 'post_records.id')
            ->whereRaw('COALESCE(aw.awards_sum,0) < COALESCE(ex.expenses_sum,0)')
            ->select('post_records.*')
            ->get();
        $expired_posts = (clone $posts)->where('status_flag', 0)->get();
        $all_posts = $grantable_posts->merge($expired_posts);

        $refunded = 0;
        $expired = 0;

        foreach ($grantable_posts as $post) {
            DB::transaction(function () use ($post, &$refunded) {
                $post->update(['status_flag' => 4]);
                PostRefundService::refundAwardsFor($post);
                $refunded++;
            });
        }
        foreach ($expired_posts as $post) {
            DB::transaction(function () use ($post, &$expired) {
                $post->timestamps = false; // Disable timestamps to avoid updating updated_at
                $post->update(['status_flag' => 5]);
                $expired++;
                $post->timestamps = true; // Re-enable timestamps
            });
        }
        $this->info("Closed and refunded {$refunded} post(s).");
        $this->info("Processed and expired {$expired} post(s).");
        return self::SUCCESS;
    }
}
