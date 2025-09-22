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
       Schema::create('drive_node_acls', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('node_id')->constrained('drive_nodes')->cascadeOnDelete();
            // Scope to users now; you can add 'group' later if you enjoy complexity
            $t->unsignedBigInteger('user_id');
            $t->enum('role', ['viewer','editor'])->default('viewer');
            // If this row was copied down from a parent for inheritance
            $t->foreignUuid('inherited_from')->nullable()->constrained('drive_nodes')->nullOnDelete();
            $t->timestamp('expires_at')->nullable();
            $t->unsignedBigInteger('granted_by');
            $t->timestamps();

            $t->unique(['node_id','user_id','inherited_from']); // allow one explicit + many inherited sources
            $t->index(['node_id','user_id','role']);
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
