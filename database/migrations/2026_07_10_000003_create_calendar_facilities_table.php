<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->unsignedSmallInteger('slot');
            $table->string('label', 100);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'slot']);
            $table->index(['type', 'active']);
        });

        $now = now();
        $rooms = [
            '本社会議室',
            '本社休憩室',
            '大阪会議室',
            '東京会議室',
            '仙台会議室',
            '青森会議室',
            'フジメンビル',
        ];
        $cars = [
            '福岡582く5617 ホンダライフ',
            '福岡582え8686 ダイハツミラ',
            '福岡580と5654 オッティ',
            '福岡480わ3206 クリッパー',
            '福岡480ね5019 バン',
            '福岡480ね5020 バン',
            '鹿児島582そ6650 ミライース',
            '福岡582ち7350',
            'なにわ502の1116',
            '大阪581わ707（ﾚﾝﾀｶｰ）',
            '仙台580ひ6191',
            '福岡582そ1234',
            '鹿児島582そ8143',
        ];

        $records = [];
        foreach ($rooms as $slot => $label) {
            $records[] = [
                'type' => 'room',
                'slot' => $slot,
                'label' => $label,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach ($cars as $slot => $label) {
            $records[] = [
                'type' => 'car',
                'slot' => $slot,
                'label' => $label,
                'active' => $slot !== 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('calendar_facilities')->insert($records);
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_facilities');
    }
};
