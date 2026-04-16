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
        Schema::table('timecard_audit_event_projections', function (Blueprint $table) {
            $table->index(['receipt_date', 'occurred_at'], 'idx_receipt_date_occurred');
            $table->index(['subject_user_id', 'receipt_date', 'occurred_at'], 'idx_subject_receipt_occurred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecard_audit_event_projections', function (Blueprint $table) {
            $table->dropIndex('idx_receipt_date_occurred');
            $table->dropIndex('idx_subject_receipt_occurred');
        });
    }
};
