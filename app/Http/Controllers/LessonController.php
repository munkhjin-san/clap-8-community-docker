<?php

namespace App\Http\Controllers;

use App\Models\CustomForm;
use App\Models\LessonAnswer;
use App\Models\LessonPersonalMaterial;
use App\Models\LessonSummary;
use App\Models\LessonSummaryAnswer;
use App\Models\LessonSummaryQuestion;
use App\Services\Learning\LearningParticipantProgressService;
use App\Services\Learning\LearningProgressService;
use App\Services\Learning\LessonViewService;
use App\Services\Learning\PersonalMaterialPresentationService;
use Illuminate\Http\Request;
use App\Models\LessonMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonTheme;
use App\Models\LessonThemeAiConfig;
use App\Models\LessonMaterialVersion;
use App\Models\LessonThemeCategory;
use App\Models\LessonForm;
use App\Models\LessonSection;
use App\Models\positionRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use OpenAI;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LessonController extends Controller
{
    public function __construct(
        private LessonViewService $lessonViewService,
        private LearningProgressService $learningProgressService,
        private LearningParticipantProgressService $learningParticipantProgressService,
        private PersonalMaterialPresentationService $personalMaterialPresentationService
    ) {
    }

    public function get_lessons(Request $request){
        // Admin authoring list: show the requested version, defaulting to the theme's default version.
        $themeId = $request->lesson_theme_id;
        $versionId = $request->version_id
            ?: $this->lessonViewService->defaultVersionId($themeId);

        $lessons = $this->lessonViewService->materials($themeId, (int) Auth::id(), $versionId);

        return response()->json($lessons);
    }
    public function get_lesson_view(Request $request){
        $request->validate([
            'lesson_theme_id' => 'required|exists:lesson_themes,id',
        ]);

        $themeId = $request->lesson_theme_id;
        return response()->json($this->lessonViewService->lessonView($themeId, (int) Auth::id()));
    }
    public function get_material(Request $request){
        $lesson = $this->lessonViewService->material($request->id, (int) Auth::id());
        return response()->json($lesson);
    }
    public function lesson_remove_record(Request $request){
        if($request->id){
            $lesson = LessonMaterial::findOrFail($request->id)->delete();
            return response()->json($lesson);
        }
    }
    public function get_learning_themes(){
        $themes = LessonTheme::with(['accessMembers', 'categories', 'aiConfigs'])->get();
        return response()->json($themes);
    }
    public function get_lesson_themes(){
        $user = Auth::user();
        $themes_portfolio = LessonTheme::query()
        ->where(function ($q) {
            $q->where('archive', 0)->orWhereNull('archive');
        })
        ->where(function ($q) use ($user) {
            $q->whereHas('accessMembers', function ($qq) use ($user) {
                $qq->where('user_id', $user->id);
            })
            ->orWhereDoesntHave('accessMembers'); // no rows = public
        })
        ->with([
            'lesson_portfolio' => function ($q){
                $q->where('user_id', Auth::id());
            }, 
            'materials' => function ($q) {
                $q->with(['answer' => function ($q) {
                    $q->where('user_id', Auth::id());
                }]);
            },
            'form.survey_answers',
            'categories',
            'aiConfigs',
        ])->get();
        $themes_portfolio->each(function ($theme) {
            $theme->setAttribute('progress', $this->learningProgressService->forThemeUser($theme, (int) Auth::id()));
        });
        return response()->json($themes_portfolio);
    }
    public function delete_learning_theme(Request $request){
        if($request->id){
            $lesson = LessonTheme::findOrFail($request->id)->delete();
            return response()->json($lesson);
        }
    }
    public function create_learning_theme(Request $request){

        $id = $request->id ?? null;
        $params = $request->params;
        $allowed_positions = $request->access_members ?? [];
        $categoryIds = $request->category_ids ?? [];
        $params['previous_version'] = $this->normalizeThemeId($params['previous_version'] ?? null);
        if (!($params['portfolio'] ?? false)) {
            $params['previous_version'] = null;
        }
        if (!empty($params['previous_version'])) {
            $previousTheme = LessonTheme::where('id', $params['previous_version'])
                ->where('portfolio', 1)
                ->when($id, fn ($query) => $query->where('id', '!=', $id))
                ->first();
            if (!$previousTheme) {
                throw ValidationException::withMessages([
                    'previous_version' => '前バージョンには別のポートフォリオテーマを選択してください。',
                ]);
            }
        }
        $theme = LessonTheme::updateOrCreate(['id' => $id], $params);
        $theme->accessMembers()->sync($allowed_positions);
        $theme->categories()->sync($categoryIds);

        return response()->json($theme->load(['accessMembers', 'categories']));
    }
    // Renew a theme's content in place: keep the theme id, retire all currently
    // active materials (kept for learner history), so new materials can be authored
    // as the next version. First-time learners then see only the new content.
    // Theme-open state for the learner: attempt history + which path options.
    public function get_learner_theme_state(Request $request, LessonTheme $theme, \App\Services\Learning\LearningAttemptService $attempts)
    {
        return response()->json($attempts->state($theme, (int) Auth::id()));
    }

    // Start a new learning attempt (path 2 "learn again"); only after clearing once.
    public function start_learning_attempt(Request $request, LessonTheme $theme, \App\Services\Learning\LearningAttemptService $attempts)
    {
        if (! $attempts->cleared($theme->id, (int) Auth::id())) {
            throw ValidationException::withMessages(['message' => 'このテーマをまだ修了していません。']);
        }

        return response()->json($attempts->startAttempt($theme, (int) Auth::id()));
    }

    // Delete an attempt whose first stage (知識研修) is not yet completed.
    public function delete_learning_attempt(Request $request, LessonTheme $theme, LessonPortfolio $portfolio, \App\Services\Learning\LearningAttemptService $attempts)
    {
        abort_unless(
            (int) $portfolio->user_id === (int) Auth::id() && (int) $portfolio->lesson_theme_id === (int) $theme->id,
            403
        );

        if ((int) $portfolio->status >= 1) {
            throw ValidationException::withMessages(['message' => '知識研修が完了した学習は削除できません。']);
        }

        $attempts->deleteAttempt($portfolio);

        return response()->json(['deleted' => true]);
    }

    // Path 3: options for challenging a salary raise from inside a cleared theme
    // (which of the learner's goals can attach a challenge for this theme).
    public function get_theme_challenge_options(Request $request, LessonTheme $theme, \App\Services\SalaryIssue\SalaryIssueEligibilityService $eligibility, \App\Services\Learning\LearningAttemptService $attempts)
    {
        $userId = (int) Auth::id();

        return response()->json(array_merge(
            $eligibility->themeChallengeOptions($theme, $userId),
            ['cleared' => $attempts->cleared($theme->id, $userId)]
        ));
    }

    // Path 3: create a salary challenge for this theme + a chosen goal, and start
    // its path-3 learning attempt. Requires the theme to be cleared first.
    public function create_theme_challenge(
        Request $request,
        LessonTheme $theme,
        \App\Services\SalaryIssue\SalaryIssueEligibilityService $eligibility,
        \App\Services\SalaryIssue\SalaryIssueLearningService $learning,
        \App\Services\Learning\LearningAttemptService $attempts
    ) {
        $request->validate(['goal_id' => 'required']);
        $userId = (int) Auth::id();

        if (! $attempts->cleared($theme->id, $userId)) {
            throw ValidationException::withMessages(['message' => 'このテーマを修了してから挑戦してください。']);
        }

        $goal = \App\Models\ProjectGoal::where('id', $request->goal_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $eligibility->assertCanChallengeTheme($goal, $theme);

        $mentorId = \App\Models\EvaluationRecord::where('user_id', $userId)
            ->where('year', $goal->year)
            ->where('which_half', $goal->which_half)
            ->value('mentor_id');

        $issue = \App\Models\SalaryIssue::create([
            'user_id' => $userId,
            'mentor_id' => $mentorId,
            'project_goal_id' => $goal->id,
            'lesson_theme_id' => $theme->id,
            'theme' => $theme->title,
            'title' => $theme->title,
            'status' => 0,
            'date' => now()->toDateString(),
        ]);

        $portfolio = $learning->challengePortfolio($issue, $theme);

        return response()->json([
            'salary_issue_id' => $issue->id,
            'portfolio_id' => $portfolio->id,
        ]);
    }

    // Guarantee a theme always has at least one (default) material version.
    private function ensureThemeHasDefaultVersion(int $themeId): LessonMaterialVersion
    {
        $default = LessonMaterialVersion::where('lesson_theme_id', $themeId)
            ->where('is_default', true)
            ->first();
        if ($default) {
            return $default;
        }

        // No default yet — promote the lowest version, or create version 1.
        $existing = LessonMaterialVersion::where('lesson_theme_id', $themeId)
            ->orderBy('version_no')
            ->first();
        if ($existing) {
            $existing->update(['is_default' => true]);
            return $existing;
        }

        return LessonMaterialVersion::create([
            'lesson_theme_id' => $themeId,
            'version_no' => 1,
            'is_default' => true,
        ]);
    }

    public function get_material_versions(Request $request, LessonTheme $theme)
    {
        $this->ensureThemeHasDefaultVersion((int) $theme->id);

        $versions = LessonMaterialVersion::where('lesson_theme_id', $theme->id)
            ->withCount('materials')
            ->orderBy('version_no')
            ->get();

        return response()->json($versions);
    }

    public function create_material_version(Request $request, LessonTheme $theme)
    {
        $data = $request->validate([
            'copy_from' => 'nullable|integer',   // version id to duplicate materials from
            'label' => 'nullable|string|max:255',
        ]);

        $this->ensureThemeHasDefaultVersion((int) $theme->id);

        $nextNo = (int) LessonMaterialVersion::where('lesson_theme_id', $theme->id)->max('version_no') + 1;

        $version = LessonMaterialVersion::create([
            'lesson_theme_id' => $theme->id,
            'version_no' => $nextNo,
            'is_default' => false,
            'label' => $data['label'] ?? null,
        ]);

        // Optionally seed the new version by duplicating another version's materials.
        if (! empty($data['copy_from'])) {
            $source = LessonMaterialVersion::where('lesson_theme_id', $theme->id)
                ->where('id', $data['copy_from'])
                ->firstOrFail();

            $sourceMaterials = LessonMaterial::where('lesson_theme_id', $theme->id)
                ->where('lesson_material_version_id', $source->id)
                ->orderBy('priority')
                ->orderBy('id')
                ->get();

            foreach ($sourceMaterials as $material) {
                $copy = $material->replicate(['retired_at']);
                $copy->lesson_material_version_id = $version->id;
                $copy->save();
            }
        }

        $version->loadCount('materials');

        return response()->json($version);
    }

    public function set_default_material_version(Request $request, LessonTheme $theme, LessonMaterialVersion $version)
    {
        abort_unless((int) $version->lesson_theme_id === (int) $theme->id, 404);

        LessonMaterialVersion::where('lesson_theme_id', $theme->id)->update(['is_default' => false]);
        $version->update(['is_default' => true]);

        return response()->json(['default_version_id' => $version->id]);
    }

    public function delete_material_version(Request $request, LessonTheme $theme, LessonMaterialVersion $version)
    {
        abort_unless((int) $version->lesson_theme_id === (int) $theme->id, 404);
        abort_if($version->is_default, 422, 'デフォルトのバージョンは削除できません。');

        $remaining = LessonMaterialVersion::where('lesson_theme_id', $theme->id)->count();
        abort_if($remaining <= 1, 422, '最後のバージョンは削除できません。');

        // Soft-delete this version's materials (history via section/answer material_id survives).
        LessonMaterial::where('lesson_theme_id', $theme->id)
            ->where('lesson_material_version_id', $version->id)
            ->delete();

        $version->delete();

        return response()->json(['deleted' => true]);
    }
    public function save_lesson_theme_ai_config(Request $request, LessonTheme $theme)
    {
        $data = $request->validate([
            'config_key' => 'required|string|max:255',
            'lesson_material_id' => 'nullable|integer|exists:lesson_materials,id',
            'model' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        if (!empty($data['lesson_material_id'])) {
            $materialExists = LessonMaterial::where('id', $data['lesson_material_id'])
                ->where('lesson_theme_id', $theme->id)
                ->exists();

            if (!$materialExists) {
                throw ValidationException::withMessages([
                    'lesson_material_id' => '指定された教材はこのテーマに属していません。',
                ]);
            }
        }

        $config = LessonThemeAiConfig::updateOrCreate(
            [
                'lesson_theme_id' => $theme->id,
                'config_key' => $data['config_key'],
            ],
            [
                'lesson_material_id' => $data['lesson_material_id'] ?? null,
                'model' => $data['model'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'settings' => $data['settings'] ?? null,
            ]
        );

        return response()->json($config);
    }

    private function normalizeThemeId(mixed $value): ?int
    {
        if (is_array($value)) {
            $value = $value['id'] ?? $value[0] ?? null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
    public function get_lesson_categories(){
        return response()->json(
            LessonThemeCategory::orderBy('position')->orderBy('id')->get()
        );
    }

    public function save_lesson_category(Request $request){
        $data = $request->validate([
            'id' => 'nullable|integer|exists:lesson_theme_categories,id',
            'name' => 'required|string|max:255',
        ]);

        $position = isset($data['id'])
            ? LessonThemeCategory::findOrFail($data['id'])->position
            : (int) LessonThemeCategory::max('position') + 1;
        $category = LessonThemeCategory::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name' => $data['name'],
                'position' => $position,
            ]
        );

        return response()->json($category->fresh());
    }

    public function delete_lesson_category(Request $request){
        $request->validate([
            'id' => 'required|integer|exists:lesson_theme_categories,id',
        ]);

        $category = LessonThemeCategory::findOrFail($request->id);
        $category->delete();

        return response()->json(true);
    }

    public function reorder_lesson_categories(Request $request){
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:lesson_theme_categories,id',
        ]);

        foreach ($data['ids'] as $index => $id) {
            LessonThemeCategory::where('id', $id)->update(['position' => $index + 1]);
        }

        return $this->get_lesson_categories();
    }

    // One global default category (pre-selected on the learner's first load). Toggling
    // the current default clears it; otherwise it becomes the sole default.
    public function set_default_lesson_category(Request $request, LessonThemeCategory $category){
        $makeDefault = ! $category->is_default;
        LessonThemeCategory::query()->update(['is_default' => false]);
        if ($makeDefault) {
            $category->update(['is_default' => true]);
        }

        return $this->get_lesson_categories();
    }
    public function lesson_add_record(Request $request){
        $id = $request->id ?? null;
        $params = $request->params;
        $userId = auth()->id();
        if ($id) {
            $params['updated_by'] = $userId;
        } else {
            $params['user_id'] = $userId;
            // New materials belong to a version: the one the admin is editing,
            // or the theme's default version as a fallback.
            if (empty($params['lesson_material_version_id']) && ! empty($params['lesson_theme_id'])) {
                $params['lesson_material_version_id'] = $this->ensureThemeHasDefaultVersion((int) $params['lesson_theme_id'])->id;
            }
        }
        $lesson = LessonMaterial::updateOrCreate(['id' => $id], $params);

        return response()->json($lesson);
    }
    // Returns the learner's current (latest) attempt for a theme, creating the
    // first attempt if none exists.
    public function check_portfolio($theme_id, $user_id){
        $lessonPortfolio = LessonPortfolio::where('lesson_theme_id', $theme_id)->where('user_id', $user_id)
            ->currentAttempt()->first();
        if(empty($lessonPortfolio)){
            $newLessonPortfolio = LessonPortfolio::create([
                "lesson_theme_id" => $theme_id,
                "user_id" => Auth::id(),
                "attempt_no" => 1,
            ]);
            return $newLessonPortfolio;
        }
        return $lessonPortfolio;
    }
    public function section_update(Request $request){

        $validatedData = $request->validate([
            'material_id' => 'required',
        ]);
        $portfolio = $this->check_portfolio($request->lesson_theme_id, Auth::id());
        
        $lessonSection = LessonSection::where('material_id', (int) $request->material_id)->where('user_id', Auth::id())->first();
        if(empty($lessonSection)){
            $lessonSection = new LessonSection; 
            $lessonSection->save();    
        }
        $update = $lessonSection->update([
            "material_id" => (int) $request->material_id,
            "portfolio_id" => $portfolio->id,
            "user_id" => Auth::id(),
            "status" => $request->section_status,
            "content" => $request->update_content,
        ]);         
        return response()->json($update);
    }

    public function save_lesson_portfolio(Request $request){

        $request->validate([
            'theme_id' => 'required',
        ]);
        $lessonPortfolio = $this->check_portfolio($request->theme_id, Auth::id());

        $lessonPortfolio->update($request->params);

        // Path 3: finalizing the challenge portfolio applies it for mentor approval.
        if ($lessonPortfolio->salary_issue_id && (int) $lessonPortfolio->status === 3) {
            $this->applyChallengePortfolioToMentor((int) $lessonPortfolio->salary_issue_id);
        }

        return response()->json($lessonPortfolio);
    }

    // Salary-issue path-3 portfolio statuses (0-11 are the legacy 課題/結果 flow):
    //  12 ポートフォリオをメンターに申請中 / 13 差戻中(本人) / 14 人事に申請中 / then 10 達成 or 11 未達成.
    private function applyChallengePortfolioToMentor(int $salaryIssueId): void
    {
        $issue = \App\Models\SalaryIssue::find($salaryIssueId);
        if (! $issue) {
            return;
        }

        // Only enter (or re-enter) mentor review from a pre-review state.
        if (! in_array((int) $issue->status, [0, 13], true)) {
            return;
        }

        $before = (int) $issue->status;
        $issue->update(['status' => 12]);
        $issue->statusLogs()->create([
            'before_number' => $before,
            'after_number' => 12,
            'user_id' => (int) Auth::id(),
            'type' => 'salary_issue',
        ]);
    }
    public function update_lesson_portfolio(Request $request){
      
        $request->validate([
            'id' => 'required',
        ]);
        $lessonPortfolio = LessonPortfolio::findOrFail($request->id)->update($request->params);       
        return response()->json($lessonPortfolio);
    }
    // Resolve the effective user, honouring the linked/active-account switch used across the app.
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        return $sub ?: Auth::user();
    }

    // Admin: remove any learner's portfolio (a specific attempt), recording an audit log.
    public function delete_admin_portfolio(Request $request, LessonPortfolio $portfolio){
        $actor = $this->active_user();
        abort_unless(optional($actor)->isAdmin(), 403);

        \App\Models\LessonPortfolioDeletionLog::create([
            'lesson_portfolio_id' => $portfolio->id,
            'lesson_theme_id' => $portfolio->lesson_theme_id,
            'owner_user_id' => $portfolio->user_id,
            'deleted_by' => (int) $actor->id,
            'attempt_no' => $portfolio->attempt_no,
            'status' => $portfolio->status,
            'reason' => $request->reason,
            'snapshot' => [
                'public_title' => $portfolio->public_title,
                'public_content' => $portfolio->public_content,
                'portfolio_title' => $portfolio->portfolio_title,
                'content' => $portfolio->content,
                'positive_feedback' => $portfolio->positive_feedback,
                'negative_feedback' => $portfolio->negative_feedback,
                'noticed' => $portfolio->noticed,
                'salary_issue_id' => $portfolio->salary_issue_id,
            ],
        ]);

        LessonSection::where('portfolio_id', $portfolio->id)->delete();
        LessonPersonalMaterial::where('config_key', $this->repeaterConfigKey($portfolio->id))->delete();

        // Path 3: a challenge portfolio is backed by a salary issue — remove it too.
        if ($portfolio->salary_issue_id) {
            \App\Models\SalaryIssue::where('id', $portfolio->salary_issue_id)->delete();
        }

        $portfolio->delete();

        return response()->json(['deleted' => true]);
    }

    public function get_lesson_portfolio(Request $request){
        $lesson_portfolio = LessonPortfolio::where('lesson_theme_id', $request->lesson_theme_id)
        ->where('user_id', Auth::id())
        ->currentAttempt()
        ->with('lesson_sections')
        ->first();

        // Path 3 (salary challenge): surface the studied AI material + the group-discussion
        // theme the learner picked in stage 1 (stored on the personal material).
        if ($lesson_portfolio && $lesson_portfolio->salary_issue_id) {
            $personalMaterial = LessonPersonalMaterial::where('lesson_theme_id', $request->lesson_theme_id)
                ->where('user_id', Auth::id())
                ->where('config_key', $this->repeaterConfigKey($lesson_portfolio->id))
                ->first();
            $lesson_portfolio->setAttribute('ai_material', $personalMaterial?->content);
            $lesson_portfolio->setAttribute('discussion_theme', $personalMaterial?->important_point);
        }

        return response()->json($lesson_portfolio);
    }
    public function get_portfolios_list(Request $request){
        return response()->json(
            $this->learningParticipantProgressService->portfolioRows((int) $request->theme_id)
        );
    }
    public function save_lesson_form(Request $request){
        $portfolio = $this->check_portfolio($request->lesson_theme_id, Auth::id());
        $portfolio->update([
            "status" => $request->status
        ]);
        
        $lesson_form = LessonForm::create([
            "user_id" => Auth::id(),
            "lesson_theme_id" => $request->lesson_theme_id,
            "question1" => $request->question1,
            "answer1" => $request->answer1,
            "question2" => $request->question2,
            "answer2" => $request->answer2,
            "question3" => $request->question3,
            "answer3" => $request->answer3,
            "content" => $request->form_content,
        ]);

        return response()->json($lesson_form);
    }
    public function upload_lesson_file(Request $request){

        $file = $request['file'];
        $requestedType = $request->input('type');
        $uniqueID = uniqid();
        $path = '/lesson_files';
        $mime = $file->getMimeType();
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        if (strpos($mime, 'image') !== false) {
            if ($requestedType && $requestedType !== 'image') {
                throw ValidationException::withMessages(['message' => '選択したファイル種別とアップロードファイルが一致しません。']);
            }

            $ext = 'webp';
            $img = Image::read($file);
            
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);      
            $thumbnail = $img->toWebp();  
            
            $thumbnail->save(storage_path('app') . $path .'/' . $fileName . '_' . $uniqueID . '.' . $ext);
            $url = '/lesson_files/' . $fileName . '_' . $uniqueID . '.' . $ext;
            return response()->json($url);
        }elseif (strpos($mime, 'video') !== false) {
            if ($requestedType && $requestedType !== 'video') {
                throw ValidationException::withMessages(['message' => '選択したファイル種別とアップロードファイルが一致しません。']);
            }

            $ext = $file->getClientOriginalExtension();
            
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);      
            Storage::disk('local')->putFileAs(
                $path, $file, $fileName . '_'  . $uniqueID . '.' . $ext
            );
            $url = '/lesson_files/' . $fileName . '_'. $uniqueID . '.' . $ext;
            return response()->json($url);
        }elseif ($mime === 'application/pdf') {
            if ($requestedType && $requestedType !== 'pdf') {
                throw ValidationException::withMessages(['message' => '選択したファイル種別とアップロードファイルが一致しません。']);
            }

            $ext = 'pdf';

            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);
            Storage::disk('local')->putFileAs(
                $path, $file, $fileName . '_' . $uniqueID . '.' . $ext
            );
            $url = '/lesson_files/' . $fileName . '_' . $uniqueID . '.' . $ext;
            return response()->json($url);
        }else{
            throw ValidationException::withMessages(['message' => '画像・動画・PDFのみアップロード可能です。']);
        }
        
    }
    public function get_lesson_files(Request $request){
        $files = Storage::allFiles('/lesson_files');
        $type = $request->input('type');
        $extensionsByType = [
            'image' => ['webp', 'jpg', 'jpeg', 'png', 'gif', 'svg'],
            'video' => ['mp4', 'webm', 'mov', 'm4v'],
            'pdf' => ['pdf'],
        ];

        if (isset($extensionsByType[$type])) {
            $files = array_values(array_filter($files, function ($file) use ($extensionsByType, $type) {
                return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensionsByType[$type], true);
            }));
        }
        
        // Map files to include metadata
        $filesWithMetadata = array_map(function($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'last_modified' => Storage::lastModified($file)
            ];
        }, $files);
        
        // Sort by last modified date (newest first)
        usort($filesWithMetadata, function ($a, $b) {
            return $b['last_modified'] - $a['last_modified'];
        });
        
        // Pagination parameters
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        // Calculate pagination
        $total = count($filesWithMetadata);
        $offset = ($page - 1) * $perPage;
        $paginatedFiles = array_slice($filesWithMetadata, $offset, $perPage);
        
        return response()->json([
            'data' => $paginatedFiles,
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : null,
            'to' => $total > 0 ? min($offset + $perPage, $total) : null
        ]);
    }
    public function remove_lesson_file(Request $request){
        $deleted = Storage::delete('/' . $request->path);
        return response()->json($deleted);
    }
    public function update_portfolio_status(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $update = LessonPortfolio::findOrFail($request->id)->update(['status' => $request->value]);
        return response()->json($update);
    }
    public function get_portfolio_view(Request $request){
        $id = $request->id;
        $portfolio_list = LessonPortfolio::where('lesson_theme_id', $request->lesson_theme_id)
        ->when($id && $id > -1, function($q) use($id) {
            $q->where('id', $id);
        })
        ->where('status', 3)
        ->with('user')
        ->with('claps')
        ->withCount('claps')
        ->orderByDesc('claps_count')->get();
        return response()->json($portfolio_list);
    }
    public function update_lesson_answer(Request $request) {
        $request->validate([
            'params.material_id' => 'required',
        ]);
        $params = $request->params;
        $params['user_id'] = auth()->id();
        $material_id = $params['material_id'];
        $lesson_answer = LessonAnswer::updateOrCreate(['material_id' => $material_id, 'user_id' => auth()->id()], $params);
        return response()->json($lesson_answer);
    }
    public function get_material_list(Request $request) {
        return response()->json(
            $this->learningParticipantProgressService->caseStudyRows((int) $request->lesson_theme_id)
        );
    }

    public function get_admin_theme_progress(Request $request, LessonTheme $theme) {
        $section = $request->input('section', 'all');
        $payload = [];

        if ($section === 'all' || $section === 'case_study') {
            $payload['case_study_participants'] = $this->learningParticipantProgressService->caseStudyRows((int) $theme->id);
        }

        if ($section === 'all' || $section === 'portfolio') {
            $payload['portfolio_participants'] = $this->learningParticipantProgressService->portfolioRows((int) $theme->id);
            $sectionExams = $this->learningParticipantProgressService->portfolioSectionExams((int) $theme->id);
            $payload['section_exams'] = $sectionExams['section_exams'];
            $payload['section_exam_results'] = $sectionExams['results'];
        }

        return response()->json($payload);
    }

    public function add_material_summary(Request $request){
        $id = $request->id ?? null;
        $params = $request->params;
        $lesson_material_summary = LessonSummary::updateOrCreate(['id' => $id], $params);
        
        foreach ($request->questions as $question) {
            $question['lesson_summary_id'] = $lesson_material_summary->id;
            
            LessonSummaryQuestion::updateOrCreate(
                ['id' => $question['id'] ?? null],
                $question
            );
        }
        foreach ($request->deleted as $id) {
            LessonSummaryQuestion::findOrFail($id)->delete();
        }
        
        return response()->json($lesson_material_summary);
    }
    public function get_forms(Request $request){
        $ankets = CustomForm::all();

        return response()->json($ankets);
    }
    public function lesson_remove_summary(Request $request){
        if($request->id){
            $lesson = LessonSummary::findOrFail($request->id)->delete();
            return response()->json($lesson);
        }
    }
    public function save_summary_answers(Request $request){
        $answers = $request->answers;
        foreach ($answers as $answer) {
            $id = $answer['id'] ?? null;
            $params = $answer;
            $lesson_summary_answer = LessonSummaryAnswer::updateOrCreate(['id' => $id], $params);
        }
        return response()->json($lesson_summary_answer);
    }
    public function get_completed_lesson_themes(Request $request){
        $themes = LessonTheme::salaryIssueTarget()->whereHas('lesson_portfolio' ,
            function ($q){
                $q->where('user_id', Auth::id())
                ->where('status', 3);
            }
        )->pluck('title')->toArray();

        return response()->json($themes, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function get_theme_data(Request $request){
        $theme = LessonTheme::where('title', $request->theme)
        ->with(['materials' => function ($q) {
            $q->where('priority', 0);
        }])
        ->first();
        return response()->json([
            'themeData' => $theme ?? null,
        ]);
    }

    public function get_members_by_position(){
        $list = positionRecord::where('deleted_flag', 0)
        ->whereHas('employees')
        ->with([
            'employees' => function ($q) {
                $q->with([
                        'positions' => function ($q) {
                            $q->where('deleted_flag', 0);
                        }
                    ])
                    ->select('id', 'name', 'icon_path', 'icon_bg', 'position_id');
            }
        ])
        ->orderBy('sort_flag', 'asc')
        ->get();   

        return response()->json($list);
    }
    public function get_previous_experience(Request $request){
        $request->validate([
            'lesson_theme_id' => 'required|exists:lesson_themes,id',
        ]);

        return response()->json(
            $this->lessonViewService->previousExperience(
                (int) $request->lesson_theme_id,
                (int) Auth::id()
            )
        );
    }

    // Repeater (path 2) personal material for the learner's CURRENT attempt.
    // No previous_version: generated from the theme's active materials (母体) +
    // the learner's FULL portfolio history on this theme (avoids duplication).
    public function generate_personal_material(Request $request, LessonTheme $theme)
    {
        $userId = (int) Auth::id();
        $this->authorizeLearnerThemeAccess($theme, $userId);

        $current = LessonPortfolio::where('lesson_theme_id', $theme->id)
            ->where('user_id', $userId)
            ->currentAttempt()
            ->first();

        // attempt 1 = first learner (pre-created content); attempt >1 = AI-generated,
        // whether a plain repeater (path 2) or a salary challenge (path 3, has salary_issue_id).
        abort_if(! $current || (int) $current->attempt_no <= 1, 400, '再学習の学習ではありません。');

        $apiKey = config('services.openai.api_key');
        abort_if(! $apiKey, 500, 'OpenAI APIキーが設定されていません。');

        $history = LessonPortfolio::where('lesson_theme_id', $theme->id)
            ->where('user_id', $userId)
            ->where('id', '!=', $current->id)
            ->orderBy('attempt_no')
            ->get();

        // Path 3 (salary challenge): fold the chosen outcome goal into the generation.
        $goal = $current->salary_issue_id
            ? optional(\App\Models\SalaryIssue::with('project_goal')->find($current->salary_issue_id))->project_goal
            : null;

        // Path 3 (salary challenge) draws from its own AI config slot; plain repeaters use the recurring slot.
        $aiConfigKey = $current->salary_issue_id ? 'salary_issue' : 'portfolio_recurring_trainee';
        $config = $theme->aiConfigs()->where('config_key', $aiConfigKey)->first();
        $configKey = $this->repeaterConfigKey($current->id);
        $learnerProfile = $this->personalMaterialPresentationService->learnerEvaluationContext(
            $userId,
            $goal?->year ? (int) $goal->year : null,
            $goal?->which_half ? (string) $goal->which_half : null
        );
        $input = $this->buildRecurringPersonalMaterialInput(
            $theme,
            $history,
            $goal,
            $learnerProfile
        );
        $defaultInstructions = $goal
            ? 'これまでの学習履歴・ポートフォリオと、挑戦する成果目標をもとに、昇給課題に挑む学習者向けの個人専用研修資料を日本語で作成してください。成果目標の達成に必要な知識・考え方・行動を補完する構成にし、過去の内容の重複は避けること。末尾にグループディスカッション用のテーマを3つ提示してください。'
            : 'これまでの学習履歴とポートフォリオをもとに、再学習者向けの個人専用研修資料を日本語で作成してください。復習ではなく、最新の考え方で自分の整理を見直す構成にし、過去の内容の重複は避けること。末尾にグループディスカッション用のテーマを3つ提示してください。';
        $settings = ($config && is_array($config->settings)) ? $config->settings : [];
        $model = ($config?->model) ?: config('services.learning_presentation.model', 'gpt-5.6-sol');
        $settings = $this->personalMaterialPresentationService->compatibleRequestSettings(
            $settings,
            $model
        );
        $textSettings = is_array($settings['text'] ?? null) ? $settings['text'] : [];
        unset($textSettings['format']);
        unset($settings['text']);
        $presentationAccentColor = $this->personalMaterialPresentationService->randomAccentColor();
        $requiredContentContract = <<<'PROMPT'
 Mandatory content contract:
 - 完成した資料の最後のsceneは、グループディスカッション用テーマ専用の<section id="group-discussion" class="scene">にする。
 - #group-discussion内に、次の構造を持つ<article class="discussion-theme">をちょうど3つ置く。省略、統合、追加は禁止。
 - 3つのarticleにはdata-theme-number="1"、"2"、"3"を順番に付ける。
 - 各articleには、<h3>テーマN：具体的なテーマ名</h3>、テーマの狙いを説明する<p>、話し合う問いを示す<p class="discussion-question">を必ず含める。
 - 3つは互いに異なる観点を扱い、入力された研修テーマ、学習履歴、成果目標の具体的な内容に接続する。
 - 視覚表現のために、この必須内容を短縮、置換、装飾テキスト化してはならない。
 - HTMLを返す直前に、#group-discussion直下または子孫の.discussion-themeがちょうど3つあり、連番、見出し、問いが揃っていることを確認し、不足があれば修正する。
PROMPT;
        $htmlPresentationInstructions = <<<PROMPT
 Role: あなたは、学習体験を設計する日本語エディトリアルデザイナー兼インフォメーションデザイナーです。

 Goal: 入力された学習履歴と目標を、読むほど理解が深まる、独創的な縦スクロール型HTML研修体験にしてください。
 オフィス文書や箇条書き資料ではなく、デザイン誌の特集ページや上質なインタラクティブ記事のように構成してください。

 Success criteria:
 - 完全な自己完結型HTMLドキュメントを1つだけ返す。説明、Markdown、JSON、コードフェンスは返さない。
 - <main class="story">直下に6〜9個の<section class="scene">を置き、1つの連続したページとして自然な縦スクロールで読めるようにする。
 - 横スクロール、scroll snap、固定スライド高、矢印ナビゲーション、ページ送りは使用しない。
 - 最初の画面だけでテーマと学習者固有の課題が印象的に伝わる。
 - 各sceneは異なる役割と視覚構成を持ち、同じカードや箇条書きレイアウトを反復しない。
 - 少なくとも3つの内容を、文章ではなくインラインSVGまたはCSSによる図解にする。
   例: 関係図、プロセス、タイムライン、比較、優先順位、ロードマップ、フィードバックループ。
 - 大きなタイポグラフィ、非対称グリッド、重なり、番号、余白、黒い面を意図的に組み合わせ、視覚的なリズムを作る。
 - 最後のグループディスカッションsceneに、学習者が次に取る行動も示す。
 - 入力の具体的な文脈とニュアンスを保ち、経験の再解釈、課題との接続、実践方法まで深める。

 Visual system:
 - 今回の唯一の有彩色は{$presentationAccentColor}。:rootに--accentとして定義する。
 - --accentは強調面、図形、ラベルに限定し、ページやscene全面の背景には使わない。
 - 白、オフホワイト、黒、グレーを主役にし、淡いアクセントと強い黒のコントラストで見せる。
 - 本文を小さく詰め込まない。長文は編集し、視覚構造へ変換する。
 - 細い左線、薄い枠、同じ角丸カード、同じ影を繰り返すだけのデザインは禁止。
 - 見出し上部などにeyebrow（短い補助ラベル）を置く場合は、英語を使用せず、内容に即した自然な日本語にする。

 Constraints:
 - <html lang="ja">、<head>、<title>、<style>、<body>、表紙の<h1>を含める。
 - 見出し、段落、リスト、blockquoteを意味に応じて使用し、Markdown版へ変換可能な意味的HTMLにする。
 - インラインSVGはviewBoxを持たせ、外部参照を使わない。装飾SVGにはaria-hidden="true"を付ける。
 - 図解の意味は近接する見出し、段落、または<figcaption>にも残し、テキスト版でも文脈を失わないようにする。
 - JavaScript、外部URL、外部フォント、外部画像、フォーム要素は使用しない。
 - 入力にない事実、個人情報、数値は作らない。
PROMPT;
        $package = array_merge($settings, [
            'model' => $model,
            'max_output_tokens' => (int) (
                $settings['max_output_tokens']
                ?? config('services.learning_presentation.max_output_tokens', 20000)
            ),
            'instructions' => (($config?->instructions) ?: $defaultInstructions)
                ."\n\n入力にない事実や個人情報を作らないでください。\n\n"
                .$requiredContentContract."\n\n"
                .$htmlPresentationInstructions,
            'input' => $input,
        ]);
        if ($textSettings !== []) {
            $package['text'] = $textSettings;
        }

        $client = OpenAI::client($apiKey);
        $presentationService = $this->personalMaterialPresentationService;
        $response = $client->responses()->create($package);
        $content = trim((string) $response->outputText);

        try {
            $presentation = $presentationService->parseHtmlResponse($content);
        } catch (RuntimeException $exception) {
            $repairPackage = $package;
            $repairPackage['instructions'] = $package['instructions']
                ."\n\nRepair requirement:\n"
                .'前回のHTMLは必須契約の検証に失敗しました。検証エラー: '
                .$exception->getMessage()
                ."\n既存の内容とデザイン品質をできる限り保ちながら、"
                .'必須構造を満たす完全なHTMLドキュメントとして修正してください。'
                .'説明、Markdown、コードフェンスは返さないでください。';
            $repairPackage['input'] = $input
                ."\n\n修正対象の前回HTML:\n"
                .$content;
            $repairResponse = $client->responses()->create($repairPackage);
            $content = trim((string) $repairResponse->outputText);
            $presentation = $presentationService->parseHtmlResponse($content);
        }

        $markdown = $presentationService->toMarkdown($presentation);
        $existingMaterial = LessonPersonalMaterial::query()
            ->where('lesson_theme_id', $theme->id)
            ->where('user_id', $userId)
            ->where('config_key', $configKey)
            ->first();
        $previousPresentationPath = $existingMaterial?->presentation_path;

        $personalMaterial = LessonPersonalMaterial::updateOrCreate(
            [
                'lesson_theme_id' => $theme->id,
                'user_id' => $userId,
                'config_key' => $configKey,
            ],
            [
                'lesson_theme_ai_config_id' => $config?->id,
                'content' => $markdown,
                'presentation_spec' => $presentation,
                'presentation_theme' => $presentationAccentColor,
                'presentation_path' => null,
                'understand' => null,
                'important_point' => null,
                'source_snapshot' => [
                    'history_portfolio_ids' => $history->pluck('id')->all(),
                ],
                'generated_at' => now(),
                'completed_at' => null,
            ]
        );

        if ($previousPresentationPath) {
            Storage::disk('local')->delete($previousPresentationPath);
        }

        return response()->json($personalMaterial->fresh());
    }

    private function repeaterConfigKey(int $attemptId): string
    {
        return 'repeater_attempt_'.$attemptId;
    }

    public function save_personal_material_feedback(Request $request, LessonTheme $theme)
    {
        $userId = (int) Auth::id();
        $this->authorizeLearnerThemeAccess($theme, $userId);

        $data = $request->validate([
            'understand' => 'required|boolean',
            'important_point' => 'nullable|string',
        ]);

        if ($data['understand'] && blank($data['important_point'] ?? null)) {
            throw ValidationException::withMessages([
                'important_point' => '特に重要だと理解した点を入力してください。',
            ]);
        }

        $current = LessonPortfolio::where('lesson_theme_id', $theme->id)
            ->where('user_id', $userId)
            ->currentAttempt()
            ->first();

        abort_if(! $current, 404, '学習が開始されていません。');

        $personalMaterial = LessonPersonalMaterial::query()
            ->where('lesson_theme_id', $theme->id)
            ->where('user_id', $userId)
            ->where('config_key', $this->repeaterConfigKey($current->id))
            ->firstOrFail();

        $personalMaterial->update([
            'understand' => $data['understand'],
            'important_point' => $data['important_point'] ?? null,
            'completed_at' => $data['understand'] ? now() : null,
        ]);

        if ($data['understand']) {
            $current->update([
                'portfolio_title' => $current->portfolio_title ?: $theme->title,
                'content' => $current->content ?: $personalMaterial->content,
                'basic_knowledge' => $data['important_point'],
            ]);
        }

        return response()->json($personalMaterial->fresh());
    }

    public function download_personal_material_presentation(
        LessonTheme $theme,
        LessonPersonalMaterial $personalMaterial
    ) {
        $userId = (int) Auth::id();
        $this->authorizeLearnerThemeAccess($theme, $userId);

        abort_unless(
            (int) $personalMaterial->lesson_theme_id === (int) $theme->id
            && (int) $personalMaterial->user_id === $userId,
            404
        );
        abort_if(
            blank($personalMaterial->presentation_path)
            || ! Storage::disk('local')->exists($personalMaterial->presentation_path),
            404,
            'プレゼンテーションファイルがありません。'
        );

        $themeTitle = str_replace(["\r", "\n", '/', '\\'], '-', $theme->title ?: '個人専用研修資料');
        $filename = $themeTitle.'-個人専用研修資料.pptx';

        return response()->download(
            Storage::disk('local')->path($personalMaterial->presentation_path),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation']
        );
    }

    private function authorizeLearnerThemeAccess(LessonTheme $theme, int $userId): void
    {
        $allowed = $theme->accessMembers()
            ->where('users.id', $userId)
            ->exists();

        if (! $allowed && $theme->accessMembers()->exists()) {
            abort(403, 'このテーマにアクセスできません。');
        }
    }

    /**
     * @param  array{職階: string|null, 等級: string|null}  $learnerProfile
     */
    private function buildRecurringPersonalMaterialInput(
        LessonTheme $theme,
        $history,
        $goal,
        array $learnerProfile
    ): string {
        // 母体 = the theme's default-version content.
        $materials = LessonMaterial::where('lesson_theme_id', $theme->id)
            ->whereHas('version', fn ($q) => $q->where('is_default', true))
            ->orderBy('priority')
            ->orderBy('id')
            ->get(['title', 'material_type', 'content'])
            ->map(function (LessonMaterial $material) {
                return [
                    'title' => $material->title,
                    'material_type' => $material->material_type,
                    'content' => strip_tags((string) $material->content),
                ];
            })
            ->values()
            ->all();

        $portfolioHistory = collect($history)
            ->map(function (LessonPortfolio $portfolio) {
                return [
                    'attempt_no' => (int) $portfolio->attempt_no,
                    'title' => $portfolio->public_title,
                    'content' => $portfolio->public_content,
                    'positive_feedback' => $portfolio->positive_feedback,
                    'negative_feedback' => $portfolio->negative_feedback,
                    'draft_content' => $portfolio->content,
                    'episode' => $portfolio->episode,
                    'basic_knowledge' => $portfolio->basic_knowledge,
                ];
            })
            ->values()
            ->all();

        return json_encode([
            'purpose' => $goal
                ? '昇給課題に挑む学習者向けの、成果目標達成に資する個人専用研修資料を作成する（過去の内容と重複しないこと）'
                : '再学習者向けの個人専用研修資料を作成する（過去の内容と重複しないこと）',
            'current_theme' => [
                'id' => $theme->id,
                'title' => $theme->title,
                'guidance' => strip_tags((string) $theme->guidance),
                'episode_guidance' => strip_tags((string) $theme->episode_guidance),
                'title_guidance' => strip_tags((string) $theme->title_guidance),
                'materials' => $materials,
            ],
            'portfolio_history' => $portfolioHistory,
            'learner_profile' => $learnerProfile,
            'salary_challenge_goal' => $goal ? [
                'title' => $goal->title,
                'outcome_goal' => $goal->outcome_goal,
                'action_plan' => $goal->action_plan,
                'expected_effect' => $goal->expected_effect,
                'kgi' => $goal->kgi,
            ] : null,
            'output_expectation' => [
                '個人専用の研修資料',
                'ディスカッションテーマ3つ',
                '必要に応じた理想的な議論の着地点例',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
