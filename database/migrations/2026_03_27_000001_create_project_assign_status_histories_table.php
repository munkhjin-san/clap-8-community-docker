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
        Schema::create('project_assign_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_assign_record_id')->index();
            $table->unsignedBigInteger('project_record_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->timestamp('changed_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_assign_status_histories');
    }
};
