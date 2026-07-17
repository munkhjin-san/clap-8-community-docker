<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_theme_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('lesson_theme_category_theme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_theme_category_id')->constrained()->cascadeOnDelete();
            $table->integer('lesson_theme_id');
            $table->timestamps();

            $table->foreign('lesson_theme_id')->references('id')->on('lesson_themes')->cascadeOnDelete();
            $table->unique(['lesson_theme_category_id', 'lesson_theme_id'], 'lesson_theme_category_theme_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_theme_category_theme');
        Schema::dropIfExists('lesson_theme_categories');
    }
};
