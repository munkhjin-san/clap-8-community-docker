<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_portfolio_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_portfolio_id')->index();
            $table->unsignedBigInteger('lesson_theme_id')->nullable()->index();
            $table->unsignedBigInteger('owner_user_id')->index();   // whose portfolio
            $table->unsignedBigInteger('deleted_by')->index();      // admin who deleted it
            $table->unsignedInteger('attempt_no')->nullable();
            $table->integer('status')->nullable();
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();                   // key portfolio fields at deletion
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_portfolio_deletion_logs');
    }
};
