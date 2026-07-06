# Capability migration — execution diary

Chronological log of the `blade → capability` migration (Stage 0+) executing the model in
`docs/capability_authorization_model_adr.md`. Newest entries at the bottom.

---

## 2026-06-26 — Stage 0a: backend `blade → capability` rename

**Scope:** terminology rename only; capability KEY STRINGS (`app.project`, `project.approve`, …) unchanged — no behavior change.

**Done:**
- `git mv` 3 class files: `CommunityBladeCatalog → CommunityCapabilityCatalog`, `EnsureCommunityBlade → EnsureCommunityCapability`, `EnforceAppBlade → EnforceAppCapability`.
- Global token rename across `app/` + `routes/`: those class names + `CONTROLLER_BLADE → CONTROLLER_CAPABILITY` (0 old refs remain).
- Catalog internals: `blade()` method → `capability()`; JSON group key `'blades' => … → 'capabilities' =>`; `$group['blades'] → ['capabilities']`; doc/comments.
- `CommunityContext::can(string $blade)` → `$capability` (+ comment).
- Middleware param/comment cleanups in both renamed middleware (incl. usage example `capability:app.project`).
- `app/Http/Kernel.php` aliases: `'blade' => … → 'capability'`, `'app.blade' => … → 'app.capability'`.
- `routes/web.php`: `"app.blade"` → `"app.capability"`; all `blade:x` → `capability:x` (6 usages); stray comment.
- `composer dump-autoload`.

**Verified:** all touched files `php -l` clean; autoload OK; tinker smoke — `CommunityCapabilityCatalog::groups()` returns the new `capabilities` key (legacy `blades` gone), `keys()` = 18 capability keys, sample `app.project,app.schedule,…` intact.

**Contract note:** `groups()[].capabilities` is consumed by the frontend `CommunityPermissionControl.vue` (was `group.blades`). Until Stage 0b (frontend) lands, the permission-matrix UI reads a now-renamed key — must do FE next.

