<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            // When an unselected rakuaward nomination's charges are returned to its chargers.
            $table->timestamp('rakuaward_refunded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->dropColumn('rakuaward_refunded_at');
        });
    }
};
