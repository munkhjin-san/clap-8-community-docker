# カスタムアプリ (Custom App Builder) — Merge & Port Plan

Status: **Planning / pre-implementation** (2026-06-30). Supersedes the framing in `approval-flow-design.md` by widening the feature into a kintone-style app builder. No code yet.

## Context

Two parallel efforts converge:
- **`feature/approval-flow`** (this branch) — strong **builder** (row layout, our element types, per-type validation) + a **status/approval engine** (`flow_*` tables, `FlowController`, `FlowService`). No runtime (no record list/views).
- **`list-management`** (origin, commit `8b613a40`) — project-scoped **list + records + views + formula** runtime: models, migration, a complete **2,230-line `ProjectManagementController`**, `ProjectManagementFormulaEvaluator`, and a 4,479-line `Management.vue` mounted as a project tab. No workflow.

They are complementary. We merge them into one engine.

## Locked decisions (2026-06-30)

1. **Single engine on `flow_*`.** Port their controller + Vue + FormulaEvaluator onto `flow_*`; **drop** `project_management_*` tables/models/routes.
2. **Positioning: カスタムアプリ.** One feature = build an app (fields + records + views); **workflow (申請・承認) is an optional mode**, not the whole thing.
3. **Port the full runtime** (table + record drawer + saved views + kanban) — it's already written.
4. **Port actions + calendar** (timeline + calendar scheduling) — already implemented in their controller.

Port source of truth: worktree at commit `8b613a40` (`.../scratchpad/list-mgmt`).

## Target architecture

A `FlowDefinition` is a generic **app**: always has fields + a records/views runtime; **optionally** carries statuses (approval) and **optionally** attaches to a project (`project_record_id`).
- `project_record_id` **null** → app-level: shared via `flow_shares`, lives in admin + dashboard, `user` field type (all users).
- `project_record_id` **set** → project-scoped: shown as a Project tab, project-member auth, `member` field type (project members), calendar-linked actions.

Endpoints carry the definition id and derive project context from it — so we do **not** need their `/projects/{project}/...` route shape; one flow API serves both mount points, branching auth on scope.

## Keep / Drop / Port / Build

| | What |
|---|---|
| **Keep (ours)** | Builder (FlowControl/FlowBuilder/tabs/inspector, row layout, validation), approval engine, `flow_*` tables, `FlowController`/`FlowService` |
| **Drop (theirs)** | Field-**builder** UI (`Management.vue` 163–418, fns 1973–2408); `project_management_*` tables/models/routes; their `Finance` route refactor in the same commit (unrelated) |
| **Port (theirs → ours)** | `ProjectManagementController` runtime methods → our controllers; `FormulaEvaluator` → `FlowFormulaEvaluator`; record table/kanban + drawer + views + actions timeline from `Management.vue`; `composables/projectManagement/{helpers,constants,types}.ts` + interface; the **formula editor widget** salvaged from their dropped builder into our inspector |
| **Build (new)** | `flow_views` + `flow_record_actions` tables/models; auth generalization; project-attach UI + Project tab mount; status-as-column in records view |

## Element-type reconciliation (ours canonical, absorb the rest)

| Unified type | Source | Value column |
|---|---|---|
| short (短文), long (長文), url, select, radio | ours / +url | `value_text` |
| number (数値) | ours | `value_numeric` |
| date (日付) | ours | `value_date` |
| time (時刻) | ours | `value_text` |
| **datetime (日時)** NEW | theirs | `value_datetime` |
| **toggle (オン/オフ)** NEW | their `checkbox`(bool) | `value_boolean` |
| checkbox (複数選択) | ours (= their `multi_select`) | `value_json` |
| file (ファイル) | ours (= their `file_reference`) | `value_json` |
| **user** NEW | theirs | `value_json` (+`value_numeric` if single) |
| **member** (プロジェクトメンバー, project-only) NEW | theirs | `value_json` (+`value_numeric` if single) |
| **formula (計算)** NEW | theirs | computed on read (not stored) |
| heading (見出し) | ours | — |
| status | **stays our workflow concept** (`flow_statuses`), not a field | — |

Confirmed by their `saveValue`/`valueForField`: formula is **compute-on-read** via the evaluator with a context keyed by field id + key + label.

## Schema deltas (Phase A migrations)

1. `flow_definitions`: + `project_record_id` (nullable, indexed).
2. `flow_record_values`: + `value_datetime`, `value_boolean`, `value_json`.
3. **new `flow_views`**: `flow_definition_id`, `name`, `columns` JSON, `filters` JSON, `sort` JSON, `view_mode`, `created_by`, timestamps.
4. `flow_fields`: + `formula` (text, null), + `result_type` (string, null), + `hidden` (bool). Reuse existing `depends_on` as `visible_when`, `options` for choices, `validation` for our rules.
5. **new `flow_record_actions`**: `flow_record_id`, `user_id`, `action_type`, `title`, `body`, `happened_at`, `next_action_on`, `metadata` JSON, `calendar_record_id`, `calendar_synced_at`, timestamps.

