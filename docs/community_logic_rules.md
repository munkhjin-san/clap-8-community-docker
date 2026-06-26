# Community Logic — Harvested Rule Catalog

Last updated: 2026-06-25
Source: read-only discovery pass over the whole permission surface (6 parallel scans).
Status: **blade model IMPLEMENTED (see below); this catalog is the harvest it was built from.**

## Implemented (2026-06-25) — the "blade" model

The converged model is live. A **blade** = one rule a role can hold. Roles map to blades; Admin is the fixed super-role that bypasses every gate; partner/registered are now **roles** (not a separate scope mask); per-object authority (PM-of-project, ownership) stays in code.

- **Single catalog:** `app/Services/Community/CommunityBladeCatalog.php` — replaces the old `CommunityCapabilityCatalog` + `CommunityScopeCatalog` (both deleted). 12 app blades + 7 action blades.
- **Gate seam:** `CommunityContext::can($blade)` now short-circuits `true` for admin; `isAdmin` = role key `admin`; partner/registered derive from role key. `community.manage`/`role.manage`/`user.manage` are no longer blades — they resolve to admin-only automatically.
- **Migration:** `database/migrations/2026_06_25_000001_redefine_role_blades.php` — keeps existing roles, rewrites their blades to behavior-preserving defaults, creates the `partner` role, and reassigns `scope=partner` memberships to it. `CommunityResolver::legacyRoleKey` now maps partner_flag → `partner` for new users.
- **UI:** `resources/js/components/AccountControl/CommunityControl/CommunityPermissionControl.vue` at `/admin_control/permissions` — a role × blade checkbox matrix grouped アプリ / 権限・操作. Admin column locked all-on. Only the admin role may edit it.
- **Endpoint:** `GET /community_context/capabilities` returns the blade catalog; `/community_context/scopes` removed.
- **Tests:** `tests/Feature/CommunityLogicTest.php` — 8 passing (blade catalog, validation against the closed set, partner-role migration).

Still legacy / not yet migrated: the ~115 raw backend literals (`[608,610]`, `position_id`, `partner_flag`) and the frontend `auth.isX` call sites. They keep working through the unchanged seam; converting them to `can('blade')` / `isAdmin()` is the follow-up (Phase 3 in the plan).

---

### Original harvest (pre-implementation draft)
Companion: [community_logic_plan.md](community_logic_plan.md), [community_logic_footprint.md](community_logic_footprint.md)

This is the *closed, code-derived* rule set. Each rule = one real authorization decision that already exists in the code. Admins map roles → these rules; only a developer adds a rule (when they add a new code gate).

## Rule kinds (the key structural finding)

Every harvested decision falls into exactly one of three kinds:

- **FLAT** — a global boolean grantable to a role. Pure checkbox material. (~20)
- **REL** — relational/ownership: the rule grants the *class* of action, but code still narrows it to *which objects* (own vs all, PM-of-this-project, author). Cannot be a checkbox alone. (~10)
- **MASK** — membership scope (`partner`/`registered`). Orthogonal identity filter, set per-member, NOT a role rule. (2)

---

## 管理 / Cross-cutting

| Rule | Kind | Decision | Granted today by |
|---|---|---|---|
| `admin.access` | FLAT | Open admin area; manage community/accounts | `[608,610]` |
| `user.manage` | FLAT | Manage employees/users; review 各種届出 change applications | `[608,610]` (EmployeeController is the *only* hard backend gate) |
| `community.manage` | FLAT | Edit community name/icon | capability (already wired) |
| `role.manage` | FLAT | Edit role↔rule matrix | capability (already wired) |

## プロジェクト

| Rule | Kind | Decision | Granted today by |
|---|---|---|---|
| `project.access` | REL | View projects (scoped to own for low tiers) | non-partner; `position_id 13/15` → self-only |
| `project.approve` | FLAT | Board approval + org-wide project view (役員) | `position_id < 6` |
| `project.manage_assigned` | REL (PM of project) | Manage one's assigned projects | `position_id==6` + `project_members.authority==1` |
| `project.manage` | FLAT | Org-wide project admin (HR steps, eval, salary status) | `[608,610]` |
| `finance.manage` | FLAT | Edit budget/cases | admin ‖ board (`<=6`) |
| `finance.unlock` ⚠️ | FLAT (narrow) | Release a *locked* finance record; unlock checkitems | **`610` alone** (`HQ_USER_ID`) |
| `finance.analyze` | FLAT | AI finance analysis + executive finance chat | `<=6` ‖ `[608,610]` |
| `incident.access` | REL | View an incident (admin/board/PM/reporter/assignee) | role + relationship |
| `incident.manage` | FLAT (+PM subset) | Administer incidents / advance workflow | `<6` ‖ `[608,610]`; `==6` gets manager-field subset |
| `evaluation.manage` | REL (mentor/HR/PM) | Create/approve personnel evaluations & goals | `[608,610,631]`, mentor_id, board, PM-of-members |

## 勤怠 (Timesheet)

