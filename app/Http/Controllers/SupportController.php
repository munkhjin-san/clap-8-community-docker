<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\questionAndAnswerRecord;
use App\Models\qandaTagRecord;
use App\Models\qandaKeyWordRecord;
use App\Models\SupportMailFormRecord;
use App\Models\SupportMailRespondingLog;
use App\Models\RegulationRecord;
use App\Models\FileRecord;
use App\Models\RegulationFile;
use App\Models\SupportConversation;
use App\Models\SupportConversationItem;
use App\Models\FileAttachment;
use App\Models\SystemUpdateDetail;
use App\Models\SystemUpdateRecord;
use App\Models\SystemUpdateCheck;
use App\Jobs\RemoveOpenAiFaqRecordDocument;
use App\Jobs\RemoveOpenAiRegulationFilePages;
use App\Jobs\SyncOpenAiFaqRecord;
use App\Jobs\SyncOpenAiRegulationFile;
use App\Services\Faq\OpenAiFaqSyncService;
use App\Services\Regulations\OpenAiRegulationSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Http;
use App\Models\EmergencyContact;
use App\Models\EmergencyContactAction;
use App\Models\Incident;
use App\Models\User;
use OpenAI;

class SupportController extends Controller
{
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    private function isSupportAdmin(): bool
    {
        return in_array($this->active_user()->id, [608, 610]);
    }

    private function authorizeSupportAdmin(): void
    {
        if (!$this->isSupportAdmin()) {
            abort(403);
        }
    }

