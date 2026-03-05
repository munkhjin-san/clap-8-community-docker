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
        Schema::table('custom_field_emote_users', function (Blueprint $table) {
            $table->string('emote_name', 50)->nullable();
        });
        Schema::table('message_emote_users', function (Blueprint $table) {
            $table->string('emote_name', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_field_emote_users', function (Blueprint $table) {
             $table->dropColumn('emote_name');
        });
        Schema::table('message_emote_users', function (Blueprint $table) {
             $table->dropColumn('emote_name');
        });
    }
};
