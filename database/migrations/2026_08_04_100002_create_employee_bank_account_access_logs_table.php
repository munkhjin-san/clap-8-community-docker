<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 口座情報への操作ログ。
 *
 * 暗号化は「DBを持ち出した人」に効く。日常の運用で実際に問題になるのは「権限を持つ人が
 * 必要もなく見た」ケースで、それを抑止できるのは記録が残ることだけ。だからこのテーブルは
 * 暗号化と同じくらい設計の本体。
 *
 * フロー側の「表示」は flow_audit_logs に secret_reveal として既に残るので、ここは管理画面での
 * 登録・変更・削除・平文表示を対象にする。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bank_account_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('操作した管理者');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('対象の従業員');
            $table->string('action', 24)->comment('reveal|create|update|delete');
            $table->timestamp('created_at')->nullable();

            // 明示的に短い名前を付ける：自動生成名はMySQLの識別子64文字制限を超える
            $table->index(['target_user_id', 'created_at'], 'ebaal_target_idx');
            $table->index(['actor_user_id', 'created_at'], 'ebaal_actor_idx');
            $table->index(['action', 'created_at'], 'ebaal_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_account_access_logs');
    }
};
