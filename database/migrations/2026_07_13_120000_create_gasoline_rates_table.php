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
        Schema::create('gasoline_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 10, 2); // ガソリン単価（円/L・全社共通）
            $table->date('effective_from'); // この日から適用
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gasoline_rates');
    }
};
