# Queue / console CommunityContext binding — deferred design

**Status:** DEFERRED (decided 2026-07-04). Currently latent — see "Why deferred". Execute the checklist below **before onboarding a second community**.

## Problem

`CommunityContext` is bound per HTTP request by the `ResolveActiveCommunity` middleware. Queue jobs and console commands run with **no** request, so no context is bound. The `BelongsToCommunity` global scope **fails open** when no context is present (`app()->bound(...)` → false → no `community_id` filter). Therefore any community-scoped **read** inside a job/command sees **all communities' rows**.

- **Write side is already handled** where it matters: jobs that create scoped rows resolve each user's community from the `community_user` pivot (preferring `is_default`, matching `CommunityResolver`) and stamp `community_id` explicitly — e.g. `app/Jobs/AttendanceClose.php` (`$communityByUser` + `upsert`). This bypasses the (also-unbound) `creating` hook.
- **Read side is NOT handled**: no job/command binds a context, so cross-community reads are unscoped.

## Why deferred (currently safe)

The app runs a **single community (`glowd`)** today. With one community, fail-open reads return only glowd's rows — identical to scoped behavior. The gap becomes a real data-isolation bug **only when a 2nd community exists**. Doing the full sweep now = high payroll-regression risk (touches ~70 salary-critical jobs) for zero current benefit, and a half-done sweep (some jobs scoped, some not) is worse than a consistent deferral.

## Intended design (implement at community #2)

1. **Add a system-context helper** on `CommunityContext` (it's `app->scoped()`, so per-job lifecycle):
   - `runFor(Community|int $community, callable $fn)` — set the active community (WITHOUT a user membership; needs a small `setCommunity()` / "system membership" extension, since today only `setMembership(CommunityMembership)` exists), run `$fn`, restore the prior state in a `finally`.
   - Reads inside `$fn` are then scoped to `$community`.
2. **Per-community jobs** (operate on one community): bind that community and run.
3. **All-community jobs** (nightly sweeps over every community): loop —
   ```php
   foreach (Community::query()->get() as $c) {
       app(CommunityContext::class)->runFor($c, fn () => $this->processCommunity($c));
   }
   ```
4. **Queued jobs**: serialize the target `community_id` into the job's constructor/props so `handle()` can `runFor()` it (the queue worker has no request context either).
5. **Keep** the explicit per-user `community_id` stamping on writes (defense in depth even with context bound).

## Execution checklist (priority = salary/data-isolation first)

Highest risk (payroll / attendance / leave) — do first and re-validate numbers per community:
- `app/Console/Commands/AutoAttendanceConfirm.php` + `app/Services/TimeSheet/AutoAttendanceConfirm.php`
- `app/Jobs/AttendanceClose.php` (writes handled; add read-scope)
- `paid-leave:grant` / `:expire` / `:reconcile-usages` (`GeneratePaidLeaveGrants`, `ExpirePaidLeaveGrants`, `ReconcilePaidLeaveUsages`, `ClearPaidLeaveLedgerData`)
- `refresh:expire` / `app:refresh-automation` + `app/Jobs/RefreshAutoAllocation.php`
- `DailyReportConfirmation`, `ApproveDailyReport`, `AlertDailyReportMissingStreaks`
- `app/Jobs/CreateDepartureAlert.php` / `DepartureAlertSend`
- `app/Jobs/GenerateLunchChallenge.php`, `CheckUserEvaluation`, `CalculateMonthlyGoalSlot`

Then the rest (notifications, sync, thumbnails, audit) — lower isolation risk; audit each for community-scoped reads.

Kintone imports (`ImportKintone*`) and CoA installers write per-community data — verify they target the right community.

## Verification when executed

Create a 2nd community with its own users + shift/leave data, run each salary job, and confirm: (a) no rows from community A appear in community B's outputs, (b) per-community payroll numbers match single-community expectations, (c) queued jobs scope correctly under a worker (no request context).

## Coupled: fail-closed scope (deferred together)

`BelongsToCommunity` currently **fails open** (no context → no filter). Flipping it to **fail-closed** (no context → return nothing / throw) is the stronger multi-community guarantee, but it would break every job/command in this doc that runs without a bound context. **Do not enable fail-closed until the jobs above bind context.** Sequence: (1) implement the `runFor` binding across jobs/commands, (2) then switch the scope to fail-closed, (3) re-run the whole job suite to confirm nothing silently returns empty.
