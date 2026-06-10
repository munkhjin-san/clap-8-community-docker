<?php

use App\Models\CommentRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('comment_records', 'status_to')) {
            Schema::table('comment_records', function (Blueprint $table) {
                $table->unsignedTinyInteger('status_to')->nullable()->after('progress_checkpoint');
            });
        }

        if (
            !Schema::hasTable('post_use_files') ||
            !Schema::hasColumn('post_use_files', 'progress') ||
            !Schema::hasTable('file_attachments')
        ) {
            return;
        }

        DB::table('post_use_files')
            ->join('comment_records', 'comment_records.id', '=', 'post_use_files.record_id')
            ->select('post_use_files.record_id', 'post_use_files.file_id')
            ->where('post_use_files.progress', 1)
            ->distinct()
            ->orderBy('post_use_files.record_id')
            ->chunk(500, function ($rows) {
                $now = now();
                $payload = $rows->map(fn ($row) => [
                    'file_id' => $row->file_id,
                    'attachable_type' => CommentRecord::class,
                    'attachable_id' => $row->record_id,
                    'collection' => 'progress_files',
                    'created_at' => $now,
                ])->all();

                if (count($payload)) {
                    DB::table('file_attachments')->insertOrIgnore($payload);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('file_attachments')) {
            DB::table('file_attachments')
                ->where('attachable_type', CommentRecord::class)
                ->where('collection', 'progress_files')
                ->delete();
        }

        if (Schema::hasColumn('comment_records', 'status_to')) {
            Schema::table('comment_records', function (Blueprint $table) {
                $table->dropColumn('status_to');
            });
        }
    }
};
