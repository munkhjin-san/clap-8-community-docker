<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\AccountChooserController;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Two-factor authentication (Fortify TOTP + recovery codes) — Sanctum migration Phase 4.
 * See docs/sanctum_migration_footprint.md.
 */
class TwoFactorTest extends TestCase
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
        $this->createUsersSchema();
        $this->createTester();
        $this->google2fa = new Google2FA();
    }

    public function test_user_can_enable_and_confirm_two_factor(): void
    {
        $user = User::find(1);

        $this->actingAs($user)->postJson('/user/two-factor-authentication')->assertOk();

        $secret = Crypt::decrypt(User::find(1)->two_factor_secret);
        $this->assertNotEmpty($secret);
        $this->assertNull(User::find(1)->two_factor_confirmed_at, '2FA must not be active until confirmed');

        $code = $this->google2fa->getCurrentOtp($secret);
        $this->actingAs($user)
            ->postJson('/user/confirmed-two-factor-authentication', ['code' => $code])
            ->assertOk();

        $this->assertNotNull(User::find(1)->two_factor_confirmed_at);
    }

    public function test_confirmed_two_factor_user_is_challenged_then_authenticates_with_code(): void
    {
        $secret = $this->seedConfirmedTwoFactor(1);

        // Password step: NOT authenticated yet, redirected to the challenge.
        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();

        // Challenge step: valid TOTP code completes login and runs the shared side-effects.
        $code = $this->google2fa->getCurrentOtp($secret);
        $response = $this->post('/two-factor-challenge', ['code' => $code]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $response->assertCookie(AccountChooserController::COOKIE_NAME);
    }

    public function test_two_factor_user_can_authenticate_with_recovery_code(): void
    {
        $this->seedConfirmedTwoFactor(1, ['recovery-aaaa-bbbb']);

        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/two-factor-challenge');

        $this->post('/two-factor-challenge', ['recovery_code' => 'recovery-aaaa-bbbb'])
            ->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_user_without_two_factor_logs_in_normally(): void
    {
        $this->post('/login', ['login' => 'tester', 'password' => $this->password])
            ->assertRedirect('/');
        $this->assertAuthenticated();
    }

    private function seedConfirmedTwoFactor(int $id, array $recoveryCodes = ['recovery-aaaa-bbbb']): string
    {
        $secret = $this->google2fa->generateSecretKey();

        DB::table('users')->where('id', $id)->update([
            'two_factor_secret' => Crypt::encrypt($secret),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ]);

        return $secret;
    }

    private function createUsersSchema(): void
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
    }

    private function createTester(): void
    {
        DB::table('users')->insert([
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
