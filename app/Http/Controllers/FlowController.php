<?php

namespace App\Http\Controllers;

use App\Models\FlowAppTool;
use App\Models\FlowAuditLog;
use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\positionRecord;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Services\FlowFormulaEvaluator;
use App\Services\FlowService;
use App\Services\KintoneImportService;
use App\Services\PdfRenderService;
use App\Support\FlowSystemSources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Output\Destination;

class FlowController extends Controller
{
    public function __construct(private FlowService $flowService) {}

    private function active_user()
    {
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();

        return $sub ?: Auth::user();
    }

    private function ensureCanManageFlows(): void
    {
        abort_unless($this->flowService->canManageFlows($this->active_user()), 403);
    }

    /* ================================================================
     | Builder — flow definitions
     |================================================================ */

    public function getFlowDefinitions(Request $request)
    {
        $user = $this->active_user();
        $projectId = $request->input('project_id');

        $pinned = DB::table('flow_app_pins')->where('user_id', $user->id)->pluck('flow_definition_id')->flip();

        return FlowDefinition::query()
            ->when($projectId, fn ($q) => $q->where('project_record_id', $projectId))
            ->when(! $projectId, fn ($q) => $q->whereNull('project_record_id'))
            ->with(['creator', 'appPermissions'])
            ->withCount(['fields', 'statuses', 'records'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($d) use ($user) {
                $perms = $this->flowService->effectiveAppPermissions($user, $d);
                // expose 管理 so the portal card menu only offers 設定/削除 to those who can use them
                // (both are manage-gated server-side; this keeps the UI honest)
                $d->setAttribute('can_manage', $perms['manage']);

                return $perms['view'];
            })
            ->map(function ($d) use ($pinned) {
                // "全社員に公開" reflects actual permissions, not the vestigial visibility flag.
                $d->setAttribute('is_public', $d->appPermissions->contains(
                    fn ($p) => $p->subject_type === 'everyone' && $p->can_view
                ));
                $d->setAttribute('pinned', $pinned->has($d->id));

                return $d->makeHidden('appPermissions');
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
                DB::table('flow_record_values')->whereIn('flow_record_id', $ids)->delete();
                DB::table('flow_record_assignees')->whereIn('flow_record_id', $ids)->delete();
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

    private function pdfFilename(FlowAppTool $tool, FlowDefinition $definition, FlowRecord $record): string
    {
        $pattern = $tool->config['filename'] ?? ($tool->name.'_{seq}');
        $name = strtr($pattern, [
            '{seq}' => (string) ($record->record_seq ?? $record->id),
            '{id}' => (string) $record->id,
            '{app}' => (string) $definition->name,
        ]);
        $name = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);

        return ($name ?: 'document').'.pdf';
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
            'fields' => $definition->fields
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
            'fields.*.label' => 'nullable|string|max:255',
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
                'sort_order' => $r['sort_order'] ?? $i,
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
            $payload = [
                'tool_type' => $t['tool_type'] ?? 'pdf',
                'name' => $t['name'] ?? 'ツール',
                'config' => $t['config'] ?? [],
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
            $payload = [
                'key' => $field['key'],
                'label' => $field['label'] ?? '',
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
            'views' => $definition->views()->get(['id', 'name', 'is_default', 'columns', 'filters', 'sort'])->toArray(),
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
            'views' => $byId(['name', 'is_default', 'columns', 'filters', 'sort']),
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
        $this->active_user();

        return response()->json([
            'users' => User::query()
                ->where('retire', 0)
                ->select('id', 'name', 'position_id', 'icon_path', 'icon_bg')
                ->orderBy('name')
                ->get(),
            'positions' => positionRecord::query()
                ->where('deleted_flag', 0)
                ->select('id', 'name')
                ->orderBy('sort_flag')
                ->get(),
            'projects' => ProjectRecord::query()
                ->select('id', 'name')
                ->orderByDesc('id')
                ->get(),
        ]);
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
        $definition = FlowDefinition::with(['fields', 'statuses', 'appPermissions', 'recordPermissionSets', 'tools' => fn ($q) => $q->where('is_active', true)])->findOrFail($definitionId);
        $app = $this->flowService->effectiveAppPermissions($user, $definition);
        abort_unless($app['view'], 403);

        $fields = $definition->fields;
        $views = $definition->views()->with('creator')->get();
        $with = ['values', 'currentStatus:id,name', 'createdByUser'];
        $base = [
            'definition' => $definition->makeHidden('appPermissions'),
            'permissions' => $app,
            'views' => $views,
        ];

        // Record-level permissions must be evaluated per record in PHP — can't be SQL-paginated
        // cleanly, so those apps return the full visible set and the front-end paginates.
        if ($definition->recordPermissionSets->isNotEmpty()) {
            $records = FlowRecord::where('flow_definition_id', $definition->id)
                ->with($with)->orderByDesc('created_at')->get()
                ->map(fn ($r) => ['rec' => $r, 'rp' => $this->flowService->recordPermissions($user, $r, $definition)])
                ->filter(fn ($x) => $x['rp']['view'])
                ->values();

            return response()->json($base + [
                'mode' => 'client',
                'records' => $records->map(fn ($x) => $this->serializeRecord($x['rec'], $fields, $x['rp']))->values(),
                'total' => $records->count(),
            ]);
        }

        // Server mode: filter + sort + search + offset pagination, all in SQL.
        $perPage = min(200, max(1, (int) $request->input('per_page', 50)));
        $page = max(1, (int) $request->input('page', 1));
        $view = $views->firstWhere('id', (int) $request->input('view_id'))
            ?? $views->firstWhere('is_default', true) ?? $views->first();
        $filters = is_array($view?->filters) ? $view->filters : [];
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
                ->with($with)->orderByDesc('created_at')->get();
            $can = ['edit' => $app['edit'], 'delete' => $app['delete']];

            return response()->json($base + [
                'mode' => 'client',
                'records' => $records->map(fn ($r) => $this->serializeRecord($r, $fields, $can))->values(),
                'total' => $records->count(),
            ]);
        }

        $query = $this->flowService->recordListQuery($definition, $filters, (string) $request->input('search', ''), $adhocFilter);
        $total = (clone $query)->count();
        $this->flowService->applyRecordSort($query, $sort, $definition);
        $records = $query->with($with)->forPage($page, $perPage)->get();

        $can = ['edit' => $app['edit'], 'delete' => $app['delete']];

        return response()->json($base + [
            'mode' => 'server',
            'records' => $records->map(fn ($r) => $this->serializeRecord($r, $fields, $can))->values(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
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
     * Best-effort self-report of a Flow file-field download (mirrors DriveController's own
     * writeDownloadLogs precedent: the frontend reports the event after it fires the download,
     * rather than gating the shared /cdn/ file route itself). The record id is recoverable straight
     * from the stored path (flow_record_files/{record_id}/...), so no extra props need threading
     * through FlowFieldInput → FilePreview just to identify which record a download belongs to.
     */
    public function logFileDownload(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|string',
            'name' => 'required|string',
        ]);

        if (! preg_match('#^/cdn/flow_record_files/(\d+)/#', $data['url'], $m)) {
            return response()->noContent();
        }

        $record = FlowRecord::with('definition')->find((int) $m[1]);
        if (! $record) {
            return response()->noContent();
        }

        $this->flowService->logAudit($record->definition, $this->active_user(), 'file_download', $record, [
            'file_name' => $data['name'],
        ]);

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

        $wantKeys = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('fields', '')))));
        if (! $wantKeys) {
            return response()->json(['values' => (object) []]);
        }

        $fields = $definition->fields;
        $vals = $this->flowService->recordValues($record, $fields); // keyed by field id
        $fp = $this->flowService->fieldPermissions($user, $definition);
        $byKey = $fields->keyBy('key');

        $out = [];
        foreach ($wantKeys as $key) {
            $f = $byKey->get($key);
            if (! $f || ! ($fp[$f->id]['view'] ?? true)) {
                continue;
            }
            $out[$key] = $vals[(string) $f->id] ?? null;
        }

        return response()->json(['values' => $out]);
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
            'fields' => collect($s['columns'])
                ->map(fn ($c) => ['key' => $c['key'], 'label' => $c['label'], 'input_type' => 'short', 'result_type' => null])
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

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $s['model']::query();
        if (isset($s['filter'])) {
            ($s['filter'])($query);
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

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $s['model']::query();
        if (isset($s['filter'])) {
            ($s['filter'])($query);
        }
        $row = $query->whereKey($id)->first();
        if (! $row) {
            return response()->json(['values' => (object) []]);
        }

        $allowed = collect($s['columns'])->pluck('key')->flip();
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

    private function serializeRecord(FlowRecord $record, $fields, ?array $can = null): array
    {
        return [
            'id' => $record->id,
            'record_number' => $record->record_number,
            'values' => $this->flowService->recordValues($record, $fields),
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
        ];
    }

    private const RECORD_DETAIL_WITH = [
        'definition.fields', 'definition.statuses', 'definition.appPermissions', 'definition.recordPermissionSets', 'definition.statusActions', 'definition.tools',
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
            'record' => $this->serializeRecord($record, $def->fields),
            'can' => $recordPerms,
            'status_actions' => $actions,
            'logs' => $logs,
            'mentionable_users' => $this->flowService->mentionableUsers($record, $def),
        ]);
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
        abort_unless($this->flowService->recordPermissions($user, $record, $def)['edit'], 403);

        $allowed = $this->flowService->editableFieldIdsForRecord($user, $record, $def);

        $old = $this->flowService->recordValues($record, $def->fields);
        $merged = $old;
        foreach ($data['values'] as $k => $v) {
            $merged[(string) $k] = $v;
        }
        $checkFields = $def->fields->filter(fn ($f) => in_array((int) $f->id, $allowed, true));
        $errors = $this->flowService->validateValues($checkFields, $merged);
        if (! empty($errors)) {
            return response()->json(['message' => '入力内容を確認してください。', 'errors' => $errors], 422);
        }

        $record->updated_by = $user->id;
        $record->save();
        $this->flowService->syncFieldValues($record, $def->fields, $data['values'], $allowed);

        // Diff old→new per field (raw values; the front-end formats by field type) for 変更履歴.
        $record->load('values');
        $new = $this->flowService->recordValues($record, $def->fields);
        $changes = [];
        foreach ($def->fields as $f) {
            if ($f->input_type === 'formula' || FlowService::isLayoutType($f->input_type)) {
                continue;
            }
            $o = $old[(string) $f->id] ?? null;
            $n = $new[(string) $f->id] ?? null;
            if (json_encode($o) !== json_encode($n)) {
                $changes[$f->key] = ['old' => $o, 'new' => $n];
            }
        }
        if (! empty($changes)) {
            $record->logs()->create(['user_id' => $user->id, 'action' => 'updated', 'changes' => $changes]);
        }

        $record->load(['values', 'currentStatus', 'createdByUser']);

        return response()->json($this->serializeRecord($record, $def->fields));
    }

    public function deleteAppRecord(Request $request)
    {
        $user = $this->active_user();
        $data = $request->validate(['id' => 'required|integer|exists:flow_records,id']);
        $record = FlowRecord::with(['definition.appPermissions', 'definition.recordPermissionSets', 'values', 'currentStatus'])
            ->findOrFail($data['id']);
        abort_unless($this->flowService->recordPermissions($user, $record, $record->definition)['delete'], 403);
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

        $this->flowService->applyStatusAction($user, $record, $action);

        $record->load(self::RECORD_DETAIL_WITH);

        return $this->respondWithRecordDetail($record);
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

        $query = $this->flowService->recordListQuery($definition, $filtersForQuery, '', $adhocFilter);
        $this->flowService->applyRecordSort($query, $sort, $definition);
        $records = $query->with(['values', 'currentStatus:id,name'])->get();

        if ($definition->recordPermissionSets->isNotEmpty()) {
            $records = $records->filter(fn ($r) => $this->flowService->recordPermissions($user, $r, $definition)['view'])->values();
        }

        $scope = $request->input('scope') === 'table' ? 'table' : 'all';
        $encoding = $request->input('encoding') === 'sjis' ? 'sjis' : 'utf8';

        if ($scope === 'table') {
            $tableField = $definition->fields->firstWhere('id', (int) $request->input('table_field_id'));
            abort_if(! $tableField || $tableField->input_type !== 'table', 422, 'テーブル項目を選択してください。');
            [$headers, $rows] = $this->buildTableOnlyExportRows($definition, $tableField, $records);
        } else {
            $columns = $this->flowService->resolveExportColumns($definition, $view, $hasStatus);
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
                    fn ($c) => $this->csvValue(is_array($row) ? ($row[$c['key'] ?? ''] ?? null) : null),
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

        return $this->csvValue($vals[(string) $col['field']->id] ?? null);
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
                    fn ($c) => $this->csvValue(is_array($row) ? ($row[$c['key'] ?? ''] ?? null) : null),
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
            \Carbon\Carbon::parse($v);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function csvValue($v): string
    {
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
            $this->flowService->saveFieldValue($record, $field, $rep[$header] ?? null);
        }

        // Sub-table: one line per group row, keyed by sub-column. Fully-empty lines are dropped.
        if ($tablePlan) {
            $tableRows = [];
            foreach ($groupRows as $r) {
                $cells = [];
                $empty = true;
                foreach ($tablePlan['subColumns'] as $sc) {
                    $v = $r[$sc['header']] ?? null;
                    $cells[$sc['key']] = $v;
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
                $stamps[$slot] = \Carbon\Carbon::parse($val)->toDateTimeString();
            } catch (\Throwable) {
            }
        }
        if ($stamps) {
            DB::table('flow_records')->where('id', $record->id)->update($stamps);
        }
    }
}
