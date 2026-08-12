<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLOWDが計算してfreeeへ登録した振替伝票の台帳。
 *
 * freeeには冪等キーが無いため、「何をどの伝票として登録済みか」をこちら側で
 * 持たないと再送のたびに二重計上になる。(対象月, 種類) を一意にして、
 * 2回目以降は同じ伝票を更新する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freee_journal_posts', function (Blueprint $table) {
            $table->id();
            $table->date('target_month');
            // 積立金の種類（refresh_reserve など）。
            $table->string('bucket', 64);
            $table->unsignedBigInteger('freee_journal_id');
            $table->unsignedBigInteger('freee_company_id')->nullable();
            // 登録内容のハッシュ。一致していれば送り直す必要がない。
            $table->string('fingerprint', 64);
            $table->bigInteger('amount')->default(0);
            $table->json('details')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamps();

            $table->unique(['target_month', 'bucket']);
            $table->index('freee_journal_id');
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freee_journal_posts');
    }
};
