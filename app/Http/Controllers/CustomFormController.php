<?php

namespace App\Http\Controllers;

use App\Models\CustomForm;
use App\Models\SurveyAnswer;
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
                    $q->where('user_id', $active_user->id);                    
                }])->with(['elements' => function($q) use($active_user) {
                    $q->with(['answers' => function($q)use($active_user)  {
                        $q->where('user_id', $active_user->id);  
                    }]);
                }]);
            }])
            ->with(['survey_answers' => function($q) use($active_user) {
                $q->where('user_id', $active_user->id);  
            }])
            ->findOrFail($request->id);
            return response()->json($survey);

    }
    public function get_custom_forms(Request $request){

        $forms = CustomForm::with(['blocks'])->get();
        return response()->json($forms);

        
    }
    public function save_custom_form (Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'blocks' => 'array',
            'blocks.*.id' => 'nullable|integer',
            'blocks.*.type' => 'required|string|max:50',
            'blocks.*.question' => 'required|string',
            'blocks.*.is_required' => 'boolean',
            'blocks.*.order_number' => 'required|integer',
            'blocks.*.elements' => 'array',
            'blocks.*.elements.*.id' => 'nullable|integer',
            'blocks.*.elements.*.value' => 'required|string',
            'blocks.*.elements.*.is_required' => 'boolean',
            'blocks.*.elements.*.has_sub_text_required' => 'boolean',
            'blocks.*.elements.*.has_sub_text' => 'boolean',
        ]);
    
        $form = $this->saveForm( $validated);

        $this->saveBlocks($form, Arr::get($validated, 'blocks', []));
    
        return response()->json(['message' => 'Form saved successfully'], 200);
    }
    private function saveForm( array $data)
    {
        return CustomForm::updateOrCreate(
            ['id' => $this->sanitizeId(Arr::get($data, 'id'))],
            [
                'title' => Arr::get($data, 'title'),
                'description' => Arr::get($data, 'description'),
            ]
        );
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

    }
    private function saveBlocks($form, array $blocks)
    {
        foreach ($blocks as $block) {
            $blockModel = $form->blocks()->updateOrCreate(
                ['id' => $this->sanitizeId(Arr::get($block, 'id'))],
                [
                    'type' => Arr::get($block, 'type'),
                    'question' => Arr::get($block, 'question'),
                    'is_required' => Arr::get($block, 'is_required', false),
                    'order_number' => Arr::get($block, 'order_number'),
                ]
            );
    
            $this->saveElements($blockModel, Arr::get($block, 'elements', []));
        }
    }
    
    /**
     * Save the elements for a block.
     */
    private function saveElements($block, array $elements)
    {
        foreach ($elements as $element) {
            $block->elements()->updateOrCreate(
                ['id' => $this->sanitizeId(Arr::get($element, 'id'))],
                [
                    'value' => Arr::get($element, 'value'),
                    'is_required' => Arr::get($element, 'is_required', false),
                    'has_sub_text_required' => Arr::get($element, 'has_sub_text_required', false),
                    'has_sub_text' => Arr::get($element, 'has_sub_text'),
                ]
            );
        }
    }
    private function sanitizeId($id)
    {
        return (is_numeric($id) && $id > 0) ? $id : null;
    }
    public function save_survey_answer(Request $request){

        $active_user = $this->active_user();
        $survey = SurveyAnswer::firstOrCreate([
            "custom_form_id" => $request->custom_form_id,
            "user_id" => $active_user->id
        ]);
        $survey->block_answers->each(function($block_answer){
            $block_answer->element_answers()->delete();
        });
        $survey->block_answers()->delete();

        $params = $request->params;
        foreach($params as $block){
            $block_answer = $survey->block_answers()->create([
                "user_id" => $active_user->id,
                "text_answer" => $block['text_answer'],
                "custom_form_block_id" => $block['custom_form_block_id']
            ]);
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
            
        

    }
    public function get_survey_answers(Request $request){

            $survey = SurveyAnswer::where('custom_form_id', $request->custom_form_id)->get();
            $custom_form = CustomForm::with(['blocks' => function($q)  {
                $q->with(['answers' => function($q) {
                    $q->with('user');                    
                }])
                ->with(['elements' => function($q)  {
                    $q->with(['answers' => function($q)  {
                        $q->with('user');                    
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
                    $data['created_at'] = $s->created_at;
                    $blocks = $custom_form->blocks;
                    foreach($blocks as $block){
                        $anwsers = [];
                        $block_answer = $s->block_answers->where('custom_form_block_id', $block->id)->first();
                        if(!empty($block_answer)){                      
                            
                            if(in_array($block->type, $simpleTypes)){
                                $anwsers[] = ['value' => $block_answer->text_answer];
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
                                'answers' => $anwsers
                            ];
                            $data['data'][] = $q;
                        }
                    }

                    $main[] = $data;
                }
                return response()->json($main);
            }
        
    }
}
