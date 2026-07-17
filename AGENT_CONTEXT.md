# MISO (clap-8) — Agent Context

This document is the primary onboarding map for AI agents working in this repository. It consolidates architecture, domain boundaries, conventions, and active migration work. Read this first, then drill into the linked docs for a specific feature.

## Related Documentation

| File | Use when |
|------|----------|
| [AGENTS.md](./AGENTS.md) | Quality gates, commit style, coding standards summary |
| [CONTRIBUTING.md](./CONTRIBUTING.md) | PR workflow, testing, contribution rules |
| [README.md](./README.md) | Setup, install, high-level product overview |
| [FEATURE.md](./FEATURE.md) | Business feature list (Japanese) — what the product does |
| [LEARNING_FLOW.md](./LEARNING_FLOW.md) | Learning/LMS remodel — domain model, progress rules, migration status |
| [DEVELOPER_SKILLS_GUIDE.md](./DEVELOPER_SKILLS_GUIDE.md) | Skill expectations for human contributors |
| [DASHBOARD_ANALYSIS.md](./DASHBOARD_ANALYSIS.md) | Dashboard architecture analysis and recommendations |
| [DASHBOARD_REFACTORING_SUMMARY.md](./DASHBOARD_REFACTORING_SUMMARY.md) | Completed dashboard refactor (config + Pinia) |
| [learning_table.sql](./learning_table.sql) | Learning database schema reference |

---

## Product Summary

**MISO** (repo: `clap-8`) is an integrated workplace platform for Japanese organizations: chat/boards, project management, timesheets, calendar, CRM, learning (LMS), assets, support desk, surveys, and admin controls. The UI is Japanese-first (`luxon` locale `ja`).

Core value: one SPA where teams communicate, manage projects/goals/finance, track attendance, run training programs, and operate HR/admin workflows.

---

## Tech Stack

| Layer | Stack |
|-------|-------|
| Backend | PHP 8.3+, **Laravel 13**, MySQL/MariaDB, Redis (recommended) |
| Frontend | **Vue 3** (Composition API), **TypeScript**, Vite 5, Vuetify 3, Tailwind CSS |
| State | Pinia (~31 stores) |
| Real-time | Custom socket emitter + Pusher auth endpoints + web push |
| AI | `laravel/ai`, `openai-php/client` — content generation, FAQ/regulation sync, MCP chat |
| Auth | Session (`web` guard) for SPA; Laravel Sanctum for mobile API |

Path aliases (Vite + tsconfig): `@/` → `resources/js/`, `utils/` → `resources/js/utils/`.

---

## High-Level Architecture

```
Browser (Vue SPA)
    │
    ├── Vue Router (resources/js/routes/*)
    ├── Pinia stores + composables (useApi, domain wrappers)
    └── axios → Laravel routes/web.php (~974 lines, auth group)
                    │
                    ├── Controllers (mostly fat, legacy)
                    ├── Services (partial extraction — Learning, TimeSheet, Contracts)
                    ├── Eloquent Models (~230)
                    ├── Jobs (async: sockets, thumbnails, notifications)
                    └── MySQL
```

**SPA shell:** Laravel renders `board.blade.php` with `<root>`; Vue Router handles all in-app navigation. Catch-all web routes delegate to `BoardController@index` for named app sections (`board`, `project`, `learning`, `dashboard`, etc.).

**Important:** This is a **monolith**, not a REST API-first design. Most endpoints are POST/GET paths like `/get_messages`, `/save_time_card` inside one authenticated middleware group.

---

## Repository Layout

