<?php

namespace App\Services;

use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\boardToUser;
use App\Services\MentionAndNotify;
use App\Jobs\IncrementUnreadCount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DraftMessageSender
{
    public function __construct(private MentionAndNotify $mentioner)
    {
    }  
    /**
     * Convert a draft message into a normal message by cloning,
     * moving files, notifying, and updating last_message.
     *
     * Returns the new messageRecord.
     */
    public function send(messageRecord $draft): array
    {
        // Basic guards
        if ((int)$draft->draft_flag !== 1) {
            // Already not a draft. Treat as idempotent no-op.
            return $draft;
        }
        // Load relationships once
        $draft->loadMissing(['message_files', 'user', 'board_record']);

        // We'll collect file moves and run them after the DB transaction commits.
        // This avoids "DB committed but file move failed" or vice versa.
        $moves = [];

        $new = DB::transaction(function () use ($draft, &$moves) {
            // Mark this draft as processed (idempotency) as early as possible
            // so another worker won’t process it too.
            $fresh = messageRecord::where('id', $draft->id)
                ->lockForUpdate()
                ->first();

            if (!$fresh) {
                // Someone else processed it
                return $draft;
            }

            // Replicate draft to create actual message
            $new = $fresh->replicate();
            $new->reserved_at = null;
            $new->draft_flag  = 0;
            $new->created_at  = now();
            $new->updated_at  = now();
            $new->save();

            // Soft-delete + flag old draft
            $fresh->deleted_flag = 1;
            $fresh->save();
            $fresh->delete();

            // Move/copy files: create new file rows and prepare disk moves
            $path_shared_files = 'shared_files/' . $new->record_id;

            foreach ($fresh->message_files as $file) {
                $newFile = new messageFile;
                $newFile->board_id    = $new->record_id;
                $newFile->message_id  = $new->id;
                $newFile->name        = $file->name;
                $newFile->extension   = $file->extension;
                $newFile->user_id     = $file->user_id;
                $newFile->mime_type   = $file->mime_type;
                $newFile->size        = $file->size;
                $newFile->save();

                $origin_path = 'shared_files/' . $file->board_id . '/' . $file->id . '_' . $file->user_id . '_' . $file->message_id . '.' . $file->extension;
                $new_path    = $path_shared_files . '/' . $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;

                $moves[] = [$origin_path, $new_path];
            }

            // Remove old file rows (soft delete if you use it)
            $fresh->message_files()->delete();

            // Update last_message
            boardToUser::where('record_id', $new->record_id)
                ->where('user_id', $new->user_id)
                ->update(["last_message" => $new->id]);
            IncrementUnreadCount::dispatch($new->record_id, $new->user_id);
            $new->board_record->touch();
            $this->mentioner->mention($new->board_record, $new->user, $new);
            
            $related_members = boardToUser::where('record_id', $new->record_id)
                ->where('deleted_status', 0)
                ->where('user_id', '!=', $new->user_id)
                ->pluck('user_id');
            
            return [
                'success' => true,
                'new' => $new,
                'record_id' => $new->record_id,
                'sender_id' => $new->user_id,
                'related_members' => $related_members,
            ];
        });

        // Perform file moves after commit (outside transaction)
        // If file move fails, log hard. You can retry later because DB is consistent.
        foreach ($moves as [$from, $to]) {
            try {
                if (!Storage::disk('local')->exists($from)) {
                    Log::warning('DraftSend file missing, skipping move', ['from' => $from, 'to' => $to]);
                    continue;
                }

                Storage::disk('local')->makeDirectory(dirname($to));
                Storage::disk('local')->move($from, $to);
            } catch (Throwable $e) {
                Log::error('DraftSend file move failed', [
                    'from' => $from,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
                // Option: throw to fail the job and retry
                // throw $e;
            }
        }

        return $new;
    }
}
