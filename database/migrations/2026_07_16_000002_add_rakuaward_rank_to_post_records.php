<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            // Final MVP rank (1-5) assigned at settlement for a rakuaward nomination.
            $table->tinyInteger('rakuaward_rank')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->dropColumn('rakuaward_rank');
        });
    }
};
