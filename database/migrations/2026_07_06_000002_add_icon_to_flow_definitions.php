<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_definitions', 'icon_svg')) {
                $table->text('icon_svg')->nullable()->after('color_id');
            }
            if (!Schema::hasColumn('flow_definitions', 'icon_image')) {
                $table->longText('icon_image')->nullable()->after('icon_svg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            foreach (['icon_svg', 'icon_image'] as $col) {
                if (Schema::hasColumn('flow_definitions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
