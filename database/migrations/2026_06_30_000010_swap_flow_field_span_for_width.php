<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_fields', 'width')) {
                $table->unsignedInteger('width')->default(260)->after('layout_row');
            }
            if (Schema::hasColumn('flow_fields', 'layout_span')) {
                $table->dropColumn('layout_span');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_fields', 'layout_span')) {
                $table->unsignedInteger('layout_span')->default(12)->after('layout_row');
            }
            if (Schema::hasColumn('flow_fields', 'width')) {
                $table->dropColumn('width');
            }
        });
    }
};