```
clap-8/
├── app/
│   ├── Http/Controllers/     # 42 feature controllers (+ Auth/)
│   ├── Models/               # ~230 Eloquent models (mixed naming conventions)
│   ├── Services/             # 45 services — newer domains use DI; legacy uses SharedService
│   ├── Jobs/                 # 34 queued jobs
│   ├── Console/Commands/     # 32 artisan/cron commands
│   ├── Ai/Agents/            # Laravel AI agents (incidents)
│   ├── Domain/Contracts/     # PlanProvider, ActualProvider interfaces
│   ├── Infrastructure/       # Kintone, Google Sheets clients
│   ├── Events/               # 2 broadcast events (secondary)
│   └── Policies/             # DriveNodePolicy only
├── resources/js/
│   ├── app.ts                # Vue entry
│   ├── router.ts             # Router + navigation hooks
│   ├── routes/               # Domain-split route files
│   ├── components/           # 27 feature folders
│   ├── store/                # Pinia stores
│   ├── composables/          # Reusable logic (api, learningApi, …)
│   ├── types/                # Newer typed domains (learning.ts)
│   ├── interface/            # Legacy TS interfaces (24 files)
│   ├── config/               # dashboardCards.ts, learning.ts
│   └── utils/                # Helpers (workApi, learningProgress, …)
├── routes/
│   ├── web.php               # Primary route file (almost everything)
│   ├── api.php               # Sanctum mobile API (small)
│   └── channels.php          # Broadcast channels
├── database/migrations/      # 219 flat migrations (additive; core tables may predate repo)
└── tests/                    # Feature tests (Learning services recently added)
```

---

## Backend Entry Points

| Purpose | Path |
|---------|------|
| Web routes | `routes/web.php` |
| Mobile API | `routes/api.php` |
| HTTP kernel / middleware | `app/Http/Kernel.php` |
| DI bindings | `app/Providers/AppServiceProvider.php` |
| Legacy shared logic | `app/Services/SharedService.php` (injected into many controllers) |
| Real-time emit | `app/Jobs/SocketEmitter.php` |
| Pusher auth | `app/Http/Controllers/BoardController.php` |

### Controller → Domain Map

| Controller | Domain |
|------------|--------|
| `BoardController`, `FileController` | Chat/boards, Pusher auth, SPA catch-all |
| `TaskController` | Tasks, Gantt |
| `ProjectController`, `ProjectPlanController`, `ProjectProfitPlanController` | Projects, goals, finance, evaluation |
| `WorkController` | Timesheet, shifts, daily reports, paid leave |
| `CalendarController`, `GoogleController` | Calendar, Google OAuth |
| `PostController`, `RefreshController` | Social posts, refresh leave |
| `LessonController`, `LessonExamController` | Learning/LMS |
| `AdminAccountController`, `AdminWorkController`, `AdminPaidLeave*` | Admin/HR controls |
| `ContactController` | CRM |
| `AssetController`, `AssetCategoryController` | Asset management |
| `DriveController` | File drive (ACL policy) |
| `SupportController` | FAQ, regulations, helpdesk |
| `CustomFormController`, `PublicSurveyController` | Surveys/forms |
| `DashboardController`, `IncidentController` | Dashboard data, incidents |
| `OpenAiController`, `FinanceChatController` | AI streaming, finance chat |
| `CommunityController`, `MemberController`, `UserController`, `EmployeeController` | People/org |
| `NoticeController`, `RemindController` | Notices, reminders, badges |
| `PushController`, `NativeController` | Web push, FCM |

### Service Layer (prefer this pattern for new code)

| Directory | Examples |
|-----------|----------|
| `Services/Learning/` | `LessonViewService`, `LearningProgressService`, `LearningParticipantProgressService` |
| `Services/TimeSheet/` | `ShiftService`, `TimecardAuditLogService`, `WorkReceiptOcrService` |
| `Services/Contracts/` | `ContractExtractionService`, OCR/PDF |
| `Services/Faq/`, `Services/Regulations/` | OpenAI vector sync |
| Root services | `SharedService` (legacy), `BadgeService`, `IncidentService`, `RefreshService` |

**Pattern for new backend work:** thin controller → injected service → Eloquent. Avoid adding logic to `SharedService` unless matching existing callers.

### Route Organization (`routes/web.php`)

1. **Public:** Zoom webhooks, iCal export, cron triggers, CDN, public surveys, signed departure report
2. **Auth scaffolding:** `Auth::routes(['register' => false])`
3. **Authenticated group** `middleware: ['auth', 'session.expired']`:
   - JSON endpoints grouped by feature (see controller map)
   - SPA catch-all: `/{name}/{any?}` → `BoardController@index` for app sections

