# Community Logic Footprint

Last updated: 2026-06-24

## Branch and worktree

- Branch: `community_logic`
- Worktree: `/Users/tumur/projects/clap-8-community_logic`
- Purpose: keep community migration work isolated from dirty local `main` and normal development push/pull flow.
- Current state: local branch only unless explicitly pushed later.

## Chat context summary

- The app is moving from a single implicit company context to multiple communities.
- Existing users are seeded into the default `グラウド株式会社` community.
- Users can belong to multiple communities and switch active community.
- Existing role behavior must remain compatible while moving away from hardcoded `position_id`, `[608, 610]`, `partner_flag`, `auth.isAdmin`, `auth.isBoss`, `auth.isPM`, and `auth.isPartner`.
- Community roles are editable community data, not built-in application roles. The migration seeds starting roles only.
- Scope settings start simple from `SCOPE_IMAGE.MD`: app feature scopes and position feature scopes. These are stored separately from internal capabilities and will be wired into actual app gates later.
- There is no Owner role. The top protected role is Admin.
- Admin is fixed, cannot be edited or deleted, and the app must always keep at least one Admin member in each community.
- `isPartner` is important and remains a distinct membership scope, not just a role permission.
- `position_id == 15` maps to registered scope.
- `user_linked_accounts` active identity switching is being replaced by remembered real-account switching, similar to a browser account chooser.
- Community UI work is intentionally in `resources/js/components/AccountControl/CommunityControl/` instead of stuffing more logic into `AdminControlList.vue`.
- The community control belongs on the top admin account route only, now labeled `コミュニティ`, and should not persist across every admin route.
- `SideMenu.vue` now uses the active community name/icon instead of hardcoded community branding.

## Schema and backend added

- `communities`
- `community_roles`
- `community_user`
- `community_membership_audit_logs`
- Active community resolution middleware: `community.active`
- Community context/auth payload endpoint: `/community_context`
- Community switching endpoint: `/community_context/switch`
- Community settings update endpoint: `PATCH /community_context`
- Role list/update endpoints:
  - `GET /community_context/roles`
  - `PATCH /community_context/roles/{role}`
- Capability catalog endpoint:
  - `GET /community_context/capabilities`

## Permission inventory

Focused search command:

```sh
rg -n "auth\\.is(Admin|Boss|PM|Partner|Registered)|hasCapability|partner_flag|position_id\\s*[<=>!]=?|\\[608, 610\\]|isPartner\\(|isRegistered\\(|isAdmin\\(|isBoss\\(|isPM\\(" resources/js app routes database tests
```

Rough current counts in `resources/js` and `app`:

- Admin style checks: 131
- Boss/director style checks: 37
- PM style checks: 17
- Partner scope checks: 78
- Registered scope checks: 42

High-risk legacy patterns still present:

- `auth.isAdmin` and `user.isAdmin()` map to Admin role or `admin.access`.
- `auth.isBoss` maps to board/director behavior or `project.approve`.
- `auth.isPM` maps to PM behavior or `project.manage_assigned`.
- `auth.isPartner` maps to `community_user.scope = partner` with legacy `partner_flag` fallback.
- `auth.isRegistered` maps to `community_user.scope = registered` with legacy `position_id == 15` fallback.
- Direct `[608, 610]`, `position_id`, and `partner_flag` checks still need gradual replacement after compatibility coverage is stronger.

## Capability catalog

Source of truth:

- `app/Services/Community/CommunityCapabilityCatalog.php`

Current internal capabilities are grouped as:

- 管理: `admin.access`, `community.manage`, `role.manage`, `user.manage`
- 主要アプリ: `board.access`, `dashboard.access`, `post.access`, `learning.access`, `contact.access`, `notice.manage`
- プロジェクト: `project.access`, `project.manage`, `project.approve`, `project.manage_assigned`, `incident.manage`, `finance.manage`
- 勤怠: `timesheet.access`, `timesheet.self`, `timesheet.manage`, `timesheet.approve_assigned`
- 資産・サポート: `asset.self`, `asset.manage`, `support.access`, `file.manage`

The role update endpoint validates submitted capability keys against this catalog.
Fresh migrations seed Japanese role names: `管理者`, `役員`, `PM`, `メンバー`, `登録社員`.
Migration `2026_06_24_000002_normalize_community_roles.php` collapses legacy `owner` memberships into `admin` for existing local/dev databases.
Migration `2026_06_24_000003_add_scopes_to_community_roles.php` adds `community_roles.scopes` and backfills the base scope defaults.

Editable base scopes:

- By App features: `プロジェクト`, `スケジュール`, `タイムシート`, `ラーニング`, `コンタクト`, `お知らせ`, `物品`, `サポート`, `フォーム`
- By Position Features: `管理本部`, `システム開発`, `人事`, `PM`, `役員`

## Frontend community control

Files:

- `resources/js/components/AccountControl/CommunityControl/CommunityAdmin.vue`
- `resources/js/components/AccountControl/CommunityControl/CommunityControl.vue`
- `resources/js/components/AccountControl/AdminControlList.vue`
- `resources/js/routes/admin.ts`
- `resources/js/components/Global/SideMenu.vue`
- `resources/js/store/auth.ts`

Current behavior:

- Community settings are opened with the edit icon next to the community title.
- The community settings modal only edits title and uploaded icon.
- App permissions are not inside the community settings modal.
- Admin route `/admin_control/permissions` shows a standalone role/scope matrix for editable community roles.
- Roles can be created from the permission settings page.
- Non-admin roles can be renamed, scope-toggled, and deleted when no members belong to them.
- The Admin role is displayed as fixed and locked in the permission settings page.
- Updating a permission checkbox persists the role capability list and refreshes the active auth community context.
- Partner and registered restrictions are called out as membership scope controls, not role capabilities.

## Test coverage

Focused PHP feature tests:

- Default community migration/backfill.
- Membership scope migration for partner and registered users.
- Active community resolver and legacy compatibility helpers.
- Community switching membership protection.
- Community title/icon update.
- Capability catalog exposure and role capability update.
- Remembered real-account switching protection.

Known quality gate note from earlier work:

- `php artisan test tests/Feature/CommunityLogicTest.php` has been the focused green gate.
- `npm run typecheck` previously failed on an unrelated existing Luxon type issue in `resources/js/components/Project/ProjectTabs/TaskCalendar.vue`.
