<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_theme_ai_configs', function (Blueprint $table) {
            $table->dropForeign(['lesson_theme_id']);
            $table->dropUnique(['lesson_theme_id']);
            $table->string('config_key')->default('theme_general')->after('lesson_theme_id');
            $table->integer('lesson_material_id')->nullable()->after('config_key');
            $table->unique(['lesson_theme_id', 'config_key'], 'lesson_theme_ai_configs_theme_key_unique');
            $table->foreign('lesson_theme_id')->references('id')->on('lesson_themes')->cascadeOnDelete();
            $table->index('lesson_material_id', 'lesson_theme_ai_configs_material_index');
            $table->foreign('lesson_material_id')->references('id')->on('lesson_materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_theme_ai_configs', function (Blueprint $table) {
            $table->dropForeign(['lesson_material_id']);
            $table->dropForeign(['lesson_theme_id']);
            $table->dropIndex('lesson_theme_ai_configs_material_index');
            $table->dropUnique('lesson_theme_ai_configs_theme_key_unique');
            $table->dropColumn(['config_key', 'lesson_material_id']);
            $table->unique('lesson_theme_id');
            $table->foreign('lesson_theme_id')->references('id')->on('lesson_themes')->cascadeOnDelete();
        });
    }
};
