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
        Schema::create('project_member_roles', function (Blueprint $table) {
            $table->id();

            $table->integer('project_record_id')->index();
            $table->unsignedBigInteger('user_id')->index();

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('comment')->nullable();
            $table->string('risk')->nullable();
            $table->text('risk_management')->nullable();
            $table->integer('member_limit')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_record_id')
                ->references('id')->on('project_records')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->index(['project_record_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_member_roles');
    }
};
