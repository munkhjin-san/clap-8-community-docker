<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountChooserController;
use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityRole;
use App\Models\User;
use App\Services\Community\CommunityContext;
use App\Services\Community\CommunityResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommunityLogicTest extends TestCase
{
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
        $this->createBaseSchema();
    }

    public function test_migration_creates_default_community_memberships_scopes_and_backfills_records(): void
    {
        DB::table('users')->insert([
            ['id' => 608, 'name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret', 'position_id' => 1, 'partner_flag' => 0, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 700, 'name' => 'PM', 'email' => 'pm@example.com', 'password' => 'secret', 'position_id' => 6, 'partner_flag' => 0, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 701, 'name' => 'Partner', 'email' => 'partner@example.com', 'password' => 'secret', 'position_id' => 14, 'partner_flag' => 1, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 702, 'name' => 'Registered', 'email' => 'registered@example.com', 'password' => 'secret', 'position_id' => 15, 'partner_flag' => 0, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('board_records')->insert(['id' => 1, 'title' => 'Legacy board', 'created_at' => now(), 'updated_at' => now()]);

        $migration = include $this->communityMigrationPath();
        $migration->up();

        $community = Community::where('slug', Community::DEFAULT_SLUG)->firstOrFail();

        $this->assertSame(Community::DEFAULT_NAME, $community->name);
        $this->assertSame(4, CommunityMembership::where('community_id', $community->id)->count());
        $this->assertSame('admin', CommunityMembership::where('user_id', 608)->firstOrFail()->role->key);
        $this->assertSame('管理者', CommunityMembership::where('user_id', 608)->firstOrFail()->role->name);
        $this->assertSame('pm', CommunityMembership::where('user_id', 700)->firstOrFail()->role->key);
        $this->assertSame(CommunityMembership::SCOPE_PARTNER, CommunityMembership::where('user_id', 701)->firstOrFail()->scope);
        $this->assertSame(CommunityMembership::SCOPE_REGISTERED, CommunityMembership::where('user_id', 702)->firstOrFail()->scope);
        $this->assertSame($community->id, DB::table('board_records')->where('id', 1)->value('community_id'));
    }

    public function test_resolver_and_permission_compatibility_use_active_community_membership(): void
    {
        $this->seedCommunityTables();
        $user = User::findOrFail(701);

        app(CommunityResolver::class)->resolveFor($user);
        $context = app(CommunityContext::class);

        $this->assertTrue($context->isPartner());
        $this->assertFalse($context->isAdmin());
        $this->assertTrue($user->isPartnerScope());

        $admin = User::findOrFail(608);
        app(CommunityResolver::class)->resolveFor($admin);

        $this->assertTrue($admin->isAdmin());
    }

    public function test_user_can_switch_only_to_member_community(): void
    {
        $this->seedCommunityTables();
        $user = User::findOrFail(700);
        $otherCommunity = Community::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active']);

        $this->actingAs($user)
            ->patchJson('/community_context/switch', ['community_id' => $otherCommunity->id])
            ->assertNotFound();

        $this->actingAs($user)
            ->patchJson('/community_context/switch', ['community_id' => Community::where('slug', Community::DEFAULT_SLUG)->value('id')])
            ->assertOk()
            ->assertJsonPath('community_scope', CommunityMembership::SCOPE_INTERNAL);
    }

    public function test_admin_can_update_active_community_title_and_icon(): void
    {
        $this->seedCommunityTables();
        $admin = User::findOrFail(608);

        $this->actingAs($admin)
            ->patchJson('/community_context', [
                'name' => 'New Community Title',
                'icon_path' => 'community-test-icon',
            ])
            ->assertOk()
            ->assertJsonPath('active_community.name', 'New Community Title')
            ->assertJsonPath('active_community.config.icon_path', 'community-test-icon');

        $community = Community::where('slug', Community::DEFAULT_SLUG)->firstOrFail();

        $this->assertSame('New Community Title', $community->name);
        $this->assertSame('community-test-icon', $community->config['icon_path']);
        $this->assertDatabaseHas('community_membership_audit_logs', [
            'community_id' => $community->id,
            'actor_user_id' => $admin->id,
            'action' => 'community.updated',
        ]);
    }

    public function test_admin_can_view_blade_catalog_and_manage_editable_role_blades(): void
    {
        $this->seedCommunityTables();
        $admin = User::findOrFail(608);
        $role = CommunityRole::where('key', 'pm')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/community_context/capabilities')
            ->assertOk()
            ->assertJsonPath('groups.0.key', 'apps')
            ->assertJsonPath('groups.0.blades.0.key', 'app.project')
            ->assertJsonPath('groups.1.key', 'actions')
            ->assertJsonPath('groups.1.blades.0.key', 'project.approve');

        $this->actingAs($admin)
            ->patchJson("/community_context/roles/{$role->id}", [
                'name' => 'プロジェクトマネージャー',
                'capabilities' => ['app.project', 'app.timesheet', 'finance.analyze'],
            ])
            ->assertOk()
            ->assertJsonPath('name', 'プロジェクトマネージャー')
            ->assertJsonPath('capabilities.2', 'finance.analyze');

        $this->assertSame(['app.project', 'app.timesheet', 'finance.analyze'], $role->fresh()->capabilities);
        $this->assertDatabaseHas('community_membership_audit_logs', [
            'community_id' => $role->community_id,
            'actor_user_id' => $admin->id,
            'action' => 'role.updated',
        ]);

        // Unknown blade keys are rejected against the closed catalog.
        $this->actingAs($admin)
            ->patchJson("/community_context/roles/{$role->id}", [
                'name' => 'プロジェクトマネージャー',
                'capabilities' => ['position.board'],
            ])
            ->assertStatus(422);

        $createdRoleId = $this->actingAs($admin)
            ->postJson('/community_context/roles', [
                'name' => '営業管理',
                'capabilities' => ['app.project'],
            ])
            ->assertCreated()
            ->assertJsonPath('name', '営業管理')
            ->assertJsonPath('capabilities.0', 'app.project')
            ->json('id');

        $this->actingAs($admin)
            ->deleteJson("/community_context/roles/{$createdRoleId}")
            ->assertOk()
            ->assertJsonPath('deleted', true);
    }

    public function test_partner_scope_users_are_migrated_to_partner_role(): void
    {
        $this->seedCommunityTables();

        $partnerRole = CommunityRole::where('key', 'partner')->firstOrFail();
        $this->assertSame(
            ['app.schedule', 'app.notice'],
            $partnerRole->capabilities
        );

        // Partner role grants its app blades but not restricted ones.
        // Dashboard/Chat are built-in, so they are never blades.
        $this->assertContains('app.notice', $partnerRole->capabilities);
        $this->assertNotContains('app.post', $partnerRole->capabilities);
        $this->assertNotContains('app.timesheet', $partnerRole->capabilities);
        $this->assertNotContains('app.dashboard', $partnerRole->capabilities);
        $this->assertNotContains('app.board', $partnerRole->capabilities);
    }

    public function test_app_blade_access_resolves_per_role(): void
    {
        $this->seedCommunityTables();
        $permissions = app(\App\Services\Community\CommunityPermissionService::class);

        // Partner (701): only schedule + notice; no project/post/timesheet.
        $partner = User::findOrFail(701);
        $this->assertTrue($permissions->can('app.schedule', $partner));
        $this->assertTrue($permissions->can('app.notice', $partner));
        $this->assertFalse($permissions->can('app.project', $partner));
        $this->assertFalse($permissions->can('app.post', $partner));
        $this->assertFalse($permissions->can('app.timesheet', $partner));

        // PM (700): full app access.
        $pm = User::findOrFail(700);
        $this->assertTrue($permissions->can('app.project', $pm));
        $this->assertTrue($permissions->can('app.timesheet', $pm));

        // Admin (608): bypasses every blade.
        $admin = User::findOrFail(608);
        $this->assertTrue($permissions->can('app.project', $admin));
        $this->assertTrue($permissions->can('app.post', $admin));
    }

    public function test_hr_approve_blade_replaces_legacy_hr_user(): void
    {
        $this->seedCommunityTables();

        // Legacy HR user 631 is moved into the 人事 role that holds hr.approve.
        $this->assertSame('hr', CommunityMembership::where('user_id', 631)->firstOrFail()->role->key);
        $this->assertTrue(User::findOrFail(631)->canHrApprove());

        // Admin bypasses; PM and partner do not have it.
        $this->assertTrue(User::findOrFail(608)->canHrApprove());
        $this->assertFalse(User::findOrFail(700)->canHrApprove());
        $this->assertFalse(User::findOrFail(701)->canHrApprove());
    }

    public function test_admin_can_sync_role_members(): void
    {
        $this->seedCommunityTables();
        $admin = User::findOrFail(608);
        $boardRole = CommunityRole::where('key', 'board')->firstOrFail();

        // user 700 starts as PM
        $this->assertSame('pm', CommunityMembership::where('user_id', 700)->firstOrFail()->role->key);

        // assigning 700 to the board role moves their membership
        $this->actingAs($admin)
            ->patchJson("/community_context/roles/{$boardRole->id}/members", ['user_ids' => [700]])
            ->assertOk()
            ->assertJsonPath('memberships_count', 1)
            ->assertJsonPath('members.0.id', 700);

        $this->assertSame('board', CommunityMembership::where('user_id', 700)->firstOrFail()->role->key);

        // removing 700 from board sends them back to the base member role
        $this->actingAs($admin)
            ->patchJson("/community_context/roles/{$boardRole->id}/members", ['user_ids' => []])
            ->assertOk()
            ->assertJsonPath('memberships_count', 0);
        $this->assertSame('member', CommunityMembership::where('user_id', 700)->firstOrFail()->role->key);

        // the community can never be left without an admin
        $adminRole = CommunityRole::where('key', 'admin')->firstOrFail();
        $this->actingAs($admin)
            ->patchJson("/community_context/roles/{$adminRole->id}/members", ['user_ids' => []])
            ->assertStatus(422);
    }

    public function test_admin_role_is_protected_and_must_keep_one_member(): void
    {
        $this->seedCommunityTables();
        $admin = User::findOrFail(608);
        $adminRole = CommunityRole::where('key', 'admin')->firstOrFail();
        $memberRole = CommunityRole::where('key', 'member')->firstOrFail();
        $adminMembership = CommunityMembership::where('user_id', $admin->id)->firstOrFail();

        $this->actingAs($admin)
            ->patchJson("/community_context/roles/{$adminRole->id}", [
                'name' => 'Changed',
                'capabilities' => ['board.access'],
            ])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->deleteJson("/community_context/roles/{$adminRole->id}")
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->patchJson("/community_context/memberships/{$adminMembership->id}", [
                'community_role_id' => $memberRole->id,
                'scope' => CommunityMembership::SCOPE_INTERNAL,
            ])
            ->assertUnprocessable();
    }

    public function test_account_chooser_switches_only_remembered_real_accounts(): void
    {
        $this->seedCommunityTables();
        $first = User::findOrFail(700);
        $second = User::findOrFail(701);

        $this->actingAs($first)
            ->postJson('/account_chooser/switch', ['user_id' => $second->id])
            ->assertForbidden();

        $this->actingAs($first)
            ->withSession([AccountChooserController::SESSION_KEY => [$first->id, $second->id]])
            ->postJson('/account_chooser/switch', ['user_id' => $second->id])
            ->assertOk()
            ->assertJsonPath('user.id', $second->id);
    }

    private function createBaseSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->integer('position_id')->nullable();
            $table->integer('partner_flag')->default(0);
            $table->integer('deleted_flag')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('board_records', function ($table) {
            $table->id();
            $table->string('title')->nullable();
            $table->integer('deleted_flag')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedCommunityTables(): void
    {
        DB::table('users')->insert([
            ['id' => 608, 'name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret', 'position_id' => 1, 'partner_flag' => 0, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 700, 'name' => 'PM', 'email' => 'pm@example.com', 'password' => 'secret', 'position_id' => 6, 'partner_flag' => 0, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 701, 'name' => 'Partner', 'email' => 'partner@example.com', 'password' => 'secret', 'position_id' => 14, 'partner_flag' => 1, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 631, 'name' => 'HR', 'email' => 'hr@example.com', 'password' => 'secret', 'position_id' => 9, 'partner_flag' => 0, 'deleted_flag' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach ([$this->communityMigrationPath(), $this->bladeMigrationPath(), $this->hrMigrationPath()] as $path) {
            (include $path)->up();
        }
    }

    private function communityMigrationPath(): string
    {
        return dirname(__DIR__, 2).'/database/migrations/2026_06_24_000001_create_communities_and_memberships.php';
    }

    private function bladeMigrationPath(): string
    {
        return dirname(__DIR__, 2).'/database/migrations/2026_06_25_000001_redefine_role_blades.php';
    }

    private function hrMigrationPath(): string
    {
        return dirname(__DIR__, 2).'/database/migrations/2026_06_25_000003_add_hr_role.php';
    }
}