## Backend port

- **Controller**: port `ProjectManagementController`'s 24 public methods into our controllers (extend `FlowController`, or split `FlowRecordController`/`FlowViewController`). Mechanical renames: `ProjectManagement*`→`Flow*`, `value_number`→`value_numeric`, `field->type`→`input_type`, `settings['formula']`→`field->formula`, `settings['result_type']`→`field->result_type`, `settings['options']`→`field->options`. Private helpers (`saveValue`, `valueForField`, `valuesForRecord`, `formulaContext`, `castFormulaResult`, `filterAndSortRecords`, `summary`, CSV import/export, `createCalendarFromAction`, `serializeList`) port ~1:1.
- **Formula**: `ProjectManagementFormulaEvaluator` → `FlowFormulaEvaluator` (largely unchanged; context built from flow fields/values).
- **Auth generalization** (in `FlowService`): app-level (null project) → existing `flow_shares`/admin predicates; project-scoped → port their `canView`/`canEditRecords`/`canManageStructure` (project member/manager/director). Single `canViewRecord`/`canEditRecords`/`canManageStructure` that branches on `project_record_id`.

## Frontend port

- **Builder (ours, extended)**: add `datetime`/`url`/`user`/`member`/`toggle`/`formula` to palette + inspector; add the salvaged **formula editor** (autocomplete + live preview + helper chips) for the `formula` type. `member`/calendar options appear only when the definition is project-attached.
- **Runtime (ported, split from the 4,479-line monolith)**:
  - `FlowRecordsView.vue` — table + kanban + filters/sort + saved views + summary (from `Management.vue` 564–749, fns 1598–1745/2442–2600).
  - `FlowRecordDrawer.vue` — add/edit record (renders inputs per our field type, **enforces our validation rules**) + actions timeline + calendar (from 850–1084, fns 2410–2683).
  - Reuse `helpers.ts`/`constants.ts`/`types.ts` ported to `composables/flow/`.
- **Mount points** (share `FlowRecordsView` via a context prop):
  - App-level: `FlowControl` list → open definition → records view (admin).
  - Project: new `ProjectTabs/CustomApps.vue` (replaces their `Management.vue` tab) listing project-attached definitions → records view.
- Status interplay: when a definition has a workflow, `current_status` is a first-class **column/filter** in the records view, and the drawer shows the approval stepper + 承認/差し戻し alongside the actions timeline.

## Permissions (kintone-style, 3 levels)

**Anyone can create apps.** Per-app permissions (not a global build gate) govern everything else. Three levels: **app-level (specified below), record-level, field-level (to be designed next).** Entrance lives in `Global/SideMenu.vue` (global, all users); the カスタムアプリ list shows apps you created or have ≥View on.

### App-level (agreed 2026-06-30)

Replaces `flow_shares` + `flow_definitions.visibility` and the old `canManageFlows` gate.

```
flow_app_groups            id, name, sort_order, created_by, timestamps
flow_app_group_permissions id, app_group_id, subject_type, subject_id, <7 bools>, sort_order
                           -- default permission TEMPLATE; copied into a new app's
                           -- flow_app_permissions on creation (then app can override)
flow_definitions           + app_group_id (nullable)        -- + project_record_id (earlier)
flow_app_permissions
  id, flow_definition_id,
  subject_type enum('creator','everyone','user','position',
                    'project_member','project_manager','project_director'),
  subject_id   nullable,            -- user_id / position_id; null for creator/everyone/project_*
  can_view, can_add, can_edit, can_delete, can_manage, can_import, can_export  (bool),
  sort_order   int                  -- precedence; FIRST-MATCH-WINS top-down
```

