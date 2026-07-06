<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\AccountChooserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 0 regression baseline for the Sanctum/Fortify migration.
 * Pins the CURRENT laravel/ui login/logout behavior so the Fortify cutover
 * (Phase 2) can be proven equivalent. See docs/sanctum_migration_footprint.md.
 *
 * Note: CommunityResolver runs inside LoginController::authenticated() but
 * safely no-ops without the community schema (CommunityResolver::resolveFor),
 * so this test keeps a minimal users table. Community resolution itself is
 * covered by tests/Feature/CommunityLogicTest.php.
 */
class LoginTest extends TestCase
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
        $this->createUsersSchema();
        $this->createTester();
    }

    public function test_login_screen_is_reachable(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_user_authenticates_with_login_username_and_password(): void
    {
        $response = $this->post('/login', [
            'login' => 'tester',
            'password' => $this->password,
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        // LoginController::authenticated() queues the account-chooser cookie.
        $response->assertCookie(AccountChooserController::COOKIE_NAME);
    }

    public function test_user_cannot_authenticate_with_wrong_password(): void
    {
        $response = $this->from('/login')->post('/login', [
            'login' => 'tester',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->post('/login', ['login' => 'tester', 'password' => $this->password]);
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    private function createUsersSchema(): void
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
