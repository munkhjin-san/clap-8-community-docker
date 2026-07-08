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
        Schema::table('project_kintone_contract_update_notifications', function (Blueprint $table) {
            $table->dropIndex('pkcun_notification_id_idx');
            $table->dropColumn('notification_id');
        });

        Schema::table('project_kintone_contract_update_notifications', function (Blueprint $table) {
            $table->string('notification_id', 36)->nullable()->after('id')->index('pkcun_notification_id_idx');
            $table->string('type')->nullable()->after('notification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_kintone_contract_update_notifications', function (Blueprint $table) {
            $table->dropIndex('pkcun_notification_id_idx');
            $table->dropColumn(['notification_id', 'type']);
        });

        Schema::table('project_kintone_contract_update_notifications', function (Blueprint $table) {
            $table->unsignedInteger('notification_id')->nullable()->after('id')->index('pkcun_notification_id_idx');
        });
    }
};
