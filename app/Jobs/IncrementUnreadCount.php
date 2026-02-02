<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Models\boardToUser;
class IncrementUnreadCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $record_id, public int $auth_user_id)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $members = boardToUser::where('record_id', $this->record_id)
        ->where('deleted_status', 0)
        ->where('user_id', '!=', $this->auth_user_id)
        ->whereNotNull('unread_count')
        ->where(function ($query) {
            $query->whereHas('user', function ($q) {
                $q->where('on_leave', 0);
            })
            ->orWhereHas('board', function ($q) {
                $q->where('private_flag', 1);
            });
        })->get();

        $members->each(function ($member) {
            $member->increment('unread_count');
            Cache::store('redis')->put("must_sync_{$member->user_id}", true);
        });
    }
}
