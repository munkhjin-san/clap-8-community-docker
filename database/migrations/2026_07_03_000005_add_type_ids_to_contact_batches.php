<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_batches', function (Blueprint $table) {
            // All contact types selected for the batch (JSON array of contact_type ids).
            // contact_type_id remains the primary type for backward compatibility.
            $table->json('type_ids')->nullable()->after('contact_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_batches', function (Blueprint $table) {
            $table->dropColumn('type_ids');
        });
    }
};
