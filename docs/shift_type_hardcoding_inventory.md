# Shift-type hardcoding inventory (breakdown — decision deferred)

> **⚠️ CORRECTION (2026-06-29): id 27 (特別休暇) is NOT dead.** Every "id 27 does not exist / drop it / dead" statement below was written against the local test DB, which lacks id 27. **Production has id 27** (特別休暇, full_day=2, value=480) — a live full-day special holiday with its own payroll field. It now has category `special_holiday` (model const `CATEGORY_SPECIAL_HOLIDAY`), is seeded + assigned to every glowd role by migration `2026_06_29_000001`, and the regressed sites (`countSpecialHoliday`, AutoAttendance `spec_holiday` + annual_full exclusion, `SharedService:45`, `WorkRecordRow.vue`) were restored. `WorkShifts.vue` is now fully converted (no longer deferred). See `docs/capability_migration_diary.md` 2026-06-29 entry.

**Problem:** `shift_types` are now CRUD-customizable per community (admins add/edit/delete), but ~100+ code sites hardcode shift-type **ids** (and a few names) to mean fixed business categories. So editing/deleting a shift type — or a *different community* having different ids — silently breaks payroll/attendance/leave logic. Same "customizable data vs hardcoded system meaning" tension as `position_id`.

Only 2 ids are constants today (`shiftType::LEGAL_HOLIDAY_ID = 18`, `UNUSED_IDS = [17]`); everything else is bare literals.

## glowd id → meaning (from shift_types names + code usage)

| id | name | semantic the code relies on |
|----|------|---|
| 0 | 休日 | day-off / non-working |
| 1 | 勤務 | work day |
| 2 | 休業 | leave of absence (`closed_day`) |
| 3 | 計画有給 | **planned paid leave** (core in PaidLeaveLedgerService) |
| 5 | 1日年休 | annual paid leave — full day |
| 6 | 半日年休 | annual paid leave — half day |
| 7–13 | 1〜7時間年休 | annual paid leave — hourly |
| 14 | 慶弔休暇 | special leave (condolence) |
| 15 | 転勤休暇 | special leave (transfer) |
| 16 | ODA休暇 | special leave (ODA) |
| 17 | 代休 | comp holiday (currently UNUSED_IDS) |
| 18 | 法定休日 | **legal holiday** |
| 19–26 | 半日〜7時間休日 | holiday work — hourly |
| 27 | — | does not exist (still referenced defensively in arrays) |

## Semantic GROUPS the logic depends on (the real "categories")

1. **Work** — `[1]` (sometimes `[1,6,7..13]` = "working_shifts" incl. partial-leave days that still count as work).
2. **Non-working / day-off** — `[0, 18]`.
3. **Planned paid leave** — `[3]` (PaidLeaveLedger ledger postings, carryover, AutoAttendance).
4. **Annual paid leave** (full/half/hourly) — `[5,6,7,8,9,10,11,12,13]`.
5. **Special leave** — `[14,15,16]`.
6. **Comp holiday** — `[17]`.
7. **Legal holiday** — `[18]`.
8. **Holiday work (hourly)** — `[19..26]`.
9. Ad-hoc combined sets, e.g. SharedService `[0,2,3,5,14,15,16,18,27]`, AutoAttendance petition map (id→count field), AdminWork leave columns `[3,5,6,7,8,9,10,11,12,13,14,15,16,17]`.

## Where it's hardcoded (inventory)

**Backend (PHP):**
- `app/Services/TimeSheet/AutoAttendanceConfirm.php` — **~24 sites** (heaviest): maps each id to a petition/leave count (`shift_type==3`→planned_paid, `==6`→half-day, `==5,13,12…`→petitionType*, `==2`→closed, `==17`→comp), `working_shifts=[1,6,7..13]`, workday/holiday sets `[1,6,7..13,19..26]`, special-leave exclusion `[14,15,16,17,18,27]`.
- `app/Http/Controllers/WorkController.php` — 7 (`whereNotIn shift_type [18]`, `[0,18,19..26]`, `shift_type==3`).
- `app/Services/PaidLeaveLedgerService.php` — 6 (`shift_type==3` planned-paid throughout: ledger, carryover ancestors, `[0,18]`).
- `app/Http/Controllers/AdminWorkController.php` — 5 (`[0,18,19..26]`, `shift_type==18`, `==3`).
- `app/Services/TimeSheet/ShiftService.php` — 4 (LEGAL_HOLIDAY_ID, UNUSED_IDS — the only constant users).
- `RemindController.php` (3), `SharedService.php` (2), `DashboardController.php` (2), `DailyReportConfirmation.php` (2), `CustomfieldController.php` (1: `[2,5,0,14,15,3]`), `AutoJobController.php` (1), `ApproveDailyReport.php` (1), `CreateDepartureAlert.php` (1).

