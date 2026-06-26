<?php

namespace App\Services\Community;

/**
 * The closed, code-owned catalog of permission "blades" (rules).
 *
 * A blade is one authorization decision that exists in the code. Community
 * admins map roles -> blades in the permission matrix; only a developer adds a
 * blade (when they add a new code gate). This is the single source of truth and
 * replaces the old CommunityCapabilityCatalog + CommunityScopeCatalog pair.
 *
 * Blades come in two kinds:
 *  - app    : "can this role use this app at all" (visibility). This is how
 *             restricted account types (partner / registered) are expressed —
 *             they are just roles that hold fewer app blades.
 *  - action : an elevated capability inside an app, configurable per role.
 *
 * Two kinds of decision are intentionally NOT blades:
 *  - admin-only actions      -> gated by isAdmin() (the fixed super role).
 *  - relational / ownership  -> gated in code (PM-of-this-project, owns-this-
 *                               asset, authored-this). Never a checkbox.
 */
class CommunityBladeCatalog
{
    /**
     * @return array<int, array{key: string, name: string, description: string, kind: string, blades: array<int, array{key: string, name: string, description: string, kind: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'apps',
                'name' => 'アプリ',
                'description' => 'このロールが利用できるアプリ（画面）を指定します。チェックを外すとそのアプリは表示されません。ダッシュボードとチャットは全員が利用できる標準アプリのため、ここには含まれません（ダッシュボード内の各カードは対応アプリの権限に連動します）。',
                'blades' => [
                    self::blade('app.project', 'プロジェクト', 'プロジェクト一覧・詳細を表示します。'),
                    self::blade('app.schedule', 'スケジュール', 'スケジュール・カレンダーを利用します。'),
                    self::blade('app.timesheet', 'タイムシート', '勤怠・シフト画面を利用します。'),
                    self::blade('app.learning', 'ラーニング', '学習コンテンツを利用します。'),
                    self::blade('app.post', '投稿', '投稿・チャレンジ・ナレッジを利用します。'),
                    self::blade('app.contact', 'コンタクト', '連絡先・コンタクト画面を利用します。'),
                    self::blade('app.notice', 'お知らせ', 'お知らせを閲覧します。'),
                    self::blade('app.asset', '物品', '備品・物品画面を利用します。'),
                    self::blade('app.support', 'サポート', 'サポート・問い合わせ画面を利用します。'),
                    self::blade('app.form', 'フォーム', 'フォーム・アンケートを利用します。'),
                ],
            ],
            [
                'key' => 'actions',
                'name' => '権限・操作',
                'description' => 'アプリ内での管理・承認などの操作権限です。担当プロジェクト単位の権限（PM 等）や所有者本人のみの操作はコード側で判定されるため、ここには含まれません。',
                'blades' => [
                    self::blade('project.approve', '役員承認・全社閲覧', '役員としてプロジェクトを承認し、全社のプロジェクトを閲覧します。', 'action'),
                    self::blade('finance.manage', '予算・収支編集', 'プロジェクトの予算・収支を編集します。', 'action'),
                    self::blade('finance.analyze', '収支AI分析', '収支のAI分析・経営向けチャットを利用します。', 'action'),
                    self::blade('notice.manage', 'お知らせ管理', 'お知らせの作成・編集・削除を行います。', 'action'),
                    self::blade('timesheet.manage_all', '全社勤怠管理', '全メンバーの勤怠・シフト・残業を確認・承認します。', 'action'),
                    self::blade('support.inbox.view', 'サポート相談閲覧', 'サポート相談ボックスの全件を閲覧します。', 'action'),
                    self::blade('hr.approve', '人事承認・確認事項', '人事として月次目標の確認・閲覧、プロジェクトメンバー配属、各種申請などの人事業務を行います。', 'action'),
                    self::blade('board.create', 'ボード作成', '新しいボード（グループ）を作成します。', 'action'),
                ],
            ],
        ];
    }

    /**
     * Every valid blade key (for validation).
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => collect($group['blades'])->pluck('key'))
            ->values()
            ->all();
    }

    /**
     * Default blade sets for the seeded starter roles. Reproduces today's
     * behavior so the migration is behavior-preserving.
     *
     * @return array<string, array<int, string>>
     */
    public static function roleDefaults(): array
    {
        $apps = collect(self::groups()[0]['blades'])->pluck('key')->all();
        $actions = collect(self::groups()[1]['blades'])->pluck('key')->all();

        $without = fn (array $list, array $remove) => array_values(array_diff($list, $remove));

        return [
            // Admin is the fixed super role; it bypasses every gate via isAdmin(),
            // but we still grant the full set so the frontend reflects it.
            'admin' => array_merge($apps, $actions),
            'board' => array_merge($apps, [
                'project.approve', 'finance.manage', 'finance.analyze', 'notice.manage', 'board.create',
            ]),
            'pm' => array_merge($apps, ['finance.analyze', 'board.create']),
            'member' => array_merge($apps, ['board.create']),
            // Registered staff: every app except posts / learning / contact.
            'registered' => array_merge(
                $without($apps, ['app.post', 'app.learning', 'app.contact']),
                ['board.create']
            ),
            // Partner (external): minimal read-mostly access, no creation rights.
            // (Dashboard + Chat are built-in and always available.)
            'partner' => ['app.schedule', 'app.notice'],
            // HR: full app access plus the human-resources approval blade.
            'hr' => array_merge($apps, ['board.create', 'hr.approve']),
        ];
    }

    /**
     * @return array{key: string, name: string, description: string, kind: string}
     */
    private static function blade(string $key, string $name, string $description, string $kind = 'app'): array
    {
        return compact('key', 'name', 'description', 'kind');
    }
}
