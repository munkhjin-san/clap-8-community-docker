<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_assign_records', function (Blueprint $table) {
            $table->id();
            $table->integer('created_user_id')->nullable();
            $table->float('score')->nullable();
            $table->json('assign_data')->nullable();
            $table->string('status')->nullable();
            $table->integer('project_record_id')->nullable()->index();
            $table->integer('user_id')->nullable()->index();
            $table->string('support_level')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_assign_records');
    }
};
