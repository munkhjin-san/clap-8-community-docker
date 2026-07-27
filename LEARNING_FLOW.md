# Learning Flow Remodel

This document is the working map for the Learning remodel. The current feature works, but progress rules are spread across Vue components, Laravel controllers, and database status columns. The remodel goal is to make the flow explicit, typed, and loaded through predictable API boundaries.

## Current Entry Points

- Learner app: `resources/js/components/Learning/LearningRoot.vue`
- Learner routes: `resources/js/routes/learning.ts`
- Admin/control panel: `resources/js/components/AccountControl/LearningControl/`
- Backend controllers: `app/Http/Controllers/LessonController.php`, `app/Http/Controllers/LessonExamController.php`
- Database shape reference: `learning_table.sql`

## Current Domain Objects

- `lesson_themes`: theme configuration, visibility, access, portfolio/case-study mode, survey form link.
  `archive = 1` keeps the theme manageable in admin but hides it from learner theme selection.
- `lesson_theme_categories`: ordered theme categories for learner filtering. Learners default to "すべて".
- `lesson_theme_category_theme`: many-to-many category assignments for themes.
- `lesson_materials`: authoring content. `priority = 0` means header/intro, `priority = 1` means learner task card.
- `lesson_answers`: per-user answer/progress for materials that do not write to portfolio sections.
- `lesson_portfolios`: per-user theme portfolio and the old multi-step portfolio status.
- `lesson_sections`: per-user written understanding for material sections.
- `lesson_summaries`, `lesson_summary_questions`, `lesson_summary_answers`: understanding checks and remediation content.
- `lesson_exams`, `lesson_exam_questions`, `lesson_exam_options`, `lesson_exam_attempts`, `lesson_exam_answers`: case-study exam system.
- `lesson_access`: theme access allow-list. Empty means public.
- `lesson_forms`: legacy fixed 3-question survey answers.
- `custom_forms` and survey answers: newer custom checklist/survey completion.

## Status Meanings To Preserve

### Material Answer Status

- `null`: no answer yet.
- `-1`: learner did not understand after remediation.
- `1`: draft/in progress when used.
- `2`: completed.

### Section Status

- `0`: not started.
- `1`: draft/saved.
- `2`: completed.

### Portfolio Status

- `0`: portfolio exists, basic learning or draft portfolio not completed.
- `1`: discussion portfolio prepared, ready for group discussion.
- `2`: group discussion completed, final public portfolio draft in progress.
- `3`: final portfolio completed/published.

Important: the current final portfolio screen appears to keep status at `2` even after the final step. That conflicts with code that treats `3` as completed/published.

### Exam Status

- `passed`: score met `passing_score`.
- `failed`: score below `passing_score`.

## Recurring Learner / Previous-Version Concept

This feature is for staff who retake a theme after already completing an older version. The goal is not simple review. The system should use the learner's previous portfolio, previous feedback, and the newest shared training philosophy to generate a personalized retake experience.

### Original Japanese Product Concept

#### 再受講者向けの具体的な流れ

1. 事前準備
   - 自分の前回のポートフォリオを用意する。
   - できれば前回のフィードバックも一緒に使う。
   - 会社側が用意しているブラッシュアップ版の研修本文（未受講者向け）を「母体」として使う。
   - 再受講者本人は母体本文を読む必要はない。読む設計ではない。

2. AIで「自分専用の研修資料」を作る
   - AIに読み込ませるもの:
     - 前回の自分のポートフォリオ
     - 前回のフィードバック（あれば）
     - ブラッシュアップ版の研修思想（共通基盤）
   - AIが出力するもの:
     - 自分専用の研修資料（構造化された内容）
     - ディスカッションテーマ（3つ提示し、本人が1つ選ぶ）
     - 必要に応じて、理想的な議論の着地点例

3. 自分専用研修資料を読む
   - 目的は「復習」ではなく、前回の自分の整理を最新版の考え方で見直すこと。
   - 確認するポイント:
     - 自分の信念・傾向
     - 発動しやすい場面
     - 強みとして働く条件
     - 負荷になりやすい条件
     - 今回扱うテーマ（どれを選ぶか）

4. グループディスカッションに参加する
   - AIが提示したテーマ3つから、自分で1つ選ぶ。
   - 正解探しはしない。
   - 自分の傾向や思考の癖が「どんな場面で出るか」を整理する。
   - 他者の視点で、自分の盲点に気づくことを狙う。
   - 自分がテーマを出す場合は「冒頭の話し言葉 → 具体例 → 他者に質問」の流れで進める。

5. ディスカッション直後に最終ポートフォリオを提出する
   - チェックや採点はしない。
   - 次回の再受講の材料として残すための提出。
   - 軽量フォーマット:
     - 現在の自己構造（信念／発動場面／感情）
     - 強みと負荷（各1つ）
     - 気づき・感想（3〜5行）

