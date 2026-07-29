<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_meeting_transcript_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_meeting_transcript_id')
                ->unique('cmts_transcript_unique');
            $table->foreign('calendar_meeting_transcript_id', 'cmts_transcript_fk')
                ->references('id')
                ->on('calendar_meeting_transcripts')
                ->cascadeOnDelete();
            $table->foreignId('requested_by')
                ->nullable();
            $table->foreign('requested_by', 'cmts_requested_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->json('content')->nullable();
            $table->string('model', 100)->nullable();
            $table->string('prompt_version', 32);
            $table->string('transcript_hash', 64);
            $table->unsignedInteger('generation')->default(1);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_meeting_transcript_summaries');
    }
};
