<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Email OTP 2FA — Sanctum migration Phase 7. See docs/sanctum_migration_footprint.md.
 * Codes live in the cache (array driver under phpunit); tests seed a known code there to
 * exercise the challenge, since send() emails a random one we can't read back.
 */
class EmailOtpTest extends TestCase
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
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->withoutVite();
        $this->createSchema();
        $this->createTester();
    }

    public function test_user_can_enable_email_otp_by_confirming_a_code(): void
    {
        // send() populates the cache for the user.
        $this->actingAs(User::find(1))->postJson('/user/email-otp/send')->assertOk();
        $this->assertTrue(Cache::has('email-otp:1'));

        // confirm with a known code (seed the cache as send() would).
        Cache::put('email-otp:1', hash('sha256', '123456'), now()->addMinutes(10));
        $this->actingAs(User::find(1))
            ->postJson('/user/email-otp/confirm', ['code' => '123456'])
            ->assertOk();

        $this->assertNotNull(User::find(1)->email_otp_enabled_at);
    }

    public function test_email_otp_user_is_challenged_then_authenticates(): void
    {
        $this->enableEmailOtp(1);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/email-otp-challenge');
        $this->assertGuest();

        Cache::put('email-otp:1', hash('sha256', '654321'), now()->addMinutes(10));
        $response = $this->post('/email-otp-challenge', ['code' => '654321']);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $response->assertCookie(\App\Http\Controllers\AccountChooserController::COOKIE_NAME);
    }

    public function test_email_otp_wrong_code_is_rejected(): void
    {
        $this->enableEmailOtp(1);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/email-otp-challenge');

        Cache::put('email-otp:1', hash('sha256', '654321'), now()->addMinutes(10));
        $this->from('/email-otp-challenge')
            ->post('/email-otp-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_remember_device_on_email_challenge_records_a_trusted_device(): void
    {
        $this->enableEmailOtp(1);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password]);
        Cache::put('email-otp:1', hash('sha256', '654321'), now()->addMinutes(10));
        $this->post('/email-otp-challenge', ['code' => '654321', 'remember_device' => '1'])
            ->assertRedirect('/');

        $this->assertSame(1, DB::table('user_trusted_devices')->where('user_id', 1)->count());
    }

    public function test_totp_takes_precedence_over_email_otp(): void
    {
        // user has BOTH a confirmed authenticator and email OTP enabled.
        $secret = (new Google2FA())->generateSecretKey();
        DB::table('users')->where('id', 1)->update([
            'two_factor_secret' => Crypt::encrypt($secret),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['recovery-aaaa'])),
            'two_factor_confirmed_at' => now(),
            'email_otp_enabled_at' => now(),
        ]);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/two-factor-challenge'); // TOTP, not email
    }

    private function enableEmailOtp(int $id): void
    {
        DB::table('users')->where('id', $id)->update(['email_otp_enabled_at' => now()]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('login')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('work_email')->nullable();
            $table->string('password')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('email_otp_enabled_at')->nullable();
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
