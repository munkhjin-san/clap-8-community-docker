<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_rakuaward_scores', function (Blueprint $table) {
            $table->id();
            $table->integer('post_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Director score for a rakuaward nomination, 1-10.
            $table->tinyInteger('score')->default(0);
            $table->timestamps();

            $table->unique(['post_id', 'user_id'], 'post_rakuaward_scores_unique_scorer');
            $table->index(['post_id']);
            $table->foreign('post_id')->references('id')->on('post_records')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_rakuaward_scores');
    }
};
