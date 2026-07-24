# HANDOFF — Flow (カスタムアプリ) + Smart Notifications

> Session handoff for resuming work on the Flow feature. Read this first, then drill into the
> referenced files. Last updated: 2026-07-24.

## 1. What Flow is

kintone-style **custom app builder** (メニュー名「アプリ」, routes `/apps/**`): users build form-based
apps (fields + records + views), with an optional 申請・承認 status flow. Japanese-first UI. Lives on
`flow_*` tables. Released to **all users** (side-menu entry is ungated); per-app permissions govern
everything.

### Backend map
- `app/Http/Controllers/FlowController.php` — nearly everything (definitions, records CRUD, views,
  transitions, CSV import/export, PDF tools, audit log, reference/lookup, system sources, notifications).
- `app/Services/FlowService.php` — permissions (app/record/field), record values (EAV), formula
  evaluation, status actions, filters/sort/search query building.
- `app/Services/FlowNotificationService.php` — notification events + read state (see §3).
- `app/Support/FlowSystemSources.php` — registry of built-in reference sources (currently `office` =
  営業所, backed by `officeRecord`). Add a source = one registry entry.
- Storage: `flow_record_values` typed columns (value_text/numeric/date/datetime/boolean/json) routed
  by input_type. Reference values = snapshot `{id, number, label}` in value_json.
- Comments are the generic polymorphic `app_comments` (`AppCommentController`), `commentable_type = flow_record`.

### Frontend map (`resources/js/components/AccountControl/FlowControl/`)
- `FlowControl.vue` — portal (`/apps`): cards/table, pin, sort, search, bell (`FlowBellMenu.vue`).
- `FlowBuilder.vue` + tabs: `FlowFormTab` (+ `FlowFieldInspector`), `FlowStatusTab`, `FlowViewTab`,
  `FlowToolsTab` (PDF designer), `FlowPermissionTab`, `FlowAuditLogTab`. Tab = route param
  (`/apps/builder/:flowId?/:tab?`); edit lands on Form tab, tab switches use `router.replace`.
- `FlowRecordsView.vue` — record list (server-mode SQL pagination unless the app has record-level
  permission sets → client mode). Selected view persists via `?view=` (seeded into `activeViewId`
  BEFORE the first fetch — do not regress this race fix).
- `FlowRecordDetail.vue` — record view/edit (`/apps/records/:flowId/edit/:recordId` by record_number,
  `/new`, duplicate = `/new?from={id}`). Comment/history side panel; unread-comment badge (§3).
- `FlowFieldInput.vue` — every field input incl. lookup picker (app target or system source).
- `FlowSearchSelect.vue` — reusable searchable select (IME-aware, keyboard nav, `group` option draws
  a divider between option groups). Used everywhere a long list needs picking.
- Types in `resources/js/types/flow.ts`. Options cache: `store/flowOptions.ts` (users/positions/projects).

### Conventions that bite
- Theme = CSS vars only (`--primary-color`, `--sub-color`, `--bg3`, `--formBorder`, `--background-color`,
  …) set from `resources/assets/theme.json`; localStorage `dark`: `'1'` dark / `'2'` light / else OS.
  Never hardcode grays for text (dark mode).
- A **global stylesheet leaks into components**: content-box box-sizing, button rules with
  `position:absolute; top:50px`, letter-spacing 1px. New components must pin `box-sizing: border-box
  !important`, `position`, `inset`, `letter-spacing` explicitly (see FlowSearchSelect/FlowBellMenu CSS comments).
- `useApi()` (`composables/api.ts`): non-cancel errors show a dialog then **re-throw**; pass
  `{ silent: true }` to suppress. `abort(403)` renders as the generic 404/権限 message.
- Server admin checks must use `active_user()` (linked-account aware), never bare `Auth::user()`.
- Design rules: B/W+gray (no blue), neutral chips + green dot for "unread/new", square-ish corners,
  `Badge.vue` for counts, LoaderButton for async submits.
- Vue gotcha that has bitten twice: `computed`/watch sources evaluated during setup must not
  reference consts declared later in the file (TDZ). Watch declaration order.

## 2. Feature log (all shipped & pushed unless noted)

Builder (fields/layout/validation/defaults/disabled), records (CRUD, duplicate via `?from=`,
comments+history), views (columns/filters/sort, `?view=` persistence), status flow (statuses,
actions, eligibility, per-status field rules), 3-level permissions (app rows first-match + creator
lockout guard; record sets; field), CSV export (encoding/scope, follows view+adhoc filter) /
import (mapping UI, grouped rows), PDF tools, kintone import, per-app audit log (record views,
exports w/ archived file, settings diffs, downloads), lookup incl. **field mappings** (copy source
values into real fields, strict type compat + scalar→text) and **system sources** (営業所), portal
polish (pin/sort/view toggle, 使い方 link), help documentation hub (`/help/documentation/app`,
27 articles + screenshots; regenerate via `node scripts/help-screenshots.mjs` — needs demo app 37
and the local-only `/dev_screenshot_login/{user}` route, both must stay).

## 3. CURRENT WORK — smart notifications (built, verified, committed with this file)