6. 次回（数年後）の再受講へつなぐ
   - 次回は、その時点の最新の共通基盤と、今回提出したポートフォリオ履歴を材料にして、AIがまた個別教材を生成する。
   - これを繰り返し、理解を更新し続ける仕組みにする。

### Implementation Interpretation

- `lesson_themes.previous_version` links the current brush-up theme to an older portfolio theme.
- A learner with a previous portfolio should see that previous experience in the current theme.
- The current "previous experience" panel is only the first UI slice. It displays the old portfolio and feedback so the learner has context.
- The real target state is an AI-generated retake package:
  - personalized training material generated from previous portfolio + feedback + current theme philosophy,
  - three discussion topic candidates,
  - optional ideal discussion landing examples,
  - lightweight final portfolio submission for the next retake cycle.
- The learner should not need to read the full brush-up base material directly when they are a recurring learner. The base material acts as the shared source for AI generation.
- Retake completion is not scored. The submitted portfolio is a historical artifact for the next version loop.

### Open Design Questions

- Where should generated retake material be stored: generated on demand, cached per learner/theme, or versioned as a saved record?
- Should topic selection happen before or after reading the generated material?
- Should the final lightweight retake portfolio use `lesson_portfolios` fields, a new retake-specific table, or a structured JSON column?
- How much of the generated AI output should managers/admins be able to view?

## Target Learner Flow

### Portfolio Theme

1. Learner opens an active accessible theme.
2. Learner completes all required basic materials.
3. Learner creates the discussion portfolio draft.
4. Learner attends/completes group discussion.
5. Learner creates the final public portfolio.
6. Learner completes the linked survey/checklist if the theme has one.
7. Theme is complete.

### Case-Study Theme

1. Learner opens an active accessible theme.
2. Learner completes all required basic materials.
3. Learner completes all case-study materials.
4. Learner passes exam if one exists.
5. Learner completes the linked survey/checklist if the theme has one.
6. Theme is complete.

## Target Admin Flow

### Theme Manager

- Create/edit theme.
- Set active state.
- Archive/unarchive themes without deleting their content or admin history.
- Manage ordered categories.
- Assign one or more categories to each theme.
- Choose exactly one primary structure: portfolio or case study.
- Attach survey/checklist.
- Configure access members.

### Content Builder

- Manage intro/header materials.
- Manage learner task materials.
- Manage understanding checks.
- Manage exam for case-study themes.

### Participant Progress

- Show one normalized progress table for all eligible members.
- Separate filters/views can show complete, in progress, and not started.
- CSV export uses the same normalized progress data as the screen.

### Settings

- Keep AI assistant/prompt configuration only if it has a real save path and runtime use.

## Remodel Principles

- One source of truth for progress calculation.
- API responses should be typed and shaped for the screen that consumes them.
- Vue components should not infer domain state from raw status numbers.
- Route guards should avoid loading large payloads into `route.meta`.
- Components should be TypeScript based.
- Large screens should split into view components, cards/rows, modals, and composables.
- Styling should avoid rounded borders and box shadows in the remodel work.

## Proposed API Shape

### Learner

- `GET /learning/themes`
  - Theme cards with normalized progress summary.
- `GET /learning/themes/{theme}/overview`
  - Theme, materials, portfolio, exam summary, survey summary.
- `GET /learning/materials/{material}`
  - One material with current learner answer and summaries.
- `POST /learning/materials/{material}/answer`
- `POST /learning/themes/{theme}/portfolio`
- `GET /learning/themes/{theme}/exam`
- `POST /learning/themes/{theme}/exam/submit`

### Admin

- `GET /admin/learning/themes`
- `POST /admin/learning/themes`
- `GET /admin/learning/themes/{theme}/content`
- `POST /admin/learning/themes/{theme}/materials`
- `POST /admin/learning/materials/{material}/summaries`
- `GET /admin/learning/themes/{theme}/progress`
- `GET /admin/learning/themes/{theme}/progress/export`

The current endpoints can stay during migration. New composables should hide old endpoint names from components.

## First Implementation Slices

1. Add shared TypeScript constants and types for statuses, materials, portfolios, exams, and progress.
2. Add learner/admin API composables that wrap current endpoints.
3. Replace direct status-number rendering in `LearningRoot.vue`, `LearningControl` participant screens, and CSV builders.
4. Move route-level data loading out of `route.meta` and into composables.
5. Split large UI files into smaller typed components.
6. Add backend progress service and switch admin participant views to one normalized endpoint.
7. Add tests for progress calculation and completion transitions.

## Implementation Progress

### Completed

