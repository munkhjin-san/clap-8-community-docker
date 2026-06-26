<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_COMMUNITY_NAME = 'グラウド株式会社';
    private const DEFAULT_COMMUNITY_SLUG = 'glowd';

    private const COMMUNITY_TABLES = [
        'board_records',
        'message_records',
        'project_records',
        'project_goals',
        'project_members',
        'project_member_roles',
        'task_records',
        'timecard_records',
        'shift_records',
        'attendance_records',
        'calendar_records',
        'post_records',
        'asset_records',
        'custom_forms',
        'notice_records',
        'support_records',
        'regulation_records',
        'faq_records',
        'lesson_themes',
        'lesson_materials',
    ];

    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->json('config')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('community_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('capabilities')->nullable();
            $table->json('scopes')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['community_id', 'key']);
        });

        Schema::create('community_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('community_role_id')->nullable()->constrained('community_roles')->nullOnDelete();
            $table->string('scope')->default('internal')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->unique(['community_id', 'user_id']);
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('community_membership_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
            $table->index(['community_id', 'action']);
        });

        $communityId = DB::table('communities')->insertGetId([
            'name' => self::DEFAULT_COMMUNITY_NAME,
            'slug' => self::DEFAULT_COMMUNITY_SLUG,
            'status' => 'active',
            'config' => json_encode(['default' => true], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleIds = $this->seedRoles($communityId);
        $this->seedMemberships($communityId, $roleIds);
        $this->addCommunityColumns($communityId);
    }

    public function down(): void
    {
        foreach (array_reverse(self::COMMUNITY_TABLES) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'community_id')) {
                Schema::table($table, function (Blueprint $table) {
                    try {
                        $table->dropForeign(['community_id']);
                    } catch (Throwable) {
                    }
                    $table->dropColumn('community_id');
                });
            }
        }

        Schema::dropIfExists('community_membership_audit_logs');
        Schema::dropIfExists('community_user');
        Schema::dropIfExists('community_roles');
        Schema::dropIfExists('communities');
    }

    /**
     * @return array<string, int>
     */
    private function seedRoles(int $communityId): array
    {
        $roles = [
            'admin' => ['管理者', 10, ['community.manage', 'role.manage', 'user.manage', 'admin.access', 'board.access', 'dashboard.access', 'post.access', 'learning.access', 'contact.access', 'notice.manage', 'project.access', 'project.manage', 'project.approve', 'project.manage_assigned', 'incident.manage', 'finance.manage', 'timesheet.access', 'timesheet.self', 'timesheet.manage', 'timesheet.approve_assigned', 'support.access', 'asset.self', 'asset.manage', 'file.manage'], ['app.project', 'app.schedule', 'app.timesheet', 'app.learning', 'app.contact', 'app.notice', 'app.asset', 'app.support', 'app.form', 'position.management_hq', 'position.system_development', 'position.hr', 'position.pm', 'position.board'], true],
            'board' => ['役員', 20, ['board.access', 'project.access', 'project.approve', 'timesheet.access', 'dashboard.access'], ['app.project', 'app.schedule', 'app.timesheet', 'app.notice', 'position.board'], false],
            'pm' => ['PM', 30, ['board.access', 'project.access', 'project.manage_assigned', 'timesheet.access', 'timesheet.approve_assigned', 'dashboard.access'], ['app.project', 'app.schedule', 'app.timesheet', 'position.pm'], false],
            'member' => ['メンバー', 40, ['board.access', 'timesheet.self', 'asset.self'], ['app.schedule', 'app.timesheet', 'app.learning', 'app.contact', 'app.notice', 'app.asset', 'app.support', 'app.form'], false],
            'registered' => ['登録社員', 50, ['board.access'], ['app.learning'], false],
        ];

        $ids = [];

        foreach ($roles as $key => [$name, $sortOrder, $capabilities, $scopes, $isSystem]) {
            $ids[$key] = DB::table('community_roles')->insertGetId([
                'community_id' => $communityId,
                'key' => $key,
                'name' => $name,
                'sort_order' => $sortOrder,
                'capabilities' => json_encode($capabilities, JSON_UNESCAPED_UNICODE),
                'scopes' => json_encode($scopes, JSON_UNESCAPED_UNICODE),
                'is_system' => $isSystem,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    /**
     * @param array<string, int> $roleIds
     */
    private function seedMemberships(int $communityId, array $roleIds): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $columns = ['id'];
        foreach (['position_id', 'partner_flag', 'deleted_at', 'deleted_flag'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        DB::table('users')
            ->select($columns)
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($communityId, $roleIds) {
                $rows = [];

                foreach ($users as $user) {
                    if (($user->deleted_at ?? null) !== null || (int) ($user->deleted_flag ?? 0) === 1) {
                        continue;
                    }

                    $positionId = $user->position_id === null ? null : (int) $user->position_id;
                    $scope = match (true) {
                        (int) ($user->partner_flag ?? 0) === 1 => 'partner',
                        $positionId === 15 => 'registered',
                        default => 'internal',
                    };
                    $roleKey = match (true) {
                        in_array((int) $user->id, [608, 610], true) => 'admin',
                        $positionId !== null && $positionId < 6 => 'board',
                        $positionId === 6 => 'pm',
                        $scope === 'registered' => 'registered',
                        default => 'member',
                    };

                    $rows[] = [
                        'community_id' => $communityId,
                        'user_id' => (int) $user->id,
                        'community_role_id' => $roleIds[$roleKey],
                        'scope' => $scope,
                        'is_default' => true,
                        'last_active_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($rows !== []) {
                    DB::table('community_user')->insertOrIgnore($rows);
                }
            });
    }

    private function addCommunityColumns(int $communityId): void
    {
        foreach (self::COMMUNITY_TABLES as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'community_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('community_id')->nullable()->after('id')->index();
            });

            DB::table($tableName)->whereNull('community_id')->update(['community_id' => $communityId]);

            if (DB::connection()->getDriverName() !== 'sqlite') {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreign('community_id')->references('id')->on('communities')->nullOnDelete();
                });
            }
        }
    }
};
