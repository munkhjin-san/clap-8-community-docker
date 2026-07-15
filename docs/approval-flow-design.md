# 申請・承認フロー — Design Document

Status: **Design / pre-implementation** (2026-06-30). No code yet. This doc captures the agreed model, schema, reuse strategy, UI, and phasing for review before build.

---

## 1. Overview

A kintone-style, **user-definable approval workflow** feature. Admins / 上長 / PM build a *flow definition* (a custom form + a status sequence + sharing rules); end-users create *flow records* that move through those statuses with assignees, history, and comments.

It lives in the admin control area as a **sibling to the フォーム (CustomForm) builder**, under `/admin_control`, gated by the same permission level (`isAdmin || isBoss || isPM`; boss = `position_id < 6`, PM = `position_id == 6`).

### Two-layer model (kintone "app vs. record")

- **Definition layer** — the reusable template: fields, statuses, sharing. Built by Admin/PM.
- **Instance layer** — one submitted application: current status, field values, assignees.

---

## 2. Requirements traceability

| # | Client requirement | Covered by |
|---|---|---|
| 1 | JP business-culture oriented | 差し戻し, 役職-based assignment, 稟議-style sequential approval |
| 2 | Admin/PM create custom flows | `flow_definitions` + builder UI, same gate as フォーム |
| 3 | Status-driven app | status engine over `flow_statuses` |
| 4 | Built-in 作成中 / 完了 | `flow_statuses.is_locked` bookends |
| 5 | Custom statuses + responsibility | `flow_statuses` + assignment rule (user/position) |
| 6 | kintone-like | builder + record UX |
| 7 | Custom form body, many input types | `flow_fields` (core type set), 12-col grid layout |
| 8 | History tracker | reuse polymorphic `UpdateLog` |
| 9 | Global comments per record | reuse polymorphic `AppComment` |
| 10 | Assignees see records on dashboard | `flow_record_assignees` + dashboard 対応待ち widget |
| 11 | Share by user / position | `flow_definitions.visibility` + `flow_shares` |

---

## 3. Locked decisions

| Decision | Choice | Note |
|---|---|---|
| Status topology | Linear + 差し戻し | Send-back to **immediately-previous status only**, required reason |
| Form field schema | **New dedicated tables** | Model the builder UX on `CustomFormCreate.vue` but do not reuse CustomForm tables |
| Field values | **Queryable EAV** | Typed value columns so records are filterable/searchable |
| Assignee resolution | **Snapshot at status entry** | Mirrors `IncidentAssignee`; drives dashboard queue |
| Field editability | **Per-status field rules** | 編集可 / 閲覧のみ / 非表示; missing rule defaults to 編集可 |
| Schema edits after records exist | **Free edits, best-effort** | Tolerant rule lookup; orphaned values harmless |
| Field types at launch | **Core set** | 短文 / 長文 / 数値 / 日付 / 時刻 / 選択 / ラジオ / チェック / ファイル / 見出し |
| Layout | **12-column grid** | `layout_row` + `layout_span` per field |
| Assignment targets | creator / user / position | `role` **deferred** until the static-auth refactor settles |

---

## 4. Data model

### Definition layer

```
flow_definitions
  id, name, description, created_by,
  visibility ENUM('limited','all_staff') default 'limited',
  is_active BOOL, timestamps, soft deletes

flow_fields                       -- the form body schema
  id, flow_definition_id,
  key, label, input_type,
  options JSON,                   -- choices for select/radio/checkbox
  is_required BOOL,
  order_number INT,
  layout_row INT, layout_span INT,-- 12-col grid placement
  depends_on JSON,                -- conditional display (later)
  timestamps

flow_statuses
  id, flow_definition_id,
  name, order_number,
  is_locked ENUM(null,'start','end'),     -- 作成中 / 完了
  assignment_type ENUM('creator','user','position'),
  assignment_target_id,                    -- user_id or position_id
  timestamps

flow_status_field_rules           -- per-status field permission matrix
  id, flow_status_id, flow_field_id,
  rule ENUM('edit','read','hide')
  -- absent row => 'edit' (best-effort default)

flow_shares
  id, flow_definition_id,
  user_id NULL, position_id NULL,          -- one or the other
  access_level ENUM('use','view'),         -- 利用可 / 閲覧のみ
  timestamps
```

### Instance layer

