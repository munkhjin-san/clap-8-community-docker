<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_relay_prizes', function (Blueprint $table) {
            $table->id();
            // The root nice post that identifies the completed relay chain.
            $table->integer('root_post_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('prize')->default(0);
            $table->tinyInteger('try_flag')->default(0);
            $table->timestamps();

            $table->unique(['root_post_id', 'user_id'], 'post_relay_prizes_unique_participant');
            $table->index(['user_id', 'try_flag']);
            $table->index(['user_id', 'prize', 'created_at']);

            $table->foreign('root_post_id')->references('id')->on('post_records')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_relay_prizes');
    }
};
