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
        Schema::table('o_auth_credentials', function (Blueprint $table) {
            $table->string('account_name')->nullable()->after('account_email');
            $table->string('avatar_url')->nullable()->after('account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('o_auth_credentials', function (Blueprint $table) {
            $table->dropColumn(['account_name', 'avatar_url']);
        });
    }
};