**Note:** Some controllers are imported in `web.php` but may be missing from the codebase (e.g. `GoalToolController`, `TimesheetToolController`). Verify class exists before relying on those routes.

---

## Frontend Entry Points

| Purpose | Path |
|---------|------|
| JS bootstrap | `resources/js/app.ts` |
| Axios + CSRF | `resources/js/bootstrap.ts` |
| Router instance | `resources/js/router.ts` |
| Route aggregation | `resources/js/routes/index.ts` |
| App shell | `resources/js/components/Root.vue` |
| Side navigation | `resources/js/components/Global/SideMenu.vue` |
| Standard API layer | `resources/js/composables/api.ts` |
| Learning API wrapper | `resources/js/composables/learningApi.ts` |
| Auth store | `resources/js/store/auth.ts` |

### Vue Route Files → Domains

| File | URL prefix / domain |
|------|---------------------|
| `routes/community.ts` | `/community` — directory, offices |
| `routes/board.ts` | `/board` — chat rooms |
| `routes/user.ts` | `/user/:userId` — profile |
| `routes/post.ts` | `/post`, `/knowledge`, `/nice`, `/challenge` |
| `routes/project.ts` | `/project/:projectId/*` |
| `routes/admin.ts` | `/admin_control/*` |
| `routes/misc.ts` | schedule, timesheet, survey, settings, help, contact, assets |
| `routes/learning.ts` | `/learning/:lessonThemeId/*` |
| `routes/dashboard.ts` | `/dashboard/:type?/:itemId?` |

### Component Folders (27 top-level)

`AccountControl/`, `Asset/`, `Auth/`, `Board/`, `Calendar/`, `Community/`, `Contact/`, `Dashboard/`, `Form/`, `Global/`, `Header/`, `Help/`, `Icons/`, `Incident/`, `Learning/`, `Mobile/`, `Notice/`, `Post/`, `Profile/`, `Project/`, `PublicSurvey/`, `Settings/`, `Support/`, `Survey/`, `Task/`, `Work/`, plus `Root.vue`.

Admin learning UI: `AccountControl/LearningControl/` (Authoring/, Participant/, Shell/ subfolders).

Learner UI: `Learning/` (BasicKnowledge/, Exam/, Portfolio/, Discussion/, shared/).

---

## Domain Feature Map

Use [FEATURE.md](./FEATURE.md) for the full business list. Quick navigation for agents:

| Feature | Frontend | Backend | Key models/tables |
|---------|----------|---------|-------------------|
| Dashboard | `Dashboard/`, `store/dashboard*.ts`, `config/dashboardCards.ts` | `DashboardController` | Various aggregated queries |
| Board/Chat | `Board/` | `BoardController` | `boardRecord`, `messageRecord`, … |
| Tasks | `Task/` | `TaskController` | `taskRecord`, … |
| Projects | `Project/` | `ProjectController`, `ProjectPlanController` | `project_records`, `project_goals`, … |
| Timesheet | `Work/` | `WorkController` | `timecard_*`, `shift_*` |
| Calendar | `Calendar/` | `CalendarController` | calendar/shift tables |
| Learning | `Learning/`, `AccountControl/LearningControl/` | `LessonController`, `LessonExamController` | `lesson_*` — see LEARNING_FLOW.md |
| Posts | `Post/` | `PostController` | post tables |
| Contact/CRM | `Contact/` | `ContactController` | `contact_*` |
| Assets | `Asset/` | `AssetController` | asset category/item tables |
| Drive | (file UI in projects/board) | `DriveController` | `drive_nodes`, ACL |
| Support | `Support/`, nested in dashboard routes | `SupportController` | FAQ, regulations |
| Admin | `AccountControl/` | `AdminAccountController`, `AdminWorkController` | users, offices, settings |
| Incidents | `Incident/` | `IncidentController` | `incidents`, `incident_reports` |
| Surveys/Forms | `Survey/`, `Form/` | `CustomFormController` | `custom_forms`, `survey_*` |

---

## Authentication & Authorization

