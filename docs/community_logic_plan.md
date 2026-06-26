# Community Logic — Permission Migration Plan

Last updated: 2026-06-24
Status: **plan only, no code changes yet**
Companion doc: [community_logic_footprint.md](community_logic_footprint.md) (what Codex built)

---

## 0. Decision recorded this session

- **Single source of truth = the SCOPE_IMAGE matrix** (9 app-feature scopes + 5 position-feature scopes per role), *not* the 24-key capability catalog.
- The 24-capability catalog is **demoted from stored, editable data to a derived projection** (see §3). It is not deleted in one shot — it becomes a computed function of the scope matrix so the already-wired downstream seam keeps working.
- This document is a written plan to review before any code lands.

---

## 1. Why the current attempt stalled (root cause)

Codex built a correct *foundation* (schema, resolver, context, auth payload, admin UI, 7 green tests) but left the **gate model undecided**. Three overlapping permission concepts exist with no rule for which one gates a feature:

| # | Concept | Where stored | Problem |
|---|---------|--------------|---------|
| 1 | `capabilities[]` (24 keys) | `community_roles.capabilities` | Fine-grained, but invented; no 1:1 with SCOPE_IMAGE |
| 2 | `scopes[]` (9+5 keys) | `community_roles.scopes` | The intended model — but **never wired to a gate** |
| 3 | membership `scope` enum | `community_user.scope` | `internal/partner/registered/external` — orthogonal identity axis |

The same legacy rule is duplicated in 3 places (e.g. "boss = `position_id < 6`" lives in `CommunityContext::isBoss`, `CommunityPermissionService::isBoss`, **and** `auth.ts`). So migrating any one of the ~300 legacy call sites forces a judgment call — capability? role key? scope? — and the work bogs down.

**The fix is to make #2 the source of truth and *derive* #1 from it.** Then the seam below is single-valued and the call sites don't each need a decision.

---

## 2. The model (resolves the granularity gap)

SCOPE_IMAGE is intentionally coarse — one checkbox per app. But legacy code distinguishes **view vs manage vs approve** within one app. The resolution: a gate is the **intersection of two orthogonal axes**, plus two cross-cuts.

### Axis A — App-feature scope (9) = *visibility / "can this role touch this app at all"*

`app.project, app.schedule, app.timesheet, app.learning, app.contact, app.notice, app.asset, app.support, app.form`

Replaces the *access/exclusion* legacy checks: `!auth.isPartner`, registered restrictions, `position_id == 15` view gating.

### Axis B — Position-feature scope (5) = *elevation lane / "does this role act with department authority"*

`position.management_hq (管理本部), position.system_development (システム開発), position.hr (人事), position.pm (PM), position.board (役員)`

Replaces the *elevation* legacy checks: `position_id < 6`, `position_id == 6`, `[608, 610]`, manage/approve actions.

**These map to real existing data**: `users.general_position` already holds `管理本部 / システム開発 / 人事 / 役員` strings, and `position_id` (`sort_flag`) carries seniority. That is the backfill source for Axis B.

### A gate = App-feature ∧ (optional) Position-feature

This is how the coarse 14-checkbox model still expresses every fine legacy gate:

| Legacy intent | New gate expression |
|---|---|
| view own timesheet | `app.timesheet` |
| manage ALL timesheets (`[608,610]`) | `app.timesheet ∧ position.management_hq` |
| PM approves assigned timesheet | `app.timesheet ∧ position.pm` |
| 役員 approves project (`position_id<6`) | `app.project ∧ position.board` |
| create/edit notice (`isAdmin || isBoss`) | `app.notice ∧ (position.management_hq ∨ position.board)` |
| view project list | `app.project` |
| manage assets (`[608,610]`) | `app.asset ∧ position.management_hq` |

### Cross-cut 1 — Admin (fixed role)

Admin is the protected top role (no Owner). It **implies every app-feature and every position-feature scope**. It is not editable/deletable, and each community must keep ≥1 Admin. Encode as a short-circuit: `isAdmin ⇒ all gates true`.

### Cross-cut 2 — Membership scope (`partner / registered / internal / external`)

This stays a **distinct membership attribute**, not a role-editable scope (per footprint: "isPartner is membership scope, not just a role permission"). It acts as a **ceiling/mask** applied *after* role scopes:

- `partner` → masks off internal-only app features (post, learning, timesheet, etc.) regardless of role grant.
- `registered` (`position_id == 15`) → its own reduced mask.
- `internal` → no mask.

So effective access = `roleScopes ∩ membershipScopeMask`, with Admin bypassing the mask.

---

## 3. The keystone move: derive capabilities (and helpers) from scopes

The reason the 110 `auth.isAdmin` / 32 `isBoss` / 12 `isPM` frontend sites and the `User::isX()` backend helpers **don't need to be touched**: they already read through a seam. We re-point that seam at the scope matrix.

```
SCOPE MATRIX (stored, editable, = SCOPE_IMAGE)   ← single source of truth
        │  derive()
        ▼
effective gate resolver  →  isAdmin / isBoss / isPM / can(appFeature, positionFeature?)
        │
        ├─ backend: User::isX(), CommunityPermissionService, a Gate::define per app-feature
        └─ frontend: auth.ts getters + a new can(feature) helper
```

