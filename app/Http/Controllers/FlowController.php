<?php

namespace App\Http\Controllers;

use App\Models\FlowAppTool;
use App\Models\FlowAuditLog;
use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowNotification;
use App\Models\FlowRecord;
use App\Models\FlowRecordFile;
use App\Models\positionRecord;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Services\FlowFileService;
use App\Services\FlowFormulaEvaluator;
use App\Services\FlowNotificationService;
use App\Services\FlowRecordActionService;
use App\Services\FlowRelatedService;
use App\Services\FlowService;
use App\Services\KintoneImportService;
use App\Services\PdfRenderService;
use App\Support\FlowRecordActions;
use App\Support\FlowRichText;
use App\Support\FlowSystemSources;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mpdf\Output\Destination;

class FlowController extends Controller
{
    /** これを超えるアプリでは、並び順の全件を持たずに番号順で隣を出す。 */
    private const NAV_MAX_RECORDS = 20000;

    public function __construct(
        private FlowService $flowService,
        private FlowNotificationService $flowNotifications,
        private FlowRecordActionService $flowRecordActions,
    ) {}

    private function active_user()
    {
        // Double-account (act-as / linked sub-account) is dropped on this branch — always the auth user.
        return Auth::user();
    }

    /* ================================================================
     | Builder — flow definitions
     |================================================================ */