**Next:** Stage 0b — frontend rename (`router.ts` ROUTE_BLADE, `dashboardCards.ts` CARD_APP_BLADE, `CommunityPermissionControl.vue` group.blades→capabilities + Blade* types/vars, `auth.ts`). FE build verification pending (can't build here).

---

## 2026-06-26 — Stage 0b: frontend `blade → capability` rename

**Done (identifiers only; capability key strings `app.project` etc. unchanged):**
- `resources/js/store/auth.ts`: `can(blade)` param → `can(capability)` + comment.
- `resources/js/router.ts`: `ROUTE_BLADE → ROUTE_CAPABILITY`, loop var `blade → capability`, comment.
- `resources/js/config/dashboardCards.ts`: `CARD_APP_BLADE → CARD_APP_CAPABILITY`, var `blade → capability`, comments.
- `resources/js/components/AccountControl/CommunityControl/CommunityPermissionControl.vue`: `group.blades → group.capabilities` (matches the renamed backend JSON key — contract now consistent), types `BladeItem/BladeGroup → CapabilityItem/CapabilityGroup`, `bladeGroups → capabilityGroups`, `totalBlades → totalCapabilities`, `toggleBlade → toggleCapability`, template loop var + `blade.* → capability.*`, function params, comments.

**Verified:** grep — zero auth-`blade` identifiers remain in `resources/js`; capability key strings (`app.project`, `app.timesheet`, `project.approve`) intact; BE `groups()[].capabilities` ↔ FE `group.capabilities` contract consistent.

**NOT verified here (env can't build Vue/TS):** `npm run dev`/build compile + the permission-matrix UI render. → user to confirm.

**Stage 0 (rename) COMPLETE.** Behavior unchanged by design. Catalog = `CommunityCapabilityCatalog`; middleware aliases = `capability:` / `app.capability`; everything reads "capability".

**Next (Stage 0c / 1):** "solidify can()" — decide the PHP call API (`$user->can('x')` via a Gate that routes to capabilities, vs an explicit `$user->hasCapability('x')`), since Laravel's native `$user->can()` hits Gate/policies, not our capability set. Then Stage 1 authority-capability fallback retirement, then per-feature expansions (Refresh = `benefit.refresh`).

---

## 2026-06-26 — Stage 0c: canonical PHP API `User::hasCapability()` (user chose option 2)

**Done:** added `User::hasCapability(string $capability): bool` → `app(CommunityPermissionService::class)->can($capability, $this)`. This is the PHP counterpart to frontend `auth.can(capability)`. Use it instead of position_id / hardcoded-id checks going forward. (Chose explicit method over a Gate fallback — no `$user->can()` magic.)

**Verified:** board → `project.approve`=1, `app.project`=1; 正社員(11) → `project.approve`=0, `app.project`=1; admin(610) → all=1 incl. unseeded `benefit.refresh` (admin bypass). Routes through community roles correctly. User.php lint clean.

### ⚠️ KEY FINDING for Stage 1 (not fixed yet — behavior change, needs sign-off)
`app()->bound(CommunityPermissionService::class)` returns **FALSE** (the service is auto-resolvable but never explicitly registered). So the existing predicates `isAdmin/isBoss/isPM/isPartnerScope/isRegisteredScope` — which gate on `app()->bound(...)` — currently **fall through to their `position_id`/`id` fallback in normal runtime, NOT the community role system.** Proof: a board user's `isBoss()` returns 1 via `position_id < 6`, while `CommunityPermissionService::can('project.approve', $u)` (resolved directly) also returns 1 — they agree only because data is seeded-aligned.
- Consequence: backend authority gates are effectively **position-based today**, despite the role infrastructure. The community-role system is engaged on the FRONTEND (auth payload via CommunityContext) but DORMANT in these backend predicates.
- `hasCapability()` deliberately bypasses that guard (resolves the service directly), so it DOES use community roles — that's why it's the canonical path.
- **Stage 1 fix (pending user decision):** either register `CommunityPermissionService` (so `bound()` is true) or change the predicates' guard to resolve directly (like hasCapability) — then retire the `position_id` fallbacks. This FLIPS backend predicates from position→role, a real behavior change → do supervised.

---

## 2026-06-26 — Stage 1: flip backend predicates position → community-role

**Pre-flight audit (the gate):** compared current runtime answer (position/id fallback) vs role-based (service) for all **198 active users**, all 7 guarded methods:
- isAdmin / isPM / isRegistered / isPartner / canManageTimesheets / canHrApprove → **0 mismatches**.
- isBoss → **1 mismatch: user 608** (admin, no position set) — role=1 (admin role holds `project.approve`), position=0. Benign / more-correct (an admin should pass authority gates). Accepted.

**Done:** rewrote all 7 guarded `User` methods (`isAdmin/isBoss/isPM/isPartnerScope/isRegisteredScope/canManageTimesheets/canHrApprove`) to **delegate directly to `CommunityPermissionService`**, dropping the broken `app()->bound()` guard (it resolved false → predicates were silently running on the `position_id` fallback) and the duplicated `position_id`/`id` fallbacks. `canManageTimesheets` kept its `work_authority===1` short-circuit. Position fallback now lives in ONE place (inside the service, as the membership-less safety net) instead of duplicated in User.php.

**Verified:** User.php lint clean; 0 bound-guards remain; runtime — 608 `isBoss`=1 (the audited flip), board `isBoss`=1, pm `isPM`/`canManageTimesheets`=1, registered `isReg`=1. **Backend authority predicates are now community-role-authoritative** (were position-based at runtime before). The only effective change is the 1 benign 608 case.

**Remaining fallback:** the service's internal `position_id`/`id` fallback (for users with NO community membership) — harmless today (all users have seeded memberships). Fully retiring it = "no membership → deny", deferred until every user is guaranteed a membership.

**Stage 1 COMPLETE.** Next: per-feature expansions — Refresh pilot (`benefit.refresh`: assign to qualifying roles, swap `ELIGIBLE_POSITION_IDS=[6,11,12,16]` → `hasCapability('benefit.refresh')`).

---

## 2026-06-26 — Stage 2 (Refresh pilot): first employment-role expansion

**Decisions (user):** keep admin-bypass for benefits (admins +2 vs old, accepted); build & run end-to-end.

**Done:**
- Catalog: new `benefits` group + `benefit.refresh` (kind `benefit`); `roleDefaults()` adds employment roles (`regular_employee`/`contract_employee`/`project_leader`/`transferred_employee`) and grants `benefit.refresh` to regular/contract/PL + pm + hr + admin (admin gets full set).
- `User::scopeWhereHasCapability($q,$cap)` — query counterpart to `hasCapability()` (whereJsonContains on the membership role's capabilities), for whereIn/list contexts.
- Migration `2026_06_26_000004_split_member_into_employment_roles.php` (ran): created the 4 employment roles (cloned member's caps + refresh for eligible), granted `benefit.refresh` to pm/hr/admin, **re-seeded** all `member` memberships to their employment role by `position_id` (11→regular, 12→contract, 16→PL, 13→transferred; unmapped stay `member`). down() reverts.
- Swapped checks: 6 per-user `in_array(position, ELIGIBLE_POSITION_IDS)` → `$user->hasCapability('benefit.refresh')` (RefreshService 296/430/587, RefreshAutoAllocation 100/141/249 — incl. job context); 1 query `whereIn('position_id', ELIGIBLE_POSITION_IDS)` → `->whereHasCapability('benefit.refresh')` (RefreshService 393). Removed the unused `ELIGIBLE_POSITION_IDS` const from both files.

**Verified:** all lint clean; migration DONE; eligibility (active users) — OLD position [6,11,12,16] = 70; NEW per-user `hasCapability` = 72; NEW query `whereHasCapability` = 72. Per-user == query (paths agree); 72 = 70 + 2 admins (accepted bypass divergence). Roles: regular_employee 74 / contract_employee 56 / project_leader 4 / transferred_employee 11 / member(catch-all) 13 (totals incl. inactive).

**Pattern proven end-to-end**: catalog capability → role assignment + re-seed → `hasCapability()` / `whereHasCapability()` swap. This is the template for the remaining employment migrations (shift-type rules, lunch-challenge). FE: permission-matrix auto-renders the new `benefits` group + the new roles (data-driven) — build-check pending user.

**Next candidates:** shift-type rules (ShiftService — `==12` 契約社員, `<=11`, `==16`; parametric, may need `shift.*` sub-capabilities), lunch-challenge (OpenAiController `<13 || ==16`). Each = its own expansion using this template.

---

## 2026-06-26 — Stage 2 (Lunch-challenge expansion)

**Done:**
- Catalog: added `benefit.lunch_challenge` to the `benefits` group; `roleDefaults()` grants it to `board, pm, regular_employee, contract_employee, project_leader, hr, admin` (NOT transferred_employee/registered/partner/member).
- Migration `2026_06_26_000005_grant_lunch_challenge_capability.php` (ran): grants the capability to those 7 roles (reversible).
- Swapped BOTH eligibility paths in OpenAiController (found a second one):
  - per-user `isLunchChallengeEligibleUser()` — position part `(<13 || ==16)` → `$user->hasCapability('benefit.lunch_challenge')` (kept deleted/retire/partner/hide filters).
  - **query** `lunchChallengeTargetUserIds()` (line ~772) — `where(position_id<13 || ==16)` → `->whereHasCapability('benefit.lunch_challenge')`.

**Verified:** lint clean; no `position_id` left in OpenAiController; both paths return identical sets (74 = 74).

**⚠️ Intentional behavior change (flagged):** old lunch-eligible = 80, new = 74. The **6 dropped are dummy/system accounts** with `position_id = null` (家族, 関係者, 知人, 推し, お知らせアカウント, rrr) — old logic included them via the `(int)null < 13` artifact. They are NOT employees (the codebase already ng-lists these exact names in AdminWorkController), so excluding them is more correct. Reversible (migration down + uncommitted) if undesired.

**Remaining (the parametric misfit):** shift-type rules in ShiftService — `==12`(契約社員 excludes shift types [19-26]), `<=11 || ==16`(privileged shift set, + general_position C-G sub-check). These are NOT boolean eligibility; they map shift-type LISTS to employment type. Needs a design decision: `shift.*` sub-capabilities per shift-type group, or role/position config-data. NOT yet done — flag for user.

---

## 2026-06-26 — Shift-type rules: design DECIDED, implementation DEFERRED

**User decision:** shift-type access = **per-role config-data** (NOT capabilities). Each Role record carries its own configurable selectable shift-type set (`community_roles.shift_types`, eventually admin-editable in the role UI). Multi-community-flexible — each community's roles pick their own shift types.

**Deferred + gated:** `general_position` (C–G) scope is deferred by the user too, and it's **intertwined** with shift logic — `ShiftService::applyPositionRules` nests `isPrivilegedPosition` (general_position) *inside* the `position<=11||==16` branch, so the allowed shift set varies by general_position *within* a role. A per-role `shift_types` config alone can't reproduce current behavior until general_position is handled. → Do shift config-data + general_position together as one dedicated piece, later.

**State:** `ShiftService` keeps its `position_id`/`general_position` logic untouched for now — the LAST remaining employment `position_id` site. No code changed this turn; ADR + memory updated with the decision and the two deferrals (shift impl, general_position).

---

## 2026-06-26 — Shift-type feature SHIPPED (backend): CRUD + per-role assignment + ShiftService cutover

User decided: full incl. ShiftService cutover; per-role assignment via PIVOT table.

**KEY unblocking finding:** the deferred `general_position` (C–G) branch in ShiftService was a **functional NO-OP** — it only gated shift-type ids `[17,27]`, but `UNUSED_IDS=[17]` (always excluded) and id `27` does not exist (max id 26). So C-G vs non-C-G yielded identical results. → dropped general_position from shift logic entirely; the earlier "shift gated on general_position" deferral is RESOLVED (it was inert for shifts).

**Done (backend):**
- Migration `2026_06_26_000006_create_community_role_shift_type_pivot.php` (ran): pivot `community_role_shift_type` (role_id, shift_type_id, FK cascade, unique) + seeded each role from the old position logic — privileged roles (board/pm/regular/PL/member/hr/admin)=ALL; contract_employee=ALL−[14,15,16,19-26]; transferred/partner=ALL−[14,15,16]; registered=[1,5].
- `CommunityRole::shiftTypes()` belongsToMany relation.
- `ShiftService::getAvailableShiftTypes` cutover → reads `selectableShiftTypeIds($user)` from the user's role pivot (via `membershipFor`), keeps work_type + UNUSED_IDS code filters; removed `applyPositionRules` + `isPrivilegedPosition`. Fallback to all types if no membership (edge).
- `ShiftTypeController` (CRUD: index/store/update/destroy, `community.manage` gated; community-scoped via BelongsToCommunity).
- `CommunityContextController::syncRoleShiftTypes` (assign shift types to a role; `role.manage` gated; validates ids belong to the community) + routes under `/community_context/shift_types` + `/community_context/roles/{role}/shift-types`.

**Verified:** all lint clean; migration ran; behavior-preservation audit — old position-logic vs new pivot per active user: **197/198 identical**, 1 mismatch = user 830 (研修サポート, the pos-14 non-employee bot in the member catch-all) gains ceremonial-leave types it won't use (same known anomaly as lunch). Relation smoke: contract=15 ids correct, regular=26, registered=[1,5]. `class_exists(ShiftTypeController)`=true. (`route:list` throws a PRE-EXISTING unrelated ReflectionException for dangling `AdminCostMasterController` — not from this work.)

**REMAINING — frontend UI (can't build/verify here):** admin screens for shift-type CRUD + the per-role shift-type assignment (a section in CommunityPermissionControl.vue or a dedicated screen). API contract ready: `GET/POST/PATCH/DELETE /community_context/shift_types[/{id}]`, `PATCH /community_context/roles/{role}/shift-types {shift_type_ids:[...]}`. FE pending user.

---

## 2026-06-26 — Shift-type feature: FRONTEND UI built (build-check pending user)

Built into `resources/js/components/AccountControl/CommunityControl/CommunityPermissionControl.vue` (the role × capability matrix):
- **Backend tie-in:** `rolePayload()` now returns `shift_type_ids` per role.
- **Per-role assignment:** a "シフト種別" section in the role detail — toggle tiles for every community shift type, `on` when the role has it; `toggleShiftType(id)` does optimistic update + `PATCH /community_context/roles/{role}/shift-types`.
- **CRUD panel:** "シフト種別を管理" toggle reveals a list with inline edit + delete, plus an add form → `POST/PATCH/DELETE /community_context/shift_types`. Methods: createShiftType/startEditShift/saveShiftType/cancelEditShift/deleteShiftType.
- Loads shift types in `loadPermissionSettings` (3rd parallel request); `updateRole` now preserves `shift_type_ids`; added types `ShiftType` + `shift_type_ids` on `CommunityRole`; minimal scoped CSS.
- **BONUS FIX:** corrected a leftover from the blade→capability rename — the `CapabilityGroup` type still declared `blades:` while template/back-end use `capabilities`. That was likely a vue-tsc build error already; now `capabilities: CapabilityItem[]`.

**Verified here:** backend lint clean; template tags balanced (read-back); Edit/Trash icons imported; all referenced helpers exist. **NOT verified:** Vue/Vite build + runtime (no FE build env in session). → user to run `npm run dev`, open the permission screen, confirm: shift tiles render per role, toggling persists, CRUD add/edit/delete works. Watch for the (now-fixed) `blades` type error clearing.

**Shift-type feature now COMPLETE end-to-end (pending FE build-check).**

---

## 2026-06-26 — Shift CRUD relocated to WorkControl tab (per user)

User: shift-type CRUD belongs in the workcontrol route as a new tab, not in the permission screen. Done:
- **New component** `WorkControl/ShiftTypeManager.vue` — standalone CRUD table (name/abbreviation/value/full_day; add row + inline edit + delete) using the same `/community_context/shift_types` endpoints.
- **Route** `shift-types` added under the `workcontrol` children in `routes/admin.ts`.
- **Tab** "シフト種別" added to `AdminWorkControl.vue` sub-tab nav (alongside 勤怠管理/領収書監査/計画有給管理/有休ルール/有休台帳).
- **Permission screen trimmed:** removed the CRUD panel + its state/methods/CSS from `CommunityPermissionControl.vue`; **kept** the per-role assignment tiles (`shiftTypes` load, `isShiftOn`, `toggleShiftType`) — assignment is a role concern, CRUD is a work-admin concern. Empty-state hint now points to "勤怠管理 › シフト種別".

Net split: **assignment** (which roles may select which shift types) stays in 権限設定; **catalog CRUD** lives in 勤怠管理 › シフト種別. No backend change (endpoints already existed). Verified: no dangling refs to removed symbols; route+tab wired; tags balanced; Edit/Trash imports still used. FE build-check pending user (`npm run dev`).

---

## 2026-06-26 — Shift CRUD UI redesigned to app conventions (user: weird/tiny buttons/broken rules)

First pass used a raw table with tiny inline buttons — off-pattern. Rebuilt to match `AdminOffice.vue`/`AdminOfficeCreate.vue` (the canonical community-CRUD pattern):
- **`ShiftTypeManager.vue`** rewritten: `FloatButton` (+ `AddIcon`) for the create action; a clean card list (`.shift-box`) instead of a table (fixes "broken rules"); each row's actions via **`ItemMenu`** kebab (編集する/削除する) instead of tiny buttons; loader + empty state mirror AdminOffice.
- **`ShiftTypeCreate.vue`** (new): the create/edit window built on **`Modal.vue`** — `ShortInput` fields (name required via `:rules="'required'"` + `nameRef.validate()`, abbreviation, value) + 終日 checkbox + `LoaderButton`. POST create / PATCH `{id}` update; emits `close(flag)`; parent reloads on save.
- API calls use the composable convention `api.del(url,{},{ask,toast})` / `api.post|patch(url,payload,{toast})` — verified `patch(url,data,options?)` signature supports it.

Verified: both files 6 section-tags (balanced); imports match AdminOffice paths; ItemMenu/ShortInput/LoaderButton/Modal/FloatButton contracts confirmed against existing usage. FE build-check pending user.

**Capability migration status: PAUSED at a clean point.** Done: Stage 0 rename, 0c hasCapability, Stage 1 predicate flip, Refresh + Lunch-challenge expansions. Deferred: shift-type config-data (gated on general_position), general_position scope, queue/console context binding, the service-level position fallback (retire once all users guaranteed a membership).

---

## 2026-06-26 — BUGFIX: side menu / capabilities vanish after profile-type updates

**Symptom (user-reported):** sometimes permissions don't load and side-menu items disappear (e.g. around the `profile_get_update_user` flow).

**Root cause (source-of-truth bug):** `resources/js/store/auth.ts` `setUser()` did a wholesale replace of community context. Community capabilities' source of truth is the auth payload (Root bootstrap `auth_user` + `/community_context`, built by `CommunityContext::authPayload()`). But `setUser()` is ALSO called with PARTIAL user payloads from endpoints that don't carry the flattened community keys — `profile_get_update_user` (ProfileContainer, useSettings, CalendarSettings), SignAction, Weather×2. `profile_get_update_user` returns the raw `communityMemberships` relation but NOT `community_capabilities`/`community_role`/`active_membership`, so `setUser` resolved `communityRole=null`, `communityCapabilities=[]` → `auth.can()` false for everything → side menu items hidden until reload.

**Fix:** `setUser()` now (a) MERGES the user object instead of replacing, and (b) only re-hydrates community context (communities/activeCommunity/scope/role/capabilities) when the payload actually carries it (`'community_capabilities'|'community_role'|'active_membership'|'communities' in payload`). Partial payloads preserve existing community state. Full payloads (Root bootstrap / community_context) behave exactly as before. `applyCommunityPayload()` left as-is (only called with full authPayload responses).

**Verification:** traced against all 7 setUser callers (logic-verified). FE build/runtime check pending user (`npm run dev`; repro: log in → open Profile → side menu must persist). Could not run browser preview in this session.

---

## 2026-06-29 — id-27 (特別休暇) regression correction + WorkShifts.vue conversion

**Trigger:** user flagged that shift_type **id 27 exists in PRODUCTION** (特別休暇, full_day=2, value=480) though it is ABSENT from the local test DB. The earlier "id 27 = dead, drop it" decision (recorded in shift_type_hardcoding_inventory.md and migration 000006's comment) was therefore wrong, and the sample-month payroll diff could not catch it (no id-27 records locally).

**id 27 footprint (original HEAD) and fixes:**
- `ShiftService::countSpecialHoliday` — was `->where('shift_type',27)->count()`; I had broken it to `whereRaw('0=1')`. **Restored** → `whereIn('shift_type', shiftType::idsFor(CATEGORY_SPECIAL_HOLIDAY))`.
- `AutoAttendanceConfirm` `$spec_holiday` (line ~244) — was `->where('shift_type',27)->count()`; I had broken it to `=0`. **Restored** → `whereIn(..., idsFor(CATEGORY_SPECIAL_HOLIDAY))`. Feeds `attendance.special_holiday` → `special_hours` (payroll).
- `AutoAttendanceConfirm` annual_full exclusion (line ~228) — original excluded `[14,15,16,17,18,27]`; mine dropped 27. **Added** `CATEGORY_SPECIAL_HOLIDAY` to the exclusion (else a 27 record is miscounted as 年休).
- `SharedService:45` leave-grouping — original `[0,2,3,5,14,15,16,18,27]`; mine dropped 27. **Added** `CATEGORY_SPECIAL_HOLIDAY`.
- `ShiftService::getAvailableShiftTypes` general_position branch — original gated 27 selectability by `general_position ∈ {C..G}`. **Buried** per user decision (don't reintroduce general_position); 27 is now a normal pivot-assignable type (see migration). The `remainingSpecialHoliday` quota (also general_position, left intact) still limits how many can be booked.
- `WorkRecordRow.vue:1436` — cosmetic styling list; **added** `'special_holiday'`.

**New category + migration:** `shiftType::CATEGORY_SPECIAL_HOLIDAY = 'special_holiday'` (distinct from the condolence/transfer/oda trio — it has its own payroll field). Migration `2026_06_29_000001_add_special_holiday_category_and_assign.php` — fully guarded (`if id 27 not exists: return`, so no-op on this test DB): sets id 27→category and inserts (role,27) for EVERY glowd role (per user: "assignable to every role for now"). down() reverses both.

**WorkShifts.vue conversion (was DEFERRED):** kept `selectedShiftType`/`selectedShifts[].type`/`shift_array.type` as the shift_type **id** (the `/add_shift` contract is unchanged) and replaced all id-literal *comparisons* (3/16/27/0/18/19) with category helpers: `SHIFT_CATEGORY` map + `categoryOfId()` (backed by a `shiftTypeMap` now seeded from BOTH selectable types and loaded records, so it resolves types outside the user's selectable set) + `isPlannedPaidId`/`isSpecialHolidayId`/`isHolidayType` + `selectedIsPlannedPaid`/`selectedIsSpecialHoliday` computeds + `plannedPaidLeaveId` (replaces the hardcoded `selectedShiftType=3` default in propsCheck, now resolved post-fetch in onMounted). The 半日休日 case (was id 19) → `category===holiday_work && hours===0.5`.

**Also fixed (pre-existing, missed by the earlier "WorkController done" pass):** `WorkController::add_shift` line 1048 `$holidayTypes=[0,2,3,5,14,15,16,17]` → `idsFor([DAY_OFF,ABSENCE,PLANNED_PAID_LEAVE,ANNUAL_LEAVE_FULL,COMP_HOLIDAY]+SPECIAL_LEAVE)` (preserved original — 27 NOT included); line 1056 `$s['type']===0` → `in_array(..., idsFor(DAY_OFF))`.

**Validation:** all changed PHP lint-clean; migration runs (no-op locally). Production simulated in a rolled-back transaction (insert fake id 27 → run migration logic → verify): `idFor(special_holiday)=27`, `idsFor=[27]`, category set, 11 pivot rows (all glowd roles), annual_full exclusion contains 27; rollback left DB untouched. **WorkShifts.vue still needs `npm run dev` + manual UI test** (shift entry, planned-leave default, 特別休暇 selection/quota) — unrunnable in agent env.

---

## 2026-06-29 (cont.) — admin-assignable shift-type category (CRUD)

Closed the gap where CRUD-created shift types had no `category` (→ no payroll/grouping meaning). Categories are a fixed Japan-labour taxonomy, so admins now pick one from that fixed list on create/edit.

- **`shiftType::categoryCatalog()`** — single source: ordered `[{value,label,hours}]` for the 14 categories (JP labels). `categoryKeys()` for validation; `HOURLY_CATEGORIES` const = `[annual_leave_hourly, holiday_work]` (the two whose meaning is disambiguated by `hours`).
- **`ShiftTypeController`** — `categories()` endpoint (`GET /community_context/shift_type_categories`, community.manage). `store`/`update` share `validatePayload()`: `category` required from the catalog; `hours` required only for hourly categories and force-nulled otherwise. (Decisions: category REQUIRED, hours CONDITIONAL — user 2026-06-29.)
- **FE `ShiftTypeCreate.vue`** — category `<select>` (required, inline error) + conditional `hours` input shown only for hourly categories; payload sends category+hours. **`ShiftTypeManager.vue`** — fetches the catalog, passes to the modal, shows a category chip per row (未分類/tomato when null).
- **Validation verified** (Validator harness): no-category FAIL, invalid FAIL, day_off PASS, holiday_work w/o hours FAIL, holiday_work+hours PASS, work+stray-hours PASS (hours nulled by controller). All PHP lint-clean.
- **Note:** editing an existing seeded type's category is allowed (community.manage) — re-categorizing e.g. planned_paid_leave away from id 3 would alter that community's payroll mapping; intentional (admin owns config). FE build-check pending user.

---

## 2026-06-29 (cont.) — special-leave review + unite (catalog) + add 特別休暇 record

**Review (user asked to examine differences before uniting):** condolence(14)/transfer(15)/oda(16) are calculated IDENTICALLY — each = `work_time_day × days`, summed the same way into the accounted/absence total (`AutoAttendanceConfirm` lines 61-86). The ONLY difference is the attendance REPORT: they route to 3 distinct `attendance_record` columns shown in `AdminWork.vue` export — 慶弔休暇 (condolence_holiday←14), ODA休暇 (oda_holiday←16), 特別休暇 (special_holiday←transfer 15 + 特別休暇 27). No pay-amount difference; only a report breakdown.

**Decision (user):** "Unite category, keep 3 report columns" — collapse to one `special_leave` category for selection/grouping, but keep each record routing to its own column.

**Implementation (zero payroll-routing change):** kept the seeded 14/15/16 records' fine sub-categories (`special_leave_condolence/transfer/oda`) so `AutoAttendanceConfirm`'s `idFor(subtype)` routing is UNCHANGED. The unite is at the catalog/group level:
- `shiftType::CATEGORY_SPECIAL_LEAVE='special_leave'` (umbrella) + `SPECIAL_LEAVE_SUBTYPES` const. `SPECIAL_LEAVE` group now = `[special_leave, condolence, transfer, oda]` (so all "any special leave" logic — SharedService/Customfield/annual_full/WorkController — still catches 14/15/16 AND any new umbrella records). `idsFor(SPECIAL_LEAVE)` = [14,15,16] (unchanged).
- `categoryCatalog()`: the 3 sub-entries replaced by ONE `特別休暇（慶弔・転勤・ODA）`→special_leave; `special_holiday` relabeled `特別休暇`. `categoryKeys()` still includes the sub-types (so editing seeded records validates).
- FE `ShiftTypeCreate.vue`: editing a sub-typed record shows the unified option but PRESERVES the original sub-category on save unless the admin picks a different category (no silent routing downgrade). `ShiftTypeManager.vue`: sub-typed records display under the 特別休暇（慶弔・転勤・ODA）label.

**Add 特別休暇 record (user request):** migration `2026_06_29_000002` — guarded (no-op if a special_holiday record already exists, e.g. prod id 27); in this test DB it inserted **id 28 特別休暇** (category special_holiday, full_day 2, value 480), assigned to all 11 glowd roles. So `countSpecialHoliday` (quota) and the 特別休暇 attendance column now resolve locally too. down() removes only an UNREFERENCED special_holiday 特別休暇 record (protects prod's in-use 27).

**Verified:** lint clean; migration ran; live API — catalog shows special_leave + special_holiday only; records 14/15/16 keep sub-categories; id 28 特別休暇 present. AutoAttendanceConfirm untouched. FE build-check (npm run dev) still pending user.

---

## 2026-06-29 (cont.) — fold 代休 into special_leave (catalog) + shift-type active flag

**代休 (comp_holiday) review:** id 17 IS used (14 shift_records here) and is salary-relevant — it has its own 代休 attendance column (AdminWork export + WorkAttendance) AND its days feed `all_worked_time` → `$month_over_time` (OVERTIME) in AutoAttendanceConfirm:263-265. So naively switching record 17's category to special_leave would zero comp_holiday → change overtime + blank the 代休 column = salary regression. Same pattern as the special-leave unite: folded 代休 under the unified 特別休暇 category in the CATALOG only, keeping `comp_holiday` as the record's internal routing category so calc/column/overtime are UNCHANGED.
- Model: removed the 代休 catalog entry; added `comp_holiday` to `SPECIAL_LEAVE_SUBTYPES` (catalog/UI folding + categoryKeys validity) but intentionally NOT to the `SPECIAL_LEAVE` calc group (its calc rule differs). Umbrella label → 特別休暇（慶弔・転勤・ODA・代休）. `idFor(comp_holiday)`=17 and `idsFor(SPECIAL_LEAVE)`=[14,15,16] both UNCHANGED. AutoAttendanceConfirm untouched.
- FE: ShiftTypeCreate/Manager `SPECIAL_LEAVE_SUBTYPES` += comp_holiday (edit shows 特別休暇, preserves comp_holiday on save; manager labels it under the umbrella).

**Active/inactive flag (migration `2026_06_29_000003`):** added `shift_types.active` (bool, default true, indexed) + model cast. Inactive types are hidden from the selection dropdown (`ShiftService::getAvailableShiftTypes` now `->where('active',1)`) but STILL resolved by `idsFor`/`idFor` (no active filter) and counted by existing shift_records — so work-hour/payroll calc is unaffected. Distinct from `deleted_flag` (full soft-delete). CRUD: index returns `active`; store/update validate+persist it; ShiftTypeCreate has an 有効 checkbox (default on) + hint; ShiftTypeManager shows a 無効 tag. Verified (rolled-back txn): marking id16 inactive → excluded from `where active 1`, still resolved by `idsFor(oda)`.
