<?php

namespace App\Http\Controllers;

use App\Models\taskRecord;
use App\Models\taskUser;
use App\Models\boardRecord;
use App\Models\messageRecord;
use App\Models\boardToUser;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
class TaskController extends Controller
{
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function getTask(Request $request){

        
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        if(!empty($request) && !empty($auth_user_id)){      
            if($request->flag == 0){
                $list1 = taskRecord::where('board_id', $request->record_id)
                ->whereNotNull('end_at')
                ->with('to_users')
                ->with('files')
                ->with('approve_user')
                ->orderBy('created_at', 'desc')->get();

                $list2 = taskRecord::where('board_id', $request->record_id)
                ->whereNull('end_at')
                ->with('to_users')
                ->with('files')
                ->with('approve_user')
                ->when($request->which == 1, function($q){
                    $q->onlyTrashed();                    
                })
                ->orderBy('created_at', 'desc')->get();
                $list = $list1->concat($list2);
                $order = $request->which == 1 ? 'updated_at' : 'created_at';
                $list3 = $list->sortByDesc($order)->values();
                return response()->json($list3);
            }     
            
                  
        
        }else{
            return response()->json('error');
        }
        
    } 
    public function completeTask(Request $request){
        
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        
        if(!empty($request) && !empty($auth_user_id)){            
            $list = taskUser::where('record_id', $request->task_id)->where('user_id', $auth_user_id)->first();
            $list->comp_flag = $request->comp_flag;
            if($request->late_answer){
                $list->late_answer = $request->late_answer;
            }
            if($request->late_answer_custom){
                $list->late_answer_custom = $request->late_answer_custom;
            }
            $list->save();
            
            $allCount = taskUser::where('record_id', $request->task_id)->count();
            if($allCount > 0){
                $completedCount = taskUser::where('record_id', '=', $request->task_id)->where('comp_flag', '=', 1)->count();
                $task = taskRecord::find($request->task_id);
                if($allCount == $completedCount){
                    $task->comp_flag = 1;
                }else{
                    $task->comp_flag = 0;
                }
                $task->save();
            }
                    
            return response()->json('saved');     
            
        }
        return response()->json('loggedOut');  
        
    } 
    public function updateTask(Request $request){
        $active_user = $this->active_user();
        $task = taskRecord::findOrFail($request->task_id);
        $task->update(['end_at' => $request->date, 'updated_user' => $active_user->id]);
        return response()->json($request);
    }
    private function path_generator(){
        $timestamp = time();
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $iconId = $timestamp . $randomString;
        if (strlen($iconId) > 15) {
            $iconId = substr($iconId, 0, 15);
        }    
        return $iconId;
    }
    public function task_file_upload(Request $request) {
        $path = $request->path;
        $fileContent = $request->file('file');
        $file_path = $this->path_generator();           
        $file_extension = $fileContent->getClientOriginalExtension();
        $file_real_name = $fileContent->getClientOriginalName();  
        $mime_type = $fileContent->getMimeType();
        $mime_type_array = explode('/',$mime_type);
        $file_type = $mime_type_array[0];           
    
        
        if($file_type == 'image' && $file_extension !== 'svg'){
            $img = Image::read($fileContent);
            
            $img->scale(640);
            $file_path .= '.webp';
            File::isDirectory(storage_path('app/') . $path) or File::makeDirectory(storage_path('app/') . $path, 0755, true, true);                      
            $img->toWebp(80)->save(storage_path('app/') . $path .'/'. $file_path);  
        } else {
            $file_path = $file_path . '.' . $file_extension;
            Storage::disk('local')->putFileAs(
                $path, $fileContent, $file_path
            );
        }
        $data = [
            "file_type" => $file_type,
            "file_path" => $file_path,
            "file_extension" => $file_extension,
            "file_name" => $file_real_name,
        ];
        return response()->json($data);
    }
    public function get_task_badge(Request $request){
        $active_user = $this->active_user();
        $result = [];
        
        $allBoard = boardRecord::whereHas('board_to_users', function($q) use($active_user){
            $q->where('user_id', $active_user->id)->where('deleted_status','=', 0);
        })->pluck('id')->toArray();
        if(!empty($allBoard)){
            foreach($allBoard as $id){
                $allTasks = taskRecord::where('comp_flag', '=', 0)->whereNotNull('end_at')->where('board_id', '=', $id)->whereHas('task_users', function($q) use($active_user){
                    $q->where('user_id', $active_user->id)->where('comp_flag', '=', 0);
                })->count();
                $result[$id] = $allTasks;
            }
            
        }
        return response()->json($result);
    }

