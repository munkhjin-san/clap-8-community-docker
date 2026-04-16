<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timecard_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timecard_record_id')->nullable()->constrained('timecard_records')->nullOnDelete();
            $table->integer('timecard_cost_record_id')->nullable();
            $table->uuid('draft_uuid')->nullable();
            $table->string('target_type', 32);
            $table->string('event_type', 64);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('request_id')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            $table->foreign('timecard_cost_record_id')->references('id')->on('timecard_cost_records')->nullOnDelete();
            $table->index(['timecard_record_id', 'occurred_at']);
            $table->index(['timecard_cost_record_id', 'occurred_at']);
            $table->index(['subject_user_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index(['draft_uuid', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timecard_audit_events');
    }
};
