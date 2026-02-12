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
        Schema::table('evaluation_records', function (Blueprint $table) {
            $table->string('last_processed_goal_month', 7)->nullable()->after('last_alert_goal_month'); // "YYYY-MM"
            $table->index(['last_processed_goal_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_records', function (Blueprint $table) {
            $table->dropColumn(['last_processed_goal_month']);
        });
    }
};
