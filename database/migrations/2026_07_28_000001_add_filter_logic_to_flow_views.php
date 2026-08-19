<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a view's own filter conditions combine. Views used to be AND-only; the ad-hoc filter already
 * offered both, so this brings saved views in line with it.
 *
 * Defaults to 'and' so every existing view keeps the behaviour it was built with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_views', function (Blueprint $table) {
            $table->string('filter_logic', 3)->default('and')->after('filters');
        });
    }

    public function down(): void
    {
        Schema::table('flow_views', function (Blueprint $table) {
            $table->dropColumn('filter_logic');
        });
    }
};
