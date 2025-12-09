<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_records', function (Blueprint $table) {
            $table->boolean('has_forecast')->default(true)->after('is_new');
            $table->boolean('has_goals')->default(false)->after('has_forecast');
            $table->string('unit_id', 32)->default('JPY')->after('has_goals');
            $table->string('custom_unit_label')->nullable()->after('unit_id');
            $table->json('actual_statuses')->nullable()->after('custom_unit_label');
        });

        $defaultStatuses = [
            ['status_id' => 1, 'label' => '未着手', 'is_system_default' => true, 'sort_order' => 1],
            ['status_id' => 2, 'label' => '進行中', 'is_system_default' => true, 'sort_order' => 2],
            ['status_id' => 3, 'label' => '完了', 'is_system_default' => true, 'sort_order' => 3],
            ['status_id' => 4, 'label' => 'キャンセル', 'is_system_default' => true, 'sort_order' => 4],
        ];

        DB::table('project_records')->update([
            'has_forecast' => true,
            'has_goals' => false,
            'unit_id' => 'JPY',
            'actual_statuses' => json_encode($defaultStatuses, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function down(): void
    {
        Schema::table('project_records', function (Blueprint $table) {
            $table->dropColumn([
                'has_forecast',
                'has_goals',
                'unit_id',
                'custom_unit_label',
                'actual_statuses',
            ]);
        });
    }
};
