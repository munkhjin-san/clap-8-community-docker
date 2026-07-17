<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_statuses', function (Blueprint $table) {
            // Free-picked status color (hex). null → falls back to the neutral --bg3 pill.
            $table->string('color', 32)->nullable()->after('is_initial');
        });
    }

    public function down(): void
    {
        Schema::table('flow_statuses', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