    private function resolveEmergencyContactForCurrentUser(int $id): EmergencyContact
    {
        $query = EmergencyContact::query()->where('id', $id);

        if (!$this->isSupportAdmin()) {
            $query->where('user_id', $this->active_user()->id);
        }

        return $query->firstOrFail();
    }
    public function support_record_list(Request $request){
        $record_list = questionAndAnswerRecord::where('deleted_flag','=', 0)->with(['qanda_use_tags' => function($q){
            $q->where('deleted_flag','=', 0)->with(['qanda_tag_records' => function($q){
                $q->where('deleted_flag','=', 0);
            }]);
        }])->orderBy('created_at', 'desc')->get();


        $tag_list = qandaTagRecord::where('deleted_flag','=', 0)->with(['tags_use_qanda' => function($q){
            $q->where('deleted_flag','=', 0)->count('useful_count');
        }])->orderBy('useful_count', 'desc')->get();
        
        $key_word_list = qandaKeyWordRecord::where('deleted_flag','=', 0)->with(['key_words_use_qanda' => function($q){
            $q->where('deleted_flag','=', 0)->count('useful_count');
        }])->orderBy('useful_count', 'desc')->get();


        $record_dates_array = array("record_list" => $record_list, "tag_list" => $tag_list, "key_word_list" => $key_word_list);
        
        return response()->json($record_dates_array);

    }
    public function support_feedback(Request $request){
        $create = SupportMailFormRecord::create([
            "user_id" => Auth::id(),
            "kind_value" => $request->kind_value,
            "contact_address" => $request->contact_address,
            "consultation_content" => $request->consultation_content,
        ]);
        return response()->json($create);
    }
    public function support_resolve_decision(Request $request){
        $incement = questionAndAnswerRecord::findOrFail($request->id)->increment('useful_count');
        return response()->json($incement);
    }
    public function faq_add_record(Request $request){
        $adminIds = [608, 610];
        $user_id = $this->active_user()->id;
        if (!in_array($user_id, $adminIds)) {
            abort(403);
        }
        $request->validate([
            'question' => 'required|string|max:200',
            'answer'   => 'required|string|max:500',
            'content'  => 'nullable|string',
            'tag_text' => 'nullable|string|max:500',
        ]);
        $record = questionAndAnswerRecord::updateOrCreate(
            ['id' => $request->id ?? null],
            [
                'user_id'       => $user_id,
                'question'      => $request->question,
                'answer'        => $request->answer,
                'content'       => $request->content ?? '',
                'tag_text'      => $request->tag_text ?? '',
                'deleted_flag'  => 0,
            ]
        );

        $syncService = app(OpenAiFaqSyncService::class);
        if ($syncService->needsSync($record)) {
            $syncService->markRecordSyncing($record);
            SyncOpenAiFaqRecord::dispatch($record->id);
            $record->refresh();
        }

        return response()->json($record);
    }
    public function faq_delete_record(Request $request){
        $adminIds = [608, 610];
        $user_id = $this->active_user()->id;
        if (!in_array($user_id, $adminIds)) {
            abort(403);
        }
        $request->validate(['id' => 'required|integer']);
        $record = questionAndAnswerRecord::findOrFail($request->id);
        $record->update(['deleted_flag' => 1]);

        $syncService = app(OpenAiFaqSyncService::class);
        $syncService->markRecordSyncing($record);
        RemoveOpenAiFaqRecordDocument::dispatch($record->id);

        return response()->json(['success' => true]);
    }
    public function faq_tag_save(Request $request){
        $adminIds = [608, 610];
        $user_id = $this->active_user()->id;
        if (!in_array($user_id, $adminIds)) {
            abort(403);
        }
        $request->validate([
            'text' => 'required|string|max:100',
        ]);
        $tag = qandaTagRecord::updateOrCreate(
            ['id' => $request->id ?? null],
            ['text' => $request->text, 'deleted_flag' => 0]
        );
        return response()->json($tag);
    }
    public function faq_tag_delete(Request $request){
        $adminIds = [608, 610];
        $user_id = $this->active_user()->id;
        if (!in_array($user_id, $adminIds)) {
            abort(403);
        }
        $request->validate(['id' => 'required|integer']);
        qandaTagRecord::findOrFail($request->id)->update(['deleted_flag' => 1]);
        return response()->json(['success' => true]);
    }
    public function support_add_consult(Request $request){
        $user_id = $this->active_user()->id;
        $create = SupportMailFormRecord::create([
            "user_id" => $user_id,
            "kind_value" => $request->kind_value,
            "contact_address" => $request->contact_address,
            "consultation_content" => $request->consultation_content,
        ]);
        return response()->json($create);
    }
    public function get_recieved_consults(){

        
        $user_id = $this->active_user()->id;
        $has_privilage = in_array($user_id, [610, 608, 516, 517, 519, 518, 526, 494]);
        $record_list = supportMailFormRecord::where('deleted_flag','=', 0)
        ->when(!$has_privilage, function($q){
            $q->where('user_id', Auth::id());
        })
        ->with('user')
        ->with(['support_mail_responding_logs' => function($q){
            $q->where('deleted_flag','=', 0)->orderBy('created_at', 'desc')->with('user');
        }])->orderBy('created_at', 'desc')->get();
        return response()->json($record_list);
    }
    public function add_memo_to_consult(Request $request){
        $user_id = $this->active_user()->id;
        $create = SupportMailRespondingLog::create([
            "user_id" => $user_id,
            "text" => $request->text,
            "record_id" => $request->record_id,
        ]);
        return response()->json($create);
    }
    public function update_consult_status(Request $request){
        $update = SupportMailFormRecord::findOrFail($request->record_id)->update([
            "status_flag" => $request->value
        ]);
        return response()->json($update);
    }

    public function get_system_updates(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|string|in:all,maintenance_plan,update_plan,update_log,notice',
            'keyword' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $category = $validated['category'] ?? 'all';
        $keyword = trim($validated['keyword'] ?? '');
        $perPage = $validated['per_page'] ?? 10;

        $records = SystemUpdateRecord::with(['details.files', 'user'])
            ->when(!$this->isSupportAdmin(), function ($query) {
                $query->where('is_published', true);
            })
            ->when($category !== 'all', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($keyword !== '', function ($query) use ($keyword) {
                $likeKeyword = '%' . $keyword . '%';
                $query->where(function ($searchQuery) use ($likeKeyword) {
                    $searchQuery->where('title', 'like', $likeKeyword)
                        ->orWhere('summary', 'like', $likeKeyword)
                        ->orWhereHas('details', function ($detailQuery) use ($likeKeyword) {
                            $detailQuery->where('title', 'like', $likeKeyword)
                                ->orWhere('content', 'like', $likeKeyword);
                        });
                });
            })
            ->withExists('systemUpdateChecks as checked_by_user', function ($query) {
                $query->where('user_id', $this->active_user()->id);
            })
            ->orderByRaw('COALESCE(published_at, scheduled_start_at, created_at) DESC')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($records);
    }

    public function save_system_update(Request $request)
    {
        $this->authorizeSupportAdmin();

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:system_update_records,id',
            'category' => 'required|string|in:maintenance_plan,update_plan,update_log,notice',
            'title' => 'required|string|max:200',
            'summary' => 'nullable|string|max:5000',
            'status' => 'required|string|in:draft,scheduled,published,completed,canceled',
            'is_published' => 'boolean',
            'must_read' => 'boolean',
            'published_at' => 'nullable|date',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after_or_equal:scheduled_start_at',
            'details' => 'required|array|min:1',
            'details.*.id' => 'nullable|integer|exists:system_update_details,id',
            'details.*.type' => 'required|string|in:new_feature,improvement,error_fix,security,performance,maintenance,ui_change,known_issue,notice,other',
            'details.*.title' => 'required|string|max:200',
            'details.*.content' => 'nullable|string|max:5000',
            'details.*.sort_order' => 'nullable|integer|min:0',
            'details.*.files' => 'array',
            'details.*.files.*.id' => 'required|integer|exists:file_records,id',
        ]);

