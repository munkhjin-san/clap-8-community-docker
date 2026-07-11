<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_statuses', function (Blueprint $table) {
            // Canvas coordinates for the status-flow graph editor (null = auto-layout).
            $table->integer('ui_x')->nullable()->after('order_number');
            $table->integer('ui_y')->nullable()->after('ui_x');
        });
    }

    public function down(): void
    {
        Schema::table('flow_statuses', function (Blueprint $table) {
            $table->dropColumn(['ui_x', 'ui_y']);
        });
    }
};
