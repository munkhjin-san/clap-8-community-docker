<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildCalendarFavourites extends Command
{
    protected $signature = 'calendar:rebuild-favourites
        {--dry-run : Report what would be written without touching the table}
        {--months=12 : Only consider records started within this many months}
        {--max-attendees=10 : Skip events with more attendees than this}';

    protected $description = 'Rebuild the "who do you usually schedule with" ranking used to order the calendar member list.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $months = max(1, (int) $this->option('months'));
        $maxAttendees = max(2, (int) $this->option('max-attendees'));

        $started = microtime(true);
        $pairs = $this->collectPairs($months, $maxAttendees);

        $this->line(sprintf(
            'window=%dmo  max_attendees=%d  pairs=%d  rows=%d  [%.2fs]',
            $months, $maxAttendees, count($pairs), count($pairs) * 2, microtime(true) - $started
        ));

        if ($dryRun) {
            $this->info('dry run: nothing written.');
            $this->preview($pairs);
            return self::SUCCESS;
        }

        // 初回の一括作成用。以降は予定作成時の加算で更新されるので、
        // このコマンドは必要になったとき（窓を引き直したいとき）に手動で流す。
        $rows = [];
        $now = now();
        foreach ($pairs as $pair) {
            $rows[] = [
                'owner_id' => (int) $pair->u1, 'member_id' => (int) $pair->u2,
                'score' => (float) $pair->score, 'shared_count' => (int) $pair->shared_count,
                'last_together_at' => $pair->last_together_at,
                'created_at' => $now, 'updated_at' => $now,
            ];
            $rows[] = [
                'owner_id' => (int) $pair->u2, 'member_id' => (int) $pair->u1,
                'score' => (float) $pair->score, 'shared_count' => (int) $pair->shared_count,
                'last_together_at' => $pair->last_together_at,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows) {
            DB::table('calendar_favourite_users')->delete();
            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('calendar_favourite_users')->insert($chunk);
            }
        });

        $this->info(sprintf('wrote %d rows.', count($rows)));

        return self::SUCCESS;
    }

    /**
     * 予定の共同参加からペアを集計する。
     * - 繰り返し予定は r_group_id で1イベントに畳む（毎週の定例が回数分効いてしまうのを防ぐ）
     * - 参加者2〜max人のイベントだけ見る（全社会議は誰とでも同席するので参考にならない）
     * - 1イベントあたりの重みは 1/(参加者数-1)。1対1が最も強い
     */
    private function collectPairs(int $months, int $maxAttendees): array
    {
        return DB::select('
            WITH ev AS (
                SELECT
                    COALESCE(NULLIF(r.r_group_id, ""), CONCAT("rec-", r.id)) AS event_key,
                    cu.user_id,
                    MAX(r.date_start) AS last_at
                FROM calendar_records r
                JOIN calendar_users cu
                    ON cu.record_id = r.id AND cu.deleted_at IS NULL AND cu.deleted_flag = 0
                JOIN users u
                    ON u.id = cu.user_id AND u.retire = 0 AND u.deleted_flag = 0
                WHERE r.deleted_at IS NULL
                  AND r.deleted_flag = 0
                  AND r.date_start <= NOW()
                  AND r.date_start >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY event_key, cu.user_id
            ),
            sz AS (
                SELECT event_key, COUNT(*) AS n, MAX(last_at) AS last_at
                FROM ev
                GROUP BY event_key
                HAVING n BETWEEN 2 AND ?
            )
            SELECT
                a.user_id AS u1,
                b.user_id AS u2,
                SUM(1.0 / (sz.n - 1)) AS score,
                COUNT(*) AS shared_count,
                MAX(sz.last_at) AS last_together_at
            FROM ev a
            JOIN sz ON sz.event_key = a.event_key
            JOIN ev b ON b.event_key = a.event_key AND b.user_id > a.user_id
            GROUP BY u1, u2
        ', [$months, $maxAttendees]);
    }

    private function preview(array $pairs): void
    {
        usort($pairs, fn($a, $b) => $b->score <=> $a->score);
        $top = array_slice($pairs, 0, 10);
        if (!$top) {
            return;
        }

        $names = DB::table('users')
            ->whereIn('id', array_merge(array_column($top, 'u1'), array_column($top, 'u2')))
            ->pluck('name', 'id');

        $this->table(['owner', 'member', 'score', 'events', 'last together'], array_map(fn($p) => [
            $names[$p->u1] ?? $p->u1,
            $names[$p->u2] ?? $p->u2,
            number_format((float) $p->score, 1),
            $p->shared_count,
            substr((string) $p->last_together_at, 0, 10),
        ], $top));
    }
}
