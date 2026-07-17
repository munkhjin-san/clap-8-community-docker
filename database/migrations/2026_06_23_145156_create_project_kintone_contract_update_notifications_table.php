<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_kintone_contract_update_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('notification_id')->nullable()->index('pkcun_notification_id_idx');
            $table->unsignedInteger('app_id')->nullable()->index('pkcun_app_id_idx');
            $table->unsignedInteger('record_id')->nullable()->index('pkcun_record_id_idx');
            $table->unsignedInteger('project_id')->nullable()->index('pkcun_project_id_idx');
            $table->text('project_name')->nullable();
            $table->unsignedInteger('target_user_id')->nullable()->index('pkcun_target_user_id_idx');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_kintone_contract_update_notifications');
    }
};
