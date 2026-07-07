<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            // When an admin grants a rakuaward nomination's charged total to the nominee's refresh.
            $table->timestamp('rakuaward_granted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->dropColumn('rakuaward_granted_at');
        });
    }
};
