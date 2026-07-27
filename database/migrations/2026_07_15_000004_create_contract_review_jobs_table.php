<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_review_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status', 16)->default('queued')->index();
            $table->string('review_type', 8);
            $table->string('role', 8);
            $table->string('contract_type', 32);
            $table->string('original_filename');
            $table->string('mime', 128)->nullable();
            $table->string('stored_path')->nullable();
            $table->json('rendered_page_paths')->nullable();
            $table->boolean('use_extracted_text')->default(false);
            $table->unsignedBigInteger('project_contract_id')->nullable()->index();
            $table->json('result_json')->nullable();
            $table->longText('raw_text')->nullable();
            $table->json('document_input')->nullable();
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_review_jobs');
    }
};
