<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_relay_prizes', function (Blueprint $table) {
            // Why this GlowdNine play exists: relay | rakuaward | challenge_award.
            $table->string('source', 30)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('post_relay_prizes', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
