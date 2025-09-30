<?php

// database/migrations/2025_09_22_000001_create_oauth_credentials_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('o_auth_credentials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Which provider/integration is this for?
            $t->string('provider')->index();          // 'google'
            $t->string('service')->nullable();        // 'calendar' (optional but handy)

            // Which Google account is connected
            $t->string('provider_user_id')->nullable();  // Google sub or calendarList primary owner id
            $t->string('account_email')->nullable()->index();

            // Tokens (encrypted JSON blobs or individual fields; see Model casts below)
            $t->text('access_token_enc');   // encrypted
            $t->text('refresh_token_enc')->nullable(); // encrypted
            $t->text('id_token_enc')->nullable();      // encrypted OIDC id_token if you need it

            $t->timestampTz('expires_at')->nullable();  // UTC; for access token expiry
            $t->string('token_type')->nullable();       // usually 'Bearer'
            $t->text('scope')->nullable();              // space-delimited scopes returned by Google

            $t->timestampTz('revoked_at')->nullable();  // if user disconnects
            $t->timestamps();

            $t->unique(['user_id', 'provider', 'service', 'account_email'], 'uniq_oauth_cred');
        });
    }

    public function down(): void {
        Schema::dropIfExists('oauth_credentials');
    }
};
