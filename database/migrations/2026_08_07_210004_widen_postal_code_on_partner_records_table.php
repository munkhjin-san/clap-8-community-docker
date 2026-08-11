<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 郵便番号の桁数を広げる。
 *
 * 「〒108-0023」「100-00004」のように、記号付き・桁数違いの実データが存在するため
 * varchar(8) では入り切らない。形式を強制して落とすより、そのまま持てるようにする。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->string('postal_code', 16)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partner_records', function (Blueprint $table) {
            $table->string('postal_code', 8)->nullable()->change();
        });
    }
};
