<?php

namespace App\Services;

use App\Models\FlowAuditLog;
use App\Models\FlowDefinition;
use App\Models\FlowField;
use App\Models\FlowRecord;
use App\Models\FlowRecordAssignee;
use App\Models\FlowRecordValue;
use App\Models\FlowStatus;
use App\Models\FlowStatusAction;
use App\Models\FlowView;
use App\Models\User;
use App\Support\FlowDynamicDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FlowService
{
    private const ADMIN_USER_IDS = [608, 610];

    /** Layout/decoration field types that hold no record value. */
    public const LAYOUT_TYPES = ['heading', 'label', 'spacer', 'divider'];

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

    /** Admins, 上長 (position_id < 6) and PM (position_id == 6) can build & manage flows. */
    public function canManageFlows(User $user): bool
    {
        return ($user->position_id && $user->position_id <= 6)
            || in_array($user->id, self::ADMIN_USER_IDS, true);
    }

    public function canCreateRecord(User $user, FlowDefinition $definition): bool
    {
        if (! $definition->is_active) {
            return false;
        }

        if ($this->canManageFlows($user) || $definition->created_by === $user->id) {
            return true;
        }

        if ($definition->visibility === 'all_staff') {
            return true;
        }

        return $this->sharesForUser($definition, $user)
            ->contains(fn ($share) => $share->access_level === 'use');
    }

    public function canViewRecord(User $user, FlowRecord $record): bool
    {
        if ($this->canManageFlows($user) || $record->created_by === $user->id) {
            return true;
        }

        if ($this->isAssignee($record, $user, false)) {
            return true;
        }

        $definition = $record->relationLoaded('definition')
            ? $record->definition
            : $record->definition()->with('shares')->first();

        if (! $definition) {
            return false;
        }

        if ($definition->visibility === 'all_staff') {
            return true;
        }

        return $this->sharesForUser($definition, $user)->isNotEmpty();
    }

    /** May act (advance / send back) on the record's current status. */
    public function canActOnRecord(User $user, FlowRecord $record): bool
    {
        if ($this->isCompleted($record)) {
            return false;
        }

        if ($this->canManageFlows($user)) {
            return true;
        }

        return $this->isAssignee($record, $user, true);
    }

    private function sharesForUser(FlowDefinition $definition, User $user)
    {
        $shares = $definition->relationLoaded('shares')
            ? $definition->shares
            : $definition->shares()->get();

        return $shares->filter(function ($share) use ($user) {
            return ($share->user_id && $share->user_id === $user->id)
                || ($share->position_id && $share->position_id === $user->position_id);
        });
    }

    /** $currentOnly = only assignees of the record's current status who haven't completed. */
    public function isAssignee(FlowRecord $record, User $user, bool $currentOnly): bool
    {
        $query = FlowRecordAssignee::query()
            ->where('flow_record_id', $record->id)
            ->where('user_id', $user->id);

        if ($currentOnly) {
            $query->where('flow_status_id', $record->current_status_id)
                ->whereNull('completed_at');
        }

        return $query->exists();
    }

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
        $def = $record->definition;
        foreach (($action->eligible ?? []) as $subj) {
            $type = $subj['subject_type'] ?? null;
            if ($type && $this->matchesSubject($type, $subj['subject_id'] ?? null, $user, $def, $record)) {
                return true;
            }
        }
        // Nobody configured → open to anyone who can edit the record (matches the builder's
        // 「未設定 = 編集権限を持つ全員が押せます」 hint).
        if (empty($action->eligible)) {
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
     */
    public function hasExplicitPendingAction(User $user, FlowRecord $record): bool
    {
        foreach ($this->statusActionsFor($record) as $action) {
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
            fn ($a) => collect($a->eligible ?? [])->contains($isExplicit)
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

    public function nextStatus(FlowRecord $record): ?FlowStatus
    {
        $statuses = $this->orderedStatuses($record->definition);
        $index = $statuses->search(fn ($s) => $s->id === $record->current_status_id);

        return $index === false ? null : $statuses->get($index + 1);
    }

    public function previousStatus(FlowRecord $record): ?FlowStatus
    {
        $statuses = $this->orderedStatuses($record->definition);
        $index = $statuses->search(fn ($s) => $s->id === $record->current_status_id);

        return ($index === false || $index === 0) ? null : $statuses->get($index - 1);
    }

    public function isCompleted(FlowRecord $record): bool
    {
        $status = $record->relationLoaded('currentStatus') ? $record->currentStatus : $record->currentStatus()->first();

        return $status && $status->is_locked === 'end';
    }

    /* ----------------------------------------------------------------
     | Assignee snapshot (resolved when a record enters a status)
     |---------------------------------------------------------------- */

    public function resolveAssigneeUserIds(FlowStatus $status, FlowRecord $record): array
    {
        switch ($status->assignment_type) {
            case 'user':
                return $status->assignment_target_id ? [(int) $status->assignment_target_id] : [];
            case 'position':
                if (! $status->assignment_target_id) {
                    return [];
                }

                return User::query()
                    ->where('position_id', $status->assignment_target_id)
                    ->where('retire', 0)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            case 'creator':
            default:
                return $record->created_by ? [(int) $record->created_by] : [];
        }
    }

    public function snapshotAssignees(FlowRecord $record, FlowStatus $status): void
    {
        $userIds = $this->resolveAssigneeUserIds($status, $record);
        $now = now();
        $source = $status->assignment_type;
        $sourceId = $status->assignment_target_id;

        foreach (array_unique($userIds) as $userId) {
            FlowRecordAssignee::updateOrCreate(
                [
                    'flow_record_id' => $record->id,
                    'flow_status_id' => $status->id,
                    'user_id' => $userId,
                ],
                [
                    'source' => $source,
                    'source_id' => $sourceId,
                    'completed_at' => null,
                ]
            );
        }
    }

    public function completeAssignees(FlowRecord $record, int $statusId): void
    {
        FlowRecordAssignee::query()
            ->where('flow_record_id', $record->id)
            ->where('flow_status_id', $statusId)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }

    /* ----------------------------------------------------------------
     | Transitions
     |---------------------------------------------------------------- */

    public function advance(FlowRecord $record, User $user, ?string $comment = null): FlowStatus
    {
        $next = $this->nextStatus($record);
        abort_if(! $next, 422, '次のステータスがありません。');

        DB::transaction(function () use ($record, $next, $user, $comment) {
            $from = $record->currentStatus;
            $this->completeAssignees($record, $record->current_status_id);
            $record->update(['current_status_id' => $next->id]);
            $this->snapshotAssignees($record, $next);
            $this->logStatusChange($record, $user, 'advanced', $from?->name, $next->name, $comment);
            $this->markRecordComplete($record, $next);
        });

        return $next;
    }

    public function sendBack(FlowRecord $record, User $user, string $reason): FlowStatus
    {
        $previous = $this->previousStatus($record);
        abort_if(! $previous, 422, '差し戻し先のステータスがありません。');

        DB::transaction(function () use ($record, $previous, $user, $reason) {
            $from = $record->currentStatus;
            $this->completeAssignees($record, $record->current_status_id);
            $record->update(['current_status_id' => $previous->id]);
            $this->snapshotAssignees($record, $previous);
            $this->logStatusChange($record, $user, 'returned', $from?->name, $previous->name, $reason);
        });

        return $previous;
    }

    private function markRecordComplete(FlowRecord $record, FlowStatus $status): void
    {
        if ($status->is_locked === 'end') {
            $this->completeAssignees($record, $status->id);
        }
    }

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

    public function waitingForUserQuery(User $user)
    {
        return FlowRecord::query()
            ->whereExists(function ($sub) use ($user) {
                $sub->select(DB::raw(1))
                    ->from('flow_record_assignees')
                    ->whereColumn('flow_record_assignees.flow_record_id', 'flow_records.id')
                    ->whereColumn('flow_record_assignees.flow_status_id', 'flow_records.current_status_id')
                    ->where('flow_record_assignees.user_id', $user->id)
                    ->whereNull('flow_record_assignees.completed_at');
            })
            ->with(['definition:id,name', 'currentStatus:id,name,flow_definition_id']);
    }

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

        if ($op === 'is_empty') {
            $q->whereDoesntHave('values', fn ($v) => $v->where('flow_field_id', $fid)->whereNotNull($col)->where($col, '!=', ''));
        } elseif ($op === 'not_empty') {
            $q->whereHas('values', fn ($v) => $v->where('flow_field_id', $fid)->whereNotNull($col)->where($col, '!=', ''));
        } elseif ($op === 'includes_any') {
            $q->whereHas('values', function ($v) use ($fid, $col, $vals) {
                $v->where('flow_field_id', $fid)->where(function ($w) use ($col, $vals) {
                    foreach ($vals as $val) {
                        $w->orWhere($col, 'like', '%"'.$val.'"%')->orWhere($col, $val);
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

    public function createRecord(FlowDefinition $definition, User $user, array $values): FlowRecord
    {
        $start = $this->startStatus($definition);
        abort_if(! $start, 422, 'このフローには開始ステータスがありません。');

        return DB::transaction(function () use ($definition, $user, $values, $start) {
            $record = FlowRecord::create([
                'flow_definition_id' => $definition->id,
                'record_number' => $this->nextRecordNumber($definition),
                'current_status_id' => $start->id,
                'created_by' => $user->id,
            ]);

            $fields = $definition->fields;
            $allowed = $this->editableFieldIds($start, $fields);
            $changes = $this->writeValues($record, $values, $fields, $allowed);

            $this->snapshotAssignees($record, $start);
            $this->logStatusChange($record, $user, 'created', null, $start->name, null);
            $this->logValueChanges($record, $user, $changes);

            return $record;
        });
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
        // Capture the previous file list before we null it, so persistFileField can delete removed files.
        $oldFiles = $field->input_type === 'file' ? ($value->value_json ?? []) : [];
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
                $value->value_json = $this->persistFileField($record, $this->arrayValue($raw), $oldFiles);
                break;
            case 'user':
            case 'member':
                $ids = $this->userIdArrayValue($raw);
                $value->value_json = $ids;
                $value->value_numeric = count($ids) === 1 ? $ids[0] : null;
                break;
            case 'table':
                $value->value_json = $this->tableValue($raw, $field);
                break;
            case 'reference':
                $ref = $this->referenceValue($raw);
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
     * Move newly-uploaded temp files (from /attach_upload_api) into the record's permanent
     * folder, keep already-stored ones, and delete files that were removed. Returns the
     * value_json list with a stable `url` per file.
     */
    private function persistFileField(FlowRecord $record, array $incoming, array $old): array
    {
        $dir = "flow_record_files/{$record->id}";
        $kept = [];
        $keptIds = [];

        foreach ($incoming as $f) {
            if (! is_array($f) || empty($f['id'])) {
                continue;
            }
            $id = (int) $f['id'];
            $ext = (string) ($f['extension'] ?? '');
            $userId = (int) ($f['user_id'] ?? $record->created_by);
            $destRel = "{$dir}/{$id}_{$userId}.{$ext}";

            if (empty($f['stored'])) {
                $srcRel = "temp_upload/{$id}.{$ext}";
                if (Storage::disk('local')->exists($srcRel)) {
                    File::isDirectory(storage_path("app/{$dir}")) or File::makeDirectory(storage_path("app/{$dir}"), 0755, true, true);
                    Storage::disk('local')->move($srcRel, $destRel);
                }
            }

            $kept[] = [
                'id' => $id,
                'name' => (string) ($f['name'] ?? "{$id}.{$ext}"),
                'extension' => $ext,
                'mime_type' => $f['mime_type'] ?? null,
                'size' => $f['size'] ?? null,
                'user_id' => $userId,
                'stored' => true,
                'url' => "/cdn/{$destRel}",
            ];
            $keptIds[] = $id;
        }

        // Physically remove files that were dropped from the field.
        foreach ($old as $f) {
            if (is_array($f) && ! empty($f['id']) && ! in_array((int) $f['id'], $keptIds, true)) {
                $ext = (string) ($f['extension'] ?? '');
                $userId = (int) ($f['user_id'] ?? 0);
                Storage::disk('local')->delete("{$dir}/{$f['id']}_{$userId}.{$ext}");
            }
        }

        return $kept;
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
            'checkbox', 'file', 'user', 'member', 'table' => $value->value_json ?: [],
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
                    $cell = $row[$ck] ?? null;
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
            $v = $values[(string) $field->id] ?? null;
            $context[(string) $field->id] = $v;
            $context[$field->key] = $v;
            $context[$field->label] = $v;
        }

        return $context;
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
    private function referenceValue($raw): ?array
    {
        if (! is_array($raw) || empty($raw['id'])) {
            return null;
        }

        return [
            'id' => (int) $raw['id'],
            'number' => isset($raw['number']) ? (int) $raw['number'] : null,
            'label' => isset($raw['label']) ? (string) $raw['label'] : '',
        ];
    }

    /** Coerce a single table cell for JSON storage, matching the column's input type. */
    private function coerceCellValue(?string $type, $raw)
    {
        return match ($type) {
            'number' => $this->numberValue($raw),
            'toggle' => $this->booleanValue($raw),
            'checkbox', 'file' => $this->arrayValue($raw),
            'user', 'member' => $this->userIdArrayValue($raw),
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

    /** App-level: first-match-wins over flow_app_permissions; returns the 7 flags. */
    public function effectiveAppPermissions(User $user, FlowDefinition $def): array
    {
        $perms = array_fill_keys(self::APP_PERMS, false);

        $rows = $def->relationLoaded('appPermissions') ? $def->appPermissions : $def->appPermissions()->get();
        foreach ($rows as $row) {
            if ($this->matchesSubject($row->subject_type, $row->subject_id, $user, $def)) {
                foreach (self::APP_PERMS as $p) {
                    $perms[$p] = (bool) $row->{'can_'.$p};
                }
                break;
            }
        }

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

        $rl = ['view' => false, 'edit' => false, 'delete' => false];
        foreach (($matched->relationLoaded('grants') ? $matched->grants : $matched->grants()->get()) as $g) {
            if ($this->matchesSubject($g->subject_type, $g->subject_id, $user, $def, $record)) {
                $rl['view'] = $rl['view'] || $g->can_view;
                $rl['edit'] = $rl['edit'] || $g->can_edit;
                $rl['delete'] = $rl['delete'] || $g->can_delete;
            }
        }

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
            $fp = ['view' => false, 'edit' => false];
            foreach ($frows as $r) {
                if ($this->matchesSubject($r->subject_type, $r->subject_id, $user, $def, $record)) {
                    $fp['view'] = $fp['view'] || $r->can_view;
                    $fp['edit'] = $fp['edit'] || $r->can_edit;
                }
            }
            $out[$f->id] = $fp;
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

    /** Field ids the user may edit on a record now: record.edit ∩ field-perm edit ∩ status-rule editable. */
    public function editableFieldIdsForRecord(User $user, FlowRecord $record, FlowDefinition $def): array
    {
        if (! $this->recordPermissions($user, $record, $def)['edit']) {
            return [];
        }
        $fp = $this->fieldPermissions($user, $def, $record);
        $status = $record->relationLoaded('currentStatus') ? $record->currentStatus : $record->currentStatus()->first();
        $fields = $def->relationLoaded('fields') ? $def->fields : $def->fields()->get();

        $ids = [];
        foreach ($fields as $f) {
            if ($f->input_type === 'formula' || self::isLayoutType($f->input_type)) {
                continue;
            }
            $statusOk = $status ? ($this->ruleForField($status, $f->id) === 'edit') : true;
            if (($fp[$f->id]['edit'] ?? true) && $statusOk) {
                $ids[] = (int) $f->id;
            }
        }

        return $ids;
    }
}
