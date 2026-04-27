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
        Schema::table('refresh_annual_reviews', function (Blueprint $table) {
            $table->timestamp('leave_review_confirmed_at')->nullable()->after('leave_status');
            $table->foreignId('leave_review_confirmed_by_user_id')
                ->nullable()
                ->after('leave_review_confirmed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refresh_annual_reviews', function (Blueprint $table) {
            $table->dropForeign(['leave_review_confirmed_by_user_id']);
            $table->dropColumn(['leave_review_confirmed_at', 'leave_review_confirmed_by_user_id']);
        });
    }
};