Concretely:
- `role.capabilities` becomes **computed**, not stored. A `ScopeToCapability` map (one place) projects the 14 scopes → the legacy helper booleans. e.g. `isBoss = hasPositionScope('position.board')`, `isAdmin = role.key==='admin'`, `isPM = hasPositionScope('position.pm')`.
- The role editor UI edits the **14 checkboxes** (matches SCOPE_IMAGE exactly), not 24 capabilities.
- Old `capabilities[]` column is kept nullable during transition, then dropped in a later migration once nothing reads it directly.

This is what lets us avoid the 300-site rewrite: **the seam changes once, the call sites inherit it.**

---

## 4. Inventory (grounded counts, this branch)

Backend raw literals that **bypass** the seam (must be converted by hand — finite):

| Pattern | Count | Convert to |
|---|---|---|
| `[608, 610]` admin literal | 18 files | `$user->isAdmin()` or `can('app.X', 'position.management_hq')` |
| `position_id < 6 / <= 6` | ~29 | `position.board` lane |
| `position_id == 6 / != 6` | ~17 | `position.pm` lane |
| `position_id == 15` | 7 | `registered` membership scope |
| `partner_flag` | 45 | `partner` membership scope |
| edge values (`==12, ==16, <=11, <13`) | ~5 | case-by-case, flag for review |

Frontend (already flow through the seam — **leave as-is**, they inherit the new derivation):

| Getter | Sites | Action |
|---|---|---|
| `auth.isAdmin` | 110 | no change |
| `auth.isBoss` | 32 | no change |
| `auth.isPM` | 12 | no change |
| `auth.isPartner` | 36 | no change |
| `auth.isRegistered` | 20 | no change |

55 frontend files reference these; **0 need editing in the core migration** because the getters are the seam. (Optional later cleanup converts them to explicit `can('app.x')` for new granular gates.)

---

## 5. Migration phases

### Phase 1 — Lock current behavior (safety net, no behavior change)
- Build a **characterization test matrix**: for each archetype user — Admin(608), 役員(`position_id<6`), PM(6), 管理本部 staff, 人事 staff, registered(15), partner(`partner_flag=1`), plain member — assert the value of every `isX()` helper and a representative set of controller gates. Snapshot today's booleans.
- This is the regression oracle for every later phase. Extends existing `CommunityLogicTest`.

### Phase 2 — Make scopes the source of truth
- Add `ScopeResolver` (backend) + scope-based derivation in `CommunityContext`: `hasAppScope()`, `hasPositionScope()`, and `can(appFeature, positionFeature = null)`.
- Re-implement `isAdmin/isBoss/isPM/isPartner/isRegistered` in terms of scopes (keep legacy fallback when no membership, as today).
- Re-seed the 5 starter roles' scope sets to reproduce today's behavior exactly (verify against Phase 1 snapshot). Backfill Axis B from `general_position` + `position_id`.
- Demote `capabilities[]` to derived (§3). Update role editor to the 14-checkbox matrix.
- **Gate:** Phase 1 matrix stays green → proven no behavior change.

### Phase 3 — Convert backend raw literals (the finite list in §4)
- Mechanical, one PR per controller cluster (Support, Work, AdminWork, AdminPaidLeave*, Asset*, Remind, Refresh).
- Each `[608,610]` → helper/gate; each `position_id` literal → lane check; `partner_flag`/`==15` → membership scope.
- Edge values (`12,16,11,13`) get individual review — do **not** auto-convert; `log`/comment what they were.
- **Gate:** Phase 1 matrix green after each PR.

### Phase 4 — Introduce real granular gates (optional, incremental)
- Now that `can('app.timesheet','position.management_hq')` exists, replace coarse `isAdmin` sites that *should* have been feature-specific, one app at a time, as product needs arise. Not required for correctness.

### Phase 5 — Drop dead infrastructure
- Remove the stored `capabilities[]` column and `CommunityCapabilityCatalog` once nothing references them. Keep `CommunityScopeCatalog` (now the catalog of record).

---

## 6. Required inputs from you (blockers for Phase 2)

1. **Position-feature backfill map** — confirm the mapping from existing data to Axis B:
   - `general_position == '管理本部'` → `position.management_hq`? (and システム開発/人事 likewise)
   - `position_id == 6` → `position.pm`?
   - `position_id < 6` → `position.board`? (Is 役員 == seniority `<6`, or a `general_position` string, or both?)
2. **Membership-scope masks** — exactly which app-features are hidden for `partner` and for `registered`? (Today this is implicit in scattered `!isPartner` checks; we need the canonical list.)
3. **Admin definition** — confirm Admin stays the hardcoded `ADMIN_USER_IDS [608,610]` seed for the default community, and is otherwise the fixed `admin` role going forward.

---

## 7. Risks & mitigations

- **Silent behavior drift** across 300 sites → Phase 1 characterization matrix is the oracle; nothing merges if it changes.
- **Granularity loss** (coarse checkbox can't express a rare fine rule) → the A∧B intersection covers the known cases (§2 table); genuine outliers (edge `position_id` values) are explicitly excluded from auto-conversion and flagged.
- **Membership-scope vs role-scope confusion** → kept on separate axes (§2 cross-cut 2); role editor never shows membership scope as a checkbox.
- **Multi-community correctness** → all gates resolve through the active-community membership (existing `community.active` middleware); Phase 1 tests run per-community.

---

## 8. What to do next

If this plan is approved, start with **Phase 1** only (pure test additions, zero risk) and review the snapshot before touching the resolver. Each subsequent phase is independently revertable and gated on the Phase 1 matrix staying green.
