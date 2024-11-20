<?php

namespace App\Http\Controllers;

use App\Models\CalendarRecord;
use App\Models\taskRecord;
use App\Models\taskUser;
use App\Models\TaskRepeat;
use App\Models\boardRecord;
use App\Services\SharedService;
use App\Jobs\TaskCreated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use DB;
class TaskController extends Controller
{
    protected $sharedService;
    public function __construct(SharedService $sharedService) {
        $this->sharedService = $sharedService;
    }
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
        $which = $request->which;
        if(!empty($request) && !empty($auth_user_id)){      
            $list1 = taskRecord::where('board_id', '=', $request->record_id)
            ->whereNotNull('end_at')
            ->where(function ($query) use ($auth_user_id, $which) {
                $query->whereHas('executors', function($q) use ($auth_user_id, $which) {
                    $q->where('comp_flag', $which)
                        ->where('user_id', $auth_user_id);
                })->orWhere(function ($query) use ($auth_user_id, $which) {
                    $query->whereDoesntHave('executors', function($q) use ($auth_user_id) {
                        $q->where('user_id', $auth_user_id);
                    })->where('comp_flag', $which);
                })->orWhereHas('supervisors', function ($q) use ($auth_user_id, $which) {
                    $q->where('user_id', $auth_user_id)
                        ->where('comp_flag', $which);
                });
            })
            ->with('executors')
            ->with('files')
            ->with('supervisors')
            ->orderBy('created_at', 'desc')->get();

            $list2 = taskRecord::where('board_id', '=', $request->record_id)
            ->whereNull('end_at')
            ->with('executors')
            ->with('files')
            ->with('supervisors')
            ->when($request->which == 1, function($q){
                $q->onlyTrashed();                    
            })
            ->orderBy('created_at', 'desc')->get();
            $list = $list1->concat($list2);
            $order = $request->which == 1 ? 'updated_at' : 'created_at';
            $list3 = $list->sortByDesc($order)->values();
            return response()->json($list3);  
                
            
                  
        
        }else{
            return response()->json('error');
        }
        
    } 
    public function completeTask(Request $request){
        
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        
        if(!empty($request) && !empty($auth_user_id)){   
            $taskUser = taskUser::where('record_id', $request->task_id)
                                ->whereHas('user', function ($q) {
                                    $q->where('retire', 0);
                                })
                                ->get();         
            $list = $taskUser->where('user_id', $auth_user_id)->first();
            $list->comp_flag = $request->comp_flag;
            $list->status_flag = $request->status_flag ?? 0;
            if($request->late_answer){
                $list->late_answer = $request->late_answer;
            }
            if($request->late_answer_custom){
                $list->late_answer_custom = $request->late_answer_custom;
            }
            $list->save();
            
            $allCount = $taskUser->where('supervisor', 0)->count();
            if($allCount > 0){
                $completedCount = $taskUser->where('comp_flag', 1)->count();
                $task = taskRecord::find($request->task_id);
                $allCount == $completedCount ? $task->comp_flag = 1 : $task->comp_flag = 0;
                if($allCount == $completedCount){
                    $this->sharedService->deleteTaskFromCalendar($task);
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
        $newStartDate = Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay();
        $newEndDate = Carbon::createFromFormat('Y-m-d', $request->date)->endOfDay();
        $calendar = CalendarRecord::where('task', $request->task_id)->first();
        if($calendar){
            $calendar->date_start = $newStartDate;
            $calendar->date_end = $newEndDate;
            $calendar->save();
        }
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
        $allBoard = boardRecord::whereHas('board_to_users', function($q) use($active_user){
            $q->where('user_id', $active_user->id)->where('deleted_status','=', 0);
        })->pluck('id')->toArray();
        $taskCounts = taskRecord::where('comp_flag', 0)
        ->whereNotNull('end_at')
        ->whereIn('board_id', $allBoard)
        ->whereHas('executors', function($q) use($active_user) {
            $q->where('users.id', $active_user->id)->where('comp_flag', 0);
        })
        ->select('board_id', DB::raw('count(*) as total_task_number'))
        ->groupBy('board_id')
        ->get()
        ->pluck('total_task_number', 'board_id')
        ->toArray();
        return response()->json($taskCounts);
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
                $minutes > 30 ? $combinedDT = date('Y-m-d H:00:00', strtotime("{$combinedDT}+1 hour")) : $combinedDT = date('Y-m-d H:00:00', strtotime($combinedDT));
                

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
        if($request->all_delete){
            $tasks = taskRecord::where('repeat_id', $task->repeat_id)->get();
            foreach($tasks as $task){
                if($task->sync_to_schedule){
                    $this->sharedService->deleteTaskFromCalendar($task);
                }
                $task->delete();
            }
        } else {
            if($task->sync_to_schedule){
                $this->sharedService->deleteTaskFromCalendar($task);
            }
            $task->delete();
        }
        $socket = [];
        
        array_push($socket, ["event" => "task:{$task->board_id}", "data" => []]);
       
        return response()->json(['socket' =>  $socket]);
        
    }
   
    private function insertItemBuilder(Request $request, $endDate){
        $active_user = $request->override_user ?? $this->active_user();
        $time = $request->response_time['hours'] * 60 + $request->response_time['minutes'];
        return [
            "user_id" => $active_user->id,
            "updated_user" => $active_user->id,
            "board_id" => $request->board_id,
            "end_at" => $endDate,
            "remarks" => $request->remarks,
            "response_time" => $time ?? null,
            "sync_to_schedule" => $request->sync_to_schedule,
            "title" => $request->title,
            "glowd_nine" => $request->glowd_nine
        ];
    }
    

    public function addTask(Request $request){
        
        $active_user = $this->active_user();
        $request->validate([
            'qualified_users' => 'required',
            'board_id' => 'required',
        ]);
    
        $endDate = $request['task_end_date'];
  
        $edit_id = $request->edit_id;
        
        if ($edit_id) {
            $this->updateSingleTask($edit_id, $this->insertItemBuilder($request, $endDate), $request);
            return response()->json(['status' => 'success']);
        }
    
        $createQuery = $this->insertItemBuilder($request, $endDate);
        if (empty($createQuery)) {
            throw ValidationException::withMessages(['message' => '繰り返し設定をもう一度確認し、有効期間を入力してください。']);
        }
    
        $this->createTasks($createQuery, $request);
        
        if(!$edit_id && $endDate){
            $after = [
                "user_id" => $active_user->id,
                "text" => $request->remarks,
                "board_id" => $request->board_id,
            ];
            TaskCreated::dispatchAfterResponse($after);
        }
        $socket = [];
        
        array_push($socket, ["event" => "task:{$request->board_id}", "data" => []]);
       
        return response()->json(['socket' =>  $socket]);

    }
    private function updateSingleTask($taskId, $data, $request) {
        $task = taskRecord::findOrFail($taskId);
        $task->update($data);
        $this->syncTaskUsers($task, $request);
        $executors = $request->qualified_users;
        if($request->sync_to_schedule){
            $this->sharedService->syncTaskToCalendar($task, $executors);
        }else{
            $this->sharedService->deleteTaskFromCalendar($task);
        }
        
    }
    
    private function createTasks($data, $request) {
            $fresh = taskRecord::create($data);
            if($request->sync_to_schedule){
                $executors = $request->qualified_users;
                $this->sharedService->syncTaskToCalendar($fresh, $executors);
            }
            $this->syncTaskUsers($fresh, $request);
       
    }
    private function syncTaskUsers($task, $request) {
        $task->executors()->sync($request->qualified_users);
        $task->supervisors()->syncWithPivotValues($request->supervisors, ['supervisor' => 1]);
        $pivotData = [];
        foreach ($request->qualified_users as $qualified_user) {
            $pivotData[$qualified_user] = [
                'glowd_nine' => in_array($qualified_user, $request->glowd_nine_users) ? 1 : 0
            ];
        }
        $task->executors()->sync($pivotData);
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
        $active_user = $this->active_user();
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
        $allCompleted = taskUser::where('record_id', $request->task_id)
                            ->where('comp_flag', '!=', 1)
                            ->where('supervisor', '!=', 1)
                            ->doesntExist();
        if ($allCompleted) {
            $task = taskRecord::findOrFail($request->task_id);
            $task->comp_flag = 1;
            taskUser::where('record_id', $request->task_id)
                    ->where('user_id', $active_user->id)
                    ->where('supervisor', 1)
                    ->update(['comp_flag' => 1]);
            $this->sharedService->deleteTaskFromCalendar($task);
            $task->save();
        }
        return response()->json($task_user);
    }

    public function task_not_approved(){
        $active_user = $this->active_user();
        $tasks = taskRecord::where('comp_flag', 0)
                            ->whereHas('supervisors', function ($q) use($active_user) {
                                $q->where('users.id', $active_user->id)
                                    ->where('supervisor', 1);
                            })
                            ->whereHas('executors', function ($q) {
                                $q->where('status_flag', 1);
                            })
                            ->with(['executors' => function ($q) {
                                $q->where('status_flag', 1);
                            }])
                            ->get();
        return response()->json($tasks);
    }
    public function task_update_prize(Request $request) {
        $request->validate([
            'task_id' => 'required',
        ]);
        $active_user = $this->active_user();
        
        
        $taskUser = taskUser::where('record_id', $request->task_id)
                    ->where('user_id', $active_user->id)
                    ->first();
        $params = $request->params;
        if ($taskUser) {
            $taskUser->update($params);
        }
        return response()->json(['message' => 'データが保存されました。']);
    }
    public function task_update_flag(Request $request) {
        $request->validate([
            'task_id' => 'required'
        ]);
        $active_user = $this->active_user();
        $taskUser = taskUser::where('record_id', $request->task_id)
                    ->where('user_id', $active_user->id)
                    ->first();
        if ($taskUser) {
            $taskUser->update([
                'try_flag' => 1
            ]);
        }
        return response()->json(['message' => 'データが保存されました。']);
    }
    public function task_update_pin(Request $request) {
        $request->validate([
            'id' => 'required'
        ]);
        $taskUser = TaskUser::findOrFail($request->id);
        $taskUser->pin_flag = !$taskUser->pin_flag;
        $taskUser->save();
        return response()->json($taskUser);
    }
}
