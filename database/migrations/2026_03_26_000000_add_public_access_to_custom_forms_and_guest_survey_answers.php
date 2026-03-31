<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_forms', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('has_prize');
            }

            if (!Schema::hasColumn('custom_forms', 'public_token')) {
                $table->string('public_token', 64)->nullable()->unique()->after('is_public');
            }
        });

        Schema::table('survey_answers', function (Blueprint $table) {
            if (Schema::hasColumn('survey_answers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->change();
            }

            if (!Schema::hasColumn('survey_answers', 'guest_uuid')) {
                $table->string('guest_uuid', 36)->nullable()->after('user_id');
            }
        });

        Schema::table('survey_block_answers', function (Blueprint $table) {
            if (Schema::hasColumn('survey_block_answers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->change();
            }
        });

        Schema::table('survey_block_element_answers', function (Blueprint $table) {
            if (Schema::hasColumn('survey_block_element_answers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_block_element_answers', function (Blueprint $table) {
            if (Schema::hasColumn('survey_block_element_answers', 'user_id')) {
                $table->foreignId('user_id')->nullable(false)->change();
            }
        });

        Schema::table('survey_block_answers', function (Blueprint $table) {
            if (Schema::hasColumn('survey_block_answers', 'user_id')) {
                $table->foreignId('user_id')->nullable(false)->change();
            }
        });

        Schema::table('survey_answers', function (Blueprint $table) {
            if (Schema::hasColumn('survey_answers', 'guest_uuid')) {
                $table->dropColumn('guest_uuid');
            }

            if (Schema::hasColumn('survey_answers', 'user_id')) {
                $table->foreignId('user_id')->nullable(false)->change();
            }
        });

        Schema::table('custom_forms', function (Blueprint $table) {
            if (Schema::hasColumn('custom_forms', 'public_token')) {
                $table->dropUnique(['public_token']);
                $table->dropColumn('public_token');
            }

            if (Schema::hasColumn('custom_forms', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
};
