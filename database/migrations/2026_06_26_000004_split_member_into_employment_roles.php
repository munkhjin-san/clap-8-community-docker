<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Capability migration — Refresh pilot (the first employment-role expansion).
 *
 * Splits the compact `member` role into employment-type roles (seeded from
 * position_records, which remain HR truth) and grants the new `benefit.refresh`
 * capability to the eligible ones + pm/hr/admin. After this, refresh eligibility
 * is role-driven (`hasCapability('benefit.refresh')`) instead of
 * ELIGIBLE_POSITION_IDS = [6,11,12,16]. See docs/capability_authorization_model_adr.md.
 */
return new class extends Migration
{
    private const SLUG = 'glowd';

    // new employment roles split out of `member`, keyed by the position they map.
    private const NEW_ROLES = [
        ['key' => 'regular_employee',     'name' => '正社員',                 'position' => 11, 'sort' => 41, 'refresh' => true],
        ['key' => 'contract_employee',    'name' => '契約社員',               'position' => 12, 'sort' => 42, 'refresh' => true],
        ['key' => 'project_leader',       'name' => 'プロジェクトリーダー', 'position' => 16, 'sort' => 43, 'refresh' => true],
        ['key' => 'transferred_employee', 'name' => '転籍社員',               'position' => 13, 'sort' => 44, 'refresh' => false],
    ];

    public function up(): void
    {
        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        $member = DB::table('community_roles')->where('community_id', $communityId)->where('key', 'member')->first();
        if (!$member) {
            return;
        }

        $memberCaps = json_decode($member->capabilities, true) ?: [];

        // 1) Create the employment-type roles (clone member's capabilities + refresh where eligible).
        $newRoleId = [];
        foreach (self::NEW_ROLES as $r) {
            $caps = $r['refresh']
                ? array_values(array_unique([...$memberCaps, 'benefit.refresh']))
                : $memberCaps;

            $id = DB::table('community_roles')
                ->where('community_id', $communityId)->where('key', $r['key'])->value('id');

            if (!$id) {
                $id = DB::table('community_roles')->insertGetId([
                    'community_id' => $communityId,
                    'key' => $r['key'],
                    'name' => $r['name'],
                    'sort_order' => $r['sort'],
                    'capabilities' => json_encode($caps, JSON_UNESCAPED_UNICODE),
                    'scopes' => $member->scopes,
                    'is_system' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $newRoleId[$r['key']] = $id;
        }

        // 2) Grant benefit.refresh to the existing pm / hr / admin roles (so per-user
        //    and query paths agree; admin already bypasses at runtime).
        foreach (['pm', 'hr', 'admin'] as $key) {
            $this->grantCapability($communityId, $key, 'benefit.refresh');
        }

        // 3) Re-seed: move members to their employment role by position. Only rows
        //    currently in `member` are touched (idempotent); unmapped positions stay.
        foreach (self::NEW_ROLES as $r) {
            DB::table('community_user')
                ->where('community_id', $communityId)
                ->where('community_role_id', $member->id)
                ->whereIn('user_id', fn ($q) => $q->select('id')->from('users')->where('position_id', $r['position']))
                ->update(['community_role_id' => $newRoleId[$r['key']], 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        $member = DB::table('community_roles')->where('community_id', $communityId)->where('key', 'member')->first();
        $keys = array_column(self::NEW_ROLES, 'key');
        $roleIds = DB::table('community_roles')->where('community_id', $communityId)->whereIn('key', $keys)->pluck('id');

        if ($member) {
            DB::table('community_user')
                ->where('community_id', $communityId)
                ->whereIn('community_role_id', $roleIds)
                ->update(['community_role_id' => $member->id, 'updated_at' => now()]);
        }

        foreach (['pm', 'hr', 'admin'] as $key) {
            $this->revokeCapability($communityId, $key, 'benefit.refresh');
        }

        DB::table('community_roles')->where('community_id', $communityId)->whereIn('key', $keys)->delete();
    }

    private function grantCapability(int $communityId, string $key, string $capability): void
    {
        $role = DB::table('community_roles')->where('community_id', $communityId)->where('key', $key)->first();
        if (!$role) {
            return;
        }
        $caps = json_decode($role->capabilities, true) ?: [];
        if (!in_array($capability, $caps, true)) {
            $caps[] = $capability;
            DB::table('community_roles')->where('id', $role->id)
                ->update(['capabilities' => json_encode(array_values($caps), JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }

    private function revokeCapability(int $communityId, string $key, string $capability): void
    {
        $role = DB::table('community_roles')->where('community_id', $communityId)->where('key', $key)->first();
        if (!$role) {
            return;
        }
        $caps = array_values(array_filter(json_decode($role->capabilities, true) ?: [], fn ($c) => $c !== $capability));
        DB::table('community_roles')->where('id', $role->id)
            ->update(['capabilities' => json_encode($caps, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }
};
