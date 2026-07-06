<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EncryptCookies;
use App\Models\User;
use App\Services\Auth\TrustedDeviceManager;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * "Remember this device" for 2FA — Sanctum migration Phase 6.
 * See docs/sanctum_migration_footprint.md.
 */
class TrustedDeviceTest extends TestCase
{
    private string $password = 'secret-password-123';
    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->withoutVite();
        $this->createSchema();
        $this->createTester();
        $this->google2fa = new Google2FA();
    }

    public function test_trusted_device_cookie_skips_the_two_factor_challenge(): void
    {
        $this->seedConfirmedTwoFactor(1);
        $token = $this->seedTrustedDevice(1);

        // Bypass cookie encryption so the test can inject a raw token; the encrypt/decrypt
        // round-trip is a framework concern, not part of the trusted-device logic.
        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie(TrustedDeviceManager::COOKIE, $token)
            ->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/'); // straight in — NOT /two-factor-challenge

        $this->assertAuthenticated();
    }

    public function test_without_trusted_cookie_two_factor_is_still_required(): void
    {
        $this->seedConfirmedTwoFactor(1);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_expired_trusted_device_still_requires_challenge(): void
    {
        $this->seedConfirmedTwoFactor(1);
        $token = $this->seedTrustedDevice(1, now()->subDay());

        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie(TrustedDeviceManager::COOKIE, $token)
            ->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_completing_challenge_with_remember_device_records_a_trusted_device(): void
    {
        $secret = $this->seedConfirmedTwoFactor(1);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/two-factor-challenge');

        $code = $this->google2fa->getCurrentOtp($secret);
        $this->post('/two-factor-challenge', ['code' => $code, 'remember_device' => '1'])
            ->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertSame(1, DB::table('user_trusted_devices')->where('user_id', 1)->count());
    }

    public function test_challenge_without_remember_device_records_nothing(): void
    {
        $secret = $this->seedConfirmedTwoFactor(1);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password]);
        $code = $this->google2fa->getCurrentOtp($secret);
        $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect('/');

        $this->assertSame(0, DB::table('user_trusted_devices')->where('user_id', 1)->count());
    }

    public function test_index_lists_only_the_users_own_trusted_devices(): void
    {
        $this->createUser(2, 'other');
        $this->seedTrustedDevice(1);
        $this->seedTrustedDevice(1);
        $this->seedTrustedDevice(2);

        $this->actingAs(User::find(1))
            ->getJson('/trusted-devices')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_destroy_revokes_a_single_device(): void
    {
        $this->seedTrustedDevice(1);
        $id = DB::table('user_trusted_devices')->where('user_id', 1)->value('id');

        $this->actingAs(User::find(1))
            ->deleteJson("/trusted-devices/{$id}")
            ->assertOk();

        $this->assertSame(0, DB::table('user_trusted_devices')->where('id', $id)->count());
    }

    public function test_destroy_cannot_revoke_another_users_device(): void
    {
        $this->createUser(2, 'other');
        $this->seedTrustedDevice(2);
        $otherId = DB::table('user_trusted_devices')->where('user_id', 2)->value('id');

        $this->actingAs(User::find(1))
            ->deleteJson("/trusted-devices/{$otherId}")
            ->assertOk();

        // user 2's device must survive — destroy is scoped to the acting user.
        $this->assertSame(1, DB::table('user_trusted_devices')->where('id', $otherId)->count());
    }

    public function test_destroy_all_revokes_every_device_for_the_user(): void
    {
        $this->createUser(2, 'other');
        $this->seedTrustedDevice(1);
        $this->seedTrustedDevice(1);
        $this->seedTrustedDevice(2);

        $this->actingAs(User::find(1))
            ->deleteJson('/trusted-devices')
            ->assertOk();

        $this->assertSame(0, DB::table('user_trusted_devices')->where('user_id', 1)->count());
        $this->assertSame(1, DB::table('user_trusted_devices')->where('user_id', 2)->count());
    }

    private function seedTrustedDevice(int $userId, $expiresAt = null): string
    {
        $token = Str::random(64);
        DB::table('user_trusted_devices')->insert([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'device_name' => 'PHPUnit',
            'expires_at' => $expiresAt ?? now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $token;
    }

    private function seedConfirmedTwoFactor(int $id): string
    {
        $secret = $this->google2fa->generateSecretKey();

        DB::table('users')->where('id', $id)->update([
            'two_factor_secret' => Crypt::encrypt($secret),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['recovery-aaaa-bbbb'])),
            'two_factor_confirmed_at' => now(),
        ]);

        return $secret;
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('login')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->integer('deleted_flag')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_trusted_devices', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token_hash', 64)->unique();
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    private function createTester(): void
    {
        $this->createUser(1, 'tester');
    }

    private function createUser(int $id, string $login): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'login' => $login,
            'name' => ucfirst($login),
            'email' => "{$login}@example.com",
            'password' => Hash::make($this->password),
            'deleted_flag' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
