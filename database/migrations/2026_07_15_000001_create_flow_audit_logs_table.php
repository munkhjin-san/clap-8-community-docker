<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_definition_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('flow_record_id')->nullable()->index();
            $table->string('action')->index();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['flow_definition_id', 'created_at']);
            $table->index(['flow_definition_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_audit_logs');
    }
};
