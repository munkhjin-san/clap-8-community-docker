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
        Schema::create('incident_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        $categories = [
            ['name' => '労務問題',               'sort_order' => 1],
            ['name' => 'ハラスメント',             'sort_order' => 2],
            ['name' => '報告漏れ・提出物遅延',       'sort_order' => 3],
            ['name' => '業務改善・指導',            'sort_order' => 4],
            ['name' => 'クレーム',               'sort_order' => 5],
            ['name' => '車両事故・違反',            'sort_order' => 6],
            ['name' => '職務懈怠',               'sort_order' => 7],
            ['name' => '情報漏えい（誤送信）',        'sort_order' => 8],
            ['name' => '物品破損・紛失',            'sort_order' => 9],
            ['name' => '懲罰委員会',              'sort_order' => 10],
            ['name' => 'プライベート交通事故・違反',    'sort_order' => 11],
            ['name' => 'その他',                 'sort_order' => 12],
        ];

        $now = now();
        DB::table('incident_categories')->insert(
            array_map(fn($c) => array_merge($c, ['created_at' => $now, 'updated_at' => $now]), $categories)
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_categories');
    }
};