| Rule | Kind | Decision | Granted today by |
|---|---|---|---|
| `timesheet.access` | FLAT/visibility | See the timesheet app | non-partner |
| `timesheet.edit_own` | REL (owner+status) | Edit own report/overtime while draft/rejected | record owner |
| `timesheet.manage_all` | FLAT | Operate/approve ANY user's timecard/shift/overtime | admin ‖ **`work_authority==1`** column |
| `timesheet.approve_assigned` | REL (PM) | Approve for managed projects only | PM of project |
| `timesheet.export_csv` | FLAT | Export work CSV | admin ‖ `position_id==6` |
| `attendance.confirm` | FLAT | Confirm/unconfirm 休業 attendance | admin |
| `plannedleave.approve_admin` ⚠️ | FLAT (admin-only) | Final approval of planned-leave change requests | **`[608,610]` only — NOT board** |
| `plannedleave.approve_pm` | REL (PM) | PM-step approval of planned-leave requests | PM of request's project |
| `paidleave.manage` | FLAT | Manage paid-leave ledger + policy | `[608,610]` |
| *(constraint)* `no_self_approve` | REL (negative) | Nobody approves their own record (admin excepted) | enforced in code, not granted |

## コミュニケーション (Board / Notice / Contact / Calendar)

| Rule | Kind | Decision | Granted today by |
|---|---|---|---|
| `board.access` | FLAT | Use board/chat; **create** restricted from partner | all members; create = non-partner |
| `notice.manage` ⚠️ | FLAT | Create/edit/delete お知らせ | admin ‖ board — **no backend guard today (frontend-only)** |
| `post.access` | MASK+REL | Posts/challenges feed; receipts = author/admin | hidden from partner+registered |
| `contact.access` | MASK+REL | Contact screens; records owner-scoped | hidden from partner+registered |
| `calendar.summary.view` | REL | View meeting summaries (participants + admin) | event members/viewers + `[608,610]` |

## 資産・サポート・ファイル・フォーム

| Rule | Kind | Decision | Granted today by |
|---|---|---|---|
| `asset.category.manage` | FLAT | CRUD asset categories & field defs | `[608,610]` |
| `asset.manage` | REL (admin+own) | Manage assets; non-admins scoped to own | admin full; owner own |
| `asset.reveal_secret` | REL (admin+own) | Decrypt an asset credential field | admin ‖ owner |
| `support.manage` | FLAT | FAQ / system-update admin | `[608,610]` |
| `support.inbox.view` ⚠️ | FLAT | See full consult inbox (else own only) | **wider list `[610,608,516,517,519,518,526,494]`** |
| `support.emergency_contact.manage` | REL (admin+own) | Manage emergency contacts | admin all; owner own |
| `form.manage` | REL (admin + form-admin + own answers) | Manage forms / see answers | admin; `custom_form_users.authority==1`; own answers |
| `drive.manage` | REL (owner + PM + ACL) | Drive/file node view/update/share | `DriveNodePolicy` (the one real Policy) |

---

## Membership-scope masks (orthogonal — not role rules)

| Mask | Hides / restricts | Source |
|---|---|---|
| **partner** | post, project, timesheet/work, support, contact, learning*, board-create, asset tools, badges | `partner_flag==1` (auto-set when `position_id==14`); `auth.isPartner` |
| **registered** | post, learning, contact + attendance special-casing | `position_id==15`; `auth.isRegistered` |

\* learning has a 7-ID partner allow-list (`Footer.vue:63`); timesheet nav has a 3-ID allow-list (`SideMenu.vue:82`) — ad-hoc exceptions.

---

## Awkward edges that need an explicit home (decisions)

1. **`610`-alone HQ powers** (`finance.unlock`, checkitem unlock) — narrower than `admin.access`. → its own rule, granted to whoever should hold it; no magic ID.
2. **Hardcoded ID allow-lists** — support-inbox (8 ids), learning (7 ids), timesheet nav (3 ids), evaluation `631`. These are people granted access ad-hoc. The *entire point* of the new system is to replace these with role/per-member rule grants. None should survive as literals.
3. **`work_authority==1`** DB column = de-facto `timesheet.manage_all` for non-admins. Keep the column as one input that maps to the rule, or migrate it into role grants.
4. **`hasPrivilage`** is redefined inconsistently in ~6 components (store formula ≠ `ProjectDetail.vue` ≠ `WorkHeader.vue`). Needs ONE definition = `project.approve ∨ project.manage_assigned ∨ admin.access`.
5. **`isMentor`** has **0 usages** — delete. **`isEmployee`** has 1 — fold into a rule or inline.
6. **Server-side gaps** — `notice_save`/`notice_delete` and *all* admin routes have no backend authorization; gating is frontend-only. Centralizing adds the missing server gate (a real security fix, not just cleanup).
7. **Bug:** `setUser` keeps `partner_flag`/`position_id` legacy fallback for `isPartner/isRegistered`; `applyCommunityPayload` uses scope only — so switching community can flip apparent partner status.

---

## What this catalog implies for the UI

The full set is **~22 grantable rules** grouped under 5 app headings + 2 membership masks. That is small enough for a single role×rule checkbox matrix (exactly the page Codex already built at `/admin_control/permissions`) — we feed it *this closed list* instead of the confusing app/position scope matrix, drop the position-features tab, and lock the Admin column all-on. Relational rules show in the matrix as the *grant* (the "own vs all" narrowing stays in code, invisible to the admin).
