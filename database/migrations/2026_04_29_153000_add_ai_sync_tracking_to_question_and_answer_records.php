<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('question_and_answer_records', 'ai_sync_status')) {
            Schema::table('question_and_answer_records', function (Blueprint $table) {
                $table->string('ai_sync_status')->default('not_synced')->after('useful_count');
            });
        }

        if (! Schema::hasColumn('question_and_answer_records', 'ai_sync_error')) {
            Schema::table('question_and_answer_records', function (Blueprint $table) {
                $table->text('ai_sync_error')->nullable()->after('ai_sync_status');
            });
        }

        if (! Schema::hasColumn('question_and_answer_records', 'ai_synced_at')) {
            Schema::table('question_and_answer_records', function (Blueprint $table) {
                $table->timestamp('ai_synced_at')->nullable()->after('ai_sync_error');
            });
        }

        if (! Schema::hasColumn('question_and_answer_records', 'ai_sync_hash')) {
            Schema::table('question_and_answer_records', function (Blueprint $table) {
                $table->string('ai_sync_hash', 64)->nullable()->after('ai_synced_at');
            });
        }

        $this->addIndexIfMissing('question_and_answer_records', 'qar_deleted_created_idx', function (Blueprint $table) {
            $table->index(['deleted_flag', 'created_at'], 'qar_deleted_created_idx');
        });
        $this->addIndexIfMissing('question_and_answer_records', 'qar_ai_status_idx', function (Blueprint $table) {
            $table->index('ai_sync_status', 'qar_ai_status_idx');
        });
        $this->addIndexIfMissing('question_and_answer_records', 'qar_ai_hash_idx', function (Blueprint $table) {
            $table->index('ai_sync_hash', 'qar_ai_hash_idx');
        });

        if (! Schema::hasTable('question_and_answer_vector_documents')) {
            Schema::create('question_and_answer_vector_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('question_and_answer_record_id');
                $table->string('markdown_path');
                $table->string('markdown_copy_path')->nullable();
                $table->string('openai_file_id')->nullable();
                $table->string('vector_store_file_id')->nullable();
                $table->timestamps();

                $table->index('question_and_answer_record_id', 'qavd_record_idx');
                $table->index('openai_file_id', 'qavd_openai_idx');
                $table->index('vector_store_file_id', 'qavd_vs_idx');
            });
        } else {
            $this->addIndexIfMissing('question_and_answer_vector_documents', 'qavd_record_idx', function (Blueprint $table) {
                $table->index('question_and_answer_record_id', 'qavd_record_idx');
            });
            $this->addIndexIfMissing('question_and_answer_vector_documents', 'qavd_openai_idx', function (Blueprint $table) {
                $table->index('openai_file_id', 'qavd_openai_idx');
            });
            $this->addIndexIfMissing('question_and_answer_vector_documents', 'qavd_vs_idx', function (Blueprint $table) {
                $table->index('vector_store_file_id', 'qavd_vs_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_and_answer_vector_documents');

        $this->dropIndexIfExists('question_and_answer_records', 'qar_deleted_created_idx');
        $this->dropIndexIfExists('question_and_answer_records', 'qar_ai_status_idx');
        $this->dropIndexIfExists('question_and_answer_records', 'qar_ai_hash_idx');

        $columns = array_values(array_filter([
            Schema::hasColumn('question_and_answer_records', 'ai_sync_status') ? 'ai_sync_status' : null,
            Schema::hasColumn('question_and_answer_records', 'ai_sync_error') ? 'ai_sync_error' : null,
            Schema::hasColumn('question_and_answer_records', 'ai_synced_at') ? 'ai_synced_at' : null,
            Schema::hasColumn('question_and_answer_records', 'ai_sync_hash') ? 'ai_sync_hash' : null,
        ]));

        if ($columns !== []) {
            Schema::table('question_and_answer_records', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    private function addIndexIfMissing(string $table, string $indexName, \Closure $callback): void
    {
        if (Schema::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, $callback);
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};
