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
        Schema::create('timecard_audit_event_projections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timecard_audit_event_id')->unique();
            $table->unsignedBigInteger('timecard_record_id')->nullable()->index();
            $table->integer('timecard_cost_record_id')->nullable()->index();
            $table->uuid('draft_uuid')->nullable()->index();
            $table->string('target_type');
            $table->string('event_type')->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->unsignedBigInteger('subject_user_id')->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->date('timecard_day')->nullable()->index();
            $table->integer('approval_state')->nullable()->index();
            $table->string('merchant_name')->nullable()->index();
            $table->date('receipt_date')->nullable()->index();
            $table->decimal('expenses', 12, 2)->nullable()->index();
            $table->string('currency', 16)->nullable();
            $table->string('department')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('ocr_run_id')->nullable()->index();
            $table->timestamps();

            $table->index(['subject_user_id', 'occurred_at'], 'idx_subject_user_occurred');
            $table->index(['event_type', 'occurred_at'], 'idx_event_type_occurred');
            $table->index(['merchant_name', 'occurred_at'], 'idx_merchant_name_occurred');
            $table->index(['approval_state', 'occurred_at'], 'idx_approval_state_occurred');
            $table->index(['timecard_day', 'occurred_at'], 'idx_timecard_day_occurred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timecard_audit_event_projections');
    }
};
