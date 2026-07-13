<?php

namespace App\Http\Controllers;

use App\Models\FlowAppTool;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->filter(fn ($d) => $this->flowService->effectiveAppPermissions($user, $d)['view'])
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
                ->map(fn ($f) => ['key' => $f->key, 'label' => $f->label, 'input_type' => $f->input_type])
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

        // Formula fields hold no stored value, so SQL can't filter/sort by them. When a view's
        // filters or sort reference one, fall back to the client-compute path: return every visible
        // record with its computed values and let the front-end filter/sort/paginate (mirrors the
        // record-permission branch above). Keeps SQL pagination for the common no-formula case.
        $isFormulaRef = function ($ref) use ($fields) {
            return is_numeric($ref)
                && optional($fields->firstWhere('id', (int) $ref))->input_type === 'formula';
        };
        $needsCompute = collect($filters)->contains(fn ($f) => $isFormulaRef($f['field'] ?? null))
            || collect($sort)->contains(fn ($s) => $isFormulaRef($s['field'] ?? null));
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

        $query = $this->flowService->recordListQuery($definition, $filters, (string) $request->input('search', ''));
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

        return $this->respondWithRecordDetail($record);
    }

    /** Addressed by (app, per-app record number) — matches the /custom-apps/records/{app}/edit/{number} URL. */
    public function getAppRecordByNumber($definitionId, $number)
    {
        $record = FlowRecord::with(self::RECORD_DETAIL_WITH)
            ->where('flow_definition_id', $definitionId)
            ->where('record_number', $number)
            ->firstOrFail();

        return $this->respondWithRecordDetail($record);
    }

    private function respondWithRecordDetail(FlowRecord $record)
    {
        $user = $this->active_user();
        $def = $record->definition;
        $recordPerms = $this->flowService->recordPermissions($user, $record, $def);
        abort_unless($recordPerms['view'], 403);

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

    public function exportRecords($definitionId)
    {
        $user = $this->active_user();
        $definition = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets'])->findOrFail($definitionId);
        abort_unless($this->flowService->effectiveAppPermissions($user, $definition)['export'], 403);

        $fields = $definition->fields->filter(fn ($f) => ! $f->hidden && ! FlowService::isLayoutType($f->input_type))->values();
        $records = FlowRecord::where('flow_definition_id', $definition->id)
            ->with('values')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($r) => $this->flowService->recordPermissions($user, $r, $definition)['view'])
            ->values();

        $filename = (Str::slug($definition->name) ?: 'app').'-'.now()->format('YmdHis').'.csv';

        return response()->streamDownload(function () use ($records, $fields, $definition) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['ID'], $fields->pluck('label')->all(), ['作成日時', '更新日時']));
            foreach ($records as $rec) {
                $vals = $this->flowService->recordValues($rec, $definition->fields);
                $row = [$rec->id];
                foreach ($fields as $f) {
                    $row[] = $this->csvValue($vals[(string) $f->id] ?? null);
                }
                $row[] = optional($rec->created_at)->format('Y-m-d H:i:s');
                $row[] = optional($rec->updated_at)->format('Y-m-d H:i:s');
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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

        // validate + commit need the uploader's mapping: { "<header>": "<fieldId>" | "__new__" | "__skip__" }
        // plus per-"__new__"-column chosen types: { "<header>": "number" | "date" | ... }
        $mapping = json_decode($request->input('mapping', '{}'), true);
        $newTypes = json_decode($request->input('new_field_types', '{}'), true);
        $plan = $this->buildImportPlan($definition, $parsed['headers'], is_array($mapping) ? $mapping : [], is_array($newTypes) ? $newTypes : [], $parsed['rows']);
        $columns = $plan['columns'];
        $system = $plan['system'] ?? [];
        abort_if(empty($columns), 422, '取り込む列を1つ以上マッピングしてください。');
        $hasNew = collect($columns)->contains(fn ($c) => $c['isNew']);
        if ($hasNew && ! $perms['manage']) {
            abort(403, '新規フィールドを作成するにはアプリの管理権限が必要です。');
        }

        $validation = $this->validateImportRows($parsed['rows'], $columns, $system);

        if ($phase === 'validate') {
            return response()->json($validation + ['sample_errors' => array_slice($validation['invalid'], 0, 50)]);
        }

        $invalidRows = collect($validation['invalid'])->pluck('row')->flip(); // 1-based row => idx
        $result = DB::transaction(function () use ($definition, $parsed, $columns, $system, $invalidRows, $user) {
            // Turn "__new__" columns into real fields, then map every column's header → its field.
            $headerToField = [];
            foreach ($columns as $c) {
                $headerToField[$c['header']] = $c['isNew']
                    ? $this->createImportField($definition, $c['header'], $c['type'], $c['options'])
                    : $c['field'];
            }
            $imported = 0;
            foreach ($parsed['rows'] as $i => $row) {
                if ($invalidRows->has($i + 1)) {
                    continue;
                }
                $this->importOneRow($definition, $headerToField, $row, $user, $system);
                $imported++;
            }

            return ['imported' => $imported, 'skipped' => count($parsed['rows']) - $imported];
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

        $columns = collect($parsed['headers'])->map(function (string $header) use ($byLabel, $parsed) {
            $sys = $this->suggestSystemColumn($header);
            $match = $byLabel->get(mb_strtolower(trim($header)));
            $inferred = $this->inferColumnType($parsed['rows'], $header);

            return [
                'header' => $header,
                'suggested' => $sys ?? ($match ? (string) $match->id : '__skip__'),
                'recommended_type' => $inferred['type'],           // used when the uploader chooses "＋新規"
                'recommended_options' => $inferred['options'],
            ];
        })->values();

        return [
            'headers' => $parsed['headers'],
            'columns' => $columns,
            'sample_rows' => array_slice($parsed['rows'], 0, 5),
            'row_count' => count($parsed['rows']),
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
    private function buildImportPlan(FlowDefinition $definition, array $headers, array $mapping, array $newTypes, array $rows): array
    {
        $fieldsById = $definition->fields->keyBy('id');
        $columns = [];
        $system = [];   // slot ('created_at'|'updated_at') => CSV header
        $usedFieldIds = [];
        $newIdx = 0;

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

            if ($target === '__new__') {
                $type = in_array($newTypes[$header] ?? 'short', self::IMPORT_NEW_TYPES, true) ? $newTypes[$header] : 'short';
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

        return ['columns' => $columns, 'system' => $system];
    }

    /** Dry-run validate each row against the mapped columns (existing fields + typed new columns alike). */
    private function validateImportRows(array $rows, array $columns, array $system = []): array
    {
        $fields = collect($columns)->pluck('field');
        $invalid = [];

        foreach ($rows as $i => $row) {
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

        return ['total' => count($rows), 'valid_count' => count($rows) - count($invalid), 'invalid' => $invalid];
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
        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            $line = array_map(fn ($c) => $this->csvCell($c), $line);
            if ($headers === null) {
                if ($this->isEmptyCsvRow($line)) {
                    continue;
                }
                $headers = $this->normalizeHeaders($line);

                continue;
            }
            if ($this->isEmptyCsvRow($line)) {
                continue;
            }
            $line = array_pad(array_slice($line, 0, count($headers)), count($headers), null);
            $rows[] = array_combine($headers, $line);
            abort_if(count($rows) > 5000, 422, '一度に取り込めるCSVは5000行までです。');
        }
        fclose($handle);
        abort_if(! $headers || count($headers) === 0, 422, 'CSVのヘッダー行が必要です。');

        return ['headers' => $headers, 'rows' => $rows];
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
    private function importOneRow(FlowDefinition $definition, array $headerToField, array $row, $user, array $system = []): void
    {
        $start = $this->flowService->startStatus($definition);
        $record = FlowRecord::create([
            'flow_definition_id' => $definition->id,
            'record_number' => $this->flowService->nextRecordNumber($definition),
            'current_status_id' => $start?->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        foreach ($headerToField as $header => $field) {
            $this->flowService->saveFieldValue($record, $field, $row[$header] ?? null);
        }

        // Imported created_at / updated_at: written last via a raw update so Eloquent's automatic
        // timestamping (which would stamp "now") doesn't overwrite the source values.
        $stamps = [];
        foreach ($system as $slot => $header) {
            $val = trim((string) ($row[$header] ?? ''));
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
