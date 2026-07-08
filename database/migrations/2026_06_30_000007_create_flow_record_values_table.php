<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_record_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_record_id')->index();
            $table->unsignedBigInteger('flow_field_id')->index();
            $table->text('value_text')->nullable();
            $table->decimal('value_numeric', 20, 4)->nullable()->index();
            $table->date('value_date')->nullable()->index();
            $table->timestamps();

            $table->unique(['flow_record_id', 'flow_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_record_values');
    }
};
