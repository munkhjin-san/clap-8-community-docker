<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_checkitems', function (Blueprint $table) {
            $table->id();
            $table->integer('project_record_id');
            $table->string('category', 100);
            $table->string('label', 255);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->foreign('project_record_id')
                ->references('id')
                ->on('project_records')
                ->cascadeOnDelete();
            $table->index('project_record_id');
            $table->index(['project_record_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_checkitems');
    }
};
