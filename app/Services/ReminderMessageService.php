<?php

namespace App\Services;
use App\Models\boardToUser;
use App\Models\messageRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReminderMessageService
{
    public const BUCKET_UNCHECKED = 'unchecked';
    public const BUCKET_UNSIGNED  = 'unsigned';
    public const BUCKET_REMINDED  = 'reminded';

    /**
     * @param array<string> $need  allowed: unchecked, unsigned, reminded, all
     */
    public function getReminderMessagesForUser(User $user, array $need = ['all']): array
    {
        $need = $this->normalizeNeed($need);

        // board filter as subquery (no pluck/toArray)
        $relatedBoardsSub = boardToUser::query()
            ->select('record_id')
            ->where('user_id', $user->id)
            ->where('deleted_status', 0);

        $base = messageRecord::query()
            ->where('deleted_flag', 0)
            ->whereIn('record_id', $relatedBoardsSub);

        $start = Carbon::parse('2023-03-13 00:00:00');

        // Only compute the buckets you actually need (each one is a DB query for IDs)
        $bucketIds = [
            self::BUCKET_UNCHECKED => collect(),
            self::BUCKET_UNSIGNED  => collect(),
            self::BUCKET_REMINDED  => collect(),
        ];

        if (in_array(self::BUCKET_UNCHECKED, $need, true)) {
            $bucketIds[self::BUCKET_UNCHECKED] = (clone $base)
                ->where('check_flag', 1)
                ->where('check_request_at', '>', $start)
                ->whereHas('checkUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('checked', 0);
                })
                ->pluck('id');
        }

        if (in_array(self::BUCKET_UNSIGNED, $need, true)) {
            $bucketIds[self::BUCKET_UNSIGNED] = (clone $base)
                ->whereHas('message_files', function ($q) use ($user) {
                    $q->where('sign_flag', 1)
                      ->whereHas('unsignedUsers', function ($uq) use ($user) {
                          $uq->where('user_id', $user->id)->where('cancel_flag', 0);
                      });
                })
                ->pluck('id');
        }

        if (in_array(self::BUCKET_REMINDED, $need, true)) {
            $bucketIds[self::BUCKET_REMINDED] = (clone $base)
                ->whereHas('messageRemindUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('reminded', 1);
                })
                ->pluck('id');
        }

        // Union only the IDs we asked for
        $allIds = collect()
            ->merge($bucketIds[self::BUCKET_UNCHECKED])
            ->merge($bucketIds[self::BUCKET_UNSIGNED])
            ->merge($bucketIds[self::BUCKET_REMINDED])
            ->unique()
            ->values();

        // If nothing needed or no hits, return empty collections for requested buckets
        if ($allIds->isEmpty()) {
            return $this->formatEmpty($need);
        }

        // Eager-load ONCE for only the needed IDs
        $messagesById = messageRecord::query()
            ->with($this->withRelations())
            ->where('deleted_flag', 0)
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        $mapIdsToModels = function (Collection $ids) use ($messagesById): Collection {
            return $ids->map(fn ($id) => $messagesById->get($id))->filter()->values();
        };

        // Return only what was requested (or all if asked)
        $out = [];
        if (in_array(self::BUCKET_UNCHECKED, $need, true)) {
            $out['remind_unchecked_messages'] = $mapIdsToModels($bucketIds[self::BUCKET_UNCHECKED]);
        }
        if (in_array(self::BUCKET_UNSIGNED, $need, true)) {
            $out['remind_unsigned_messages']  = $mapIdsToModels($bucketIds[self::BUCKET_UNSIGNED]);
        }
        if (in_array(self::BUCKET_REMINDED, $need, true)) {
            $out['remind_reminded_messages']  = $mapIdsToModels($bucketIds[self::BUCKET_REMINDED]);
        }

        return $out;
    }

    private function withRelations(): array
    {
        // You can also make this conditional by bucket, see note below.
        return [
            'user',
            'actual_sender',
            'message_files.unsignedUsers',
            'message_files.signedUsers',
            'message_reply',
            'message_quot',
            'message_forward',
            'reactedUsers',
            'checkedUsers',
            'uncheckedUsers',
            'emotedUsers',
            'messageRemindUsers',
            'task',
        ];
    }

    private function normalizeNeed(array $need): array
    {
        $need = array_values(array_unique($need));

        if (in_array('all', $need, true)) {
            return [self::BUCKET_UNCHECKED, self::BUCKET_UNSIGNED, self::BUCKET_REMINDED];
        }

        $allowed = [self::BUCKET_UNCHECKED, self::BUCKET_UNSIGNED, self::BUCKET_REMINDED];
        return array_values(array_intersect($need, $allowed));
    }

    private function formatEmpty(array $need): array
    {
        $out = [];
        if (in_array(self::BUCKET_UNCHECKED, $need, true)) $out['remind_unchecked_messages'] = collect();
        if (in_array(self::BUCKET_UNSIGNED, $need, true))  $out['remind_unsigned_messages']  = collect();
        if (in_array(self::BUCKET_REMINDED, $need, true))  $out['remind_reminded_messages']  = collect();
        return $out;
    }
}
