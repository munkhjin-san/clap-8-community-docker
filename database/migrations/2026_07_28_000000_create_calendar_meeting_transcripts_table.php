<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_meeting_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_record_id')
                ->nullable()
                ->constrained('calendar_records')
                ->nullOnDelete();
            $table->foreignId('zoom_account_id')
                ->nullable()
                ->constrained('zoom_accounts')
                ->nullOnDelete();
            $table->string('meeting_id', 180);
            $table->string('meeting_uuid', 255);
            $table->string('file_id', 255);
            $table->string('attach_type', 64)->default('durable_transcript');
            $table->timestamp('meeting_start_time')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('storage_path')->nullable();
            $table->unsignedSmallInteger('download_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->unique(['zoom_account_id', 'file_id'], 'zoom_transcript_account_file_unique');
            $table->index(['meeting_id', 'meeting_start_time'], 'zoom_transcript_meeting_start_index');
            $table->index(['calendar_record_id', 'status'], 'zoom_transcript_calendar_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_meeting_transcripts');
    }
};
