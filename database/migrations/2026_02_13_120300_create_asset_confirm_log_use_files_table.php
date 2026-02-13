<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('asset_confirm_log_use_files')) {
            return;
        }

        Schema::create('asset_confirm_log_use_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_confirm_log_id')->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_confirm_log_id']);
            $table->index(['file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_confirm_log_use_files');
    }
};
