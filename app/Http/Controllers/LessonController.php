<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LessonMaterial;
use App\Models\LessonPortfolio;
use App\Models\LessonTheme;
use App\Models\User;
use App\Models\LessonForm;
use App\Models\LessonSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
class LessonController extends Controller
{
    public function get_lessons(Request $request){
        
        $lessons = LessonMaterial::where('lesson_theme_id', $request->lesson_theme_id)->get();

     
        return response()->json($lessons);
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
        $themes_portfolio = LessonTheme::with(['lesson_portfolio' => function ($q){
            $q->where('user_id', Auth::id());
        }])->get();
        return response()->json($themes_portfolio);
    }
    public function delete_learning_theme(Request $request){
        if($request->id){
            $lesson = LessonTheme::findOrFail($request->id)->delete();
            return response()->json($lesson);
        }
    }
    public function create_learning_theme(Request $request){
        if($request->edit_id){
            $theme = LessonTheme::findOrFail($request->edit_id)->update([
                "title" => $request->title,
                "discussion_date" => $request->discussion_date,
                "active" => $request->active
            ]);
        }
        else{
            $theme = LessonTheme::create([
                "title" => $request->title,
                "discussion_date" => $request->discussion_date,
                "active" => $request->active
            ]);
        }
        return response()->json($theme);
    }
    public function lesson_add_record(Request $request){
        if($request->edit_id){
            $lesson = LessonMaterial::findOrFail($request->edit_id)->update([
                "content" => $request->content,
                "content_detailed" => $request->content_detailed,
                "title" => $request->title,
                "has_feedback" => $request->has_feedback,
                "lesson_theme_id" => $request->lesson_theme_id,
                "updated_by" => Auth::id(),
                "priority" => $request->priority
            ]);
        }
        else{
            $lesson = LessonMaterial::create([
                "content" => $request->content,
                "content_detailed" => $request->content_detailed,
                "title" => $request->title,
                "has_feedback" => $request->has_feedback,
                "lesson_theme_id" => $request->lesson_theme_id,
                "updated_by" => Auth::id(),
                "user_id" => Auth::id(),
                "priority" => $request->priority

            ]);
        }
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
            "content" => $request->content,
        ]);         
        return response()->json();
    }

    public function save_lesson_portfolio(Request $request){
      
            // $lessonPortfolio = LessonPortfolio::findOrFail($request->portfolio_id);
        $validatedData = $request->validate([
            'theme_id' => 'required',
        ]);
        $lessonPortfolio = $this->check_portfolio($request->theme_id, Auth::id());

        $lessonPortfolio->update([
            "content" => $request->content ?? $lessonPortfolio->content,
            "positive_feedback" => $request->p_feedback ?? $lessonPortfolio->positive_feedback,
            "negative_feedback" => $request->n_feedback ?? $lessonPortfolio->negative_feedback, 
            "status" => $request->status ?? $lessonPortfolio->status,
            "understand" => $request->understand ?? $lessonPortfolio->understand,
            "portfolio_title" => $request->portfolio_title ?? $lessonPortfolio->portfolio_title,
            "noticed" => $request->noticed ?? $lessonPortfolio->noticed
        ]);
            
      
        
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
        $lesson_portfolio = LessonPortfolio::where('lesson_theme_id', $request->theme_id)->with('user')->get();

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
            "content" => $request->content,
        ]);

        return response()->json($lesson_form);
    }
    public function upload_lesson_file(Request $request){

        $file = $request['file'];
        $uniqueID = uniqid();
        $path = '/lesson_files';
        if($request['type'] == 'imagePicker'){
            $ext = 'webp';
            $img = Image::make($file)->orientate();
            
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);      
            $thumbnail = $img->encode('webp');  
            
            $thumbnail->save(storage_path('app') . $path .'/'. $uniqueID . '.' . $ext , 100);
            $url = '/lesson_files/' . $uniqueID . '.' . $ext;
            return response()->json($url);
        }else if($request['type'] == 'videoPicker'){
            $ext = $file->getClientOriginalExtension();
            
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);      
            Storage::disk('local')->putFileAs(
                $path, $file, $uniqueID . '.' . $ext
            );
            $url = '/lesson_files/' . $uniqueID . '.' . $ext;
            return response()->json($url);
        }
        
    }
}
