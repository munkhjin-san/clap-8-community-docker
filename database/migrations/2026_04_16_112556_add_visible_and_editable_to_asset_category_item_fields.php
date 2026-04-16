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
        Schema::table('asset_category_item_fields', function (Blueprint $table) {
            $table->enum('visible', ['public', 'private', 'user'])->default('public')->after('rules');
            $table->boolean('editable')->default(true)->after('visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_category_item_fields', function (Blueprint $table) {
            $table->dropColumn(['visible', 'editable']);
        });
    }
};
