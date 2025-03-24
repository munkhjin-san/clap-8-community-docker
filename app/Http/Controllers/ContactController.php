<?php

namespace App\Http\Controllers;

use App\Models\ContactRecord;
use App\Models\ContactType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
class ContactController extends Controller
{

    protected $gemini_url;

    public function __construct()
    {
        $this->gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';
    }
    public function get_contact_types(){
        $types = ContactType::all();
        return response()->json($types);
    }
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    private function get_company_name($image)
    {
        $apiKey = config('app.gemini_api_key');
    
        if (empty($apiKey)) {
            throw ValidationException::withMessages(['message' => 'APIキーが設定されていません。']);
        }
        $instruction = <<<EOD
            名称画像ファイルから[氏名、会社名、役職、住所、電話番号、メールアドレス、FAX、ホームページURL]を出力してください。
            情報が見つからない場合は空白にしてください。
            例: {name: 氏名, company_name: 会社名, position: 役職, address: 住所, phone: 電話番号, email: メールアドレス, fax: FAX, url: ホームページURL}'
        EOD;
        // Prepare payload
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ["text" => $instruction],
                        [
                            'inline_data' => [
                                'data' => $image,
                                'mimeType' => 'image/webp',
                            ],
                        ]
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1.0,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_name' => ['type' => 'string'],
                        'name' => ['type' => 'string'],
                        'position' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                        'phone' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                        'fax' => ['type' => 'string'],
                        'url' => ['type' => 'string']
                    ],
                    'required' => ['company_name', 'name'],
                ]
            ],
        ];
    
        // Send request
        $url = $this->gemini_url;
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
                        ->post("$url?key={$apiKey}", $payload);
    
        if (!$response->successful()) {
            throw ValidationException::withMessages(['message' => '画像ファイルの読み取りに失敗しました。']);
        }
    
        // Parse the response
        $data = $response->json();
        $text = data_get($data, 'candidates.0.content.parts.0.text');
    
        if (empty($text)) {
            throw ValidationException::withMessages(['message' => 'データ出力できません。']);
        }
    
        // Clean up and decode JSON
        $text = preg_replace('/^json\s+/i', '', trim($text));
        $jsonData = json_decode($text, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages(['message' => '無効レスポンス。']);
        }
    
        // Extract company name
        $name = $jsonData['name'] ?? null;
        $companyName = $jsonData['company_name'] ?? null;
    
        if (empty($name) || empty($companyName)) {
            throw ValidationException::withMessages(['message' => '企業名を認識できません。']);
        }
    
        return $jsonData;
    }
    public function company_data_gemini($cardData){
        $apiKey = config('app.gemini_api_key');
        if (empty($apiKey)) {
            throw ValidationException::withMessages(['message' => 'APIキーが設定されていません。']);
        }
        $instruction = $this->instruction($cardData);
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $instruction,
                        ],
                    ],
                ],
            ],
            'tools' => [
                'google_search_retrieval' => [
                    'dynamic_retrieval_config' => [
                        'mode' => 'MODE_DYNAMIC',
                        'dynamic_threshold' => 0.3,
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain'
            ],
        ];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-8b:generateContent?key=$apiKey";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);
        $data = $response->json();
        $text = data_get($data, 'candidates.0.content.parts.0.text');        
        if (!$text) {
            throw ValidationException::withMessages(['message' => 'データ出力できません。']);
        }
        $cleanJson = preg_replace('/^```html\n|\n```$/', '', $text);
        return $cleanJson;
    }

    public function scan_card(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:8000',
        ]);
        $file = $request->file('image');
        $base64Image = base64_encode(file_get_contents($file));

        $cardData = $this->get_company_name($base64Image);
        $companyData = $this->company_data_gemini($cardData);
        
        return response()->json(['text' => $companyData, 'data' => $cardData]);
    }
    public function contact_list(Request $request)
    {
        
        $contacts = ContactRecord::orderBy('created_at', 'desc')->with(['updater', 'creator', 'type'])->get();
        return response()->json($contacts);
    }
    private function path_generator()
    {
        $timestamp = time();
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $iconId = $timestamp . $randomString;
        if (strlen($iconId) > 15) {
            $iconId = substr($iconId, 0, 15);
        }
        return $iconId;
    }
    public function upload_name_card(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
        ]);
        $file = $request->file('image');
        $img = Image::read($file);
        $unique_path = $this->path_generator();
        File::isDirectory(storage_path('app/card_files')) or File::makeDirectory(storage_path('app/card_files'), 0755, true, true);
        $img->toWebp()->save(storage_path("app/card_files/$unique_path.webp"));
        return response()->json($unique_path);
    }
    public function delete_contact(Request $request){
        $request->validate([
            "id" => 'required'
        ]);
        ContactRecord::findOrFail($request->id)->delete();
        return response('ok');
    }
    public function create_contact(Request $request)
    {

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:100',
            // 'name_kana' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:100',
            // 'company_name_kana' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:250',
            'phone' => 'nullable|string|max:100',
            'email' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'strategy' => 'nullable|string',
            'card_path' => 'nullable|string',
            'data' => 'nullable|string',
            // 'contact_type_id' => 'nullable|integer'
        ]);



        $id = $request->id ?? null;

        $record = ContactRecord::updateOrCreate(["id" => $id], $validatedData);

        if ($id == null) {
            $record->update([
                'created_by' => $this->active_user()->id
            ]);
        }
        $type_id = $request->contact_type_id;
        if($type_id == -1){
            $type = ContactType::firstOrCreate(["title" => $request->pseudo_type]);
            $type_id = $type->id;
        }
        $record->update([
            'updated_by' => $this->active_user()->id,
            'contact_type_id' => $type_id
        ]);

        return response('OK');
    }
    private function instruction($cardData)
    {
        
        $name = $cardData['company_name'];
        $url = $cardData['url'];
        $address = $cardData['address'];
        return <<<EOD
            会社情報:
            会社名: $name
            ホームページのURL: $url
            住所: $address

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
