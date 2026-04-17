<?php

namespace App\Services;

use App\Jobs\Concerns\HandlesContactBatch;
use App\Models\ContactBatch;

class ContactBatchSubmissionService
{
    use HandlesContactBatch;

    public function submit(ContactBatch $batch): void
    {
        $batch->loadMissing('items');

        if ($batch->status !== ContactBatch::STATUS_QUEUED) {
            return;
        }

        $apiKey = config('services.google.gemini_api_key');
        if (empty($apiKey)) {
            $this->markBatchFailed($batch, 'Gemini API key is not configured.');
            return;
        }

        if ($batch->items->isEmpty()) {
            $this->markBatchFailed($batch, 'No batch items found.');
            return;
        }

        $this->submitScan($batch, $apiKey);
    }

    protected function submitScan(ContactBatch $batch, string $apiKey): void
    {
        $instruction = $this->prompt();

        $generationConfig = [
            'responseSchema' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'required' => ['company_name', 'name'],
                    'properties' => [
                        'company_name' => ['type' => 'STRING'],
                        'name' => ['type' => 'STRING'],
                        'position' => ['type' => 'STRING'],
                        'address' => ['type' => 'STRING'],
                        'phone' => ['type' => 'STRING'],
                        'email' => ['type' => 'STRING'],
                        'fax' => ['type' => 'STRING'],
                        'url' => ['type' => 'STRING'],
                        'company_info' => ['type' => 'STRING'],
                    ],
                ],
            ],
            'temperature' => 0,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 8192,
        ];

        $requests = $this->buildScanRequests($batch, $instruction, $generationConfig);

        if (empty($requests)) {
            $this->markBatchFailed($batch, 'No valid batch requests could be created.');
            return;
        }

        $payload = [
            'batch' => [
                'displayName' => 'contact-scan-' . now()->format('YmdHis'),
                'model' => 'models/gemini-3-flash-preview',
                'inputConfig' => [
                    'requests' => [
                        'requests' => $requests,
                    ],
                ],
            ],
        ];

        $this->logEntry($batch, 'scan_submit', 'Submitting scan batch.', ['request_count' => count($requests)], 'models/gemini-3-flash-preview');

        $operation = $this->startGeminiBatch($batch, $apiKey, 'models/gemini-3-flash-preview', $payload);

        $batch->update([
            'status' => ContactBatch::STATUS_SCANNING,
            'scan_operation' => $operation['name'] ?? null,
            'scan_attempts' => 0,
            'scan_requested_at' => now(),
            'scan_completed_at' => null,
            'error' => null,
        ]);
    }

    protected function prompt(): string
    {
        return <<<EOD
            あなたは厳密なデータ抽出者かつウェブリサーチャーです。次の2つを行ってください。
            (1) 名刺OCR結果（JSON、内部に text_raw を含む）から連絡先情報を抽出する。
            (2) (1)で判明した「会社名」または「公式サイトURL」だけを用いて公的なウェブ情報を調査し、日本語のMarkdownで簡潔な会社情報を作成する。

            ## 入力
            - business_card_json（文字列化されたJSON）: {{business_card_json}}
            - 注意:
                - これは単一の名刺を表すJSONです。
                - `text_raw` に会社情報が含まれる可能性があります。
            - 会社名と公式サイトURLの両方が取得できない場合は、ウェブ調査は行わず、連絡先データのみ返してください。

            ## 出力（厳守）
            単一のJSONオブジェクトのみを返してください。余分な文章は禁止。

            {
            "name": "氏名",
            "company_name": "会社名",
            "position": "役職",
            "address": "住所",
            "phone": "電話番号",
            "email": "メールアドレス",
            "fax": "FAX",
            "url": "ホームページURL",
            "company_info": "MARKDOWN_STRING"
            }

            ### 連絡先フィールドの規則
            - 抽出元は business_card_json のみ（推測・創作禁止）。
            - 見つからない項目は空文字 "" とする（nullや省略は禁止）。
            - 明白な範囲でのみ正規化（電話の空白整形、メールの小文字化など）。曖昧なら無理に整形しない。

            ### company_info（Markdown文字列）の規則
            - 名刺から会社名または公式サイトURLが確定できない場合、company_info は空文字 "" とする。
            - 確定できた場合は、公式社名・公式サイトを起点に「公開情報のみ」を調査し、日本語のMarkdownでコンパクトに記述する。検証できた項目だけを掲載し、未知の項目は丸ごと省略。
            - 各項目に必ずソースURLを付記（公式サイト・プレスリリース・信頼できる報道・有価証券報告書など）。
            - 事実ベースで簡潔に。誇張・推測・宣伝調は不要。

            #### Markdownの形式と順序（不明な項目・セクションは削除）
            - **基本情報**
            1. 会社名 : {{company_name}}
            2. ロゴ（画像URL） : {{logo_url}}
            3. 所在地 : {{hq_address}}
            4. 設立年月日 : {{founded_date}}
            5. 代表者名 : {{ceo_name}}
            6. 従業員数 : {{employees}}
            7. 資本金 : {{capital}}
            8. 売上高 : {{revenue}}
            9. 株式情報 : {{listing_status}} {{ticker_or_code}}

            - **事業概要**
            1. 事業内容 : {{brief_description}}
            2. 主な製品・サービス : {{products_services_list}}
            3. 業種分類 : {{industry}}
            4. 顧客層 : {{customer_segments}}
            5. 主な取引先 : {{key_clients}}

            - **事業戦略**
            1. ミッション・ビジョン : {{mission_vision}}
            2. 戦略目標 : {{strategic_goals}}
            3. 競争優位性 : {{moats}}
            4. 進行中の取り組み : {{initiatives}}

            - **最新情報**
            1. 最新ニュース : {{headline}}（日付: {{news_date}}）
            2. 受賞・認定 : {{awards}}
            3. 提携・コラボ : {{partnerships}}
            4. 株主・取引先の動向 : {{stakeholder_updates}}

            - **財務情報**
            1. 年度別業績 : {{yearly_perf_list}}
            2. 成長率 : {{growth_rates}}
            3. 資金調達 : {{financing_history}}

            - **人事情報**
            1. 採用情報 : {{careers_page_url}}
            2. 福利厚生の特徴 : {{benefits}}
            3. 求める人物像 : {{talent_profile}}

            - **ウェブ・SNS情報**
            1. 公式サイト : {{official_site_url}}
            2. SNS : {{sns_list}}（LinkedIn/Twitter/Facebook/YouTube など）
            3. 問い合わせ窓口 : {{contact_email_or_phone}}

            - **その他**
            1. CSR活動 : {{csr}}
            2. サステナビリティ : {{sustainability}}
            3. 特許・認定技術 : {{patents_tech}}

            ### ソース方針
            - 必ず「会社名」で公式情報を特定し、各項目の末尾にソースURLを付与。
            - 信頼性の低い情報は除外。非公開情報や推測は不可。
            - 不明な項目はMarkdownから削除（空欄で残さない）。

            ### 例外処理
            - 同名企業が複数ある場合、名刺情報（URL・所在地・電話等）と照合し、最も一致する企業に限定。
            - 旧社名が見つかった場合は、現行の公式社名に正規化し、必要なら旧称を括弧補足。
            - 最新ニュースは可能なら直近6〜12カ月を優先し、日付を明示。

            ### 返却前チェック
            1) 連絡先フィールドは全て文字列（不明は ""）。
            2) 調査不可なら "company_info": ""。
            3) 調査成功なら "company_info" は単一のMarkdown文字列で、既知項目のみ・各項目にソースURL付き。
            4) 返却は上記JSONオブジェクトのみ。余計なテキスト出力は禁止。
        EOD;
    }
}
