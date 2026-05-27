<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->nullable()->index();
            $table->timestamps();
        });

        $defaults = collect([
            '報告済み',
            '処分未決定',
            '処分決定済み・管理部未入力',
            '未指導',
            '完了',
        ]);

        $existing = Schema::hasTable('incidents')
            ? DB::table('incidents')->whereNotNull('status')->distinct()->pluck('status')
            : collect();

        $now = now();
        $defaults
            ->merge($existing)
            ->filter()
            ->unique()
            ->values()
            ->each(function (string $name, int $index) use ($now) {
                DB::table('incident_statuses')->insert([
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_statuses');
    }
};
