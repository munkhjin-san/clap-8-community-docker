<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-action switch for「通知バッジを表示する」.
 *
 * When off, the people named in that action's 押せる人 stop being counted as 対応待ち and stop
 * receiving the pending_action notification — the button still works, it just no longer chases
 * anyone. Useful for statuses where the named group watches the list anyway and does not want a
 * badge per record.
 *
 * Defaults to true so every existing action keeps notifying exactly as it does today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_status_actions', function (Blueprint $table) {
            $table->boolean('notify')->default(true)->after('eligible');
        });
    }

    public function down(): void
    {
        Schema::table('flow_status_actions', function (Blueprint $table) {
            $table->dropColumn('notify');
        });
    }
};
