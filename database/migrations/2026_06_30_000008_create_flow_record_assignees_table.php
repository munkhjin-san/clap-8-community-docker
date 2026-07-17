<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_record_assignees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_record_id')->index();
            $table->unsignedBigInteger('flow_status_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('source')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['flow_record_id', 'flow_status_id', 'user_id'], 'flow_record_assignee_unique');
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_record_assignees');
    }
};
