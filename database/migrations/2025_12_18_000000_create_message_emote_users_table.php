<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_emote_users', function (Blueprint $table) {
            $table->unsignedBigInteger('message_record_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('emote_id');
            $table->timestamps();

            $table->unique(['message_record_id', 'user_id', 'emote_id'], 'message_emote_users_unique');

            $table->index(['message_record_id'], 'message_emote_users_message_record_id_index');
            $table->index(['user_id'], 'message_emote_users_user_id_index');
            $table->index(['emote_id'], 'message_emote_users_emote_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_emote_users');
    }
};
