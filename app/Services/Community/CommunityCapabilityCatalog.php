<?php

namespace App\Services\Community;

/**
 * The closed, code-owned catalog of permission "capabilities" (rules).
 *
 * A capability is one authorization decision that exists in the code. Community
 * admins map roles -> capabilities in the permission matrix; only a developer adds a
 * capability (when they add a new code gate). This is the single source of truth and
 * replaces the old CommunityCapabilityCatalog + CommunityScopeCatalog pair.
 *
 * Blades come in two kinds:
 *  - app    : "can this role use this app at all" (visibility). This is how
 *             restricted account types (partner / registered) are expressed —
 *             they are just roles that hold fewer app capabilities.
 *  - action : an elevated capability inside an app, configurable per role.
 *
 * Two kinds of decision are intentionally NOT capabilities:
 *  - admin-only actions      -> gated by isAdmin() (the fixed super role).
 *  - relational / ownership  -> gated in code (PM-of-this-project, owns-this-
 *                               asset, authored-this). Never a checkbox.
 */
class CommunityCapabilityCatalog
{
    /**
     * @return array<int, array{key: string, name: string, description: string, kind: string, capabilities: array<int, array{key: string, name: string, description: string, kind: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'apps',
                'name' => 'アプリ',
                'description' => 'このロールが利用できるアプリ（画面）を指定します。チェックを外すとそのアプリは表示されません。ダッシュボードとチャットは全員が利用できる標準アプリのため、ここには含まれません（ダッシュボード内の各カードは対応アプリの権限に連動します）。',
                'capabilities' => [
                    self::capability('app.project', 'プロジェクト', 'プロジェクト一覧・詳細を表示します。'),
                    self::capability('app.schedule', 'スケジュール', 'スケジュール・カレンダーを利用します。'),
                    self::capability('app.timesheet', 'タイムシート', '勤怠・シフト画面を利用します。'),
                    self::capability('app.learning', 'ラーニング', '学習コンテンツを利用します。'),
                    self::capability('app.post', '投稿', '投稿・チャレンジ・ナレッジを利用します。'),
                    self::capability('app.contact', 'コンタクト', '連絡先・コンタクト画面を利用します。'),
                    self::capability('app.notice', 'お知らせ', 'お知らせを閲覧します。'),
                    self::capability('app.asset', '物品', '備品・物品画面を利用します。'),
                    self::capability('app.support', 'サポート', 'サポート・問い合わせ画面を利用します。'),
                    self::capability('app.form', 'フォーム', 'フォーム・アンケートを利用します。'),
                ],
            ],
            [
                'key' => 'actions',
                'name' => '権限・操作',
                'description' => 'アプリ内での管理・承認などの操作権限です。担当プロジェクト単位の権限（PM 等）や所有者本人のみの操作はコード側で判定されるため、ここには含まれません。',
                'capabilities' => [
                    self::capability('project.approve', '役員承認・全社閲覧', '役員としてプロジェクトを承認し、全社のプロジェクトを閲覧します。', 'action'),
                    self::capability('finance.manage', '予算・収支編集', 'プロジェクトの予算・収支を編集します。', 'action'),
                    self::capability('finance.analyze', '収支AI分析', '収支のAI分析・経営向けチャットを利用します。', 'action'),
                    self::capability('notice.manage', 'お知らせ管理', 'お知らせの作成・編集・削除を行います。', 'action'),
                    self::capability('timesheet.manage_all', '全社勤怠管理', '全メンバーの勤怠・シフト・残業を確認・承認します。', 'action'),
                    self::capability('support.inbox.view', 'サポート相談閲覧', 'サポート相談ボックスの全件を閲覧します。', 'action'),
                    self::capability('hr.approve', '人事承認・確認事項', '人事として月次目標の確認・閲覧、プロジェクトメンバー配属、各種申請などの人事業務を行います。', 'action'),
                    self::capability('board.create', 'ボード作成', '新しいボード（グループ）を作成します。', 'action'),
                ],
            ],
            [
                'key' => 'benefits',
                'name' => '福利厚生',
                'description' => '対象となる雇用形態（ロール）に紐づく福利厚生プログラムです。雇用形態のロールに付与します。',
                'capabilities' => [
                    self::capability('benefit.refresh', 'リフレッシュ休暇', 'リフレッシュ休暇プログラムの対象です。', 'benefit'),
                    self::capability('benefit.lunch_challenge', 'ランチチャレンジ', 'ランチチャレンジ（昼食チャレンジ）の対象です。', 'benefit'),
                ],
            ],
        ];
    }

    /**
     * Every valid capability key (for validation).
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => collect($group['capabilities'])->pluck('key'))
            ->values()
            ->all();
    }

    /**
     * Default capability sets for the seeded starter roles. Reproduces today's
     * behavior so the migration is behavior-preserving.
     *
     * @return array<string, array<int, string>>
     */
    public static function roleDefaults(): array
    {
        $apps = collect(self::groups()[0]['capabilities'])->pluck('key')->all();
        $actions = collect(self::groups()[1]['capabilities'])->pluck('key')->all();
        $benefits = collect(self::groups()[2]['capabilities'])->pluck('key')->all();

        $without = fn (array $list, array $remove) => array_values(array_diff($list, $remove));

        // Base internal-staff set; the employment-type roles extend it.
        $member = array_merge($apps, ['board.create']);

        return [
            // Admin is the fixed super role; it bypasses every gate via isAdmin(),
            // but we still grant the full set so the frontend reflects it (incl. benefits).
            'admin' => array_merge($apps, $actions, $benefits),
            'board' => array_merge($apps, [
                'project.approve', 'finance.manage', 'finance.analyze', 'notice.manage', 'board.create',
                'benefit.lunch_challenge',
            ]),
            'pm' => array_merge($apps, ['finance.analyze', 'board.create', 'benefit.refresh', 'benefit.lunch_challenge']),
            // Catch-all internal staff (unmapped positions: dummy/system accounts); no benefits.
            'member' => $member,
            // Employment-type roles (seeded from position; HR truth stays on position_records).
            'regular_employee' => array_merge($member, ['benefit.refresh', 'benefit.lunch_challenge']),
            'contract_employee' => array_merge($member, ['benefit.refresh', 'benefit.lunch_challenge']),
            'project_leader' => array_merge($member, ['benefit.refresh', 'benefit.lunch_challenge']),
            'transferred_employee' => $member,
            // Registered staff: every app except posts / learning / contact.
            'registered' => array_merge(
                $without($apps, ['app.post', 'app.learning', 'app.contact']),
                ['board.create']
            ),
            // Partner (external): minimal read-mostly access, no creation rights.
            // (Dashboard + Chat are built-in and always available.)
            'partner' => ['app.schedule', 'app.notice'],
            // HR: full app access plus the human-resources approval capability + benefits.
            'hr' => array_merge($apps, ['board.create', 'hr.approve', 'benefit.refresh', 'benefit.lunch_challenge']),
        ];
    }

    /**
     * @return array{key: string, name: string, description: string, kind: string}
     */
    private static function capability(string $key, string $name, string $description, string $kind = 'app'): array
    {
        return compact('key', 'name', 'description', 'kind');
    }
}
