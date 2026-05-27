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
        Schema::create('app_comments', function (Blueprint $table) {
            $table->id();
            $table->string('commentable_type')->index();
            $table->unsignedBigInteger('commentable_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('content');
            $table->json('mentioned_user_ids')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'created_at']);
        });

        Schema::table('message_files', function (Blueprint $table) {
            if (!Schema::hasColumn('message_files', 'app_comment_id')) {
                $table->unsignedBigInteger('app_comment_id')->nullable()->index()->after('comment_record_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_files', function (Blueprint $table) {
            if (Schema::hasColumn('message_files', 'app_comment_id')) {
                $table->dropColumn('app_comment_id');
            }
        });

        Schema::dropIfExists('app_comments');
    }
};
