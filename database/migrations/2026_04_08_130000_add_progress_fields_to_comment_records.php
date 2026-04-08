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
        Schema::table('comment_records', function (Blueprint $table) {
            $table->string('comment_type', 50)->default('normal')->after('messages');
            $table->unsignedTinyInteger('progress_checkpoint')->nullable()->after('comment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comment_records', function (Blueprint $table) {
            $table->dropColumn(['comment_type', 'progress_checkpoint']);
        });
    }
};
