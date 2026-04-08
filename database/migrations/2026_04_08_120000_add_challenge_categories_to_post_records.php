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
        Schema::table('post_records', function (Blueprint $table) {
            $table->string('challenge_main_category', 100)->nullable()->after('donation_target');
            $table->string('challenge_sub_category', 100)->nullable()->after('challenge_main_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->dropColumn(['challenge_main_category', 'challenge_sub_category']);
        });
    }
};
