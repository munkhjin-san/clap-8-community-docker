<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateLunchChallenge;
use App\Models\User;
use App\Services\ChallengeSuggestionService;
use App\Services\Contracts\CachedContractExtractionService;
use App\Services\Contracts\GeminiContractOcrService;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use OpenAI;
use Generator;
use Throwable;

class OpenAiController extends Controller
{
    private const LUNCH_CHALLENGE_TARGET_COUNT = 10;

    public function summarize_contract_comparison(Request $request)
    {
        $data = $request->validate([
            'base_contract_name' => 'required|string|max:255',
            'target_contract_name' => 'required|string|max:255',
            'summary' => 'required|array',
            'summary.added' => 'required|integer|min:0',
            'summary.removed' => 'required|integer|min:0',
            'summary.modified' => 'required|integer|min:0',
            'changes' => 'required|array|max:40',
            'changes.*.change_type' => 'required|string|in:added,removed,modified',
            'changes.*.clause_label' => 'nullable|string|max:255',
            'changes.*.before_text' => 'nullable|string',
            'changes.*.after_text' => 'nullable|string',
        ]);

        $apiKey = config('services.openai.api_key');
        abort_if(!$apiKey, 500, 'OpenAI APIキーが設定されていません。');

        if (
            ($data['summary']['added'] ?? 0) === 0
            && ($data['summary']['removed'] ?? 0) === 0
            && ($data['summary']['modified'] ?? 0) === 0
        ) {
            return response()->json([
                'overview' => '今回の比較では、法的に意味のある差分は検出されませんでした。',
                'legal_impact' => '追加・削除・変更はいずれも 0 件です。',
                'key_changes' => [],
                'negotiation_points' => [],
                'caution_items' => [],
            ]);
        }

        $changes = collect($data['changes'])
            ->take(25)
            ->map(function (array $change, int $index) {
                return [
                    'no' => $index + 1,
                    'type' => $change['change_type'],
                    'clause' => $change['clause_label'] ?? '条文名不明',
                    'before' => Str::limit($change['before_text'] ?? '', 700, '...'),
                    'after' => Str::limit($change['after_text'] ?? '', 700, '...'),
                ];
            })
            ->values()
            ->all();

        $client = OpenAI::client($apiKey);
        $model = config('services.openai.compare_model', 'gpt-4.1-mini');

        $response = $client->responses()->create([
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => <<<TXT
あなたは日本の契約レビュー担当者です。
与えられた契約差分データだけを根拠に、日本語で簡潔に要約してください。
推測しすぎず、変更が法務上どのような意味を持つかを実務的に説明してください。
出力は必ずJSONのみで返してください。
JSON形式:
{
  "overview": "比較全体の短い要約",
  "legal_impact": "法務上の影響の要約",
  "key_changes": ["重要な変更点1", "重要な変更点2"],
  "negotiation_points": ["確認・交渉したい点1", "確認・交渉したい点2"],
  "caution_items": ["追加確認が必要な点1", "追加確認が必要な点2"]
}
各配列は最大3件。空なら空配列。
TXT
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => json_encode([
                                'base_contract_name' => $data['base_contract_name'],
                                'target_contract_name' => $data['target_contract_name'],
                                'summary' => $data['summary'],
                                'changes' => $changes,
                            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        ],
                    ],
                ],
            ],
        ]);

        $text = $response->outputText ?? null;
        if (!$text) {
            foreach ($response->output as $output) {
                if (($output['role'] ?? null) !== 'assistant') {
                    continue;
                }

                foreach (($output['content'] ?? []) as $content) {
                    $text .= $content['text'] ?? '';
                }
            }
        }

        $payloadText = trim((string) $text);
        if (str_starts_with($payloadText, '```')) {
            $payloadText = preg_replace('/^```(?:json)?\s*/', '', $payloadText) ?? $payloadText;
            $payloadText = preg_replace('/\s*```$/', '', $payloadText) ?? $payloadText;
        }

        $json = json_decode($payloadText, true);
        abort_if(!is_array($json), 500, '比較サマリーの生成に失敗しました。');

        return response()->json([
            'overview' => (string) ($json['overview'] ?? ''),
            'legal_impact' => (string) ($json['legal_impact'] ?? ''),
            'key_changes' => array_values(array_filter($json['key_changes'] ?? [], fn ($item) => is_string($item) && $item !== '')),
            'negotiation_points' => array_values(array_filter($json['negotiation_points'] ?? [], fn ($item) => is_string($item) && $item !== '')),
            'caution_items' => array_values(array_filter($json['caution_items'] ?? [], fn ($item) => is_string($item) && $item !== '')),
        ]);
    }

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
    public function review_document(
        Request $request,
        CachedContractExtractionService $contractExtractionService,
        GeminiContractOcrService $geminiContractOcrService,
    ){
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
            'rendered_pages' => 'nullable|array|max:80',
            'rendered_pages.*' => 'file|mimes:png,jpg,jpeg,webp|max:20480',
        ]);
        abort_if(!isset($data['file']), 400, 'Missing file');
        $file = $data['file'];
        $apiKey = config('services.openai.api_key');
        $client = OpenAI::client($apiKey);
        $review_type = $data['review_type'];
        $configKey = $review_type === 'deep' ? "services.openai.prompts.legal_deep_review" : "services.openai.prompts.legal_quick_review";
        $promptId = config($configKey);
        $mime = $file->getClientMimeType();
        $fileName = $file->getClientOriginalName();
        
        if(!$promptId){
            abort(400, 'Invalid config_key');
        }
        $role = $data['role'];
        $type = $data['type'];
        $criteria = $CRITERIA[$role] ?? $CRITERIA['乙'];
        
        $inputContent = null;
        $documentInput = [
            'method' => 'openai_file',
            'filename' => $fileName,
            'mime' => $mime,
        ];

        if ($this->shouldUseExtractedContractText($file, $contractExtractionService)) {
            $renderedPages = $request->file('rendered_pages', []);
            $renderedPages = is_array($renderedPages) ? array_values($renderedPages) : [$renderedPages];

            if ($renderedPages === [] && $this->requiresClientRenderedPdfPages()) {
                return response()->json([
                    'message' => 'PDF_RENDERED_PAGES_REQUIRED',
                    'code' => 'rendered_pages_required',
                ], 422);
            }

            try {
                if ($renderedPages !== []) {
                    $documentIndex = $contractExtractionService->rememberExtractedIndex(
                        $file->getRealPath(),
                        'pdf',
                        true,
                        function () use ($contractExtractionService, $geminiContractOcrService, $renderedPages) {
                            $pages = $geminiContractOcrService->extractImagePages($renderedPages);

                            return [
                                'document_index' => $contractExtractionService->buildIndexFromPages($pages),
                                'extraction' => [
                                    'extension' => 'pdf',
                                    'method' => 'gemini_rendered_image_ocr',
                                    'rendered_pages' => count($renderedPages),
                                    'text_length' => $this->countDocumentPageTextLength($pages),
                                ],
                            ];
                        }
                    );
                } else {
                    $documentIndex = $contractExtractionService->extractIndex($file->getRealPath(), 'pdf', true);
                }
                $contractText = $this->formatDocumentIndexForReview($documentIndex);
            } catch (Throwable $exception) {
                abort(422, 'PDF OCR extraction failed: '.$exception->getMessage());
            }

            if (trim($contractText) === '') {
                abort(422, 'PDF OCR extraction returned no reviewable text.');
            }

            $inputContent = [[
                'type' => 'input_text',
                'text' => $this->buildExtractedContractReviewInput($fileName, $contractText),
            ]];
            $documentInput = [
                'method' => 'extracted_text',
                'filename' => $fileName,
                'mime' => $mime,
                'extraction' => $contractExtractionService->lastExtractionMetadata(),
            ];
        } else {
            $base64String = base64_encode((string) file_get_contents($file->getRealPath()));
            $inputContent = [[
                'type' => "input_file",
                "file_data" => "data:${mime};base64,${base64String}",
                "filename" => $fileName
            ]];
        }

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
                    "content" => $inputContent,
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
            'type' => $type,
            'document_input' => $documentInput,
        ]);
    }

    private function shouldUseExtractedContractText($file, CachedContractExtractionService $contractExtractionService): bool
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return false;
        }

        try {
            $preflight = $contractExtractionService->inspectPdf($file->getRealPath());
        } catch (Throwable) {
            return false;
        }

        return (bool) ($preflight['requires_ocr'] ?? false);
    }

    private function requiresClientRenderedPdfPages(): bool
    {
        return filter_var(config('services.google.contract_ocr_client_render_pages', true), FILTER_VALIDATE_BOOL);
    }

    private function countDocumentPageTextLength(array $pages): int
    {
        $text = '';

        foreach ($pages as $page) {
            $text .= (string) ($page['text'] ?? '');
        }

        return mb_strlen(trim($text), 'UTF-8');
    }

    private function formatDocumentIndexForReview(array $documentIndex): string
    {
        $pageTexts = [];

        foreach (($documentIndex['pages'] ?? []) as $page) {
            $text = trim((string) ($page['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $pageNumber = (int) ($page['page'] ?? count($pageTexts) + 1);
            $pageTexts[] = "【Page {$pageNumber}】\n".$text;
        }

        $text = trim(implode("\n\n", $pageTexts));

        return Str::limit($text, 180000, "\n\n[contract text truncated]");
    }

    private function buildExtractedContractReviewInput(string $fileName, string $contractText): string
    {
        return <<<TXT
The uploaded PDF could not be reviewed reliably as a raw PDF because it has no usable embedded text layer.
The contract text below was extracted with OCR. Review this extracted text as the source contract.

Filename: {$fileName}

{$contractText}
TXT;
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
    public function suggest_challenge(Request $request, ChallengeSuggestionService $challengeSuggestionService)
    {
        $validated = $request->validate([
            'challenger' => 'required|integer',
            'idea' => 'nullable|string|max:1000',
            'mini' => 'nullable|boolean',
        ]);
        $user = User::with([
            'post' => fn ($q) => $q
                ->where('app_type', 2)
                ->select(['id', 'user_id', 'title', 'content_rule', 'content_goal']),
        ])
        ->select(['id', 'name'])
        ->findOrFail($validated['challenger']);

        $json = $challengeSuggestionService->suggest(
            $user,
            $validated['idea'] ?? null,
            $validated['mini'] ?? false,
        );

        return response()->json($json);
    }

    public function lunch_challenge_popup(Request $request)
    {
        $user = Auth::user();
        $now = now()->timezone(config('app.timezone'));
        $dateKey = $now->toDateString();
        if (! $user) {
            return response()->json([
                'show_popup' => false,
                'pending' => false,
                'targeted' => false,
                'within_lunch_window' => false,
                'challenge_date' => $dateKey,
            ], 401);
        }
        $refresh = $request->boolean('refresh');

        if ((! $this->isLunchChallengeWindow($now)) || ! $this->isLunchChallengeEligibleUser($user)) {
            return response()->json([
                'show_popup' => false,
                'pending' => false,
                'targeted' => false,
                'within_lunch_window' => false,
                'challenge_date' => $dateKey,
            ]);
        }

        $targetUserIds = $this->lunchChallengeTargetUserIds($dateKey);

        if (! in_array($user->id, $targetUserIds, true)) {
            return response()->json([
                'show_popup' => false,
                'pending' => false,
                'targeted' => false,
                'within_lunch_window' => true,
                'challenge_date' => $dateKey,
            ]);
        }

        $challengeCacheKey = $this->lunchChallengePayloadCacheKey($dateKey, $user->id);
        $pendingCacheKey = $this->lunchChallengePendingCacheKey($dateKey, $user->id);

        if ($refresh) {
            Cache::forget($challengeCacheKey);
            Cache::forget($pendingCacheKey);
        }

        $cachedChallenge = Cache::get($challengeCacheKey);

        if (is_array($cachedChallenge)) {
            return response()->json([
                'show_popup' => true,
                'pending' => false,
                'targeted' => true,
                'within_lunch_window' => true,
                'challenge_date' => $dateKey,
                'generated_challenge' => $cachedChallenge['generated_challenge'] ?? $cachedChallenge,
            ]);
        }

        if (Cache::add($pendingCacheKey, true, $now->copy()->endOfDay())) {
            GenerateLunchChallenge::dispatchAfterResponse($user->id, $user->name, $dateKey);
        }

        return response()->json([
            'show_popup' => false,
            'pending' => true,
            'targeted' => true,
            'within_lunch_window' => true,
            'challenge_date' => $dateKey,
        ]);
    }

    private function isLunchChallengeWindow($now): bool
    {
        $start = $now->copy()->startOfDay()->setTime(12, 0, 0);
        $end = $now->copy()->startOfDay()->setTime(13, 59, 59);

        return $now->betweenIncluded($start, $end);
    }

    private function isLunchChallengeEligibleUser(User $user): bool
    {
        return
            (int) $user->deleted_flag === 0 &&
            (int) $user->retire === 0 &&
            (int) $user->partner_flag === 0 &&
            (int) $user->hide_flag === 0 &&
            (
                (int) $user->position_id < 13 ||
                (int) $user->position_id === 16
            );
    }

    private function lunchChallengeTargetUserIds(string $dateKey): array
    {
        return Cache::remember(
            "lunch_challenge:targets:{$dateKey}",
            now()->endOfDay(),
            function () {
                return User::query()
                    ->where('deleted_flag', 0)
                    ->where('retire', 0)
                    ->where('partner_flag', 0)
                    ->where('hide_flag', 0)
                    ->where(function ($query) {
                        $query->where(function ($employeeQuery) {
                            $employeeQuery->where('position_id', '<', 13);
                        })->orWhere('position_id', 16);
                    })
                    ->pluck('id')
                    ->shuffle()
                    ->take(self::LUNCH_CHALLENGE_TARGET_COUNT)
                    ->values()
                    ->all();
            }
        );
    }

    private function lunchChallengePayloadCacheKey(string $dateKey, int $userId): string
    {
        return "lunch_challenge:payload:{$dateKey}:{$userId}";
    }

    private function lunchChallengePendingCacheKey(string $dateKey, int $userId): string
    {
        return "lunch_challenge:pending:{$dateKey}:{$userId}";
    }
        public function session(Request $request)
    {
        $user = $request->user();
       
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'chatkit_beta=v1',
        ])->post('https://api.openai.com/v1/chatkit/sessions', [
            'workflow' => [
                'id' => config('services.openai.chatkit_workflow_id'),
            ],
            'user' => (string) $user->id,
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to create ChatKit session',
                'error' => $response->json(),
            ], 500);
        }

        return response()->json([
            'client_secret' => $response->json('client_secret'),
        ]);
    }
}
