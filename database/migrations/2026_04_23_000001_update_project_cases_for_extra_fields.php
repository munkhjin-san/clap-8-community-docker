<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_cases', function (Blueprint $table) {
            

            // Add a plain index on timecard_record_id so the FK can be re-created
            $table->index('timecard_record_id');

            // Re-add the foreign key
            $table->foreign('timecard_record_id')
                ->references('id')
                ->on('timecard_records')
                ->cascadeOnDelete();

            // Add meta column to store extra field values (selector choices, free text, etc.)
            $table->json('meta')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('project_cases', function (Blueprint $table) {
            $table->dropColumn('meta');

            $table->dropForeign(['timecard_record_id']);
            $table->dropIndex(['timecard_record_id']);

            $table->unique(['timecard_record_id', 'status']);
            $table->foreign('timecard_record_id')
                ->references('id')
                ->on('timecard_records')
                ->cascadeOnDelete();
        });
    }
};
