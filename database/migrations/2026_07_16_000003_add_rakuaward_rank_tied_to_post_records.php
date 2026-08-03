<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            // True when another nomination shares the same rank (same total score).
            $table->boolean('rakuaward_rank_tied')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->dropColumn('rakuaward_rank_tied');
        });
    }
};
