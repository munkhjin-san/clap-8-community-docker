<?php

namespace App\Services;

use App\Models\ContactRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ContactEnrichmentService
{
    /**
     * Fetch a company profile (via Gemini + web search) for the contact and
     * store it as HTML in `data`. Returns true when `data` was populated.
     * Safe to call from a queued/after-response job.
     */
    public function enrich(ContactRecord $contact): bool
    {
        if (empty($contact->company_name)) {
            return false;
        }

        $apiKey = config('services.google.gemini_api_key') ?: config('app.gemini_api_key');
        if (empty($apiKey)) {
            return false;
        }

        $markdown = $this->fetchCompanyMarkdown([
            'company_name' => $contact->company_name,
            'url' => $contact->url,
            'address' => $contact->address,
        ], $apiKey);

        if (empty($markdown)) {
            return false;
        }

        $contact->update(['data' => Str::markdown($markdown)]);

        return true;
    }

    private function fetchCompanyMarkdown(array $cardData, string $apiKey): ?string
    {
        $base = rtrim(config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = config('services.google.contact_enrich_model') ?: 'models/gemini-2.5-flash';

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $this->instruction($cardData)],
                    ],
                ],
            ],
            'tools' => [
                ['google_search' => (object) []],
            ],
            'generationConfig' => [
                'temperature' => 0.0,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain',
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(120)
            ->post("{$base}/{$model}:generateContent?key={$apiKey}", $payload);

        if ($response->failed()) {
            return null;
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (empty($text)) {
            return null;
        }

        return preg_replace('/^```(?:html|markdown)?\s*|\s*```$/', '', trim($text));
    }

    private function instruction(array $cardData): string
    {
        $name = $cardData['company_name'] ?? '';
        $url = trim((string) ($cardData['url'] ?? ''));
        $address = $cardData['address'] ?? '';
        $urlLine = $url !== '' ? "ホームページのURL: {$url}" : 'ホームページのURL: （不明）';
        $searchHint = $url !== ''
            ? 'まず指定のホームページURLを参照し、不足する情報は会社名でWeb検索して補ってください。'
            : 'ホームページのURLが不明です。会社名（必要に応じて住所も併用）でWeb検索し、公式サイトや信頼できる情報源を特定してから情報を取得してください。同名の別会社と取り違えないよう、住所や地域で必ず確認してください。特定できない場合は推測せず、その項目を省略してください。';

        return <<<EOD
            会社情報:
            会社名: $name
            $urlLine
            住所: $address

            $searchHint

            上記の企業情報を利用し、会社名や会社のホームページを利用して情報をを取得し、各カテゴリを整理してください。情報はユーザーが企業の基本情報や最新の動向を素早く把握できるよう、簡潔かつ直感的に表示できる形式で提供してください。

            出力形式

            Markdown形式で順番とサブテキスト形を守り、データを取得・整理してください。
            各カラムはわかりやすい見出しに統一し、重複を避けてください。
            カラムの情報が不明や取得出来なかった場合はカラムを消してください。
            情報のソースURLを付記することで、情報の信頼性を担保してください。
            情報が不明なカラムをレスポンスに入れないでください。
            レスポンス例:

            - **基本情報**
                1. 会社名 : {{company_name}}
                2. ロゴ（画像URL） : {{logo_url}}
                ...
            - **事業概要**
            ...

            ---

            注意事項
            1. 情報は必ず、会社名でwebから取得します。名称情報から適当に作成しません
            2. 取得した情報は簡潔かつ正確にまとめてください。
            3. 不明な情報や取得できなかった場合はカラムはいりません。
            4. カテゴリごとに情報を整理し、ユーザーが即座に理解できるようにしてください。
            5. 機密性の高い情報（例: 非公開情報）は取得対象から除外してください。

            取得する情報のカテゴリとカラム
            ※情報が不明な場合カラムを削除してください。
            1. 基本情報
                会社名
                ロゴ（画像URLまたはホームページのfavicon URL大きめの）
                所在地（本社住所、支店情報）
                設立年月日
                代表者名
                従業員数
                資本金
                売上高
                株式情報（上場/未上場、証券コード）
            2. 事業概要
                事業内容（簡潔な概要）
                主な製品・サービス（リスト形式）
                業種分類（例: IT、製造、飲食）
                顧客層（例: 法人向け、個人向け）
                主な取引先
            3. 事業戦略
                ミッション・ビジョン（企業理念や目標）
                戦略目標（例: SDGs、DX推進）
                競争優位性（例: 特許、技術力、ブランド力）
                現在進行中のプロジェクトや取り組み
            4. 最新情報
                最新ニュース（プレスリリース、イベント情報）
                受賞歴や認定（例: ISO認証、業界賞）
                提携・コラボ情報（他社との協業内容）
                株主や取引先の動向
            5. 財務情報
                年度別業績（売上、利益など）
                成長率
                資金調達の履歴や状況
            6. 人事情報
                採用情報
                福利厚生の特徴
                求める人物像
            7. ウェブ・SNS情報
                公式サイトのURL
                SNSアカウント情報（LinkedIn, Twitter, Facebookなど）
                問い合わせ窓口（メールアドレス、電話番号）
            8. その他
                CSR活動（社会貢献活動の内容）
                サステナビリティ情報
                特許や認定技術の詳細
            ---


            EOD;
    }
}