- Added shared Learning constants, TypeScript types, API wrappers, and learner-flow composables.
- Converted the main learner screens and LearningControl screens to TypeScript-based Vue SFCs.
- Split repeated learner UI into shared header, theme card/grid, topic menu, intro, and status components.
- Removed lesson route guard payload loading from `resources/js/routes/learning.ts`.
- Moved evaluation and support-account loading into the consuming screens.
- Fixed invalid nested paragraph markup in `TraineeControl.vue`.
- Added `GET /get_lesson_view` as a migration endpoint that returns lesson materials, learner portfolio, and exam summary in one learner-view payload.
- Changed `LessonContainer.vue` to own the combined lesson-view state and pass exam summary data to the basic learner menu.
- Extracted learner lesson-view payload loading into `App\Services\Learning\LessonViewService`.
- Added `App\Services\Learning\LearningProgressService` and attached normalized progress to learner theme responses.
- Updated learner theme cards to prefer the normalized progress payload while keeping relationship-based fallbacks during migration.
- Updated learner top/basic menus to prefer normalized progress for workflow gating, exam state, survey state, and portfolio status.
- Removed duplicated learner-menu inference for basic, case-study, exam, and survey completion. Menus now use normalized progress plus per-material row state.
- Added normalized per-user progress to the admin case-study participant endpoint and updated the participant table/CSV to use it.
- Added normalized per-user progress to admin portfolio trainee rows and switched status/CSV labels to read from it.
- Split the admin portfolio trainee view into a typed coordinator, reusable table, preview popup cell, and CSV row helper.
- Split the admin case-study participant view into a typed coordinator, reusable participant table, shared preview popup cell, and CSV/status helper.
- Split the admin content builder cards into typed exam summary and material card components while keeping save/edit orchestration in `ContentControl.vue`.
- Split exam authoring question/option markup into a typed `ExamQuestionEditor` component with shared editable exam types.
- Split lesson material priority/type/request settings into a typed `LessonMaterialSettingsSection` component while keeping validation and save orchestration in `LessonCreate.vue`.
- Split understanding-check question editing into a typed `SummaryQuestionEditor` component with stable local editor keys.
- Split theme structure and portfolio guidance editing into a typed `ThemeStructureSection` component.
- Split theme survey/access selectors into a typed `ThemeAccessSection` component while keeping position-to-member expansion in `ThemeCreate.vue`.
- Split admin LearningControl shell UI into typed theme grid and theme tab/header components.
- Added focused backend tests for lesson overview loading, normalized progress, exam attempt limits, and portfolio status transitions.
- Added batched normalized progress loading for admin participant endpoints to avoid reloading theme, portfolio, exam, and survey data once per participant.
- Moved theme AI prompt/assistant ID editing into the theme create/edit save path and changed the AI assistant tab to a typed read-only settings view.
- Split the non-participant admin view into a typed coordinator, table/empty-state component, and shared participant ID collector.
- Slimmed the case-study participant endpoint and UI helpers so status, survey, and exam display come from normalized progress instead of duplicated legacy fields.
- Added a versioned admin theme progress endpoint backed by `LearningParticipantProgressService`, with the legacy material-list endpoint delegating to the same service.
- Added portfolio participant rows to the versioned admin progress endpoint, with the legacy portfolio-list endpoint delegating to the same service.
- Added lazy section loading to the admin progress endpoint so case-study and portfolio tabs request only their own rows.
- Removed remaining inline presentation styles from the theme create/edit modal and moved them into scoped classes.
- Removed remaining inline presentation styles and dead commented switch markup from the lesson material create/edit modal.
- Removed remaining inline presentation styles and stale commented input markup from the exam create/edit modal.
- Removed remaining inline presentation styles from the understanding-check create/edit modal.
- Removed remaining inline presentation styles from the exam attempts modal.
- Removed remaining inline presentation styles from the admin Learning shell, theme container, and content builder surfaces.
- Added lesson theme categories with admin CRUD/reorder controls, theme category assignment, and learner category filtering.
- Changed learner category filtering to default to "すべて" and sync the selected category through the route query.
- Added a theme archive flag, editable in the theme modal, visible in admin, and filtered out of learner theme selection.

### Current Migration Boundary

- `GET /get_lesson_view` is the new learner overview endpoint for materials, portfolio, and exam summary.
- `GET /get_lesson_themes` now includes `progress` for each learner theme.
- `GET /learning_exam` remains the full exam-taking endpoint because it conditionally includes questions, options, correct answers, and final attempt answers.
- Existing old endpoints remain wrapped in `useLearningApi()` so child components can refresh narrowly while the rewrite continues.

### Next Slices

1. Add frontend tests or browser checks around learner menu gating once the UI migration settles.
2. Do a browser QA pass through category CRUD, theme category assignment, admin tabs, and learner category filtering.
3. Review learner-facing portfolio/assessment screens that still call legacy portfolio endpoints and decide whether they need a learner-specific wrapper.
