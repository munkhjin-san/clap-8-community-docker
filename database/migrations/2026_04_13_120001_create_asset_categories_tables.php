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
        Schema::create('asset_category_items', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable(); // asset | account
            $table->string('title');
            $table->string('required_data')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });

        // Initial data
        $now = now();

        $physicalItems = [
            ['title' => 'ノートPC', 'required_data' => 'メーカー・OS・バージョン'],
            ['title' => 'デスクトップPC', 'required_data' => 'メーカー・OS・バージョン'],
            ['title' => 'モニター', 'required_data' => 'メーカー・型番'],
            ['title' => '業務端末（本体）', 'required_data' => 'メーカー'],
            ['title' => 'SIM', 'required_data' => '電話番号'],
            ['title' => '事務所キー', 'required_data' => 'キー番号'],
            ['title' => 'ロッカーキー', 'required_data' => 'キー番号'],
            ['title' => 'ETCカード', 'required_data' => 'カード番号'],
            ['title' => 'ガソリンカード', 'required_data' => 'カード番号・TFC番号'],
            ['title' => 'レンタカーカード', 'required_data' => 'カード番号'],
            ['title' => 'ICカード', 'required_data' => 'カード番号'],
            ['title' => 'Times Business Card', 'required_data' => 'カード番号'],
        ];

        $accountItems = [
            ['title' => 'Googleアカウント', 'required_data' => 'アカウントID'],
            ['title' => 'Microsoftアカウント', 'required_data' => 'アカウントID'],
            ['title' => 'グラウドメール', 'required_data' => 'アカウントID'],
        ];

        foreach ($physicalItems as $index => $item) {
            DB::table('asset_category_items')->insert([
                'type' => 'asset',
                'title' => $item['title'],
                'required_data' => $item['required_data'] ?? null,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($accountItems as $index => $item) {
            DB::table('asset_category_items')->insert([
                'type' => 'account',
                'title' => $item['title'],
                'required_data' => $item['required_data'] ?? null,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_category_items');
    }
};
