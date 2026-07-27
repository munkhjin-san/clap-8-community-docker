# HANDOFF — Flow (カスタムアプリ) + Smart Notifications

> Session handoff for resuming work on the Flow feature. Read this first, then drill into the
> referenced files. Last updated: 2026-07-27.

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
- `FlowControl.vue` — portal (`/apps`): cards/table, pin, sort, search, 対応待ち icon
  (`FlowPendingMenu.vue`) then bell (`FlowBellMenu.vue`) — both popups teleport to `<body>` with
  fixed positioning (overflow-clipping table wrapper + side-menu paint order make in-place
  absolute dropdowns unusable; see §1 conventions).
- `FlowBuilder.vue` + tabs: `FlowFormTab` (+ `FlowFieldInspector`), `FlowStatusTab`, `FlowViewTab`,
  `FlowToolsTab` (PDF designer), `FlowPermissionTab`, `FlowAuditLogTab`. Tab = route param
  (`/apps/builder/:flowId?/:tab?`); edit lands on Form tab, tab switches use `router.replace`.
- `FlowRecordsView.vue` — record list (server-mode SQL pagination unless the app has record-level
  permission sets → client mode). **List state lives in the URL** so it survives open→back:
  `?view=` (view id) + `?sf=`/`?sd=` (sort field/direction) + `?f=` (ad-hoc filter, base64url of a
  compact array form — `encodeAdhoc`/`decodeAdhoc` in the SFC). All of it is seeded into the refs at
  declaration time, BEFORE the first fetch — do not regress that race fix (otherwise the first query
  goes out with the default view and the rows contradict the selector). `listQuery()` builds the
  param bag that record links carry.
- `FlowRecordDetail.vue` — record view/edit (`/apps/records/:flowId/edit/:recordId` by record_number,
  `/new`, duplicate = `/new?from={id}`). Comment/history side panel; unread-comment badge (§3).
  Header has ↑/↓ **neighbour navigation** (`nav.prev`/`nav.next` ride the detail payload — adjacent
  record_number in one indexed query, bounded walk as fallback); back returns to the list with the
  full `listQuery()` state intact.
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
- **Dropdowns/popups**: never rely on `position:absolute` + z-index inside portal/table containers —
  `.fc-table-scroll` (overflow) clips them and the side menu wins by paint order (its stacking
  context beats any z-index trapped in a card). Pattern: `<Teleport to="body">` + `position:fixed`
  coords from the trigger's rect, viewport clamp, close-on-scroll (see FlowBellMenu/FlowPendingMenu;
  `ItemMenu` has an opt-in `teleport` prop — pass it inside any scrolling container).
