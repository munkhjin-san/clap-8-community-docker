<?php

namespace App\Http\Controllers;

use App\Models\CustomForm;
use App\Models\SurveyAnswer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
class CustomFormController extends Controller
{
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function get_survey(Request $request){
        $request->validate([
            'id' => 'required'
        ]);

        $active_user = $this->active_user();

            $survey = CustomForm::with(['blocks' => function($q) use($active_user)  {
                $q->with(['answers' => function($q)use($active_user)  {
                    $q->where('user_id', $active_user->id)->with('files');                    
                }])->with(['elements' => function($q) use($active_user) {
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

        $form = CustomForm::with(['blocks', 'users', 'admins'])->findOrFail($request->id);
        $new_form = $form->replicate();
        $new_form->title = $form->title . ' (コピー)';
        $new_form->save();
        $form->blocks->each(function($block) use($new_form){
            $new_block = $block->replicate();
            $new_block->custom_form_id = $new_form->id;
            $new_block->save();
            $block->elements->each(function($element) use($new_block){
                $new_element = $element->replicate();
                $new_element->custom_form_block_id = $new_block->id;
                $new_element->save();
            });
        });
        $now = now();
        $form->users->each(fn($user) => $new_form->users()->attach($user->id, ['authority' => 0, 'created_at' => $now, 'updated_at' => $now]));

        $form->admins->each(fn($admin) => $new_form->admins()->attach($admin->id, ['authority' => 1, 'created_at' => $now, 'updated_at' => $now]));
        return response()->json(['message' => 'Form duplicated successfully'], 200);
    }
    public function get_custom_forms(Request $request){
        $active_user = $this->active_user();

        $forms = CustomForm::where(function($q) {
            $q->whereNull('board_record_id')->orWhere('board_record_id', 3758);
        })->with(['blocks'])->orderBy('created_at', 'desc')
        ->when($active_user->position_id <= 6 && ($active_user->id !== 610 && $active_user->id !== 608), function($q) use($active_user){
            $q->whereHas('admins', function($q) use($active_user){
                $q->where('user_id', $active_user->id);
            });
        })
        ->with(
        [
            'users', 
            'admins', 
            'survey_answers'
            ]
        )->get();

        $forms->map(function($form){
            $form->users->map(function($user) use($form){
                $user['is_answered'] = $form->survey_answers->where('user_id', $user->id)->count() > 0;
            });
        });

        
        return response()->json($forms);

        
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
            'blocks.*.id' => 'nullable|integer',
            'blocks.*.type' => 'required|string|max:50',
            'blocks.*.question' => 'required|string',
            'blocks.*.is_required' => 'boolean',
            'blocks.*.order_number' => 'required|integer',
            'blocks.*.placeholder' => 'nullable|string',
            'blocks.*.elements' => 'array',
            'blocks.*.elements.*.id' => 'nullable|integer',
            'blocks.*.elements.*.value' => 'required|string',
            'blocks.*.elements.*.is_required' => 'boolean',
            'blocks.*.elements.*.has_sub_text_required' => 'boolean',
            'blocks.*.elements.*.has_sub_text' => 'boolean',
            'blocks.*.elements.*.placeholder' => 'nullable|string',
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

        $form = CustomForm::updateOrCreate(
            ['id' => $this->sanitizeId(Arr::get($data, 'id'))],
            [
                'title' => Arr::get($data, 'title'),
                'description' => Arr::get($data, 'description'),
                'repeat_setting' => Arr::get($data, 'repeat_setting' ),
                'repeat_day' => Arr::get($data, 'repeat_day' ),
                'board_record_id' => Arr::get($data, 'board_record_id', null),
                'has_prize' => Arr::get($data, 'has_prize', false),
            ]
        );
        $users = Arr::get($data, 'users', []);
        $user_ids = collect($users)->map(fn($user) => $user['id']);

        $admins = Arr::get($data, 'admins', []);
        $admin_ids = collect($admins)->map(fn($admin) => $admin['id']);
        $now = now();
        $form->users()->syncWithPivotValues($user_ids, ['authority' => 0, 'created_at' => $now, 'updated_at' => $now]);
        $form->admins()->syncWithPivotValues($admin_ids, ['authority' => 1, 'created_at' => $now, 'updated_at' => $now]);
        return $form;
    }
    public function delete_custom_form(Request $request){
        $request->validate([
            'id' => 'required'
        ]);

        $form = CustomForm::findOrFail($request->id);
        $form->blocks->each(function($block){
            $block->elements()->delete();
        });
        $form->blocks()->delete();
        $form->delete();
        return response('Form deleted successfully', 200);
    }
    private function saveBlocks($form, array $blocks)
    {
        $block_ids = [];
        foreach ($blocks as $block) {
            $blockModel = $form->blocks()->updateOrCreate(
                ['id' => $this->sanitizeId(Arr::get($block, 'id'))],
                [
                    'type' => Arr::get($block, 'type'),
                    'question' => Arr::get($block, 'question'),
                    'is_required' => Arr::get($block, 'is_required', false),
                    'order_number' => Arr::get($block, 'order_number'),
                    'placeholder' => Arr::get($block, 'placeholder'),
                ]
            );
            $block_ids[] = $blockModel->id;
            $element_ids = $this->saveElements($blockModel, Arr::get($block, 'elements', []));
            $blockModel->elements()->whereNotIn('id', $element_ids)->delete();
        }
        return $block_ids;
    }
    
    /**
     * Save the elements for a block.
     */
    private function saveElements($block, array $elements)
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
                    'placeholder' => Arr::get($element, 'placeholder'),
                ]
            );
            $element_ids[] = $el_record->id;
        }
        return $element_ids;
    }
    private function sanitizeId($id)
    {
        return (is_numeric($id) && $id > 0) ? $id : null;
    }
    public function save_survey_answer(Request $request){

        $active_user = $this->active_user();
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
            $block_answer->files()->sync($block['files']);
            $elements = $block['element_answers'];
            foreach($elements as $element){
                $block_answer->element_answers()->create([
                    "user_id" => $active_user->id,
                    "custom_form_block_element_id" => $element['custom_form_block_element_id'],
                    "sub_text" => $element['sub_text'] ?? null,
                    "checked" => $element['checked']
                ]);
            }
        }

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
    public function get_survey_answers(Request $request){

        $repeat = $request->repeat_setting;
        $year = $request->year;
        $month = $request->month;
        $target_date = Carbon::create($year, $month, 1)->startOfMonth();

        $survey = SurveyAnswer::where('custom_form_id', $request->custom_form_id)->where('status', 2)
        ->when($repeat == 1, function($q) use($target_date){
            $q->where('target_date', $target_date);
        })
        ->get();
        $custom_form = CustomForm::with(['blocks' => function($q) use($repeat, $target_date) {
            $q->with(['answers' => function($q) use($repeat, $target_date) {
                $q->whereHas('survey_answer', function($q) use($repeat, $target_date){
                    $q->where('status', 2)->when($repeat == 1, function($q) use($target_date){
                        $q->where('target_date', $target_date);
                    });
                })->with(['user', 'files'])->orderBy('created_at', 'desc');                    
            }])
            ->with(['elements' => function($q)  {
                $q->with(['answers' => function($q)  {
                    $q->whereHas('survey_block_answer', function($q){
                        $q->whereHas('survey_answer', function($q){
                            $q->where('status', 2);
                        });
                    })->with('user')->orderBy('created_at', 'desc'); ;                    
                }]);
            }]);
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
                $data['user'] = $user;
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
                        ->select('id', 'name', 'icon_path', 'icon_bg')
                        ->get();
        return response()->json($user_list);
    }
    public function get_my_surveys(Request $request) {
        $active_user = $this->active_user();
        $surveys = CustomForm::whereHas('users', function($query) use($active_user) {
            $query->where('users.id', $active_user->id);
        })->orWhereHas('survey_answers', function($query) use($active_user) {
            $query->where('user_id', $active_user->id);
        })
        ->with(['blocks' => function($q) use($active_user)  {
            $q->with(['answers' => function($q)use($active_user)  {
                $q->where('user_id', $active_user->id)->with('files');                    
            }])->with(['elements' => function($q) use($active_user) {
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
        }])->orderBy('created_at', 'desc')->get();

        return response()->json($surveys);
    }
    public function get_board_forms(Request $request){
        $active_user = $this->active_user();
        $request->validate([
            'board_id' => 'required|integer',
        ]);
        $forms = CustomForm::where('board_record_id', $request->board_id)
            ->with(['blocks'])
            ->when($active_user->position_id <= 6 && ($active_user->id !== 610 && $active_user->id !== 608), function($q) use($active_user){
                $q->whereHas('admins', function($q) use($active_user){
                    $q->where('user_id', $active_user->id);
                });
            })
            ->with(['users', 'admins', 'survey_answers' => function($q){
                $q->with(['user' => function($q){
                    $q->select('id', 'name', 'icon_path', 'icon_bg');
                }, 'block_answers' => function($q){
                    $q->with(['element_answers' => function($q){
                        $q->with('user');
                    }])->with('files');
                },]);
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
        $active_user = $this->active_user();
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
