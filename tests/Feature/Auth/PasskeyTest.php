<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Passkeys / WebAuthn — Sanctum migration Phase 8. See docs/sanctum_migration_footprint.md.
 *
 * The full create/verify ceremony needs a real authenticator (Touch ID / security key) and a
 * browser, so it can't run here. These tests cover the parts that don't: option generation,
 * auth-gating of the management endpoints, and listing a user's passkeys.
 */
class PasskeyTest extends TestCase
{
    private string $password = 'secret-password-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'http://localhost',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->withoutVite();
        $this->createSchema();
        $this->createTester();
    }

    public function test_passkey_login_options_are_available_to_guests(): void
    {
        $this->getJson('/passkeys/login/options')
            ->assertOk()
            ->assertJsonStructure(['options' => ['challenge']]);
    }

    public function test_passkey_registration_options_require_authentication(): void
    {
        $this->getJson('/user/passkeys/options')->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_registration_options(): void
    {
        $this->actingAs(User::find(1))
            ->getJson('/user/passkeys/options')
            ->assertOk()
            ->assertJsonStructure(['options' => ['challenge', 'rp', 'user']]);
    }

    public function test_user_can_list_their_passkeys(): void
    {
        DB::table('passkeys')->insert([
            'user_id' => 1,
            'name' => 'My iPhone',
            'credential_id' => 'cred-abc',
            'credential' => json_encode(['foo' => 'bar']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::find(1))
            ->getJson('/user/passkeys')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'My iPhone');
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('login')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->integer('deleted_flag')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('passkeys', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    private function createTester(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'login' => 'tester',
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => Hash::make($this->password),
            'deleted_flag' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
