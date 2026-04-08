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
            $table->string('grant_type', 30)->nullable()->after('grant_year');
            $table->date('grant_date')->nullable()->after('grant_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refresh_annual_reviews', function (Blueprint $table) {
            $table->dropColumn(['grant_type', 'grant_date']);
        });
    }
};
