<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incident_punishments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        $punishments = [
            ['name' => '無',               'sort_order' => 1],
            ['name' => '注意',             'sort_order' => 2],
            ['name' => '指導',             'sort_order' => 3],
            ['name' => '厳重注意',          'sort_order' => 4],
            ['name' => '訓戒（始末書）',     'sort_order' => 5],
            ['name' => '減給',             'sort_order' => 6],
            ['name' => '出勤停止',          'sort_order' => 7],
            ['name' => '諭旨解雇',          'sort_order' => 8],
            ['name' => '懲戒解雇',          'sort_order' => 9],
            ['name' => 'パートナー企業処分',  'sort_order' => 10],
        ];

        $now = now();
        DB::table('incident_punishments')->insert(
            array_map(fn($p) => array_merge($p, ['created_at' => $now, 'updated_at' => $now]), $punishments)
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_punishments');
    }
};
