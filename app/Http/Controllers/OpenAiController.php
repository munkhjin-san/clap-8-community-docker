<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use OpenAI;
use Generator;
class OpenAiController extends Controller
{
    public function prepare(Request $request)
    {
        $validated = $request->validate([
            'text'        => 'required|string',
        ]); 

        $id = (string) Str::uuid();
        Cache::put("ai_correction:$id", $validated, now()->addMinutes(5));

        return response()->json(['id' => $id]);
    }
    public function stream_prompt(Request $request){
        $query = $request->query(); 
        $id = $query['request_id'] ?? null;
        abort_if(!$id, 400, '入力データが見つかりません。');
        
        $input = Cache::pull("ai_correction:$id");        

        abort_if(!$input, 410, 'セッションの有効期限が切れています。');

        $package = [
            'input' => $input['text'] ?? '',
            'prompt' => [
                'id' => '',
                'variables' => []
            ],
        ];

        abort_if(!$package['input'], 400, '入力テキストが見つかりません。');

        Arr::forget($query, 'request_id');

        foreach($query as $key => $value){
            if($key === 'config_key'){
                $configKey = $value;
                $configKey = "services.openai.prompts." . $configKey;
                $promptId = config($configKey);
                abort_if(!$promptId, 400, '無効なconfig_keyです。');

                $package['prompt']['id'] = $promptId;
            } else if($key === 'prompt_id'){
                $promptId = $value;
                abort_if(!$promptId, 400, '無効なprompt_idです。');

                $package['prompt']['id'] = $promptId;
                Arr::forget($query, 'prompt_id');
            }
            else {                
                $package['prompt']['variables'][$key] = $value;
            }
        }
        
        if(empty($package['prompt']['variables'])){
            Arr::forget($package['prompt'], 'variables');
        }
        $apiKey = config('services.openai.api_key');
        if(!$apiKey){
            abort(500, 'OpenAI APIキーが設定されていません。');
        }
        

        $headers = [
            'Content-Type'       => 'text/plain; charset=utf-8',
            'Cache-Control'      => 'no-cache, no-transform',
            'X-Accel-Buffering'  => 'no',             // avoid Nginx buffering
            'Content-Encoding'   => 'none',           // dodge proxy gzip buffering
        ];
        
        $client = OpenAI::client($apiKey);
        return response()->eventStream(function () use ($client, $package, $headers) {
            $stream = $client->responses()->createStreamed($package);
            foreach ($stream as $event) {
                yield json_encode($event);
            }
        }, $headers);
        
    }
    public function non_stream_prompt(Request $request){

        $text = $request['message'] ?? '';
        abort_if(!$text, 400, 'Missing message');

        $apiKey = config('services.openai.api_key');
        $client = OpenAI::client($apiKey);
        $configKey = $request['config_key'] ?? '';
        if(!$configKey){
            abort(400, 'Missing config_key');
        }
        $configKey = "services.openai.prompts." . $configKey;
        $promptId = config($configKey);
        if(!$promptId){
            abort(400, 'Invalid config_key');
        }
        $response = $client->responses()->create([
            'input' => $text,
            'prompt' => [
                "id" => $promptId,
            ],
        ]);

        $reply = '';
        if($response->status !== 'completed') {
            $reply = '申し訳ございませんが現在、リクエストを処理できません。後でもう一度お試しください。';
        } else {
            foreach ($response->output as $output) {
                if(isset($output['role']) && $output['role'] === 'assistant') {
                    foreach ($output['content'] as $content) {
                        $reply .= $content['text'];
                    }
                }
                
            }
        }
        return response()->json($reply);
    }
    public function review_document(Request $request){
        $CRITERIA = [
        '乙' => <<<TXT
        - 乙に対する無制限または上限なしの損害賠償責任、間接・特別損害の包含
        - 甲による一方的な変更・中止・解除が補償なしで可能な条項
        - 乙の既存資産や共通ライブラリまでを含めた知的財産権の譲渡要求
        - 検収基準が不明確、みなし検収がなく支払時期が曖昧
        - 再委託の不合理な制限、過度な守秘・広報禁止
        - その他、乙の立場で重大な不利益
        TXT,
        '甲' => <<<TXT
        - 成果物の品質・仕様・納期に関する基準や検収手続が不明確または甲に不利
        - 乙の再委託・外注に対する甲の承認や管理・報告義務が不十分
        - 成果物や成果知財の帰属・使用範囲が甲に不利（ライセンスが限定的、エスカローション不可等）
        - 解除・変更・中止時の救済や損害賠償の範囲が甲に不利、責任上限が低すぎる
        - 守秘義務・情報セキュリティ・法令遵守（下請法・個情法等）への担保が弱い
        - その他、甲の立場で重大な不利益
        TXT,
        ];
        $data = $request->validate([
            'file' => 'required|file|max:209715',
            'role' => 'required|string',
            'type' => 'required|string',
            'review_type' => 'required|string',
        ]);
        abort_if(!isset($data['file']), 400, 'Missing file');
        $file = $data['file'];
        $apiKey = config('services.openai.api_key');
        $client = OpenAI::client($apiKey);
        $review_type = $data['review_type'];
        $configKey = $review_type === 'deep' ? "services.openai.prompts.legal_deep_review" : "services.openai.prompts.legal_quick_review";
        $promptId = config($configKey);
        $mime = $file->getClientMimeType();
        $base64String = base64_encode(file_get_contents($file));
        $fileName = $file->getClientOriginalName();
        
        if(!$promptId){
            abort(400, 'Invalid config_key');
        }
        $role = $data['role'];
        $type = $data['type'];
        $criteria = $CRITERIA[$role] ?? $CRITERIA['乙'];
        
        $resp = $client->responses()->create([
            'prompt' => [
                "id" => $promptId,
                'variables' => [
                    'role'          => $role,          // '甲' or '乙'
                    'contract_type' => $type,          // '業務委託契約' etc.
                    'criteria'      => $criteria,  // the bullet list string
                ],
            ],
            'metadata' => [
                'role' => $role,
                'contract_type' => $type,
            ],
            'input' => [
                [
                    "role" => "user",
                    "content" => [
                        [
                            'type' => "input_file", 
                            "file_data" => "data:${mime};base64,${base64String}",
                            "filename" => $fileName
                        ],
                    ],
                ],
            ],
        ]);
        $text = $resp->output?->content[1]?->text
          ?? $resp->outputText
          ?? null;
        $json = json_decode($text ?? '', true);
        $filePath = null;
        if ($review_type !== 'deep') {
            $filePath = $file->storeAs('project_files/contracts/' . Str::uuid()->toString(), $fileName);
        }
        return response()->json([
            'status' => 'ok',
            'raw'    => $text,     // in case you want to see it
            'json'   => $json ?? null,
            'path' => $filePath,
            'role' => $role,
            'type' => $type
        ]);
    }
    public function stream_tts(Request $request){
        $request->validate([
            'text' => 'required|string|max:40960', // Increased limit since we'll chunk it
        ]);

        $text = $request->input('text');
        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            abort(500, 'OpenAI APIキーが設定されていません。');
        }