Per-app bell badges on the portal. **No 対応待ち logic** (deliberately dropped by owner).

### Spec (owner-locked decisions)
- Events stored per recipient in `flow_notifications`: `comment` (record creator + past commenters),
  `new_record` (**everyone with view permission** on the app), `status_change` (record creator).
  Own actions never notify. CSV import writes **one grouped event** (`meta.count`, no record id).
- Prefs: `flow_notification_prefs`, keys `comment_own / comment_participated / new_record /
  status_change`, **all default ON**, sparse rows (only deviations). Any viewer can toggle their own,
  per app, from the bell popup gear.
- Bell popup is **view-only** (never marks read). Tap event → record detail.
- Clearing rules:
  - `new_record` / `status_change` → cleared when the record is opened
    (`respondWithRecordDetail(logView:true)` → `markRecordOpened`).
  - grouped import events → cleared when the app's **record list** is opened (`markImportSeen`).
  - `comment` → cleared only after the comment tab has been **actually visible ~5s**
    (`FlowRecordDetail` watches `commentVisible` = tab active AND panel expanded / mobile sheet open;
    timer → POST `/flow_notification_comments_read`). Badge rides the comment tab icon, or the
    collapsed reveal chevron when the panel is closed.
- Badge = raw unread count piggybacked on `getFlowDefinitions` (`unread_notifications`); popup list =
  `GET /flow_notifications/{definition}` (latest 30 + prefs); pref save = `POST /flow_notification_pref`.

### Files
- `database/migrations/2026_07_23_100000_create_flow_notifications_tables.php`
- `app/Models/FlowNotification.php` (UPDATED_AT null), `app/Models/FlowNotificationPref.php`
- `app/Services/FlowNotificationService.php` — all write/read logic; recipient resolution for
  new_record loops active users × `effectiveAppPermissions` in memory (company scale, bulk insert).
- Hooks: `AppCommentController::store` (flow_record branch), `FlowController::storeAppRecord`,
  `transitionAppRecord` (captures from/to names before `applyStatusAction`), `importRecords`
  (after commit), `getAppRecords` (markImportSeen), `respondWithRecordDetail` (markRecordOpened +
  `unread_comments` in payload), `getFlowDefinitions` (counts).
- FE: `FlowBellMenu.vue` (bell + popup + prefs), `Icons/Bell.vue`, wiring in `FlowControl.vue`
  (grid card + table row), badge + 5s timer in `FlowRecordDetail.vue`, `unread_notifications` type.

### Verified
Service-level via tinker (fan-out 189 recipients w/ actor excluded, creator-only status, pref
opt-out, grouped import meta, open-clears) and full E2E in browser on demo app 37 (badge → popup →
prefs round-trip → event tap-through → each clearing rule incl. collapsed-panel hold + 5s clear).

### Known gaps / phase 2
- No pruning job yet (read rows accumulate — add scheduler delete of read_at > ~90d).
- Counts refresh only on portal load (no socket push; existing badge/socket infra is the candidate).
- No notification center across apps; no live comment-count update while the record is open.
- Fan-out cost: new_record inserts one row per viewer — fine at company scale, revisit if usage grows.

## 4. Environment & workflow

- Quality gates: `npm run typecheck` (vue-tsc) must pass. Pint: run only on NEW php files
  (`vendor/bin/pint <files>`) — FlowController/web.php have large pre-existing style debt; do not
  reformat them wholesale.
- After adding routes: `php artisan route:clear` (dev server caches; new routes 404 otherwise).
  `php artisan route:list` errors on a missing `AdminCostMasterController` — pre-existing, ignore.
- Verify via tinker scripts (write to a file, `php artisan tinker <file>`) + the in-app browser
  (dev servers on :8000/:5173 usually already running; don't start your own).
- Commit style: `type(flow): summary` + body; work stays on `main`; push only when asked. Remote
  sometimes gains commits between sessions (user commits from elsewhere) — fetch/rebase, never force.

## 5. Data hygiene (IMPORTANT)

- **App 15 (送付状-NEW) holds real client PII** — never dump its records into logs/screenshots.
  It currently carries a test lookup field "office" (reference → 営業所, mapping 住所→発送・返送場所)
  that the owner chose to keep; confirm before touching.
- **App 37 (備品購入申請)** = docs/demo app, fabricated data — KEEP (screenshot pipeline + testing).
  App 1 (取引先マスタ) holds ~101 fabricated dummy records — keep.
- Test apps you create: fabricated data only, delete afterwards (records → values → fields → perms → app).
- Do NOT commit the pre-existing WIP in the tree: `.env.example`, `tsconfig.json`, `package.json`
  (pptxgenjs), Learning files, EmployeeContract models/migration, `docs/*-plan/analysis*.md`, `.claude/`.

## 6. Open threads

1. Notification phase 2 (pruning job, socket counts, notification center) — see §3 gaps.
2. システム更新情報 notice draft for the アプリ release was generated (SystemUpdateCreate format)
   but not yet entered/published — owner has the text.
3. Docs articles exist for all topics; screenshots regenerate via the pipeline when UI changes.
