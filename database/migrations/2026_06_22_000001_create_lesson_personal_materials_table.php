<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('lesson_personal_materials');

        Schema::create('lesson_personal_materials', function (Blueprint $table) {
            $table->id();
            $table->integer('lesson_theme_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lesson_theme_ai_config_id')->nullable();
            $table->string('config_key');
            $table->longText('content')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_theme_id', 'user_id', 'config_key'], 'lesson_personal_material_unique');
            $table->foreign('lesson_theme_id')->references('id')->on('lesson_themes')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lesson_theme_ai_config_id', 'lesson_personal_material_ai_config_foreign')
                ->references('id')
                ->on('lesson_theme_ai_configs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_personal_materials');
    }
};
