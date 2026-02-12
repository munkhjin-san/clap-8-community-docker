<?php

namespace App\Services;
use App\Models\boardToUser;
use App\Models\messageRecord;
use App\Models\taskRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RemindTaskService
{
    public const BUCKET_UNTOUCHED = 'untouchedTasks';
    public const BUCKET_UNFINISHED  = 'unfinishedTasks';
    public const BUCKET_NOT_APPROVED  = 'pendingApprovalTasks';

    /**
     * @param array<string> $need  allowed: untouched, unfinished, not_approved, all
     */
    public function getReminderTaskForUser(User $user, array $need = ['all']): array
    {
        $need = $this->normalizeNeed($need);

        $base = taskRecord::query();

        // Only compute the buckets you actually need (each one is a DB query for IDs)
        $bucketIds = [
            self::BUCKET_UNTOUCHED => collect(),
            self::BUCKET_UNFINISHED  => collect(),
            self::BUCKET_NOT_APPROVED  => collect(),
        ];

        if (in_array(self::BUCKET_UNTOUCHED, $need, true)) {
            $bucketIds[self::BUCKET_UNTOUCHED] = (clone $base)
                ->whereHas('executors', fn($q) => $q->where('users.id', $user->id)->where('progress_flag', 0))
                ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL TIMESTAMPDIFF(SECOND, created_at, CONCAT(end_at, " 23:59:59")) / 2 SECOND)')
                ->pluck('id');
        }

        if (in_array(self::BUCKET_UNFINISHED, $need, true)) {
            $bucketIds[self::BUCKET_UNFINISHED] = (clone $base)
                ->whereHas('executors', fn($q) => $q->where('users.id', $user->id)->where('progress_flag', 1))
                 ->whereRaw('NOW() > DATE_ADD(created_at, INTERVAL TIMESTAMPDIFF(SECOND, created_at, CONCAT(end_at, " 23:59:59")) * 0.8 SECOND)')
                ->pluck('id');
        }

        if (in_array(self::BUCKET_NOT_APPROVED, $need, true)) {
            $bucketIds[self::BUCKET_NOT_APPROVED] = (clone $base)
                ->where('comp_flag', 0)
                ->whereHas('supervisors', fn($q) => $q->where('users.id', $user->id)->where('supervisor', 1))                
                ->whereHas('executors', fn ($q) => $q->where('status_flag', 1))
                ->pluck('id');

        }

        // Union only the IDs we asked for
        $allIds = collect()
            ->merge($bucketIds[self::BUCKET_UNTOUCHED])
            ->merge($bucketIds[self::BUCKET_UNFINISHED])
            ->merge($bucketIds[self::BUCKET_NOT_APPROVED])
            ->unique()
            ->values();

        // If nothing needed or no hits, return empty collections for requested buckets
        if ($allIds->isEmpty()) {
            return $this->formatEmpty($need);
        }

        // Eager-load ONCE for only the needed IDs
        $tasksById = taskRecord::query()
            ->with($this->withRelations())
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        $mapIdsToModels = function (Collection $ids) use ($tasksById): Collection {
            return $ids->map(fn ($id) => $tasksById->get($id))->filter()->values();
        };

        // Return only what was requested (or all if asked)
        $out = [];
        if (in_array(self::BUCKET_UNTOUCHED, $need, true)) {
            $out['untouchedTasks'] = $mapIdsToModels($bucketIds[self::BUCKET_UNTOUCHED]);
        }
        if (in_array(self::BUCKET_UNFINISHED, $need, true)) {
            $out['unfinishedTasks']  = $mapIdsToModels($bucketIds[self::BUCKET_UNFINISHED]);
        }
        if (in_array(self::BUCKET_NOT_APPROVED, $need, true)) {
            $out['pendingApprovalTasks']  = $mapIdsToModels($bucketIds[self::BUCKET_NOT_APPROVED]);
        }

        return $out;
    }

    private function withRelations(): array
    {
        // You can also make this conditional by bucket, see note below.
        return [
            'executors', 
            'files', 
            'supervisors', 
            'project', 
            'board.board_to_users.user'
        ];
    }

    private function normalizeNeed(array $need): array
    {
        $need = array_values(array_unique($need));

        if (in_array('all', $need, true)) {
            return [self::BUCKET_UNTOUCHED, self::BUCKET_UNFINISHED, self::BUCKET_NOT_APPROVED];
        }

        $allowed = [self::BUCKET_UNTOUCHED, self::BUCKET_UNFINISHED, self::BUCKET_NOT_APPROVED];
        return array_values(array_intersect($need, $allowed));
    }

    private function formatEmpty(array $need): array
    {
        $out = [];
        if (in_array(self::BUCKET_UNTOUCHED, $need, true)) $out['untouchedTasks'] = collect();
        if (in_array(self::BUCKET_UNFINISHED, $need, true))  $out['unfinishedTasks']  = collect();
        if (in_array(self::BUCKET_NOT_APPROVED, $need, true))  $out['pendingApprovalTasks']  = collect();
        return $out;
    }
}
