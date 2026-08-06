<?php

namespace App\Services;

use App\Models\FlowAuditLog;
use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\FlowRecordValue;
use App\Models\FlowStatus;
use App\Models\FlowStatusAction;
use App\Models\FlowView;
use App\Models\ProjectRecord;
use App\Models\User;
use App\Support\FlowDynamicDate;
use App\Support\FlowSystemSources;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FlowService
{
    private const ADMIN_USER_IDS = [608, 610];

    /**
     * Field types that hold no record value.
     *
     * 「関連レコード」(related) belongs here even though it shows data: it displays OTHER records —
     * the ones pointing at this one through a ルックアップ field — so this record stores nothing for
     * it. Listing it here is what keeps it out of saves, formulas, CSV export, search, view columns
     * and validation without a single special case in any of them.
     */
    public const LAYOUT_TYPES = ['heading', 'label', 'spacer', 'divider', 'related'];

    public static function isLayoutType(?string $type): bool
    {
        return in_array($type, self::LAYOUT_TYPES, true);
    }

    /**
     * Secret field types: stored encrypted (AccountVault) and deliberately excluded from every
     * value-bearing pipeline — CSV export, search, view columns/filters/sort, formulas, lookup
     * field-copy, PDF tools, record duplicate, and change-history values. readFieldValue() yields
     * a BOOLEAN "has value" for these, never the ciphertext, so anything that forgets to check
     * still cannot leak the credential. Reveal is a separate, audited endpoint.
     */
    public const SECRET_TYPES = ['password'];

    public static function isSecret(?string $type): bool
    {
        return in_array($type, self::SECRET_TYPES, true);
    }

    /* ----------------------------------------------------------------
     | Permissions
     |---------------------------------------------------------------- */

    /* ----------------------------------------------------------------
     | Status helpers
     |---------------------------------------------------------------- */

    public function orderedStatuses(FlowDefinition $definition)
    {
        return ($definition->relationLoaded('statuses') ? $definition->statuses : $definition->statuses()->get())
            ->sortBy([['order_number', 'asc'], ['id', 'asc']])
            ->values();
    }

    public function startStatus(FlowDefinition $definition): ?FlowStatus
    {
        if (! $definition->use_status_flow) {
            return null;
        }
        $statuses = $this->orderedStatuses($definition);

        return $statuses->firstWhere('is_initial', true)
            ?? $statuses->firstWhere('is_locked', 'start')
            ?? $statuses->first();
    }

    /* ----------------------------------------------------------------
     | Status-flow actions (custom buttons) + eligibility
     |---------------------------------------------------------------- */

    /** Action buttons available on the record's current status. */
    public function statusActionsFor(FlowRecord $record): Collection
    {
        $def = $record->definition;
        if (! $def || ! $def->use_status_flow || ! $record->current_status_id) {
            return collect();
        }
        $actions = $def->relationLoaded('statusActions') ? $def->statusActions : $def->statusActions()->get();

        return $actions->where('flow_status_id', $record->current_status_id)->values();
    }

    /** Can this user press this action? (any eligible subject matches, or app-manage as a safety net) */
    public function canPressAction(User $user, FlowRecord $record, FlowStatusAction $action): bool
    {
        return $this->matchesAnySubject($user, $record, $action->eligible);
    }

    /**
     * Shared 押せる人 test for a subject list ([{subject_type, subject_id}]) on a record.
     * Status-flow buttons and カスタムボタン (flow_app_tools tool_type=action) both use it, so the
     * two answer the same question the same way — the builder configures one vocabulary.
     */
    public function matchesAnySubject(User $user, FlowRecord $record, ?array $eligible): bool
    {
        $def = $record->definition;
        foreach (($eligible ?? []) as $subj) {
            $type = $subj['subject_type'] ?? null;
            if ($type && $this->matchesSubject($type, $subj['subject_id'] ?? null, $user, $def, $record)) {
                return true;
            }
        }
        // Nobody configured → open to anyone who can edit the record (matches the builder's
        // 「未設定 = 編集権限を持つ全員が押せます」 hint).
        if (empty($eligible)) {
            return $this->recordPermissions($user, $record, $def)['edit'];
        }

        // Configured but no subject matched → NOT pressable. 管理 deliberately grants no override
        // here: once someone has said who may press a button, that list is the whole answer, or
        // "承認は部長だけ" would silently mean "部長とアプリ管理者だけ". An app manager who needs to
        // unstick a record edits the action's 押せる人 instead — a change that lands in the audit log
        // rather than a silent press. (This also keeps 対応待ち honest: hasExplicitPendingAction has
        // never counted the manage override, so the counter and the button now agree.)
        return false;
    }

    /** True if the user can act on the record's current status (drives 自分待ち). */
    public function hasPendingAction(User $user, FlowRecord $record): bool
    {
        foreach ($this->statusActionsFor($record) as $action) {
            if ($this->canPressAction($user, $record, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True if an action on the record's current status EXPLICITLY names this user
     * (user / position / field / project-role subject). Strict counterpart of
     * hasPendingAction: 'everyone', empty eligible and the manage safety net don't
     * count — a duty needs the user's name on it. Drives the 対応待ち counter.
     *
     * Actions with 通知バッジを表示する turned off are skipped entirely: they remain pressable by the
     * same people, they just stop being anybody's outstanding task.
     */
    public function hasExplicitPendingAction(User $user, FlowRecord $record): bool
    {
        foreach ($this->statusActionsFor($record) as $action) {
            // 通知バッジを表示する = off → this action chases nobody (it stays pressable)
            if (! $action->notify) {
                continue;
            }
            foreach (($action->eligible ?? []) as $subj) {
                $type = $subj['subject_type'] ?? null;
                if ($type && $type !== 'everyone'
                    && $this->matchesSubject($type, $subj['subject_id'] ?? null, $user, $record->definition, $record)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Records in this app awaiting the user's action (strict 対応待ち). Live data — nothing
     * is stored: the count exists exactly as long as the record sits on a status whose
     * actions name the user, and drops the moment they (or anyone) move it on.
     */
    public function pendingActionRecords(User $user, FlowDefinition $definition)
    {
        if (! $definition->use_status_flow || ! $definition->is_active) {
            return collect();
        }
        $definition->loadMissing(['statuses', 'statusActions', 'recordPermissionSets']);
        // only actions with explicit subjects can flag anything — apps without them skip the
        // record scan entirely (this runs on every portal load, once per status-flow app)
        $isExplicit = fn ($s) => ($s['subject_type'] ?? null) && $s['subject_type'] !== 'everyone';
        $actions = $definition->statusActions->filter(
            fn ($a) => $a->notify && collect($a->eligible ?? [])->contains($isExplicit)
        );
        $statusIds = $actions->pluck('flow_status_id')->unique()->values();
        if ($statusIds->isEmpty()) {
            return collect();
        }
        // 'field' subjects resolve against record values — eager-load them only when needed
        // (otherwise fieldUserIds lazy-loads per record → N+1)
        $needsValues = $actions->contains(fn ($a) => collect($a->eligible ?? [])
            ->contains(fn ($s) => ($s['subject_type'] ?? null) === 'field'));

        return FlowRecord::where('flow_definition_id', $definition->id)
            ->whereIn('current_status_id', $statusIds)
            ->with($needsValues ? ['currentStatus:id,name', 'values'] : ['currentStatus:id,name'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($rec) => tap($rec)->setRelation('definition', $definition))
            ->filter(fn ($rec) => $this->recordPermissions($user, $rec, $definition)['view']
                && $this->hasExplicitPendingAction($user, $rec))
            ->values();
    }

    /** Users who may VIEW this record (app-level ∩ record-level) — the mentionable set for comments. */
    public function mentionableUsers(FlowRecord $record, FlowDefinition $def)
    {
        return User::query()
            ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id')
            ->where('retire', 0)
            ->where('hide_flag', 0)
            ->orderBy('name')
            ->get()
            ->filter(fn ($u) => $this->recordPermissions($u, $record, $def)['view'])
            ->values();
    }

    /** Log record creation (shows as 「レコードを作成」 in 変更履歴). */
    public function logRecordCreated(FlowRecord $record, User $user): void
    {
        $record->logs()->create(['user_id' => $user->id, 'action' => 'created']);
    }

    /**
     * App-level audit trail (「監査ログ」builder tab) — distinct from the per-record 変更履歴
     * (UpdateLog/$record->logs()) above. Covers record views, CSV exports, settings changes, and
     * file downloads; never record field edits (those stay in the per-record change log).
     */
    public function logAudit(FlowDefinition $definition, ?User $user, string $action, ?FlowRecord $record = null, array $meta = []): void
    {
        FlowAuditLog::create([
            'flow_definition_id' => $definition->id,
            'user_id' => $user?->id,
            'flow_record_id' => $record?->id,
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    /** Execute a button: move to its target status and log the change. */
    public function applyStatusAction(User $user, FlowRecord $record, FlowStatusAction $action): void
    {
        $statuses = $record->definition->relationLoaded('statuses')
            ? $record->definition->statuses : $record->definition->statuses()->get();
        $from = $record->currentStatus?->name;
        $to = $statuses->firstWhere('id', $action->to_status_id)?->name;
        $record->update(['current_status_id' => $action->to_status_id, 'updated_by' => $user->id]);
        $this->logStatusChange($record, $user, 'status', $from, $to, $action->label);
    }

    /* ----------------------------------------------------------------
     | Assignee snapshot (resolved when a record enters a status)
     |---------------------------------------------------------------- */

    /* ----------------------------------------------------------------
     | Transitions
     |---------------------------------------------------------------- */

    private function logStatusChange(FlowRecord $record, User $user, string $action, ?string $old, ?string $new, ?string $note): void
    {
        $record->logs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'field' => 'status',
            'old_value' => $old,
            'new_value' => $new,
            'changes' => ['status' => ['old' => $old, 'new' => $new]],
            'note' => $note,
        ]);
    }

    /* ----------------------------------------------------------------
     | Field permissions
     |---------------------------------------------------------------- */

    /** field_id => rule (edit|read|hide); missing defaults to 'edit'. */
    public function statusFieldRules(FlowStatus $status): array
    {
        $rules = $status->relationLoaded('fieldRules') ? $status->fieldRules : $status->fieldRules()->get();

        return $rules->pluck('rule', 'flow_field_id')->all();
    }

    public function ruleForField(FlowStatus $status, int $fieldId): string
    {
        return $this->statusFieldRules($status)[$fieldId] ?? 'edit';
    }

    public function editableFieldIds(FlowStatus $status, $fields): array
    {
        $rules = $this->statusFieldRules($status);

        return collect($fields)
            ->filter(fn (FlowField $f) => ($rules[$f->id] ?? 'edit') === 'edit')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /* ----------------------------------------------------------------
     | EAV values
     |---------------------------------------------------------------- */

    public function columnForType(string $inputType): string
    {
        return match ($inputType) {
            'number', 'project' => 'value_numeric',
            'date' => 'value_date',
            default => 'value_text',
        };
    }

    /** Persist incoming values keyed by field id; only $allowedFieldIds are written. */
    public function writeValues(FlowRecord $record, array $values, $fields, ?array $allowedFieldIds = null): array
    {
        $changes = [];
        $byId = collect($fields)->keyBy('id');

        foreach ($values as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $field = $byId->get($fieldId);
            if (! $field) {
                continue;
            }
            if ($allowedFieldIds !== null && ! in_array($fieldId, $allowedFieldIds, true)) {
                continue;
            }

            $column = $this->columnForType($field->input_type);
            $payload = ['value_text' => null, 'value_numeric' => null, 'value_date' => null];

            if ($column === 'value_numeric') {
                $payload['value_numeric'] = ($value === '' || $value === null) ? null : (float) preg_replace('/[^\d.\-]/', '', (string) $value);
            } elseif ($column === 'value_date') {
                $payload['value_date'] = $value ?: null;
            } else {
                $payload['value_text'] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value === '' ? null : $value);
            }

            $existing = FlowRecordValue::query()
                ->where('flow_record_id', $record->id)
                ->where('flow_field_id', $fieldId)
                ->first();

            $oldValue = $existing ? ($existing->{$column}) : null;
            $newValue = $payload[$column];

            FlowRecordValue::updateOrCreate(
                ['flow_record_id' => $record->id, 'flow_field_id' => $fieldId],
                $payload
            );

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field->key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return $changes;
    }

    /** record's values as field_id => scalar (column chosen by field type). */
    public function readValues(FlowRecord $record, $fields): array
    {
        $byId = collect($fields)->keyBy('id');
        $values = $record->relationLoaded('values') ? $record->values : $record->values()->get();
        $out = [];

        foreach ($values as $v) {
            $field = $byId->get($v->flow_field_id);
            if (! $field) {
                continue;
            }
            $out[$v->flow_field_id] = $v->{$this->columnForType($field->input_type)};
        }

        return $out;
    }

    public function logValueChanges(FlowRecord $record, User $user, array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        $record->logs()->create([
            'user_id' => $user->id,
            'action' => 'values_updated',
            'field' => null,
            'changes' => $changes,
        ]);
    }

    /* ----------------------------------------------------------------
     | Dashboard — records awaiting this user's action
     |---------------------------------------------------------------- */

    /* ----------------------------------------------------------------
     | Record creation (enters the start status)
     |---------------------------------------------------------------- */

    /**
     * Allocate the next per-app record number. The counter on flow_definitions only ever
     * increases (never reused after deletes → kintone semantics); the row lock serializes
     * concurrent creates so two records can't share a number.
     */
    public function nextRecordNumber(FlowDefinition $definition): int
    {
        return DB::transaction(function () use ($definition) {
            // SELECT ... FOR UPDATE takes the row lock; a raw column update avoids bumping the app's updated_at.
            $seq = (int) (FlowDefinition::whereKey($definition->id)->lockForUpdate()->value('record_seq') ?? 0);
            $next = $seq + 1;
            DB::table('flow_definitions')->where('id', $definition->id)->update(['record_seq' => $next]);

            return $next;
        });
    }

    /* ----------------------------------------------------------------
     | Server-side list query — filter / search over the typed EAV, for
     | offset pagination. (Mirrors utils/flowView.ts, but in SQL.)
     |---------------------------------------------------------------- */

    /**
     * Resolve a view's column list (or "all" when the view has none) into ordered descriptors —
     * mirrors resolveColumns()/allColumnRefs() in utils/flowView.ts, used for CSV export since that
     * has no client-rendered table to read columns from.
     */
    public function resolveExportColumns(FlowDefinition $definition, ?FlowView $view, bool $hasStatus): array
    {
        // secrets are never exported — a CSV is the easiest way for a credential to walk out
        $fields = ($definition->relationLoaded('fields') ? $definition->fields : $definition->fields()->get())
            ->filter(fn ($f) => ! self::isLayoutType($f->input_type) && ! self::isSecret($f->input_type))
            ->values();
        $fieldsById = $fields->keyBy('id');

        $refs = is_array($view?->columns) && $view->columns
            ? $view->columns
            : $this->allExportColumnRefs($fields, $hasStatus);

        $sysLabels = [
            '$record_number' => 'ID',
            '$status' => 'ステータス',
            '$created_at' => '作成日時',
            '$updated_at' => '更新日時',
        ];

        $out = [];
        foreach ($refs as $ref) {
            if (is_string($ref) && str_starts_with($ref, '$')) {
                if ($ref === '$status' && ! $hasStatus) {
                    continue;
                }
                if (isset($sysLabels[$ref])) {
                    $out[] = ['ref' => $ref, 'system' => true, 'label' => $sysLabels[$ref], 'field' => null];
                }

                continue;
            }
            $field = $fieldsById->get((int) $ref);
            if ($field) {
                $out[] = ['ref' => $field->id, 'system' => false, 'label' => $field->label, 'field' => $field];
            }
        }

        return $out;
    }

    /** Default (null-columns) view = ID (+ ステータス) + all non-layout fields + 作成日時 + 更新日時. */
    private function allExportColumnRefs($fields, bool $hasStatus): array
    {
        return [
            '$record_number',
            ...($hasStatus ? ['$status'] : []),
            ...$fields->pluck('id')->all(),
            '$created_at',
            '$updated_at',
        ];
    }

    private function valueColumnFor(?string $type): string
    {
        return match ($type) {
            'number', 'project' => 'value_numeric',
            'date' => 'value_date',
            'datetime' => 'value_datetime',
            'toggle' => 'value_boolean',
            'checkbox', 'user', 'member', 'file', 'table', 'reference' => 'value_json',
            default => 'value_text',
        };
    }

    /**
     * Base query: definition scope + view filters + ad-hoc filter + keyword search (no ordering).
     * $filterLogic: how the view's own conditions combine ('and'|'or').
     * $adhocFilter (optional): ['logic' => 'and'|'or', 'conditions' => [same shape as $filters]] —
     * the search bar's quick filter, applied as one extra AND-ed group alongside everything else.
     * So an OR view filtered by an OR ad-hoc reads as (a OR b) AND (c OR d), and each group stays
     * self-contained rather than the two OR-ing into each other.
     */
    public function recordListQuery(FlowDefinition $definition, array $filters, string $search, ?array $adhocFilter = null, string $filterLogic = 'and')
    {
        $fieldsById = ($definition->relationLoaded('fields') ? $definition->fields : $definition->fields()->get())->keyBy('id');
        // Qualify the column — a sort on the $status column left-joins flow_statuses,
        // which also has a flow_definition_id (otherwise "ambiguous column" in SQL).
        $q = FlowRecord::where('flow_records.flow_definition_id', $definition->id);

        $this->applyConditionGroup($q, $filters, $filterLogic, $fieldsById);
        if ($adhocFilter) {
            $this->applyConditionGroup($q, $adhocFilter['conditions'] ?? [], $adhocFilter['logic'] ?? 'and', $fieldsById);
        }

        $kw = trim($search);
        if ($kw !== '') {
            $like = '%'.mb_strtolower($kw).'%';
            // secrets hold ciphertext — matching it is meaningless and would only ever be a
            // (very slow) way to confirm someone guessed a stored value; skip those rows
            $secretIds = $fieldsById->filter(fn ($f) => self::isSecret($f->input_type))->keys()->all();
            $q->where(function ($w) use ($like, $secretIds) {
                $w->whereRaw('CAST(record_number AS CHAR) LIKE ?', [$like])
                    ->orWhereHas('values', function ($vq) use ($like, $secretIds) {
                        if ($secretIds) {
                            $vq->whereNotIn('flow_field_id', $secretIds);
                        }
                        $vq->where(function ($m) use ($like) {
                            $m->whereRaw('LOWER(value_text) LIKE ?', [$like])
                                ->orWhereRaw('CAST(value_numeric AS CHAR) LIKE ?', [$like]);
                        });
                    });
            });
        }

        return $q;
    }

    /** Apply an ordered list of {field,direction} sorts to a record query. */
    public function applyRecordSort($q, array $sort, FlowDefinition $definition): void
    {
        $fieldsById = ($definition->relationLoaded('fields') ? $definition->fields : $definition->fields()->get())->keyBy('id');
        $applied = false;
        foreach (array_values($sort) as $i => $s) {
            $ref = $s['field'] ?? null;
            $dir = (($s['direction'] ?? 'asc') === 'desc') ? 'desc' : 'asc';
            if (in_array($ref, ['$record_number', '$created_at', '$updated_at'], true)) {
                $col = $ref === '$record_number' ? 'record_number' : ($ref === '$created_at' ? 'created_at' : 'updated_at');
                $q->orderBy($col, $dir);
                $applied = true;

                continue;
            }
            if ($ref === '$status') {
                // Sort by the status's own order in the flow (logical progression), not name.
                $q->leftJoin('flow_statuses as sort_status', 'sort_status.id', '=', 'flow_records.current_status_id')
                    ->orderBy('sort_status.order_number', $dir)
                    ->select('flow_records.*');
                $applied = true;

                continue;
            }
            $field = $fieldsById->get((int) $ref);
            if (! $field) {
                continue;
            }
            $alias = 'sort_'.$i;
            $col = $this->valueColumnFor($field->input_type);
            $q->leftJoin("flow_record_values as {$alias}", function ($j) use ($alias, $field) {
                $j->on("{$alias}.flow_record_id", '=', 'flow_records.id')
                    ->where("{$alias}.flow_field_id", '=', (int) $field->id);
            })->orderBy("{$alias}.{$col}", $dir);
            $q->select('flow_records.*');
            $applied = true;
        }
        if (! $applied) {
            $q->orderByDesc('created_at')->orderByDesc('id');
        }
    }

    /**
     * One list of conditions combined by a single AND/OR, wrapped in its own grouped clause.
     *
     * The wrapping is what makes OR safe: without it an orWhere would escape the group and OR
     * itself against the definition scope and keyword search, matching records from other apps.
     * Used for both a view's saved filters and the search bar's ad-hoc filter, so the two read
     * identically.
     */
    private function applyConditionGroup($q, $conditions, string $logic, $fieldsById): void
    {
        $conditions = is_array($conditions) ? array_values($conditions) : [];
        if (! $conditions) {
            return;
        }
        $isOr = $logic === 'or';
        $q->where(function ($outer) use ($conditions, $fieldsById, $isOr) {
            foreach ($conditions as $i => $f) {
                $method = ($isOr && $i > 0) ? 'orWhere' : 'where';
                $outer->$method(function ($sub) use ($f, $fieldsById) {
                    $this->applyFilterToQuery($sub, $f, $fieldsById);
                });
            }
        });
    }

    private function applyFilterToQuery($q, array $f, $fieldsById): void
    {
        $ref = $f['field'] ?? null;
        $op = $f['operator'] ?? 'equals';
        $vals = $f['values'] ?? [];
        $first = $vals[0] ?? null;

        if (in_array($ref, ['$record_number', '$created_at', '$updated_at'], true)) {
            $col = $ref === '$record_number' ? 'record_number' : ($ref === '$created_at' ? 'created_at' : 'updated_at');
            if ($col !== 'record_number' && FlowDynamicDate::isDynamic($first)) {
                $this->applyDynamicDateOp($q, $col, $op, FlowDynamicDate::resolve($first, true));

                return;
            }
            $this->applyScalarOp($q, $col, $op, $first);

            return;
        }

        if ($ref === '$status') {
            if ($op === 'is_empty') {
                $q->whereNull('current_status_id');
            } elseif ($op === 'not_empty') {
                $q->whereNotNull('current_status_id');
            } else {
                $q->whereHas('currentStatus', fn ($s) => $this->applyScalarOp($s, 'name', $op, $first));
            }

            return;
        }

        $field = $fieldsById->get((int) $ref);
        if (! $field) {
            return;
        }
        $col = $this->valueColumnFor($field->input_type);
        $fid = (int) $field->id;

        if ($op === 'is_empty' || $op === 'not_empty') {
            // 「値がある」の判定。JSON列は空でも `[]` が入っていて NULL でも空文字でもないので、
            // 長さを見ないと未選択のチェックボックスが「値あり」に化ける
            // （チェックが付いていないレコードを出す一覧が0件になる）。
            $hasValue = fn ($v) => $v->where('flow_field_id', $fid)
                ->whereNotNull($col)
                ->where($col, '!=', '')
                ->when($col === 'value_json', fn ($x) => $x->whereRaw("JSON_LENGTH({$col}) > 0"));

            $op === 'is_empty' ? $q->whereDoesntHave('values', $hasValue) : $q->whereHas('values', $hasValue);
        } elseif ($op === 'includes_any') {
            // includes_any spans two storage shapes: a JSON array (checkbox option labels, user/member
            // ids) and a plain scalar (select/radio in value_text). For the JSON case, match on
            // JSON_CONTAINS rather than a LIKE for '"val"' — ids are stored as JSON *numbers*
            // (`[604]`, no quotes), so the quoted LIKE matched option labels but silently missed
            // every user/member id, i.e. filtering by user never returned anything.
            $isJson = $col === 'value_json';
            $q->whereHas('values', function ($v) use ($fid, $col, $vals, $isJson) {
                $v->where('flow_field_id', $fid)->where(function ($w) use ($col, $vals, $isJson) {
                    foreach ($vals as $val) {
                        if (! $isJson) {
                            $w->orWhere($col, $val);

                            continue;
                        }
                        $w->orWhereRaw("JSON_CONTAINS({$col}, ?)", [json_encode((string) $val)]);
                        if (is_numeric($val)) {
                            $w->orWhereRaw("JSON_CONTAINS({$col}, ?)", [json_encode((int) $val)]);
                        }
                    }
                });
            });
        } elseif (in_array($field->input_type, ['date', 'datetime'], true) && FlowDynamicDate::isDynamic($first)) {
            $range = FlowDynamicDate::resolve($first, $field->input_type === 'datetime');
            $q->whereHas('values', fn ($v) => $this->applyDynamicDateOp($v->where('flow_field_id', $fid), $col, $op, $range));
        } else {
            $q->whereHas('values', fn ($v) => $this->applyScalarOp($v->where('flow_field_id', $fid), $col, $op, $first));
        }
    }

    /**
     * Comparison against a resolved dynamic-date [start, end] range.
     *
     * A period is a span, so the operators read against its edges: "以上 今月" means from the 1st
     * onwards, while "より大きい 今月" means strictly after the month ends. equals = falls anywhere
     * inside it, which is what 「今月」 means in a filter.
     */
    private function applyDynamicDateOp($q, string $col, string $op, ?array $range)
    {
        if (! $range) {
            return $q;
        }
        [$start, $end] = $range;

        return match ($op) {
            'not_equals' => $q->where(fn ($w) => $w->whereNotBetween($col, [$start, $end])->orWhereNull($col)),
            'gt' => $q->where($col, '>', $end),
            'gte' => $q->where($col, '>=', $start),
            'lt' => $q->where($col, '<', $start),
            'lte' => $q->where($col, '<=', $end),
            default => $q->whereBetween($col, [$start, $end]),
        };
    }

    private function applyScalarOp($q, string $col, string $op, $first)
    {
        return match ($op) {
            'not_equals' => $q->where(fn ($w) => $w->where($col, '!=', $first)->orWhereNull($col)),
            'contains' => $q->where($col, 'like', '%'.$first.'%'),
            'not_contains' => $q->where(fn ($w) => $w->where($col, 'not like', '%'.$first.'%')->orWhereNull($col)),
            'gt' => $q->where($col, '>', $first),
            'gte' => $q->where($col, '>=', $first),
            'lt' => $q->where($col, '<', $first),
            'lte' => $q->where($col, '<=', $first),
            default => $q->where($col, $first),
        };
    }

    /* ----------------------------------------------------------------
     | App-runtime value read/write — typed EAV across all field types
     |---------------------------------------------------------------- */

    public function saveFieldValue(FlowRecord $record, FlowField $field, mixed $raw): void
    {
        if ($field->input_type === 'formula' || self::isLayoutType($field->input_type)) {
            return;
        }

        $value = FlowRecordValue::firstOrNew([
            'flow_record_id' => $record->id,
            'flow_field_id' => $field->id,
        ]);
        // Capture the previous value before we null it, so removed files can be deleted. Tables need
        // it too: a file column inside a subtable is a file field in every way that matters.
        $oldFiles = in_array($field->input_type, ['file', 'table'], true) ? ($value->value_json ?? []) : [];
        $value->fill([
            'value_text' => null, 'value_numeric' => null, 'value_date' => null,
            'value_datetime' => null, 'value_boolean' => null, 'value_json' => null,
        ]);

        switch ($field->input_type) {
            case 'password':
                // ['clear' => true] wipes it; a blank submit KEEPS the stored secret (returning
                // early leaves the row untouched — the nulls filled above are never persisted),
                // so editing a record doesn't require reading the credential first.
                if (is_array($raw) && ! empty($raw['clear'])) {
                    break;
                }
                // a bare boolean is the "is one stored?" marker we handed out on read — echoing it
                // back means "unchanged", never a new value (otherwise true would be saved as "1")
                if (is_bool($raw)) {
                    return;
                }
                $plain = is_scalar($raw) ? trim((string) $raw) : '';
                if ($plain === '') {
                    return;
                }
                $value->value_text = app(AccountVault::class)->encrypt($plain);
                break;
            case 'number':
                $value->value_numeric = $this->numberValue($raw);
                break;
            case 'date':
                $value->value_date = $this->dateValue($raw);
                break;
            case 'datetime':
                $value->value_datetime = $this->dateTimeValue($raw);
                break;
            case 'toggle':
                $value->value_boolean = $this->booleanValue($raw);
                break;
            case 'checkbox':
                $value->value_json = $this->arrayValue($raw);
                break;
            case 'file':
                $value->value_json = $this->files()->syncFieldFiles($record, $field, $this->arrayValue($raw), $oldFiles);
                break;
            case 'user':
            case 'member':
                $ids = $this->userIdArrayValue($raw);
                $value->value_json = $ids;
                $value->value_numeric = count($ids) === 1 ? $ids[0] : null;
                break;
            case 'table':
                // File columns inside a subtable go through the same attach path as a top-level file
                // field. Before this they went through none of it: the upload stayed in temp_upload/
                // and RemoveFile('temp') deleted it after 7 days.
                $value->value_json = $this->files()->syncTableFiles(
                    $record, $field, $this->tableValue($raw, $field), $oldFiles
                );
                break;
            case 'reference':
                // value_numeric に相手のレコードIDが入るのが、関連レコード（裏引き）の索引になる
                $ref = $this->referenceValue($raw, $field);
                $value->value_json = $ref;
                $value->value_numeric = $ref['id'] ?? null;
                break;
            case 'project':
                // single project id (multiple not supported)
                $id = is_array($raw) ? ($raw['id'] ?? null) : $raw;
                $value->value_numeric = ($id === null || $id === '') ? null : (int) $id;
                break;
            default:
                $value->value_text = $this->stringValue($raw);
                break;
        }

        $value->save();
    }

    /**
     * File handling lives in its own service (ledger table + sharded storage + permissions).
     * Resolved lazily so FlowService keeps its no-argument constructor — it is built by hand in
     * a few places and in tests.
     */
    private function files(): FlowFileService
    {
        return app(FlowFileService::class);
    }

    /** Same url/status decoration as a top-level file field, for file columns inside a subtable. */
    private function decorateTableFiles(FlowField $field, array $rows): array
    {
        $columns = $this->files()->fileColumnKeys($field);
        if ($columns === []) {
            return $rows;
        }

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($columns as $key) {
                if (is_array($row[$key] ?? null)) {
                    $rows[$i][$key] = $this->files()->decorate($row[$key]);
                }
            }
        }

        return $rows;
    }

    public function readFieldValue(?FlowRecordValue $value, FlowField $field): mixed
    {
        $multi = in_array($field->input_type, ['checkbox', 'file', 'user', 'member', 'table'], true);
        if (! $value) {
            // secrets read as "is one stored?" — never the value, not even when absent
            return self::isSecret($field->input_type) ? false : ($multi ? [] : null);
        }

        return match ($field->input_type) {
            // NEVER return the ciphertext: every consumer (lists, detail payloads, history diffs)
            // gets a boolean. Plaintext is only ever produced by the audited reveal endpoint.
            'password' => $value->value_text !== null && $value->value_text !== '',
            'number' => $value->value_numeric === null ? null : (float) $value->value_numeric,
            'date' => $value->value_date?->toDateString(),
            'datetime' => $value->value_datetime?->format('Y-m-d\TH:i'),
            'toggle' => (bool) $value->value_boolean,
            // files carry no url in storage — it is built here, so changing the storage layout
            // never means rewriting record data
            'file' => $this->files()->decorate($value->value_json ?: []),
            'table' => $this->decorateTableFiles($field, $value->value_json ?: []),
            'checkbox', 'user', 'member' => $value->value_json ?: [],
            'reference' => $value->value_json ?: null,
            'project' => $value->value_numeric === null ? null : (int) $value->value_numeric,
            default => $value->value_text,
        };
    }

    /** field_id (string) => value; formula fields computed last. */
    public function recordValues(FlowRecord $record, $fields): array
    {
        $stored = ($record->relationLoaded('values') ? $record->values : $record->values()->get())
            ->keyBy('flow_field_id');
        $out = [];

        foreach ($fields as $field) {
            if ($field->input_type === 'formula' || self::isLayoutType($field->input_type)) {
                continue;
            }
            $out[(string) $field->id] = $this->readFieldValue($stored->get($field->id), $field);
        }

        // Formulas span two levels that reference each other: per-row table calc columns
        // (e.g. 金額 = 数量 * 単価) and top-level fields that aggregate them (e.g. 合計 = SUM([表.金額])),
        // which in turn feed further top-level formulas (e.g. 比率 = 合計 / 売上 * 100). Compute the
        // table columns first so aggregates can read them, then iterate the top-level formulas to a
        // fixpoint. Acyclic dependencies converge within (#formulas + 1) passes; we stop early once
        // nothing changes. computeTableCalcColumns re-runs each pass so a column that references a
        // top-level formula also settles.
        $formulaFields = collect($fields)->where('input_type', 'formula')->values();
        $evaluator = app(FlowFormulaEvaluator::class);
        $passes = max(1, $formulaFields->count() + 1);
        for ($p = 0; $p < $passes; $p++) {
            $this->computeTableCalcColumns($fields, $out);
            $changed = false;
            foreach ($formulaFields as $field) {
                $context = $this->formulaContext($fields, $out);
                $result = $this->castFormulaResult($evaluator->evaluate($field->formula ?? '', $context), $field->result_type ?? 'number');
                if (($out[(string) $field->id] ?? null) !== $result) {
                    $changed = true;
                }
                $out[(string) $field->id] = $result;
            }
            if (! $changed) {
                break;
            }
        }

        return $out;
    }

    /** Fill in formula columns of each table field's rows (compute-on-read, like top-level formulas). */
    private function computeTableCalcColumns($fields, array &$out): void
    {
        $topContext = $this->formulaContext($fields, $out);
        $evaluator = app(FlowFormulaEvaluator::class);

        foreach ($fields as $field) {
            if ($field->input_type !== 'table') {
                continue;
            }
            $validation = is_array($field->validation) ? $field->validation : [];
            $columns = is_array($validation['columns'] ?? null) ? $validation['columns'] : [];
            $calcCols = array_filter($columns, fn ($c) => ($c['input_type'] ?? '') === 'formula');
            if (empty($calcCols)) {
                continue;
            }
            $rows = $out[(string) $field->id] ?? [];
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $ri => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rowContext = $topContext;
                foreach ($columns as $c) {
                    $ck = $c['key'] ?? null;
                    if (! $ck) {
                        continue;
                    }
                    // through formulaDisplayValue for the same reason top-level fields are: a プロジェクト
                    // or ユーザー column stores an id, so a calc column reading it printed "173" instead
                    // of the project's name.
                    $cell = $this->formulaDisplayValue($c['input_type'] ?? null, $row[$ck] ?? null);
                    $rowContext[$ck] = $cell;
                    if (! empty($c['label'])) {
                        $rowContext[$c['label']] = $cell;
                    }
                }
                // Multi-pass so a calc column can reference another calc column in the same row.
                // Acyclic chains converge within (#calc columns) passes; stops early once stable.
                $passes = max(1, count($calcCols));
                for ($p = 0; $p < $passes; $p++) {
                    $changed = false;
                    foreach ($calcCols as $c) {
                        $ck = $c['key'] ?? null;
                        if (! $ck) {
                            continue;
                        }
                        $val = $this->castFormulaResult($evaluator->evaluate($c['formula'] ?? '', $rowContext), $c['result_type'] ?? 'number');
                        if (($rowContext[$ck] ?? null) !== $val) {
                            $changed = true;
                        }
                        $rowContext[$ck] = $val;
                        if (! empty($c['label'])) {
                            $rowContext[$c['label']] = $val;
                        }
                        $row[$ck] = $val;
                    }
                    if (! $changed) {
                        break;
                    }
                }
                $rows[$ri] = $row;
            }
            $out[(string) $field->id] = $rows;
        }
    }

    public function formulaContext($fields, array $values): array
    {
        $context = [];
        foreach ($fields as $field) {
            // Layout parts (見出し・ラベル等) carry display text, never values — and the builder
            // allows them to share a data field's label, which would clobber that field's
            // context entry with null. They can't be referenced, so skip them entirely.
            if (self::isLayoutType($field->input_type)) {
                continue;
            }
            $v = $this->formulaDisplayValue($field->input_type, $values[(string) $field->id] ?? null);
            $context[(string) $field->id] = $v;
            $context[$field->key] = $v;
            $context[$field->label] = $v;

            // 列参照 [テーブル.金額] を解決できるようにする。行の中に別名を足すのではなく、平坦な
            // エントリとして持たせる：行に足すと1セルが2回数えられ、表全体の SUM が倍になる
            // （実測で 25,600 が 51,200 になった）。
            if ($field->input_type === 'table') {
                $this->addTableColumnRefs($context, $field, $v);
            }
        }

        return $context;
    }

    /**
     * `テーブル.列` で1列だけ取り出せるように、平坦な参照を context に足す。
     *
     * 評価側は識別子をまず context のキーとして引くので、"テーブル.金額" というキーがあればそれで
     * 解決する。行の中に別名を入れる方法もあるが、それだと1セルが列キーと列ラベルの2回数えられ、
     * 表全体の SUM が倍になる。
     *
     * キー・ラベルの4通りを登録するのは、既存の式が列キー（c1 等）で書かれている一方、これから
     * ピッカーが挿入するのはラベル形式だから。既に埋まっているキーは上書きしない。
     */
    private function addTableColumnRefs(array &$context, $field, $rows): void
    {
        $columns = is_array($field->validation['columns'] ?? null) ? $field->validation['columns'] : [];
        if (! is_array($rows) || ! $columns) {
            return;
        }

        foreach ($columns as $c) {
            $key = $c['key'] ?? null;
            if ($key === null) {
                continue;
            }
            $label = ($c['label'] ?? '') !== '' ? $c['label'] : $key;
            $values = array_map(fn ($r) => is_array($r) ? ($r[$key] ?? null) : null, $rows);

            foreach ([$field->key, $field->label] as $tableRef) {
                if ($tableRef === null || $tableRef === '') {
                    continue;
                }
                foreach ([$key, $label] as $colRef) {
                    $ref = $tableRef.'.'.$colRef;
                    if (! array_key_exists($ref, $context)) {
                        $context[$ref] = $values;
                    }
                }
            }
        }
    }

    /**
     * What a formula should see for a field whose stored value is a key rather than something a person
     * would read.
     *
     * ユーザー and プロジェクト store ids, 参照 stores a {id, number, label} snapshot and ファイル a list of
     * file objects. Feeding those to a formula verbatim produced "487" for a person, the project's id
     * for a project, and "1159, 東京工業株式会社, 2" for a reference — castFormulaResult() flattens an
     * array by imploding it, so even the label came out buried in punctuation.
     *
     * Takes the input type rather than a field: テーブル columns are plain arrays out of the parent
     * field's validation JSON, never field objects, and they need this same treatment — a calc column
     * reading a プロジェクト column beside it was printing the raw id.
     *
     * Arrays stay arrays (castFormulaResult joins them) so a multi-user field still reads as a list.
     *
     * Trade-off worth knowing: a formula comparing a user field against a numeric id — [担当] == 487 —
     * compares against the name now. Nobody can read an id off the screen to write such a formula in
     * the first place, and the same expression against a name is the one people can actually author.
     */
    private function formulaDisplayValue(?string $inputType, mixed $v): mixed
    {
        switch ($inputType) {
            case 'user':
            case 'member':
                $names = $this->userNameMap();

                return array_values(array_map(
                    fn ($id) => $names[(int) $id] ?? '#'.$id,
                    array_filter($this->arrayValue($v), fn ($x) => is_numeric($x))
                ));
            case 'project':
                if ($v === null || $v === '' || ! is_numeric($v)) {
                    return $v;
                }

                return $this->projectNameMap()[(int) $v] ?? '#'.$v;
            case 'reference':
                // the picked record's label rides along in the stored snapshot — no lookup needed
                return is_array($v) ? ($v['label'] ?? ($v['number'] ?? null)) : $v;
            case 'file':
                return array_values(array_map(
                    fn ($f) => is_array($f) ? (string) ($f['name'] ?? '') : (string) $f,
                    $this->arrayValue($v)
                ));
            default:
                return $v;
        }
    }

    /**
     * id => name, loaded at most once per request and only if a formula context actually needs it.
     * Retired people are included on purpose: a record can legitimately still name one, and dropping
     * them would silently turn an old record's formula into "#487".
     */
    private ?array $userNames = null;

    private ?array $projectNames = null;

    private function userNameMap(): array
    {
        return $this->userNames ??= User::query()->pluck('name', 'id')->map(fn ($n) => (string) $n)->all();
    }

    private function projectNameMap(): array
    {
        return $this->projectNames ??= ProjectRecord::query()->pluck('name', 'id')->map(fn ($n) => (string) $n)->all();
    }

    public function castFormulaResult(mixed $value, string $type): mixed
    {
        return match ($type) {
            'text' => $value === null ? '' : (is_array($value) ? implode(', ', $value) : (string) $value),
            'toggle', 'boolean', 'checkbox' => (bool) $value,
            default => is_numeric($value) ? (float) $value : 0,
        };
    }

    public function syncFieldValues(FlowRecord $record, $fields, array $values, ?array $allowedFieldIds = null): array
    {
        $byId = collect($fields)->keyBy('id');
        $changes = [];

        foreach ($values as $fieldId => $raw) {
            $field = $byId->get((int) $fieldId);
            if (! $field || $field->input_type === 'formula' || self::isLayoutType($field->input_type)) {
                continue;
            }
            if ($allowedFieldIds !== null && ! in_array((int) $fieldId, $allowedFieldIds, true)) {
                continue;
            }
            $this->saveFieldValue($record, $field, $raw);
            // never let a secret's plaintext into the change log — record only that it was set
            $changes[$field->key] = self::isSecret($field->input_type)
                ? ['new' => true]
                : ['new' => is_array($raw) ? $raw : (string) $raw];
        }

        return $changes;
    }

    /**
     * Normalize a table field's raw value into a clean list of row objects.
     * Each row keeps only cells for defined columns, coerced by the column's own type.
     */
    private function tableValue($raw, FlowField $field): array
    {
        $validation = is_array($field->validation) ? $field->validation : [];
        $columns = is_array($validation['columns'] ?? null) ? $validation['columns'] : [];
        $rows = is_array($raw) ? $raw : [];

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $clean = [];
            foreach ($columns as $col) {
                $key = $col['key'] ?? null;
                if (! $key) {
                    continue;
                }
                // formula columns are derived on read, never persisted from input
                if (($col['input_type'] ?? null) === 'formula') {
                    continue;
                }
                $clean[$key] = $this->coerceCellValue($col['input_type'] ?? 'short', $row[$key] ?? null);
            }
            $out[] = $clean;
        }

        return $out;
    }

    /** Reference field: snapshot the picked target record as {id, number, label}. */
    /**
     * ルックアップの保存値。番号とラベルは**サーバーで引き直す**。
     *
     * 以前は画面が送ってきた number/label をそのまま入れていたので、IDだけを送るクライアント
     * （関連レコードの「＋追加」など）では `#undefined` のまま保存され、古いラベルや偽のラベルも
     * そのまま残った。表示名の出しかた（label_field）はサーバーが知っているので、ここで作る。
     */
    private function referenceValue($raw, ?FlowField $field = null): ?array
    {
        if (! is_array($raw) || empty($raw['id'])) {
            return null;
        }
        $id = (int) $raw['id'];

        $targetId = (int) ($field->validation['target_definition_id'] ?? 0);
        if ($targetId > 0) {
            $target = FlowDefinition::with('fields')->find($targetId);
            $record = $target ? FlowRecord::where('flow_definition_id', $targetId)->find($id) : null;
            if ($record) {
                return [
                    'id' => $id,
                    'number' => (int) $record->record_number,
                    'label' => $this->referenceLabel($record, $target, $field->validation['label_field'] ?? null),
                ];
            }
        }

        // 参照先が引けない（システムソース参照など）ときは、送られてきた値をそのまま使う
        return [
            'id' => $id,
            'number' => isset($raw['number']) ? (int) $raw['number'] : null,
            'label' => isset($raw['label']) ? (string) $raw['label'] : '',
        ];
    }

    /** ルックアップの表示名。label_field が無い／空なら「#レコード番号」。 */
    private function referenceLabel(FlowRecord $record, FlowDefinition $target, ?string $labelFieldKey): string
    {
        $labelField = filled($labelFieldKey) ? $target->fields->firstWhere('key', $labelFieldKey) : null;
        if ($labelField && ! self::isSecret($labelField->input_type)) {
            $vals = $this->recordValues($record, $target->fields);
            $raw = $vals[(string) $labelField->id] ?? null;
            $label = is_array($raw)
                ? implode(' / ', array_map(fn ($x) => is_scalar($x) ? (string) $x : '', $raw))
                : (is_scalar($raw) ? (string) $raw : '');
            if (trim($label) !== '') {
                return $label;
            }
        }

        return '#'.$record->record_number;
    }

    /** Coerce a single table cell for JSON storage, matching the column's input type. */
    private function coerceCellValue(?string $type, $raw)
    {
        return match ($type) {
            'number' => $this->numberValue($raw),
            'toggle' => $this->booleanValue($raw),
            'checkbox', 'file' => $this->arrayValue($raw),
            'user', 'member' => $this->userIdArrayValue($raw),
            // テーブル内のルックアップ列は列定義（配列）しか無いため、番号とラベルは画面の値を使う。
            // トップレベルのルックアップだけがサーバーで引き直される。
            'reference' => $this->referenceValue($raw),
            default => ($raw === null || $raw === '') ? null : (is_scalar($raw) ? (string) $raw : $raw),
        };
    }

    private function numberValue($raw): ?float
    {
        if ($raw === '' || $raw === null) {
            return null;
        }
        $n = preg_replace('/[^\d.\-]/', '', (string) $raw);

        return $n === '' ? null : (float) $n;
    }

    private function dateValue($raw): ?string
    {
        if (! $raw) {
            return null;
        }
        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dateTimeValue($raw): ?string
    {
        if (! $raw) {
            return null;
        }
        try {
            return Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function booleanValue($raw): bool
    {
        return in_array($raw, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    private function arrayValue($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter($raw, fn ($x) => $x !== null && $x !== ''));
        }
        if ($raw === null || $raw === '') {
            return [];
        }

        return [$raw];
    }

    private function userIdArrayValue($raw): array
    {
        return array_values(array_unique(array_map(
            'intval',
            array_filter($this->arrayValue($raw), fn ($x) => is_numeric($x))
        )));
    }

    public function stringValue($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return is_array($raw) ? json_encode($raw, JSON_UNESCAPED_UNICODE) : (string) $raw;
    }

    /* ================================================================
     | Server-side value validation (mirrors utils/flowValidation.ts)
     |================================================================ */

    /** Returns field_id(string) => error message for invalid values. */
    public function validateValues($fields, array $values, ?FlowRecord $record = null): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if ($field->input_type === 'formula' || self::isLayoutType($field->input_type)) {
                continue;
            }
            $value = $values[$field->id] ?? ($values[(string) $field->id] ?? null);
            // a blank secret means "keep what's stored", so 必須 is about the value that will
            // EXIST after the save — not about what was submitted
            if (self::isSecret($field->input_type)) {
                $clearing = is_array($value) && ! empty($value['clear']);
                $incoming = is_scalar($value) ? trim((string) $value) : '';
                $stored = $record !== null && $this->readFieldValue(
                    ($record->relationLoaded('values') ? $record->values : $record->values()->get())
                        ->firstWhere('flow_field_id', $field->id),
                    $field
                ) === true;
                if ($field->is_required && ($clearing || ($incoming === '' && ! $stored))) {
                    $errors[(string) $field->id] = '必須項目です。';
                }

                continue;
            }
            $error = $this->validateOne($field, $value);
            if ($error) {
                $errors[(string) $field->id] = $error;
            }
        }

        return $errors;
    }

    private function validateOne(FlowField $field, $value): ?string
    {
        $rules = is_array($field->validation) ? $field->validation : [];
        $empty = $value === null || $value === '' || (is_array($value) && count($value) === 0);

        if ($field->is_required && $empty) {
            return '必須項目です。';
        }
        if ($empty) {
            return null;
        }

        switch ($field->input_type) {
            case 'short':
            case 'long':
                $len = mb_strlen((string) $value);
                if (($rules['min_length'] ?? null) !== null && $len < $rules['min_length']) {
                    return "{$rules['min_length']}文字以上で入力してください。";
                }
                if (($rules['max_length'] ?? null) !== null && $len > $rules['max_length']) {
                    return "{$rules['max_length']}文字以内で入力してください。";
                }
                if ($field->input_type === 'short' && ! empty($rules['format']) && $rules['format'] !== 'none' && ! $this->matchFormat($rules['format'], (string) $value)) {
                    return '形式が正しくありません。';
                }
                break;
            case 'number':
                if (! is_numeric($value)) {
                    return '数値で入力してください。';
                }
                $n = (float) $value;
                if (! empty($rules['integer_only']) && floor($n) != $n) {
                    return '整数で入力してください。';
                }
                if (($rules['min'] ?? null) !== null && $n < $rules['min']) {
                    return "{$rules['min']}以上で入力してください。";
                }
                if (($rules['max'] ?? null) !== null && $n > $rules['max']) {
                    return "{$rules['max']}以下で入力してください。";
                }
                break;
            case 'checkbox':
                $count = is_array($value) ? count($value) : 0;
                if (($rules['min_select'] ?? null) !== null && $count < $rules['min_select']) {
                    return "{$rules['min_select']}個以上選択してください。";
                }
                if (($rules['max_select'] ?? null) !== null && $count > $rules['max_select']) {
                    return "{$rules['max_select']}個以内で選択してください。";
                }
                break;
            case 'date':
            case 'datetime':
                if (! empty($rules['min_date']) && $value < $rules['min_date']) {
                    return "{$rules['min_date']} 以降で入力してください。";
                }
                if (! empty($rules['max_date']) && $value > $rules['max_date']) {
                    return "{$rules['max_date']} 以前で入力してください。";
                }
                break;
            case 'time':
                if (! empty($rules['min_time']) && $value < $rules['min_time']) {
                    return "{$rules['min_time']} 以降で入力してください。";
                }
                if (! empty($rules['max_time']) && $value > $rules['max_time']) {
                    return "{$rules['max_time']} 以前で入力してください。";
                }
                break;
            case 'table':
                $columns = is_array($rules['columns'] ?? null) ? $rules['columns'] : [];
                $rows = is_array($value) ? $value : [];
                foreach ($rows as $i => $row) {
                    foreach ($columns as $col) {
                        $key = $col['key'] ?? null;
                        if (! $key) {
                            continue;
                        }
                        $colField = new FlowField([
                            'input_type' => $col['input_type'] ?? 'short',
                            'is_required' => (bool) ($col['required'] ?? false),
                            'options' => $col['options'] ?? null,
                            'validation' => is_array($col['validation'] ?? null) ? $col['validation'] : [],
                        ]);
                        $cellError = $this->validateOne($colField, is_array($row) ? ($row[$key] ?? null) : null);
                        if ($cellError) {
                            return ($i + 1).'行目「'.($col['label'] ?? '').'」：'.$cellError;
                        }
                    }
                }
                break;
        }

        return null;
    }

    private function matchFormat(string $format, string $value): bool
    {
        return match ($format) {
            'email' => (bool) preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $value),
            'tel' => (bool) preg_match('/^[\d\-+()\s]+$/', $value),
            'url' => (bool) preg_match('/^https?:\/\/.+/i', $value),
            default => true,
        };
    }

    /* ================================================================
     | Permission engine (kintone-style 3 levels)
     |================================================================ */

    private const APP_PERMS = ['view', 'add', 'edit', 'delete', 'manage', 'import', 'export', 'bulk'];

    public function isDirectorLevel(User $user): bool
    {
        return in_array($user->id, self::ADMIN_USER_IDS, true)
            || ($user->position_id !== null && $user->position_id < 6);
    }

    private function isSuperAdmin(User $user): bool
    {
        return in_array($user->id, self::ADMIN_USER_IDS, true);
    }

    private function isProjectMember(int $projectId, User $user): bool
    {
        return DB::table('project_members')->where('project_id', $projectId)->where('user_id', $user->id)->exists();
    }

    private function isProjectManager(int $projectId, User $user): bool
    {
        return DB::table('project_members')->where('project_id', $projectId)->where('user_id', $user->id)->where('authority', 1)->exists();
    }

    private function isProjectDirector(int $projectId, User $user): bool
    {
        $director = DB::table('project_records')->where('id', $projectId)->value('director_id');

        return $director !== null && (int) $director === (int) $user->id;
    }

    /** project ids the given user is a MANAGER of (project_members.authority = 1). */
    private function projectIdsManagedBy(int $userId): array
    {
        return DB::table('project_members')
            ->where('user_id', $userId)->where('authority', 1)
            ->pluck('project_id')->map(fn ($x) => (int) $x)->all();
    }

    /** 作成者のPM: $user manages a project that the record's creator also manages. */
    private function isCreatorProjectManager(FlowRecord $record, User $user): bool
    {
        if (! $record->created_by) {
            return false;
        }
        $creatorProjects = $this->projectIdsManagedBy((int) $record->created_by);
        if (empty($creatorProjects)) {
            return false;
        }

        return DB::table('project_members')
            ->where('user_id', $user->id)->where('authority', 1)
            ->whereIn('project_id', $creatorProjects)->exists();
    }

    /** 選択プロジェクトのPM: $user manages the project chosen in the given project-field on this record. */
    private function isFieldProjectManager(FlowRecord $record, int $fieldId, User $user): bool
    {
        $record->loadMissing('values');
        $projectId = (int) ($record->values->firstWhere('flow_field_id', $fieldId)?->value_numeric ?? 0);
        if (! $projectId) {
            return false;
        }

        return DB::table('project_members')
            ->where('project_id', $projectId)->where('user_id', $user->id)->where('authority', 1)
            ->exists();
    }

    /** Does a permission subject match the user, in the context of a definition (+ optional record for field-ref subjects)? */
    public function matchesSubject(string $type, $subjectId, User $user, FlowDefinition $def, ?FlowRecord $record = null): bool
    {
        $projectId = $def->project_record_id ? (int) $def->project_record_id : null;

        return match ($type) {
            'everyone' => true,
            // 作成者 means the creator of the thing being judged. With a record in context
            // (status actions, record permissions, field permissions) that is the person who
            // SUBMITTED the record — 申請者 — which is what the builder's 作成者 checkbox promises,
            // and what its neighbour 作成者のPM already resolves against. Without a record we are
            // evaluating app-level permissions, where 作成者 correctly means whoever built the app
            // (this is the subject seedAppPermissions writes for the creator row).
            'creator' => $record !== null
                ? ($record->created_by !== null && (int) $record->created_by === (int) $user->id)
                : ($def->created_by !== null && (int) $def->created_by === (int) $user->id),
            'user' => (int) $subjectId === (int) $user->id,
            'position' => $user->position_id !== null && (int) $subjectId === (int) $user->position_id,
            'project_member' => $projectId !== null && $this->isProjectMember($projectId, $user),
            'project_manager' => $projectId !== null && $this->isProjectManager($projectId, $user),
            'project_director' => $projectId !== null && $this->isProjectDirector($projectId, $user),
            'field' => $record !== null && in_array((int) $user->id, $this->fieldUserIds($record, (int) $subjectId), true),
            // project-scoped process (glowd): dynamic, resolved per record
            'creator_project_manager' => $record !== null && $this->isCreatorProjectManager($record, $user),
            'field_project_manager' => $record !== null && $this->isFieldProjectManager($record, (int) $subjectId, $user),
            default => false,
        };
    }

    private function fieldUserIds(FlowRecord $record, int $fieldId): array
    {
        $record->loadMissing('values');
        $value = $record->values->firstWhere('flow_field_id', $fieldId);
        $json = $value?->value_json ?? [];

        return is_array($json) ? array_map('intval', $json) : [];
    }

    /**
     * How specific a subject is. The most specific tier that matches a user decides their
     * permissions outright; broader tiers are then ignored for that person.
     *
     * 全員 < 役職・ロール < 個人指定. Anything unrecognised counts as a role rather than an
     * individual, so a subject type added later cannot silently outrank someone's own row.
     */
    private const SUBJECT_RANK = [
        'everyone' => 0,
        // roles: a group of people, however small
        'position' => 1,
        'project_member' => 1,
        'project_manager' => 1,
        'project_director' => 1,
        'creator_project_manager' => 1,
        'field_project_manager' => 1,
        // individuals: this person, by name or by being named in the record
        'creator' => 2,
        'user' => 2,
        'field' => 2,
    ];

    /**
     * Resolve subject-scoped permission rows for one user: the most specific matching tier wins,
     * and rows inside that tier are unioned.
     *
     * Shared by all three layers (app / record-set grants / field) so that "who gets what" reads the
     * same everywhere — an individual entry overrides a role entry, which overrides 全員.
     *
     * @param  string[]  $flags  the can_* suffixes to resolve
     * @return array<string, bool>
     */
    private function resolveSubjectRows($rows, array $flags, User $user, FlowDefinition $def, ?FlowRecord $record = null): array
    {
        $out = array_fill_keys($flags, false);
        $bestRank = -1;
        $winning = [];

        foreach ($rows as $row) {
            if (! $this->matchesSubject($row->subject_type, $row->subject_id, $user, $def, $record)) {
                continue;
            }
            $rank = self::SUBJECT_RANK[$row->subject_type] ?? 1;
            if ($rank > $bestRank) {
                $bestRank = $rank;
                $winning = [$row];
            } elseif ($rank === $bestRank) {
                $winning[] = $row;
            }
        }
        foreach ($winning as $row) {
            foreach ($flags as $f) {
                $out[$f] = $out[$f] || (bool) $row->{'can_'.$f};
            }
        }

        return $out;
    }

    /**
     * App-level: the most specific matching tier wins — 個人指定 over 役職 over 全員.
     *
     * Row order used to decide this, which made the same two rows mean different things depending on
     * an ordering whose effect was invisible. Specificity is both order-independent and the way
     * people already read these tables: "everyone gets this, except this role, except this person".
     *
     * Rows within the winning tier are unioned, so two matching role rows still combine — it is only
     * across tiers that the narrower one takes over. A consequence worth knowing: an individual row
     * REPLACES the person's 役職 row rather than adding to it, so an unchecked box on their own row
     * removes an ability their 役職 would have given them. The builder flags exactly that case.
     */
    public function effectiveAppPermissions(User $user, FlowDefinition $def): array
    {
        $rows = $def->relationLoaded('appPermissions') ? $def->appPermissions : $def->appPermissions()->get();
        $perms = $this->resolveSubjectRows($rows, self::APP_PERMS, $user, $def);

        // Safety: the app creator never loses control of their own app (lockout guard).
        // Note: this is per-app (the creator of THIS app), not a global role — app-level
        // permission settings are otherwise the sole authority (no admin/role override).
        if ($def->created_by !== null && (int) $def->created_by === (int) $user->id) {
            $perms['view'] = true;
            $perms['manage'] = true;
        }

        return $perms;
    }

    /** Record-level: app ∩ record (first matching set's union of matched grants). Returns view/edit/delete. */
    public function recordPermissions(User $user, FlowRecord $record, ?FlowDefinition $def = null): array
    {
        $def = $def ?: $record->definition()->with(['appPermissions', 'recordPermissionSets', 'fields'])->first();
        $app = $this->effectiveAppPermissions($user, $def);
        $base = ['view' => $app['view'], 'edit' => $app['edit'], 'delete' => $app['delete']];

        $sets = $def->relationLoaded('recordPermissionSets') ? $def->recordPermissionSets : $def->recordPermissionSets()->get();
        if ($sets->isEmpty()) {
            return $base;
        }

        $record->loadMissing(['values', 'currentStatus']);
        $fieldsById = ($def->relationLoaded('fields') ? $def->fields : $def->fields()->get())->keyBy('id');

        $matched = null;
        foreach ($sets as $set) {
            if ($this->recordSetMatches($set, $record, $fieldsById)) {
                $matched = $set;
                break;
            }
        }
        if (! $matched) {
            return $base; // unmatched record → app-level fallback
        }

        // which SET applies is still decided by record conditions in order (above); WHO gets what
        // inside it follows the same subject hierarchy as everywhere else
        $grants = $matched->relationLoaded('grants') ? $matched->grants : $matched->grants()->get();
        $rl = $this->resolveSubjectRows($grants, ['view', 'edit', 'delete'], $user, $def, $record);

        return [
            'view' => $app['view'] && $rl['view'],
            'edit' => $app['edit'] && $rl['edit'],
            'delete' => $app['delete'] && $rl['delete'],
        ];
    }

    private function recordSetMatches($set, FlowRecord $record, $fieldsById): bool
    {
        $conds = $set->relationLoaded('conditions') ? $set->conditions : $set->conditions()->get();
        if ($conds->isEmpty()) {
            return true;
        }
        $results = $conds->map(fn ($c) => $this->conditionMatches($c, $record, $fieldsById));

        return $set->match_mode === 'any' ? $results->contains(true) : ! $results->contains(false);
    }

    private function conditionMatches($cond, FlowRecord $record, $fieldsById): bool
    {
        switch ($cond->source) {
            case 'creator':
                $actual = [$record->created_by];
                break;
            case 'updater':
                $actual = [$record->updated_by ?? null];
                break;
            case 'status':
                $actual = [$record->current_status_id, optional($record->currentStatus)->name];
                break;
            case 'field':
            default:
                $field = $fieldsById->get($cond->field_id);
                if (! $field) {
                    return false;
                }
                $val = $this->readFieldValue($record->values->firstWhere('flow_field_id', $field->id), $field);
                $actual = is_array($val) ? $val : [$val];
                break;
        }

        $actual = array_values(array_map(fn ($x) => (string) $x, array_filter($actual, fn ($x) => $x !== null && $x !== '')));
        $vals = array_map(fn ($x) => (string) $x, (array) ($cond->values ?? []));

        return match ($cond->operator) {
            'is_empty' => count($actual) === 0,
            'not_empty' => count($actual) > 0,
            'includes_all' => count($vals) > 0 && count(array_diff($vals, $actual)) === 0,
            'equals' => count($actual) === 1 && in_array($actual[0], $vals, true),
            default => count(array_intersect($actual, $vals)) > 0, // includes_any
        };
    }

    /** Field-level subject perms: field_id => ['view'=>bool,'edit'=>bool]; default-open, union of matched rows. */
    public function fieldPermissions(User $user, FlowDefinition $def, ?FlowRecord $record = null): array
    {
        $rows = ($def->relationLoaded('fieldPermissions') ? $def->fieldPermissions : $def->fieldPermissions()->get())->groupBy('field_id');
        $out = [];

        foreach (($def->relationLoaded('fields') ? $def->fields : $def->fields()->get()) as $f) {
            $frows = $rows->get($f->id);
            if (! $frows || $frows->isEmpty()) {
                $out[$f->id] = ['view' => true, 'edit' => true];

                continue;
            }
            $out[$f->id] = $this->resolveSubjectRows($frows, ['view', 'edit'], $user, $def, $record);
        }

        return $out;
    }

    /**
     * May this user reveal a secret field's plaintext on this record?
     *
     * Full chain: app view ∩ record view ∩ field view. The twist is the DEFAULT — fieldPermissions()
     * grants view+edit to everyone when a field has no permission rows, which is right for ordinary
     * fields but the wrong failure mode for a credential: whoever adds the field would expose it to
     * every record viewer until they remember to configure it. So secret fields FAIL CLOSED —
     * unconfigured means 管理 only.
     */
    public function canRevealSecret(User $user, FlowRecord $record, FlowField $field, FlowDefinition $def): bool
    {
        if (! self::isSecret($field->input_type) || (int) $field->flow_definition_id !== (int) $def->id) {
            return false;
        }
        $app = $this->effectiveAppPermissions($user, $def);
        if (! $app['view'] || ! $this->recordPermissions($user, $record, $def)['view']) {
            return false;
        }

        $rows = ($def->relationLoaded('fieldPermissions') ? $def->fieldPermissions : $def->fieldPermissions()->get())
            ->where('field_id', $field->id);

        return $rows->isEmpty()
            ? (bool) $app['manage']
            : (bool) ($this->fieldPermissions($user, $def, $record)[$field->id]['view'] ?? false);
    }

    /**
     * Plaintext of a stored secret, or null when the field is empty.
     * Throws DecryptException on a key mismatch — callers must NOT swallow that into "no value",
     * or a broken/rotated key looks exactly like an empty credential.
     */
    public function revealSecret(FlowRecord $record, FlowField $field): ?string
    {
        $row = ($record->relationLoaded('values') ? $record->values : $record->values()->get())
            ->firstWhere('flow_field_id', $field->id);
        $cipher = $row->value_text ?? null;

        return ($cipher === null || $cipher === '') ? null : app(AccountVault::class)->decrypt($cipher);
    }

    /** The ユーザー/プロジェクト master a field's picker reads from, or null if it is not such a field. */
    private static function autoFillSourceKey(FlowField $f): ?string
    {
        return match ($f->input_type) {
            'project' => 'project',
            'user', 'member' => 'user',
            default => null,
        };
    }

    /** Field ids that are the destination of some ユーザー/プロジェクト auto-fill mapping. */
    public function autoFillDestinationIds(FlowDefinition $def): array
    {
        $fields = $def->relationLoaded('fields') ? $def->fields : $def->fields()->get();
        $byKey = $fields->keyBy('key');
        $out = [];

        foreach ($fields as $f) {
            if (self::autoFillSourceKey($f) === null) {
                continue;
            }
            foreach ((array) ($f->validation['field_mappings'] ?? []) as $m) {
                $dest = $byKey->get($m['to'] ?? '');
                if ($dest) {
                    $out[(int) $dest->id] = true;
                }
            }
        }

        return array_keys($out);
    }

    /**
     * Fill ユーザー/プロジェクト auto-fill destinations that this user is NOT allowed to write.
     *
     * Why the server does this at all: the destinations are real fields, so they obey field permissions
     * — which meant that in the intended setup (anyone may pick a person, only one team may read 役職)
     * the value visibly appeared in the form and was then dropped on insert, because the writer had no
     * 編集 on it. Worse than not working: the form showed a value that silently vanished.
     *
     * Why not simply let the client write it: then "this is an auto-fill" becomes a claim the client
     * makes, and anyone could put arbitrary text into a restricted field with a crafted request. The
     * field permission would be advisory. So for a destination the user cannot write, the value is
     * resolved HERE from the master and whatever the client sent for it is discarded.
     *
     * Destinations the user CAN write are left alone on purpose: the client already filled them live
     * and the user is allowed to correct them, which is the behaviour that makes auto-fill pleasant.
     *
     * Returns [$values, $extraWritableIds] — the caller must union those ids into its writable set,
     * since by definition they are fields the normal permission check just refused.
     */
    public function applyMasterAutoFill(FlowDefinition $def, array $values, array $writable): array
    {
        $fields = $def->relationLoaded('fields') ? $def->fields : $def->fields()->get();
        $byKey = $fields->keyBy('key');
        $extra = [];

        foreach ($fields as $src) {
            $sourceKey = self::autoFillSourceKey($src);
            $mappings = (array) ($src->validation['field_mappings'] ?? []);
            // the picker itself must be writable by this user: the fill is a consequence of them
            // setting it, not a way to reach fields on a record they cannot touch
            if ($sourceKey === null || ! $mappings || ! in_array((int) $src->id, $writable, true)) {
                continue;
            }

            $raw = $values[(string) $src->id] ?? null;
            $ids = array_values(array_filter(is_array($raw) ? $raw : [$raw], fn ($x) => $x !== null && $x !== ''));
            // >1 selected has no single master row to copy from — same rule the client applies
            $id = count($ids) === 1 ? $ids[0] : null;
            $attrs = $id !== null ? $this->masterAttributes($sourceKey, $id, array_column($mappings, 'from')) : [];

            foreach ($mappings as $m) {
                $dest = $byKey->get($m['to'] ?? '');
                if (! $dest) {
                    continue;
                }
                $secretDest = self::isSecret($dest->input_type);

                // Writable and not a secret → the client already filled it live and the user may have
                // corrected it, so leave it alone.
                if (! $secretDest && in_array((int) $dest->id, $writable, true)) {
                    continue;
                }

                /*
                 * A secret destination is ALWAYS filled here, writable or not.
                 *
                 * The client cannot fill it even when the user is allowed to edit it:
                 * /flow_system_record refuses to serve secret columns (it has no permission context,
                 * so serving a decrypted 口座番号 there would hand it to any authenticated caller).
                 * With the client unable and the server declining because the field "is writable",
                 * nothing filled it at all — which is exactly what happened.
                 *
                 * A value the user typed themselves still wins. The echoed-back boolean marker
                 * ("a value is stored") is NOT a typed value, so re-picking the person legitimately
                 * refreshes the snapshot.
                 */
                if ($secretDest) {
                    $submitted = $values[(string) $dest->id] ?? null;
                    $typed = (is_string($submitted) || is_numeric($submitted)) && trim((string) $submitted) !== '';
                    if ($typed) {
                        continue;
                    }
                }
                $resolved = $attrs[$m['from'] ?? ''] ?? null;
                // Cleared picker (or an ambiguous multi-select) blanks the copy, matching 参照.
                // A secret needs the explicit clear marker: for those, a blank means "keep what is
                // stored", so writing null would silently leave the old 口座番号 on the record after
                // the person it belonged to was removed.
                $values[(string) $dest->id] = ($resolved === null || $resolved === '') && self::isSecret($dest->input_type)
                    ? ['clear' => true]
                    : $resolved;
                $extra[] = (int) $dest->id;
            }
        }

        return [$values, array_values(array_unique($extra))];
    }

    /** Allowlisted master attributes for one row, straight from the FlowSystemSources spec. */
    private function masterAttributes(string $sourceKey, $id, array $wantKeys): array
    {
        $spec = FlowSystemSources::get($sourceKey);
        if (! $spec) {
            return [];
        }
        $query = $spec['model']::query();
        if (isset($spec['filter'])) {
            ($spec['filter'])($query);
        }
        $row = $query->whereKey($id)->first();
        if (! $row) {
            return [];
        }
        $allowed = collect($spec['columns'])->pluck('key')->flip();
        $resolve = $spec['value'] ?? fn ($m, $k) => $m->{$k} ?? null;
        $out = [];
        foreach (array_unique($wantKeys) as $k) {
            if ($allowed->has($k)) {
                $out[$k] = $resolve($row, $k);
            }
        }

        return $out;
    }

    /**
     * Field ids whose VALUE this user may not see.
     *
     * fieldPermissions() has always resolved a per-field `view`, and search / CSV export / PDF have
     * always honoured it — but the record payload did not: serializeRecord() sent recordValues() in
     * full, so a field restricted to one person was still delivered to, and rendered for, everyone who
     * could open the record. Computing the permission and then not applying it is worse than not
     * having it, because the builder UI promises it works.
     *
     * Callers strip these keys AFTER recordValues() has run, never before: formulas are computed from
     * the other values, so hiding an input first would change what the formula says rather than who
     * can read it. A formula's own view permission is what governs the formula.
     */
    public function unviewableFieldIds(User $user, FlowDefinition $def, ?FlowRecord $record = null): array
    {
        $fp = $this->fieldPermissions($user, $def, $record);

        return array_values(array_map('intval', array_keys(array_filter(
            $fp,
            fn ($p) => ($p['view'] ?? true) !== true
        ))));
    }

    /** Field ids the user may edit on a record now: record.edit ∩ field-perm edit ∩ status-rule editable. */
    /**
     * $recordPerms lets a caller that already resolved recordPermissions() pass it in — the record
     * list resolves it per row, and re-deriving it here would double that work on every row.
     */
    public function editableFieldIdsForRecord(User $user, FlowRecord $record, FlowDefinition $def, ?array $recordPerms = null): array
    {
        $perms = $recordPerms ?? $this->recordPermissions($user, $record, $def);
        if (! ($perms['edit'] ?? false)) {
            return [];
        }
        $fp = $this->fieldPermissions($user, $def, $record);
        $status = $record->relationLoaded('currentStatus') ? $record->currentStatus : $record->currentStatus()->first();
        $fields = $def->relationLoaded('fields') ? $def->fields : $def->fields()->get();

        // Resolve the status's rules ONCE. ruleForField() re-reads them per call, and when the
        // record's currentStatus was loaded without its fieldRules that is a query per field —
        // which the record list multiplies by every row.
        $rules = $status ? $this->statusFieldRules($status) : [];

        $ids = [];
        foreach ($fields as $f) {
            if ($f->input_type === 'formula' || self::isLayoutType($f->input_type)) {
                continue;
            }
            $statusOk = ! $status || (($rules[$f->id] ?? 'edit') === 'edit');
            if (($fp[$f->id]['edit'] ?? true) && $statusOk) {
                $ids[] = (int) $f->id;
            }
        }

        return $ids;
    }

    /* ================================================================
     | Cross-app record search (kintone-style「レコードから検索」)
     |================================================================ */

    /**
     * Search stored record values across every app the user may view.
     *
     * Two stages on purpose. SQL narrows to candidate value rows with a coarse LIKE (plus id
     * matches for people/projects, whose names live in other tables); PHP then decides what
     * actually matched. The split exists because a raw LIKE over value_json would hit JSON *keys*
     * as well as values — searching a column key like "c1" would return every subtable row — and
     * because the per-record and per-field permission checks can only run in PHP anyway.
     *
     * Excluded by design: password fields (matching ciphertext is meaningless and would let
     * someone confirm a guessed value) and formula fields (never persisted, so nothing to match).
     */
    public function searchRecordsAcrossApps(User $user, string $kw, int $page = 1, int $perPage = 20, int $candidateCap = 4000): array
    {
        $kw = trim($kw);
        $empty = ['hits' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'truncated' => false];
        if ($kw === '') {
            return $empty;
        }

        $defs = FlowDefinition::with(['fields', 'appPermissions', 'recordPermissionSets', 'fieldPermissions'])
            ->get()
            ->filter(fn ($d) => $this->effectiveAppPermissions($user, $d)['view']);
        if ($defs->isEmpty()) {
            return $empty;
        }

        // field-permission verdicts are per (app, user) and reused for every hit in that app
        $defsById = $defs->keyBy('id');
        $fieldPerms = [];
        $searchable = [];   // field id => field, across all eligible apps
        foreach ($defs as $def) {
            $fieldPerms[$def->id] = $this->fieldPermissions($user, $def);
            foreach ($def->fields as $f) {
                if (self::isSecret($f->input_type) || self::isLayoutType($f->input_type) || $f->input_type === 'formula') {
                    continue;
                }
                $searchable[$f->id] = $f;
            }
        }
        if (! $searchable) {
            return $empty;
        }

        $like = '%'.mb_strtolower($kw).'%';
        // people and projects are stored as ids, so "田中" has to become an id list before SQL can match
        $userIds = User::whereRaw('LOWER(name) LIKE ?', [$like])->pluck('id')->map(fn ($i) => (int) $i)->all();
        $projectIds = DB::table('project_records')->whereRaw('LOWER(name) LIKE ?', [$like])
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        $rows = FlowRecordValue::query()
            ->whereIn('flow_field_id', array_keys($searchable))
            ->where(function ($w) use ($like, $userIds, $projectIds) {
                $w->whereRaw('LOWER(value_text) LIKE ?', [$like])
                    ->orWhereRaw('CAST(value_numeric AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(value_json AS CHAR)) LIKE ?', [$like])
                    ->orWhereRaw("DATE_FORMAT(value_date, '%Y-%m-%d') LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(value_datetime, '%Y-%m-%d %H:%i') LIKE ?", [$like]);
                foreach ($userIds as $id) {
                    $w->orWhereRaw('JSON_CONTAINS(value_json, ?)', [json_encode($id)]);
                }
                if ($projectIds) {
                    $w->orWhereIn('value_numeric', $projectIds);
                }
            })
            ->orderByDesc('id')
            ->limit($candidateCap + 1)
            ->get();

        $truncated = $rows->count() > $candidateCap;
        if ($truncated) {
            $rows = $rows->take($candidateCap);
        }

        // record ids also match on their own number (「#12」 style lookups)
        $recordIds = $rows->pluck('flow_record_id')->unique()->all();
        $records = FlowRecord::whereIn('id', $recordIds)->get()->keyBy('id');

        $names = $this->searchNameMaps($userIds, $projectIds, $kw);

        $hits = [];
        foreach ($rows as $row) {
            $record = $records->get($row->flow_record_id);
            $field = $searchable[$row->flow_field_id] ?? null;
            if (! $record || ! $field || (int) $record->flow_definition_id !== (int) $field->flow_definition_id) {
                continue;
            }
            $def = $defsById->get($record->flow_definition_id);
            if (! $def) {
                continue;
            }
            // a hit on a field the user cannot view must be dropped, not merely hidden — field
            // permissions default to allow-all, so this only bites on apps that configure them
            if (($fieldPerms[$def->id][$field->id]['view'] ?? true) !== true) {
                continue;
            }
            $matched = $this->matchedValueFor($field, $row, $kw, $names);
            if ($matched === null) {
                continue;
            }
            $hits[] = [
                'definition_id' => (int) $def->id,
                'definition_name' => $def->name,
                'record_id' => (int) $record->id,
                'record_number' => (int) $record->record_number,
                'field_label' => $matched['label'],
                'value' => $matched['value'],
                'updated_at' => optional($record->updated_at)->toIso8601String(),
                '_sort' => optional($record->updated_at)->getTimestamp() ?? 0,
            ];
        }

        // record-level permission sets can only be judged per record in PHP, so they are applied
        // after matching (and before pagination, or the page counts would lie)
        $hits = $this->filterHitsByRecordPermission($user, $hits, $defsById, $records);

        usort($hits, fn ($a, $b) => $b['_sort'] <=> $a['_sort'] ?: $b['record_number'] <=> $a['record_number']);
        $total = count($hits);
        // array_map, not `foreach ($slice as &$h)`: that leaves $h bound to the last element, and the
        // plain `foreach ($slice as $h)` below then writes through it on every pass — which silently
        // replaced the last hit of every page with a copy of the one before it.
        $slice = array_map(function ($h) {
            unset($h['_sort']);

            return $h;
        }, array_slice($hits, ($page - 1) * $perPage, $perPage));

        // app identity (name + icon) is sent once per app rather than repeated on every hit — the
        // modal can't source it locally because search spans apps outside the portal's project scope
        $apps = [];
        foreach ($slice as $h) {
            $id = $h['definition_id'];
            if (isset($apps[$id])) {
                continue;
            }
            $def = $defsById->get($id);
            $apps[$id] = [
                'id' => (int) $id,
                'name' => $def->name ?? '',
                'icon_svg' => $def->icon_svg ?? null,
                'icon_image' => $def->icon_image ?? null,
                'color_id' => $def->color_id ?? null,
            ];
        }

        return [
            'hits' => array_values($slice),
            'apps' => $apps,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'truncated' => $truncated,
        ];
    }

    /** id => name lookups for the field types that store ids (people, projects). */
    private function searchNameMaps(array $userIds, array $projectIds, string $kw): array
    {
        return [
            'kw' => mb_strtolower($kw),
            'users' => User::whereIn('id', $userIds)->pluck('name', 'id')->all(),
            'projects' => DB::table('project_records')->whereIn('id', $projectIds)->pluck('name', 'id')->all(),
        ];
    }

    /** Drop hits whose record the user may not view (apps with record-level permission sets). */
    private function filterHitsByRecordPermission(User $user, array $hits, $defsById, $records): array
    {
        $verdict = [];   // record id => bool

        return array_values(array_filter($hits, function ($h) use ($user, $defsById, $records, &$verdict) {
            $def = $defsById->get($h['definition_id']);
            if (! $def || $def->recordPermissionSets->isEmpty()) {
                return true;
            }
            $rid = $h['record_id'];
            if (! array_key_exists($rid, $verdict)) {
                $rec = $records->get($rid);
                $verdict[$rid] = $rec ? (bool) $this->recordPermissions($user, $rec, $def)['view'] : false;
            }

            return $verdict[$rid];
        }));
    }

    /**
     * What in this value row actually matched, as {label, value} — or null if nothing did.
     * The SQL prefilter is deliberately loose (a JSON LIKE also hits keys), so this is where a
     * candidate becomes a real hit.
     */
    private function matchedValueFor(FlowField $field, FlowRecordValue $row, string $kw, array $names): ?array
    {
        $needle = mb_strtolower($kw);
        $hit = fn ($value, $suffix = '') => ['label' => $field->label.$suffix, 'value' => (string) $value];
        $contains = fn ($hay) => $hay !== null && $hay !== '' && str_contains(mb_strtolower((string) $hay), $needle);

        switch ($field->input_type) {
            case 'number':
                return $contains($row->value_numeric) || $contains(rtrim(rtrim((string) $row->value_numeric, '0'), '.'))
                    ? $hit(rtrim(rtrim((string) $row->value_numeric, '0'), '.')) : null;
            case 'project':
                $id = $row->value_numeric === null ? null : (int) $row->value_numeric;
                $name = $id !== null ? ($names['projects'][$id] ?? null) : null;

                return $name !== null ? $hit($name) : null;
            case 'user':
            case 'member':
                $ids = is_array($row->value_json) ? $row->value_json : [];
                $matchedNames = [];
                foreach ($ids as $id) {
                    if (isset($names['users'][(int) $id])) {
                        $matchedNames[] = $names['users'][(int) $id];
                    }
                }

                return $matchedNames ? $hit(implode('、', $matchedNames)) : null;
            case 'checkbox':
                $picked = array_values(array_filter(is_array($row->value_json) ? $row->value_json : [], $contains));

                return $picked ? $hit(implode('、', $picked)) : null;
            case 'reference':
                $label = is_array($row->value_json) ? ($row->value_json['label'] ?? null) : null;

                return $contains($label) ? $hit($label) : null;
            case 'date':
                $s = optional($row->value_date)->toDateString();

                return $contains($s) ? $hit($s) : null;
            case 'datetime':
                $s = optional($row->value_datetime)->format('Y-m-d H:i');

                return $contains($s) ? $hit($s) : null;
            case 'table':
                return $this->matchedTableCell($field, $row, $needle, $names);
            case 'toggle':
            case 'file':
                return null;   // オン/オフ and attachments carry no searchable text
            default:
                return $contains($row->value_text) ? $hit($row->value_text) : null;
        }
    }

    /** First matching cell inside a subtable, labelled 「テーブル名 > 列名（N行目）」. */
    private function matchedTableCell(FlowField $field, FlowRecordValue $row, string $needle, array $names): ?array
    {
        $rows = is_array($row->value_json) ? $row->value_json : [];
        $columns = collect($field->validation['columns'] ?? [])->keyBy('key');

        foreach (array_values($rows) as $i => $r) {
            if (! is_array($r)) {
                continue;
            }
            foreach ($r as $key => $cell) {
                $col = $columns->get($key);
                if (! $col || self::isSecret($col['input_type'] ?? null) || ($col['input_type'] ?? null) === 'formula') {
                    continue;
                }
                $text = $this->flattenCellText($cell, $names);
                if ($text !== '' && str_contains(mb_strtolower($text), $needle)) {
                    return [
                        'label' => $field->label.' › '.($col['label'] ?? $key).'（'.($i + 1).'行目）',
                        'value' => $text,
                    ];
                }
            }
        }

        return null;
    }

    /** Subtable cells are raw JSON — flatten to searchable text (resolving people/projects). */
    private function flattenCellText($cell, array $names): string
    {
        if ($cell === null || is_bool($cell)) {
            return '';
        }
        if (is_scalar($cell)) {
            // a bare id may be a person or a project; surface the name so it is matchable
            $asInt = (int) $cell;
            if ((string) $asInt === (string) $cell) {
                foreach (['users', 'projects'] as $k) {
                    if (isset($names[$k][$asInt])) {
                        return (string) $names[$k][$asInt];
                    }
                }
            }

            return (string) $cell;
        }
        if (is_array($cell)) {
            if (isset($cell['label'])) {
                return (string) $cell['label'];   // reference cell
            }
            $parts = [];
            foreach ($cell as $v) {
                $t = $this->flattenCellText($v, $names);
                if ($t !== '') {
                    $parts[] = $t;
                }
            }

            return implode('、', $parts);
        }

        return '';
    }

    /* ================================================================
     | Slots — free areas above/below the record table. Only 集計 so far.
     |================================================================ */

    /**
     * Compute a slot's aggregates over an entire record set.
     *
     * Deliberately not SQL. Two of the three allowed sources cannot be aggregated by the database:
     * 計算 fields hold no stored value (they are evaluated on read) and subtable columns live inside
     * a JSON blob. So the caller hands over every record matching the active view/filter and this
     * walks them — which also means the numbers always agree with the list the user is looking at.
     *
     * $records must already be permission-filtered by the caller: a SUM over rows the user may not
     * open would leak their contents in aggregate. Field-level permission is applied here, since
     * that is per item rather than per record.
     */
    public function computeSlotAggregates(FlowDefinition $definition, $records, User $user, array $slots, bool $withValues = true): array
    {
        if (! $slots) {
            return [];
        }
        $fields = $definition->relationLoaded('fields') ? $definition->fields : $definition->fields()->get();
        $fieldsById = $fields->keyBy('id');
        $fieldPerms = $this->fieldPermissions($user, $definition);

        // recordValues() evaluates 計算 fields, so do it once per record and share across every item
        $valuesByRecord = [];
        if ($withValues) {
            foreach ($records as $rec) {
                $valuesByRecord[$rec->id] = $this->recordValues($rec, $fields);
            }
        }

        $out = [];
        foreach ($slots as $slot) {
            $config = is_array($slot->config) ? $slot->config : [];
            $items = [];
            foreach (($config['items'] ?? []) as $item) {
                $resolved = $this->resolveSlotSource($item['source'] ?? '', $fieldsById);
                if (! $resolved) {
                    continue;
                }
                // a hidden field's total is still information about that field — drop the item
                if (($fieldPerms[$resolved['field']->id]['view'] ?? true) !== true) {
                    continue;
                }
                $fn = in_array($item['fn'] ?? '', ['sum', 'avg', 'max', 'min'], true) ? $item['fn'] : 'sum';
                // client mode: the front end holds every visible record and narrows it further with
                // its own search/ad-hoc filter, so a value computed here would describe a different
                // set than the list shows. Ship the resolved item and let it do the arithmetic.
                $numbers = $withValues ? $this->slotNumbers($resolved, $valuesByRecord) : [];
                $items[] = [
                    'source' => $item['source'],
                    'fn' => $fn,
                    'label' => $item['label'] ?: $resolved['label'].' の '.self::SLOT_FN_LABELS[$fn],
                    // display-only: the affixes wrap the rendered number, never the arithmetic
                    'prefix' => (string) ($item['prefix'] ?? ''),
                    'suffix' => (string) ($item['suffix'] ?? ''),
                    'value' => $withValues ? $this->applySlotFn($fn, $numbers) : null,
                    'count' => $withValues ? count($numbers) : null,
                    'computed' => $withValues,
                ];
            }
            $out[] = [
                'id' => (int) $slot->id,
                'name' => $slot->name,
                'position' => ($config['position'] ?? 'bottom') === 'top' ? 'top' : 'bottom',
                'items' => $items,
            ];
        }

        return $out;
    }

    private const SLOT_FN_LABELS = ['sum' => '合計', 'avg' => '平均', 'max' => '最大', 'min' => '最小'];

    /** "<fieldId>" => a 数値/計算 field; "<tableFieldId>:<colKey>" => a number column inside a テーブル. */
    private function resolveSlotSource(string $source, $fieldsById): ?array
    {
        if ($source === '') {
            return null;
        }
        if (! str_contains($source, ':')) {
            $field = $fieldsById->get((int) $source);
            if (! $field || ! in_array($field->input_type, ['number', 'formula'], true)) {
                return null;
            }

            return ['kind' => 'field', 'field' => $field, 'label' => $field->label];
        }

        [$tableId, $colKey] = explode(':', $source, 2);
        $field = $fieldsById->get((int) $tableId);
        if (! $field || $field->input_type !== 'table') {
            return null;
        }
        $col = collect($field->validation['columns'] ?? [])->firstWhere('key', $colKey);
        if (! $col || ! in_array($col['input_type'] ?? '', ['number', 'formula'], true)) {
            return null;
        }

        return ['kind' => 'column', 'field' => $field, 'column' => $colKey, 'label' => $field->label.' › '.($col['label'] ?? $colKey)];
    }

    /** Every numeric value the source contributes across the record set (blanks skipped, not zeroed). */
    private function slotNumbers(array $resolved, array $valuesByRecord): array
    {
        $numbers = [];
        foreach ($valuesByRecord as $vals) {
            $raw = $vals[(string) $resolved['field']->id] ?? null;
            if ($resolved['kind'] === 'field') {
                if (is_numeric($raw)) {
                    $numbers[] = (float) $raw;
                }

                continue;
            }
            foreach (is_array($raw) ? $raw : [] as $row) {
                $cell = is_array($row) ? ($row[$resolved['column']] ?? null) : null;
                if (is_numeric($cell)) {
                    $numbers[] = (float) $cell;
                }
            }
        }

        return $numbers;
    }

    /** null for an empty set — a 平均 of nothing is not 0, and neither is a 最大. */
    private function applySlotFn(string $fn, array $numbers): ?float
    {
        if (! $numbers) {
            return $fn === 'sum' ? 0.0 : null;
        }

        return match ($fn) {
            'avg' => array_sum($numbers) / count($numbers),
            'max' => max($numbers),
            'min' => min($numbers),
            default => array_sum($numbers),
        };
    }
}
