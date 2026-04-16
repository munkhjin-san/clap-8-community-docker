<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timecard_cost_records', function (Blueprint $table) {
            $table->unsignedTinyInteger('transport_type')->nullable()->after('type');
            $table->string('departure_place')->nullable()->after('transport_type');
            $table->string('arrival_place')->nullable()->after('departure_place');
        });
    }

    public function down(): void
    {
        Schema::table('timecard_cost_records', function (Blueprint $table) {
            $table->dropColumn([
                'transport_type',
                'departure_place',
                'arrival_place',
            ]);
        });
    }
};