        $record = DB::transaction(function () use ($validated) {
            $isPublished = $validated['is_published'] ?? true;
            $record = SystemUpdateRecord::updateOrCreate(
                ['id' => $validated['id'] ?? null],
                [
                    'user_id' => $this->active_user()->id,
                    'category' => $validated['category'],
                    'title' => $validated['title'],
                    'summary' => $validated['summary'] ?? null,
                    'status' => $validated['status'],
                    'is_published' => $isPublished,
                    'must_read' => $validated['must_read'] ?? false,
                    'published_at' => $validated['published_at'] ?? ($isPublished ? now() : null),
                    'scheduled_start_at' => $validated['scheduled_start_at'] ?? null,
                    'scheduled_end_at' => $validated['scheduled_end_at'] ?? null,
                ]
            );

            $detailIds = [];
            foreach (($validated['details'] ?? []) as $index => $detail) {
                $detailRecord = $record->details()->updateOrCreate(
                    ['id' => $detail['id'] ?? null],
                    [
                        'type' => $detail['type'],
                        'title' => $detail['title'],
                        'content' => $detail['content'] ?? null,
                        'sort_order' => $detail['sort_order'] ?? $index,
                    ]
                );
                $fileIds = collect($detail['files'] ?? [])->pluck('id')->filter()->values()->all();
                FileAttachment::where('attachable_type', SystemUpdateDetail::class)
                    ->where('attachable_id', $detailRecord->id)
                    ->where('collection', 'attachments')
                    ->when(count($fileIds), fn ($query) => $query->whereNotIn('file_id', $fileIds))
                    ->delete();

                foreach ($fileIds as $fileId) {
                    FileAttachment::firstOrCreate([
                        'file_id' => $fileId,
                        'attachable_type' => SystemUpdateDetail::class,
                        'attachable_id' => $detailRecord->id,
                        'collection' => 'attachments',
                    ]);
                }
                $detailIds[] = $detailRecord->id;
            }

            $removedDetails = $record->details()
                ->when(count($detailIds), fn ($query) => $query->whereNotIn('id', $detailIds))
                ->when(!count($detailIds), fn ($query) => $query)
                ->with('fileAttachments')
                ->get();

            foreach ($removedDetails as $removedDetail) {
                $this->deleteSystemUpdateDetailAttachments($removedDetail);
            }

            $record->details()
                ->when(count($detailIds), fn ($query) => $query->whereNotIn('id', $detailIds))
                ->when(!count($detailIds), fn ($query) => $query)
                ->delete();

            return $record->load(['details.files', 'user']);
        });

