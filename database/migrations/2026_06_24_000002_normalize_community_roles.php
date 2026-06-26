<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ADMIN_CAPABILITIES = [
        'community.manage',
        'role.manage',
        'user.manage',
        'admin.access',
        'board.access',
        'dashboard.access',
        'post.access',
        'learning.access',
        'contact.access',
        'notice.manage',
        'project.access',
        'project.manage',
        'project.approve',
        'project.manage_assigned',
        'incident.manage',
        'finance.manage',
        'timesheet.access',
        'timesheet.self',
        'timesheet.manage',
        'timesheet.approve_assigned',
        'support.access',
        'asset.self',
        'asset.manage',
        'file.manage',
    ];

    private const ROLE_DEFAULTS = [
        'admin' => ['管理者', 10, true],
        'board' => ['役員', 20, false],
        'pm' => ['PM', 30, false],
        'member' => ['メンバー', 40, false],
        'registered' => ['登録社員', 50, false],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('communities') || !Schema::hasTable('community_roles') || !Schema::hasTable('community_user')) {
            return;
        }

        DB::table('communities')->select('id')->orderBy('id')->chunkById(100, function ($communities) {
            foreach ($communities as $community) {
                $this->normalizeCommunity((int) $community->id);
            }
        });
    }

    public function down(): void
    {
    }

    private function normalizeCommunity(int $communityId): void
    {
        $adminRole = DB::table('community_roles')
            ->where('community_id', $communityId)
            ->where('key', 'admin')
            ->first();

        $ownerRole = DB::table('community_roles')
            ->where('community_id', $communityId)
            ->where('key', 'owner')
            ->first();

        if ($ownerRole && !$adminRole) {
            DB::table('community_roles')
                ->where('id', $ownerRole->id)
                ->update([
                    'key' => 'admin',
                    'name' => '管理者',
                    'sort_order' => 10,
                    'capabilities' => json_encode(self::ADMIN_CAPABILITIES, JSON_UNESCAPED_UNICODE),
                    'is_system' => true,
                    'updated_at' => now(),
                ]);
        } elseif ($ownerRole && $adminRole) {
            DB::table('community_user')
                ->where('community_role_id', $ownerRole->id)
                ->update([
                    'community_role_id' => $adminRole->id,
                    'updated_at' => now(),
                ]);

            DB::table('community_roles')->where('id', $ownerRole->id)->delete();
        }

        foreach (self::ROLE_DEFAULTS as $key => [$name, $sortOrder, $isSystem]) {
            DB::table('community_roles')
                ->where('community_id', $communityId)
                ->where('key', $key)
                ->update([
                    'name' => $name,
                    'sort_order' => $sortOrder,
                    'is_system' => $isSystem,
                    'updated_at' => now(),
                    ...($key === 'admin' ? [
                        'capabilities' => json_encode(self::ADMIN_CAPABILITIES, JSON_UNESCAPED_UNICODE),
                    ] : []),
                ]);
        }
    }
};
