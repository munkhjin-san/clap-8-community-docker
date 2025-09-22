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
        Schema::create('drive_download_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('node_id')->nullable()->constrained('drive_nodes')->nullOnDelete();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('action', 20); // file | folder_zip | multi_zip
            $t->string('requested_name', 255);
            $t->unsignedInteger('file_count')->default(1);
            $t->unsignedBigInteger('bytes_expected')->nullable();
            $t->unsignedBigInteger('bytes_sent')->nullable();
            $t->unsignedSmallInteger('status')->default(200);
            $t->boolean('success')->default(true);
            // store IP as string; if you want fancy, swap to VARBINARY(16) + inet_pton/ntop
            $t->string('client_ip', 45)->nullable();
            $t->string('user_agent', 512)->nullable();
            $t->string('referer', 1024)->nullable();
            $t->json('manifest')->nullable(); // [{id,name,size}] for zips
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->unsignedInteger('duration_ms')->nullable();
            $t->timestamps();

            $t->index(['node_id', 'started_at']);
            $t->index(['user_id', 'started_at']);
            $t->index(['action', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drive_download_logs');
    }
};
