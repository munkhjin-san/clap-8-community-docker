<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_definitions', 'color_id')) {
                $table->unsignedTinyInteger('color_id')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            if (Schema::hasColumn('flow_definitions', 'color_id')) {
                $table->dropColumn('color_id');
            }
        });
    }
};