**Frontend (Vue/TS):**
- `components/Work/WorkReport.vue` — **~57 sites** (heaviest; per-leave-type input rows + logic).
- `components/Work/WorkShifts.vue` — ~22 (`shift_type.id===3` planned, `===16` ODA, holiday set `[0,18,19..26]`).
- `components/Work/WorkContainer.vue` (7: `shift_type.id==3 / ==2`), `WorkRecordRow.vue` (4: `[0,3,5,14,15,16,17,18,27]`), `ShiftApproval.vue` (4: `[0,5,14,15,16,3]`, `[0,18]`), `CustomField.vue` (3: `id==0`), `AdminWork.vue` (5: `shift_type.id==18`, leave columns `[3,5,6..17]`), `workApi.ts` (2), `WorkNotSubmitted.vue` (1).

(Note: `CommunityPermissionControl.vue`'s 12 are the new role-assignment tiles — not hardcoded semantics; ignore.)

## Decision space (for later)

The fix mirrors the capability/position pattern: give shift types a **stable, code-facing classification** decoupled from the customizable id.

- **Option A — `category` enum column on `shift_types`.** A closed set of system categories (`work`, `day_off`, `planned_paid_leave`, `annual_leave_full`, `annual_leave_half`, `annual_leave_hourly`, `special_leave`, `comp_holiday`, `legal_holiday`, `holiday_work`). Code keys off `category` (+ a `hours`/`unit` field for the hourly ones) instead of id. Admins CRUD shift types but choose a category. Multi-community-safe. **Most aligned with the rest of the refactor.** Biggest effort (touch ~20 files + seed categories + the hourly-amount nuance).
- **Option B — protected "system" shift types.** Flag certain ids as system (non-deletable, fixed meaning); allow custom ones alongside. Smaller change, but logic stays id-coupled and is NOT multi-community-safe (ids differ per community).
- **Option C — boolean attribute flags** (`counts_as_work`, `is_paid_leave`, `is_legal_holiday`, `leave_unit`…). Granular but several flags to reason about; overlaps with A.

**Open nuances to resolve when deciding:**
- The **hourly** annual-leave / holiday-work ids encode an *amount* (1h–7h), not just a category — a category alone isn't enough; needs an `hours`/`minutes` attribute too.
- `27` is referenced but doesn't exist (dead defensive code — clean up).
- `17 代休` is in UNUSED_IDS yet still referenced — clarify its status.
- AutoAttendanceConfirm's id→`petitionType*` mapping is the densest knot; it likely drives payroll export field names — needs care.

**Status:** breakdown only. No code changed. Recommendation (mine): Option A (category enum + hours attribute), done as its own carefully-verified migration like the position/capability work — but deferred to user decision.

---

## UPDATE 2026-06-26 — Option A chosen; FOUNDATION built (no logic switched yet)

User chose Option A (salary-critical → careful/behavior-preserving, gated).

**Done (foundation, zero behavior change):**
- Migration `2026_06_26_000007_add_category_to_shift_types.php` (ran): added `category` (string, indexed) + `hours` (decimal) to `shift_types`; seeded glowd's 26 types into the 11-category taxonomy (see table above; partition VERIFIED clean — every id has exactly one category, 0 uncategorized).
- `shiftType` model: added `CATEGORY_*` consts, `idsFor(category|[])` (active-community id resolver, memoized), `idFor(category, hours?)` (disambiguate hourly), `isCategory()`. **Also fixed a latent bug: shiftType had no `$guarded`/`$fillable`, so the new CRUD `create()` would have thrown MassAssignmentException — added `$guarded = []` + casts.**

**Divergence report (category-union vs hardcoded literals) — the gate before converting call sites:**
- EXACT: non_working `[0,18]`, working_shifts `[1,6,7-13]`, holiday_set `[0,18,19-26]`, planned `[3]`, annual_hourly `[7-13]`.
- ⚠️ **shift_workdays (AutoAttendanceConfirm:197) MISSING 25 (6時間休日)** — literal `[1,6,7-13,19,20,21,22,23,24,26]`; category-union adds 25. Likely a typo BUG; affects attendance day-count → salary. NEEDS DECISION: fix (include 25) vs preserve (exclude).
- **27** in special_excl + shared literals doesn't exist (dead ref); union omits it — converting is harmless.
- **customfield (CustomfieldController:43)** uses special_leave 14,15 but NOT 16 (ODA); category `special_leave` includes 16 → converting adds ODA. RESOLVED: bug — include ODA (use full `special_leave`).

**DECISIONS (user, 2026-06-26):**
1. `shift_workdays` missing 25 = **BUG → include 25** (use full `holiday_work` category; attendance day-count will now include 6h-holiday-work, consistent with 5h/7h).
2. `customfield` ODA exclusion = **BUG → include ODA** (decision REVISED 2026-06-26: user corrected — ODA(16) must count as `special_leave`). `CustomfieldController:43` converts to the full `special_leave` category, ADDING ODA(16) to the `[0,2,3,5,14,15]` set. So `special_leave` is used wholesale (14,15,16) everywhere — no finer split needed.
3. `27` dead reference → drop on conversion (no behavior change).

**Key technical note:** `shiftType::idsFor()` works in BOTH requests and jobs. In a request it scopes to the active community; in a job/command (no CommunityContext) the BelongsToCommunity scope fails open → returns all (= glowd's) category ids = today's behavior. So conversions are safe in AutoAttendanceConfirm (a queued job) etc. (Becomes properly per-community once jobs bind context — the separately-deferred item.)

**TAXONOMY REVISED (2026-06-26):** `special_leave` split into 3 categories — `special_leave_condolence`(14)/`special_leave_transfer`(15)/`special_leave_oda`(16) — because AutoAttendanceConfirm counts them into distinct payroll fields. `shiftType::SPECIAL_LEAVE` group const (= the 3) for "any special leave". Migrations: `000007` seed updated + `000008` re-seeds live DB. So the 11→13 category set now: work/day_off/absence/planned_paid_leave/annual_leave_full/annual_leave_half/annual_leave_hourly/**special_leave_condolence/transfer/oda**/comp_holiday/legal_holiday/holiday_work.

**CONVERSION PROGRESS:**
- ✅ **ShiftService + misc services/commands DONE (2026-06-26)** — ShiftService (requestedShiftType===planned, hasOdaShift→ODA, getAvailable LEGAL idFor, calculateTotalHolidays holiday-set; `countSpecialHoliday` shift_type 27 → `whereRaw('0=1')` always-0 since 27 never existed; UNUSED_IDS const left as centralized), RemindController (work, + indirect `$value2['type']!=1`→WORK), SharedService (work + composite [0,2,3,5,14,15,16,18,27]→categories, 27 dropped), DashboardController (work), CustomfieldController (composite **+ODA per decision**), DailyReportConfirmation (work; #[Description] text still says shift_type=1 — doc only), ApproveDailyReport (`!==1`→WORK), AutoJobController (whereNot 3→PLANNED), CreateDepartureAlert (work). Imports added where missing. All lint clean; all resolutions verified == originals (work=[1], planned=[3], ODA=16, legal=18, holiday set=[0,18,19-26], shared=[0,2,3,5,14,15,16,18], customfield=[0,2,3,5,14,15,16]+ODA).
- ✅ **`WorkController` + `AdminWorkController` DONE (2026-06-26)** — 9 sites: `[0,18,19-26]` holiday set → `idsFor([DAY_OFF,LEGAL_HOLIDAY,HOLIDAY_WORK])` (×2; literal already had 25, exact match); `whereNotIn [18]`→`idsFor(LEGAL_HOLIDAY)`; `$record->shift_type==18`→`===idFor(LEGAL_HOLIDAY)`; `where('shift_type',3)`→`whereIn(idsFor(PLANNED_PAID_LEAVE))` (×3); `whereNot('shift_type',3)`→`whereNotIn(idsFor(PLANNED_PAID_LEAVE))`; the `shiftRecord::create(["shift_type"=>3])` WRITE → `idFor(PLANNED_PAID_LEAVE)`. Added shiftType import to AdminWorkController. Lint clean, 0 residual, resolutions verified (legal=[18], holiday set=[0,18,19-26], planned=[3]).
- ✅ **`AutoAttendanceConfirm` DONE (2026-06-26)** — the densest/most salary-critical (~24 sites). petitionType map → `idFor(category[,hours])`; the `[13..6]` hours-loop → `idsFor([hourly,half])` with the `===6` half-check via `idFor(half)`; planned-minutes switch → category + the new `hours` attribute (added `category,hours` to the shiftType eager-load select); `working_shifts`/`whereNotIn[0,18]`/`where 0`/`where 2` → categories; special counts → the 3 sub-categories; **shift_workdays got the +25 fix**; dead `27` count → `0`. VERIFIED: all 25 resolver calls return the exact original id(s); only shift_workdays changes (gains 25, as decided). Lint clean, 0 residual literals.
- ✅ **`PaidLeaveLedgerService` DONE (2026-06-26)** — 7 sites: 2 DB `where('shift_type',3)` → `whereIn('shift_type', shiftType::idsFor(CATEGORY_PLANNED_PAID_LEAVE))`; 4 property checks `(int)$x->shift_type ===/!== 3` → `in_array(...idsFor(PLANNED_PAID_LEAVE)...)`; 1 `[0,18]` → `idsFor([DAY_OFF, LEGAL_HOLIDAY])`. Lint clean, 0 residual, verified `idsFor(planned_paid_leave)=[3]` and `[day_off,legal_holiday]=[0,18]` (exact match to originals).

**CONVERSION PLAN (remaining, focused verified passes):**
- Backend, domain by domain, each with a before/after id-set audit:
  1. ✅ `PaidLeaveLedgerService` — DONE.
  2. `AutoAttendanceConfirm` (~24; the id→petitionType* map via `idFor(category,hours)`; `working_shifts`; apply the **+25 fix**).
  3. `WorkController` / `AdminWorkController` (holiday sets, `==3`, `==18`).
  4. `ShiftService` (already constant-based; switch LEGAL_HOLIDAY/UNUSED to category for consistency — low risk).
  5. Remaining controllers/services/commands (Remind, Shared, Dashboard, CustomfieldController [full special_leave incl. ODA], DailyReport, ApproveDailyReport, AutoJob, CreateDepartureAlert).
- ✅ **BACKEND COMPLETE + DATA-VALIDATED** (all controllers/services/commands/jobs converted + verified).
  - **Sample-month payroll diff (2022-05, 111 users): 666 field checks, 0 mismatches** — new (category) code == old (literal) logic on real data (shift_count, workedday_count, condolence/transfer/oda/comp). Script: scratchpad/payroll_diff.php.
  - **No `shift_type 25` records exist anywhere in the DB** → the +25 fix affects zero real data (correct for future, impacts nobody now). So the backend conversion is behavior-identical in practice.
- 🔄 Frontend (build-verification pending user; can't run Vite here):
  - ✅ **Payloads (step 1) DONE** — added `category`,`hours` to all FE-facing shift-type selects (ShiftService colon-selects, WorkController ×4, AdminWorkController shiftType select, ShiftTypeController index). Also fixed a missed backend check `ShiftService:164 $type->id==0` → `category===day_off`. Additive/safe; lint clean.
  - ✅ **Clean object-based sites DONE (5 files)** — AdminWork.vue (`shift_type.id==18`→`category==='legal_holiday'`), ShiftApproval.vue (2 sets), WorkContainer.vue (planned/absence ×4), WorkRecordRow.vue (set), CustomField.vue (`id==0`→day_off). All use `shift_type.category` now (payloads carry it). 0 residual in these files.
  - ✅ **WorkReport.vue: NO-OP** — the "~57" was a false count (its `.type` refs are project-detail types: incident/allowance/comment, NOT shift_type). Its shift-type uses pass the object (`:shift_type="shift?.shift_type"`) or the id as a value — no literal comparisons.
  - ⚠️ **WorkShifts.vue: DEFERRED (intricate + risky + drives shift creation)** — `selectedShiftType` is an *id* compared to `3`(planned)/`27`(dead) in ~20 interdependent checks (lines 27,75,78,82,83,88,89,90,159,468,617,703,709,717,720,813,818,824) + raw `shift.type`/`element.type`/`record.type` vs 0/18/19/3 (422-426,773-774). Needs an id→category map from `shiftTypes.value` + careful rewrite; 27 is dead UI. MUST be done with the ability to run/test the shift-entry UI (it creates shift_records → feeds payroll). Flagged for a build-verified pass.
- ⏳ Final: full attendance/payroll re-computation diff on a sample month (old vs new) before considering it done. NB: static grep can miss INDIRECT id flows (id stored in a renamed var/key then compared to a literal — e.g. the `$value2['type'] != 1` found in RemindController). The sample-month diff is the safety net for any remaining indirect cases.
