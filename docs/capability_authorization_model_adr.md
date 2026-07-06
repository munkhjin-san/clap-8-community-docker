# ADR: Capability-based authorization model

- **Status:** Accepted (2026-06-26)
- **Branch:** `community_logic`
- **Supersedes/relates:** `docs/community_logic_rules.md`, `docs/community_logic_plan.md`; the "blade" RBAC introduced earlier (see below — "blade" is renamed to "capability").

## Context

`position_id` (a single integer on `users`, values 1–16 = グラウド's org chart: 代表取締役…プロジェクトリーダー) had been overloaded to mean **three unrelated things** at once:

1. **Authority / privilege** — `position_id < 6` ⇒ board, `== 6` ⇒ PM, `[608,610]` ⇒ admin. Used as permission gates.
2. **Member classification** — `== 14` ⇒ partner (協力会社), `== 15` ⇒ registered (登録社員).
3. **Employment / HR profile** — `== 12` ⇒ 契約社員, `<= 11` ⇒ permanent staff, `[6,11,12,16]` ⇒ refresh-leave eligible, plus shift-type rules.

This conflation is the root cause of the permission confusion. It is also fundamentally at odds with multi-tenancy: `position_id` values are グラウド-specific, but the app is becoming multi-community (`position_records` is now community-scoped), so other communities will have different positions/ids.

Work already done on this branch (the migration "seam"): ~250 call sites were moved off raw `position_id` onto centralized predicates (`isBoss/isPM/isAdmin/isPartnerScope/isRegisteredScope`) and `can()`, which already route through `CommunityPermissionService` → community roles, with `position_id` kept only as an internal fallback. Per-user permission checks were also made side-effect-free (`CommunityResolver::membershipFor`). See memory `static-auth-refactor`, `community-logic-blade-model`.

## Decision

**Separate the three concepts; unify all permission/feature checks under one mechanism.**

1. **Position = HR truth, inert for permissions.** `position_records` / `position_id` remain the employee's org position (HR/payroll/display). They **do not** grant or deny anything in the app. Left as-is.

2. **Role = the sole source of capability.** A user's community **role** holds their capabilities. Roles are a **separate existence** from positions: *seeded ("adopted") from position structure* for sensible defaults, but independent and customizable thereafter. Changing app permissions never touches HR position, and vice-versa.

3. **Capability = the atomic unit. Every check is `$user->can('capability')`.** No `position_id`, no magic numbers, ever, in permission/feature logic. (Renames the prior "blade" term — it collided with Laravel Blade views. `blade` → `capability` across catalog, middleware, frontend.)

4. **Role shape: one rich role per membership (option A), compact first.** Keep the current single-role schema (`community_user.community_role_id`). Do **not** pre-build employment-type roles. The capability catalog starts **compact** (what exists today) and grows **per feature**, namespaced (`app.*`, `project.*`, `benefit.*`, `shift.*`, …). Option B (multiple capability sources per user, unioned) is **deferred** — reachable cheaply later precisely because Capability is the atomic unit.

5. **Scope collapses into roles.** `partner` / `registered` are not a separate axis — they are roles (or role attributes) whose capability sets are restricted. `isPartner/isRegistered` become "lacks capability X."

6. **Parametric employment rules** (e.g. "契約社員 may not use shift types [19–26]") are the one misfit for boolean capabilities: model as boolean sub-capabilities where the groups are small/stable (`shift.use_*`), else keep as role/position **config data**. Decide per case.

## Consequences

- `position_id` loses all permission meaning. Authority = role capabilities; classification = role; HR rules = employment profile.
- The hybrid predicates keep working; their `position_id` fallbacks are **retired feature-by-feature** as each capability is migrated. Call sites don't change again (already centralized).
- Multi-tenancy falls out: each community customizes its own roles' capabilities.
- One-time rename `blade → capability`.

## Migration plan (compact-first, incremental — strangler pattern)

- **Stage 0 — terminology & model.** Rename `blade → capability` (catalog `CommunityBladeCatalog → CommunityCapabilityCatalog`, `EnsureCommunityBlade`, `blade:` middleware, frontend `auth.can`). Solidify `User::can()` over the existing compact role set. No behavior change.
- **Stage 1 — authority capabilities.** Ensure the clean authority gates (already on `isBoss/isPM/isAdmin` + `can()`) resolve purely through role capabilities; begin retiring their `position_id` fallbacks. Keep employment checks quarantined on `position_id` (labeled).
- **Stage 2+ — per-feature expansion.** When a feature warrants it, expand the catalog + (if needed) role granularity and migrate that feature's check to `can()`, retiring its `position_id` fallback. **Refresh** is the model for the first expansion: add `benefit.refresh`, assign to the qualifying roles, replace `ELIGIBLE_POSITION_IDS = [6,11,12,16]` (RefreshService + RefreshAutoAllocation, ~6 sites) with `$user->can('benefit.refresh')`.

## Open / per-case (not blocking)

- Exact catalog namespacing conventions as it grows.
- When (if) to expand role granularity to express employment distinctions for `benefit.*` capabilities.

## Decided 2026-06-26 — shift-type rules = per-role config-data (NOT capabilities); DEFERRED

Shift-type access is **parametric** (a *list* of allowed shift types per employment type, not a boolean), so it is NOT modeled as capabilities. Decision: **each Role record carries its own configurable selectable-shift-type set** (`community_roles.shift_types` config-data, eventually admin-editable in the role UI). This is the multi-community-flexible solution — each community's roles choose their own shift types.

**DEFERRED (gated):** implementation is blocked on the **`general_position` (C–G) decision, also deferred.** They are intertwined: `ShiftService::applyPositionRules` nests `isPrivilegedPosition` (general_position C-G) *inside* the `position <= 11 || == 16` branch, so for board/pm/regular_employee/project_leader the allowed shift-type set varies by general_position *within* the role — a per-role `shift_types` config alone can't reproduce current behavior. So: do shift-type config-data + the general_position layer together, as one dedicated piece, later. Until then `ShiftService` keeps its position_id/general_position logic (the last remaining employment position_id site).
