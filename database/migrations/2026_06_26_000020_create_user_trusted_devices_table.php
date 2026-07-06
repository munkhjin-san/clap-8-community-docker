<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trusted ("remember this device") records for 2FA — Sanctum migration Phase 6.
 * A browser that completed the 2FA challenge can be remembered so it skips the
 * challenge on subsequent logins until the record expires. The cookie holds a
 * random token; only its sha256 hash is stored here. See docs/sanctum_migration_footprint.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('token_hash', 64)->unique();
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_trusted_devices');
    }
};
