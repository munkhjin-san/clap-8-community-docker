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
        Schema::table('post_awards', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->index();
            $table->uuid('refund_batch_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_awards', function (Blueprint $table) {
            $table->dropIndex(['refunded_at', 'refund_batch_id']); // drop if you added it
            $table->dropColumn('refunded_at');
            $table->dropColumn('refund_batch_id');
        });
    }
};
