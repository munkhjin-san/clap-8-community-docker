<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_record_values', function (Blueprint $table) {
            if (!Schema::hasColumn('flow_record_values', 'value_datetime')) {
                $table->dateTime('value_datetime')->nullable()->after('value_date');
            }
            if (!Schema::hasColumn('flow_record_values', 'value_boolean')) {
                $table->boolean('value_boolean')->nullable()->after('value_datetime');
            }
            if (!Schema::hasColumn('flow_record_values', 'value_json')) {
                $table->json('value_json')->nullable()->after('value_boolean');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_record_values', function (Blueprint $table) {
            foreach (['value_datetime', 'value_boolean', 'value_json'] as $col) {
                if (Schema::hasColumn('flow_record_values', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
