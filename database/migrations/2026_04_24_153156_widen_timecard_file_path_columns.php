<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE timecard_cost_records MODIFY file_path VARCHAR(255) NULL');

        Schema::create('timecard_receipt_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timecard_record_id')->nullable()->constrained('timecard_records')->nullOnDelete();
            $table->integer('timecard_cost_record_id')->nullable();
            $table->uuid('draft_uuid')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->string('extension', 16)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64)->index();
            $table->string('canonical_path', 255)->index();
            $table->string('preview_path', 255)->nullable();
            $table->string('source_type', 32)->default('paper_scan')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('uploaded_at')->index();
            $table->timestamp('finalized_at')->nullable()->index();
            $table->integer('scan_dpi')->nullable();
            $table->integer('scan_color_depth')->nullable();
            $table->string('scan_color_mode', 32)->nullable();
            $table->string('document_size', 32)->nullable();
            $table->integer('image_width_px')->nullable();
            $table->integer('image_height_px')->nullable();
            $table->boolean('is_deleted')->default(false)->index();
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('timecard_cost_record_id')->references('id')->on('timecard_cost_records')->nullOnDelete();
            $table->index(['user_id', 'uploaded_at']);
            $table->index(['status', 'uploaded_at']);
        });

        Schema::create('audit_daily_digests', function (Blueprint $table) {
            $table->id();
            $table->date('digest_date')->unique();
            $table->string('first_event_hash', 64);
            $table->string('last_event_hash', 64);
            $table->integer('event_count');
            $table->string('digest_hash', 64);
            $table->timestamp('sealed_at');
            $table->timestamps();
        });

        Schema::table('timecard_cost_records', function (Blueprint $table) {
            $table->foreignId('receipt_file_id')->nullable()->after('file_path')->constrained('timecard_receipt_files')->nullOnDelete();
            $table->integer('scan_dpi')->nullable()->after('file_uploaded_at');
            $table->integer('scan_color_depth')->nullable()->after('scan_dpi');
            $table->string('scan_color_mode', 32)->nullable()->after('scan_color_depth');
            $table->string('document_size', 32)->nullable()->after('scan_color_mode');
            $table->integer('image_width_px')->nullable()->after('document_size');
            $table->integer('image_height_px')->nullable()->after('image_width_px');
            $table->index('receipt_file_id');
        });

        Schema::table('timecard_audit_events', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('metadata');
            $table->string('previous_event_hash', 64)->nullable()->after('payload_hash');
            $table->string('event_hash', 64)->nullable()->after('previous_event_hash');
            $table->index('event_hash');
        });

        Schema::table('timecard_audit_event_projections', function (Blueprint $table) {
            $table->foreignId('receipt_file_id')->nullable()->after('file_path')->constrained('timecard_receipt_files')->nullOnDelete();
            $table->string('file_sha256', 64)->nullable()->after('receipt_file_id');
            $table->string('internal_control_status', 32)->nullable()->after('file_sha256');
            $table->index('receipt_file_id');
            $table->index('internal_control_status');
        });
    }

    public function down(): void
    {
        // Do not shrink this column back. Existing nested paths may exceed the old length.
        Schema::table('timecard_audit_event_projections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_file_id');
            $table->dropColumn(['file_sha256', 'internal_control_status']);
        });
        Schema::table('timecard_audit_events', function (Blueprint $table) {
            $table->dropColumn(['payload_hash', 'previous_event_hash', 'event_hash']);
        });
        Schema::table('timecard_cost_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_file_id');
            $table->dropColumn([
                'scan_dpi',
                'scan_color_depth',
                'scan_color_mode',
                'document_size',
                'image_width_px',
                'image_height_px',
            ]);
        });
        Schema::dropIfExists('audit_daily_digests');
        Schema::dropIfExists('timecard_receipt_files');
    }
};
