<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 従業員の振込口座。1人1件（現在の口座のみ）。
 *
 * users に列を足さない理由：users は /flow_options やプロジェクトメンバー、各種ピッカーが常に
 * 広い select で読むモデルなので、機微な列を置くと誰かが select を増やした瞬間に漏れる。専用
 * テーブル＋専用モデルにすることで経路を1本に絞る。
 *
 * 履歴は持たない。過去に振り込んだ口座は、フロー側レコードが暗号化スナップショットとして
 * 持っているため、マスタが昔の値を覚えている必要がない。
 *
 * 退職しても消さない（方針決定済み）。softDeletes は管理画面からの削除操作用。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete()
                ->comment('対象従業員。1人1件');

            $table->string('account_holder')->nullable()->comment('口座名義人');
            $table->string('account_holder_kana')->nullable()->comment('口座名義人（フリガナ）');
            $table->string('bank_name')->nullable()->comment('金融機関名');
            $table->string('branch_name')->nullable()->comment('支店名');

            // 口座番号だけを暗号化する。金融機関名・支店名・名義が揃っても番号が無ければ振込には
            // 使えないので、番号を伏せれば残りは単体では役に立たない。TEXT なのは暗号文が元の桁数
            // より長いため。暗号化列はインデックスも検索もできない（口座番号で検索する用途はない）。
            $table->text('account_number')->nullable()->comment('口座番号（AccountVaultで暗号化）');
            // 一覧で伏せ字表示するための下4桁。復号せずに本人確認できるようにする（200人の一覧で
            // 200回復号するのを避ける）。モデルの mutator が番号と同時に書くのでズレない。
            $table->char('account_number_last4', 4)->nullable()->comment('口座番号の下4桁（平文・表示用）');

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('誰が登録したか');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('誰が変更したか');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
    }
};