```
flow_records
  id, flow_definition_id, current_status_id,
  created_by, timestamps, soft deletes

flow_record_values                -- typed EAV for queryability
  id, flow_record_id, flow_field_id,
  value_text, value_numeric, value_date    -- populated by field type; indexed
  -- index value_numeric, value_date for range filters

flow_record_assignees             -- snapshot of responsibility per status entry
  id, flow_record_id, flow_status_id, user_id,
  source ENUM('user','position'), source_id,
  completed_at NULL,
  unique(flow_record_id, flow_status_id, user_id),
  index(user_id, completed_at)             -- "assigned to me" dashboard query
```

### Reused polymorphic infrastructure (no schema work)

- `update_logs` (`loggable`) — every status move + value change. Status changes write `action`, `old_value`/`new_value` = status names, `note` = 差し戻し reason.
- `app_comments` (`commentable`) — discussion thread, `mentioned_user_ids` for @mentions.
- `file_attachments` (`attachable`) — values of `file` fields.

---

## 5. Status engine behaviour

- **Advance**: current assignee approves → record moves to `order_number + 1`; engine re-snapshots that status's assignees into `flow_record_assignees`; writes `UpdateLog`.
- **差し戻し**: returns to `order_number - 1` only; **reason required** (stored on the `UpdateLog`); re-snapshots the previous status's assignees.
- **Snapshot**: on entering a status, resolve `assignment_type`/`target` to concrete user rows (a position expands to its current holders). Stable for dashboard + audit; does not auto-update on later org changes (accepted trade-off).
- **Bookends**: `作成中` (start) — applicant edits the body, assignee = creator. `完了` (end) — terminal, all fields read-only.

---

## 6. Permissions & visibility

- **Builder access**: `isAdmin || isBoss || isPM` (same as フォーム).
- **Definition visibility**: `visibility = all_staff` → any employee can create records. `visibility = limited` → only `flow_shares` targets (by user or by position) can, per `access_level` (`use` = create + act; `view` = read-only). Creator + admins always have access.
- **Record access** (model on `IncidentService` predicates): admin sees all; creator sees own; current/past assignees see records they're on; shared targets see per `access_level`.

---

## 7. UI

Builder is a single screen with four tabs (mockups produced in the 2026-06-30 design session):

1. **フォーム** — left element palette → drag onto a 12-column workbench; fields placed side-by-side or stacked; width buttons set `layout_span`; inspector edits label/key/required/width. DnD via `sortablejs` + `@vueuse/integrations` `useSortable` (Vuetify 3.7).
2. **フロー設定** — vertical status pipeline (locked 作成中/完了 bookends, custom statuses between, 差し戻し one step back); per-status inspector for assignee rule + field-permission matrix.
3. **共有設定** — visibility scope (limited / all-staff) + share list editor (add by user or position, access level per row).
4. *(record runtime)* **Record detail** — status stepper, action banner (承認 / 差し戻し with reason), form body rendered with current-status permissions, comments, history timeline. Dashboard **対応待ち** widget = the `(user_id, completed_at)` query, sibling to `DashboardIncident.vue`.

---

## 8. Reuse map (build on these)

| New piece | Model on |
|---|---|
| `FlowController` / `FlowService` | `IncidentController` / `IncidentService` |
| Record detail Vue | `IncidentDetailModal.vue` |
| Dashboard widget | `DashboardIncident.vue` |
| status + assignee tables | `IncidentStatus` / `IncidentAssignee` |
| Closest existing precedent (status + form + history) | `ProjectAssignRecord` (+ `ProjectAssignStatusHistory`, `ProjectAssignAction`) |
| Builder DnD / modal | `CustomFormCreate.vue`, `Global/Modal.vue` |

---

## 9. Phasing

- **Phase 1 — engine + one usable flow**: definitions, fields (core types incl. file), statuses w/ user+position assignment + per-status rules, records, EAV values, advance + one-step 差し戻し, history + comments wired, `flow_shares`/visibility, dashboard 対応待ち widget.
- **Phase 2 — kintone-grade record list**: search / filter / sort records by field values (the EAV payoff), saved views.
- **Phase 3 — later**: role-based assignment (post auth-refactor), user/position picker & computed/linked field types.

---

## 10. Risks & open items

- **Net-new FE**: grid-aware drag-and-drop (side-by-side + width resize). Current フォーム builder is vertical-stack only — this is the main new front-end engineering.
- **`role` assignment** depends on the in-flight static-auth refactor; deferred to Phase 3.
- **Open questions** (not yet decided): notification channel for assignees (in-app only vs. email), whether 完了 records can be reopened, definition versioning if "free-edit" proves too loose in practice.

---

*Independent of the uncommitted Learning remodel + employee-contract work — different tables and components.*
