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
        Schema::create('timecard_missing_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_record_id')->nullable()->constrained('shift_records')->nullOnDelete();

            $table->date('report_date');      
            $table->date('counted_date');     
            $table->timestamp('resolved_at')->nullable(); 

            $table->timestamps();

            $table->unique(['user_id', 'report_date']);
            $table->index(['user_id', 'counted_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timecard_missing_occurrences');
    }
};