        return response()->json($record);
    }

    public function delete_system_update(Request $request)
    {
        $this->authorizeSupportAdmin();

        $request->validate([
            'id' => 'required|integer|exists:system_update_records,id',
        ]);

        $record = SystemUpdateRecord::findOrFail($request->id);
        foreach ($record->details as $detail) {
            $this->deleteSystemUpdateDetailAttachments($detail);
        }
        $record->details()->delete();
        $record->delete();

        return response()->json(['success' => true]);
    }
    public function system_update_check(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer|exists:system_update_records,id',
        ]);

        $recordId = $request->record_id;
        $userId = $this->active_user()->id;

        $check = SystemUpdateCheck::firstOrCreate([
            'user_id' => $userId,
            'system_update_record_id' => $recordId,
        ]);

        return response()->json(['success' => true]);
    }
    private function deleteSystemUpdateDetailAttachments(SystemUpdateDetail $detail): void
    {
        $detail->fileAttachments()->delete();
    }

    private function conversation($conversationId)
    {
        $apiKey = config('services.openai.api_key');
        // dd($apiKey);
        if($conversationId) {
            $url = "https://api.openai.com/v1/conversations/{$conversationId}";
        } else {
            $url = "https://api.openai.com/v1/conversations";
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->post($url, [
            'metadata' => (object) []
        ]);
        if($response->failed()) {
            return null;
        }
        return $response->json();
    }
    public function get_conversations_history(Request $request)
    {
        $conversations = SupportConversation::with(['items' => function($q) {
            $q->orderBy('created_at', 'asc');
        }, 'user'])->where('user_id', $this->active_user()->id)->orderBy('created_at', 'desc')->get();
        return response()->json($conversations);
    }
    private function generateSummary($text)
    {
        $apiKey = config('services.openai.api_key');
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->post("https://api.openai.com/v1/responses", [
            'model' => 'gpt-4o-mini',
            'input' => $text,
            'instructions' => '次の文章をサポートチャットのタイトルとして最大20文字で要約してください: ' . $text,
        ]);
        if($response->failed()) {
            return null;
        }
        $data = $response->json();
        $summary = $data['output'][0]['content'][0]['text'] ?? '';
        return $summary;
    }
    public function support_add_message(Request $request){
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $conversationId = $request->input('conversationId') ?? null;
        $conversation = $this->conversation($conversationId);
        

        $message = $request->input('message');

        $title = '';
        if($conversationId == null){
            $title = $this->generateSummary($message);
        }
        $valid_conversation_id = $conversation ? $conversation['id'] : null;

        if(!$valid_conversation_id) {
            return response()->json(['reply' => '申し訳ございませんが現在、リクエストを処理できません。後でもう一度お試しください。']);
        }
        $supportConversation = SupportConversation::firstOrCreate([
            'conversation_id' => $valid_conversation_id,
            'user_id' => $this->active_user()->id,
        ]);
        if(!$conversationId){
            $supportConversation->update(['title' => $title]);
        }
        $supportConversation->items()->create([
            'message' => $message,
            'role' => 'user',
        ]);
        $vectorStoreId = $this->regulationOpenAiVectorStoreId();
        if (!$vectorStoreId) {
            return response()->json(['message' => 'No synced OpenAI regulation store found.'], 404);
        }

        $apiKey = config('services.openai.api_key');
        $response = Http::withToken($apiKey)
        ->acceptJson()
        ->asJson()
        ->timeout(600)
        ->post("https://api.openai.com/v1/responses", [
            'model' => 'gpt-4o-mini',
            'conversation' => $valid_conversation_id,
            'input' => $message,
            'tools' => [
                [
                    'type' => 'file_search',
                    'vector_store_ids' => [$vectorStoreId],
                    'max_num_results' => 12,
                ]
            ],
            'include' => ['file_search_call.results'],
            'instructions' => 'あなたは社内ナレッジ専用の回答アシスタントです。' .
            '可能な限り file_search で文書根拠を使って回答してください。' .
            '回答の根拠を示すときは、必ず元PDFファイル名とページ番号を「参考: ファイル名 p.ページ番号」の形式で明記してください。' .
            'file_search の検索結果はページごとのMarkdownです。Markdown本文冒頭のメタデータではなく、本文内容を根拠に回答してください。' .
            '関連する根拠が見つからない場合は、はっきり「関連する社内ファイルが見つかりませんでした」と最初に明記してから、' .
            '一般知識では推測せずに必要な情報を箇条書きで尋ねてください。',
        ]);
        
        if($response->failed()) {
            return response()->json(['message' => $response->json()], 500);
        }
        
        $conversationUpdated = $response->json();
        // dd($conversationUpdated);
        // return response()->json($conversationUpdated);
        $data = $this->responseParser($conversationUpdated);
        // return response()->json($data);
        $assistantMessage = $data['reply'];
        $file_names = $data['file_names'];
        $keywords = $data['keywords'];

        $supportConversation->items()->create([
            'message' => $assistantMessage,
            'role' => 'assistant',
            'source' => $file_names,
            'keywords' => $keywords,
        ]);

        $d = $supportConversation->load(['items' => function($q) {
            $q->orderBy('created_at', 'asc');
        }, 'user']);

        return response()->json($d);

        // $apiKey = config('services.openai.api_key');


        // $client = OpenAI::client($apiKey);
        // $response = $client->responses()->create([
        //     'model' => 'gpt-4o-mini',
        //     'tools' => [
        //         [
        //             'type' => 'file_search',
        //             'vector_store_ids' => ["vs_68a7c6b10f048191b5fa9cd63fefefde"],
        //         ]
        //     ],
        //     'input' => $message,
        //     'store' => true,
        //     'metadata' => [
        //         'user_id' => '123',
        //         'session_id' => 'abc456'
        //     ]
        // ]);

        // $reply = '';
        // if($response->status !== 'completed') {
        //     $reply = '申し訳ございませんが現在、リクエストを処理できません。後でもう一度お試しください。';
        // } else {
        //     foreach ($response->output as $output) {
        //         if(isset($output['role']) && $output['role'] === 'assistant') {
        //             foreach ($output['content'] as $content) {
        //                 $reply .= $content['text'];
        //             }
        //         }
                
        //     }
        // }
        // return response()->json(['reply' => $reply]);
    }
    protected function responseParser($response)
    {
        $message = '';
        $keywords = [];
        $sources = [];
        foreach (($response['output'] ?? []) as $output) {
            if(isset($output['role']) && $output['role'] === 'assistant') {
                foreach ($output['content'] as $content) {                    
                    $message = $content['text'];
                }
            }        
            if(isset($output['type']) && $output['type'] === 'file_search_call' && isset($output['queries'])) {

                foreach ($output['queries'] as $query) {
                    $keywords[] = $query;
                }
            }
            if(isset($output['type']) && $output['type'] === 'file_search_call' && isset($output['results'])) {
                foreach ($output['results'] as $result) {
                    $source = $this->openAiFileSearchReference($result);
                    if ($source) {
                        $sources[] = $source;
                    }
                }
            }
        }
        return [
            'reply' => $message,
            'file_names' => array_values(array_unique($sources)),
            'keywords' => array_values(array_unique($keywords)),
        ];
    }

    private function regulationOpenAiVectorStoreId(): ?string
    {
        $storeDataPath = storage_path('app/regulation_openai_store/store.json');
        if (!file_exists($storeDataPath)) {
            return null;
        }

        $storeData = json_decode(file_get_contents($storeDataPath), true);

        return $storeData['vector_store_id'] ?? null;
    }

    private function openAiFileSearchReference(array $result): ?string
    {
        $attributes = $result['attributes'] ?? [];
        $fileName = $result['filename'] ?? null;
        $title = $attributes['original_file_name'] ?? $this->originalNameFromGeneratedFileName($fileName);
        $pageNumber = $attributes['page'] ?? $this->pageNumberFromGeneratedFileName($fileName);

        if (!$title) {
            return null;
        }

        return $pageNumber ? "{$title} p.{$pageNumber}" : $title;
    }
    public function delete_conversation(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $user_id = $this->active_user()->id;
        $conversation = SupportConversation::where('id', $request->id)
            ->where('user_id', $user_id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'チャットが見つかりません。'
            ], 404);
        }

        // Delete conversation items first due to foreign key constraints
        $conversation->items()->delete();
        // Then delete the conversation itself
        $conversation->delete();

        //　execute silently delete in OpenAI
        $apiKey = config('services.openai.api_key');
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->delete("https://api.openai.com/v1/conversations/{$conversation->conversation_id}");

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully.'
        ]);
    }
    // Regulation methods
    public function get_regulations(Request $request)
    {
   
        // $vectorStoreId = 'vs_68a7c6b10f048191b5fa9cd63fefefde';
        // $apiKey = config('services.openai.api_key');
        // $client = OpenAI::client($apiKey);
        // $files_list = $client->vectorStores()->files()->list($vectorStoreId);
        $regulations = RegulationRecord::with(['user', 'regulation_files'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($regulations);

    }

    public function save_regulation(Request $request)
    {

            $request->validate([
                'title' => 'required|string',
                'content' => 'required|string',
            ]);


            $user_id = $this->active_user()->id;
            $id = $request->id ?? null;
            $regulation = RegulationRecord::updateOrCreate(
                ['id' => $id],
                [
                    'user_id' => $user_id,
                    'title' => $request->title,
                    'content' => $request->content,
                ]
            );

            $files = $request['regulation_files'] ?? [];
            $syncService = app(OpenAiRegulationSyncService::class);

            $current_files = $regulation->regulation_files()->pluck('id')->toArray();
            $incomingFileIds = array_values(array_filter(array_map(fn($file) => $file['id'] ?? null, $files)));
            $files_to_detach = array_diff($current_files, $incomingFileIds);
            if(!empty($files_to_detach)){
                $remove_targets = RegulationFile::whereIn('id', $files_to_detach)->get();
                foreach ($remove_targets as $removeFileRecord) {
                    $syncService->markFileSyncing($removeFileRecord);
                    RemoveOpenAiRegulationFilePages::dispatch($removeFileRecord->id);
                    $removeFileRecord->delete();
                }
            }

            foreach ($files as $file) {

                if (!isset($file['id'])) {
                    continue;
                }

                $fileRecord = RegulationFile::find($file['id']);
                if (!$fileRecord) {
                    continue;
                }

                $wasSupported = $syncService->isSupportedPdf($fileRecord);

                $fileRecord->regulation_record_id = $regulation->id;
                if (isset($file['chat_supported'])) {
                    $fileRecord->chat_supported = (bool) $file['chat_supported'];
                }

                if ($fileRecord->isDirty()) {
                    $fileRecord->save();
                }

                $fileRecord->refresh();
                $isSupported = $syncService->isSupportedPdf($fileRecord);

                if ($wasSupported && !$isSupported) {
                    $syncService->markFileSyncing($fileRecord);
                    RemoveOpenAiRegulationFilePages::dispatch($fileRecord->id);
                    continue;
                }

                if ($isSupported && $syncService->needsSync($fileRecord)) {
                    $syncService->markFileSyncing($fileRecord);
                    SyncOpenAiRegulationFile::dispatch($fileRecord->id);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $request->id ? 'Regulation updated successfully' : 'Regulation created successfully',
                'data' => $regulation,
            ]);


    }
    private function save_into_vector_store(RegulationFile $fileRecord, $client, $vectorStoreId)
    {
        $relPath = "/regulation_files/{$fileRecord['path']}.{$fileRecord['extension']}";
        if(file_exists(storage_path('app' . $relPath))) {
            $absPath = Storage::disk('local')->path($relPath);
            $file = $client->files()->upload([
                'purpose' => 'assistants',
                'file'    => fopen($absPath, 'r'),
            ]);
            $vsFile = $client->vectorStores()->files()->create(
                vectorStoreId: $vectorStoreId,
                parameters: ['file_id' => $file->id]
            );
            $status = $vsFile->status;
            $tries  = 20;
            while ($status === 'in_progress' && $tries-- > 0) {
                sleep(1);
                $check = $client->vectorStores()->files()->retrieve(
                    vectorStoreId: $vectorStoreId,
                    fileId: $vsFile->id
                );
                $status = $check->status;
            }
            if ($status === 'completed') {
                // Update pivot table with vector_file_id
                $fileRecord->vector_file_id = $vsFile->id;
                $fileRecord->save();
                return $vsFile->id;
            } else {
                // Handle failure case if needed
                return null;
            }
        }
        return null;
    }
    private function delete_from_vector_store($vector_file_id, $client, $vectorStoreId)
    {
        try {
            $client->vectorStores()->files()->delete(
                vectorStoreId: $vectorStoreId,
                fileId: $vector_file_id
            );
            $client->files()->delete($vector_file_id);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
        }
    }
    public function delete_regulation(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:regulation_records,id'
            ]);

            DB::beginTransaction();

            $regulation = RegulationRecord::findOrFail($request->id);
            
            // Soft delete the regulation
            $regulation->delete();

            $regulationFiles = $regulation->regulation_files;
            if($regulationFiles){
                $syncService = app(OpenAiRegulationSyncService::class);
                foreach ($regulationFiles as $file) {
                    $syncService->markFileSyncing($file);
                    RemoveOpenAiRegulationFilePages::dispatch($file->id);
                    $file->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Regulation deleted successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete regulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function regulation_file_upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $file = $request->file('file');
        $path = '/regulation_files';
        $file_path = date("YmdHis") . md5(uniqid());           
        $file_extension = $file->getClientOriginalExtension();
        $file_real_name = $file->getClientOriginalName();            
        $mime_type = $file->getMimeType();
        $mime_type_array = explode('/',$mime_type);
        $file_type = $mime_type_array[0];            
        $file_size = $file->getSize();

        $fileRecord = RegulationFile::create([
            'path' => $file_path,
            'name' => $file_real_name,
            'mime_type' => $file_type,
            'extension' => $file_extension,
            'size' => $file_size,
        ]);

        $set_path = $file_path . '.' . $file_extension;
        $thumbnail_path = 'thumbnail/' . $file_path . '_thumbnail.webp';
        $height = 130;
        if($file_type == 'image' && $file_extension !== 'svg'){
            $img = Image::read($file);
                
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                      
            $img->save(storage_path('app') . $path .'/'. $set_path, 30);  
            File::isDirectory(storage_path('app') . $path .'/thumbnail') or File::makeDirectory(storage_path('app') . '/' . $path .'/thumbnail', 0755, true, true);
            $thumbnail = $img->scale(height: 130);  
            $thumbnail->toWebp()->save(storage_path('app') . $path .'/'. $thumbnail_path);
        }else{
            Storage::disk('local')->putFileAs(
                $path, $file, $set_path
            );
        }
        $sizeAfter = File::size(storage_path('app' . $path . '/' . $set_path));
    
        // $fileRecord->size = $sizeAfter;
        $fileRecord->update(['size' => $sizeAfter]);

        return response()->json($fileRecord);
    }
    public function search_regulations_from_files(Request $request){
        $request->validate([
            'keyword' => 'required|string|max:100',
        ]);
        $keyword = $request->input('keyword');

        $storeDataPath = storage_path('app/regulation_openai_store/store.json');
        if (!file_exists($storeDataPath)) {
            return response()->json(['message' => 'No synced store found.'], 404);
        }

        $storeData = json_decode(file_get_contents($storeDataPath), true);
        $vectorStoreId = $storeData['vector_store_id'] ?? null;
        if (!$vectorStoreId) {
            return response()->json(['message' => 'Invalid store data.'], 500);
        }

        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            return response()->json(['message' => 'OpenAI API key is not configured.'], 500);
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(600)
            ->post("https://api.openai.com/v1/vector_stores/{$vectorStoreId}/search", [
                'query' => $keyword,
                'max_num_results' => 10,
                'rewrite_query' => false,
                'ranking_options' => [
                    'score_threshold' => 0.25,
                ],
            ]);

        if ($response->failed()) {
            return response()->json(['message' => 'OpenAI request failed.', 'error' => $response->json()], 500);
        }

        $chunks = $this->openAiVectorSearchChunks($response->json(), $vectorStoreId, $keyword);

        return response()->json(['chunks' => $chunks]);
    }

    protected function openAiVectorSearchChunks(array $data, string $vectorStoreId, string $keyword): array
    {
        $chunks = [];

        foreach (data_get($data, 'data', []) as $result) {
            $attributes = $result['attributes'] ?? [];
            $fileName = $result['filename'] ?? null;
            $title = $attributes['original_file_name'] ?? $this->originalNameFromGeneratedFileName($fileName);
            $pageNumber = $attributes['page'] ?? $this->pageNumberFromGeneratedFileName($fileName);
            $text = $this->keywordContextText($this->openAiVectorSearchResultText($result), $keyword);

            if (!$text) {
                continue;
            }

            $chunks[] = [
                'title' => $title,
                'text' => $text,
                'uri' => $attributes['source_path'] ?? null,
                'fileSearchStore' => $vectorStoreId,
                'customMetadata' => $attributes,
                'pageNumber' => $pageNumber !== null ? (int) $pageNumber : null,
                'score' => $result['score'] ?? null,
            ];
        }

        return $chunks;
    }

    private function openAiVectorSearchResultText(array $result): string
    {
        $content = $result['content'] ?? [];

        if (is_string($content)) {
            return $this->stripMarkdownSearchMetadata($content);
        }

        if (is_array($content)) {
            $text = collect($content)
                ->map(fn ($part) => is_array($part) ? ($part['text'] ?? null) : $part)
                ->filter()
                ->implode("\n");

            return $this->stripMarkdownSearchMetadata($text);
        }

        return '';
    }

    private function stripMarkdownSearchMetadata(string $text): string
    {
        $text = str_replace("\r", '', $text);
        $parts = preg_split("/\n---\n/u", $text, 2);

        if (count($parts) === 2) {
            $text = $parts[1];
        }

        $lines = collect(preg_split("/\n/u", $text) ?: [])
            ->reject(fn ($line) => preg_match('/^\s*(#|- Regulation file ID:|- Source path:|- Page:)/u', $line) === 1)
            ->values()
            ->all();

        return trim(preg_replace("/\n{3,}/u", "\n\n", implode("\n", $lines)) ?? implode("\n", $lines));
    }

    private function keywordContextText(string $text, string $keyword): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $keyword = trim($keyword);
        if ($keyword === '') {
            return mb_substr($text, 0, 240, 'UTF-8');
        }

        $position = mb_stripos($text, $keyword, 0, 'UTF-8');
        if ($position === false) {
            return mb_substr($text, 0, 240, 'UTF-8');
        }

        $start = max(0, $position - 80);
        $length = mb_strlen($keyword, 'UTF-8') + 180;
        $snippet = mb_substr($text, $start, $length, 'UTF-8');

        return ($start > 0 ? '...' : '').trim($snippet).(mb_strlen($text, 'UTF-8') > $start + $length ? '...' : '');
    }

    private function originalNameFromGeneratedFileName(?string $fileName): ?string
    {
        if (!$fileName) {
            return null;
        }

        return preg_replace('/(?:__file\d+)?__p\d{3}\.md$/', '.pdf', $fileName) ?: $fileName;
    }

    private function pageNumberFromGeneratedFileName(?string $fileName): ?int
    {
        if (!$fileName || preg_match('/(?:__file\d+)?__p(\d{3})\.md$/', $fileName, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
    public function add_emergency_contact(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'content' => 'required|string',
        ]);

        $user_id = $this->active_user()->id;
        $type = $request->type;
        if($type == 'emergency'){
            $create = EmergencyContact::create([
                'user_id' => $user_id,
                'content' => $request->content,
                'status' => EmergencyContact::STATUS_PENDING,
            ]);

            $sender = $this->active_user();
            $messageContent = "緊急連絡がありました\nユーザー: {$sender->name}\n内容: {$request->content}";
            $bosses = User::where('position_id', '<', 5)->whereNotNull('email')->get();
            foreach ($bosses as $boss) {
                if (!filter_var($boss->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                try {
                    Mail::raw($messageContent, function ($message) use ($boss) {
                        $message
                            ->to($boss->email)
                            ->subject('緊急連絡がありました');
                    });
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }

            return response()->json($create);
        }else if ($type == 'incident'){
            $create = Incident::create([
                'reported_by' => $user_id,
                'description' => $request->content,
                'status' => '処分未決定',
            ]);
            $create->logs()->create([
                'user_id' => $user_id,
                'action' => 'created',
                'changes' => [
                    'description' => ['old' => null, 'new' => $request->content],
                    'status' => ['old' => null, 'new' => '処分未決定'],
                ],
            ]);
            return response()->json($create);
        }
        

        
    }

    public function get_emergency_contacts()
    {
        $contacts = EmergencyContact::query()
            ->withCount('actions')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($contacts);
    }

    public function update_emergency_contact_status(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'status' => 'required|string|in:pending,complete',
        ]);
        $contact = EmergencyContact::findOrFail($validated['id']);

        $contact->update([
            'status' => $validated['status'],
        ]);

        return response()->json($contact->fresh());
    }

    public function get_emergency_contact_actions(Request $request)
    {
        $validated = $request->validate([
            'emergency_contact_id' => 'required|integer',
        ]);

        $contact = $this->resolveEmergencyContactForCurrentUser($validated['emergency_contact_id']);

        $actions = $contact->actions()->with('user')->get();

        return response()->json($actions);
    }

    public function add_emergency_contact_action(Request $request)
    {
        $validated = $request->validate([
            'emergency_contact_id' => 'required|integer',
            'text' => 'required|string|max:2000',
        ]);

        $contact = $this->resolveEmergencyContactForCurrentUser($validated['emergency_contact_id']);

        $action = EmergencyContactAction::create([
            'emergency_contact_id' => $contact->id,
            'user_id' => Auth::id(),
            'text' => $validated['text'],
        ]);

        return response()->json($action->load('user'));
    }

}
