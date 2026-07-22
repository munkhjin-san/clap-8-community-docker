<?php

namespace App\Http\Controllers;

use App\Jobs\SendContactEmail;
use App\Jobs\EnrichContactCompany;
use App\Models\ContactBatch;
use App\Models\ContactBatchItem;
use App\Models\ContactBatchNotification;
use App\Models\ContactRecord;
use App\Models\ContactRecordHistory;
use App\Models\ContactPrivateMemo;
use App\Models\messageFile;
use App\Models\ContactType;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Models\ContactCommentLastRead;
use App\Mail\ContactMention;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\ContactScanService; 
use App\Services\ContactCardSplitService;
use App\Services\ContactBatchSubmissionService;
use App\Services\ContactBatchMonitorService;
use App\Services\ContactBatchNotificationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ContactController extends Controller
{

    protected $gemini_url;
    protected $contactScanService;
    protected $contactCardSplitService;
    protected $contactBatchSubmissionService;
    protected $contactBatchMonitorService;
    protected $contactBatchNotificationService;

    public function __construct(
        ContactScanService $contactScanService,
        ContactCardSplitService $contactCardSplitService,
        ContactBatchSubmissionService $contactBatchSubmissionService,
        ContactBatchMonitorService $contactBatchMonitorService,
        ContactBatchNotificationService $contactBatchNotificationService
    )
    {
        $this->gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        $this->contactScanService = $contactScanService;
        $this->contactCardSplitService = $contactCardSplitService;
        $this->contactBatchSubmissionService = $contactBatchSubmissionService;
        $this->contactBatchMonitorService = $contactBatchMonitorService;
        $this->contactBatchNotificationService = $contactBatchNotificationService;
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
    private function get_company_name($image, $mime = 'image/jpeg')
    {
        $apiKey = config('services.google.gemini_api_key') ?: config('app.gemini_api_key');

        if (empty($apiKey)) {
            throw ValidationException::withMessages(['message' => 'APIキーが設定されていません。']);
        }

        $base = rtrim(config('services.google.gemini_url') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = config('services.google.contact_scan_model') ?: 'models/gemini-3.6-flash';

        $instruction = <<<EOD
            あなたは厳密な名刺OCR抽出器です。添付された名刺画像だけを見て、連絡先情報を抽出してください。
            返却はJSONオブジェクトのみです。説明文やMarkdownは出力しないでください。

            規則:
            - 抽出元は添付画像のみです。推測・補完・創作は禁止です。
            - すべての値は文字列にしてください。見つからない項目は空文字 "" にしてください。
            - 部署（例: 営業部）と役職（例: 代表取締役）は別項目として分けてください。部署は department、役職は position に入れてください。
            - 住所は郵便番号を含め、同じ住所情報として自然な1つの文字列にまとめてください。
            - メールアドレスは小文字で返してください。
            - 会社名、氏名、部署、役職、住所、電話番号、FAX、URLを取り違えないでください。
        EOD;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $instruction],
                        [
                            'inlineData' => [
                                'mimeType' => $mime,
                                'data' => $image,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'required' => ['company_name', 'name', 'department', 'position', 'address', 'phone', 'email', 'fax', 'url'],
                    'properties' => [
                        'company_name' => ['type' => 'STRING'],
                        'name' => ['type' => 'STRING'],
                        'department' => ['type' => 'STRING'],
                        'position' => ['type' => 'STRING'],
                        'address' => ['type' => 'STRING'],
                        'phone' => ['type' => 'STRING'],
                        'email' => ['type' => 'STRING'],
                        'fax' => ['type' => 'STRING'],
                        'url' => ['type' => 'STRING'],
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(60)
            ->post("{$base}/{$model}:generateContent?key={$apiKey}", $payload);

        if (!$response->successful()) {
            \Log::warning('Contact scan_card OCR failed', [
                'status' => $response->status(),
                'model' => $model,
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw ValidationException::withMessages(['message' => '画像ファイルの読み取りに失敗しました。']);
        }

        $data = $response->json();
        $text = data_get($data, 'candidates.0.content.parts.0.text');

        if (empty($text)) {
            throw ValidationException::withMessages(['message' => 'データ出力できません。']);
        }

        // Clean up and decode JSON
        $text = preg_replace('/^```json\s*|\s*```$/', '', trim($text));
        $text = preg_replace('/^json\s+/i', '', $text);
        $jsonData = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages(['message' => '無効レスポンス。']);
        }

        // Require at least something usable; the user can fill the rest manually.
        $name = $jsonData['name'] ?? null;
        $companyName = $jsonData['company_name'] ?? null;

        if (empty($name) && empty($companyName)) {
            throw ValidationException::withMessages(['message' => '名刺を認識できませんでした。手入力で登録してください。']);
        }

        return $jsonData;
    }
    public function scan_batch_cards(Request $request)
    {
        $validated = $request->validate([
            'images' => 'required|array|min:1|max:30',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'types' => 'required|array|min:1',
            'types.*' => 'string|max:100',
        ]);

        $authUser = Auth::user();

        $typeIds = [];
        foreach ($validated['types'] as $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            $typeIds[] = ContactType::firstOrCreate(['title' => $title])->id;
        }
        $typeIds = array_values(array_unique($typeIds));

        if (empty($typeIds)) {
            throw ValidationException::withMessages(['types' => 'コンタクト種類を選択してください。']);
        }

        $batch = ContactBatch::create([
            'user_id' => $authUser?->id,
            'status' => ContactBatch::STATUS_QUEUED,
            'contact_type_id' => $typeIds[0],
            'type_ids' => $typeIds,
        ]);

        $storageDisk = Storage::disk('local');
        $directory = "contact_batches/{$batch->id}";
        $storageDisk->makeDirectory($directory);
        $this->contactCardSplitService->createItems($batch, $request->file('images', []), $directory);

        if ($batch->items()->count() === 0) {
            $batch->delete();
            throw ValidationException::withMessages(['message' => '画像ファイルがありません。']);
        }

        $this->contactBatchSubmissionService->submit($batch);

        return response()->json($this->transformBatch($batch->fresh('items.contactRecord.collaborators')));
    }
    public function check_batch_status(Request $request) 
    {
        $validated = $request->validate([
            'batch_id' => 'required|integer|exists:contact_batches,id',
        ]);

        $batch = ContactBatch::with(['items.contactRecord.collaborators'])->findOrFail($validated['batch_id']);
        $this->assertBatchOwner($batch);

        return response()->json($this->transformBatch($batch));
    }
    public function contact_batches()
    {
        $batches = ContactBatch::query()
            ->ownedBy(Auth::id())
            ->whereNull('dismissed_at')
            ->where(function ($query) {
                $query->where('updated_at', '>=', now()->subDays(14))
                    ->orWhereIn('status', [
                        ContactBatch::STATUS_QUEUED,
                        ContactBatch::STATUS_SCANNING,
                        ContactBatch::STATUS_ENRICHING,
                    ]);
            })
            ->with(['items.contactRecord.collaborators'])
            ->latest()
            ->limit(10)
            ->get();

        $batches = $batches->map(function (ContactBatch $batch) {
            if (!in_array($batch->status, [
                ContactBatch::STATUS_QUEUED,
                ContactBatch::STATUS_SCANNING,
                ContactBatch::STATUS_ENRICHING,
            ], true)) {
                return $batch;
            }

            $batch = $this->contactBatchMonitorService->refresh($batch);
            $this->contactBatchNotificationService->notifyIfNeeded($batch);

            return $batch;
        })->filter(function (ContactBatch $batch) {
            return !$batch->dismissed_at;
        })->values();

        return response()->json(
            $batches->map(fn (ContactBatch $batch) => $this->transformBatch($batch))->values()
        );
    }
    public function contact_batch_notifications()
    {
        $notifications = ContactBatchNotification::query()
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->with(['batch.items.contactRecord.collaborators'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json(
            $notifications->map(fn (ContactBatchNotification $notification) => $this->transformBatchNotification($notification))->values()
        );
    }
    public function contact_batch_notification_read(ContactBatchNotification $notification)
    {
        $this->assertBatchNotificationOwner($notification);

        if (!$notification->read_at) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return response()->json(['ok' => true]);
    }
    public function dismiss_contact_batch(ContactBatch $batch)
    {
        $this->assertBatchOwner($batch);

        // Any batch can be dismissed (including stuck ones); if it later
        // completes, the completion notification still fires.
        if (!$batch->dismissed_at) {
            $batch->forceFill([
                'dismissed_at' => now(),
            ])->save();
        }

        ContactBatchNotification::query()
            ->where('user_id', Auth::id())
            ->where('contact_batch_id', $batch->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }
    protected function transformBatch(ContactBatch $batch): array
    {
        $batch->loadMissing('items.contactRecord.collaborators');

        $items = $batch->items->sortBy('index')->values();
        $statusCounts = [
            'total' => $items->count(),
            ContactBatchItem::STATUS_SCANNING => $items->where('status', ContactBatchItem::STATUS_SCANNING)->count(),
            ContactBatchItem::STATUS_COMPLETED => $items->where('status', ContactBatchItem::STATUS_COMPLETED)->count(),
            ContactBatchItem::STATUS_FAILED => $items->where('status', ContactBatchItem::STATUS_FAILED)->count(),
        ];

        $startedAt = $batch->scan_requested_at ?? $batch->created_at;
        $finishedAt = $batch->enrich_completed_at ?? $batch->scan_completed_at;
        $durationSeconds = $startedAt
            ? $startedAt->diffInSeconds($finishedAt ?? now())
            : null;

        return [
            'id' => $batch->id,
            'status' => $this->mapBatchStatus($batch->status),
            'scan_operation' => $batch->scan_operation,
            'enrich_operation' => $batch->enrich_operation,
            'scan_attempts' => $batch->scan_attempts,
            'enrich_attempts' => $batch->enrich_attempts,
            'scan_requested_at' => optional($batch->scan_requested_at)->toIso8601String(),
            'scan_completed_at' => optional($batch->scan_completed_at)->toIso8601String(),
            'enrich_requested_at' => optional($batch->enrich_requested_at)->toIso8601String(),
            'enrich_completed_at' => optional($batch->enrich_completed_at)->toIso8601String(),
            'dismissed_at' => optional($batch->dismissed_at)->toIso8601String(),
            'created_at' => optional($batch->created_at)->toIso8601String(),
            'updated_at' => optional($batch->updated_at)->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'counts' => $statusCounts,
            'error' => $batch->error,
            'items' => $items->map(function (ContactBatchItem $item) {
                $contact = $item->contactRecord;

                return [
                    'id' => $item->id,
                    'original_filename' => $item->original_filename,
                    'index' => $item->index,
                    'status' => $this->mapItemStatus($item->status),
                    'error' => $item->error,
                    'scan_result' => $item->scan_result,
                    'enrich_result' => $item->enrich_result,
                    'needs_review' => (bool) $item->needs_review,
                    'card_hash' => $item->card_hash,
                    'duplicate_candidates' => $item->duplicate_candidates,
                    'contact_record_id' => $item->contact_record_id,
                    'created_at' => optional($item->created_at)->toIso8601String(),
                    'updated_at' => optional($item->updated_at)->toIso8601String(),
                    'contact' => $contact ? [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'company_name' => $contact->company_name,
                        'position' => $contact->position,
                        'card_hash' => $contact->card_hash,
                        'is_duplicate' => (bool) $contact->is_duplicate,
                        'duplicate_of' => $contact->duplicate_of,
                        'card_path' => $contact->card_path,
                        'collaborators' => $contact->collaborators
                            ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name, 'role' => $user->pivot->role])
                            ->all(),
                    ] : null,
                ];
            })->all(),
        ];
    }
    protected function transformBatchNotification(ContactBatchNotification $notification): array
    {
        $notification->loadMissing('batch.items.contactRecord.collaborators');

        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'status' => $notification->status,
            'url' => $notification->url,
            'read_at' => optional($notification->read_at)->toIso8601String(),
            'created_at' => optional($notification->created_at)->toIso8601String(),
            'batch' => $notification->batch ? $this->transformBatch($notification->batch) : null,
        ];
    }

    private function mapBatchStatus(string $status): string
    {
        return match ($status) {
            ContactBatch::STATUS_QUEUED => 'queued',
            ContactBatch::STATUS_SCANNING => 'scanning',
            ContactBatch::STATUS_ENRICHING => 'enriching',
            ContactBatch::STATUS_COMPLETED => 'completed',
            ContactBatch::STATUS_FAILED => 'failed',
            default => 'scanning',
        };
    }

    private function mapItemStatus(string $status): string
    {
        return match ($status) {
            ContactBatchItem::STATUS_QUEUED => 'queued',
            ContactBatchItem::STATUS_SCANNING => 'scanning',
            ContactBatchItem::STATUS_SCANNED => 'scanned',
            ContactBatchItem::STATUS_ENRICHING => 'enriching',
            ContactBatchItem::STATUS_COMPLETED => 'completed',
            ContactBatchItem::STATUS_FAILED => 'failed',
            default => 'scanning',
        };
    }

    protected function assertBatchOwner(ContactBatch $batch): void
    {
        $userId = Auth::id();
        if ($batch->user_id && $batch->user_id !== $userId) {
            abort(403, '指定されたバッチにはアクセスできません。');
        }
    }
    protected function assertBatchNotificationOwner(ContactBatchNotification $notification): void
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403, '指定された通知にはアクセスできません。');
        }
    }
    public function get_batch_results(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|integer|exists:contact_batches,id',
        ]);

        $batch = ContactBatch::with([
            'items.contactRecord.collaborators',
        ])->findOrFail($validated['batch_id']);
        $this->assertBatchOwner($batch);
        $batch = $this->contactBatchMonitorService->refresh($batch);
        $this->contactBatchNotificationService->notifyIfNeeded($batch);

        return response()->json($this->transformBatch($batch));
    }
    
    public function duplicate_index()
    {
        $userId = Auth::id();

        $duplicates = ContactRecord::with(['collaborators', 'type'])
            ->where('is_duplicate', true)
            ->when($userId, function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('created_by', $userId)
                        ->orWhere('updated_by', $userId)
                        ->orWhereHas('collaborators', function ($c) use ($userId) {
                            $c->where('user_id', $userId);
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->get();

        $payload = $duplicates->map(function (ContactRecord $contact) {
            return [
                'contact' => $this->formatContactRecord($contact),
                'candidates' => $this->duplicateCandidates($contact),
            ];
        });

        return response()->json([
            'duplicates' => $payload,
        ]);
    }

    public function resolve_duplicate(Request $request, ContactRecord $contact)
    {
        if (!$contact->is_duplicate) {
            return response()->json([
                'message' => 'このコンタクトは重複フラグが設定されていません。',
            ], 422);
        }

        $data = $request->validate([
            'action' => 'required|in:keep,merge',
            'target_id' => 'nullable|integer|exists:contact_records,id',
        ]);

        if ($data['action'] === 'keep') {
            $contact->update([
                'is_duplicate' => false,
                'duplicate_of' => null,
            ]);

            ContactBatchItem::where('contact_record_id', $contact->id)->update([
                'needs_review' => false,
                'duplicate_candidates' => null,
            ]);

            return response()->json([
                'status' => 'kept',
                'contact' => $this->formatContactRecord($contact->fresh(['collaborators', 'type'])),
            ]);
        }

        $targetId = $data['target_id'] ?? $contact->duplicate_of;
        if (!$targetId || $targetId == $contact->id) {
            throw ValidationException::withMessages(['target_id' => '統合先のコンタクトを選択してください。']);
        }

        $target = ContactRecord::with(['collaborators', 'type'])->findOrFail($targetId);

        $deletedCardPath = null;

        DB::transaction(function () use ($contact, $target, &$deletedCardPath) {
            $contact->loadMissing(['collaborators', 'types']);

            $fields = ['position', 'address', 'phone', 'email', 'fax', 'url', 'description', 'data'];
            foreach ($fields as $field) {
                if (empty($target->$field) && !empty($contact->$field)) {
                    $target->$field = $contact->$field;
                }
            }

            if (empty($target->contact_type_id) && $contact->contact_type_id) {
                $target->contact_type_id = $contact->contact_type_id;
            }

            if ((empty($target->card_path) || !Storage::disk('local')->exists($target->card_path)) && $contact->card_path) {
                $target->card_path = $contact->card_path;
            }

            if (empty($target->card_hash) && $contact->card_hash) {
                $target->card_hash = $contact->card_hash;
            }

            $target->is_duplicate = false;
            $target->duplicate_of = null;
            $target->save();

            $collaboratorData = [];
            foreach ($contact->collaborators as $user) {
                $collaboratorData[$user->id] = ['role' => $user->pivot->role];
            }

            if ($contact->created_by) {
                $collaboratorData[$contact->created_by] = ['role' => 'owner'];
            }

            if (!empty($collaboratorData)) {
                $target->collaborators()->syncWithoutDetaching($collaboratorData);
            }

            $target->types()->syncWithoutDetaching($contact->types->pluck('id')->all());

            ContactBatchItem::where('contact_record_id', $contact->id)->update([
                'contact_record_id' => $target->id,
                'needs_review' => false,
                'duplicate_candidates' => null,
            ]);

            $contact->collaborators()->detach();
            $contact->delete();

            if ($contact->card_path && $contact->card_path !== $target->card_path) {
                $deletedCardPath = $contact->card_path;
            }
        });

        if ($deletedCardPath) {
            Storage::disk('local')->delete($deletedCardPath);
        }

        $target->refresh();

        return response()->json([
            'status' => 'merged',
            'target' => $this->formatContactRecord($target->loadMissing(['collaborators', 'type'])),
        ]);
    }
    public function get_batch_company_data()
    {
        return response()->json([
            'message' => 'Batch enrichment is handled automatically when the job completes.'],
            410
        );
    }
    public function scan_card(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:8000',
        ]);
        $file = $request->file('image');
        $base64Image = base64_encode(file_get_contents($file));
        $mime = $file->getMimeType() ?: 'image/jpeg';

        // Real-time step: OCR the card for the basic contact fields only (fast).
        // The slower company-profile enrichment runs in the background after the
        // record is saved (see create_contact -> EnrichContactCompany).
        $cardData = $this->get_company_name($base64Image, $mime);

        return response()->json(['data' => $cardData]);
    }
    public function contact_list(Request $request)
    {
        
        $contacts = ContactRecord::orderBy('created_at', 'desc')
            ->with(['comments' =>
                function ($q) {
                    $q->with(['user', 'files']);
                },
                'updater', 'creator', 'type', 'types', 'collaborators', 'projects', 'relatedContacts', 'files'
            ])->get();
        return response()->json($contacts);
    }
    // 履歴 (change history) — loaded on demand for the detail modal, not with the list.
    public function list_contact_histories(int $contactId)
    {
        return response()->json(
            ContactRecordHistory::where('contact_record_id', $contactId)
                ->with('user')
                ->orderByDesc('id')
                ->get()
        );
    }

    // 非公開メモ is a per-user, timestamped, add/delete log (private to each user).
    public function list_private_memos(int $contactId)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403, 'メモを利用するにはログインが必要です。');
        }
        $memos = ContactPrivateMemo::where('contact_record_id', $contactId)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();
        return response()->json($memos);
    }

    public function add_private_memo(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403, 'メモを追加するにはログインが必要です。');
        }
        $data = $request->validate([
            'contact_id' => 'required|integer|exists:contact_records,id',
            'body' => 'required|string|max:2000',
        ]);
        $memo = ContactPrivateMemo::create([
            'contact_record_id' => $data['contact_id'],
            'user_id' => $userId,
            'body' => $data['body'],
        ]);
        return response()->json($memo->fresh(), 201);
    }

    public function delete_private_memo(int $memoId)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403);
        }
        // Only the author can delete their own private memo.
        $memo = ContactPrivateMemo::where('id', $memoId)->where('user_id', $userId)->first();
        if ($memo) {
            $memo->delete();
        }
        return response()->json(['status' => 'ok']);
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
        // Return the full storage-relative path so `/cdn/{card_path}` resolves to
        // storage/app/{card_path}. Returning the bare id here left card images 404.
        return response()->json("card_files/$unique_path.webp");
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
            'department' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:250',
            'phone' => 'nullable|string|max:100',
            'email' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:100',
            'url' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'strategy' => 'nullable|string',
            'card_path' => 'nullable|string',
            'data' => 'nullable|string',
            'types' => 'nullable|array',
            'types.*' => 'string|max:100',
        ]);

        $typeTitles = $validatedData['types'] ?? [];
        unset($validatedData['types']);

        $id = $request->id ?? null;

        // Snapshot the pre-edit state so we can log field-level 履歴 (change history).
        $existing = $id ? ContactRecord::find($id) : null;
        $oldSnapshot = $existing ? $this->historySnapshot($existing) : [];
        $oldTypeTitles = $existing ? $existing->types()->pluck('contact_types.title')->sort()->values()->all() : [];

        $record = ContactRecord::updateOrCreate(["id" => $id], $validatedData);

        if ($id == null) {
            $record->update([
                'created_by' => $this->active_user()->id
            ]);
            // Register the creator as an owner collaborator. Permissions (edit/delete,
            // no forced follow) are derived from the collaborators pivot role, not
            // created_by — mirrors the batch import path (syncCollaborator).
            $record->collaborators()->syncWithoutDetaching([
                $this->active_user()->id => ['role' => 'owner'],
            ]);
        }

        $typeIds = [];
        if ($request->has('types')) {
            $typeIds = $this->syncContactTypes($record, $typeTitles);
        }

        $record->update([
            'updated_by' => $this->active_user()->id,
            'contact_type_id' => $typeIds[0] ?? $record->contact_type_id,
        ]);

        // 履歴: record creation, or field-level diffs on edit.
        $userId = $this->active_user()->id;
        if ($id == null) {
            ContactRecordHistory::create([
                'contact_record_id' => $record->id,
                'user_id' => $userId,
                'event' => 'created',
            ]);
        } else {
            $record->refresh();
            $this->logContactChanges(
                $record,
                $oldSnapshot,
                $oldTypeTitles,
                $request->has('types') ? $typeTitles : null,
                $userId
            );
        }

        // Background company enrichment: only for brand-new records that have a
        // company but no profile yet. Runs after the response is sent (the queue
        // connection is `sync`), so the user never waits on the create screen.
        $record->refresh();
        if ($id == null && !empty($record->company_name) && empty($record->data)) {
            // Mark as pending so the UI can show "企業情報を取得中" until the job
            // (dispatchAfterResponse, sync queue) flips it to completed/failed.
            $record->update(['enrichment_status' => 'pending']);
            EnrichContactCompany::dispatchAfterResponse($record->id);
        }

        return response('OK');
    }
    // Fields tracked for 履歴 (change history).
    private function historySnapshot(ContactRecord $record): array
    {
        return [
            'name' => $record->name,
            'company_name' => $record->company_name,
            'department' => $record->department,
            'position' => $record->position,
            'address' => $record->address,
            'phone' => $record->phone,
            'email' => $record->email,
            'fax' => $record->fax,
            'url' => $record->url,
            'description' => $record->description,
        ];
    }

    // Diff old vs new snapshot (and types when submitted) → one history row per changed field.
    private function logContactChanges(ContactRecord $record, array $old, array $oldTypeTitles, ?array $newTypeTitles, ?int $userId): void
    {
        $new = $this->historySnapshot($record);
        foreach ($new as $field => $newVal) {
            $oldVal = $old[$field] ?? null;
            if ((string) ($oldVal ?? '') === (string) ($newVal ?? '')) {
                continue;
            }
            ContactRecordHistory::create([
                'contact_record_id' => $record->id,
                'user_id' => $userId,
                'event' => 'updated',
                'field' => $field,
                'old_value' => $oldVal,
                'new_value' => $newVal,
            ]);
        }

        if ($newTypeTitles !== null) {
            $newSorted = collect($newTypeTitles)->map(fn ($t) => trim((string) $t))->filter()->unique()->sort()->values()->all();
            $oldSorted = array_values($oldTypeTitles);
            if ($newSorted !== $oldSorted) {
                ContactRecordHistory::create([
                    'contact_record_id' => $record->id,
                    'user_id' => $userId,
                    'event' => 'updated',
                    'field' => 'types',
                    'old_value' => implode('、', $oldSorted),
                    'new_value' => implode('、', $newSorted),
                ]);
            }
        }
    }

    // Only owner/follower collaborators may manage a contact's files — mirrors the
    // frontend canManage gate (viewers get read-only). Enforced server-side because
    // file deletion is irreversible (physical file removed from disk).
    private function userCanManageContact(int $contactId): bool
    {
        $userId = Auth::id();
        if (!$userId) {
            return false;
        }
        return DB::table('contact_record_user')
            ->where('contact_record_id', $contactId)
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'follower'])
            ->exists();
    }

    // Attach previously-uploaded temp files (from /attach_upload_api) to a contact,
    // as either 裏面 photos (kind=image) or documents (kind=file). Mirrors the
    // comment-attach flow: move temp_upload/{id}.{ext} → contact_files/{id}_{uid}.{ext}.
    public function contact_attach_files(Request $request)
    {
        $data = $request->validate([
            'record_id' => 'required|integer|exists:contact_records,id',
            'kind' => 'required|in:image,file',
            'attached_temp_files' => 'required|array|min:1',
            'attached_temp_files.*.id' => 'required|integer',
        ]);

        if (!$this->userCanManageContact((int) $data['record_id'])) {
            abort(403, 'このコンタクトのファイルを追加する権限がありません。');
        }

        $record = ContactRecord::findOrFail($data['record_id']);
        $userId = Auth::id();
        $path = 'contact_files';
        File::isDirectory(storage_path("app/{$path}")) or File::makeDirectory(storage_path("app/{$path}"), 0755, true, true);

        foreach ($data['attached_temp_files'] as $item) {
            // Only the caller's OWN, still-pending temp upload may be attached. This
            // blocks hijacking another user's / another feature's messageFile (IDOR)
            // and prevents stamping a phantom row when no physical temp file exists.
            $file = messageFile::where('id', $item['id'])
                ->where('user_id', $userId)
                ->whereNull('message_id')
                ->whereNull('comment_record_id')
                ->whereNull('contact_record_id')
                ->first();
            if (!$file) {
                continue;
            }
            $src = "temp_upload/{$file->id}.{$file->extension}";
            if (!Storage::disk('local')->exists($src)) {
                continue;
            }
            $dest = "{$path}/{$file->id}_{$file->user_id}.{$file->extension}";
            Storage::disk('local')->move($src, $dest);
            $file->update([
                'contact_record_id' => $record->id,
                'contact_file_kind' => $data['kind'],
            ]);
        }

        return response()->json($record->fresh('files')->files);
    }

    public function contact_file_delete(Request $request)
    {
        $data = $request->validate(['id' => 'required|integer']);
        $file = messageFile::whereNotNull('contact_record_id')->find($data['id']);
        if ($file) {
            // Physical deletion is irreversible — require owner/follower on the contact.
            if (!$this->userCanManageContact((int) $file->contact_record_id)) {
                abort(403, 'このファイルを削除する権限がありません。');
            }
            $rel = "contact_files/{$file->id}_{$file->user_id}.{$file->extension}";
            if (Storage::disk('local')->exists($rel)) {
                Storage::disk('local')->delete($rel);
            }
            $file->delete();
        }
        return response()->json(['status' => 'ok']);
    }

    // OCR a stored 裏面/名刺 photo (kind=image) and return the extracted fields, so the
    // user can review + apply them to the contact (re-scan after a job change, etc.).
    public function contact_scan_file(Request $request)
    {
        $data = $request->validate(['file_id' => 'required|integer']);
        $file = messageFile::whereNotNull('contact_record_id')
            ->where('contact_file_kind', 'image')
            ->find($data['file_id']);
        if (!$file) {
            abort(404, '画像が見つかりません。');
        }
        if (!$this->userCanManageContact((int) $file->contact_record_id)) {
            abort(403, 'この操作を行う権限がありません。');
        }
        $rel = "contact_files/{$file->id}_{$file->user_id}.{$file->extension}";
        $abs = storage_path("app/{$rel}");
        if (!is_file($abs)) {
            abort(404, '画像ファイルが見つかりません。');
        }
        $base64 = base64_encode(file_get_contents($abs));
        $mime = @mime_content_type($abs) ?: 'image/jpeg';
        $cardData = $this->get_company_name($base64, $mime);
        return response()->json(['data' => $cardData]);
    }

    private function syncContactTypes(ContactRecord $record, array $typeTitles): array
    {
        $ids = [];
        foreach ($typeTitles as $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            // Match existing types by title; create new ones on demand (free entry).
            $type = ContactType::firstOrCreate(['title' => $title]);
            $ids[] = $type->id;
        }
        $ids = array_values(array_unique($ids));
        $record->types()->sync($ids);
        return $ids;
    }

    private function formatContactRecord(ContactRecord $contact): array
    {
        $contact->loadMissing(['collaborators', 'type', 'types']);

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'company_name' => $contact->company_name,
            'position' => $contact->position,
            'address' => $contact->address,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'fax' => $contact->fax,
            'url' => $contact->url,
            'card_path' => $contact->card_path,
            'card_hash' => $contact->card_hash,
            'is_duplicate' => (bool) $contact->is_duplicate,
            'duplicate_of' => $contact->duplicate_of,
            'contact_type_id' => $contact->contact_type_id,
            'type' => $contact->type ? [
                'id' => $contact->type->id,
                'title' => $contact->type->title,
            ] : null,
            'types' => $contact->types
                ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title])->all(),
            'collaborators' => $contact->collaborators
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->pivot->role,
                ])->all(),
        ];
    }

    private function duplicateCandidates(ContactRecord $contact): array
    {
        $query = ContactRecord::query()->where('id', '!=', $contact->id);

        if ($contact->card_hash) {
            $query->where('card_hash', $contact->card_hash);
        } elseif ($contact->name && $contact->company_name) {
            $query->where(function ($q) use ($contact) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($contact->name ?? '')])
                    ->whereRaw('LOWER(company_name) = ?', [mb_strtolower($contact->company_name ?? '')]);

                if ($contact->email) {
                    $q->orWhereRaw('LOWER(email) = ?', [mb_strtolower($contact->email ?? '')]);
                }
            });
        } else {
            return [];
        }

        $results = $query->limit(5)->get([
            'id',
            'name',
            'company_name',
            'email',
            'phone',
            'card_path',
            'card_hash',
            'updated_at',
        ]);

        if ($contact->duplicate_of && !$results->firstWhere('id', $contact->duplicate_of)) {
            $target = ContactRecord::find($contact->duplicate_of);
            if ($target) {
                $results->prepend($target);
            }
        }

        return $results->unique('id')->values()->map(function (ContactRecord $record) {
            return [
                'id' => $record->id,
                'name' => $record->name,
                'company_name' => $record->company_name,
                'email' => $record->email,
                'phone' => $record->phone,
                'card_path' => $record->card_path,
                'card_hash' => $record->card_hash,
                'updated_at' => optional($record->updated_at)->toIso8601String(),
            ];
        })->all();
    }
    public function contact_create_comment(Request $request)
    {
        $data = $request->validate([
            'record_id' => 'required',
            'comment' => 'required|string',
        ]);
        $user = $this->active_user();
        $record = ContactRecord::findOrFail($request->record_id);
        $report = $record->comments()->create([
            'comment' => $request->comment,
            'user_id' => $user->id,
        ]);
        if($request->attached_temp_files){
            $path = "contact_comment_files";
            foreach($request->attached_temp_files as $item){
                // Only the caller's own still-pending temp upload — blocks hijacking
                // another user's / another feature's messageFile (IDOR).
                $file = messageFile::where('id', $item['id'])
                    ->where('user_id', $user->id)
                    ->whereNull('message_id')
                    ->whereNull('comment_record_id')
                    ->whereNull('contact_record_id')
                    ->first();
                if (!$file) {
                    continue;
                }
                $src = "temp_upload/{$file->id}.{$file->extension}";
                if (!Storage::disk('local')->exists($src)) {
                    continue;
                }
                File::isDirectory(storage_path("app/{$path}")) or File::makeDirectory(storage_path("app/{$path}"), 0755, true, true);
                $destPath = "{$file->id}_{$file->user_id}.{$file->extension}";
                Storage::disk('local')->move($src, "{$path}/{$destPath}");
                $file->update(['comment_record_id' => $report->id]);
            }
        }
        $syntax = '/\[To:(.*?)\:\]/';
        preg_match_all($syntax, $request->comment, $matches);
        $mentioned_targets = $matches[1];
        $emails = User::query()
            ->whereKeyNot($user->id)             
            ->where('on_leave', false)         
            ->whereIn('name', $mentioned_targets)
            ->whereNotNull('email')
            ->pluck('email')                    
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->map(fn ($e) => strtolower($e))     
            ->unique()
            ->values()
            ->all();
            
        if(!empty($emails)){
            $content = strip_tags(htmlspecialchars_decode($request->comment));
            $blocked = preg_match('/\b(pass|pw|password)\b/i', $request->comment)
            || str_contains($request->comment, 'パスワード')
            || str_contains($request->comment, 'ﾊﾟｽﾜｰﾄﾞ');       
            $url = rtrim(config('app.url'), '/') . "/contact/tab2/{$record->id}";
            $url .= "?mention=true";          
            SendContactEmail::dispatchSync($emails, new ContactMention($record, $content, $blocked, $url, $user));
        }
        
        return response()->json(['id' => $report->id], 201);
    }
    public function follow_contact(Request $request)
    {
        $data = $request->validate([
            'record_id' => 'required'
        ]);
        $user = $this->active_user();
        $contact = ContactRecord::with(['collaborators', 'type'])->findOrFail($data['record_id']);
        DB::transaction(function () use ($contact, $user) {
            $collaboratorData = [];
            
            $collaboratorData[$user->id] = ['role' => 'follower'];
            

            if (!empty($collaboratorData)) {
                $contact->collaborators()->syncWithoutDetaching($collaboratorData);
            }
        });
        $contact->refresh();

        return response()->json([
            'status' => 'subscriped',
            'target' => $this->formatContactRecord($contact->loadMissing(['collaborators', 'type'])),
        ]);
    }
    public function contact_comment_read(Request $request, int $contactId) {
        $user = $this->active_user();
        
        $lastRead = ContactCommentLastRead::updateOrCreate(
            ['contact_record_id' => $contactId, 'user_id' => $user->id],
            ['last_read_at' => now()]
        );

        return response()->json(['status' => 'ok']);
    }
    public function get_contact_comment_badge() {
        $user = $this->active_user();
        $userId = $user->id;
               
        $rows = DB::table('contact_record_comments as c')
        ->join('contact_record_user as cc', function ($j) use ($userId) {
            $j->on('cc.contact_record_id', '=', 'c.contact_record_id')
              ->where('cc.user_id', '=', $userId);   // only contacts I collaborate on
        })
        ->leftJoin('contact_comment_last_reads as lr', function ($j) use ($userId) {
            $j->on('lr.contact_record_id', '=', 'c.contact_record_id')
              ->where('lr.user_id', '=', $userId);
        })
        ->where('c.user_id', '!=', $userId)
        ->whereRaw('c.created_at > COALESCE(lr.last_read_at, "1970-01-01 00:00:00")')
        ->groupBy('c.contact_record_id')
        ->select('c.contact_record_id', DB::raw('COUNT(*) AS unread_count'))
        ->get();

        $data = $rows->map(fn($r) => [
            'contact_id' => (int) $r->contact_record_id,
            'comments'   => (int) $r->unread_count,
        ])->values();

        return response()->json($data);
    }
    public function unfollow_contact(int $contactId)
    {
        $uid = $this->active_user()->id;

        $deleted = DB::table('contact_record_user')
            ->where('user_id', $uid)
            ->where('contact_record_id', $contactId)
            ->delete();

        return response()->json([
            'status' => $deleted ? 'deleted' : 'noop',
            'deleted_rows' => $deleted,
        ]);
    }

    public function link_contact_project(Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'required|integer|exists:contact_records,id',
            'project_id' => 'required|integer|exists:project_records,id',
        ]);
        ContactRecord::findOrFail($data['contact_id'])
            ->projects()->syncWithoutDetaching([$data['project_id']]);

        return response()->json(['status' => 'ok']);
    }

    public function unlink_contact_project(Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'required|integer|exists:contact_records,id',
            'project_id' => 'required|integer',
        ]);
        ContactRecord::findOrFail($data['contact_id'])
            ->projects()->detach($data['project_id']);

        return response()->json(['status' => 'ok']);
    }

    public function link_related_contact(Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'required|integer|exists:contact_records,id',
            'related_id' => 'required|integer|exists:contact_records,id|different:contact_id',
        ]);
        $contact = ContactRecord::findOrFail($data['contact_id']);
        $related = ContactRecord::findOrFail($data['related_id']);
        // Symmetric link — store both directions so it shows on either contact.
        $contact->relatedContacts()->syncWithoutDetaching([$related->id]);
        $related->relatedContacts()->syncWithoutDetaching([$contact->id]);

        return response()->json(['status' => 'ok']);
    }

    public function unlink_related_contact(Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'required|integer|exists:contact_records,id',
            'related_id' => 'required|integer',
        ]);
        $contact = ContactRecord::findOrFail($data['contact_id']);
        $contact->relatedContacts()->detach($data['related_id']);
        optional(ContactRecord::find($data['related_id']))->relatedContacts()->detach($contact->id);

        return response()->json(['status' => 'ok']);
    }

    public function search_projects(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $userId = $this->active_user()->id;

        $projects = ProjectRecord::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->where(function ($query) use ($userId) {
                $query->where('director_id', $userId)
                    ->orWhereIn('id', function ($sub) use ($userId) {
                        $sub->select('project_id')->from('project_members')->where('user_id', $userId);
                    });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($projects);
    }
}