        $client = OpenAI::client($apiKey);
        $model = 'gpt-4o-mini-tts';
        $voice = 'nova';
        $format = 'mp3';

        return response()->stream(function () use ($client, $model, $voice, $format, $text) {
            try {
                // Disable buffering
                if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
                @ini_set('zlib.output_compression', '0');
                @ini_set('output_buffering', 'off');
                while (ob_get_level() > 0) { @ob_end_flush(); }
                @ob_implicit_flush(1);

                // Split text into chunks (1400 characters for Japanese)
                $chunks = $this->chunkText($text, 1400);

                foreach ($chunks as $chunk) {
                    $stream = $client->audio()->speechStreamed([
                        'model'  => $model,
                        'voice'  => $voice,
                        'input'  => $chunk,
                        'instructions' => "発音: ほとんど日本語ですので、日本語の発音に注意ください。ネイティブ日本語っぽく。 声: 温かみがあり、共感的で、プロフェッショナルな口調で、お客様の問題が理解され解決されることをお客様に安心させます。\n\n句読点: 自然な間を置いた構造で、明瞭で安定した落ち着いた流れを実現します。\n\n話し方: 落ち着いて辛抱強く、聞き手に思いやりのあるサポートと理解のある口調で話します。\n\n言い回し: 明確かつ簡潔で、専門用語を避けながらプロ意識を維持し、お客様にわかりやすい言葉を使用します。\n\n口調: 共感的でソリューション重視で、理解と積極的な支援の両方を重視します。",
                        'response_format' => $format,
                    ]);

                    foreach ($stream as $audioChunk) {
                        echo $audioChunk;
                        flush();
                    }
                }
            } catch (\Exception $e) {
                \Log::error('TTS Error: ' . $e->getMessage());
                http_response_code(500);
            }
        }, 200, [
            'Content-Type'      => 'audio/mpeg',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Split text into chunks respecting sentence boundaries
     * 
     * @param string $text
     * @param int $maxLength
     * @return array
     */
    private function chunkText(string $text, int $maxLength = 1400): array
    {
        // If text is already short enough, return as-is
        if (mb_strlen($text) <= $maxLength) {
            return [$text];
        }

        $chunks = [];
        $currentChunk = '';
        
        // Split by sentences (Japanese and English punctuation)
        $sentences = preg_split('/([。！？\.!?]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        for ($i = 0; $i < count($sentences); $i += 2) {
            $sentence = $sentences[$i] ?? '';
            $delimiter = $sentences[$i + 1] ?? '';
            $fullSentence = $sentence . $delimiter;
            
            // If single sentence is too long, split by phrases
            if (mb_strlen($fullSentence) > $maxLength) {
                // Save current chunk if not empty
                if (!empty(trim($currentChunk))) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }
                
                // Split long sentence by phrases (commas, etc.)
                $phrases = preg_split('/([、,，]+)/u', $fullSentence, -1, PREG_SPLIT_DELIM_CAPTURE);
                
                foreach ($phrases as $j => $phrase) {
                    if (empty(trim($phrase))) continue;
                    
                    // If adding this phrase exceeds limit, save current chunk
                    if (mb_strlen($currentChunk . $phrase) > $maxLength && !empty(trim($currentChunk))) {
                        $chunks[] = trim($currentChunk);
                        $currentChunk = $phrase;
                    } else {
                        $currentChunk .= $phrase;
                    }
                }
            } else {
                // If adding this sentence exceeds limit, save current chunk
                if (mb_strlen($currentChunk . $fullSentence) > $maxLength && !empty(trim($currentChunk))) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = $fullSentence;
                } else {
                    $currentChunk .= $fullSentence;
                }
            }
        }
        
        // Add remaining chunk
        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }
        
        // Fallback: if any chunk is still too long, split by character limit
        $finalChunks = [];
        foreach ($chunks as $chunk) {
            if (mb_strlen($chunk) > $maxLength) {
                // Hard split by character count
                $parts = mb_str_split($chunk, $maxLength);
                $finalChunks = array_merge($finalChunks, $parts);
            } else {
                $finalChunks[] = $chunk;
            }
        }
        
        return $finalChunks;
    }
}
