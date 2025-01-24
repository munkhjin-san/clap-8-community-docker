<?php

namespace App\Http\Controllers;

use App\Models\CustomForm;
use App\Models\LessonAnswer;
use App\Models\LessonSummary;
use App\Models\LessonSummaryAnswer;
use App\Models\LessonSummaryQuestion;
use Illuminate\Http\Request;
use App\Models\LessonMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonTheme;
use App\Models\LessonForm;
use App\Models\LessonSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class LessonController extends Controller
{
    public function get_lessons(Request $request){
        
        $lessons = LessonMaterial::where('lesson_theme_id', $request->lesson_theme_id)
                                ->with(['answer' => function ($q) {
                                    $q->where('user_id', Auth::id());
                                }])->with(['summaries' => function ($q) {
                                    $q->with([
                                        'questions.answer' => function ($q) {
                                            $q->where('user_id', Auth::id());
                                        },
                                        'answers' => function ($q) {
                                            $q->where('user_id', Auth::id());
                                        }
                                    ]);
                                }])
                                ->get();

     
        return response()->json($lessons);
    }
    public function get_material(Request $request){
        $lesson = LessonMaterial::where('id', $request->id)
        ->with(['answer' => function ($q) {
            $q->where('user_id', Auth::id());
        }])->with(['summaries' => function ($q) {
            $q->with([
                'questions.answer' => function ($q) {
                    $q->where('user_id', Auth::id());
                },
                'answers' => function ($q) {
                    $q->where('user_id', Auth::id());
                }
            ]);
        }])
        ->first();
        return response()->json($lesson);
    }
    public function lesson_remove_record(Request $request){
        if($request->id){
            $lesson = LessonMaterial::findOrFail($request->id)->delete();
            return response()->json($lesson);
        }
    }
    public function get_learning_themes(){
        $themes = LessonTheme::get();
        return response()->json($themes);
    }
    public function get_lesson_themes(){
        $themes_portfolio = LessonTheme::with([
            'lesson_portfolio' => function ($q){
                $q->where('user_id', Auth::id());
            }, 
            'materials' => function ($q) {
                $q->with(['answer' => function ($q) {
                    $q->where('user_id', Auth::id());
                }]);
            }
        ])->get();
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
        $theme = LessonTheme::updateOrCreate(['id' => $id], $params);
        return response()->json($theme);
    }
    public function lesson_add_record(Request $request){
        $id = $request->id ?? null;
        $params = $request->params;
        $userId = auth()->id();
        if ($id) {
            $params['updated_by'] = $userId;
        } else {
            $params['user_id'] = $userId;
        }
        $lesson = LessonMaterial::updateOrCreate(['id' => $id], $params);
        
        return response()->json($lesson);
    }
    public function check_portfolio($theme_id, $user_id){
        $lessonPortfolio = LessonPortfolio::where('lesson_theme_id', $theme_id)->where('user_id', $user_id)->first();
        if(empty($lessonPortfolio)){
            $newLessonPortfolio = LessonPortfolio::create([
                "lesson_theme_id" => $theme_id,
                "user_id" => Auth::id(),
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
        return response()->json();
    }

    public function save_lesson_portfolio(Request $request){      
    
        $request->validate([
            'theme_id' => 'required',
        ]);
        $lessonPortfolio = $this->check_portfolio($request->theme_id, Auth::id());

        $lessonPortfolio->update($request->params);
            
      
        
        return response()->json($lessonPortfolio);
    }
    public function update_lesson_portfolio(Request $request){
      
        $request->validate([
            'id' => 'required',
        ]);
        $lessonPortfolio = LessonPortfolio::findOrFail($request->id)->update($request->params);       
        return response()->json($lessonPortfolio);
    }
    public function get_lesson_portfolio(Request $request){
        $lesson_portfolio = LessonPortfolio::where('lesson_theme_id', $request->lesson_theme_id)
        ->where('user_id', Auth::id())
        ->with('lesson_sections')
        ->first();
        return response()->json($lesson_portfolio);
    }
    public function get_portfolios_list(Request $request){
        $theme_id = $request->theme_id;
        $lesson_portfolio = LessonPortfolio::where('lesson_theme_id', $theme_id)
        ->with('user')
        ->with('lesson_sections')
        ->with(['lesson_form' => function($q) use($theme_id){
            $q->where('lesson_theme_id', $theme_id);
        }])
        ->get();

        return response()->json($lesson_portfolio);
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
        $uniqueID = uniqid();
        $path = '/lesson_files';
        $mime = $file->getMimeType();
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        if (strpos($mime, 'image') !== false) {
            $ext = 'webp';
            $img = Image::read($file);
            
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);      
            $thumbnail = $img->toWebp();  
            
            $thumbnail->save(storage_path('app') . $path .'/' . $fileName . '_' . $uniqueID . '.' . $ext);
            $url = '/lesson_files/' . $fileName . '_' . $uniqueID . '.' . $ext;
            return response()->json($url);
        }elseif (strpos($mime, 'video') !== false) {
            $ext = $file->getClientOriginalExtension();
            
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);      
            Storage::disk('local')->putFileAs(
                $path, $file, $fileName . '_'  . $uniqueID . '.' . $ext
            );
            $url = '/lesson_files/' . $fileName . '_'. $uniqueID . '.' . $ext;
            return response()->json($url);
        }else{
            throw ValidationException::withMessages(['message' => '動画・画像のみアップロード可能です。']);          
        }
        
    }
    public function get_lesson_files(Request $request){
        $files = Storage::allFiles('/lesson_files');
        usort($files, function ($a, $b) {
            return Storage::lastModified($b) - Storage::lastModified($a);
        });
        return response()->json($files);
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
        $id = $request->id ?? null;
        $params = $request->params;
        $params['user_id'] = auth()->id();
        $lesson_answer = LessonAnswer::updateOrCreate(['id' => $id], $params);
        return response()->json($lesson_answer);
    }
    public function get_material_list(Request $request) {
        $lessons = LessonMaterial::where('lesson_theme_id', $request->lesson_theme_id)
                    ->whereHas('answers', function ($q) {
                        $q->whereHas('user');
                    })
                    ->with(['answers.user'])
                    ->get();

        $usersProgress = []; 

        foreach ($lessons as $lesson) {
            $type = $lesson->material_type;
            $answers = $lesson->answers;
            foreach ($answers as $answer) {
                $userId = $answer->user->id;
                if (!isset($usersProgress[$userId])) {
                    $usersProgress[$userId] = [
                        'user' => $answer->user,
                        'basic_knowledge_statuses' => [],
                        'case_study_statuses' => [],
                        'answers' => [],
                        'cant_understand' => '',
                        'reason_dnt_und' => ''
                    ];
                }
                if ($type === '基礎知識') {
                    $usersProgress[$userId]['basic_knowledge_statuses'][] = $answer->status;
                    $usersProgress[$userId]['cant_understand'] = $answer->cant_understand;
                    $usersProgress[$userId]['reason_dnt_und'] = $answer->reason_dnt_und;
                } elseif ($type === 'ケーススタディ') {
                    $case_answers = [
                        'title' => $lesson->title,
                        'answer' => $answer->answer
                    ];
                    $usersProgress[$userId]['case_study_statuses'][] = $answer->status;
                    $usersProgress[$userId]['answers'][] = $case_answers;
                }
            }
            
        }
        foreach ($usersProgress as $userId => &$progress) {
            $progress['basic_knowledge_completed'] = !empty($progress['basic_knowledge_statuses']) &&
                collect($progress['basic_knowledge_statuses'])->every(function ($status) {
                    return $status == 2;
                });
            $progress['case_study_completed'] = !empty($progress['case_study_statuses']) &&
                collect($progress['case_study_statuses'])->every(function ($status) {
                    return $status == 2;
                });
            $progress['basic_knowledge_uncompleted'] = !empty($progress['basic_knowledge_statuses']) &&
                collect($progress['basic_knowledge_statuses'])->some(function ($status) {
                    return $status == -1;
                });
            $progress['completed'] = $progress['basic_knowledge_completed'] && $progress['case_study_completed'];
        }
                                
        return response()->json($usersProgress);
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
}