### Authentication
- **SPA:** session cookie, login field is `login` (not email) — see `config/auth.php`
- **Mobile:** `POST /api/sanctum/token` → Sanctum bearer tokens
- **Session idle:** `CheckSessionExpired` middleware (`session.expired`)
- **401 handling:** axios interceptor in `bootstrap.ts` redirects to `/login`

### Authorization (mostly ad hoc — no Spatie roles)
- `User.admin_flag` — global admin
- `User.position_id` — hierarchy (lower number = more authority; `< 6` often means boss-level)
- Hardcoded admin user IDs `[608, 610]` in several places
- Project membership / manager checks on models
- **Only formal policy:** `DriveNodePolicy` for drive ACL
- Board-level admin via `boardToUser` pivot

When adding access checks, follow nearby controller patterns; do not assume Laravel policies exist.

---

## Real-Time & Notifications

1. **Primary:** `SocketEmitter` job POSTs to external socket service (`config/socket.php`)
2. **Pusher:** client auth via `/pusher_authorizition`, `/pusher_subscribe`, `/pusher/beams-auth`
3. **Web push:** `PushController` + `minishlink/web-push`
4. **Laravel Broadcasting:** configured but `BROADCAST_DRIVER=null` — secondary/legacy

`Root.vue` listens for socket events to refresh badges and show alerts.

---

## API Calling Patterns (Frontend)

**Preferred:** `useApi()` from `composables/api.ts`
- Wraps axios with confirm dialogs, error toasts, loading refs, optional abort per URL

**Domain wrappers:** e.g. `useLearningApi()` — typed methods hiding legacy endpoint names

**Still direct axios:** some Pinia stores, route guards (`post.ts`), legacy heavy components

**Legacy endpoint naming:** snake_case paths (`/get_lesson_themes`, `/save_time_card`). New Learning endpoints are migrating toward clearer shapes — see LEARNING_FLOW.md.

---

## Data Layer

- **~230 Eloquent models** — mixed naming (`boardRecord` camelCase legacy vs newer PascalCase)
- **219 migrations** — flat directory, mostly additive alterations
- **Form requests:** minimal (3 files) — most validation inline in controllers
- **Exports/Imports:** Maatwebsite Excel, League CSV

Major table groups: board/message, project/goals/finance, timecard/shift, lesson_*, drive_*, contact_*, asset_*, incidents, employee_contracts, refresh_*, custom_forms/surveys.

---

## Active Work: Learning Remodel

**Status doc:** [LEARNING_FLOW.md](./LEARNING_FLOW.md) (keep updated as work progresses)

### Goal
Centralize progress calculation in backend services; typed API responses; Vue components stop inferring domain state from raw status integers.

### Key backend services (new pattern)
- `App\Services\Learning\LessonViewService` — learner overview payload
- `App\Services\Learning\LearningProgressService` — normalized learner progress
- `App\Services\Learning\LearningParticipantProgressService` — admin participant rows

### Key frontend artifacts
- `resources/js/types/learning.ts` — domain types
- `resources/js/config/learning.ts` — status constants
- `resources/js/composables/learningApi.ts`, `learningLessonView.ts`, `learningDraftContext.ts`
- `resources/js/utils/learningProgress.ts`, `learningParticipants.ts`

### Migration boundary (current)
- `GET /get_lesson_view` — combined learner materials + portfolio + exam summary
- `GET /get_lesson_themes` — includes normalized `progress` per theme
- Old endpoints remain wrapped in `useLearningApi()` during migration
- Tests: `tests/Feature/Learning/*`

### Remodel UI rules
- TypeScript Vue SFCs
- No rounded borders / box shadows in remodel surfaces
- Split large screens into view components + composables
- Do not load large payloads into `route.meta`

---

## Completed Refactor: Dashboard

See [DASHBOARD_REFACTORING_SUMMARY.md](./DASHBOARD_REFACTORING_SUMMARY.md).

- Config centralized in `resources/js/config/dashboardCards.ts` (component map O(1) lookup)
- Goal state in `resources/js/store/dashboardGoals.ts` (Pinia)
- `composables/dashboard.ts` is a **deprecated** backward-compat re-export
- Prefer `useDashboardGoalsStore()` for new dashboard work