    public function getFlowDefinitions(Request $request)
    {
        $user = $this->active_user();
        $projectId = $request->input('project_id');

        $pinned = DB::table('flow_app_pins')->where('user_id', $user->id)->pluck('flow_definition_id')->flip();
        $unread = $this->flowNotifications->unreadCounts($user);

        return FlowDefinition::query()
            ->when($projectId, fn ($q) => $q->where('project_record_id', $projectId))
            ->when(! $projectId, fn ($q) => $q->whereNull('project_record_id'))
            ->with(['creator', 'appPermissions'])
            ->withCount('records')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($d) use ($user) {
                $perms = $this->flowService->effectiveAppPermissions($user, $d);
                // expose 管理 so the portal card menu only offers 設定/削除 to those who can use them
                // (both are manage-gated server-side; this keeps the UI honest)
                $d->setAttribute('can_manage', $perms['manage']);

                return $perms['view'];
            })
            ->map(function ($d) use ($user, $pinned, $unread) {
                // "全社員に公開" reflects actual permissions, not the vestigial visibility flag.
                $d->setAttribute('is_public', $d->appPermissions->contains(
                    fn ($p) => $p->subject_type === 'everyone' && $p->can_view
                ));
                $d->setAttribute('pinned', $pinned->has($d->id));
                // per-app bell badge (unread notification events for this user)
                $d->setAttribute('unread_notifications', $unread[$d->id] ?? 0);
                // 対応待ち: live count of records whose current status names this user as
                // worker — no stored rows, no prefs; it drops only when the record moves on
                $d->setAttribute('pending_actions', $this->flowService->pendingActionRecords($user, $d)->count());

                return $d->makeHidden(['appPermissions', 'statuses', 'statusActions', 'recordPermissionSets']);
            })
            ->values();
    }

    /** Toggle a per-user pin on an app (pinned apps sort to the top of the portal). */
    public function toggleAppPin(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'flow_definition_id' => 'required|integer|exists:flow_definitions,id',
            'pinned' => 'required|boolean',
        ]);
        if ($data['pinned']) {
            DB::table('flow_app_pins')->updateOrInsert(
                ['user_id' => $user->id, 'flow_definition_id' => $data['flow_definition_id']],
                ['updated_at' => now(), 'created_at' => now()],
            );
        } else {
            DB::table('flow_app_pins')
                ->where('user_id', $user->id)
                ->where('flow_definition_id', $data['flow_definition_id'])
                ->delete();
        }

        return response()->json(['ok' => true]);
    }

    /** Per-user portal view preferences (grid density + sort order). */
    public function getPortalPrefs()
    {
        $user = $this->active_user();
        $row = DB::table('flow_portal_prefs')->where('user_id', $user->id)->first();

        return response()->json([
            'density' => $row->density ?? 'normal',
            'sort' => $row->sort ?? 'created_desc',
        ]);
    }

    public function savePortalPrefs(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'density' => 'nullable|string|max:20',
            'sort' => 'nullable|string|max:30',
        ]);
        DB::table('flow_portal_prefs')->updateOrInsert(
            ['user_id' => $user->id],
            ['density' => $data['density'] ?? 'normal', 'sort' => $data['sort'] ?? 'created_desc', 'updated_at' => now()],
        );

        return response()->json(['ok' => true]);
    }

    public function getFlowDefinition($id)
    {
        $user = $this->active_user();

        $definition = FlowDefinition::query()
            ->with(['fields', 'statuses.fieldRules', 'statusActions', 'appPermissions', 'recordPermissionSets', 'fieldPermissions', 'views', 'tools'])
            ->findOrFail($id);

        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['manage'], 403);

        // expose the current user's effective app permissions (builder uses this to show the
        // bulk-truncate action only to those granted 一括処理).
        $definition->setAttribute('my_permissions', $this->flowService->effectiveAppPermissions($user, $definition));

        return response()->json($definition);
    }

    /** Delete ALL records of an app and reset its numbering to 1. Gated by 一括処理 (bulk). */
    public function truncateAppRecords($id)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with('appPermissions')->findOrFail($id);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['bulk'], 403);

        $count = DB::transaction(function () use ($definition) {
            $ids = FlowRecord::withTrashed()->where('flow_definition_id', $definition->id)->pluck('id');
            $n = $ids->count();
            if ($ids->isNotEmpty()) {
                // 添付の実体も落とす（レコードフォルダごと）
                app(FlowFileService::class)->deleteForRecordIds($definition->id, $ids->all());
                DB::table('flow_record_values')->whereIn('flow_record_id', $ids)->delete();
                DB::table('app_comments')
                    ->whereIn('commentable_id', $ids)
                    ->whereIn('commentable_type', [FlowRecord::class, 'flow_record'])
                    ->delete();
                // hard delete: soft-deleted rows would keep the (definition, record_number) unique slot
                FlowRecord::withTrashed()->whereIn('id', $ids)->forceDelete();
            }
            // reset the per-app sequence so the next record is #1
            DB::table('flow_definitions')->where('id', $definition->id)->update(['record_seq' => 0]);

            return $n;
        });

        return response()->json(['ok' => true, 'deleted' => $count]);
    }

    /** Render a saved PDF tool for one record and stream it (download or inline). */
    public function renderToolPdf(Request $request, $toolId, $recordId)
    {
        $user = $this->active_user();
        $tool = FlowAppTool::findOrFail($toolId);
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets'])->findOrFail($tool->flow_definition_id);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        $record = FlowRecord::with('values')->where('flow_definition_id', $definition->id)->findOrFail($recordId);
        abort_unless($this->flowService->recordPermissions($user, $record, $definition)['view'], 403);

        $mpdf = app(PdfRenderService::class)->render($definition, $record, $tool->config ?? []);
        $name = $this->pdfFilename($tool, $definition, $record);
        $inline = $request->boolean('inline');

        return response($mpdf->Output($name, Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.rawurlencode($name).'"',
        ]);
    }

    /** Preview an unsaved template (builder designer) against a real record — inline PDF. */
    public function previewToolPdf(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'flow_definition_id' => 'required|integer',
            'config' => 'required|array',
            'record_id' => 'nullable|integer',
        ]);
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets'])->findOrFail($data['flow_definition_id']);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['manage'], 403);

        $record = FlowRecord::with('values')->where('flow_definition_id', $definition->id)
            ->when(! empty($data['record_id']), fn ($q) => $q->whereKey($data['record_id']))
            ->orderByDesc('id')->first();
        abort_unless($record !== null, 422, 'プレビュー用のレコードがありません。まずレコードを1件作成してください。');

        $mpdf = app(PdfRenderService::class)->render($definition, $record, $data['config']);

        return response($mpdf->Output('preview.pdf', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }

    /**
     * 下敷きにするPDFを受け取る。
     *
     * 既にある帳票（契約書のひな形など）の上に差込項目を置けるようにするためのもの。
     * ここで**取り込めるかどうかを確かめてから**保存する——出力の瞬間に「この形式は読めません」と
     * 言われるより、上げた本人がその場で気づける方がいい。
     */
    public function uploadToolBackground(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'flow_definition_id' => 'required|integer',
            'file' => 'required|file|mimetypes:application/pdf|max:20480',
        ]);
        $definition = FlowDefinition::findOrFail($data['flow_definition_id']);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['manage'], 403);

        $upload = $request->file('file');
        $pages = app(PdfRenderService::class)->probeBackground($upload->getRealPath());
        if ($pages === null) {
            return response()->json([
                'message' => 'このPDFは圧縮方式が対応外で、下敷きにできません。'
                    .'PDFを開いて「印刷 → PDFに保存」または「別名で保存」で作り直したものをお試しください。',
            ], 422);
        }

        // 中身のハッシュを名前にする：同じひな形を何度上げても増えないし、推測もされない
        $hash = sha1_file($upload->getRealPath());
        $path = "flow_tool_backgrounds/{$definition->id}/{$hash}.pdf";
        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, file_get_contents($upload->getRealPath()));
        }

        return response()->json([
            'path' => $path,
            'name' => $upload->getClientOriginalName(),
            'pages' => $pages,
        ]);
    }

    /** デザイナーが下敷きを表示するための配信。アプリを設定できる人だけ。 */
    public function toolBackground(Request $request, $definitionId, $hash)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['manage'], 403);

        // ハッシュ以外の文字は受け取らない（パスに使う値なので、形で弾く）
        abort_unless(preg_match('/^[a-f0-9]{40}$/', (string) $hash) === 1, 404);

        $path = "flow_tool_backgrounds/{$definition->id}/{$hash}.pdf";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="background.pdf"',
        ]);
    }

    /**
     * 出力するPDFのファイル名。
     *
     * `{seq}` `{id}` `{app}` に加えて、`{フィールドコード}` でそのレコードの値を差し込める。
     * 「契約書No_取引先名.pdf」のように、保存した後で中身を開かずに見分けられるようにするため。
     *
     * 値の出し方は画面と同じ FlowService::displayValue を通す——ユーザーやプロジェクトは
     * IDを持っているので、そのままだとファイル名に「487」と入ってしまう。
     *
     * 知らない `{…}` はそのまま残す。黙って空にすると、書き間違えたコードが「値が空だった」と
     * 見分けられなくなる。
     */
    private function pdfFilename(FlowAppTool $tool, FlowDefinition $definition, FlowRecord $record): string
    {
        $pattern = $tool->config['filename'] ?? ($tool->name.'_{seq}');

        $replacements = [
            // {seq} は画面に出ているレコード番号。flow_records に record_seq は無く、
            // これまでは黙って内部IDに落ちていた（説明文の「レコード番号」と食い違っていた）。
            '{seq}' => (string) ($record->record_number ?? $record->id),
            '{id}' => (string) $record->id,
            '{app}' => (string) $definition->name,
        ];

        if (str_contains($pattern, '{')) {
            $fields = $definition->relationLoaded('fields') ? $definition->fields : $definition->fields()->get();
            $values = $this->flowService->recordValues($record, $fields);
            foreach ($fields as $f) {
                $token = '{'.$f->key.'}';
                if (isset($replacements[$token]) || ! str_contains($pattern, $token)) {
                    continue;
                }
                // 秘匿項目はファイル名に出さない。名前は保存先にもメールにも残る。
                if (FlowService::isSecret($f->input_type) || FlowService::isLayoutType($f->input_type)) {
                    $replacements[$token] = '';

                    continue;
                }
                $replacements[$token] = $this->filenamePart(
                    $this->flowService->displayValue($f->input_type, $values[(string) $f->id] ?? null)
                );
            }
        }

        $name = strtr($pattern, $replacements);
        // パスに使えない文字と改行を落とす。長文項目を差し込むと改行が混ざりうる。
        $name = preg_replace('/[\/\\\\:*?"<>|\r\n\t]/', '_', $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        // ファイル名の上限（多くのファイルシステムで255バイト）に収める
        $name = mb_strimwidth($name, 0, 180, '');

        return ($name !== '' ? $name : 'document').'.pdf';
    }

    /** 差し込む値を1つの文字列にする。配列（ファイル・ユーザーなど）は中黒でつなぐ。 */
    private function filenamePart(mixed $v): string
    {
        if (is_array($v)) {
            return implode('・', array_map(
                fn ($x) => is_array($x) ? (string) ($x['name'] ?? $x['label'] ?? '') : (string) $x,
                $v
            ));
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }

        return $v === null ? '' : (string) $v;
    }

    /**
     * Lightweight field list for a definition (view-gated) — used by the reference field's
     * builder config to pick which target-app field to display as the label.
     */
    public function getDefinitionFields($id)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets'])->findOrFail($id);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        return response()->json([
            'id' => $definition->id,
            'name' => $definition->name,
            // secrets are not offerable as a lookup label or field-copy source — copying one into
            // an ordinary field of another app would silently strip the encryption
            'fields' => $definition->fields
                ->reject(fn ($f) => FlowService::isSecret($f->input_type))
                ->map(fn ($f) => ['key' => $f->key, 'label' => $f->label, 'input_type' => $f->input_type, 'result_type' => $f->result_type])
                ->values(),
        ]);
    }

    /** Preview a kintone app's config + form fields mapped to our ワークフロー schema (no writes). */
    public function kintonePreview(Request $request, KintoneImportService $kintone)
    {
        $this->active_user();
        $data = $request->validate(['app_id' => 'required|integer|min:1']);

        try {
            return response()->json($kintone->preview($data['app_id']));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'kintoneアプリの取得に失敗しました。アプリIDと接続設定をご確認ください。'], 422);
        }
    }

    public function saveFlowDefinition(Request $request)
    {
        $user = $this->active_user();

        $data = $request->validate([
            'id' => 'nullable|integer|exists:flow_definitions,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color_id' => 'nullable|integer|min:0|max:255',
            'icon_svg' => 'nullable|string|max:20000',
            'icon_image' => 'nullable|string|max:400000',
            'is_active' => 'boolean',
            'use_status_flow' => 'boolean',
            'project_record_id' => 'nullable|integer',
            'fields' => 'array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.key' => 'required|string|max:255',
            // ラベル項目はHTMLを持つので255では足りない（kintoneの注意書きは1つで約1,000文字）。
            // データ項目の見出しは syncFields 側で255に丸める。
            'fields.*.label' => 'nullable|string|max:'.FlowRichText::MAX_LENGTH,
            'fields.*.input_type' => 'required|string|max:50',
            'fields.*.options' => 'nullable|array',
            'fields.*.is_required' => 'boolean',
            'fields.*.hidden' => 'boolean',
            'fields.*.layout_row' => 'integer',
            'fields.*.order_number' => 'integer',
            'fields.*.width' => 'integer|min:80|max:4000',
            'fields.*.depends_on' => 'nullable|array',
            'fields.*.validation' => 'nullable|array',
            'fields.*.formula' => 'nullable|string',
            'fields.*.result_type' => 'nullable|string',
            'statuses' => 'array',
            'statuses.*.id' => 'nullable|integer',
            'statuses.*.key' => 'required|string|max:255',
            'statuses.*.name' => 'required|string|max:255',
            'statuses.*.is_initial' => 'boolean',
            'statuses.*.color' => 'nullable|string|max:32',
            'statuses.*.ui_x' => 'nullable|integer',
            'statuses.*.ui_y' => 'nullable|integer',
            'statuses.*.field_rules' => 'array',
            'statuses.*.field_rules.*.field_key' => 'required|string',
            'statuses.*.field_rules.*.rule' => 'required|in:edit,read,hide',
            'status_actions' => 'array',
            'status_actions.*.id' => 'nullable|integer',
            'status_actions.*.from_status_key' => 'required|string',
            'status_actions.*.to_status_key' => 'required|string',
            'status_actions.*.name' => 'nullable|string|max:255',
            'status_actions.*.label' => 'required|string|max:255',
            'status_actions.*.color' => 'nullable|string|max:32',
            'status_actions.*.eligible' => 'nullable|array',
            'status_actions.*.notify' => 'boolean',
            'app_permissions' => 'array',
            'app_permissions.*.subject_type' => 'required|string',
            'app_permissions.*.subject_id' => 'nullable|integer',
            'app_permissions.*.can_view' => 'boolean',
            'app_permissions.*.can_add' => 'boolean',
            'app_permissions.*.can_edit' => 'boolean',
            'app_permissions.*.can_delete' => 'boolean',
            'app_permissions.*.can_manage' => 'boolean',
            'app_permissions.*.can_import' => 'boolean',
            'app_permissions.*.can_export' => 'boolean',
            'app_permissions.*.can_bulk' => 'boolean',
            'app_permissions.*.sort_order' => 'integer',
            'record_permissions' => 'array',
            'record_permissions.*.match_mode' => 'nullable|in:all,any',
            'record_permissions.*.conditions' => 'array',
            'record_permissions.*.grants' => 'array',
            'field_permissions' => 'array',
            'field_permissions.*.field_id' => 'required|integer',
            'field_permissions.*.subject_type' => 'required|string',
            'field_permissions.*.subject_id' => 'nullable|integer',
            'field_permissions.*.can_view' => 'boolean',
            'field_permissions.*.can_edit' => 'boolean',
            'views' => 'array',
            'views.*.id' => 'nullable|integer',
            'views.*.name' => 'required|string|max:255',
            'views.*.is_default' => 'boolean',
            'views.*.columns' => 'nullable|array',
            'views.*.filters' => 'nullable|array',
            'views.*.filter_logic' => 'nullable|in:and,or',
            'views.*.sort' => 'nullable|array',
            'tools' => 'array',
            'tools.*.id' => 'nullable|integer',
            'tools.*.tool_type' => 'required|string|max:50',
            'tools.*.name' => 'required|string|max:255',
            'tools.*.config' => 'nullable|array',
            'tools.*.is_active' => 'boolean',
        ]);

        // Field keys are identifiers (referenced by formulas, status field-rules, view columns),
        // so they must be unique within the app. Labels are display-only and not enforced here.
        $keys = array_map(fn ($f) => $f['key'], $data['fields'] ?? []);
        if (count($keys) !== count(array_unique($keys))) {
            $dup = collect($keys)->duplicates()->first();

            return response()->json(['message' => "フィールドキー「{$dup}」が重複しています。キーはアプリ内で一意にしてください。"], 422);
        }

        if (isset($data['id'])) {
            $existing = FlowDefinition::with('appPermissions')->findOrFail($data['id']);
            abort_unless($this->flowService->effectiveAppPermissions($user, $existing)['manage'], 403);
        }

        $definition = DB::transaction(function () use ($data, $user) {
            $isNew = ! isset($data['id']);
            $definition = $isNew
                ? new FlowDefinition(['created_by' => $user->id])
                : FlowDefinition::findOrFail($data['id']);

            // Snapshot before any sync* call mutates it — the audit diff below compares this against
            // the same snapshot shape taken again once everything's saved. Skipped on create: there's
            // nothing to diff a brand-new app against.
            $before = $isNew ? null : $this->snapshotDefinitionForAudit($definition);

            $definition->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'color_id' => $data['color_id'] ?? $definition->color_id,
                'icon_svg' => $this->sanitizeIconSvg($data['icon_svg'] ?? null),
                'icon_image' => $this->sanitizeIconImage($data['icon_image'] ?? null),
                'visibility' => $definition->visibility ?? 'limited',
                'is_active' => $data['is_active'] ?? true,
                'use_status_flow' => $data['use_status_flow'] ?? false,
                'project_record_id' => $data['project_record_id'] ?? $definition->project_record_id,
            ])->save();

            $keyToId = $this->syncFields($definition, $data['fields'] ?? []);
            $statusKeyToId = $this->syncStatuses($definition, $data['statuses'] ?? [], $keyToId);
            $this->syncStatusActions($definition, $data['status_actions'] ?? [], $statusKeyToId);

            if (array_key_exists('app_permissions', $data)) {
                $this->syncAppPermissions($definition, $data['app_permissions']);
            } elseif ($isNew) {
                $this->seedAppPermissions($definition, $user);
            }
            $this->ensureCreatorPermission($definition);

            if (array_key_exists('record_permissions', $data)) {
                $this->syncRecordPermissions($definition, $data['record_permissions']);
            }
            if (array_key_exists('field_permissions', $data)) {
                $this->syncFieldPermissions($definition, $data['field_permissions']);
            }
            if (array_key_exists('views', $data)) {
                $this->syncViews($definition, $data['views'], $user);
            }
            $this->ensureDefaultView($definition, $user);

            if (array_key_exists('tools', $data)) {
                $this->syncTools($definition, $data['tools']);
            }

            if (! $isNew) {
                $diff = $this->diffDefinitionSnapshots($before, $this->snapshotDefinitionForAudit($definition));
                if ($diff) {
                    $this->flowService->logAudit($definition, $user, 'settings_change', null, ['diff' => $diff]);
                }
            }

            return $definition;
        });

        return $this->getFlowDefinition($definition->id);
    }

    private function seedAppPermissions(FlowDefinition $definition, $user): void
    {
        // Creator row first (sort_order 0) so it always wins first-match.
        $definition->appPermissions()->create([
            'subject_type' => 'creator',
            'subject_id' => null,
            'can_view' => true, 'can_add' => true, 'can_edit' => true, 'can_delete' => true,
            'can_manage' => true, 'can_import' => true, 'can_export' => true,
            'can_bulk' => true,
            'sort_order' => 0,
        ]);
    }

    private function syncAppPermissions(FlowDefinition $definition, array $rows): void
    {
        $definition->appPermissions()->delete();
        foreach (array_values($rows) as $i => $r) {
            $definition->appPermissions()->create([
                'subject_type' => $r['subject_type'],
                'subject_id' => $r['subject_id'] ?? null,
                'can_view' => ! empty($r['can_view']),
                'can_add' => ! empty($r['can_add']),
                'can_edit' => ! empty($r['can_edit']),
                'can_delete' => ! empty($r['can_delete']),
                'can_manage' => ! empty($r['can_manage']),
                'can_import' => ! empty($r['can_import']),
                'can_export' => ! empty($r['can_export']),
                'can_bulk' => ! empty($r['can_bulk']),
                // the payload order IS the intent, so index wins. Honouring an incoming sort_order
                // meant a reordered row kept the number it was loaded with, and the relation
                // (ordered by sort_order) put it straight back where it was on the next load.
                'sort_order' => $i,
            ]);
        }
    }

    private function ensureCreatorPermission(FlowDefinition $definition): void
    {
        if ($definition->appPermissions()->where('subject_type', 'creator')->exists()) {
            return;
        }
        $definition->appPermissions()->create([
            'subject_type' => 'creator',
            'subject_id' => null,
            'can_view' => true, 'can_add' => true, 'can_edit' => true, 'can_delete' => true,
            'can_manage' => true, 'can_import' => true, 'can_export' => true,
            'can_bulk' => true,
            'sort_order' => 0,
        ]);
    }

    private function syncRecordPermissions(FlowDefinition $definition, array $sets): void
    {
        $existing = $definition->recordPermissionSets()->pluck('id');
        if ($existing->isNotEmpty()) {
            DB::table('flow_record_permission_conditions')->whereIn('set_id', $existing)->delete();
            DB::table('flow_record_permission_grants')->whereIn('set_id', $existing)->delete();
            $definition->recordPermissionSets()->delete();
        }

        foreach (array_values($sets) as $i => $s) {
            $set = $definition->recordPermissionSets()->create([
                'match_mode' => $s['match_mode'] ?? 'all',
                'sort_order' => $i,
            ]);
            foreach (array_values($s['conditions'] ?? []) as $ci => $c) {
                $set->conditions()->create([
                    'source' => $c['source'] ?? 'field',
                    'field_id' => $c['field_id'] ?? null,
                    'operator' => $c['operator'] ?? 'includes_any',
                    'values' => $c['values'] ?? [],
                    'sort_order' => $ci,
                ]);
            }
            foreach (array_values($s['grants'] ?? []) as $gi => $g) {
                $set->grants()->create([
                    'subject_type' => $g['subject_type'],
                    'subject_id' => $g['subject_id'] ?? null,
                    'can_view' => ! empty($g['can_view']),
                    'can_edit' => ! empty($g['can_edit']),
                    'can_delete' => ! empty($g['can_delete']),
                    'sort_order' => $gi,
                ]);
            }
        }
    }

    private function syncFieldPermissions(FlowDefinition $definition, array $rows): void
    {
        $definition->fieldPermissions()->delete();
        foreach (array_values($rows) as $i => $r) {
            $definition->fieldPermissions()->create([
                'field_id' => $r['field_id'],
                'subject_type' => $r['subject_type'],
                'subject_id' => $r['subject_id'] ?? null,
                'can_view' => ! empty($r['can_view']),
                'can_edit' => ! empty($r['can_edit']),
                'sort_order' => $i,
            ]);
        }
    }

    /** Upsert builder-defined views; delete removed; enforce a single default. */
    private function syncViews(FlowDefinition $definition, array $views, $user): void
    {
        $keptIds = [];
        $hasDefault = false;
        foreach (array_values($views) as $v) {
            $model = ! empty($v['id'])
                ? ($definition->views()->whereKey($v['id'])->first() ?? $definition->views()->make())
                : $definition->views()->make();

            $default = ! empty($v['is_default']) && ! $hasDefault;
            if ($default) {
                $hasDefault = true;
            }
            $model->fill([
                'name' => $v['name'] ?? 'ビュー',
                'view_mode' => 'table',
                'is_default' => $default,
                'columns' => $v['columns'] ?? null,
                'filters' => $v['filters'] ?? null,
                'filter_logic' => ($v['filter_logic'] ?? 'and') === 'or' ? 'or' : 'and',
                'sort' => $v['sort'] ?? null,
            ]);
            if (! $model->created_by) {
                $model->created_by = $user->id;
            }
            $model->flow_definition_id = $definition->id;
            $model->save();
            $keptIds[] = $model->id;
        }
        $definition->views()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    /** Upsert the app's tools (PDF etc.); config is a free-form JSON blob per tool type. */
    private function syncTools(FlowDefinition $definition, array $tools): void
    {
        $keptIds = [];
        foreach (array_values($tools) as $i => $t) {
            $config = $t['config'] ?? [];
            // カスタムボタンが呼べるのは許可リストのメソッドだけ。知らない名前は保存しない
            // （保存できてしまうと「設定に書いた宛先を呼ぶ」形に一歩近づく）。
            if (($t['tool_type'] ?? null) === 'action') {
                $config = $this->sanitizeActionConfig(is_array($config) ? $config : []);
            }
            $payload = [
                'tool_type' => $t['tool_type'] ?? 'pdf',
                'name' => $t['name'] ?? 'ツール',
                'config' => $config,
                'is_active' => $t['is_active'] ?? true,
                'sort_order' => $i,
            ];
            $model = ! empty($t['id']) ? $definition->tools()->whereKey($t['id'])->first() : null;
            if ($model) {
                $model->fill($payload)->save();
            } else {
                $model = $definition->tools()->create($payload);
            }
            $keptIds[] = $model->id;
        }
        $definition->tools()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    /**
     * カスタムボタンのconfigを、こちらが知っている3つだけに絞る。
     * handler は許可リストにあるメソッド名でなければ null にする（そのボタンは動かない）。
     */
    private function sanitizeActionConfig(array $config): array
    {
        $handler = $config['handler'] ?? null;
        $eligible = [];
        foreach ((array) ($config['eligible'] ?? []) as $subj) {
            if (! is_array($subj) || ! filled($subj['subject_type'] ?? null)) {
                continue;
            }
            $eligible[] = [
                'subject_type' => (string) $subj['subject_type'],
                'subject_id' => isset($subj['subject_id']) && $subj['subject_id'] !== null
                    ? (int) $subj['subject_id'] : null,
            ];
        }
        $color = is_string($config['color'] ?? null) && preg_match('/^#[0-9a-fA-F]{6}$/', $config['color'])
            ? $config['color'] : '';

        return [
            'handler' => FlowRecordActions::isCallable(is_string($handler) ? $handler : null) ? $handler : null,
            'color' => $color,
            'eligible' => $eligible,
        ];
    }

    /** An app is never view-less: seed 「すべて」 (all columns) if empty, and guarantee one default. */
    private function ensureDefaultView(FlowDefinition $definition, $user): void
    {
        if ($definition->views()->count() === 0) {
            $definition->views()->create([
                'name' => 'すべて',
                'view_mode' => 'table',
                'is_default' => true,
                'columns' => null,
                'filters' => null,
                'sort' => null,
                'created_by' => $user->id ?? null,
            ]);

            return;
        }
        if (! $definition->views()->where('is_default', true)->exists()) {
            $definition->views()->orderBy('id')->first()?->update(['is_default' => true]);
        }
    }

    /** Upsert fields by id; delete those removed. Returns field key => id map. */
    private function syncFields(FlowDefinition $definition, array $fields): array
    {
        $keptIds = [];
        $keyToId = [];

        foreach ($fields as $index => $field) {
            // ラベル項目だけHTMLを持てる。画面では v-html で出すので、保存する前にここで削る
            // （書けるのは管理者でも、実行されるのは閲覧者のブラウザなので信用の問題ではない）。
            $label = ($field['input_type'] ?? null) === 'label'
                ? FlowRichText::sanitize($field['label'] ?? '')
                : mb_substr((string) ($field['label'] ?? ''), 0, 255);

            $payload = [
                'key' => $field['key'],
                'label' => $label,
                'input_type' => $field['input_type'],
                'options' => $field['options'] ?? null,
                'is_required' => $field['is_required'] ?? false,
                'hidden' => $field['hidden'] ?? false,
                'order_number' => $field['order_number'] ?? $index,
                'layout_row' => $field['layout_row'] ?? 0,
                'width' => $field['width'] ?? 260,
                'depends_on' => $field['depends_on'] ?? null,
                'validation' => $field['validation'] ?? null,
                'formula' => $field['formula'] ?? null,
                'result_type' => $field['result_type'] ?? null,
            ];

            $model = ! empty($field['id'])
                ? tap($definition->fields()->whereKey($field['id'])->first())?->fill($payload)
                : $definition->fields()->make($payload);

            if (! $model) {
                $model = $definition->fields()->make($payload);
            }
            if (! $model->flow_definition_id) {
                $model->flow_definition_id = $definition->id;
            }
            $model->save();

            $keptIds[] = $model->id;
            $keyToId[$model->key] = $model->id;
        }

        $definition->fields()->whereNotIn('id', $keptIds ?: [0])->delete();

        return $keyToId;
    }

    /** Upsert statuses; returns builder status-key => id (so actions can link from/to). */
    private function syncStatuses(FlowDefinition $definition, array $statuses, array $keyToId): array
    {
        $keptIds = [];
        $statusKeyToId = [];
        $hasInitial = false;

        foreach (array_values($statuses) as $index => $status) {
            $initial = ! empty($status['is_initial']) && ! $hasInitial;
            if ($initial) {
                $hasInitial = true;
            }
            $payload = [
                'name' => $status['name'],
                'order_number' => $index,
                'is_initial' => $initial,
                'color' => $status['color'] ?? null,
                'ui_x' => $status['ui_x'] ?? null,
                'ui_y' => $status['ui_y'] ?? null,
            ];

            $model = ! empty($status['id'])
                ? $definition->statuses()->whereKey($status['id'])->first()
                : null;

            if ($model) {
                $model->fill($payload)->save();
            } else {
                $model = $definition->statuses()->create($payload);
            }

            $keptIds[] = $model->id;
            if (! empty($status['key'])) {
                $statusKeyToId[$status['key']] = $model->id;
            }

            $model->fieldRules()->delete();
            foreach ($status['field_rules'] ?? [] as $rule) {
                $fieldId = $keyToId[$rule['field_key']] ?? null;
                if ($fieldId) {
                    $model->fieldRules()->create(['flow_field_id' => $fieldId, 'rule' => $rule['rule']]);
                }
            }
        }

        // Guarantee exactly one initial status when any exist.
        if (! $hasInitial && ! empty($keptIds)) {
            $definition->statuses()->whereKey($keptIds[0])->update(['is_initial' => true]);
        }

        $removed = $definition->statuses()->whereNotIn('id', $keptIds ?: [0])->pluck('id');
        if ($removed->isNotEmpty()) {
            DB::table('flow_status_field_rules')->whereIn('flow_status_id', $removed)->delete();
            $definition->statuses()->whereIn('id', $removed)->delete();
        }

        return $statusKeyToId;
    }

    /** Upsert custom action buttons; resolves from/to statuses via the builder key map. */
    private function syncStatusActions(FlowDefinition $definition, array $actions, array $statusKeyToId): void
    {
        $keptIds = [];
        foreach (array_values($actions) as $i => $a) {
            $fromId = $statusKeyToId[$a['from_status_key']] ?? null;
            $toId = $statusKeyToId[$a['to_status_key']] ?? null;
            if (! $fromId || ! $toId) {
                continue; // orphaned reference (status removed) → skip
            }
            $payload = [
                'flow_definition_id' => $definition->id,
                'flow_status_id' => $fromId,
                'to_status_id' => $toId,
                'name' => $a['name'] ?? null,
                'label' => $a['label'],
                'color' => $a['color'] ?? null,
                'eligible' => $a['eligible'] ?? [],
                // absent in older payloads -> keep notifying, which is the existing behaviour
                'notify' => $a['notify'] ?? true,
                'sort_order' => $i,
            ];
            $model = ! empty($a['id']) ? $definition->statusActions()->whereKey($a['id'])->first() : null;
            if ($model) {
                $model->fill($payload)->save();
            } else {
                $model = $definition->statusActions()->create($payload);
            }
            $keptIds[] = $model->id;
        }
        $definition->statusActions()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    private function syncShares(FlowDefinition $definition, array $shares): void
    {
        $definition->shares()->delete();

        foreach ($shares as $share) {
            if (empty($share['user_id']) && empty($share['position_id'])) {
                continue;
            }
            $definition->shares()->create([
                'user_id' => $share['user_id'] ?? null,
                'position_id' => $share['position_id'] ?? null,
                'access_level' => $share['access_level'],
            ]);
        }
    }

    /**
     * Snapshot every builder-editable concern of an app, for diffing before vs. after a
     * saveFlowDefinition() save (audit log's "settings_change" event). Always queries fresh from the
     * DB rather than relying on possibly-stale relation loads, since it's called both before and
     * after the sync* calls mutate the same $definition instance within one transaction.
     */
    private function snapshotDefinitionForAudit(FlowDefinition $definition): array
    {
        return [
            'general' => $definition->only(['name', 'description', 'color_id', 'is_active', 'use_status_flow', 'project_record_id']) + [
                'has_icon_svg' => (bool) $definition->icon_svg,
                'has_icon_image' => (bool) $definition->icon_image,
            ],
            'fields' => $definition->fields()->get([
                'id', 'key', 'label', 'input_type', 'options', 'is_required', 'hidden',
                'order_number', 'layout_row', 'width', 'depends_on', 'validation', 'formula', 'result_type',
            ])->toArray(),
            'statuses' => $definition->statuses()->get(['id', 'name', 'order_number', 'is_initial', 'color'])->toArray(),
            'status_actions' => $definition->statusActions()->get(['id', 'flow_status_id', 'to_status_id', 'name', 'label', 'color', 'eligible', 'sort_order'])->toArray(),
            'app_permissions' => $definition->appPermissions()->get([
                'id', 'subject_type', 'subject_id', 'can_view', 'can_add', 'can_edit', 'can_delete',
                'can_manage', 'can_import', 'can_export', 'can_bulk', 'sort_order',
            ])->toArray(),
            'field_permissions' => $definition->fieldPermissions()->get(['id', 'field_id', 'subject_type', 'subject_id', 'can_view', 'can_edit', 'sort_order'])->toArray(),
            // Nested conditions/grants aren't worth a fine-grained diff (rare to change, hard to
            // summarize usefully) — a changed/unchanged flag on the whole structure is enough.
            'record_permissions' => $definition->recordPermissionSets()->with('conditions', 'grants')->get()->toArray(),
            'views' => $definition->views()->get(['id', 'name', 'is_default', 'columns', 'filters', 'filter_logic', 'sort'])->toArray(),
            'tools' => $definition->tools()->get(['id', 'tool_type', 'name', 'config', 'is_active', 'sort_order'])->toArray(),
        ];
    }

    /** Per-concern diff between two snapshotDefinitionForAudit() results; empty array = no changes. */
    private function diffDefinitionSnapshots(array $before, array $after): array
    {
        $diff = [];

        $generalDiff = [];
        foreach ($after['general'] as $k => $v) {
            $old = $before['general'][$k] ?? null;
            if ($old != $v) {
                $generalDiff[$k] = ['old' => $old, 'new' => $v];
            }
        }
        if ($generalDiff) {
            $diff['general'] = $generalDiff;
        }

        $byId = fn ($fields) => fn ($b, $a) => $this->diffRowsByKey($b, $a, fn ($r) => $r['id'], $fields);
        $byIdentity = fn ($idFields, $fields) => fn ($b, $a) => $this->diffRowsByKey(
            $b, $a, fn ($r) => implode('|', array_map(fn ($f) => (string) ($r[$f] ?? ''), $idFields)), $fields
        );

        $concerns = [
            'fields' => $byId(['key', 'label', 'input_type', 'options', 'is_required', 'hidden', 'order_number', 'layout_row', 'width', 'depends_on', 'validation', 'formula', 'result_type']),
            'statuses' => $byId(['name', 'order_number', 'is_initial', 'color']),
            'status_actions' => $byId(['flow_status_id', 'to_status_id', 'name', 'label', 'color', 'eligible', 'sort_order']),
            'app_permissions' => $byIdentity(['subject_type', 'subject_id'], ['can_view', 'can_add', 'can_edit', 'can_delete', 'can_manage', 'can_import', 'can_export', 'can_bulk', 'sort_order']),
            'field_permissions' => $byIdentity(['field_id', 'subject_type', 'subject_id'], ['can_view', 'can_edit', 'sort_order']),
            'views' => $byId(['name', 'is_default', 'columns', 'filters', 'filter_logic', 'sort']),
            'tools' => $byId(['tool_type', 'name', 'config', 'is_active']),
        ];
        foreach ($concerns as $key => $fn) {
            $d = $fn($before[$key], $after[$key]);
            if ($d) {
                $diff[$key] = $d;
            }
        }

        if (json_encode($before['record_permissions']) !== json_encode($after['record_permissions'])) {
            $diff['record_permissions'] = ['changed' => true];
        }

        return $diff;
    }

    /** Generic added/removed/changed diff over two row-array snapshots, matched by an arbitrary key. */
    private function diffRowsByKey(array $before, array $after, callable $keyFn, array $fields): array
    {
        $beforeByKey = collect($before)->keyBy($keyFn);
        $afterByKey = collect($after)->keyBy($keyFn);
        $addedKeys = $afterByKey->keys()->diff($beforeByKey->keys());
        $removedKeys = $beforeByKey->keys()->diff($afterByKey->keys());

        $changed = [];
        foreach ($afterByKey as $key => $row) {
            if (! $beforeByKey->has($key)) {
                continue;
            }
            $old = $beforeByKey[$key];
            $fieldDiff = [];
            foreach ($fields as $f) {
                $ov = $old[$f] ?? null;
                $nv = $row[$f] ?? null;
                if ($ov != $nv) {
                    $fieldDiff[$f] = ['old' => $ov, 'new' => $nv];
                }
            }
            if ($fieldDiff) {
                $changed[(string) $key] = $fieldDiff;
            }
        }

        $result = [];
        if ($addedKeys->isNotEmpty()) {
            $result['added'] = $afterByKey->only($addedKeys)->values()->all();
        }
        if ($removedKeys->isNotEmpty()) {
            $result['removed'] = $beforeByKey->only($removedKeys)->values()->all();
        }
        if ($changed) {
            $result['changed'] = $changed;
        }

        return $result;
    }

    public function deleteFlowDefinition(Request $request)
    {
        $user = $this->active_user();
        $id = $request->input('id');
        $definition = FlowDefinition::with('appPermissions')->findOrFail($id);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['manage'], 403);
        $definition->delete();

        return response()->json(['deleted' => true]);
    }

    public function getFlowOptions()
    {
        $user = $this->active_user();

        return response()->json([
            // User picker: confined to the active community (User is not community-
            // scoped by trait, so the picker would otherwise span all communities).
            'users' => User::query()
                ->inActiveCommunity()
                ->where('retire', 0)
                ->where('id', '>', 105)
                ->select('id', 'name', 'position_id', 'icon_path', 'icon_bg')
                ->get(),
            'positions' => positionRecord::query()
                ->where('deleted_flag', 0)
                ->select('id', 'name')
                ->orderBy('sort_flag')
                ->get(),
            // Project picker: ProjectRecord uses BelongsToCommunity, so this query
            // auto-scopes to the active community via the global scope.
            /*
             * The projects this person is on come first — with 122 of them, scrolling past everyone
             * else's to reach your own three is the common case.
             *
             * Participation is any project_members row, either authority. The app's own "participated"
             * queries use the members() relation, which is wherePivot('authority', 0) and so leaves out
             * the projects you manage — for an account that only ever manages, that returns nothing and
             * the ordering looks broken. Managing a project is participating in it.
             *
             * EXISTS rather than a join: a join over the pivot multiplies rows per membership and would
             * need a distinct.
             */
            'projects' => ProjectRecord::query()
                ->select('project_records.id', 'project_records.name')
                ->selectRaw(
                    'EXISTS (SELECT 1 FROM project_members pm'
                    .' WHERE pm.project_id = project_records.id AND pm.user_id = ? AND pm.deleted_at IS NULL) AS is_mine',
                    [$user->id]
                )
                ->orderByDesc('is_mine')
                ->orderByDesc('project_records.id')
                ->get(),
        ]);
    }

    /**
     * 「レコードから検索」— keyword search over stored values in every app the user may view.
     * Permission-scoped in the service (app view, record-level sets, per-field view; secrets and
     * formulas excluded). `truncated` tells the UI the candidate cap was hit so it can say so
     * rather than presenting a partial result as complete.
     */
    public function searchFlowRecords(Request $request)
    {
        $user = $this->active_user();
        $kw = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));

        return response()->json($this->flowService->searchRecordsAcrossApps($user, $kw, $page, $perPage));
    }

    /** 自分待ち: records at a status where the current user can press a button. */
    public function getFlowDashboard()
    {
        $user = $this->active_user();
        $defs = FlowDefinition::where('use_status_flow', true)->where('is_active', true)
            ->with(['statuses', 'statusActions', 'appPermissions', 'recordPermissionSets'])
            ->get()
            ->filter(fn ($d) => $this->flowService->effectiveAppPermissions($user, $d)['view']);

        $items = [];
        foreach ($defs as $def) {
            $statusIds = $def->statusActions->pluck('flow_status_id')->unique()->values();
            if ($statusIds->isEmpty()) {
                continue;
            }
            $records = FlowRecord::where('flow_definition_id', $def->id)
                ->whereIn('current_status_id', $statusIds)
                ->with('currentStatus:id,name')
                ->orderByDesc('updated_at')
                ->get();
            foreach ($records as $rec) {
                $rec->setRelation('definition', $def);
                if (! $this->flowService->recordPermissions($user, $rec, $def)['view']) {
                    continue;
                }
                if ($this->flowService->hasPendingAction($user, $rec)) {
                    $items[] = [
                        'app_id' => $def->id,
                        'app_name' => $def->name,
                        'record_id' => $rec->id,
                        'record_number' => $rec->record_number,
                        'status' => $rec->currentStatus?->name,
                        'updated_at' => $rec->updated_at,
                    ];
                }
            }
        }

        return response()->json(['items' => $items]);
    }

    /* ================================================================
     | App runtime — records / views / actions / formula
     | NOTE: permission gating (app/record/field) is wired in Phase B2.
     |================================================================ */

    public function getAppRecords(Request $request, $definitionId)
    {
        $user = $this->active_user();
        // fieldPermissions + statuses.fieldRules feed editable_field_ids per row (see serializeRecord)
        $definition = FlowDefinition::with(['fields', 'statuses.fieldRules', 'statusActions', 'appPermissions', 'recordPermissionSets', 'fieldPermissions', 'tools' => fn ($q) => $q->where('is_active', true)])->findOrFail($definitionId);
        $app = $this->flowService->effectiveAppPermissions($user, $definition);
        abort_unless($app['view'], 403);

        $fields = $definition->fields;
        $views = $definition->views()->with('creator')->get();
        // currentStatus.fieldRules: a record's status is a different instance from definition.statuses,
        // so without this editable_field_ids would re-query the rules for every row
        $with = ['values', 'currentStatus:id,name', 'currentStatus.fieldRules', 'createdByUser'];
        $base = [
            'definition' => $definition->makeHidden('appPermissions'),
            'permissions' => $app,
            'views' => $views,
            // The 新規作成 form loads from this endpoint and has no record to carry per-field answers,
            // so it would otherwise know nothing about field permissions. It needs these to tell an
            // auto-fill destination it may not read ("自動入力されます") apart from an ordinary empty
            // field. Resolved with $record = null: the same basis storeAppRecord() writes on.
            'new_record_unviewable_field_ids' => $this->flowService->unviewableFieldIds($user, $definition, null),
        ];

        // 集計スロット configs live on the app (tool_type=slot), so every view shows them
        $slotTools = $definition->tools->where('tool_type', 'slot')->values()->all();
        $deferredSlots = fn () => $this->flowService->computeSlotAggregates($definition, [], $user, $slotTools, false);

        // seeing the record list clears grouped CSV-import events (they have no single record to open)
        $this->flowNotifications->markImportSeen($user, $definition);

        // Record-level permissions must be evaluated per record in PHP — can't be SQL-paginated
        // cleanly, so those apps return the full visible set and the front-end paginates.
        if ($definition->recordPermissionSets->isNotEmpty()) {
            $records = FlowRecord::where('flow_definition_id', $definition->id)
                ->with($with)->orderByDesc('created_at')->get()
                ->map(fn ($r) => ['rec' => tap($r)->setRelation('definition', $definition), 'rp' => $this->flowService->recordPermissions($user, $r, $definition)])
                ->filter(fn ($x) => $x['rp']['view'])
                ->values();

            return response()->json($base + [
                'mode' => 'client',
                'records' => $records->map(fn ($x) => $this->serializeRecord($x['rec'], $fields, $x['rp'], $user, $definition))->values(),
                'total' => $records->count(),
                'slots' => $deferredSlots(),
            ]);
        }

        // Server mode: filter + sort + search + offset pagination, all in SQL.
        $perPage = min(200, max(1, (int) $request->input('per_page', 50)));
        $page = max(1, (int) $request->input('page', 1));
        $view = $views->firstWhere('id', (int) $request->input('view_id'))
            ?? $views->firstWhere('is_default', true) ?? $views->first();
        $filters = is_array($view?->filters) ? $view->filters : [];
        $filterLogic = $view?->filter_logic === 'or' ? 'or' : 'and';
        $sort = $request->filled('sort_field')
            ? [['field' => $request->input('sort_field'), 'direction' => $request->input('sort_dir') === 'desc' ? 'desc' : 'asc']]
            : (is_array($view?->sort) ? $view->sort : []);
        // ad-hoc filter from the search bar's quick-filter icon (session-only, not saved to the view)
        $adhocFilter = $this->decodeAdhocFilter($request);

        // Formula fields hold no stored value, so SQL can't filter/sort by them. When a view's
        // filters or sort (or the ad-hoc filter) reference one, fall back to the client-compute path:
        // return every visible record with its computed values and let the front-end filter/sort/
        // paginate (mirrors the record-permission branch above). Keeps SQL pagination for the common
        // no-formula case.
        $isFormulaRef = function ($ref) use ($fields) {
            return is_numeric($ref)
                && optional($fields->firstWhere('id', (int) $ref))->input_type === 'formula';
        };
        $needsCompute = collect($filters)->contains(fn ($f) => $isFormulaRef($f['field'] ?? null))
            || collect($sort)->contains(fn ($s) => $isFormulaRef($s['field'] ?? null))
            || collect($adhocFilter['conditions'] ?? [])->contains(fn ($f) => $isFormulaRef($f['field'] ?? null));
        if ($needsCompute) {
            $records = FlowRecord::where('flow_records.flow_definition_id', $definition->id)
                ->with($with)->orderByDesc('created_at')->get()
                ->each(fn ($r) => $r->setRelation('definition', $definition));
            $can = ['edit' => $app['edit'], 'delete' => $app['delete']];

            return response()->json($base + [
                'mode' => 'client',
                'records' => $records->map(fn ($r) => $this->serializeRecord($r, $fields, $can, $user, $definition))->values(),
                'total' => $records->count(),
                'slots' => $deferredSlots(),
            ]);
        }

        $query = $this->flowService->recordListQuery($definition, $filters, (string) $request->input('search', ''), $adhocFilter, $filterLogic);
        $total = (clone $query)->count();
        $this->flowService->applyRecordSort($query, $sort, $definition);
        $records = $query->with($with)->forPage($page, $perPage)->get()
            ->each(fn ($r) => $r->setRelation('definition', $definition));

        $can = ['edit' => $app['edit'], 'delete' => $app['delete']];

        // Aggregates cover the whole filtered set, not the page on screen — so this re-runs the
        // query without pagination. Only when the app actually has a slot: otherwise every list
        // load would pay for a full fetch it never uses.
        $slots = [];
        if ($slotTools) {
            $allForSlots = $this->flowService
                ->recordListQuery($definition, $filters, (string) $request->input('search', ''), $adhocFilter, $filterLogic)
                ->with('values')->get()
                ->each(fn ($r) => $r->setRelation('definition', $definition));
            $slots = $this->flowService->computeSlotAggregates($definition, $allForSlots, $user, $slotTools);
        }

        return response()->json($base + [
            'mode' => 'server',
            'records' => $records->map(fn ($r) => $this->serializeRecord($r, $fields, $can, $user, $definition))->values(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'slots' => $slots,
        ]);
    }

    /**
     * Decode + lightly sanitize the `filters` query param (the search bar's ad-hoc quick filter):
     * {"logic": "and"|"or", "conditions": [{"field", "operator", "values"}, ...]}. Malformed input
     * (or no conditions) yields null — the caller then just skips ad-hoc filtering. Field/operator
     * values aren't otherwise validated here since applyFilterToQuery()/applyScalarOp() already treat
     * unknown fields/operators as no-ops or safe defaults.
     */
    private function decodeAdhocFilter(Request $request): ?array
    {
        $raw = $request->input('filters');
        if (! $raw) {
            return null;
        }
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded) || ! is_array($decoded['conditions'] ?? null)) {
            return null;
        }
        $conditions = [];
        foreach ($decoded['conditions'] as $c) {
            if (! is_array($c) || ! array_key_exists('field', $c) || ! isset($c['operator'])) {
                continue;
            }
            $conditions[] = [
                'field' => $c['field'],
                'operator' => (string) $c['operator'],
                'values' => is_array($c['values'] ?? null) ? array_values($c['values']) : [],
            ];
        }
        if (! $conditions) {
            return null;
        }

        return ['logic' => ($decoded['logic'] ?? 'and') === 'or' ? 'or' : 'and', 'conditions' => $conditions];
    }

    /**
     * App-level audit log (「監査ログ」builder tab) — manage-only. Distinct from a record's own
     * 変更履歴 (UpdateLog): this tracks who opened/exported/downloaded what and when, plus settings
     * changes, never individual field edits.
     */
    public function getFlowAuditLogs(Request $request, $definitionId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['manage'], 403);

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(10, (int) $request->input('per_page', 30)));

        $query = FlowAuditLog::where('flow_definition_id', $definition->id)
            ->with(['user', 'record'])
            ->orderByDesc('id');

        $action = (string) $request->input('action', '');
        if ($action !== '') {
            $query->where('action', $action);
        }

        $total = (clone $query)->count();
        $logs = $query->forPage($page, $perPage)->get();

        return response()->json([
            'logs' => $logs->map(fn ($l) => [
                'id' => $l->id,
                'user' => $l->user,
                'action' => $l->action,
                'record' => $l->record ? ['id' => $l->record->id, 'record_number' => $l->record->record_number] : null,
                'meta' => $l->meta,
                'created_at' => $l->created_at,
            ]),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /** Re-download the archived copy of a past CSV export (manage-only), from a 'csv_export' log row. */
    public function downloadAuditExport($logId)
    {
        $user = $this->active_user();
        $log = FlowAuditLog::with('definition')->findOrFail($logId);
        abort_unless($log->action === 'csv_export', 404);
        abort_unless($this->flowService->effectiveAppPermissions($user, $log->definition)['manage'], 403);

        $path = $log->meta['stored_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->download(storage_path('app/'.$path), $log->meta['filename'] ?? 'export.csv');
    }

    /**
     * ファイル項目のアップロード先。
     *
     * 共通の /attach_upload_api は使わない——あれはIDを取るために message_files（チャット用の
     * テーブル）に行を作るので、カスタムアプリのファイルがチャット側に溜まっていた。
     * レコードはまだ無いので pending として置き、保存時に結び付ける。
     */
    public function uploadRecordFile(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'field_id' => 'required|integer|exists:flow_fields,id',
            'column_key' => 'nullable|string|max:255',
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:51200',
        ]);

        $field = FlowField::findOrFail($data['field_id']);
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets', 'fieldPermissions'])
            ->findOrFail($field->flow_definition_id);

        // アップロードできるのは、そのアプリにレコードを作れる／編集できる人だけ。
        $perms = $this->flowService->effectiveAppPermissions($user, $definition);
        abort_unless($perms['add'] || $perms['edit'], 403);
        // 項目自体の編集権限も見る（閲覧しかできない項目に添付させない）
        abort_unless($this->flowService->fieldPermissions($user, $definition)[$field->id]['edit'] ?? true, 403);

        // 送られた field/column が本当にファイルを置ける場所かを確かめる
        $columnKey = $data['column_key'] ?? null;
        if ($columnKey !== null) {
            abort_unless($field->input_type === 'table', 422, 'この項目にファイル列はありません。');
            abort_unless(
                in_array($columnKey, app(FlowFileService::class)->fileColumnKeys($field), true),
                422, 'この列はファイル列ではありません。'
            );
        } else {
            abort_unless($field->input_type === 'file', 422, 'この項目はファイル項目ではありません。');
        }

        $stored = [];
        foreach ($request->file('files') as $upload) {
            $stored[] = app(FlowFileService::class)
                ->storePending($upload, $definition, $field, $columnKey, $user->id)
                ->apiPayload();
        }

        return response()->json(['files' => $stored]);
    }

    /**
     * ファイルの配信。**権限を見てから流す唯一の経路**。
     *
     * これまでファイル項目は共通の /cdn/{path} で配られていて、あのルートは
     * `response()->file(storage_path('app/'.$path))` を無条件に返すだけだった。つまりログインさえ
     * していれば、アプリ権限・レコード権限・項目の閲覧権限のすべてを飛び越えて読めた（IDも連番で
     * 推測できた）。ここでアプリ→レコード→項目の3段を確かめる。
     */
    public function serveRecordFile(Request $request, $fileId)
    {
        $user = $this->active_user();
        $file = FlowRecordFile::findOrFail($fileId);
        $record = $this->authorizeRecordFile($file, $user);

        // 監査はここで書く。URLからレコードIDを正規表現で拾って画面に自己申告させていた頃と違い、
        // バイトを返すのと同じリクエストで記録するので取りこぼしも偽装もできない。
        if ($record && $request->boolean('dl')) {
            $this->flowService->logAudit($record->definition, $user, 'file_download', $record, [
                'file_name' => $file->name,
                'flow_record_file_id' => $file->id,
            ]);
        }

        return $this->streamRecordFile($file, $request->boolean('dl'));
    }

    /**
     * ファイル1件の閲覧可否。アプリ→レコード→項目の3段。
     * 戻り値は持ち主のレコード（保存前の pending は null）。
     */
    private function authorizeRecordFile(FlowRecordFile $file, $user): ?FlowRecord
    {
        // pending（保存前）は本人だけ。保存済みはレコードの権限で判断する。
        if ($file->status === FlowRecordFile::STATUS_PENDING) {
            abort_unless((int) $file->uploaded_by === (int) $user->id, 403);

            return null;
        }

        abort_unless($file->flow_record_id, 404);
        $record = FlowRecord::with(self::RECORD_DETAIL_WITH)->findOrFail($file->flow_record_id);
        $definition = $record->definition;

        abort_unless($this->flowService->recordPermissions($user, $record, $definition)['view'], 403);
        if ($file->flow_field_id) {
            $fieldPerms = $this->flowService->fieldPermissions($user, $definition, $record);
            abort_unless($fieldPerms[$file->flow_field_id]['view'] ?? true, 403);
        }

        return $record;
    }

    /**
     * Office形式（xlsx/docx/pptx…）を表示するための、署名付きの一時URLを返す。
     *
     * これらは Microsoft の Office Online（view.officeapps.live.com）が描画するため、**向こうの
     * サーバーがログインなしで取得できるURL**が必要になる。既存の仕組み
     * `/cdn_external/{user_id}/{file_key}/{パス}` は使わない——あれはユーザーIDと8文字の鍵さえあれば
     * storage 配下の任意のパスを配ってしまい、ファイル項目に権限を付けた意味が無くなる。
     *
     * 代わりに、ここで閲覧権限を確かめてから、そのファイル1件だけを指す期限付きの署名URLを出す。
     * 署名はURL全体（ファイルIDと期限を含む）に掛かるので、別のファイルに向け直せない。
     */
    public function recordFileViewerUrl(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate(['id' => 'required|integer']);

        $file = FlowRecordFile::findOrFail($data['id']);
        $this->authorizeRecordFile($file, $user);
        abort_if($file->status === FlowRecordFile::STATUS_MISSING, 404, 'ファイルの実体が見つかりません。');

        return response()->json([
            // Office Online は即座に取得するので、短く切って構わない
            'url' => URL::temporarySignedRoute('flow.file.external', now()->addMinutes(10), ['fileId' => $file->id]),
        ]);
    }

    /**
     * 署名付きURLでの配信。セッションが無い相手（Office Online）向けの唯一の出口。
     *
     * 権限は署名を発行した時点で確認済みで、この署名は1ファイル・10分に限定されている。
     * `signed` ミドルウェアが改ざんと期限切れを弾く。
     */
    public function serveRecordFileExternal($fileId)
    {
        $file = FlowRecordFile::findOrFail($fileId);

        return $this->streamRecordFile($file, false);
    }

    private function streamRecordFile(FlowRecordFile $file, bool $download)
    {
        abort_if($file->status === FlowRecordFile::STATUS_MISSING, 404, 'ファイルの実体が見つかりません。');
        abort_unless($file->disk_path && Storage::disk('local')->exists($file->disk_path), 404);

        $absolute = storage_path('app/'.$file->disk_path);

        return $download
            ? response()->download($absolute, $file->name)
            : response()->file($absolute);
    }

    /** 保存前に取り消されたファイルを捨てる（画面で×を押した分）。 */
    public function discardRecordFile(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate(['id' => 'required|integer']);

        $file = FlowRecordFile::find($data['id']);
        // 自分がアップロードした pending だけ。保存済みのファイルはレコードの保存で外す。
        if ($file && $file->status === FlowRecordFile::STATUS_PENDING && (int) $file->uploaded_by === (int) $user->id) {
            app(FlowFileService::class)->discardPending($file);
        }

        return response()->noContent();
    }

    /**
     * Lightweight record search for a reference field's picker: returns {id, number, label}
     * candidates from the target app, filtered by keyword. Respects the target app's view permission.
     */
    public function referenceSearch(Request $request, $definitionId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets', 'fieldPermissions'])->findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        $fields = $definition->fields;
        $labelField = $fields->firstWhere('key', (string) $request->input('label_field', ''));
        // Honor field-level permission on the label field (kintone won't surface a field the user can't view).
        if ($labelField) {
            $fp = $this->flowService->fieldPermissions($user, $definition);
            if (! ($fp[$labelField->id]['view'] ?? true)) {
                $labelField = null;
            }
        }

        $query = $this->flowService->recordListQuery($definition, [], (string) $request->input('q', ''));
        $records = $query->with(['values', 'currentStatus:id,name'])->orderByDesc('created_at')->limit(30)->get();

        if ($definition->recordPermissionSets->isNotEmpty()) {
            $records = $records->filter(fn ($r) => $this->flowService->recordPermissions($user, $r, $definition)['view'])->values();
        }

        $records = $records->take(20);
        $out = $records->map(function (FlowRecord $r) use ($fields, $labelField) {
            $label = '';
            if ($labelField) {
                $vals = $this->flowService->recordValues($r, $fields);
                $raw = $vals[(string) $labelField->id] ?? null;
                $label = is_array($raw)
                    ? implode(' / ', array_map(fn ($x) => is_scalar($x) ? (string) $x : '', $raw))
                    : (is_scalar($raw) ? (string) $raw : '');
            }

            return [
                'id' => $r->id,
                'number' => $r->record_number,
                'label' => $label !== '' ? $label : ('#'.$r->record_number),
            ];
        });

        return response()->json(['records' => $out->values()]);
    }

    /**
     * A picked lookup record's field values, keyed by field key — used by the lookup field's
     * kintone-style field copy when a record is picked. `fields` is a comma-separated list of the
     * source field keys the mappings need. Respects the source app's view permission, per-record
     * permissions, and field-level view permissions (a hidden field yields no value).
     */
    public function lookupRecord(Request $request, $definitionId, $recordId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets', 'fieldPermissions'])->findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        $record = FlowRecord::where('flow_definition_id', $definition->id)->where('id', $recordId)->firstOrFail();
        if ($definition->recordPermissionSets->isNotEmpty()) {
            abort_unless($this->flowService->recordPermissions($user, $record, $definition)['view'], 403);
        }

        // 参照の「表示」に要るもの。IDだけ渡された画面は、番号も名前も知らないまま
        // 「#undefined」を出すことになる（関連レコードの＋追加から来た場合がこれ）。
        $refInfo = ['id' => $record->id, 'number' => (int) $record->record_number, 'label' => null];
        if ($refFieldId = $request->input('ref_field')) {
            $refField = FlowField::find($refFieldId);
            if ($refField
                && $refField->input_type === 'reference'
                && (int) ($refField->validation['target_definition_id'] ?? 0) === (int) $definition->id) {
                $refInfo['label'] = $this->flowService->referenceLabel(
                    $record, $definition, $refField->validation['label_field'] ?? null
                );
            }
        }

        $wantKeys = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('fields', '')))));
        if (! $wantKeys) {
            return response()->json(['values' => (object) [], 'reference' => $refInfo]);
        }

        $fields = $definition->fields;
        $vals = $this->flowService->recordValues($record, $fields); // keyed by field id
        $fp = $this->flowService->fieldPermissions($user, $definition);
        $byKey = $fields->keyBy('key');

        $out = [];
        foreach ($wantKeys as $key) {
            $f = $byKey->get($key);
            // secrets can never be copied out (defence in depth — the inspector already hides them)
            if (! $f || FlowService::isSecret($f->input_type) || ! ($fp[$f->id]['view'] ?? true)) {
                continue;
            }
            $out[$key] = $vals[(string) $f->id] ?? null;
        }

        return response()->json(['values' => $out, 'reference' => $refInfo]);
    }

    /* ---- system reference sources (built-in masters, e.g. offices) ---------------------------------
     * A reference field can point at a real master table instead of another Flow app. These mirror
     * the app-reference endpoints above (list picker / label / field-copy) against the source's real
     * model, so the master stays the single source of truth. See App\Support\FlowSystemSources. */

    /** The available system sources ({key,label}) for the field inspector's source picker. */
    public function systemReferenceSources()
    {
        return response()->json(['sources' => FlowSystemSources::options()]);
    }

    /** A system source's columns as text pseudo-fields — same shape as getDefinitionFields. */
    public function systemReferenceFields($source)
    {
        $s = FlowSystemSources::get($source);
        abort_unless($s !== null, 404);

        return response()->json([
            'id' => $source,
            'name' => $s['label'],
            // Columns are text pseudo-fields unless one declares otherwise. A column CAN declare
            // 'password': the inspector's copy rule is strict same-type for non-scalars, so such a
            // column will only offer encrypted destinations — that is how 口座番号 is prevented from
            // being mapped into an ordinary text field and landing in flow_record_values in clear.
            'fields' => collect($s['columns'])
                ->map(fn ($c) => [
                    'key' => $c['key'],
                    'label' => $c['label'],
                    'input_type' => $c['input_type'] ?? 'short',
                    'result_type' => null,
                ])
                ->values(),
        ]);
    }

    /** Search a system source (mirrors referenceSearch): returns {id, number, label} rows. */
    public function systemReferenceSearch(Request $request, $source)
    {
        $s = FlowSystemSources::get($source);
        abort_unless($s !== null, 404);

        $q = trim((string) $request->input('q', ''));
        $labelKey = (string) $request->input('label_field', '') ?: $s['label_column'];
        $resolve = $s['value'] ?? fn ($m, $k) => $m->{$k} ?? null;

        /** @var Builder $query */
        $query = $s['model']::query();
        if (isset($s['filter'])) {
            ($s['filter'])($query);
        }
        if (isset($s['with'])) {
            ($s['with'])($query);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($s, $q) {
                foreach ($s['search'] as $col) {
                    $w->orWhere($col, 'like', '%'.$q.'%');
                }
            });
        }
        $rows = $query->orderBy($s['label_column'])->limit(20)->get();

        $out = $rows->map(function ($m) use ($resolve, $labelKey) {
            $label = $resolve($m, $labelKey);
            $label = is_scalar($label) ? (string) $label : '';

            return ['id' => $m->id, 'number' => $m->id, 'label' => $label !== '' ? $label : ('#'.$m->id)];
        });

        return response()->json(['records' => $out->values()]);
    }

    /** A picked system-source row's column values (mirrors lookupRecord) for field-copy. */
    public function systemReferenceRecord(Request $request, $source, $id)
    {
        $s = FlowSystemSources::get($source);
        abort_unless($s !== null, 404);

        $wantKeys = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('fields', '')))));
        if (! $wantKeys) {
            return response()->json(['values' => (object) []]);
        }

        /** @var Builder $query */
        $query = $s['model']::query();
        if (isset($s['filter'])) {
            ($s['filter'])($query);
        }
        if (isset($s['with'])) {
            ($s['with'])($query);
        }
        $row = $query->whereKey($id)->first();
        if (! $row) {
            return response()->json(['values' => (object) []]);
        }

        /*
         * Secret-typed columns are NEVER served here.
         *
         * This endpoint is generic and deliberately cheap: it takes a source key and a row id and has
         * no app/record/field permission context at all. That is fine for 営業所名 or 役職, and became a
         * hole the moment a column resolved to a decrypted 口座番号 — any authenticated client could
         * have read anyone's account number with one GET.
         *
         * The client never needs it: for a destination the user may write, the value must be visible to
         * them anyway (so it is not a secret), and for one they may not, applyMasterAutoFill resolves it
         * server-side during the save. Same rule readFieldValue already enforces for stored secrets.
         */
        $allowed = collect($s['columns'])
            ->reject(fn ($c) => FlowService::isSecret($c['input_type'] ?? 'short'))
            ->pluck('key')
            ->flip();
        $resolve = $s['value'] ?? fn ($m, $k) => $m->{$k} ?? null;

        $out = [];
        foreach ($wantKeys as $key) {
            if (! $allowed->has($key)) {
                continue;
            }
            $out[$key] = $resolve($row, $key);
        }

        return response()->json(['values' => $out]);
    }

    /**
     * Notification events are best-effort side writes that run AFTER the main save committed —
     * a failure here must never turn an already-successful save into a 500 for the user.
     */
    private function notifySafely(\Closure $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** 対応待ち popup: records in this app currently awaiting the user's own action (live). */
    public function getFlowPendingActions($definitionId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with('appPermissions')->findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        $items = $this->flowService->pendingActionRecords($user, $definition)
            ->map(fn ($r) => [
                'record_id' => $r->id,
                'record_number' => $r->record_number,
                'status' => $r->currentStatus?->name,
                'updated_at' => $r->updated_at,
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    /**
     * Reveal a stored secret's plaintext. Gated by app view ∩ record view ∩ field view (fail-closed
     * to 管理 when the field has no permission rows) and ALWAYS audit-logged — for a credential
     * store, "who took what, when" is the control that matters most.
     */
    public function revealFlowSecret(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'record_id' => 'required|integer|exists:flow_records,id',
            'field_id' => 'required|integer|exists:flow_fields,id',
        ]);

        $record = FlowRecord::with(['values', 'definition.fields', 'definition.appPermissions', 'definition.recordPermissionSets', 'definition.fieldPermissions'])
            ->findOrFail($data['record_id']);
        $def = $record->definition;
        $field = $def->fields->firstWhere('id', (int) $data['field_id']);

        abort_unless($field && $this->flowService->canRevealSecret($user, $record, $field, $def), 403);

        try {
            $plain = $this->flowService->revealSecret($record, $field);
        } catch (DecryptException $e) {
            // surfaced, not swallowed: a key mismatch must not masquerade as "no password set"
            report($e);

            return response()->json(['message' => '保管された値を復号できませんでした。管理者にお問い合わせください。'], 500);
        }

        $this->flowService->logAudit($def, $user, 'secret_reveal', $record, [
            'record_number' => $record->record_number,
            'field_key' => $field->key,
            'field_label' => $field->label,
        ]);

        return response()->json(['value' => $plain]);
    }

    /* ---- flow notifications (per-app bell badge + popup) --------------------------------------- */

    /** The bell popup: latest events for this user on this app + their notification prefs. */
    /** One page of the bell menu. */
    private const NOTIFICATIONS_PER_PAGE = 15;

    /**
     * The bell popup's events, newest first, one page at a time.
     *
     * Paged on the id rather than an offset: notifications arrive while the menu is sitting open, and
     * every new row would shift an offset window down by one, so もっと見る would re-show rows the user
     * had already scrolled past. An id cursor is anchored to what they have actually seen.
     *
     * This replaced a flat limit(30) with no way to ask for more — older notifications existed but
     * were simply unreachable, and nothing on screen said the list had been cut off.
     */
    public function getFlowNotifications(Request $request, $definitionId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with('appPermissions')->findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        $before = $request->integer('before') ?: null;

        $rows = FlowNotification::where('user_id', $user->id)
            ->where('flow_definition_id', $definition->id)
            ->when($before, fn ($q) => $q->where('id', '<', $before))
            ->with(['actor', 'record:id,record_number'])
            ->orderByDesc('id')
            // one past the page: its presence is the has_more answer, without a second COUNT query
            ->limit(self::NOTIFICATIONS_PER_PAGE + 1)
            ->get();

        $hasMore = $rows->count() > self::NOTIFICATIONS_PER_PAGE;

        $events = $rows->take(self::NOTIFICATIONS_PER_PAGE)
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'actor' => $n->actor,
                'record_number' => $n->record?->record_number,
                'meta' => $n->meta,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at,
            ])
            ->values();

        return response()->json([
            'events' => $events,
            'has_more' => $hasMore,
            'prefs' => $this->flowNotifications->prefsFor($user, (int) $definition->id),
        ]);
    }

    /** すべて既読 button in the bell popup → clear every unread event for this user on this app. */
    public function markAllFlowNotificationsRead(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'flow_definition_id' => 'required|integer|exists:flow_definitions,id',
        ]);
        $definition = FlowDefinition::with('appPermissions')->findOrFail($data['flow_definition_id']);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        return response()->json(['ok' => true, 'read' => $this->flowNotifications->markAllRead($user, $definition)]);
    }

    /** Per-user per-app notification opt-in/out (the toggles inside the bell popup). */
    public function saveFlowNotificationPref(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'flow_definition_id' => 'required|integer|exists:flow_definitions,id',
            'pref' => 'required|string',
            'enabled' => 'required|boolean',
        ]);
        $definition = FlowDefinition::with('appPermissions')->findOrFail($data['flow_definition_id']);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['view'], 403);

        $this->flowNotifications->savePref($user, (int) $definition->id, $data['pref'], (bool) $data['enabled']);

        return response()->json(['ok' => true]);
    }

    /** The comment tab has actually been viewed (FE fires after ~5s visible) → clear comment badges. */
    public function markFlowCommentsRead(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate(['record_id' => 'required|integer|exists:flow_records,id']);
        $record = FlowRecord::findOrFail($data['record_id']);

        $this->flowNotifications->markCommentsRead($user, $record);

        return response()->json(['ok' => true]);
    }

    /**
     * $def turns on 'editable_field_ids' — the per-record intersection of record-edit, field
     * permissions and the current status's field rules. Without it the front-end can only guess at
     * editability from the field definition, so it renders an input for a field the server will
     * refuse to write and the value is silently discarded on save.
     */
    private function serializeRecord(FlowRecord $record, $fields, ?array $can = null, ?User $forUser = null, ?FlowDefinition $def = null): array
    {
        $values = $this->flowService->recordValues($record, $fields);

        // Field-level 閲覧 is applied HERE, after the formulas have been computed from the full set —
        // stripping first would change what a formula says instead of who may read it. Until this
        // existed the payload carried every value regardless of the field's 閲覧 rows, so a field
        // restricted to one person was delivered to (and displayed for) every record viewer.
        $unviewable = $def !== null && $forUser !== null
            ? $this->flowService->unviewableFieldIds($forUser, $def, $record)
            : [];
        foreach ($unviewable as $fid) {
            unset($values[(string) $fid]);
        }

        return [
            'id' => $record->id,
            'record_number' => $record->record_number,
            'values' => $values,
            // the front-end renders 閲覧権限がありません for these rather than an empty field, so "hidden"
            // never reads as "nobody filled it in"
            'unviewable_field_ids' => $def !== null && $forUser !== null ? $unviewable : null,
            'created_by' => $record->created_by,
            'creator' => $record->createdByUser,
            'current_status_id' => $record->current_status_id,
            'current_status' => $record->currentStatus?->name,
            'source' => $record->source,
            'source_id' => $record->source_id,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
            // per-record eligibility for the row shortcut buttons
            'can_edit' => (bool) ($can['edit'] ?? false),
            'can_delete' => (bool) ($can['delete'] ?? false),
            // 要対応 marker (red dot on the list's status pill): an action on the record's
            // current status explicitly names this user — same strict rule as the portal counter
            'pending_action' => $forUser !== null && $this->flowService->hasExplicitPendingAction($forUser, $record),
            // which of this record's fields THIS user may actually write (null = not resolved here)
            'editable_field_ids' => $def !== null && $forUser !== null
                ? $this->flowService->editableFieldIdsForRecord($forUser, $record, $def, $can)
                : null,
        ];
    }

    private const RECORD_DETAIL_WITH = [
        'definition.fields', 'definition.statuses.fieldRules', 'definition.appPermissions', 'definition.recordPermissionSets',
        'definition.statusActions', 'definition.tools', 'definition.fieldPermissions',
        'currentStatus', 'values', 'createdByUser', 'logs.user',
    ];

    public function getAppRecord($id)
    {
        $record = FlowRecord::with(self::RECORD_DETAIL_WITH)->findOrFail($id);

        return $this->respondWithRecordDetail($record, logView: true);
    }

    /** Addressed by (app, per-app record number) — matches the /custom-apps/records/{app}/edit/{number} URL. */
    public function getAppRecordByNumber($definitionId, $number)
    {
        $record = FlowRecord::with(self::RECORD_DETAIL_WITH)
            ->where('flow_definition_id', $definitionId)
            ->where('record_number', $number)
            ->firstOrFail();

        return $this->respondWithRecordDetail($record, logView: true);
    }

    /**
     * $logView is only true from the two GET-by-id/number entry points above — the record-detail
     * response is also reused by write actions (e.g. status transitions) to hand back the fresh
     * record, and those aren't "opens" for audit purposes.
     */
    private function respondWithRecordDetail(FlowRecord $record, bool $logView = false)
    {
        $user = $this->active_user();
        $def = $record->definition;
        $recordPerms = $this->flowService->recordPermissions($user, $record, $def);
        abort_unless($recordPerms['view'], 403);

        if ($logView) {
            $this->flowService->logAudit($def, $user, 'record_view', $record, ['record_number' => $record->record_number]);
            // opening the record clears its new-record / status-change badges (comments clear
            // separately, only after the comment tab has actually been viewed)
            $this->flowNotifications->markRecordOpened($user, $record);
        }

        $statusNames = $def->statuses->pluck('name', 'id');
        $actions = $this->flowService->statusActionsFor($record)->map(fn ($a) => [
            'id' => $a->id,
            'label' => $a->label,
            'color' => $a->color,
            'to_status_id' => $a->to_status_id,
            'to_status' => $statusNames[$a->to_status_id] ?? null,
            'can' => $this->flowService->canPressAction($user, $record, $a),
        ])->values();

        // prev/next record numbers for the header nav arrows.
        $neighbor = function (bool $forward) use ($def, $record, $user) {
            $q = FlowRecord::where('flow_definition_id', $def->id);
            $forward
                ? $q->where('record_number', '>', $record->record_number)->orderBy('record_number')
                : $q->where('record_number', '<', $record->record_number)->orderByDesc('record_number');
            if ($def->recordPermissionSets->isEmpty()) {
                return $q->value('record_number');
            }
            foreach ($q->with('values')->limit(30)->get() as $cand) {
                $cand->setRelation('definition', $def);
                if ($this->flowService->recordPermissions($user, $cand, $def)['view']) {
                    return $cand->record_number;
                }
            }

            return null;
        };

        $logs = $record->logs->sortByDesc('id')->values()->map(fn ($l) => [
            'id' => $l->id,
            'user' => $l->user,
            'action' => $l->action,
            'field' => $l->field,
            'old_value' => $l->old_value,
            'new_value' => $l->new_value,
            'changes' => $l->changes,
            'note' => $l->note,
            'created_at' => $l->created_at,
        ]);

        return response()->json([
            'definition' => $def->makeHidden(['appPermissions', 'recordPermissionSets']),
            'permissions' => $this->flowService->effectiveAppPermissions($user, $def),
            'record' => $this->serializeRecord($record, $def->fields, $recordPerms, $user, $def),
            'can' => $recordPerms,
            'status_actions' => $actions,
            // カスタムボタン（コード側の処理を呼ぶボタン）。押せる人にだけ返る。
            'custom_actions' => $this->flowRecordActions->actionsFor($user, $record),
            'logs' => $logs,
            'mentionable_users' => $this->flowService->mentionableUsers($record, $def),
            // unread comment events for THIS user on this record → comment-tab badge
            'unread_comments' => $this->flowNotifications->unreadCommentCount($user, $record),
            'nav' => $this->recordNeighbors($def, $record, $user, $neighbor),
        ]);
    }

    /**
     * 上下の矢印が指すレコード番号。
     *
     * **一覧と同じ並び・同じ絞り込みで隣を探す。** 番号の前後をそのまま辿ると、ビューで絞った
     * 一覧から開いたときに、一覧に出ていないレコードへ飛んでしまう（画面上の「次」と一致しない）。
     * 絞り込みと並び順は、レコードのURLに乗って渡ってくる一覧の状態（view_id/sort_field/
     * sort_dir/filters）から組み直す。指定が無いときも、一覧と同じく既定のビューを使う。
     *
     * 番号順に落とすのは、SQLで同じ一覧を作れない場合だけ：
     *   - 行ごとの権限があるアプリ（一覧を画面側で組んでいる）
     *   - 数式項目で絞る／並べるビュー（数式は値を保存していないのでSQLで扱えない）
     *   - 対象が極端に多いアプリ（並び順の全件を持つのが割に合わない）
     *
     * @param  callable(bool): ?int  $adjacent  番号で隣を探す従来の方法
     * @return array{prev: ?int, next: ?int}
     */
    private function recordNeighbors(FlowDefinition $def, FlowRecord $record, $user, callable $adjacent): array
    {
        $byNumber = fn () => ['prev' => $adjacent(false), 'next' => $adjacent(true)];

        if ($def->recordPermissionSets->isNotEmpty()) {
            return $byNumber();
        }

        $request = request();
        $views = $def->relationLoaded('views') ? $def->views : $def->views()->get();
        $view = $views->firstWhere('id', (int) $request->input('view_id'))
            ?? $views->firstWhere('is_default', true) ?? $views->first();

        $filters = is_array($view?->filters) ? $view->filters : [];
        $logic = $view?->filter_logic === 'or' ? 'or' : 'and';
        $sort = $request->filled('sort_field')
            ? [['field' => $request->input('sort_field'), 'direction' => $request->input('sort_dir') === 'desc' ? 'desc' : 'asc']]
            : (is_array($view?->sort) ? $view->sort : []);
        $adhoc = $this->decodeAdhocFilter($request);

        $fields = $def->fields;
        $isFormulaRef = fn ($ref) => is_numeric($ref)
            && optional($fields->firstWhere('id', (int) $ref))->input_type === 'formula';
        $usesFormula = collect($filters)->contains(fn ($f) => $isFormulaRef($f['field'] ?? null))
            || collect($sort)->contains(fn ($s) => $isFormulaRef($s['field'] ?? null))
            || collect($adhoc['conditions'] ?? [])->contains(fn ($f) => $isFormulaRef($f['field'] ?? null));
        if ($usesFormula) {
            return $byNumber();
        }

        $query = $this->flowService->recordListQuery(
            $def, $filters, (string) $request->input('search', ''), $adhoc, $logic
        );
        if ((clone $query)->count() > self::NAV_MAX_RECORDS) {
            return $byNumber();
        }
        $this->flowService->applyRecordSort($query, $sort, $def);

        $numbers = $query->pluck('flow_records.record_number')->all();
        $i = array_search($record->record_number, $numbers, true);
        if ($i === false) {
            // 今の絞り込みに入っていないレコードを直接開いた場合。隣が無いのが正しい。
            return ['prev' => null, 'next' => null];
        }

        // 画面の「一つ下」＝一覧で下の行。番号の大小ではなく並び順で決める。
        return [
            'prev' => $numbers[$i + 1] ?? null,
            'next' => $i > 0 ? $numbers[$i - 1] : null,
        ];
    }

    public function storeAppRecord(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'flow_definition_id' => 'required|integer|exists:flow_definitions,id',
            'values' => 'array',
        ]);

        $definition = FlowDefinition::with(['fields', 'statuses', 'appPermissions', 'fieldPermissions'])->findOrFail($data['flow_definition_id']);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['add'], 403);

        $start = $this->flowService->startStatus($definition);

        // On create, editable = field-perm edit ∩ start-status rule (record-edit gate doesn't apply to the creator yet).
        $fp = $this->flowService->fieldPermissions($user, $definition, null);
        $allowed = [];
        foreach ($definition->fields as $f) {
            if ($f->input_type === 'formula' || FlowService::isLayoutType($f->input_type)) {
                continue;
            }
            $statusOk = $start ? ($this->flowService->ruleForField($start, $f->id) === 'edit') : true;
            if (($fp[$f->id]['edit'] ?? true) && $statusOk) {
                $allowed[] = (int) $f->id;
            }
        }

        // same server-side auto-fill as the update path, so a creator without 編集 on the destination
        // still gets it populated (and cannot dictate what lands there)
        [$data['values'], $autoFilled] = $this->flowService->applyMasterAutoFill($definition, $data['values'] ?? [], $allowed);
        $allowed = array_values(array_unique(array_merge($allowed, $autoFilled)));

        $checkFields = $definition->fields->filter(fn ($f) => in_array((int) $f->id, $allowed, true));
        $errors = $this->flowService->validateValues($checkFields, $data['values'] ?? []);
        if (! empty($errors)) {
            return response()->json(['message' => '入力内容を確認してください。', 'errors' => $errors], 422);
        }

        // Counter + insert + values in one transaction: a mid-flight failure rolls the counter
        // back too (no consumed-but-unused number, no half-populated record).
        $record = DB::transaction(function () use ($definition, $start, $user, $data, $allowed) {
            $record = FlowRecord::create([
                'flow_definition_id' => $definition->id,
                'record_number' => $this->flowService->nextRecordNumber($definition),
                'current_status_id' => $start?->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $this->flowService->syncFieldValues($record, $definition->fields, $data['values'] ?? [], $allowed);
            $this->flowService->logRecordCreated($record, $user);

            return $record;
        });

        $record->load(['values', 'currentStatus', 'createdByUser']);

        // badge event: everyone who can view the app hears about the new record (per prefs)
        $this->notifySafely(fn () => $this->flowNotifications->notifyNewRecord($definition, $record, $user));
        // the initial status may already name someone — the first step of a flow should not be the
        // one step nobody is told about
        $this->notifySafely(fn () => $this->flowNotifications->syncPendingAction($record, $user));


        return response()->json($this->serializeRecord($record, $definition->fields));
    }

    public function updateAppRecord(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:flow_records,id',
            'values' => 'required|array',
        ]);

        $user = $this->active_user();
        $record = FlowRecord::with([
            'definition.fields', 'definition.appPermissions', 'definition.recordPermissionSets', 'definition.fieldPermissions',
            'currentStatus', 'values',
        ])->findOrFail($data['id']);
        $def = $record->definition;
        $recordPerms = $this->flowService->recordPermissions($user, $record, $def);
        abort_unless($recordPerms['edit'], 403);

        $allowed = $this->flowService->editableFieldIdsForRecord($user, $record, $def, $recordPerms);

        // ユーザー/プロジェクト auto-fill for destinations this user may NOT write: resolved server-side
        // from the master, and the client's value for them discarded. Without this the value showed in
        // the form and was dropped here, because the writer has no 編集 on the destination.
        [$data['values'], $autoFilled] = $this->flowService->applyMasterAutoFill($def, $data['values'], $allowed);
        $allowed = array_values(array_unique(array_merge($allowed, $autoFilled)));

        $old = $this->flowService->recordValues($record, $def->fields);
        $merged = $old;
        foreach ($data['values'] as $k => $v) {
            $merged[(string) $k] = $v;
        }
        $checkFields = $def->fields->filter(fn ($f) => in_array((int) $f->id, $allowed, true));
        // pass the record so a kept (blank) secret counts as "already set" for 必須
        $errors = $this->flowService->validateValues($checkFields, $merged, $record);
        if (! empty($errors)) {
            return response()->json(['message' => '入力内容を確認してください。', 'errors' => $errors], 422);
        }

        $record->updated_by = $user->id;
        $record->save();
        $this->flowService->syncFieldValues($record, $def->fields, $data['values'], $allowed);

        // A ユーザー field can BE the 押せる人 ('field' subject), so editing the record can hand the
        // duty to someone else — re-resolve. syncPendingAction keeps existing recipients' read state.
        $record->load('values');
        $this->notifySafely(fn () => $this->flowNotifications->syncPendingAction($record, $user));

        // Diff old→new per field (raw values; the front-end formats by field type) for 変更履歴.
        $new = $this->flowService->recordValues($record, $def->fields);
        $changes = [];
        foreach ($def->fields as $f) {
            if ($f->input_type === 'formula' || FlowService::isLayoutType($f->input_type)) {
                continue;
            }
            $o = $old[(string) $f->id] ?? null;
            $n = $new[(string) $f->id] ?? null;
            // Secrets read as an unchanged boolean when rotated (true → true), so a diff alone
            // would hide the rotation. Detect the write intent from what was submitted instead —
            // "when was this credential last changed?" is a question a vault has to answer.
            if (FlowService::isSecret($f->input_type)) {
                $raw = $merged[(string) $f->id] ?? null;
                $wrote = (is_array($raw) && ! empty($raw['clear']))
                    || (! is_bool($raw) && is_scalar($raw) && trim((string) $raw) !== '');
                if ($wrote) {
                    $changes[$f->key] = ['old' => $o, 'new' => $n];
                }

                continue;
            }
            if (json_encode($o) !== json_encode($n)) {
                $changes[$f->key] = ['old' => $o, 'new' => $n];
            }
        }
        if (! empty($changes)) {
            $record->logs()->create(['user_id' => $user->id, 'action' => 'updated', 'changes' => $changes]);
        }

        $record->load(['values', 'currentStatus', 'createdByUser']);


        return response()->json($this->serializeRecord($record, $def->fields, $recordPerms, $user, $def));
    }

    public function deleteAppRecord(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate(['id' => 'required|integer|exists:flow_records,id']);
        $record = FlowRecord::with(['definition.appPermissions', 'definition.recordPermissionSets', 'values', 'currentStatus'])
            ->findOrFail($data['id']);
        abort_unless($this->flowService->recordPermissions($user, $record, $record->definition)['delete'], 403);
        $this->notifySafely(fn () => $this->flowNotifications->withdrawPendingAction($record));
        // 添付の実体も落とす（以前は value だけ消してファイルはディスクに残り続けていた）
        app(FlowFileService::class)->deleteForRecord($record);
        $record->values()->delete();
        $record->delete();

        return response()->json(['deleted' => true]);
    }

    /** Press a status-flow action button: validate eligibility, move status, log 変更履歴. */
    public function transitionAppRecord(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'id' => 'required|integer|exists:flow_records,id',
            'action_id' => 'required|integer',
        ]);
        $record = FlowRecord::with(self::RECORD_DETAIL_WITH)->findOrFail($data['id']);
        $def = $record->definition;
        abort_unless($def->use_status_flow, 422, 'このアプリはステータス機能を使用していません。');

        $action = $this->flowService->statusActionsFor($record)->firstWhere('id', (int) $data['action_id']);
        abort_unless($action, 422, '現在のステータスでは実行できないアクションです。');
        abort_unless($this->flowService->canPressAction($user, $record, $action), 403);

        $fromName = $record->currentStatus?->name;
        $toName = $def->statuses->firstWhere('id', $action->to_status_id)?->name;
        $this->flowService->applyStatusAction($user, $record, $action);

        // badge event: the record's creator hears their record moved (per prefs)
        $this->notifySafely(fn () => $this->flowNotifications->notifyStatusChange($record, $user, $fromName, $toName));
        // the duty moves with the record: whoever was named on the old status is released, whoever is
        // named on the new one is told. syncPendingAction does both.
        $this->notifySafely(fn () => $this->flowNotifications->syncPendingAction($record, $user));

        $record->load(self::RECORD_DETAIL_WITH);

        return $this->respondWithRecordDetail($record);
    }

    /**
     * 関連レコードの中身。レコード詳細の「関連レコード」ブロックが1つずつ取りに来る。
     *
     * 行ごとの権限は子アプリの規則で見る（見えないレコードは件数にも入らない）。
     */
    public function relatedRecords($fieldId, $recordId)
    {
        $user = $this->active_user();
        $record = FlowRecord::with(self::RECORD_DETAIL_WITH)->findOrFail($recordId);
        abort_unless($this->flowService->recordPermissions($user, $record, $record->definition)['view'], 403);

        $field = $record->definition->fields->firstWhere('id', (int) $fieldId);
        abort_unless($field && $field->input_type === 'related', 404);

        return response()->json(app(FlowRelatedService::class)->listFor($user, $record, $field));
    }

    /**
     * 設定画面用：このアプリを指しているルックアップ項目の一覧。
     *
     * 「値の一致で結ぶ」のではなく、既にある関係から選ばせるための材料。ここに出てこない
     * 組み合わせは設定できない＝壊れた関連レコードを作れない。
     */
    public function relatedCandidates($definitionId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with('appPermissions')->findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['manage'], 403);

        $out = [];
        $others = FlowDefinition::with('fields')->where('id', '!=', $definition->id)->get();

        foreach ($others as $child) {
            if (! $this->flowService->effectiveAppPermissions($user, $child)['view']) {
                continue;
            }
            $links = $child->fields
                ->where('input_type', 'reference')
                ->filter(fn ($f) => (int) ($f->validation['target_definition_id'] ?? 0) === (int) $definition->id)
                ->values();

            if ($links->isEmpty()) {
                continue;
            }

            $out[] = [
                'definition_id' => $child->id,
                'definition_name' => $child->name,
                'link_fields' => $links->map(fn ($f) => ['id' => $f->id, 'label' => $f->label])->values()->all(),
                // 一覧に出せる項目（列と合計の選択肢）
                'fields' => $child->fields
                    ->reject(fn ($f) => FlowService::isLayoutType($f->input_type) || FlowService::isSecret($f->input_type))
                    ->map(fn ($f) => ['id' => $f->id, 'label' => $f->label, 'input_type' => $f->input_type])
                    ->values()->all(),
            ];
        }

        return response()->json(['candidates' => $out]);
    }

    /** Keep only a safe inline <svg> (strip scripts, event handlers, foreignObject, javascript: hrefs). */
    private function sanitizeIconSvg(?string $svg): ?string
    {
        $svg = trim((string) $svg);
        if ($svg === '' || ! preg_match('/<svg[\s>]/i', $svg)) {
            return null;
        }
        $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
        $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg);
        $svg = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg);
        $svg = preg_replace('/(?:xlink:)?href\s*=\s*(?:"\s*javascript:[^"]*"|\'\s*javascript:[^\']*\')/i', '', $svg);
        if (preg_match('/<svg\b.*<\/svg>/is', $svg, $m)) {
            $svg = $m[0];
        }

        return mb_strlen($svg) > 20000 ? null : $svg;
    }

    /** Only accept a base64 image data-URI (uploaded, cropped icon). */
    private function sanitizeIconImage(?string $data): ?string
    {
        $data = trim((string) $data);
        if ($data === '') {
            return null;
        }
        if (! preg_match('#^data:image/(?:png|webp|jpe?g|gif);base64,[A-Za-z0-9+/=\s]+$#', $data)) {
            return null;
        }

        return mb_strlen($data) > 400000 ? null : $data;
    }

    /** Generate a minimal single-shape monochrome SVG app icon from the app's name + description. */
    public function generateAppIcon(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);
        $apiKey = config('services.openai.api_key');
        abort_if(! $apiKey, 500, 'OpenAIが設定されていません。');

        $client = \OpenAI::client($apiKey);
        $model = config('services.openai.icon_model', config('services.openai.chat_model', 'gpt-4.1-mini'));

        $desc = trim((string) ($data['description'] ?? ''));
        $desc = trim(strip_tags($desc));
        $instruction = "あなたはアプリ用アイコンをデザインするアシスタントです。与えられたアプリを表す、シンプルで記号的なSVGアイコンを1つだけ生成してください。\n"
            ."要件:\n"
            ."- viewBox=\"0 0 24 24\"\n"
            ."- 単純な図形1〜2個のみ（Feather / Tabler 風の線画アイコン）\n"
            ."- 文字・テキストは含めない\n"
            ."- 背景の四角形は入れない\n"
            ."- 色は必ず currentColor を使う（fill=\"currentColor\" または stroke=\"currentColor\"）\n"
            ."- stroke を使う場合は stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"、fill=\"none\"\n"
            .'- 出力は <svg>...</svg> のコードのみ。説明・マークダウンは不要。';
        $input = "アプリ名: {$data['name']}\n説明: ".($desc !== '' ? $desc : '（なし）');

        try {
            $response = $client->responses()->create([
                'model' => $model,
                'instructions' => $instruction,
                'input' => $input,
            ]);
            $text = '';
            foreach (($response->output ?? []) as $output) {
                if (($output['role'] ?? null) === 'assistant') {
                    foreach (($output['content'] ?? []) as $content) {
                        $text .= $content['text'] ?? '';
                    }
                }
            }
            $svg = $this->sanitizeIconSvg($text);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'アイコンの生成に失敗しました。'.$e->getMessage()], 502);
        }

        if (! $svg) {
            return response()->json(['message' => 'アイコンを生成できませんでした。もう一度お試しください。'], 422);
        }

        return response()->json(['svg' => $svg]);
    }

    public function previewFormula(Request $request)
    {
        $data = $request->validate([
            'formula' => 'required|string',
            'result_type' => 'nullable|string',
            'values' => 'array', // sample values keyed by field key/label — pure computation, no record access
        ]);

        // Formula evaluation is a pure function of the formula + supplied sample values (no DB / record
        // access), so preview works while building an unsaved app and needs no per-app permission.
        $result = app(FlowFormulaEvaluator::class)->evaluateWithError($data['formula'], $data['values'] ?? []);
        $value = $result['ok']
            ? $this->flowService->castFormulaResult($result['value'], $data['result_type'] ?? 'number')
            : null;

        // Detect a result-type mismatch so the editor can warn (e.g. a text result under 数値,
        // which would silently cast to 0). Suggest the type the raw value actually wants.
        $suggested = null;
        if ($result['ok']) {
            $rt = $data['result_type'] ?? 'number';
            $raw = $result['value'];
            if ($rt !== 'text' && is_string($raw) && ! is_numeric($raw)) {
                $suggested = 'text';
            } elseif ($rt !== 'toggle' && is_bool($raw)) {
                $suggested = 'toggle';
            }
        }

        return response()->json([
            'ok' => $result['ok'],
            'value' => $value,
            'suggested_type' => $suggested,
            'error' => $result['error'],
            // references that don't resolve against the supplied sample values — the editor flags
            // these (deleted fields / typos would otherwise silently compute as 0)
            'missing_refs' => app(FlowFormulaEvaluator::class)->missingReferences($data['formula'], $data['values'] ?? []),
        ]);
    }

    /* ================================================================
     | CSV export / import
     |================================================================ */

    /**
     * CSV export. Options:
     *  - encoding: 'utf8' (default, BOM'd for Excel) | 'sjis' (Shift-JIS/CP932, no BOM)
     *  - scope: 'all' (default — the view's columns, table field included as a kintone-style
     *    multi-row block if present) | 'table' (only one chosen Table field's rows, merged across
     *    every record in range, with a leading ID + New-record-flag column for traceability)
     *    | 'no_table' (the view's columns with Table fields dropped, so every record is exactly one
     *    row — 'all' emits one row per subtable row, which is awkward to pivot on)
     *  - table_field_id: required when scope=table
     *
     * Export range (fields/filter/sort) follows the currently open view, UNLESS the caller passes
     * an ad-hoc filter and/or a column sort — those override the view's own filter/sort respectively
     * (they don't compose with it, unlike the live records view). Fields always come from the view.
     */
    public function exportRecords(Request $request, $definitionId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets'])->findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['export'], 403);

        $hasStatus = (bool) $definition->use_status_flow;
        $views = $definition->views()->get();
        $view = $views->firstWhere('id', (int) $request->input('view_id'))
            ?? $views->firstWhere('is_default', true) ?? $views->first();

        $adhocFilter = $this->decodeAdhocFilter($request);
        $overrideSort = $request->filled('sort_field')
            ? [['field' => $request->input('sort_field'), 'direction' => $request->input('sort_dir') === 'desc' ? 'desc' : 'asc']]
            : [];
        $sort = $overrideSort ?: (is_array($view?->sort) ? $view->sort : []);
        // an ad-hoc filter fully replaces the view's own filter for export (not AND-composed, unlike
        // the live records view — see the class doc above)
        $filtersForQuery = $adhocFilter ? [] : (is_array($view?->filters) ? $view->filters : []);

        $query = $this->flowService->recordListQuery($definition, $filtersForQuery, '', $adhocFilter, $view?->filter_logic === 'or' ? 'or' : 'and');
        $this->flowService->applyRecordSort($query, $sort, $definition);
        $records = $query->with(['values', 'currentStatus:id,name'])->get();

        if ($definition->recordPermissionSets->isNotEmpty()) {
            $records = $records->filter(fn ($r) => $this->flowService->recordPermissions($user, $r, $definition)['view'])->values();
        }

        $scope = in_array($request->input('scope'), ['table', 'no_table'], true) ? $request->input('scope') : 'all';
        $encoding = $request->input('encoding') === 'sjis' ? 'sjis' : 'utf8';

        if ($scope === 'table') {
            $tableField = $definition->fields->firstWhere('id', (int) $request->input('table_field_id'));
            abort_if(! $tableField || $tableField->input_type !== 'table', 422, 'テーブル項目を選択してください。');
            [$headers, $rows] = $this->buildTableOnlyExportRows($definition, $tableField, $records);
        } else {
            $columns = $this->flowService->resolveExportColumns($definition, $view, $hasStatus);
            if ($scope === 'no_table') {
                // dropping the Table columns is all it takes: the row builder already emits a single
                // row per record when no table column is present, so no separate code path is needed
                $columns = array_values(array_filter(
                    $columns,
                    fn ($c) => $c['system'] || ($c['field']->input_type ?? null) !== 'table'
                ));
            }
            [$headers, $rows] = $this->buildAllFieldsExportRows($definition, $columns, $records);
        }

        // Str::slug() strips non-ASCII entirely (Japanese app names would come out as "app"), so
        // sanitize just the filesystem-illegal characters instead of transliterating.
        $safeName = trim(preg_replace('/[\\\\\/:*?"<>|]+/u', '_', $definition->name)) ?: 'app';
        $filename = "{$safeName}-".now()->format('YmdHis').'.csv';

        // Build the CSV up front (rather than streaming row-by-row) so the exact bytes served to the
        // browser can also be archived for the audit log — "csv export -> what was exported" needs a
        // real copy on disk, not just a metadata summary of the request.
        $content = $this->buildCsvString($headers, $rows, $encoding);

        $storedPath = "flow_audit_exports/{$definition->id}/".now()->format('Ymd_His')."_u{$user->id}.csv";
        Storage::disk('local')->put($storedPath, $content);

        $this->flowService->logAudit($definition, $user, 'csv_export', null, [
            'filename' => $filename,
            'encoding' => $encoding,
            'scope' => $scope,
            'table_field_id' => $scope === 'table' ? (int) $request->input('table_field_id') : null,
            'row_count' => count($rows),
            'stored_path' => $storedPath,
        ]);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/csv; charset='.($encoding === 'sjis' ? 'Shift_JIS' : 'UTF-8'),
        ]);
    }

    /** Renders headers+rows to a CSV string in the given encoding (BOM'd for utf8, CP932 for sjis). */
    private function buildCsvString(array $headers, array $rows, string $encoding): string
    {
        $out = fopen('php://temp', 'r+');
        if ($encoding === 'utf8') {
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel opens UTF-8 CSVs correctly
        }
        // CP932's double-byte trail bytes (0x40-0x7E, 0x80-0xFC) never collide with the ASCII
        // delimiter/enclosure bytes fputcsv scans for (, " \r \n), so converting per-cell before
        // fputcsv is safe — no risk of a Japanese character's trailing byte being mistaken for a
        // comma/quote and corrupting the row structure.
        $convert = fn ($v) => $encoding === 'sjis' ? mb_convert_encoding((string) $v, 'SJIS-win', 'UTF-8') : $v;
        fputcsv($out, array_map($convert, $headers));
        foreach ($rows as $row) {
            fputcsv($out, array_map($convert, $row));
        }
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return $content;
    }

    /**
     * "All fields" export: the view's own columns, in order. When those columns include a Table
     * field, its rows expand into a kintone-style multi-row block per record — a leading "New record
     * flag" column marks the first physical row of each record ('*') vs. continuations (blank), and
     * the record's own (non-table) column values only appear on that first row, exactly mirroring
     * kintone's own table-export layout (and what FlowController::parseCsv's importer expects back).
     * Only the FIRST table-type column drives this; any further table columns (rare — most apps have
     * at most one) fall back to a flattened single-cell join via csvValue().
     */
    private function buildAllFieldsExportRows(FlowDefinition $definition, array $columns, $records): array
    {
        $tableColIndex = null;
        foreach ($columns as $i => $c) {
            if (! $c['system'] && $c['field']->input_type === 'table') {
                $tableColIndex = $i;
                break;
            }
        }

        $tableColumn = null;
        $scalarColumns = $columns;
        if ($tableColIndex !== null) {
            $tableColumn = $columns[$tableColIndex]['field'];
            unset($scalarColumns[$tableColIndex]);
            $scalarColumns = array_values($scalarColumns);
        }

        $subCols = $tableColumn && is_array($tableColumn->validation['columns'] ?? null) ? $tableColumn->validation['columns'] : [];

        $headers = array_merge(
            $tableColumn ? ['New record flag'] : [],
            array_map(fn ($c) => $c['label'], $scalarColumns),
            array_map(fn ($c) => (string) ($c['label'] ?? $c['key'] ?? ''), $subCols),
        );

        $rows = [];
        foreach ($records as $rec) {
            $vals = $this->flowService->recordValues($rec, $definition->fields);
            $scalarValues = array_map(fn ($c) => $this->exportScalarValue($rec, $c, $vals), $scalarColumns);

            if (! $tableColumn) {
                $rows[] = $scalarValues;

                continue;
            }

            $tableRows = $vals[(string) $tableColumn->id] ?? [];
            if (! is_array($tableRows) || ! count($tableRows)) {
                $tableRows = [[]]; // still emit the record's own row even when its table is empty
            }
            foreach (array_values($tableRows) as $ri => $row) {
                $flag = $ri === 0 ? '*' : '';
                $rowScalars = $ri === 0 ? $scalarValues : array_fill(0, count($scalarValues), '');
                $rowTableVals = array_map(
                    fn ($c) => $this->csvValue(is_array($row) ? ($row[$c['key'] ?? ''] ?? null) : null, $c['input_type'] ?? null),
                    $subCols,
                );
                $rows[] = array_merge([$flag], $rowScalars, $rowTableVals);
            }
        }

        return [$headers, $rows];
    }

    /** One resolved export column's formatted value for a record (system sentinel or a real field). */
    private function exportScalarValue(FlowRecord $rec, array $col, array $vals): string
    {
        if ($col['system']) {
            return match ($col['ref']) {
                '$record_number' => (string) $rec->record_number,
                '$status' => (string) ($rec->currentStatus->name ?? ''),
                '$created_at' => (string) (optional($rec->created_at)->format('Y-m-d H:i:s') ?? ''),
                '$updated_at' => (string) (optional($rec->updated_at)->format('Y-m-d H:i:s') ?? ''),
                default => '',
            };
        }

        return $this->csvValue($vals[(string) $col['field']->id] ?? null, $col['field']->input_type);
    }

    /**
     * "Only tables" export: just one chosen Table field's rows, merged across every record in range.
     * Same kintone-style New-record-flag convention as the all-fields path, with a leading ID
     * (record number) column for traceability — blank on continuation rows, like any other scalar
     * column would be. A record whose table is empty contributes no rows at all.
     */
    private function buildTableOnlyExportRows(FlowDefinition $definition, FlowField $tableField, $records): array
    {
        $subCols = is_array($tableField->validation['columns'] ?? null) ? $tableField->validation['columns'] : [];
        $headers = array_merge(
            ['New record flag', 'ID'],
            array_map(fn ($c) => (string) ($c['label'] ?? $c['key'] ?? ''), $subCols),
        );

        $rows = [];
        foreach ($records as $rec) {
            $vals = $this->flowService->recordValues($rec, $definition->fields);
            $tableRows = $vals[(string) $tableField->id] ?? [];
            if (! is_array($tableRows) || ! count($tableRows)) {
                continue; // nothing to contribute
            }
            foreach (array_values($tableRows) as $ri => $row) {
                $flag = $ri === 0 ? '*' : '';
                $id = $ri === 0 ? (string) $rec->record_number : '';
                $rowVals = array_map(
                    fn ($c) => $this->csvValue(is_array($row) ? ($row[$c['key'] ?? ''] ?? null) : null, $c['input_type'] ?? null),
                    $subCols,
                );
                $rows[] = array_merge([$flag, $id], $rowVals);
            }
        }

        return [$headers, $rows];
    }

    /**
     * CSV import — 3 phases via `phase`:
     *  - analyze: parse the file, return headers + sample rows + suggested mapping + the app's fields (no writes)
     *  - validate: given the uploader's column→field mapping, dry-run validate every row (no writes)
     *  - commit: create any "new field" columns, insert the VALID rows only (initial status), skip+report invalid
     */
    public function importRecords(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate([
            'flow_definition_id' => 'required|integer|exists:flow_definitions,id',
            'csv' => ['required', 'file', 'max:20480'],
            'phase' => ['nullable', 'in:analyze,validate,commit'],
            'mapping' => ['nullable', 'string'],
        ]);

        $definition = FlowDefinition::with(['fields', 'statuses', 'appPermissions'])->findOrFail($data['flow_definition_id']);
        $perms = $this->flowService->effectiveAppPermissions($user, $definition);
        abort_unless($perms['import'], 403);

        $parsed = $this->parseCsv($request);
        $phase = $request->input('phase', 'analyze');

        if ($phase === 'analyze') {
            return response()->json($this->csvAnalyze($definition, $parsed));
        }

        // validate + commit need the uploader's mapping: { "<header>": "<fieldId>" | "__new__" | "__table__" | "__skip__" }
        // plus per-"__new__"/"__table__"-column chosen types, and (for a sub-table) the new Table field's name.
        $mapping = json_decode($request->input('mapping', '{}'), true);
        $newTypes = json_decode($request->input('new_field_types', '{}'), true);
        $tableName = (string) $request->input('table_name', '');
        $tableTarget = (string) $request->input('table_target', '__new__');  // existing Table field id, or '__new__'
        $plan = $this->buildImportPlan($definition, $parsed['headers'], is_array($mapping) ? $mapping : [], is_array($newTypes) ? $newTypes : [], $parsed['rows'], $tableName, $tableTarget);
        $columns = $plan['columns'];
        $system = $plan['system'] ?? [];
        $table = $plan['table'] ?? null;
        abort_if(empty($columns) && ! $table, 422, '取り込む列を1つ以上マッピングしてください。');
        // A schema change (new field, new Table field, or new sub-columns on an existing Table) needs manage.
        $tableChangesSchema = $table && ($table['existingFieldId'] === null || ! empty($table['newSubColumns']));
        $hasNew = collect($columns)->contains(fn ($c) => $c['isNew']) || $tableChangesSchema;
        if ($hasNew && ! $perms['manage']) {
            abort(403, '新規フィールドを作成するにはアプリの管理権限が必要です。');
        }

        // Validation runs once per record (the group's first row); record-level values repeat across a
        // group, so validating the representative row is sufficient. Totals count records, not CSV lines.
        $validation = $this->validateImportRows($parsed['rows'], $columns, $system, $parsed['groups']);

        if ($phase === 'validate') {
            return response()->json($validation + ['sample_errors' => array_slice($validation['invalid'], 0, 50)]);
        }

        $invalidRows = collect($validation['invalid'])->pluck('row')->flip(); // 1-based representative row => idx
        $result = DB::transaction(function () use ($definition, $parsed, $columns, $system, $table, $invalidRows, $user) {
            // Turn "__new__" columns into real fields, then map every column's header → its field.
            $headerToField = [];
            foreach ($columns as $c) {
                $headerToField[$c['header']] = $c['isNew']
                    ? $this->createImportField($definition, $c['header'], $c['type'], $c['options'])
                    : $c['field'];
            }
            // Resolve the sub-table's Table field: reuse the chosen existing field (appending any
            // sub-columns it lacks) or create a new one. subColumns map CSV header → the field's column key.
            $tablePlan = null;
            if ($table) {
                $tablePlan = [
                    'field' => $table['existingFieldId']
                        ? $this->useExistingTableField($definition, $table['existingFieldId'], $table['newSubColumns'])
                        : $this->createImportTableField($definition, $table['label'], $table['subColumns']),
                    'subColumns' => $table['subColumns'],
                ];
            }

            $imported = 0;
            foreach ($parsed['groups'] as $group) {
                if ($invalidRows->has($group[0] + 1)) {   // representative row invalid → skip the whole record
                    continue;
                }
                $groupRows = array_map(fn ($i) => $parsed['rows'][$i], $group);
                $this->importOneGroup($definition, $headerToField, $groupRows, $user, $system, $tablePlan);
                $imported++;
            }

            return ['imported' => $imported, 'skipped' => count($parsed['groups']) - $imported];
        });

        // badge event: ONE grouped notification for the whole import (never one per row)
        if (($result['imported'] ?? 0) > 0) {
            $this->notifySafely(fn () => $this->flowNotifications->notifyImport($definition, $user, (int) $result['imported']));
        }

        return response()->json($result + ['invalid' => array_slice($validation['invalid'], 0, 50)]);
    }

    /** Phase 1: headers + sample + label-based suggested mapping + the app's mappable fields. */
    private function csvAnalyze(FlowDefinition $definition, array $parsed): array
    {
        $fields = $definition->fields
            ->filter(fn ($f) => ! FlowService::isLayoutType($f->input_type) && $f->input_type !== 'formula')
            ->values();
        $byLabel = $fields->keyBy(fn ($f) => mb_strtolower(trim($f->label)));

        // Columns that belong to a kintone sub-table (auto-import into one Table field by default).
        $tableHeaders = $this->detectSubtableColumns($parsed['headers'], $parsed['rows'], $parsed['groups']);
        $tableSet = array_flip($tableHeaders);
        $existingTables = $this->existingTableFields($definition);

        $columns = collect($parsed['headers'])->map(function (string $header) use ($byLabel, $parsed, $tableSet) {
            $sys = $this->suggestSystemColumn($header);
            $match = $byLabel->get(mb_strtolower(trim($header)));
            $inferred = $this->inferColumnType($parsed['rows'], $header);
            $inTable = isset($tableSet[$header]);

            return [
                'header' => $header,
                'in_table' => $inTable,
                'suggested' => $inTable ? '__table__' : ($sys ?? ($match ? (string) $match->id : '__skip__')),
                'recommended_type' => $inferred['type'],           // used when the uploader chooses "＋新規" or table sub-column
                'recommended_options' => $inferred['options'],
            ];
        })->values();

        return [
            'headers' => $parsed['headers'],
            'columns' => $columns,
            'sample_rows' => array_slice($parsed['rows'], 0, 5),
            'row_count' => count($parsed['groups']),               // records (grouped), not physical CSV lines
            'physical_rows' => count($parsed['rows']),
            'subtable' => [
                'present' => ! empty($tableHeaders),
                'columns' => $tableHeaders,
                'suggested_name' => 'テーブル',
                // Existing Table fields the sub-table can be imported into (reuse over creating a duplicate).
                'existing_tables' => $existingTables,
                'suggested_target' => $this->suggestTableTarget($tableHeaders, $existingTables),
            ],
            'fields' => $fields->map(fn ($f) => [
                'id' => $f->id, 'label' => $f->label, 'input_type' => $f->input_type,
                'is_required' => (bool) $f->is_required, 'options' => $f->options ?? [],
            ])->values(),
            // System columns the uploader may map onto (normally set automatically). Importing them
            // preserves the source system's timestamps instead of stamping "now" on every row.
            'system_columns' => [
                ['key' => '__created_at__', 'label' => '作成日時'],
                ['key' => '__updated_at__', 'label' => '更新日時'],
            ],
        ];
    }

    /**
     * Detect a kintone sub-table's columns from the grouped rows. The tell: within a record's group
     * (multiple physical rows), sub-table columns vary while record-level columns repeat identically.
     * kintone lays a table's columns out as one contiguous trailing block, so we take everything from
     * the first varying column to the end of the row. Returns [] when there is no sub-table.
     */
    private function detectSubtableColumns(array $headers, array $rows, array $groups): array
    {
        $firstVarying = null;
        foreach ($headers as $idx => $header) {
            foreach ($groups as $g) {
                if (count($g) < 2) {
                    continue;
                }
                $vals = array_map(fn ($i) => (string) ($rows[$i][$header] ?? ''), $g);
                if (count(array_unique($vals)) > 1) {
                    $firstVarying = $firstVarying === null ? $idx : min($firstVarying, $idx);
                    break;
                }
            }
        }
        if ($firstVarying === null) {
            return [];   // no column varies within any record → no sub-table (or every record is single-row)
        }

        return array_slice($headers, $firstVarying);
    }

    /** The app's Table fields, with their sub-columns, as import targets for a sub-table. */
    private function existingTableFields(FlowDefinition $definition): array
    {
        return $definition->fields
            ->where('input_type', 'table')
            ->map(fn ($f) => [
                'id' => $f->id,
                'label' => $f->label,
                'columns' => collect(is_array($f->validation['columns'] ?? null) ? $f->validation['columns'] : [])
                    ->map(fn ($c) => ['key' => $c['key'] ?? '', 'label' => $c['label'] ?? '', 'input_type' => $c['input_type'] ?? 'short'])
                    ->values()->all(),
            ])->values()->all();
    }

    /** Pick the existing Table field whose columns best match the CSV sub-table (by label); else "__new__". */
    private function suggestTableTarget(array $tableHeaders, array $existingTables): string
    {
        $wanted = array_map(fn ($h) => mb_strtolower(trim($h)), $tableHeaders);
        $best = null;
        $bestScore = 0;
        foreach ($existingTables as $t) {
            $labels = array_map(fn ($c) => mb_strtolower(trim($c['label'])), $t['columns']);
            $score = count(array_filter($wanted, fn ($h) => in_array($h, $labels, true)));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $t['id'];
            }
        }

        return $bestScore > 0 ? (string) $best : '__new__';
    }

    /** Map a CSV header that looks like a timestamp column onto the matching system-column sentinel. */
    private function suggestSystemColumn(string $header): ?string
    {
        $h = mb_strtolower(trim($header));
        if (in_array($h, ['作成日時', '作成日', '登録日時', '登録日', 'created_at', 'created', 'create_at'], true)) {
            return '__created_at__';
        }
        if (in_array($h, ['更新日時', '更新日', 'updated_at', 'updated', 'update_at', 'modified'], true)) {
            return '__updated_at__';
        }

        return null;
    }

    /** Types a "＋新規" column can be created as (CSV-seedable only — excludes user/member/formula/file). */
    private const IMPORT_NEW_TYPES = ['short', 'long', 'number', 'date', 'datetime', 'time', 'select', 'radio', 'checkbox', 'toggle'];

    /** Non-empty values of one CSV column (optionally capped for sampling). */
    private function columnValues(array $rows, string $header, ?int $limit = null): array
    {
        $out = [];
        foreach ($rows as $row) {
            $v = trim((string) ($row[$header] ?? ''));
            if ($v !== '') {
                $out[] = $v;
            }
            if ($limit !== null && count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /** Guess a field type (and select options) from a column's data. Conservative: unsure → short. */
    private function inferColumnType(array $rows, string $header): array
    {
        $vals = $this->columnValues($rows, $header, 200);
        if (! $vals) {
            return ['type' => 'short', 'options' => []];
        }

        $all = fn (callable $fn) => count(array_filter($vals, $fn)) === count($vals);

        $boolSet = ['true', 'false', '1', '0', 'はい', 'いいえ', '有', '無', '○', '×', 'yes', 'no', 'on', 'off'];
        if ($all(fn ($v) => in_array(mb_strtolower($v), $boolSet, true))) {
            return ['type' => 'toggle', 'options' => []];
        }

        // Number — but keep values with a significant leading zero (007, zip, phone) as text.
        $numeric = $all(fn ($v) => preg_match('/^-?\d+(\.\d+)?$/', str_replace(',', '', $v)) === 1);
        $leadingZero = count(array_filter($vals, fn ($v) => preg_match('/^0\d+/', str_replace(',', '', $v)) === 1)) > 0;
        if ($numeric && ! $leadingZero) {
            return ['type' => 'number', 'options' => []];
        }

        if ($all(fn ($v) => preg_match('#^\d{4}[-/]\d{1,2}[-/]\d{1,2}[ T]\d{1,2}:\d{2}#', $v) === 1)) {
            return ['type' => 'datetime', 'options' => []];
        }
        if ($all(fn ($v) => preg_match('#^\d{4}[-/]\d{1,2}[-/]\d{1,2}$#', $v) === 1)) {
            return ['type' => 'date', 'options' => []];
        }
        if ($all(fn ($v) => preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $v) === 1)) {
            return ['type' => 'time', 'options' => []];
        }

        // Select — a small set of repeated distinct values.
        $distinct = array_values(array_unique($vals));
        if (count($distinct) >= 2 && count($distinct) <= 15 && count($vals) >= count($distinct) * 2) {
            return ['type' => 'select', 'options' => $distinct];
        }

        $longish = max(array_map('mb_strlen', $vals)) > 100
            || count(array_filter($vals, fn ($v) => str_contains($v, "\n"))) > 0;
        if ($longish) {
            return ['type' => 'long', 'options' => []];
        }

        return ['type' => 'short', 'options' => []];
    }

    /**
     * Resolve the uploader's mapping into an ordered list of columns, each carrying a FlowField:
     * a REAL field for existing targets, or a TRANSIENT (unsaved) field spec for "__new__" columns
     * (typed per $newTypes, with select/radio/checkbox options derived from the column's data).
     * The transient field is enough for validation; commit turns it into a real field.
     */
    private function buildImportPlan(FlowDefinition $definition, array $headers, array $mapping, array $newTypes, array $rows, string $tableName = '', string $tableTarget = '__new__'): array
    {
        $fieldsById = $definition->fields->keyBy('id');
        $columns = [];
        $system = [];   // slot ('created_at'|'updated_at') => CSV header
        $usedFieldIds = [];
        $newIdx = 0;
        $tableSubRaw = [];   // raw sub-table columns (header/type/options); resolved to keys after the loop
        $seenTableHeaders = [];

        foreach ($headers as $header) {
            $target = $mapping[$header] ?? '__skip__';
            if ($target === '__skip__' || $target === '' || $target === null) {
                continue;
            }

            if ($target === '__created_at__' || $target === '__updated_at__') {
                $slot = $target === '__created_at__' ? 'created_at' : 'updated_at';
                $system[$slot] ??= $header; // first column mapped to a slot wins

                continue;
            }

            // Sub-table column: folds into a single Table field (existing or new), one line per group row.
            if ($target === '__table__') {
                if (in_array($header, $seenTableHeaders, true)) {
                    continue;
                }
                $seenTableHeaders[] = $header;
                $type = $this->resolveNewFieldType($newTypes[$header] ?? null);
                $options = in_array($type, ['select', 'radio', 'checkbox'], true)
                    ? array_values(array_unique($this->columnValues($rows, $header)))
                    : [];
                $tableSubRaw[] = ['header' => $header, 'input_type' => $type, 'options' => $options];

                continue;
            }

            if ($target === '__new__') {
                $type = $this->resolveNewFieldType($newTypes[$header] ?? null);
                $options = in_array($type, ['select', 'radio', 'checkbox'], true)
                    ? array_values(array_unique($this->columnValues($rows, $header)))
                    : [];
                $field = new FlowField(['input_type' => $type, 'is_required' => false, 'options' => $options, 'validation' => []]);
                $field->id = -(++$newIdx); // synthetic negative key (Eloquent int-casts id; must be distinct & not collide with real ids)
                $columns[] = ['header' => $header, 'field' => $field, 'isNew' => true, 'type' => $type, 'options' => $options];

                continue;
            }

            $field = $fieldsById->get((int) $target);
            if (! $field
                || FlowService::isLayoutType($field->input_type)
                || $field->input_type === 'formula'
                || in_array((int) $target, $usedFieldIds, true)) {
                continue; // invalid / duplicate target → treat as skip
            }
            $columns[] = ['header' => $header, 'field' => $field, 'isNew' => false];
            $usedFieldIds[] = (int) $target;
        }

        return ['columns' => $columns, 'system' => $system, 'table' => $this->resolveTablePlan($definition, $tableSubRaw, $tableName, $tableTarget)];
    }

    /**
     * Resolve the sub-table columns against the chosen target: an existing Table field (reuse — match
     * sub-columns by label, append any the target lacks) or a brand-new Table field. Returns null when
     * there are no sub-table columns. `subColumns` carries CSV-header → target-column-key for value assembly.
     */
    private function resolveTablePlan(FlowDefinition $definition, array $tableSubRaw, string $tableName, string $tableTarget): ?array
    {
        if (! $tableSubRaw) {
            return null;
        }

        $existing = ctype_digit($tableTarget)
            ? $definition->fields->first(fn ($f) => (string) $f->id === $tableTarget && $f->input_type === 'table')
            : null;

        if ($existing) {
            $existingCols = is_array($existing->validation['columns'] ?? null) ? $existing->validation['columns'] : [];
            $byLabel = collect($existingCols)->keyBy(fn ($c) => mb_strtolower(trim($c['label'] ?? '')));
            $usedKeys = collect($existingCols)->pluck('key')->filter()->all();
            $subColumns = [];
            $newSubColumns = [];   // CSV sub-columns the target lacks → appended to its schema on commit
            foreach ($tableSubRaw as $sc) {
                $match = $byLabel->get(mb_strtolower(trim($sc['header'])));
                if ($match) {
                    $subColumns[] = ['header' => $sc['header'], 'key' => $match['key']];
                } else {
                    $key = $this->uniqueSubColumnKey($sc['header'], $usedKeys);
                    $usedKeys[] = $key;
                    $col = ['header' => $sc['header'], 'key' => $key, 'input_type' => $sc['input_type'], 'options' => $sc['options']];
                    $subColumns[] = ['header' => $sc['header'], 'key' => $key];
                    $newSubColumns[] = $col;
                }
            }

            return ['existingFieldId' => $existing->id, 'label' => $existing->label, 'subColumns' => $subColumns, 'newSubColumns' => $newSubColumns];
        }

        // Brand-new Table field: every sub-table column becomes a fresh sub-column.
        $usedKeys = [];
        $subColumns = [];
        foreach ($tableSubRaw as $sc) {
            $key = $this->uniqueSubColumnKey($sc['header'], $usedKeys);
            $usedKeys[] = $key;
            $subColumns[] = ['header' => $sc['header'], 'key' => $key, 'input_type' => $sc['input_type'], 'options' => $sc['options']];
        }
        $label = trim($tableName) !== '' ? trim($tableName) : 'テーブル';

        return ['existingFieldId' => null, 'label' => $label, 'subColumns' => $subColumns, 'newSubColumns' => []];
    }

    /** A CSV-seedable field type, falling back to short text when unset/invalid. */
    private function resolveNewFieldType(?string $type): string
    {
        return in_array($type, self::IMPORT_NEW_TYPES, true) ? $type : 'short';
    }

    /** A stable, unique sub-column key for a Table field, derived from the CSV header. */
    private function uniqueSubColumnKey(string $header, array $used): string
    {
        $base = Str::slug($header, '_') ?: 'col';
        $key = $base;
        $n = 2;
        while (in_array($key, $used, true)) {
            $key = $base.'_'.$n;
            $n++;
        }

        return $key;
    }

    /**
     * Dry-run validate each record against the mapped columns (existing fields + typed new columns alike).
     * When rows are grouped (kintone sub-table), only the group's representative (first) row is validated —
     * record-level values repeat across the group, and sub-table columns aren't record-level fields.
     */
    private function validateImportRows(array $rows, array $columns, array $system = [], ?array $groups = null): array
    {
        $fields = collect($columns)->pluck('field');
        $invalid = [];

        // Representative row index per record; default = every row is its own record.
        $repIndexes = $groups !== null ? array_map(fn ($g) => $g[0], $groups) : array_keys($rows);

        foreach ($repIndexes as $i) {
            $row = $rows[$i];
            $valuesById = [];
            foreach ($columns as $c) {
                $valuesById[$c['field']->id] = $row[$c['header']] ?? null;
            }
            $errors = $this->flowService->validateValues($fields, $valuesById);

            // validateValues doesn't check option membership — enforce it here for select/radio/checkbox.
            foreach ($columns as $c) {
                $field = $c['field'];
                if (isset($errors[(string) $field->id])) {
                    continue;
                }
                $val = $row[$c['header']] ?? null;
                if ($val === null || $val === '') {
                    continue;
                }
                $options = is_array($field->options) ? $field->options : [];
                if (($field->input_type === 'select' || $field->input_type === 'radio') && $options && ! in_array($val, $options, true)) {
                    $errors[(string) $field->id] = '選択肢にない値です。';
                } elseif ($field->input_type === 'checkbox' && $options) {
                    $vals = array_filter(array_map('trim', explode(',', (string) $val)), fn ($v) => $v !== '');
                    $bad = array_diff($vals, $options);
                    if ($bad) {
                        $errors[(string) $field->id] = '選択肢にない値です: '.implode(', ', $bad);
                    }
                }
            }

            // System timestamp columns: a non-empty value must parse as a date/time.
            $sysErrors = [];
            foreach ($system as $header) {
                $val = trim((string) ($row[$header] ?? ''));
                if ($val !== '' && ! $this->parsableDateTime($val)) {
                    $sysErrors[$header] = '日時として認識できません。';
                }
            }

            if ($errors || $sysErrors) {
                $byField = [];
                foreach ($columns as $c) {
                    if (isset($errors[(string) $c['field']->id])) {
                        $byField[] = ['header' => $c['header'], 'message' => $errors[(string) $c['field']->id]];
                    }
                }
                foreach ($sysErrors as $header => $message) {
                    $byField[] = ['header' => $header, 'message' => $message];
                }
                $invalid[] = ['row' => $i + 1, 'errors' => $byField];
            }
        }

        $total = count($repIndexes);

        return ['total' => $total, 'valid_count' => $total - count($invalid), 'invalid' => $invalid];
    }

    private function parsableDateTime(string $v): bool
    {
        try {
            Carbon::parse($v);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * One CSV cell. $inputType is what turns a stored key into the value a person reads: ユーザー and
     * プロジェクト hold ids, 参照 holds a {id, number, label} snapshot — exported verbatim those came out as
     * "487", the project's id, and "1159, 東京工業株式会社, 2". The same resolution the record screen and
     * formulas use (FlowService::displayValue), so the file matches what the app shows. Passing no type
     * leaves the value alone.
     */
    private function csvValue($v, ?string $inputType = null): string
    {
        $v = $this->flowService->displayValue($inputType, $v);

        if (is_array($v)) {
            return implode(', ', array_map(fn ($x) => is_array($x) ? (string) ($x['name'] ?? '') : (string) $x, $v));
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }

        return $v === null ? '' : (string) $v;
    }

    private function parseCsv(Request $request): array
    {
        $file = $request->file('csv');
        $handle = fopen($file->getRealPath(), 'rb');
        abort_unless($handle, 422, 'CSVファイルを読み込めませんでした。');

        $headers = null;
        $hasFlag = false;   // kintone table exports put a "New record flag" column first
        $rows = [];
        $flags = [];        // per physical row: the flag cell ('*' = new record, '' = continuation sub-table row)
        while (($line = fgetcsv($handle)) !== false) {
            $line = array_map(fn ($c) => $this->csvCell($c), $line);
            if ($headers === null) {
                if ($this->isEmptyCsvRow($line)) {
                    continue;
                }
                $hasFlag = $this->isNewRecordFlagHeader($line[0] ?? '');
                if ($hasFlag) {
                    $line = array_slice($line, 1);
                }
                $headers = $this->normalizeHeaders($line);

                continue;
            }
            if ($this->isEmptyCsvRow($line)) {
                continue;
            }
            $flag = '';
            if ($hasFlag) {
                $flag = trim((string) ($line[0] ?? ''));
                $line = array_slice($line, 1);
            }
            $line = array_pad(array_slice($line, 0, count($headers)), count($headers), null);
            $rows[] = array_combine($headers, $line);
            $flags[] = $flag;
            abort_if(count($rows) > 5000, 422, '一度に取り込めるCSVは5000行までです。');
        }
        fclose($handle);
        abort_if(! $headers || count($headers) === 0, 422, 'CSVのヘッダー行が必要です。');

        // Group physical rows into records. With a flag column, '*' (non-empty) starts a record and
        // blank-flag rows are additional sub-table lines of the same record. Without it, 1 row = 1 record.
        $groups = [];
        foreach ($rows as $i => $_) {
            if (! $hasFlag || trim((string) $flags[$i]) !== '' || empty($groups)) {
                $groups[] = [$i];
            } else {
                $groups[count($groups) - 1][] = $i;
            }
        }

        return ['headers' => $headers, 'rows' => $rows, 'groups' => $groups, 'has_flag' => $hasFlag];
    }

    /** kintone prefixes a table-bearing CSV export with this exact column. */
    private function isNewRecordFlagHeader(?string $header): bool
    {
        return in_array(mb_strtolower(trim((string) $header)), ['new record flag', 'new_record_flag'], true);
    }

    private function csvCell($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;
        if (function_exists('mb_convert_encoding')) {
            $value = mb_convert_encoding($value, 'UTF-8', ['UTF-8', 'SJIS-win', 'SJIS', 'EUC-JP', 'ASCII']);
        }

        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $value));
    }

    private function isEmptyCsvRow(array $row): bool
    {
        return collect($row)->every(fn ($c) => trim((string) $c) === '');
    }

    private function normalizeHeaders(array $headers): array
    {
        $seen = [];

        return collect($headers)->map(function ($header, int $i) use (&$seen) {
            $label = trim((string) $header);
            $label = preg_replace('/^\xEF\xBB\xBF/', '', $label) ?: 'Column '.($i + 1);
            $base = $label;
            $n = 2;
            while (in_array(mb_strtolower($label), $seen, true)) {
                $label = "{$base} ({$n})";
                $n++;
            }
            $seen[] = mb_strtolower($label);

            return $label;
        })->values()->all();
    }

    /** Create one field for a CSV column the uploader chose to add, with the chosen type (+ options for choice types). */
    private function createImportField(FlowDefinition $definition, string $header, string $type = 'short', array $options = [])
    {
        if (! in_array($type, self::IMPORT_NEW_TYPES, true)) {
            $type = 'short';
        }
        $order = (int) $definition->fields()->max('order_number') + 1;

        return $definition->fields()->create([
            'key' => $this->uniqueFieldKey($definition, $header),
            'label' => $header !== '' ? $header : 'field',
            'input_type' => $type,
            'options' => in_array($type, ['select', 'radio', 'checkbox'], true) && $options ? array_values($options) : null,
            'is_required' => false,
            'hidden' => false,
            'order_number' => $order,
            'layout_row' => $order,
            'width' => 260,
        ]);
    }

    /** Create the Table field for an imported kintone sub-table, with one sub-column per CSV column. */
    private function createImportTableField(FlowDefinition $definition, string $label, array $subColumns): FlowField
    {
        $order = (int) $definition->fields()->max('order_number') + 1;
        $cols = array_map(function (array $sc) {
            $type = $this->resolveNewFieldType($sc['input_type'] ?? null);

            return [
                'key' => $sc['key'],
                'label' => $sc['header'] !== '' ? $sc['header'] : $sc['key'],
                'input_type' => $type,
                'options' => in_array($type, ['select', 'radio', 'checkbox'], true) && ! empty($sc['options']) ? array_values($sc['options']) : null,
                'width' => 200,
            ];
        }, $subColumns);

        return $definition->fields()->create([
            'key' => $this->uniqueFieldKey($definition, $label ?: 'table'),
            'label' => $label !== '' ? $label : 'テーブル',
            'input_type' => 'table',
            'options' => null,
            'validation' => ['columns' => $cols],
            'is_required' => false,
            'hidden' => false,
            'order_number' => $order,
            'layout_row' => $order,
            'width' => 260,
        ]);
    }

    /** Reuse an existing Table field, appending any sub-columns the CSV had that it lacks. */
    private function useExistingTableField(FlowDefinition $definition, int $fieldId, array $newSubColumns): FlowField
    {
        $field = $definition->fields->first(fn ($f) => $f->id === $fieldId) ?? FlowField::findOrFail($fieldId);
        if ($newSubColumns) {
            $cols = is_array($field->validation['columns'] ?? null) ? $field->validation['columns'] : [];
            foreach ($newSubColumns as $sc) {
                $type = $this->resolveNewFieldType($sc['input_type'] ?? null);
                $cols[] = [
                    'key' => $sc['key'],
                    'label' => $sc['header'] !== '' ? $sc['header'] : $sc['key'],
                    'input_type' => $type,
                    'options' => in_array($type, ['select', 'radio', 'checkbox'], true) && ! empty($sc['options']) ? array_values($sc['options']) : null,
                    'width' => 200,
                ];
            }
            $validation = is_array($field->validation) ? $field->validation : [];
            $validation['columns'] = $cols;
            $field->validation = $validation;
            $field->save();
        }

        return $field;
    }

    private function uniqueFieldKey(FlowDefinition $definition, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $key = $base;
        $n = 2;
        $used = $definition->fields()->pluck('key')->map(fn ($k) => strtolower($k))->all();
        while (in_array(strtolower($key), $used, true)) {
            $key = $base.'_'.$n;
            $n++;
        }

        return $key;
    }

    /** Insert one record from a CSV row (initial status if the app uses the flow), writing the mapped cells. */
    /**
     * Insert one record from a group of CSV rows. Record-level cells (and system timestamps) come from
     * the group's first row; when a sub-table is configured, every row in the group contributes one
     * line to the Table field. A single-row group (no sub-table) behaves exactly like one record.
     */
    private function importOneGroup(FlowDefinition $definition, array $headerToField, array $groupRows, $user, array $system = [], ?array $tablePlan = null): void
    {
        $rep = $groupRows[0];
        $start = $this->flowService->startStatus($definition);
        $record = FlowRecord::create([
            'flow_definition_id' => $definition->id,
            'record_number' => $this->flowService->nextRecordNumber($definition),
            'current_status_id' => $start?->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        foreach ($headerToField as $header => $field) {
            // ユーザー/プロジェクト columns arrive as names (that is what the export writes) — turn them
            // back into ids before saving, or the cell would silently import as empty / 0.
            $cell = $this->flowService->valueFromDisplay($field->input_type, $rep[$header] ?? null);
            $this->flowService->saveFieldValue($record, $field, $cell);
        }

        // Sub-table: one line per group row, keyed by sub-column. Fully-empty lines are dropped.
        if ($tablePlan) {
            // Sub-column types come off the resolved Table field: a ユーザー/プロジェクト column inside an
            // existing table needs the same name→id pass as a top-level one. (Columns the import
            // creates are only ever plain types — see IMPORT_NEW_TYPES — so this only ever matters
            // when importing into a table that was built in the app.)
            $subTypes = [];
            foreach (($tablePlan['field']->validation['columns'] ?? []) as $c) {
                if (! empty($c['key'])) {
                    $subTypes[$c['key']] = $c['input_type'] ?? null;
                }
            }
            $tableRows = [];
            foreach ($groupRows as $r) {
                $cells = [];
                $empty = true;
                foreach ($tablePlan['subColumns'] as $sc) {
                    $v = $r[$sc['header']] ?? null;
                    $cells[$sc['key']] = $this->flowService->valueFromDisplay($subTypes[$sc['key']] ?? null, $v);
                    if (trim((string) $v) !== '') {
                        $empty = false;
                    }
                }
                if (! $empty) {
                    $tableRows[] = $cells;
                }
            }
            $this->flowService->saveFieldValue($record, $tablePlan['field'], $tableRows);
        }

        // Imported created_at / updated_at: written last via a raw update so Eloquent's automatic
        // timestamping (which would stamp "now") doesn't overwrite the source values.
        $stamps = [];
        foreach ($system as $slot => $header) {
            $val = trim((string) ($rep[$header] ?? ''));
            if ($val === '') {
                continue;
            }
            try {
                $stamps[$slot] = Carbon::parse($val)->toDateTimeString();
            } catch (\Throwable) {
            }
        }
        if ($stamps) {
            DB::table('flow_records')->where('id', $record->id)->update($stamps);
        }
    }
}
