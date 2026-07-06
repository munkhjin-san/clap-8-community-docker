<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email OTP opt-in flag (Sanctum migration Phase 7). Non-null = the user has confirmed
 * email-based 2FA and will be challenged for an emailed code at login. The codes themselves
 * live in the cache (hashed, short-lived), not the DB. See docs/sanctum_migration_footprint.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_otp_enabled_at')->after('two_factor_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_otp_enabled_at');
        });
    }
};
