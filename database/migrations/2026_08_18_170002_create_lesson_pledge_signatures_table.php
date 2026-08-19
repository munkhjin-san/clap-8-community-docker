<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_pledge_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_theme_id');
            $table->unsignedBigInteger('user_id');
            // The learner's own signed copy of the pledge PDF.
            $table->string('file_path');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_theme_id', 'user_id'], 'lesson_pledge_theme_user_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_pledge_signatures');
    }
};
