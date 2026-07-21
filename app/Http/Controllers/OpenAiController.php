<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateLunchChallenge;
use App\Jobs\ProcessContractReview;
use App\Models\ContractReviewJob;
use App\Models\LessonThemeAiConfig;
use App\Models\ProjectContract;
use App\Models\User;
use App\Services\ChallengeSuggestionService;
use App\Services\Contracts\CachedContractExtractionService;
use App\Services\Contracts\ContractReviewService;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    public function models()
    {
        $apiKey = config('services.openai.api_key');
        abort_if(!$apiKey, 500, 'OpenAI APIキーが設定されていません。');

        $models = Cache::remember('openai:selectable_models', now()->addHours(6), function () use ($apiKey) {
            $baseUrl = rtrim(config('ai.providers.openai.url', 'https://api.openai.com/v1'), '/');
            $response = Http::withToken($apiKey)->get("{$baseUrl}/models");

            abort_if(!$response->successful(), 500, 'OpenAIモデル一覧の取得に失敗しました。');

            return collect($response->json('data', []))
                ->pluck('id')
                ->filter(fn ($id) => is_string($id) && $this->isSelectableGenerationModel($id))
                ->sort()
                ->values()
                ->all();
        });

        return response()->json($models);
    }

    private function isSelectableGenerationModel(string $modelId): bool
    {
        if (preg_match('/(embedding|whisper|tts|transcribe|moderation|dall-e|image|audio|realtime)/i', $modelId)) {
            return false;
        }

        return preg_match('/^(gpt|o[0-9]|o[1-9]|chatgpt)/i', $modelId) === 1;
    }

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
        $model = config('services.openai.compare_model', 'gpt-5.6-luna');

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

        $payloadText = ContractReviewService::stripJsonCodeFences(ContractReviewService::extractOutputText($response));

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
        $lessonThemeId = $query['lesson_theme_id'] ?? null;
        Arr::forget($query, 'lesson_theme_id');

        foreach($query as $key => $value){
            if($key === 'config_key'){
                if ($lessonThemeId) {
                    $themeConfig = LessonThemeAiConfig::query()
                        ->where('lesson_theme_id', $lessonThemeId)
                        ->where('config_key', $value)
                        ->first();

                    abort_if(!$themeConfig, 400, '無効なconfig_keyです。');

                    $settings = is_array($themeConfig->settings) ? $themeConfig->settings : [];
                    $package = array_merge($settings, [
                        'input' => $package['input'],
                        'model' => $themeConfig->model ?: config('services.openai.chat_model', 'gpt-4.1-mini'),
                        'instructions' => $themeConfig->instructions ?? '',
                    ]);

                    continue;
                }

                $configKey = "services.openai.prompts." . $value;
                $promptId = config($configKey);
                abort_if(!$promptId, 400, '無効なconfig_keyです。');

                $package['prompt']['id'] = $promptId;
            } else {
                $package['prompt']['variables'][$key] = $value;
            }
        }
        
        if(isset($package['prompt']['variables']) && empty($package['prompt']['variables'])){
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
    ){
        $data = $request->validate([
            'file' => 'required_without:contract_id|file|max:209715|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,odt,ods,odp',
            'contract_id' => 'required_without:file|integer',
            'project_id' => 'required_with:contract_id|integer',
            'role' => ['required', 'string', Rule::in(['甲', '乙'])],
            'type' => 'required|string|max:32',
            'review_type' => ['required', Rule::in(['quick', 'deep'])],
            'rendered_pages' => 'nullable|array|max:80',
            'rendered_pages.*' => 'file|mimes:png,jpg,jpeg,webp|max:20480',
        ]);

        $user = $this->active_user();
        abort_unless($user, 403, '権限がありません。');

        $review_type = $data['review_type'];
        $configKey = $review_type === 'deep' ? "services.openai.prompts.legal_deep_review" : "services.openai.prompts.legal_quick_review";
        abort_if(!config($configKey), 400, 'Invalid config_key');

        $disk = Storage::disk('local');
        $file = $request->file('file');
        $sourceContract = null;

        if ($file) {
            $absolutePath = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            $fileName = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
            $fileSize = (int) $file->getSize();
        } else {
            $project = \App\Models\ProjectRecord::findOrFail($data['project_id']);
            abort_unless(ProjectAccess::allows($user, $project), 403, '権限がありません。');

            $sourceContract = ProjectContract::where('project_record_id', $project->id)
                ->findOrFail($data['contract_id']);
            abort_if(!$sourceContract->file_path || !$disk->exists($sourceContract->file_path), 404, '契約書ファイルが見つかりません。');

            $absolutePath = $disk->path($sourceContract->file_path);
            $extension = strtolower(pathinfo($sourceContract->file_path, PATHINFO_EXTENSION));
            $fileName = basename($sourceContract->file_path);
            $mime = $disk->mimeType($sourceContract->file_path) ?: 'application/octet-stream';
            $fileSize = (int) $disk->size($sourceContract->file_path);
        }

        $useExtractedText = $this->pdfRequiresOcr($absolutePath, $extension, $contractExtractionService);

        $renderedPages = $file ? $request->file('rendered_pages', []) : [];
        $renderedPages = is_array($renderedPages) ? array_values(array_filter($renderedPages)) : [$renderedPages];

        if ($useExtractedText && $renderedPages === []) {
            $hasCachedIndex = $contractExtractionService->hasCachedExtraction($absolutePath, 'pdf', true);

            if (!$hasCachedIndex && $this->requiresClientRenderedPdfPages()) {
                return response()->json([
                    'message' => 'PDF_RENDERED_PAGES_REQUIRED',
                    'code' => 'rendered_pages_required',
                ], 422);
            }

            $serverRendersPages = filter_var(config('services.google.contract_ocr_render_pages', false), FILTER_VALIDATE_BOOL);
            $inlineOcrMaxBytes = (int) config('contracts.inline_ocr_max_bytes', 50 * 1024 * 1024);
            if (!$hasCachedIndex && !$serverRendersPages && $fileSize > $inlineOcrMaxBytes) {
                abort(422, sprintf(
                    'OCRが必要なPDFは%dMBまで処理できます。ファイルを分割するか、テキストレイヤー付きのPDFをご利用ください。',
                    intdiv($inlineOcrMaxBytes, 1024 * 1024),
                ));
            }
        }

        // Persist inputs so the queued job can read them after this request ends.
        if ($file) {
            $pendingDir = ContractReviewService::PENDING_DIR.'/'.Str::uuid()->toString();
            $storedPath = $file->storeAs($pendingDir, basename($fileName));

            $renderedPagePaths = [];
            foreach ($renderedPages as $index => $renderedPage) {
                $renderedPagePaths[] = $renderedPage->storeAs(
                    $pendingDir.'/pages',
                    sprintf('page-%03d.%s', $index + 1, $renderedPage->getClientOriginalExtension() ?: 'png'),
                );
            }
        } else {
            $storedPath = $sourceContract->file_path;
            $renderedPagePaths = [];
        }

        $job = ContractReviewJob::create([
            'user_id' => $user->id,
            'status' => ContractReviewJob::STATUS_QUEUED,
            'review_type' => $review_type,
            'role' => $data['role'],
            'contract_type' => $data['type'],
            'original_filename' => $fileName,
            'mime' => $mime,
            'stored_path' => $storedPath,
            'rendered_page_paths' => $renderedPagePaths,
            'use_extracted_text' => $useExtractedText,
            'project_contract_id' => $sourceContract?->id,
        ]);

        ProcessContractReview::dispatch($job->id);

        return response()->json([
            'job_id' => $job->id,
            'status' => $job->fresh()->status,
        ], 202);
    }

    public function review_document_status(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
        ]);

        $job = ContractReviewJob::findOrFail($data['job_id']);
        $user = $this->active_user();
        abort_unless($user && (int) $job->user_id === (int) $user->id, 403, '権限がありません。');

        $payload = [
            'job_id' => $job->id,
            'status' => $job->status,
            'error' => $job->error_message,
        ];

        if ($job->status === ContractReviewJob::STATUS_COMPLETED) {
            $payload['result'] = [
                'status' => 'ok',
                'raw' => $job->raw_text,
                'json' => $job->result_json,
                'path' => $job->file_path,
                'role' => $job->role,
                'type' => $job->contract_type,
                'document_input' => $job->document_input,
            ];
        }

        return response()->json($payload);
    }

    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }

    private function pdfRequiresOcr(string $absolutePath, string $extension, CachedContractExtractionService $contractExtractionService): bool
    {
        if ($extension !== 'pdf') {
            return false;
        }

        try {
            $preflight = $contractExtractionService->inspectPdf($absolutePath);
        } catch (Throwable) {
            return false;
        }

        return (bool) ($preflight['requires_ocr'] ?? false);
    }

    private function requiresClientRenderedPdfPages(): bool
    {
        return filter_var(config('services.google.contract_ocr_client_render_pages', true), FILTER_VALIDATE_BOOL);
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

                // A long text becomes several sequential OpenAI requests; the
                // total wall time can exceed default php-fpm/fastcgi timeouts and
                // get the stream killed mid-playback. Remove the PHP time limit.
                @set_time_limit(0);

                // Split text into chunks (1400 characters for Japanese)
                $chunks = $this->chunkText($text, 1400);
                $total = count($chunks);

                $instructions = "発音: ほとんど日本語ですので、日本語の発音に注意ください。ネイティブ日本語っぽく。 声: 温かみがあり、共感的で、プロフェッショナルな口調で、お客様の問題が理解され解決されることをお客様に安心させます。\n\n句読点: 自然な間を置いた構造で、明瞭で安定した落ち着いた流れを実現します。\n\n話し方: 落ち着いて辛抱強く、聞き手に思いやりのあるサポートと理解のある口調で話します。\n\n言い回し: 明確かつ簡潔で、専門用語を避けながらプロ意識を維持し、お客様にわかりやすい言葉を使用します。\n\n口調: 共感的でソリューション重視で、理解と積極的な支援の両方を重視します。";

                foreach ($chunks as $i => $chunk) {
                    // Retry establishing each part's stream so a transient OpenAI
                    // failure (429 rate-limit / 5xx / network) does not silently
                    // truncate the audio. Retrying only the establishment avoids
                    // duplicating bytes that were already streamed to the client.
                    $stream = null;
                    $lastError = null;
                    for ($attempt = 1; $attempt <= 3; $attempt++) {
                        try {
                            $stream = $client->audio()->speechStreamed([
                                'model'  => $model,
                                'voice'  => $voice,
                                'input'  => $chunk,
                                'instructions' => $instructions,
                                'response_format' => $format,
                            ]);
                            break;
                        } catch (\Exception $e) {
                            $lastError = $e;
                            \Log::warning('TTS part ' . ($i + 1) . "/{$total} attempt {$attempt} failed: " . $e->getMessage());
                            usleep(400000 * $attempt); // 0.4s, 0.8s, 1.2s backoff
                        }
                    }

                    if (!$stream) {
                        \Log::error('TTS Error (part ' . ($i + 1) . "/{$total} gave up): " . ($lastError ? $lastError->getMessage() : 'unknown'));
                        break; // cannot recover mid-stream; client plays what arrived
                    }

                    foreach ($stream as $audioChunk) {
                        echo $audioChunk;
                        flush();
                    }

                    if ($i < $total - 1) {
                        usleep(150000); // brief gap between parts eases rate limits
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
