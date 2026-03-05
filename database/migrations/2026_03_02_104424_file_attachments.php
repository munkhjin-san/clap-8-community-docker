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
        Schema::create('file_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('file_id')
                ->constrained('file_records')
                ->cascadeOnDelete();
            
            $table->string('attachable_type', 191);
            $table->unsignedBigInteger('attachable_id');

            $table->string('collection', 50)->default('attachments');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['attachable_type', 'attachable_id'], 'file_attachables_index');
            $table->index(['file_id'], 'file_attachments_file_id_index');

            $table->unique(
                ['file_id', 'attachable_type', 'attachable_id', 'collection'],
                'file_attachments_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_attachments');
    }
};