    public function taskEdit(Request $request){
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        if($auth_user_id){
            $task = taskRecord::find($request->task_id);
            // #20201202_0013 Tumur　通知機能追加
            if($task){
                $task_info_edit_trigger = 0;
                $end_time = '00:00:00';
                if($request->task_end_time){
                    $end_time = $request->task_end_time;
                }
                $combinedDT = date('Y-m-d H:i:s', strtotime("$request->task_end_date $end_time"));
                $minutes = intval(date('i', strtotime($combinedDT))); // Get the minutes from the combined datetime

                if ($minutes > 30) {
                    // Increment the hour by 1
                    $combinedDT = date('Y-m-d H:00:00', strtotime($combinedDT . '+1 hour'));
                } else {
                    // Set the minutes to 0
                    $combinedDT = date('Y-m-d H:00:00', strtotime($combinedDT));
                }

                if($task->task_end !== $combinedDT){
                    $task_info_edit_trigger = 1;
                }
                $task->updated_user = $auth_user_id;
                $task->end_at = $combinedDT;
          
                $task->remarks = $request->remarks;
                $task->title = $request->title;
                // $task->color = $request->color;
                $task->save();
                
                if(!empty($request->qualified_users)){

                    $qualified_users = $request->qualified_users;
                    $old_users = taskUser::where('record_id', '=', $request->task_id)->get();
                    foreach($old_users as $old_user){
                        $old_user->delete();
                        $old_user->save();
                    }                   
                    foreach($qualified_users as $qualified_user){
                        $exist_user = taskUser::where('record_id', '=', $request->task_id)->where('user_id', '=', $qualified_user)->first();
                        if($exist_user){
                            $exist_user->delete();
                            $exist_user->save();
                            
                        }else{
                            $new_user = new taskUser;
                            $new_user->record_id = $request->task_id;
                            $new_user->user_id = $qualified_user;
                            $new_user->save();
                           
                            
                        }
                        
                    }    
                    
                }                
                return response()->json($task);
            }
            
        }
    }
    public function taskDelete(Request $request){
        $task = taskRecord::find($request->task_id);
        if($task){
            $task->delete();
            boardRecord::findOrFail($task->board_id);
            return response()->json($task);
        }
    }

    public function addTask(Request $request){
        $active_user = $request->override_user ? $request->override_user : $this->active_user();
        $request->validate([
            'qualified_users' => 'required',
            'board_id' => 'required',
        ]);
        if(!$request->edit_id){
            $infoMessage = new messageRecord;
            $infoMessage->user_id = $active_user->id;
            $infoMessage->info_flag = 2;
            $infoMessage->record_id = $request->board_id;
            $infoMessage->message = '新しいタスクが追加されました。';
            $infoMessage->message_text = '新しいタスクが追加されました。';
            $infoMessage->save();
        }

        $end_time = '23:59:59';
        
        if($request->edit_id){
            $task = taskRecord::findOrFail($request->edit_id);
        }else{
            $task = new taskRecord;
        }
        

        $task->user_id = $active_user->id;
        $task->updated_user = $active_user->id;
        if($request->task_end_date){
            $combinedDT = date('Y-m-d H:i:s', strtotime("$request->task_end_date $end_time"));
            $minutes = intval(date('i', strtotime($combinedDT))); // Get the minutes from the combined datetime

            if ($minutes > 30) {
                $combinedDT = date('Y-m-d H:00:00', strtotime($combinedDT . '+1 hour'));
            } else {
                $combinedDT = date('Y-m-d H:00:00', strtotime($combinedDT));
            }
            $task->end_at = $combinedDT;
        }else{
            $task->end_at = null;
        }
        
        if(!$request->edit_id){
            $task->message_id = $infoMessage->id;
        }
        $task->approver_id = $request->approver;
        $task->remarks = $request->remarks;
        $task->board_id = $request->board_id;

        $task->save();
       

        $task->to_users()->syncWithPivotValues($request->qualified_users, ['updated_at' => now()]);
        $related_members = boardToUser::where('record_id','=', $request->board_id)->where('deleted_status', '=', 0)->where('user_id', '!=', $active_user->id)->pluck('user_id');
        if(!$request->edit_id){
            boardToUser::where('record_id','=', $request->board_id)->where('user_id', '=', $active_user->id)->update(["last_message" => $infoMessage->id]);
        }
        $rebound = array(
            "type" => "new_message",
            "board_members" => $related_members,
            "board_id" => $request->record_id,
            "sender" => $active_user->id
        );                      
        event(new MessageSent($rebound)); 
                      
        return response()->json($task->id);

    }

    public function task_approve_request(Request $request){
        $request->validate([
            'task_id' => 'required',
        ]);
        $active_user = $this->active_user();
        
        
        $taskUser = taskUser::where('record_id', $request->task_id)
                    ->where('user_id', $active_user->id)
                    ->first();
        if ($taskUser) {
            $taskUser->update([
                'comment' => $request->comment,
                'status_flag' => $request->status_flag,
                'late_answer' => $request->late_answer,
                'late_answer_custom' => $request->late_answer_custom
            ]);
            taskRecord::findOrFail($request->task_id)->files()->syncWithoutDetaching($request->file_ids);
        }

        return response()->json($taskUser);
    }

    public function task_approve(Request $request){
        $request->validate([
            'user_id' => 'required',
            'task_id' => 'required',
        ]);
        $task_user = taskUser::where('record_id', $request->task_id)
                              ->where('user_id', $request->user_id)
                              ->update([
                                'status_flag' => $request->status_flag,
                                'comp_flag' => $request->comp_flag,
                              ]);
        return response()->json($task_user);
    }

    public function task_not_approved(){
        $active_user = $this->active_user();
        $tasks = taskRecord::where('approver_id', $active_user->id)
                            ->where('comp_flag', 0)
                            ->whereHas('task_users', function ($q) {
                                $q->where('status_flag', 1);
                            })
                            ->with(['task_users' => function ($q) {
                                $q->where('status_flag', 1);
                            }])
                            ->get();
        return response()->json($tasks);
    }
}
