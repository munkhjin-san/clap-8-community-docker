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
        Schema::create('drive_nodes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('parent_id')->nullable()->index();
            $t->enum('type', ['folder','file']);
            $t->string('name');                 // unique within parent
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->string('storage_path')->nullable(); // only for files
            $t->foreignUuid('owner_id')->index();
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['parent_id','name','deleted_at']); // enforce unique names per folder
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