- `Badge.vue` digit centering is deliberate: `line-height/letter-spacing` pinned with `!important`
  (global sheet leaks 15px/1px), Arial for digits (Noto Sans JP's CJK ascent sinks them ~0.7px),
  `text-box: trim-both cap alphabetic` where supported. Consumers overriding position must reset
  `left` (base style hardcodes `left:33px`).

## 2. Feature log (all shipped & pushed unless noted)

Builder (fields/layout/validation/defaults/disabled), records (CRUD, duplicate via `?from=`,
comments+history), views (columns/filters/sort, `?view=` persistence), status flow (statuses,
actions, eligibility, per-status field rules), 3-level permissions (app rows first-match + creator
lockout guard; record sets; field), CSV export (encoding/scope, follows view+adhoc filter) /
import (mapping UI, grouped rows), PDF tools, kintone import, per-app audit log (record views,
exports w/ archived file, settings diffs, downloads), lookup incl. **field mappings** (copy source
values into real fields, strict type compat + scalar→text) and **system sources** (営業所), portal
polish (pin/sort/view toggle, 使い方 link), help documentation hub (`/help/documentation/app`,
29 articles + 22 screenshots; regenerate via `node scripts/help-screenshots.mjs` — needs demo app 37
and the local-only `/dev_screenshot_login/{user}` route, both must stay), 対応待ち counter (§3),
portal popup hardening 2026-07-25 (bell/対応待ち/ItemMenu dropdowns → teleport+fixed, Badge digit
centering — see §1 conventions; bell moved to card foot, pin icon 15px),
list state in the URL + record ↑/↓ navigation (§1 FlowRecordsView/FlowRecordDetail),
app-list perf (dropped `fields_count`/`statuses_count` — cards show 件 only),
mobile fixes (portal table width + name ellipsis, sheet toggle flush at the row edge),
docs refresh 2026-07-27 (see §6.3),
**encrypted `password` field type** (AccountVault; boolean-only payloads, audited fail-closed
reveal, excluded from export/search/views/formula/lookup/PDF/duplicate — see §7),
アプリ attention badge in the side menu (badge_summary → `BadgeService::flow()`).

## 3. CURRENT WORK — smart notifications + 対応待ち counter (built & verified)

Per-app bell badges on the portal, plus (2026-07-25) a separate live 対応待ち counter.
History note: 対応待ち was first dropped, then briefly built as an `assigned` notification event,
then **reverted the same day** — the owner ruled it a duty, not information (see spec below). Don't
re-add an `assigned` event type.

### Spec (owner-locked decisions)
- Events stored per recipient in `flow_notifications`: `comment` (record creator + past commenters),
  `new_record` (**everyone with view permission** on the app), `status_change` (record creator).
  Own actions never notify. CSV import writes **one grouped event** (`meta.count`, no record id).
- 対応待ち is **NOT a notification** (owner decision 2026-07-25): being the 作業者 is a duty, not
  info. It's a separate icon BEFORE the bell (`FlowPendingMenu.vue`, ⚠ exclamation-in-circle +
  count badge) —
  **live-computed, no stored rows, no read state, NO prefs** (you can't opt out of your job).
  Count = records whose current status has an action whose `eligible` EXPLICITLY names the user
  (`FlowService::hasExplicitPendingAction` — `everyone`/empty-eligible/manage-safety-net do NOT
  count; own records DO). Drops only when the record leaves the status. Piggybacked as
  `pending_actions` on `getFlowDefinitions`; popup list = `GET /flow_pending_actions/{definition}`.
  Same rule per record: `pending_action` on list rows (`serializeRecord` w/ user) → red dot inside
  the status pill in `FlowRecordsView` (`.rv-pdot`, white-ringed for colored pills).
  NB: the `flow_statuses.assignment_type` / `flow_record_assignees` snapshot subsystem is dead code
  (`waitingForUserQuery` too); the portal 対応待ち tab remains commented out.
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
- All event writes go through `FlowController::notifySafely` (try/catch + report) — they run after
  the main save committed, so a notify failure must never 500 an already-successful save. Keep new
  hooks inside it. (Queue/dispatchAfterResponse deliberately not used: shared host, no worker;
  revisit if fan-out cost grows ~10×.)
- Hooks: `AppCommentController::store` (flow_record branch), `FlowController::storeAppRecord`,
  `transitionAppRecord` (captures from/to names before `applyStatusAction`), `importRecords`
  (after commit), `getAppRecords` (markImportSeen), `respondWithRecordDetail` (markRecordOpened +
  `unread_comments` in payload), `getFlowDefinitions` (counts).
- FE: `FlowBellMenu.vue` (bell + popup + prefs), `FlowPendingMenu.vue` (対応待ち icon + popup),
  `Icons/Bell.vue`, wiring in `FlowControl.vue` (grid card foot + table row; pending icon first),
  badge + 5s timer in `FlowRecordDetail.vue`, `unread_notifications` / `pending_actions` types.
- 対応待ち backend: `FlowService::hasExplicitPendingAction` + `pendingActionRecords`,
  `FlowController::getFlowPendingActions`, counts in `getFlowDefinitions`.

### Verified
Service-level via tinker (fan-out 189 recipients w/ actor excluded, creator-only status, pref
opt-out, grouped import meta, open-clears) and full E2E in browser on demo app 37 (badge → popup →
prefs round-trip → event tap-through → each clearing rule incl. collapsed-panel hold + 5s clear).

### Known gaps / phase 2
- No pruning job yet (read rows accumulate — add scheduler delete of read_at > ~90d).
- Counts (`unread_notifications` AND `pending_actions`) refresh only on portal load (no socket push;
  existing badge/socket infra is the candidate).
- No notification center across apps; no live comment-count update while the record is open.
- Fan-out cost: new_record inserts one row per viewer — fine at company scale, revisit if usage grows.
- `pending_actions` cost: per status-flow app it loads every record sitting on an action-bearing
  status and evaluates eligibility in memory — measured 2026-07-25: ~7 queries / 6ms per app at toy
  scale. Mitigations already in: apps with no explicit-subject actions skip the scan; `values`
  eager-loaded only for `field` subjects. If it hurts later: short-TTL per-user count cache, or
  materialize assignees at transition time (= resurrect the dead `flow_record_assignees` design).
  Known N+1 left: `project_member/manager` eligible subjects query membership per record.

## 4. Environment & workflow

- Quality gates: `npm run typecheck` (vue-tsc) must pass. Pint: run only on NEW php files
  (`vendor/bin/pint <files>`) — FlowController/web.php have large pre-existing style debt; do not
  reformat them wholesale.
- After adding routes: `php artisan route:clear` (dev server caches; new routes 404 otherwise).
  `php artisan route:list` errors on a missing `AdminCostMasterController` — pre-existing, ignore.
- Verify via tinker scripts (write to a file, `php artisan tinker <file>`) + browser.

### ⚠️ TWO environments exist — detect which one you're on before anything else

Sessions have run against both. They differ in workflow AND in data, so **check first**:

```bash
grep -E '^APP_ENV|^APP_URL' .env
lsof -nP -iTCP:8000 -sTCP:LISTEN   # local dev server?
php artisan tinker --execute="echo config('database.connections.'.config('database.default').'.database');"
php artisan tinker --execute="App\Models\FlowDefinition::orderBy('id')->get(['id','name'])->each(fn(\$d)=>print(\$d->id.' '.\$d->name.PHP_EOL));"
```

- **Local dev (this machine, verified 2026-07-27)** — `APP_ENV=local`, DB `g4_prod_test` @ localhost,
  **dev servers running** on `:8000` (artisan serve) + `:5173` (vite). Apps 1/12/13/14/15/21/37 —
  i.e. the §5 data. FE changes hot-reload; no build needed. Browser via the in-app preview tools;
  `/dev_screenshot_login/{user}` logs a session in (local-only route). This is where the docs
  screenshot pipeline runs.
- **Shared host (`xs954629.xsrv.jp`)** — no dev servers: every FE change needs `npm run build` to
  show at `https://xs954629.xsrv.jp`. Browser testing via the Claude-in-Chrome extension on the
  owner's machine (they switch linked accounts to test different users). A 2026-07-25 session saw a
  near-empty app set there (1=システム資産 w/ status-flow test config eligible→user 604, 2=労使協定,
  3=re) — **§5's app ids do NOT apply there**.

Never assume the app ids in §5 without running the check above.
- Commit style: `type(flow): summary` + body; work stays on `main`; push only when asked. Remote
  sometimes gains commits between sessions (user commits from elsewhere) — fetch/rebase, never force.

## 5. Data hygiene (IMPORTANT)

> App ids below are the **local dev DB** (`g4_prod_test`) — confirmed 2026-07-27. On the shared host
> they mean nothing; run the §4 detection first.

- **App 15 (送付状-NEW) holds real client PII** — never dump its records into logs/screenshots.
  It currently carries a test lookup field "office" (reference → 営業所, mapping 住所→発送・返送場所)
  that the owner chose to keep; confirm before touching.
- **App 37 (備品購入申請)** = docs/demo app, fabricated data — KEEP (screenshot pipeline + testing).
  Its 承認待ち actions (承認する / 差し戻す) deliberately name **user 608** in `eligible` so the
  対応待ち icon has something to show in the docs screenshots — don't "clean" that back to empty.
  It also carries a `password` field (発注サイトのパスワード, value on record #3) for the
  password-field screenshots — same deal, keep it.
  App 1 (取引先マスタ) holds ~101 fabricated dummy records — keep.
- Test apps you create: fabricated data only, delete afterwards (records → values → fields → perms → app).
- Do NOT commit the pre-existing WIP in the tree: `.env.example`, `tsconfig.json`, `package.json`
  (pptxgenjs), Learning files, EmployeeContract models/migration, `docs/*-plan/analysis*.md`, `.claude/`.

## 6. Open threads

1. Notification phase 2 (pruning job, socket counts, notification center) — see §3 gaps.
2. システム更新情報 notice draft for the アプリ release was generated (SystemUpdateCreate format)
   but not yet entered/published — owner has the text. It predates the 対応待ち/通知 icons, so add
   a line about them before publishing.
3. **Docs ↔ UI drift is a recurring tax.** Fixed 2026-07-27 (項目数 claim removed, 対応待ちタブ
   article rewritten for the per-app icon, new 通知を受け取る article, URL-state + ↑/↓ nav noted,
   all 18 screenshots regenerated). Whenever portal/list/detail UI changes, re-check
   `resources/assets/help/help.documentation.ts` and re-run the pipeline — screenshots go stale
   silently. Regenerating the notification badge shots needs transient seeded events (see the
   script header).
4. ~~getFlowOptions debug leftover~~ — **resolved**: the owner committed it deliberately in
   `1039e5e8 feat(flow): filter users by ID`. `FlowController::getFlowOptions()` intentionally
   restricts the Flow user pickers / mention lists to `id > 105` and no longer sorts by name.
   Treat it as intended behaviour, not a leftover.

## 7. Encrypted `password` field (2026-07-27)

Credentials stored inside an app, encrypted at rest via the pre-existing **`AccountVault`**
(unchanged — same `ACCOUNT_VAULT_KEY` the asset vault uses, deliberately separate from `APP_KEY`).

**The load-bearing decision:** `FlowService::readFieldValue()` returns a **boolean** for secret types
("is one stored?"), never the ciphertext. Lists, record payloads, history diffs and formula context
are therefore safe by construction — a pipeline that forgets to check still cannot leak. Plaintext
exists in exactly one place: `POST /flow_secret_reveal`.

- **Reveal** = app view ∩ record view ∩ field view, and secret fields **fail closed**:
  `fieldPermissions()` grants view+edit to everyone when a field has no rows, which for a credential
  is the wrong default, so unconfigured ⇒ 管理 only (`FlowService::canRevealSecret`). Every reveal
  writes a `secret_reveal` audit entry; the value auto-hides after 30s.
  Note: once ANY field-permission row exists, managers lose reveal unless listed — consistent with
  ordinary fields, and a manager self-granting shows up in the settings audit diff.
- **Write contract** (`saveFieldValue`): `''`/absent = keep, a string = set, `['clear' => true]` =
  delete, and a bare **boolean = keep** (it's the marker we handed out on read).
- **必須** means "will a value exist after this save?" — `validateValues($fields, $values, $record)`.
- **Excluded** from CSV export, search, view columns/filters/sort, formulas, lookup copy (both ends),
  PDF, duplicate — via `FlowService::isSecret()` / `isSecretType()` in `types/flow.ts`. **CSV import
  is allowed** (encrypts on the way in) — product decision: the CSV file is the user's risk.
- **変更履歴** logs the action only (設定/変更/削除されました). A rotation reads true→true, so the write
  intent is taken from the submitted payload, not the diff.

Two bugs worth not reintroducing: echoing the boolean marker back on save encrypted the string `"1"`
(silently destroying the credential, with no diff to show for it), and rotations produced no history
entry at all.

Known follow-up: `ACCOUNT_VAULT_KEY` is missing from `.env.example`, and `AccountVault::decodeKey()`
type-hints `string`, so a fresh deploy TypeErrors on a null key rather than failing at boot.
