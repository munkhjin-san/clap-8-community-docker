<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freee_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('label')->comment('管理画面上の表示名');

            // freee アプリ管理で発行される OAuth アプリ資格情報
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable()->comment('encrypted');
            $table->string('redirect_uri')->nullable()
                ->comment('freeeアプリに登録したコールバックURLと完全一致させる。コールバック不可の環境では urn:ietf:wg:oauth:2.0:oob');

            // 連携先の事業所（認可完了時に freee から返る）
            $table->unsignedBigInteger('company_id')->nullable()->comment('freee 事業所ID');
            $table->string('company_name')->nullable();
            $table->string('external_cid')->nullable();

            // トークンペア。refresh_token は単回使用のため必ず同一トランザクションで差し替える。
            // scopeは保存しない（分岐に使わず、freee側の権限変更で古くなるだけ。認可時にログへ残す）。
            $table->text('access_token')->nullable()->comment('encrypted');
            $table->text('refresh_token')->nullable()->comment('encrypted');
            $table->string('token_type')->nullable();
            $table->timestamp('access_token_expires_at')->nullable()->comment('発行後6時間');
            $table->timestamp('refresh_token_expires_at')->nullable()->comment('発行後90日');
            $table->timestamp('last_refreshed_at')->nullable();
            $table->unsignedInteger('refresh_count')->default(0);

            // 健全性
            $table->string('status')->default('unconfigured')
                ->comment('unconfigured / awaiting_consent / connected / needs_reauth');
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('ブラウザで認可した管理者');
            $table->timestamp('authorized_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // 同じ事業所を二重に連携させない（MySQLはNULLの重複を許すため未接続行は複数可）
            $table->unique('company_id');
            $table->index(['active', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freee_credentials');
    }
};
