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
            $table->unsignedSmallInteger('alert_streak')->default(0); // or wherever
            $table->string('last_alert_goal_month', 7)->nullable()->after('alert_streak'); // "YYYY-MM"
            $table->index(['user_id', 'year', 'which_half']);
            $table->index(['last_alert_goal_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_records', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'year', 'which_half']);
            $table->dropIndex(['last_alert_goal_month']);
            $table->dropColumn(['alert_streak', 'last_alert_goal_month']);
        });
    }
};
