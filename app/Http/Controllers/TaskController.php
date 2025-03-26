<?php

namespace App\Http\Controllers;

use App\Models\CalendarRecord;
use App\Models\taskRecord;
use App\Models\taskUser;
use App\Models\TaskRepeat;
use App\Models\boardRecord;
use App\Models\ProjectRecord;
use App\Models\TaskComment;
use App\Services\SharedService;
use App\Jobs\TaskCreated;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
        $user_id = $request->user_id;
        $progress_flag = $request->progress_flag;
        
        if(!empty($request)){

            $tasks = taskRecord::where('board_id', $request->record_id)
            ->whereHas('executors', function($q) use($user_id, $progress_flag) {
                if ($user_id !== null) {
                    $q->where('users.id', $user_id);
                    if ($progress_flag !== null && $progress_flag > -1) {
                        $q->where('progress_flag', $progress_flag);
                    }
                } 
            })->orWhereHas('supervisors', function ($q) use($user_id, $progress_flag) {
                $q->where('users.id', $user_id)->whereNot('progress_flag', 2);
                if ($progress_flag !== null && $progress_flag > -1) {
                    $q->where('progress_flag', $progress_flag);
                }
            })
            ->with(['executors', 'files', 'supervisors'])
            ->orderByDesc('created_at')
            ->get();
            
            
            return response()->json($tasks);      
        }  
    }  
    public function addBoardTask(Request $request){
        $active_user = $this->active_user();
        $request->validate([
            'board_id' => 'required',
            'qualified_users' => 'required',
        ]);
        $edit_id = $request->edit_id ?? null;
        $endDate = $request->task_end_date ?? null;
        $time = $request->response_time['hours'] * 60 + $request->response_time['minutes'];
        $task = taskRecord::updateOrCreate(['id' => $edit_id], [
            "user_id" => $active_user->id,
            "updated_user" => $active_user->id,
            "board_id" => $request->board_id,
            "end_at" => $endDate,
            "remarks" => $request->remarks,
            "response_time" => $time ?? null,
            "sync_to_schedule" => $request->sync_to_schedule,
            "title" => $request->title,
            "glowd_nine" => $request->glowd_nine
        ]);
        $task->executors()->sync($request->qualified_users);
        $task->supervisors()->syncWithPivotValues($request->supervisors, ['supervisor' => 1]);
        if ($request->sync_to_schedule) {
            $this->sharedService->syncTaskToCalendar($task, $request->qualified_users);
        } else {
            $this->sharedService->deleteTaskFromCalendar($task);
        }
        $pivotData = [];
        foreach ($request->qualified_users as $qualified_user) {
            $pivotData[$qualified_user] = [
                'glowd_nine' => in_array($qualified_user, $request->glowd_nine_users) ? 1 : 0
            ];
        }
        $task->executors()->sync($pivotData);
        if(!$edit_id && $endDate){ 
            $after = [
                "user_id" => $active_user->id,
                "text" => $request->remarks,
                "board_id" => $request->board_id,
                "glowd_nine" => $request->glowd_nine
            ];
            TaskCreated::dispatchAfterResponse($after);  
        }
        $socket = [];
        array_push($socket, ["event" => "task:{$request->board_id}", "data" => []]);
        return response()->json(['socket' =>  $socket]);
    }
    public function addTask(Request $request){
        $active_user = $this->active_user();
        $request->validate([
            'executors' => 'required',
            'remarks' => 'required',
            'start_at' => 'required',
            'end_at' => 'required',
            'project_id' => 'required'

        ]);  
        $project = ProjectRecord::findOrFail($request->project_id);
        $this->task_project_date_checker($project, $request['start_at'], $request['end_at']);
        foreach($request->sub_tasks as $sub_task){
            $this->task_project_date_checker($project, $sub_task['start_at'], $sub_task['end_at']);
        }
        $id = $request->id ?? null;
        $task = taskRecord::updateOrCreate(['id' => $id], [
            "user_id" => $active_user->id,
            "updated_user" => $active_user->id,
            "end_at" => $request->end_at,
            "start_at"=>$request->start_at,
            "remarks" => $request->remarks,
            "board_id" => $request->board_id ?? null,
            "title" => $request->title,
            "project_record_id" => $request->project_id
        ]);
        
        $task->executors()->sync($request->executors);
        $task->supervisors()->syncWithPivotValues($request->supervisors, ['supervisor' => 1]);
        $pivotData = [];
        // foreach ($request->executors as $qualified_user) {
        //     $pivotData[$qualified_user] = [
        //         'glowd_nine' => in_array($qualified_user, $request->glowd_nine_users) ? 1 : 0
        //     ];
        // }
        // $task->executors()->sync($pivotData);
        if(!$request->id && $request->end_at){ 
            $after = [
                "user_id" => $active_user->id,
                "text" => $request->remarks,
                "board_id" => $request->board_id
            ];
            TaskCreated::dispatchAfterResponse($after);  
        }

        $sub_task_id = [];
        if($request->sub_tasks){

            foreach($request->sub_tasks as $sub_task){
                $subTask = $this->executeSubTask($sub_task, $task, $request->project_id);               
                $sub_task_id[] = $subTask->id;
            }
        }        
       
        taskRecord::where('parent_task_id', $task->id)->whereNotIn('id', $sub_task_id)->delete();        
        $socket = [];
        array_push($socket, ["event" => "task:{$request->board_id}", "data" => []]);      
        return response()->json(['socket' =>  $socket]);  
                      
    }
    public function addSubTask(Request $request){
        $mainTask = taskRecord::findOrFail($request->mainTaskId);
        $this->task_project_date_checker($mainTask->project, $request->params['start_at'], $request->params['end_at']);
        $sub_task = $this->executeSubTask($request->params, $mainTask, $mainTask->project->id);
        return response()->json($sub_task);  
    }
    public function executeSubTask($sub_task, $task, $project_id){
        $id = $sub_task['id'] ?? null;
        
        $subTask = taskRecord::updateOrCreate(['id' => $id ], [
            "remarks" => $sub_task['remarks'],
            "parent_task_id" => $task->id,
            "project_record_id" => $project_id,
            "start_at" => $sub_task['start_at'],
            "end_at" => $sub_task['end_at']                    
        ]);

        $subTask->executors()->sync($sub_task['pre_executors']);          
        return $subTask;
    }
    public function completeTask(Request $request){ 
        $active_user = $this->active_user();        
        $task = taskRecord::findOrFail($request->id);
        $my = $task->executors()->updateExistingPivot($active_user->id, $request->params);
       
        return response()->json($my); 
    }
    public function quick_edit_task(Request $request){
        $task = taskRecord::findOrFail($request->id);
       
        if($request->column == 'start_at' || $request->column == 'end_at'){
            $v1 = $request->column == 'start_at' ? $request->value : $task['start_at'];
            $v2 = $request->column == 'end_at' ? $request->value : $task['end_at'];
            $this->task_project_date_checker($task->project, $v1, $v2);            
        }

        $task->update([$request->column => $request->value]);
       

        return response('OK', 200); 
    }
    private function task_project_date_checker($project, $start, $end){
        $start_limit = $project->date_start;
        $end_limit = $project->date_end;
        if ($start_limit && $end_limit) {
            if($start < $start_limit || $end > $end_limit){
                throw ValidationException::withMessages(['message' => 'プロジェクト期間内に設定してください。<br>プロジェクトの期間は'.$start_limit.'から'.$end_limit.'までです。']);
            }
            if( $start > $end){
                throw ValidationException::withMessages(['message' => '開始日は終了日より後にはできません。']);
            }
        }
        
        return;
    }
    public function completeSubTask(Request $request){ 
        $active_user = $this->active_user();   
        taskRecord::findOrFail($request->id)
        ->executors()
        ->updateExistingPivot($active_user->id, $request->params);
        return response()->json(200); 
    }
    public function taskDelete(Request $request){
        $task = taskRecord::find($request->task_id);
        if($task->sync_to_schedule){
            $this->sharedService->deleteTaskFromCalendar($task);
        }
        $task->comments()->delete();
        $task->sub_tasks()->delete();
        $task->delete();
        $socket = [];
        array_push($socket, ["event" => "task:{$task->board_id}", "data" => []]);      
        return response()->json(['socket' =>  $socket]);  
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
        $allBoard = boardRecord::whereHas('board_to_users', function($q) use($active_user){
            $q->where('user_id', $active_user->id)->where('deleted_status','=', 0);
        })->pluck('id')->toArray();
        $taskCounts = taskRecord::whereNotNull('end_at')
        ->whereIn('board_id', $allBoard)
        ->whereHas('executors', function($q) use($active_user) {
            $q->where('users.id', $active_user->id)->where(function($q) {
                $q->where('progress_flag', 0)
                  ->orWhere('progress_flag', 1);
            });
        })
        ->select('board_id', DB::raw('count(*) as total_task_number'))
        ->groupBy('board_id')
        ->get()
        ->pluck('total_task_number', 'board_id')
        ->toArray();
        return response()->json($taskCounts);
    }
    public function get_task_comment_list(Request $request){
        $request->validate([
            'task_record_id' => 'required',
        ]); 
        $task = taskRecord::findOrFail($request->task_record_id);
        $comments = $task->comments()->with('user')->get();
        return response()->json($comments);
    }
   
    public function task_comment(Request $request){
        $active_user = Auth::user();
        $request->validate([
            'task_record_id' => 'required',
            'comment' => 'required',
        ]); 
        $task = taskRecord::findOrFail($request->task_record_id);
        $data = $request->toArray();
        $data['user_id'] = $active_user->id;
        $comment = $task->comments()->create($data);
        $related_users = $task->taskUsers()->pluck('user_id')->toArray();
        $socket = [];


        array_push($socket, ["event" => 'refresh:task', "data" => $request->task_record_id]);  
        array_push($socket, ["event" => 'refresh:task_comment', "data" => ['task_id' => $request->task_record_id, 'members' => $related_users]]);


        // event(new MessageSent($rebound));    
        return response()->json([
            "socket" => $socket
        ]);

    }
    public function task_comment_update(Request $request){
        $request->validate([
            'id' => 'required',
            'comment' => 'required',
        ]); 
        $comment = TaskComment::findOrFail($request->id);
        $comment->update(['comment' => $request->comment]);
        return response(200);

    }
    public function update_task_comment_check(Request $request){
        $request->validate([
            'task_id' => 'required',
        ]); 
        $active_user = $this->active_user();
        $task = taskRecord::findOrFail($request->task_id);
        $task->executors()->updateExistingPivot($active_user->id, ['checked_at' => now()]);
        return response(200);

    }
    public function task_comment_delete(Request $request){
        $request->validate([
            'id' => 'required',
        ]); 
        $comment = TaskComment::findOrFail($request->id);
        $comment->delete();
        return response(200);

    }
    public function get_gantt_project_tasks(Request $request){
        $request->validate([
            "id" => "required"
        ]);
        $user_id = $request->user_id;
        $progress_flag = $request->progress_flag;
        $weekStartDate = Carbon::now()->startOfWeek(CarbonInterface::MONDAY)->toDateString(); 
        $projects = ProjectRecord::where('id', $request->id)
        ->with(['members', 'manager', 'director', 'tasks' => function($q) use($user_id, $progress_flag, $weekStartDate) {
            $q->whereNull('parent_task_id')->when($user_id, function($q)use($user_id, $progress_flag){
                $q->whereHas('executors', function($q) use($user_id, $progress_flag){
                    $q->where('users.id', $user_id)->when($progress_flag !== null && $progress_flag > -1, function($q) use($progress_flag){
                        $q->where('progress_flag', $progress_flag);
                    });
                });
            })->with(['executors','files','supervisors', 'project', 'sub_tasks' => function($q) use($user_id, $progress_flag){
                $q->when($user_id, function($q)use($user_id, $progress_flag){
                    $q->whereHas('executors', function($q) use($user_id, $progress_flag){
                        $q->where('users.id', $user_id)->when($progress_flag !== null && $progress_flag > -1, function($q) use($progress_flag){
                            $q->where('progress_flag', $progress_flag);
                        });
                    });
                })->withCount('comments');
            }])
            ->withCount('comments')
            ->orderBy('created_at', 'desc');
        },
        'project_conditions' => function ($q) use ($weekStartDate) {
            $q->where('week_start_date', $weekStartDate);
        }
        ])->first();
        if ($projects['date_start'] == null || $projects['date_end'] == null) {
            $projects['date_start'] = Carbon::now()->startOfYear()->format('Y-m-d');  
            $projects['date_end'] = Carbon::now()->endOfYear()->format('Y-m-d');

        }
        return response()->json(['project' => empty($projects) ? null : $projects]);
    }
    public function get_gantt_projects(Request $request){

        $unit = $request->unit;
        
        $weekStartDate = Carbon::now()->startOfWeek(CarbonInterface::MONDAY)->toDateString(); 

        $fromInstance = Carbon::parse($request->from);
        $toInstance = Carbon::parse($request->to);
        $projects = ProjectRecord::when(($unit == 'month' || $unit == 'day') && ($fromInstance->isValid() && $toInstance->isValid()), function($query) use ($fromInstance, $toInstance){
            $query->where(function($query) use ($fromInstance, $toInstance) {
                $query->whereBetween('date_start', [$fromInstance, $toInstance])
                    ->orWhereBetween('date_end', [$fromInstance, $toInstance])
                    ->orWhere(function($query) use ($fromInstance, $toInstance) {
                        $query->where('date_start', '<', $fromInstance)
                            ->where('date_end', '>', $toInstance);
                    })->orWhere(function($query) use ($toInstance) {
                        $query->whereNull('date_start')
                                ->whereDate('date_end', '>=', $toInstance->startOfDay())
                                ->whereDate('date_end', '<=', $toInstance->endOfDay());
                    });
            });
        })
        ->when($unit !== 'year', function ($query){
            $query->orWhereNull('date_start') 
            ->orWhereNull('date_end');
        })
        ->with(['members','manager' ])
        ->with(['project_conditions' => function ($q) use ($weekStartDate) {
            $q->where('week_start_date', $weekStartDate);
        }])
        ->withCount('tasks')->orderBy('date_start', 'asc')->get();


        

        if($unit == 'year' && count($projects)){
            $collection = collect($projects);
            $min = $collection->min('date_start');
            $max = $collection->max('date_end');
            $fromInstance = Carbon::parse($min);
            $toInstance = Carbon::parse($max);
        }

        $projects->map(function($project) use($fromInstance, $toInstance, $unit){
            if ($project['date_start'] == null || $project['date_end'] == null) {
                $start = Carbon::now()->startOfYear()->format('Y-m-d');
                $end = Carbon::now()->endOfYear()->format('Y-m-d');
                $project['pseudo_start'] = $start < $fromInstance ? $fromInstance->clone()->format('Y-m-d') : $start;  
                $project['pseudo_end'] = $end > $toInstance ? $toInstance->clone()->format('Y-m-d') : $end;
    
            } else {
                $start = Carbon::createFromFormat('Y-m-d', $project['date_start']);
                $end = Carbon::createFromFormat('Y-m-d', $project['date_end']);
        
                $project['pseudo_start'] = $start < $fromInstance ? $fromInstance->clone()->format('Y-m-d') : $project['date_start'];  
                $project['pseudo_end'] = $end > $toInstance ? $toInstance->clone()->format('Y-m-d') : $project['date_end'];
            }
                              
        
            $duration = Carbon::createFromFormat('Y-m-d', $project['pseudo_end'])->$unit - Carbon::createFromFormat('Y-m-d', $project['pseudo_start'])->$unit;
            $duration++;
            $project['duration'] = abs($duration);
        });
        $sortedProjects = $projects->sortByDesc(function ($project) {
            $isMember = in_array(Auth::id(), $project->members->pluck('id')->toArray());
            $isManager = in_array(Auth::id(), $project->manager->pluck('id')->toArray());
            $isDirector = $project->director && $project->director->id == Auth::id();
            if ($isMember) {
                return 3;
            } elseif ($isManager) {
                return 2;
            } elseif ($isDirector) {
                return 1;
            }
            return 0;
        })->values();
        return response()->json($sortedProjects);
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
                                'progress_flag' => $request->progress_flag,
                              ]);
        $allCompleted = taskUser::where('record_id', $request->task_id)
                            ->where('progress_flag', '!=', 1)
                            ->orWhere('progress_flag', '!=', 0)
                            ->where('supervisor', '!=', 1)
                            ->doesntExist();
        if ($allCompleted) {
            $task = taskRecord::findOrFail($request->task_id);
            $task->comp_flag = 1;
            taskUser::where('record_id', $request->task_id)
                    ->where('user_id', $active_user->id)
                    ->where('supervisor', 1)
                    ->update(['progress_flag' => 2]);
            $this->sharedService->deleteTaskFromCalendar($task);
            $task->save();
        }
        return response()->json($task_user);
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
