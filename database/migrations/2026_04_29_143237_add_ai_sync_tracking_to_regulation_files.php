<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('regulation_files', 'ai_sync_status')) {
            Schema::table('regulation_files', function (Blueprint $table) {
                $table->string('ai_sync_status')->default('not_synced')->after('chat_supported');
            });
        }

        if (! Schema::hasColumn('regulation_files', 'ai_sync_error')) {
            Schema::table('regulation_files', function (Blueprint $table) {
                $table->text('ai_sync_error')->nullable()->after('ai_sync_status');
            });
        }

        if (! Schema::hasColumn('regulation_files', 'ai_synced_at')) {
            Schema::table('regulation_files', function (Blueprint $table) {
                $table->timestamp('ai_synced_at')->nullable()->after('ai_sync_error');
            });
        }

        if (! Schema::hasColumn('regulation_files', 'ai_sync_hash')) {
            Schema::table('regulation_files', function (Blueprint $table) {
                $table->string('ai_sync_hash', 64)->nullable()->after('ai_synced_at');
            });
        }

        if (! Schema::hasTable('regulation_file_vector_pages')) {
            Schema::create('regulation_file_vector_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('regulation_file_id')->constrained('regulation_files')->cascadeOnDelete();
                $table->unsignedInteger('page_number');
                $table->string('markdown_path');
                $table->string('markdown_copy_path')->nullable();
                $table->string('openai_file_id')->nullable();
                $table->string('vector_store_file_id')->nullable();
                $table->timestamps();

                $table->index(['regulation_file_id', 'page_number'], 'rfvp_file_page_idx');
                $table->index('openai_file_id', 'rfvp_openai_idx');
                $table->index('vector_store_file_id', 'rfvp_vs_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('regulation_file_vector_pages');

        $columns = array_values(array_filter([
            Schema::hasColumn('regulation_files', 'ai_sync_status') ? 'ai_sync_status' : null,
            Schema::hasColumn('regulation_files', 'ai_sync_error') ? 'ai_sync_error' : null,
            Schema::hasColumn('regulation_files', 'ai_synced_at') ? 'ai_synced_at' : null,
            Schema::hasColumn('regulation_files', 'ai_sync_hash') ? 'ai_sync_hash' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('regulation_files', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
