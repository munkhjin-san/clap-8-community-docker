<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('asset_confirm_logs')) {
            Schema::create('asset_confirm_logs', function (Blueprint $table) {
                $table->id();
                $table->integer('asset_record_id');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('external_user')->nullable();
                $table->string('memo')->nullable();
                $table->timestamps();

                $table->index(['asset_record_id', 'created_at']);
                $table->foreign('asset_record_id')
                    ->references('id')
                    ->on('asset_records')
                    ->cascadeOnDelete();
            });

            return;
        }

        // If the table already exists (e.g. created manually), ensure it matches the expected schema.
        if (Schema::hasColumn('asset_confirm_logs', 'asset_record_id')) {
            DB::statement('ALTER TABLE asset_confirm_logs MODIFY asset_record_id INT NOT NULL');
        } else {
            Schema::table('asset_confirm_logs', function (Blueprint $table) {
                $table->integer('asset_record_id');
            });
        }

        if (! Schema::hasColumn('asset_confirm_logs', 'user_id')) {
            Schema::table('asset_confirm_logs', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('asset_confirm_logs', 'external_user')) {
            Schema::table('asset_confirm_logs', function (Blueprint $table) {
                $table->string('external_user')->nullable();
            });
        }

        if (! Schema::hasColumn('asset_confirm_logs', 'memo')) {
            Schema::table('asset_confirm_logs', function (Blueprint $table) {
                $table->string('memo')->nullable();
            });
        }

        if (! Schema::hasColumn('asset_confirm_logs', 'created_at')) {
            Schema::table('asset_confirm_logs', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        $assetFkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'asset_confirm_logs')
            ->where('CONSTRAINT_NAME', 'asset_confirm_logs_asset_record_id_foreign')
            ->exists();

        if (! $assetFkExists) {
            Schema::table('asset_confirm_logs', function (Blueprint $table) {
                $table->foreign('asset_record_id', 'asset_confirm_logs_asset_record_id_foreign')
                    ->references('id')
                    ->on('asset_records')
                    ->cascadeOnDelete();
            });
        }

        $assetCreatedAtIndexExists = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'asset_confirm_logs')
            ->where('INDEX_NAME', 'asset_confirm_logs_asset_record_id_created_at_index')
            ->exists();

        if (! $assetCreatedAtIndexExists) {
            Schema::table('asset_confirm_logs', function (Blueprint $table) {
                $table->index(['asset_record_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_confirm_logs');
    }
};
