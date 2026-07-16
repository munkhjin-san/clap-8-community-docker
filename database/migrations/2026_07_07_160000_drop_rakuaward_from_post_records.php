<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Rakuaward nominations are now identified solely by app_type = 7, so the
    // redundant boolean flag is no longer needed.
    public function up(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->dropColumn('rakuaward');
        });
    }

    public function down(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->boolean('rakuaward')->default(false);
        });
    }
};
