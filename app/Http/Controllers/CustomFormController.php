<?php

namespace App\Http\Controllers;

use App\Models\CustomForm;
use App\Models\ProjectRecord;
use App\Models\ProjectCheckitemCategory;
use App\Models\ProjectType;
use App\Models\SurveyAnswer;
use App\Models\CustomFormBlockElement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class CustomFormController extends Controller
{
    private const PROJECT_CREATION_USAGE = 'project_creation';

    public function get_survey(Request $request){
        $request->validate([
            'id' => 'required'
        ]);

        $active_user = Auth::user();

            $survey = CustomForm::with(['blocks' => function($q) use($active_user)  {
                $q->with(['answers' => function($q)use($active_user)  {
                    $q->where('user_id', $active_user->id)->with('files');                    
                }])->with(['elements' => function($q) use($active_user) {
                    $q->with('files');
                    $q->with(['answers' => function($q)use($active_user)  {
                        $q->where('user_id', $active_user->id);
                    }]);
                }]);
            }])
            ->with(['survey_answers' => function($q) use($active_user) {
                $q->where('user_id', $active_user->id)->with(['block_answers' => function($q) use($active_user) {
                    $q->where('user_id', $active_user->id)->with(['element_answers' => function($q) use($active_user) {
                        $q->where('user_id', $active_user->id);
                    }])->with('files');
                }]);
            }])
            ->findOrFail($request->id);
            return response()->json($survey);

    }
    public function duplicate_custom_form(Request $request){
        $request->validate([
            'id' => 'required'
        ]);

        $form = CustomForm::with(['blocks.elements.files', 'blocks.checkitemCategories', 'users', 'admins'])->findOrFail($request->id);
        $usage = $this->normalizeUsage($form->usage);
        $status = (int) ($form->status ?? 0);
        $this->ensureExclusiveActiveProjectCreationForm($usage, $status, null, $form->project_type_id);
        $new_form = $form->replicate();
        $new_form->title = $form->title . ' (コピー)';
        $new_form->usage = $usage;
        $new_form->status = $status;
        $new_form->is_public = false;
        $new_form->public_token = null;
        $new_form->save();
        $block_id_map = [];
        $element_id_map = [];
        $new_blocks = [];
        $form->blocks->each(function($block) use($new_form, &$block_id_map, &$element_id_map, &$new_blocks){
            $new_block = $block->replicate();
            $new_block->custom_form_id = $new_form->id;
            $new_block->depends_on = null;
            $new_block->save();
            $new_block->checkitemCategories()->sync($block->checkitemCategories->pluck('id')->all());
            $block_id_map[$block->id] = $new_block->id;
            $new_blocks[] = ['model' => $new_block, 'origin' => $block];
            $block->elements->each(function($element) use($new_block, &$element_id_map){
                $new_element = $element->replicate();
                $new_element->custom_form_block_id = $new_block->id;
                $new_element->save();
                $this->syncElementFiles($new_element, $element->files);
                $element_id_map[$element->id] = $new_element->id;
            });
        });
        foreach ($new_blocks as $saved) {
            $mapped = $this->mapDependsOn($saved['origin']->depends_on, $block_id_map, $element_id_map);
            $saved['model']->update([
                'depends_on' => $mapped,
            ]);
        }
        $now = now();
        $form->users->each(fn($user) => $new_form->users()->attach($user->id, ['authority' => 0, 'created_at' => $now, 'updated_at' => $now]));

        $form->admins->each(fn($admin) => $new_form->admins()->attach($admin->id, ['authority' => 1, 'created_at' => $now, 'updated_at' => $now]));
        return response()->json(['message' => 'Form duplicated successfully'], 200);
    }
    public function get_custom_forms(Request $request){
        $active_user = Auth::user();

        $forms = CustomForm::when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('usage'), function ($q) use ($request) {
                $usage = $request->input('usage');
                if ($usage === 'public') {
                    $q->where('usage', 'general')->where('is_public', true);
                    return;
                }

                if ($usage === 'general') {
                    $q->where('usage', 'general')->where(function ($inner) {
                        $inner->where('is_public', false)->orWhereNull('is_public');
                    });
                    return;
                }

                $q->where('usage', $usage);
            })
            ->when($request->filled('project_type_id'), function ($q) use ($request) {
                $q->where('project_type_id', $request->integer('project_type_id'));
            })
            ->when($request->filled('keyword'), function ($q) use ($request) {
                $keyword = trim((string) $request->input('keyword'));
                $q->where('title', 'like', '%' . $keyword . '%');
            })
            ->where(function ($q) {
                $q->whereNull('board_record_id')
                ->orWhere('board_record_id', 3758);
            })
            ->with(['blocks.checkitemCategories', 'projectType'])
            ->orderBy('created_at', 'desc')
            ->when(($active_user->isBoss() || $active_user->isPM()) && !$active_user->isAdmin(), function ($q) use ($active_user) {
                $q->whereHas('admins', function ($q) use ($active_user) {
                    $q->where('user_id', $active_user->id);
                });
            })
            ->with(['users', 'admins', 'survey_answers'])
            ->get();


        $forms->map(function($form){
            $form->users->map(function($user) use($form){
                $user['is_answered'] = $form->survey_answers->where('user_id', $user->id)->count() > 0;
            });
        });

        
        return response()->json($forms);

        
    }
    public function get_active_project_creation_form(Request $request)
    {
        $validated = $request->validate([
            'project_type_id' => ['required', 'integer', 'exists:project_types,id'],
        ]);

        $form = CustomForm::with(['blocks.elements', 'blocks.checkitemCategories', 'projectType'])
            ->where('usage', self::PROJECT_CREATION_USAGE)
            ->where('status', 0)
            ->where('project_type_id', $validated['project_type_id'])
            ->where(function ($q) {
                $q->whereNull('board_record_id')
                    ->orWhere('board_record_id', 3758);
            })
            ->latest('updated_at')
            ->first();

        return response()->json($form);
    }
    public function get_form_projects(CustomForm $form)
    {
        if ($this->normalizeUsage($form->usage) !== self::PROJECT_CREATION_USAGE) {
            return response()->json([]);
        }

        $projects = ProjectRecord::query()
            ->select([
                'id',
                'name',
                'status',
                'date_start',
                'date_end',
                'project_type_id',
            ])
            ->with([
                'projectType:id,label,key',
                'manager',
                'specs:id,project_id,updated_at',
            ])
            ->whereHas('specs', function ($q) use ($form) {
                $q->where('spec_data->form_id', $form->id);
            })
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($projects);
    }
    public function save_custom_form (Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'users' => 'array',
            'admins' => 'array',
            'blocks' => 'array',
            'board_record_id' => 'nullable|integer',
            'repeat_setting' => 'integer',
            'repeat_day' => 'nullable|integer|min:1|max:31',
            'has_prize' => 'boolean',
            'is_public' => 'boolean',
            'status' => 'sometimes|integer|in:0,1',
            'usage' => 'nullable|string|in:general,project_creation',
            'project_type_id' => 'nullable|integer|exists:project_types,id',
            'blocks.*.id' => 'nullable|integer',
            'blocks.*.type' => 'required|string|max:50',
            'blocks.*.question' => 'required|string',
            'blocks.*.is_required' => 'boolean',
            'blocks.*.order_number' => 'required|integer',
            'blocks.*.placeholder' => 'nullable|string',
            'blocks.*.category_ids' => 'nullable|array',
            'blocks.*.elements' => 'array',
            'blocks.*.elements.*.id' => 'nullable|integer',
            'blocks.*.elements.*.value' => 'required|string',
            'blocks.*.elements.*.is_required' => 'boolean',
            'blocks.*.elements.*.has_sub_text_required' => 'boolean',
            'blocks.*.elements.*.has_sub_text' => 'boolean',
            'blocks.*.elements.*.has_file_attachment' => 'boolean',
            'blocks.*.elements.*.placeholder' => 'nullable|string',
            'blocks.*.elements.*.files' => 'nullable|array',
            'blocks.*.depends_on' => 'nullable|array',
            'blocks.*.depends_on.block_id' => 'nullable|integer',
            'blocks.*.depends_on.type' => 'nullable|string|in:radio,checkbox',
            'blocks.*.depends_on.element_ids' => 'nullable|array',
            'blocks.*.depends_on.element_ids.*' => 'integer',
            'blocks.*.depends_on.match' => 'nullable|string|in:any,all',
            'blocks.*.depends_on.*.block_id' => 'nullable|integer',
            'blocks.*.depends_on.*.type' => 'nullable|string|in:radio,checkbox',
            'blocks.*.depends_on.*.element_ids' => 'nullable|array',
            'blocks.*.depends_on.*.element_ids.*' => 'integer',
            'blocks.*.depends_on.*.match' => 'nullable|string|in:any,all',
        ]);
    
        $form = $this->saveForm( $validated);

        $block_ids = $this->saveBlocks($form, Arr::get($validated, 'blocks', []));
        // if (!empty($request->removed_items)) {
        //     $blocks = $form->blocks()->whereIn('id', $request->removed_items)->get();
        //     foreach ($blocks as $block) {
        //         $block->elements()->delete();
        //     }
        //     $form->blocks()->whereIn('id', $request->removed_items)->delete();
        // }
        $form->blocks()->whereNotIn('id', $block_ids)->delete();
        return response()->json(['message' => 'Form saved successfully'], 200);
    }
    private function saveForm( array $data)
    {
        $id = $this->sanitizeId(Arr::get($data, 'id'));
        $usage = $this->normalizeUsage(Arr::get($data, 'usage'));
        $status = (int) Arr::get($data, 'status', 0);
        $projectTypeId = $usage === self::PROJECT_CREATION_USAGE
            ? $this->sanitizeId(Arr::get($data, 'project_type_id'))
            : null;
        $repeatSetting = $this->normalizeRepeatSetting($usage, Arr::get($data, 'repeat_setting'));
        $repeatDay = $repeatSetting === 1 ? Arr::get($data, 'repeat_day') : null;
        $hasPrize = $usage === self::PROJECT_CREATION_USAGE
            ? false
            : (bool) Arr::get($data, 'has_prize', false);
        $users = $usage === self::PROJECT_CREATION_USAGE
            ? []
            : Arr::get($data, 'users', []);
        $existingForm = $id ? CustomForm::find($id) : null;
        $publicAccess = $this->normalizePublicSetting($data, $usage);
        if ($publicAccess['is_public'] && $existingForm?->public_token) {
            $publicAccess['public_token'] = $existingForm->public_token;
        }
        if ($publicAccess['is_public']) {
            $users = [];
            $hasPrize = false;
            $repeatSetting = 0;
            $repeatDay = null;
        }
        $this->ensureExclusiveActiveProjectCreationForm($usage, $status, $id, $projectTypeId);

        $form = CustomForm::updateOrCreate(
            ['id' => $id],
            [
                'title' => Arr::get($data, 'title'),
                'description' => Arr::get($data, 'description'),
                'repeat_setting' => $repeatSetting,
                'repeat_day' => $repeatDay,
                'board_record_id' => Arr::get($data, 'board_record_id', null),
                'has_prize' => $hasPrize,
                'is_public' => $publicAccess['is_public'],
                'public_token' => $publicAccess['public_token'],
                'status' => $status,
                'usage' => $usage,
                'project_type_id' => $projectTypeId,
            ]
        );
        $user_ids = collect($users)->pluck('id');

        $admins = Arr::get($data, 'admins', []);
        $admin_ids = collect($admins)->pluck('id');
        $this->syncAuthorizedMembers($form->users(), $user_ids, 0);
        $this->syncAuthorizedMembers($form->admins(), $admin_ids, 1);
        return $form;
    }
    public function delete_custom_form(Request $request){
        $request->validate([
            'id' => 'required'
        ]);

        $form = CustomForm::findOrFail($request->id);
        $form->blocks->each(function($block){
            $block->elements->each(function($element){
                $element->fileAttachments()->delete();
            });
            $block->elements()->delete();
        });
        $form->blocks()->delete();
        $form->delete();
        return response('Form deleted successfully', 200);
    }
    public function update_custom_form_status(Request $request){
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|integer|in:0,1',
        ]);
        $form = CustomForm::findOrFail($request->id);
        $nextStatus = (int) $request->status;
        $this->ensureExclusiveActiveProjectCreationForm(
            $this->normalizeUsage($form->usage),
            $nextStatus,
            $form->id,
            $form->project_type_id
        );
        $form->update([
            'status' => $nextStatus,
        ]);
        return response()->json($form);
    }
    private function saveBlocks($form, array $blocks)
    {
        $usage = $this->normalizeUsage($form->usage);
        $block_ids = [];
        $block_id_map = [];
        $element_id_map = [];
        $saved_blocks = [];
        foreach ($blocks as $block) {
            $blockModel = $form->blocks()->updateOrCreate(
                ['id' => $this->sanitizeId(Arr::get($block, 'id'))],
                [
                    'type' => Arr::get($block, 'type'),
                    'question' => Arr::get($block, 'question'),
                    'is_required' => Arr::get($block, 'is_required', false),
                    'order_number' => Arr::get($block, 'order_number'),
                    'placeholder' => Arr::get($block, 'placeholder'),
                    'categories' => null,
                ]
            );
            if ($usage === self::PROJECT_CREATION_USAGE) {
                $categoryIds = $this->resolveBlockCategoryIds($block);
                $blockModel->checkitemCategories()->sync($categoryIds);
            } else {
                $blockModel->checkitemCategories()->sync([]);
            }
            $block_ids[] = $blockModel->id;
            $original_block_id = Arr::get($block, 'id');
            if (is_numeric($original_block_id)) {
                $block_id_map[$original_block_id] = $blockModel->id;
            }
            $save_result = $this->saveElements($blockModel, Arr::get($block, 'elements', []), $element_id_map);
            $element_ids = $save_result['element_ids'];
            $element_id_map = $save_result['element_id_map'];
            $blockModel->elements()->whereNotIn('id', $element_ids)->get()->each(function ($element) {
                $element->fileAttachments()->delete();
                $element->delete();
            });
            $saved_blocks[] = ['model' => $blockModel, 'data' => $block];
        }
        foreach ($saved_blocks as $saved) {
            $mapped = $this->mapDependsOn(Arr::get($saved['data'], 'depends_on'), $block_id_map, $element_id_map);
            $saved['model']->update([
                'depends_on' => $mapped,
            ]);
        }
        return $block_ids;
    }
    
    /**
     * Save the elements for a block.
     */
    private function saveElements($block, array $elements, array $element_id_map = [])
    {
        $element_ids = [];
        foreach ($elements as $element) {
            $el_record = $block->elements()->updateOrCreate(
                ['id' => $this->sanitizeId(Arr::get($element, 'id'))],
                [
                    'value' => Arr::get($element, 'value'),
                    'is_required' => Arr::get($element, 'is_required', false),
                    'has_sub_text_required' => Arr::get($element, 'has_sub_text_required', false),
                    'has_sub_text' => Arr::get($element, 'has_sub_text'),
                    'has_file_attachment' => Arr::get($element, 'has_file_attachment', false),
                    'placeholder' => Arr::get($element, 'placeholder'),
                ]
            );
            $this->syncElementFiles(
                $el_record,
                Arr::get($element, 'has_file_attachment', false) ? Arr::get($element, 'files', []) : []
            );
            $element_ids[] = $el_record->id;
            $original_element_id = Arr::get($element, 'id');
            if (is_numeric($original_element_id)) {
                $element_id_map[$original_element_id] = $el_record->id;
            }
        }
        return [
            'element_ids' => $element_ids,
            'element_id_map' => $element_id_map,
        ];
    }
    private function mapDependsOn($depends_on, array $block_id_map, array $element_id_map)
    {
        if (!is_array($depends_on)) {
            return null;
        }
        $conditions = $this->normalizeDependsOnConditions($depends_on);
        $mapped = [];
        foreach ($conditions as $condition) {
            $block_id = Arr::get($condition, 'block_id');
            if (!is_numeric($block_id)) {
                continue;
            }
            $mapped_block_id = $block_id_map[$block_id] ?? $block_id;
            if (!is_numeric($mapped_block_id)) {
                continue;
            }
            $element_ids = Arr::get($condition, 'element_ids');
            if (!is_array($element_ids)) {
                $element_ids = [];
            }
            $mapped_element_ids = collect($element_ids)
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => $element_id_map[$id] ?? $id)
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            if (!count($mapped_element_ids)) {
                continue;
            }
            $type = Arr::get($condition, 'type');
            $condition_type = in_array($type, ['radio', 'checkbox'], true) ? $type : 'radio';
            $payload = [
                'block_id' => (int) $mapped_block_id,
                'type' => $condition_type,
                'element_ids' => $mapped_element_ids,
            ];
            if ($condition_type === 'checkbox') {
                $match = Arr::get($condition, 'match');
                $payload['match'] = in_array($match, ['any', 'all'], true) ? $match : 'any';
            }
            $mapped[] = $payload;
        }
        return count($mapped) ? $mapped : null;
    }

    private function normalizeDependsOnConditions(array $depends_on): array
    {
        if (!count($depends_on)) {
            return [];
        }
        $is_assoc = array_keys($depends_on) !== range(0, count($depends_on) - 1);
        return $is_assoc ? [$depends_on] : $depends_on;
    }
    private function sanitizeId($id)
    {
        return (is_numeric($id) && $id > 0) ? $id : null;
    }

    private function syncAuthorizedMembers(BelongsToMany $relation, $memberIds, int $authority): void
    {
        $memberIds = collect($memberIds)
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existingIds = $relation->allRelatedIds()
            ->map(fn ($id) => (int) $id)
            ->values();

        $newIds = $memberIds->diff($existingIds);
        $now = now();

        $payload = $memberIds->mapWithKeys(function (int $memberId) use ($authority, $newIds, $now) {
            $attributes = ['authority' => $authority];

            if ($newIds->contains($memberId)) {
                $attributes['created_at'] = $now;
                $attributes['updated_at'] = $now;
            }

            return [$memberId => $attributes];
        })->all();

        $relation->sync($payload);
    }

    private function normalizePublicSetting(array $data, string $usage): array
    {
        $requested = $usage !== self::PROJECT_CREATION_USAGE
            && (bool) Arr::get($data, 'is_public', false);

        if (!$requested) {
            return [
                'is_public' => false,
                'public_token' => null,
            ];
        }

        $blocks = Arr::get($data, 'blocks', []);
        $hasFileBlock = collect($blocks)->contains(
            fn ($block) => Arr::get($block, 'type') === 'file'
        );

        if ($hasFileBlock) {
            throw ValidationException::withMessages([
                'is_public' => '公開フォームではファイル質問をまだ利用できません。',
            ]);
        }

        return [
            'is_public' => true,
            'public_token' => $this->generatePublicToken(),
        ];
    }
    private function generatePublicToken(): string
    {
        do {
            $token = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        } while (CustomForm::where('public_token', $token)->exists());

        return $token;
    }
    private function normalizeUsage($usage): string
    {
        return $usage === self::PROJECT_CREATION_USAGE
            ? self::PROJECT_CREATION_USAGE
            : 'general';
    }
    private function normalizeRepeatSetting(string $usage, $repeatSetting): int
    {
        if ($usage === self::PROJECT_CREATION_USAGE) {
            return 0;
        }

        return (int) $repeatSetting === 1 ? 1 : 0;
    }
    private function ensureExclusiveActiveProjectCreationForm(string $usage, int $status, ?int $ignoreId = null, ?int $projectTypeId = null): void
    {
        if ($usage !== self::PROJECT_CREATION_USAGE || $status !== 0) {
            return;
        }

        if (!$projectTypeId) {
            throw ValidationException::withMessages([
                'project_type_id' => '案件作成フォームにはプロジェクト種別が必要です。',
            ]);
        }

        $query = CustomForm::query()
            ->where('usage', self::PROJECT_CREATION_USAGE)
            ->where('status', 0)
            ->where('project_type_id', $projectTypeId);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if (!$query->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'project_type_id' => '選択したプロジェクト種別には進行中の案件作成フォームが既に存在します。既存フォームを完了してから作成・再開してください。',
        ]);
    }
    private function resolveBlockCategoryIds(array $block): array
    {
        $categoryInputs = collect(Arr::get($block, 'category_ids', []))
            ->when(
                empty(Arr::get($block, 'category_ids')),
                fn ($items) => $items->merge(Arr::get($block, 'categories', []))
            )
            ->unique()
            ->values();

        $ids = collect();
        $labels = collect();

        foreach ($categoryInputs as $input) {
            if (is_numeric($input)) {
                $ids->push((int) $input);
                continue;
            }

            $label = trim((string) $input);
            if ($label !== '') {
                $labels->push($label);
            }
        }

        if ($labels->isNotEmpty()) {
            $existing = ProjectCheckitemCategory::whereIn('label', $labels->all())->pluck('id', 'label');
            foreach ($labels->unique() as $label) {
                $categoryId = $existing[$label] ?? $this->createCategoryFromLabel($label);
                $ids->push((int) $categoryId);
            }
        }

        return $ids->unique()->values()->all();
    }

    private function createCategoryFromLabel(string $label): int
    {
        $label = trim($label);
        $existing = ProjectCheckitemCategory::where('label', $label)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $baseKey = Str::slug($label, '_') ?: 'category';
        $key = $baseKey;
        $suffix = 2;
        while (ProjectCheckitemCategory::where('key', $key)->exists()) {
            $key = $baseKey . '_' . $suffix;
            $suffix++;
        }

        $sortOrder = ((int) ProjectCheckitemCategory::max('sort_order')) + 1;

        return (int) ProjectCheckitemCategory::create([
            'key' => $key,
            'label' => $label,
            'sort_order' => $sortOrder,
            'status' => 0,
        ])->id;
    }
    public function save_survey_answer(Request $request){

        $active_user = Auth::user();
        $survey = DB::transaction(function () use ($request, $active_user) {
            $survey = SurveyAnswer::firstOrCreate([
                "id" => $request->survey_answer_id,
            ]);
            if($request->survey_answer_id){
                $survey->block_answers->each(function($block_answer){
                    $block_answer->element_answers()->delete();
                });
                $survey->block_answers()->delete();
            }

            $survey->update([
                'status' => $request->status,
                'custom_form_id' => $request->custom_form_id,
                'user_id' => $active_user->id,
                'target_date' => $request->target_date ?? null,
            ]);

            $params = $request->params;
            foreach($params as $block){
                $block_answer = $survey->block_answers()->create([
                    "user_id" => $active_user->id,
                    "text_answer" => $block['text_answer'],
                    "custom_form_block_id" => $block['custom_form_block_id']
                ]);
                $block_answer->files()->sync(Arr::get($block, 'files', []));
                $elements = Arr::get($block, 'element_answers', []);
                foreach($elements as $element){
                    $block_answer->element_answers()->create([
                        "user_id" => $active_user->id,
                        "custom_form_block_element_id" => $element['custom_form_block_element_id'],
                        "sub_text" => $element['sub_text'] ?? null,
                        "checked" => $element['checked']
                    ]);
                }
            }

            return $survey;
        });

        $prize_eligible = false;
        $custom_form = CustomForm::findOrFail($request->custom_form_id);
        if($custom_form->has_prize){
            $user = $custom_form->users()->where('users.id', $active_user->id)->wherePivot('try_flag', 0)->first();
            if($user){
                $prize_eligible = true;
            }
        }

        return response()->json([
            'id' => $survey->id,
            'prize_eligible' => $prize_eligible,
        ]);
            
        

    }
    private function syncElementFiles(CustomFormBlockElement $element, mixed $files): void
    {
        $element->files()->syncWithPivotValues(
            $this->normalizeFileIds($files),
            [
                'attachable_type' => CustomFormBlockElement::class,
                'collection' => 'attachments',
                'created_at' => now(),
            ]
        );
    }
    private function normalizeFileIds(mixed $files): array
    {
        return collect($files ?? [])
            ->map(function ($file) {
                if (is_array($file)) {
                    return Arr::get($file, 'id');
                }

                if (is_object($file)) {
                    return $file->id ?? null;
                }

                return $file;
            })
            ->filter(fn ($fileId) => is_numeric($fileId))
            ->map(fn ($fileId) => (int) $fileId)
            ->unique()
            ->values()
            ->all();
    }
    public function get_survey_answers(Request $request){

        $repeat = $request->repeat_setting;
        $year = $request->year;
        $month = $request->month;
        $target_date = Carbon::create($year, $month, 1)->startOfMonth();

        $survey = SurveyAnswer::where('custom_form_id', $request->custom_form_id)->where('status', 2)
        ->when($repeat == 1, function($q) use($target_date){
            $q->where('target_date', $target_date);
        })
        ->with([
            'user',
            'block_answers.files',
            'block_answers.element_answers',
        ])
        ->get();
        $custom_form = CustomForm::with(['blocks' => function($q) use($repeat, $target_date) {
            $q->with(['answers' => function($q) use($repeat, $target_date) {
                $q->whereHas('survey_answer', function($q) use($repeat, $target_date){
                    $q->where('status', 2)->when($repeat == 1, function($q) use($target_date){
                        $q->where('target_date', $target_date);
                    });
                })->with([
                    'user',
                    'files',
                    'survey_answer:id,user_id',
                    'survey_answer.user:id,name',
                ])->orderBy('created_at', 'desc');                    
            }])
            ->with(['elements' => function($q)  {
                $q->with('files');
                $q->with(['answers' => function($q)  {
                    $q->whereHas('survey_block_answer', function($q){
                        $q->whereHas('survey_answer', function($q){
                            $q->where('status', 2);
                        });
                    })->with([
                        'user',
                        'survey_block_answer:id,survey_answer_id',
                        'survey_block_answer.survey_answer:id,user_id',
                        'survey_block_answer.survey_answer.user:id,name',
                    ])->orderBy('created_at', 'desc');                  
                }]);
            }])->where('type', '!=', 'header');
        }])->findOrFail($request->custom_form_id);
        
        if($request->sort == 'block'){
            return response()->json($custom_form);
        }

        if($request->sort == 'user'){
            $main = [];
            $simpleTypes = ['singletext', 'multitext', 'date', 'time', 'select'];
            foreach($survey as $s){
                $user = $s->user;                
                $data = [];
                $data['id'] = $s->id;
                $data['user'] = $user;
                $data['respondent_label'] = $s->respondent_label;
                $data['created_at'] = $s->updated_at;
                $blocks = $custom_form->blocks;
                foreach($blocks as $block){
                    $anwsers = [];
                    $block_answer = $s->block_answers->where('custom_form_block_id', $block->id)->first();
                    if(!empty($block_answer)){                      
                        
                        if(in_array($block->type, $simpleTypes)){
                            $anwsers[] = ['value' => $block_answer->text_answer];
                        } else if($block->type === 'file'){
                            $anwsers = $block_answer->files;
                        }else{
                            $elements = $block->elements;
                            foreach($elements as $element){
                                $element_answer = $block_answer->element_answers->where('custom_form_block_element_id', $element->id)->first();
                                if(!empty($element_answer)){
                                    $anwsers[] = [
                                        'value' => $element->value,
                                        'sub_text' => $element_answer->sub_text,
                                        'files' => $element->files,
                                    ];
                                }
                            }
                        }
                        $q = [
                            'question' => $block->question,
                            'type' => $block->type,
                            'answers' => $anwsers,
                        ];
                        $data['data'][] = $q;
                    }
                }

                $main[] = $data;
            }
            $main = collect($main)->sortByDesc('created_at')->values()->all();
            return response()->json($main);
        }
        
    }
    public function get_authorized_users() {
        $user_list = User::where('position_id', '<=', 6)
                        ->where('retire', 0)
                        ->inActiveCommunity()
                        ->select('id', 'name', 'icon_path', 'icon_bg')
                        ->get();
        return response()->json($user_list);
    }
    public function get_my_surveys(Request $request) {
        $active_user = Auth::user();
        $per_page = (int) $request->input('per_page', 10);
        $per_page = max(1, min($per_page, 50));
        $keyword = trim((string) $request->input('keyword', ''));

        $surveys = CustomForm::query()
            ->where(function($q) use($active_user) {
                $q->whereHas('users', function($query) use($active_user) {
                    $query->where('users.id', $active_user->id);
                })->orWhereHas('survey_answers', function($query) use($active_user) {
                    $query->where('user_id', $active_user->id);
                });
            })
            ->when($keyword !== '', function($q) use($keyword) {
                $like = '%' . $keyword . '%';
                $q->where(function($qq) use($like) {
                    $qq->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->with(['blocks' => function($q) use($active_user)  {
                $q->with(['answers' => function($q)use($active_user)  {
                    $q->where('user_id', $active_user->id)->with('files');
                }])->with(['elements' => function($q) use($active_user) {
                    $q->with('files');
                    $q->with(['answers' => function($q)use($active_user)  {
                        $q->where('user_id', $active_user->id);
                    }]);
                }]);
            }])
            ->with(['survey_answers' => function($q) use($active_user) {
                $q->where('user_id', $active_user->id)->with(['block_answers' => function($q) use($active_user) {
                    $q->where('user_id', $active_user->id)->with(['element_answers' => function($q) use($active_user) {
                        $q->where('user_id', $active_user->id);
                    }])->with('files');
                }])->with('user');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($per_page);

        return response()->json($surveys);
    }
    public function get_board_forms(Request $request){
        $active_user = Auth::user();
        $request->validate([
            'board_id' => 'required|integer',
        ]);
        $forms = CustomForm::where('board_record_id', $request->board_id)
            ->with(['blocks'])
            // ->when($active_user->position_id <= 6 && ($active_user->id !== 610 && $active_user->id !== 608), function($q) use($active_user){
            //     $q->whereHas('admins', function($q) use($active_user){
            //         $q->where('user_id', $active_user->id);
            //     });
            // })
            ->with(['users' => function ($q) use($active_user) {
                    $q->orderByRaw('user_id = ? DESC', [$active_user->id]);
                }, 
                'admins', 'survey_answers' => function($q){
                $q->with(['user' => function($q){
                    $q->select('id', 'name', 'icon_path', 'icon_bg');
                }, 'block_answers' => function($q){
                    $q->with(['element_answers' => function($q){
                        $q->with('user');
                    }])->with('files');
                },])
                ->orderBy('updated_at', 'desc');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($forms);
    }
    public function save_form_prize(Request $request) {
        $request->validate([
            'form_id' => 'required|integer',
            'params' => 'required|array',
            'params.prize' => 'integer|nullable',
        ]);
        $active_user = Auth::user();
        $form = CustomForm::findOrFail($request->form_id);
        $user = $form->users()->where('users.id', $active_user->id)->first();
        if(!$user){
            throw ValidationException::withMessages(['message' => 'このフォームに対する権限がありません。']);
        }
        $form->users()->updateExistingPivot($active_user->id, [
            'try_flag' => 1,
            'prize' => $request->params['prize'] ?? 0,
            'updated_at' => now(),
        ]);
        return response()->json(['message' => 'Form prize saved successfully'], 200);
    }
}
