<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timecard_cost_ocr_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timecard_record_id')->nullable()->constrained('timecard_records')->nullOnDelete();
            $table->integer('timecard_cost_record_id')->nullable();
            $table->uuid('draft_uuid');
            $table->string('source_file_path', 255);
            $table->string('source_file_sha256', 64);
            $table->string('provider', 32)->default('gemini');
            $table->string('model', 128);
            $table->string('status', 32);
            $table->json('normalized_result')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->foreign('timecard_cost_record_id')->references('id')->on('timecard_cost_records')->nullOnDelete();
            $table->index(['timecard_cost_record_id', 'created_at']);
            $table->index(['draft_uuid', 'created_at']);
            $table->index(['source_file_sha256']);
            $table->index(['executed_by_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timecard_cost_ocr_runs');
    }
};
