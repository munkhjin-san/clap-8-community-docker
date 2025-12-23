<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_emote_users', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_field_data_record_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('emote_id');
            $table->timestamps();

            $table->unique(['custom_field_data_record_id', 'user_id', 'emote_id'], 'custom_field_emote_users_unique');

            $table->index(['custom_field_data_record_id'], 'custom_field_emote_users_custom_field_data_record_id_index');
            $table->index(['user_id'], 'custom_field_emote_users_user_id_index');
            $table->index(['emote_id'], 'custom_field_emote_users_emote_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_emote_users');
    }
};