---

## Coding Conventions

### PHP/Laravel
- PSR-12, Laravel conventions
- Constructor DI for new services
- Conventional Commits: `feat:`, `fix:`, `refactor:`, etc.

### Vue/TypeScript
- Composition API, explicit props/emits
- New Learning/admin work: TypeScript SFCs
- Tailwind utilities preferred; minimize custom CSS
- JSDoc on non-obvious functions

### Naming quirks (legacy)
- Controllers: `get_messages`, `board_list` method names match route paths
- Models: many legacy lowercase/camelCase class names
- Endpoints: `/get_*`, `/save_*` prefix pattern

---

## Quality Gates

Run before submitting changes:

```bash
# PHP (if scripts exist in your environment — see CONTRIBUTING.md)
composer run-script phpcs
composer run-script phpstan

# JS/TS
npm run typecheck          # vue-tsc (package.json)
# npm run lint             # referenced in AGENTS.md; verify script exists locally

# Tests
php artisan test
php artisan test tests/Feature/Learning/
```

---

## Agent Playbook: Common Tasks

### Add a Learning feature
1. Read [LEARNING_FLOW.md](./LEARNING_FLOW.md)
2. Backend: extend service in `app/Services/Learning/`, wire in `LessonController`
3. Frontend: types in `types/learning.ts`, API in `learningApi.ts`, UI in `Learning/` or `LearningControl/`
4. Add Feature test under `tests/Feature/Learning/`

### Add a dashboard card
1. Add card definition to `config/dashboardCards.ts`
2. Create layout component in `Dashboard/Layout/`
3. Register in `DASHBOARD_COMPONENTS` map
4. Wire data key in `store/dashboard.ts` batch fetch

### Add a new SPA section
1. Add catch-all name in `routes/web.php` `whereIn('name', [...])`
2. Create route file in `resources/js/routes/`
3. Import in `routes/index.ts`
4. Add SideMenu entry in `Global/SideMenu.vue`

### Add an authenticated API endpoint
1. Add route inside auth group in `routes/web.php`
2. Add controller method (or service + thin controller)
3. Call from frontend via `useApi()` or domain composable
4. Follow existing validation/response JSON patterns in that controller

### Fix authorization
1. Find similar feature's controller checks (`admin_flag`, `position_id`, project membership)
2. Only use `$this->authorize()` if a policy exists (currently mostly Drive)
3. Do not introduce a new RBAC package without explicit request

---

## Pitfalls for Agents

1. **Monolithic routes file** — search `routes/web.php` by path string; don't assume RESTful resource routes.
2. **Fat controllers** — business logic may live in controller methods, not services. Read the full method before extracting.
3. **SharedService** — large legacy helper; changes ripple widely.
4. **Dual type systems** — `interface/` (legacy) and `types/` (newer). Learning remodel uses `types/learning.ts`.
5. **Status integers** — especially Learning; use normalized progress from services, not raw DB values in Vue.
6. **Missing controller classes** — some `web.php` imports may reference absent controllers.
7. **Japanese UI** — user-facing strings are Japanese; keep tone consistent.
8. **No register route** — users are admin-created, not self-registration.
9. **Session auth** — axios must send cookies; CSRF token from Laravel meta tags.
10. **Do not commit** unless explicitly asked; do not edit unrelated files.

---

## Uncommitted Work Snapshot (branch state)

Recent in-progress areas (verify `git status` at session start):

- **Learning remodel** — services, types, composables, admin/learner component splits, theme categories, archive flag
- **Employee contracts** — new models/migrations (`EmployeeContract*`, `PrefecturalMinimumWage`)
- **Tests** — `tests/Feature/Learning/*`, test infrastructure stubs

Always run `git status` and read [LEARNING_FLOW.md](./LEARNING_FLOW.md) "Implementation Progress" before touching Learning.

---

## Quick Reference Commands

```bash
# Dev
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run dev          # Vite
php artisan serve    # Laravel

# Verify
php artisan test
npm run typecheck
```

---

*Last consolidated: 2026-06-20. Update this file when major architecture or domain boundaries change.*