- **7 permissions → operations:** view=see app+records · add=create record · edit=edit values · delete=delete record · **manage**=edit definition (fields/statuses/views/**permissions**) · import/export=CSV. 1:1 with the ported controller.
- **Resolve effective perms** (`FlowService::effectivePermissions(user, def)`): walk rows by `sort_order`; the FIRST row the user matches wins (creator→is created_by; user→id; position→position_id; everyone→always; project_*→membership/role of the attached project). No union.
- **Defaults on create:** one `creator` row, all-on. Everyone gets nothing until added.
- **Subjects now:** user + position (one-by-one or by position) + special `creator`/`everyone`; `project_*` only when project-attached. (groups/departments later.)
- **Builder:** the 共有設定 tab becomes **アクセス権** — the kintone matrix (subject rows × 7 checkboxes, add user/position, drag-reorder, App-group selector).
- **App groups:** organizational grouping in the sidebar **+ a default permission template** (`flow_app_group_permissions`). On app creation, the group's template rows are copied into the app's `flow_app_permissions` (plus the creator row); the app can then override freely.

### Record-level (agreed 2026-06-30)

kintone "Permissions for records" model: a list of **permission sets**, evaluated top-down, **first-match-wins per record**. Each set = target conditions (which records) + grants (who gets View/Edit/Delete). Record-level only **narrows** app-level (`effective = app ∩ record`); it covers View/Edit/Delete only (Add is app-level).

```
flow_record_permission_sets        id, flow_definition_id, match_mode('all'|'any'), sort_order
flow_record_permission_conditions  id, set_id, source('field'|'creator'|'updater'|'status'),
                                    field_id?, operator, values(json)
flow_record_permission_grants      id, set_id,
                                    subject_type('everyone'|'user'|'position'|'field'|'project_member'|'project_manager'|'project_director'),
                                    subject_id?,          -- user/position id, OR flow_field_id when type='field' (dynamic: whoever is in that record's user/member field)
                                    can_view, can_edit, can_delete, sort_order
```

**Resolution** `FlowService::recordPermissions(user, record)`:
1. If no sets → governed entirely by app-level (open default).
2. Find the FIRST set whose conditions match the record (`match_mode` all/any). Conditions: `creator`/`updater`/`status`/field `includes_any|includes_all|equals|is_empty|not_empty` values.
3. A record matching **no set** → falls back to app-level (open).
4. Within the matched set: the user's record perms = **union** of all grant rows they match (everyone always; user id; position; field-ref = user is in the record's referenced user/member field). No match → denied.
5. `effective = app-level ∩ record-level` for view/edit/delete.

**Operators (phase-1 core):** includes any of · includes all of · equals · is empty · not empty (over creator/updater/status/select-field/user-field). Numeric/date ranges later.

**Enforcement:** View must be a **query filter** (translate condition sets → joins/subqueries on `flow_record_values` + `current_status_id`/`created_by`) so users only fetch permitted rows; Edit/Delete checked in-memory per record. This is the heaviest implementation piece.

### Field-level (agreed 2026-06-30)

kintone "Permissions for fields": per target field, subjects (user/position/everyone/field-ref) × View/Edit. Two independent layers that **intersect**: subject-based (security) + status-based (`flow_status_field_rules`, workflow stage).

```
flow_field_permissions  id, flow_definition_id, field_id,
                        subject_type('everyone'|'user'|'position'|'field'|'project_member'|'project_manager'|'project_director'),
                        subject_id?, can_view, can_edit, sort_order
```
`fieldPermissions(user, field)`: no rows → `{view:true, edit:true}`; else union of matched subject rows (none matched → denied). Field-ref subjects resolve per record.

### Combined effective access (all three levels)

```
canViewRecord(u,r)    = app.view(u)   ∧ record.view(u,r)
canEditRecord(u,r)    = app.edit(u)   ∧ record.edit(u,r)
canDeleteRecord(u,r)  = app.delete(u) ∧ record.delete(u,r)
canAddRecord(u)       = app.add(u)
canViewField(u,r,f)   = canViewRecord(u,r) ∧ fieldPerm.view(u,f) ∧ statusRule(r.status,f) ≠ hidden
canEditField(u,r,f)   = canEditRecord(u,r) ∧ fieldPerm.edit(u,f) ∧ statusRule(r.status,f) = editable
manage / import / export = app-level only
```
Workflow transitions (advance/差し戻し) remain governed by the status-assignee model, orthogonal to the above.

### Full permission-related schema (Phase A additions)

`flow_app_groups`, `flow_app_group_permissions`, `flow_app_permissions`, `flow_record_permission_sets`, `flow_record_permission_conditions`, `flow_record_permission_grants`, `flow_field_permissions` — plus `flow_definitions.app_group_id` / `project_record_id`. (Runtime tables `flow_views`, `flow_record_actions`, and `flow_record_values` extra columns are listed earlier.)

## Phased sequence

- **A — Schema unification**: migrations 1–5 + model updates (FlowDefinition project relation; FlowField formula/hidden/result_type; FlowRecordValue extra casts; new FlowView, FlowRecordAction).
- **B — Backend runtime**: port controller methods + `FlowFormulaEvaluator` + auth generalization; wire routes (app-level; project context via definition).
- **C — Builder element types + formula editor** (FE).
- **D — Runtime FE**: `FlowRecordsView` + `FlowRecordDrawer` (table, cells, filters/sort, views, kanban, validation enforcement, actions timeline).
- **E — Project attachment**: `project_record_id` UI + `CustomApps.vue` project tab + `member` field scoping + project-member auth end-to-end.
- **F — Polish**: CSV import/export, field-impact, formula validation messages, calendar sync, カスタムアプリ rebranding of nav/labels.

## Risks / open items

- `flow_records` already has `current_status_id` + assignees; their records have none → unified record simply has **optional** status. Fine.
- Column naming: keep ours (`value_numeric`); rename during port.
- Splitting the 4,479-line `Management.vue` during the port is extra effort but worth it for maintainability.
- The same upstream commit also refactors `Finance` routes — explicitly out of scope; do not port.
- Independent of the uncommitted Learning work.
