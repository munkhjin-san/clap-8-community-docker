<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Capability migration — Lunch-challenge expansion.
 *
 * Grants `benefit.lunch_challenge` to the eligible employment/authority roles so
 * the OpenAiController eligibility check moves from position_id (< 13 || == 16)
 * to hasCapability('benefit.lunch_challenge'). Excludes 転籍社員(13)/協力会社(14)/
 * 登録社員(15) and the `member` catch-all (dummy/system accounts: 家族/関係者/
 * お知らせアカウント/etc., which the old position-null artifact wrongly included).
 */
return new class extends Migration
{
    private const SLUG = 'glowd';
    private const CAPABILITY = 'benefit.lunch_challenge';
    private const ROLES = ['board', 'pm', 'regular_employee', 'contract_employee', 'project_leader', 'hr', 'admin'];

    public function up(): void
    {
        $this->apply(true);
    }

    public function down(): void
    {
        $this->apply(false);
    }

    private function apply(bool $grant): void
    {
        $communityId = DB::table('communities')->where('slug', self::SLUG)->value('id');
        if (!$communityId) {
            return;
        }

        foreach (self::ROLES as $key) {
            $role = DB::table('community_roles')->where('community_id', $communityId)->where('key', $key)->first();
            if (!$role) {
                continue;
            }
            $caps = json_decode($role->capabilities, true) ?: [];
            $has = in_array(self::CAPABILITY, $caps, true);

            if ($grant && !$has) {
                $caps[] = self::CAPABILITY;
            } elseif (!$grant && $has) {
                $caps = array_filter($caps, fn ($c) => $c !== self::CAPABILITY);
            } else {
                continue;
            }

            DB::table('community_roles')->where('id', $role->id)->update([
                'capabilities' => json_encode(array_values($caps), JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }
};
