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
        Schema::table('message_records', function (Blueprint $table) {
            $table->timestamp('check_request_deadline')->after('check_request_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_records', function (Blueprint $table) {
            $table->dropColumn('check_request_deadline');
        });
    }
};
