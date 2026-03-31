<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_form_blocks', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_form_blocks', 'categories')) {
                $table->json('categories')->nullable()->after('depends_on');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_form_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('custom_form_blocks', 'categories')) {
                $table->dropColumn('categories');
            }
        });
    }
};
