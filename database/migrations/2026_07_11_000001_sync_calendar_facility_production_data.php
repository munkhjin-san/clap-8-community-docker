<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rooms = [
            '本社会議室',
            '本社休憩室',
            '大阪会議室',
            '東京会議室',
            '仙台会議室',
            '青森会議室',
            'フジメンビル',
        ];
        $cars = [
            '福岡582く5617 ホンダライフ',
            '福岡582え8686 ダイハツミラ',
            '福岡580と5654 オッティ',
            '福岡480わ3206 クリッパー',
            '福岡480ね5019 バン',
            '福岡480ね5020 バン',
            '鹿児島582そ6650 ミライース',
            '福岡582ち7350',
            'なにわ502の1116',
            '大阪581わ707（ﾚﾝﾀｶｰ）',
            '仙台580ひ6191',
            '福岡582そ1234',
            '鹿児島582そ8143',
        ];
        $zoomAccounts = [
            [
                'label' => 'Zoom1',
                'host_email' => 'zoom1@glowd.co.jp',
                'account_id' => 'NIv1ZAkIRdCnjX7uddQLAQ',
                'client_id' => 'fwZ711P6R5C47R5pb4zugg',
            ],
            [
                'label' => 'Zoom2',
                'host_email' => 'zoom2@glowd.co.jp',
                'account_id' => 'NIv1ZAkIRdCnjX7uddQLAQ',
                'client_id' => 'ozsT8JcdQpKLhdbdVPMZzg',
            ],
            [
                'label' => 'Zoom3',
                'host_email' => 'zoom3@glowd.co.jp',
                'account_id' => 'NIv1ZAkIRdCnjX7uddQLAQ',
                'client_id' => '62FVOmH6SZu1rVpPxiZaFw',
            ],
        ];

        foreach ($rooms as $slot => $label) {
            DB::table('calendar_facilities')->upsert(
                [[
                    'type' => 'room',
                    'slot' => $slot,
                    'label' => $label,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['type', 'slot'],
                ['label', 'active', 'updated_at']
            );
        }

        foreach ($cars as $slot => $label) {
            DB::table('calendar_facilities')->upsert(
                [[
                    'type' => 'car',
                    'slot' => $slot,
                    'label' => $label,
                    'active' => $slot !== 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['type', 'slot'],
                ['label', 'active', 'updated_at']
            );
        }

        foreach ($zoomAccounts as $slot => $account) {
            DB::table('zoom_accounts')->upsert(
                [[
                    'slot' => $slot,
                    ...$account,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['slot'],
                ['label', 'host_email', 'account_id', 'client_id', 'active', 'updated_at']
            );
        }
    }

    public function down(): void
    {
        // Production reference data is intentionally retained on rollback.
    }
};
