<?php

namespace App\Console\Commands;

use App\Models\PostRecord;
use App\Models\PostRelay;
use App\Models\PostRelayPrize;
use Illuminate\Console\Command;

class BackfillNiceRelayGlowdNine extends Command
{
    protected $signature = 'posts:backfill-nice-relay-glowd-nine {--dry-run} {--min= : Override the participant threshold (defaults to PostRelay::NICE_RELAY_LIMIT)}';

    protected $description = 'Create GlowdNine plays for already-completed nice relays that reached the participant limit before the feature could record them.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $min = $this->option('min') !== null ? (int) $this->option('min') : PostRelay::NICE_RELAY_LIMIT;

        // Any nice post that handed a baton onward is a potential "trigger" post; we run the
        // same participant computation used at post-creation time (chain authors + pending recipients).
        $sourcePostIds = PostRelay::where('relay_type', PostRelay::TYPE_NICE)
            ->pluck('source_post_id')
            ->unique()
            ->values();

        $seenRoots = [];
        $qualifying = 0;
        $created = 0;

        foreach ($sourcePostIds as $sourcePostId) {
            $post = PostRecord::with('user')->find($sourcePostId);
            if (! $post || (int) $post->app_type !== 0 || $post->rakuaward) {
                continue;
            }

            [$rootId, $posterIds] = $this->walkBack($post);

            $pendingRecipientIds = PostRelay::where('relay_type', PostRelay::TYPE_NICE)
                ->where('source_post_id', $post->id)
                ->where('status', PostRelay::STATUS_PENDING)
                ->whereNotIn('to_user_id', PostRelay::EXCLUDED_USER_IDS)
                ->pluck('to_user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $participantIds = collect($posterIds)->merge($pendingRecipientIds)->unique()->values();

            if ($participantIds->count() < $min || in_array($rootId, $seenRoots, true)) {
                continue;
            }

            $seenRoots[] = $rootId;
            $qualifying++;
            $this->info("chain root #{$rootId}: {$participantIds->count()} participants -> [" . $participantIds->implode(',') . ']');

            if ($dryRun) {
                continue;
            }

            $participantIds->each(function ($userId) use ($rootId, &$created) {
                $prize = PostRelayPrize::firstOrCreate(
                    ['root_post_id' => (int) $rootId, 'user_id' => (int) $userId],
                    ['prize' => 0, 'try_flag' => 0],
                );

                if ($prize->wasRecentlyCreated) {
                    $created++;
                }
            });
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "threshold={$min}, qualifying chains={$qualifying}, prize rows created={$created}");

        return self::SUCCESS;
    }

    /**
     * Walk the accepted-post chain back to its root, collecting distinct poster user ids.
     *
     * @return array{0:int,1:int[]}
     */
    private function walkBack(PostRecord $post): array
    {
        $ids = [];
        $visited = [];
        $rootId = (int) $post->id;
        $current = $post->loadMissing('user');

        while ($current && ! in_array((int) $current->id, $visited, true)) {
            $visited[] = (int) $current->id;
            $rootId = (int) $current->id;

            if ($current->user && ! in_array((int) $current->user->id, PostRelay::EXCLUDED_USER_IDS, true)) {
                $ids[] = (int) $current->user->id;
            }

            $incoming = PostRelay::where('relay_type', PostRelay::TYPE_NICE)
                ->where('accepted_post_id', $current->id)
                ->orderBy('assigned_at')
                ->orderBy('id')
                ->first();

            if (! $incoming || ! $incoming->source_post_id) {
                break;
            }

            $current = PostRecord::with('user')->find($incoming->source_post_id);
        }

        return [$rootId, array_values(array_unique($ids))];
    }
}
