<?php

namespace App\Http\Controllers;

use App\Jobs\SendContactEmail;
use App\Models\ContactBatch;
use App\Models\ContactBatchItem;
use App\Models\ContactBatchNotification;
use App\Models\ContactRecord;
use App\Models\ContactType;
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
use App\Services\ContactScanService; 
use App\Services\ContactBatchSubmissionService;
use App\Services\ContactBatchMonitorService;
use App\Services\ContactBatchNotificationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ContactController extends Controller
{

    protected $gemini_url;
    protected $contactScanService;
    protected $contactBatchSubmissionService;
    protected $contactBatchMonitorService;
    protected $contactBatchNotificationService;

    public function __construct(
        ContactScanService $contactScanService,
        ContactBatchSubmissionService $contactBatchSubmissionService,
        ContactBatchMonitorService $contactBatchMonitorService,
        ContactBatchNotificationService $contactBatchNotificationService
    )
    {
        $this->gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        $this->contactScanService = $contactScanService;
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
            "tools" => [
                [
                    "google_search" => (object)[]
                ]
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain'
            ],
        ];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent?key=$apiKey";
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
    public function scan_batch_cards(Request $request)
    {
        $validated = $request->validate([
            'images' => 'required|array|min:1|max:30',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'type_id' => 'required|integer',
            'p_type' => 'nullable|string|max:100',
        ]);

        $authUser = Auth::user();

        $typeId = (int) $validated['type_id'];
        $pseudoType = $validated['p_type'] ?? null;

        if ($typeId === -1) {
            if (!$pseudoType) {
                throw ValidationException::withMessages(['type_id' => '新規コンタクト種類の名称を入力してください。']);
            }
            $type = ContactType::firstOrCreate(['title' => $pseudoType]);
            $typeId = $type->id;
        }

        $batch = ContactBatch::create([
            'user_id' => $authUser?->id,
            'status' => ContactBatch::STATUS_QUEUED,
            'contact_type_id' => $typeId,
            'pseudo_type' => $pseudoType,
        ]);

        $storageDisk = Storage::disk('local');
        $directory = "contact_batches/{$batch->id}";
        $storageDisk->makeDirectory($directory);
        foreach ($request->file('images', []) as $index => $file) {
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid()->toString() . '.' . $extension;
            $relativePath = $file->storeAs($directory, $filename, 'local');

            ContactBatchItem::create([
                'contact_batch_id' => $batch->id,
                'index' => $index,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $relativePath,
                'status' => ContactBatchItem::STATUS_QUEUED,
            ]);
        }

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
            ->where(function ($query) {
                $query->whereNull('dismissed_at')
                    ->orWhereIn('status', [
                        ContactBatch::STATUS_QUEUED,
                        ContactBatch::STATUS_SCANNING,
                        ContactBatch::STATUS_ENRICHING,
                    ]);
            })
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
            return !$batch->dismissed_at || in_array($batch->status, [
                ContactBatch::STATUS_QUEUED,
                ContactBatch::STATUS_SCANNING,
                ContactBatch::STATUS_ENRICHING,
            ], true);
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

        if (!in_array($batch->status, [ContactBatch::STATUS_COMPLETED, ContactBatch::STATUS_FAILED], true)) {
            return response()->json([
                'message' => '進行中の取り込み状況は閉じられません。',
            ], 422);
        }

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
            $contact->loadMissing('collaborators');

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
        
        $contacts = ContactRecord::orderBy('created_at', 'desc')
            ->with(['comments' => 
                function ($q) {
                    $q->with(['user', 'files']);
                },
                'updater', 'creator', 'type', 'collaborators'
            ])->get();
        return response()->json($contacts);
    }
    public function update_private_memo(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403, 'メモを更新するにはログインが必要です。');
        }

        $data = $request->validate([
            'contact_id' => 'required|integer|exists:contact_records,id',
            'private_memo' => 'nullable|string|max:2000',
        ]);

        $contact = ContactRecord::with(['collaborators' => function ($query) use ($userId) {
            $query->where('users.id', $userId);
        }])->findOrFail($data['contact_id']);

        $existingPivot = $contact->collaborators->first();

        if ($existingPivot) {
            $contact->collaborators()->updateExistingPivot($userId, [
                'private_memo' => $data['private_memo'] ?? '',
            ]);
            $role = $existingPivot->pivot->role;
        } else {
            $role = 'viewer';
            $contact->collaborators()->attach($userId, [
                'role' => $role,
                'private_memo' => $data['private_memo'] ?? '',
            ]);
        }

        return response()->json([
            'contact_id' => $contact->id,
            'private_memo' => $data['private_memo'] ?? '',
            'role' => $role,
        ]);
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
        $name = $cardData['company_name'] ?? '';
        $url = $cardData['url'] ?? '';
        $address = $cardData['address'] ?? '';
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

    private function formatContactRecord(ContactRecord $contact): array
    {
        $contact->loadMissing(['collaborators', 'type']);

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
            foreach($request->attached_temp_files as $item){      
                $file = messageFile::findOrFail($item['id']);
                $file->update(['comment_record_id' => $report->id]);
                $path = "contact_comment_files";
                File::isDirectory(storage_path("app/{$path}")) or File::makeDirectory(storage_path("app/{$path}"), 0755, true, true);         
                $srcPath = "{$file->id}.{$file->extension}";
                $destPath = "{$file->id}_{$file->user_id}.{$file->extension}";
                $temp_path = storage_path("app/temp_upload/{$srcPath}");
                Storage::disk('local')->move("temp_upload/{$file->id}.{$file->extension}", "{$path}/{$destPath}");                
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
}
