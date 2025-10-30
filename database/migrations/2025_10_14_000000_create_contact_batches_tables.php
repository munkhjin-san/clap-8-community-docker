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
        Schema::create('contact_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('queued')->index();
            $table->string('scan_operation')->nullable()->index();
            $table->string('enrich_operation')->nullable()->index();
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('index')->default(0);
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('status')->default('queued')->index();
            $table->text('error')->nullable();
            $table->json('scan_result')->nullable();
            $table->json('enrich_result')->nullable();
            $table->bigInteger('contact_record_id')->nullable();
            $table->foreign('contact_record_id')->references('id')->on('contact_records')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_batch_items');
        Schema::dropIfExists('contact_batches');
    }
};
