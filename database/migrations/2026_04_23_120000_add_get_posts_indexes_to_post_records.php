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
        Schema::table('post_records', function (Blueprint $table) {
            $table->index(
                ['app_type', 'deleted_at', 'updated_at'],
                'post_records_get_posts_feed_idx'
            );

            $table->index(
                ['app_type', 'challenge_main_category', 'challenge_sub_category', 'deleted_at', 'updated_at'],
                'post_records_get_posts_category_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_records', function (Blueprint $table) {
            $table->dropIndex('post_records_get_posts_feed_idx');
            $table->dropIndex('post_records_get_posts_category_idx');
        });
    }
};
