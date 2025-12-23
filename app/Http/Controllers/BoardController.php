<?php

namespace App\Http\Controllers;


use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\User;
use App\Models\Icons;
use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\searchHistoryRecord;
use App\Models\taskRecord;
use App\Models\taskUser;
use App\Models\CalendarRecord;
use App\Models\messageRemindUser;
use App\Models\messageSignUser;
use App\Models\UserLeaveRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Pusher\Pusher;
use App\Services\SharedService;
use App\Mail\Confirm;
use App\Jobs\SendNotification;
use App\Jobs\SendEmail;
use DB;
class BoardController extends Controller
{
    protected $sharedService;
    
    public function __construct(SharedService $sharedService)
    {
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
    public function start_private_board(Request $request){
        $with = $request['with'];
        $active_user = $this->active_user();
        if($with){
            $correspondId = (int) $with;
            $checkCurrentBoard = boardRecord::where('private_flag', '=', 1)
            ->whereHas('board_to_users', function($q) use($active_user){
                $q->where('user_id', '=', $active_user->id)->withTrashed();
            })->whereHas('board_to_users', function($q)use($correspondId){
                $q->where('user_id', '=', $correspondId)->withTrashed();
            })->first();

            if(!empty($checkCurrentBoard)){ 
                $restoreUsers = $checkCurrentBoard->board_to_users()->where('deleted_status', 1)->update([
                    'deleted_status' => 0,
                    'created_at' => now()
                ]);  
                $newUrl = url('board/' . $checkCurrentBoard->id);
                if($request['nodirect']){
                    return response()->json($checkCurrentBoard->id);
                }else{
                    return redirect($newUrl);
                }
                
            }else{            
                $board = new boardRecord;
                $board->user_id = $active_user->id;
                $board->private_flag = 1; 
                $board->title = 'NoTitle';                
                $board->save();      
                
                $to_users = [$active_user->id, $correspondId];

                foreach($to_users as $to_user){
                    $boardToUser = new boardToUser;
                    $boardToUser->record_id = $board->id;
                    $boardToUser->user_id = $to_user; 
                    if($to_user == $active_user->id){
                        $boardToUser->admin_flag = 1;
                        $boardToUser->last_act = now();
                    }                
                    $boardToUser->save();                   
                }      
                $newUrl = url('board/' . $board->id);
                if($request['nodirect']){
                    return response()->json($board->id);
                }else{
                    return redirect($newUrl);
                }   
            }
        }
        return $with;
    }
    public function index(Request $request){ 
        $id = $request->query('id');
        $m = $request->query('m');
        if($id && $m){
            $newUrl = url('board/' . $id . '?m=' . $m);
            return redirect($newUrl);
        }
        $date = null;
        $name = $request->name;
        $id = $request->id;
        if($name && $name == 'schedule' && $id){
            
            $find = CalendarRecord::where('id', $id)->first();
            if(!empty($find)){
                $date = Carbon::parse($find->date_start)->format('Y-m-d');
            }
        }
        $no_partner_zone = ['post', 'work', 'support', 'project'];
        if(in_array($name, $no_partner_zone) && Auth::user()->partner_flag == 1){
            return redirect('board');
        }
        $no_registered_zone = ['post', 'learning'];
        if(in_array($name, $no_registered_zone) && Auth::user()->position_id == 15){
            return redirect('board');
        } 
        // echo $id; 
        // return;
        $today = Carbon::now()->format('Y-m-d');     
        
        $user = auth()->user()->load(['weathers' => function($q) use($today){
            $q->where('type_id', 43)->where('date', $today);
        }, 'project_settings', 'linked']);
       
        return view('board')->with(array('initialDate'=> $date, 'user' => $user));

    } 
    public function board_list(Request $request) {       
        $active_user = $this->active_user();
        $perPage = 40;

        $base = boardRecord::query()
        ->whereHas('board_to_users', function($q) use($active_user){
            $q->where('user_id', $active_user->id)->where('deleted_status', 0);
        })
        ->when($active_user->on_leave === 1, function ($q) {
            return $q->where('private_flag', '!=', 0);
        })
        ->orderByDesc('updated_at')
        ->orderByDesc('id');

        $with = [
            'user',
            'icons' => fn($q) => $q->select('id', 'extension'),
            'board_to_users' => fn($q) => $q->whereHas('user')->with('user'),
            'project',
            'last_message',
        ];

        if ($request->filled('id')) {
            $board = (clone $base)->whereKey($request->id)->first();

            if (!$board) {
                throw ValidationException::withMessages([
                'message' => 'チャットが削除されているか、権限がないためアクセスできません。'
                ]);
            }

            $beforeCount = (clone $base)
                ->where(function ($q) use ($board) {
                $q->where('updated_at', '>', $board->updated_at)
                    ->orWhere(function ($q) use ($board) {
                    $q->where('updated_at', '=', $board->updated_at)
                        ->where('id', '>', $board->id);
                    });
                })
                ->count();

            $rank = $beforeCount + 1;
            $bucket = (int) (ceil($rank / $perPage) * $perPage);

            $bucketPage = (clone $base)
                ->with($with)
                ->cursorPaginate($bucket);

            return response()->json($bucketPage);
        }

        // normal cursor paging
        $board_list = (clone $base)
            ->with($with)
            ->cursorPaginate($perPage);

        return response()->json($board_list);
 

        // $self_list = boardRecord::whereHas('board_to_users', function($q) use($active_user){
        //     $q->where('user_id', $active_user->id)->where('deleted_status', 0);
        // })->with('user')
        // ->with(['icons' => function($q){
        //     $q->select('id','extension');
        // }])->with(['board_to_users' => function($q){
        //     $q->whereHas('user')
        //     ->with('user');
        // }])
        // ->with('project')
        // ->with('last_message')
        // ->when($active_user->on_leave === 1, function ($query) {
        //     return $query->where('private_flag', '!=', 0);
        // })
        // ->orderBy('updated_at', 'desc')
        // ->orderBy('id', 'desc')
        // ->cursorPaginate(30); 

        // return response()->json($self_list);
        
    }
    public function postRestoreMessage(Request $request){
        $active_user = $this->active_user();
        $user = boardToUser::where('record_id', '=', $request->id)->where('user_id', '=', $active_user->id)->first();
        if($user && $user->deleted_status == 1){
            $user->deleted_status = 0;
            $user->save();
            $board = boardRecord::find($request->id);
            $board->touch();
        }
        return response()->json($request);
    }    
    public function board_create(Request $request){
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        $file_array = $request->file;
        $file_id_array = [];
        if(is_array($file_array)){
            foreach( $file_array as $files ){
                $str = explode('_',$files);                
                array_push( $file_id_array , $str[0] );
            }   
        }
        if(!empty($request->to_users)){
            $defaultTitle = null;                  

            $usersCount = count($request->to_users);            
            if($usersCount == 1 && $request->private_flag == 1){
                $correspondId = $request->to_users[0];
                $checkCurrentBoard = boardRecord::where('private_flag', '=', 1)
                    ->whereHas('board_to_users', function($q) use($active_user){
                        $q->where('user_id', '=', $active_user->id)->withTrashed();
                    })->whereHas('board_to_users', function($q)use($correspondId){
                        $q->where('user_id', '=', $correspondId)->withTrashed();
                    })->first();
                    if(!empty($checkCurrentBoard)){ 
                        $restoreUsers = $checkCurrentBoard->board_to_users()->where('deleted_status', 1)->update([
                            'deleted_status' => 0,
                            'created_at' => now()
                        ]);     
                        $socket = array();
                        $related_members = $checkCurrentBoard->board_to_users()->pluck('user_id')->toArray();
                        array_push($socket, ["event" => 'refresh:badge', "data" => $related_members]);
                        array_push($socket, ["event" => 'refresh:board', "data" => $related_members]);                 
                        $arr = [
                            "restored" => $restoreUsers,
                            "message" => $restoreUsers ? "作成しました。" : "チャットがすでに存在します。",
                            "success" => true,
                            "data" => $checkCurrentBoard,
                            "socket" => $socket
                        ];   
                        $checkCurrentBoard->touch(); 
                        return response()->json($arr);
                    }
                $defaultTitle = 'NoTitle';                   
            }            
            if($usersCount > 1 && empty($request->title)){
                $arr = [
                    "message" => "タイトルを入力してください。",
                    "success" => false
                ];
                return response()->json($arr);
            }
            
            $board = new boardRecord;
            $board->user_id = $auth_user_id;
            $board->private_flag = $request->private_flag;           
            $board->icon_bg = $request->icon_bg;
            $board->icon_text = $request->icon_text;
            $board->icon_path = $request->icon_path;

            
            if($defaultTitle == null){
                $board->title = $request->title;
            }elseif($defaultTitle == 'NoTitle'){
                $board->title = $defaultTitle;
            }    
            $board->save();           
            

            $to_users = $request->to_users;
            array_unshift($to_users, $auth_user_id);
            $uniqueArray = array_unique($to_users);
            foreach($uniqueArray as $to_user){
                $boardToUser = new boardToUser;
                $boardToUser->record_id = $board->id;
                $boardToUser->user_id = $to_user; 
                if($to_user == $auth_user_id){
                    $boardToUser->admin_flag = 1;
                    $boardToUser->last_act = now();
                }                
                $boardToUser->save();
                $initialMember = User::where('id', $to_user)->select('id', 'name')->first();
                if($initialMember){
                    $new_members[] = $initialMember->name;
                }

            }            
           
            // if(empty($file_id_array) && $request->private_flag !== 1){
            //     try {
            //         $createIcon = $this->sharedService->createBoardDefaultIcon($board, $active_user->id);             
                   
            //         if ($createIcon) {
            //             $board->save();
            //         } else {
            //             $board->delete();
            //             throw ValidationException::withMessages(['message' => $createIcon]);
            //         }   
            //     } catch (\Exception $e) {           
            //         $board->delete();       
            //         throw ValidationException::withMessages(['message' => $createIcon]);
            //     }               

            // }
            // if(!empty($file_id_array)){
            //     $board->icon_path = $request->icon_path;
            // } else {
            //     $board->icon_text = $request->icon_text;
            // }      
            // $board->save();
            


            $socket = array();
            $related_members = $board->board_to_users()->pluck('user_id')->toArray();
            array_push($socket, ["event" => 'refresh:badge', "data" => $related_members]);
            array_push($socket, ["event" => 'refresh:board', "data" => $related_members]);   
            $arr = [
                "message" => "作成しました。",
                "success" => true,
                "data" => $board,
                "socket" => $socket
            ];  
            // event(new MessageSent($rebound));
            return response()->json($arr);
            
        }else{
            $arr = [
                "message" => "メンバーを選択してください。",
                "success" => false
            ];   
            return response()->json($arr);

        }

    }
    //編集処理
    public function board_edit(Request $request){
        $active_user = $this->active_user();
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $board = boardRecord::findOrFail($request->id); 
        $checkAdmin = $board->board_to_users()->where('user_id', $active_user->id)->where('admin_flag', 1)->exists();
        if(!$checkAdmin){
            throw ValidationException::withMessages(['message' => '管理者でないメンバーはチャット編集できません']);
        }             
        $board->timestamps = false;
        $board->update([
            'title' => $request->title, 
            'icon_bg' => $request->icon_bg, 
            'icon_text' => $request->icon_text,
            'icon_path' => $request->icon_path
        ]);    
         
        
        $board->timestamps = true;       

        $socket = array();
        $related_members = $board->board_to_users()->pluck('user_id')->toArray();
        array_push($socket, ["event" => 'refresh:badge', "data" => $related_members]);
        array_push($socket, ["event" => 'refresh:board', "data" => $related_members]); 
        // event(new MessageSent($rebound));
        return response()->json([
            "socket" => $socket
        ]);       
    }
    public function board_delete(Request $request){
        
     
            
            $board = boardRecord::findOrFail($request->id);
            $active_user = $this->active_user();
            if(!empty($board)){
                if($board->private_flag == 0){
                    $admin_access = $board->board_to_users()->where('user_id', $active_user->id)->where('admin_flag', 1)->exists();
                    if(!$admin_access){
                        throw ValidationException::withMessages(['message' => 'Sufficient administrative permission.']);
                    }
                    $createIcon = $this->sharedService->removeBoard($board);     
                    return response()->json($createIcon);
                }else if($board->private_flag == 1){
                    $member_access = $board->board_to_users()->where('user_id', $active_user->id)->first();
                    if(!empty($member_access)){
                        $member_access->update(['deleted_status' => 1]);
                        return response()->json('success');
                    }
                }
            }               
        
    }
    public function cancelSignature(Request $request){
        $active_user = $this->active_user();
        $auth_id = $active_user->id;
        $originalSign = '';
        $signUser = '';
        if($request->original_id){
            $signUser = messageSignUser::where('message_file_id', $request->file_id)->where('user_id', $auth_id)->first();
            $originalSign = messageSignUser::where('message_file_id', $request->original_id)->where('user_id', $auth_id)->first();
        }else{
            $signUser = messageSignUser::where('message_file_id', $request->file_id)->where('user_id', $auth_id)->first();
        }

        if($originalSign && $signUser){
            $originalSign->cancel_flag = 1;
            $signUser->cancel_flag = 1;
            $signUser->save();
            $originalSign->save();
        }else if($signUser){
            $signUser->cancel_flag = 1;
            $signUser->save();
        }

        return response()->json($signUser);
    }
    // public function saveSignature(Request $request){
    //     $active_user = $this->active_user();
    //     $auth_id = $active_user->id;
    //     $user = User::findOrFail($auth_id)->with('linked');
    //     $unique_number = rand(1000, 9999); 
    //     $current_timestamp = time(); 
    //     $new_a_path = $current_timestamp . $unique_number; 
    //     $set_path = $auth_id . '_' . $new_a_path . '.png';
    //     if (!Storage::disk('local')->exists('user_signature')) {
    //         Storage::disk('local')->makeDirectory('user_signature');
    //     }
    //     Storage::disk('local')->put('user_signature/' . $set_path, file_get_contents($request->sign));
    //     Storage::disk('local')->delete('user_signature/' . $auth_id . '_' . $user->sign_path . '.png');
    //     $user->sign_path = $new_a_path;
    //     $user->save();
    //     return response()->json($user);
    // }
    public function getEditUser(Request $request){
        $active_user = $this->active_user();
        $auth_id = $active_user->id;
        $msg_file = messageFile::findOrFail($request->file_id);
        $data = [];
        $user = User::findOrFail($auth_id); 
        if($user->sign_path != null){
            $path = $auth_id . '_' . $user->sign_path . '.png';
            $data = [
                'sign_path' => $path
            ];
        }
        if ($msg_file->edit_flag != null) {
            $editFlagExpiration = Carbon::parse($msg_file->edit_flag)->addMinutes(30);
        
            if ($editFlagExpiration->isPast()) {
                $msg_file->edit_flag = Carbon::now();
                $msg_file->edit_user = $auth_id;
                $msg_file->save();
                return response()->json($data);
            } else if($msg_file->edit_user != $auth_id){

                $user = User::select('id', 'name')->findOrFail($msg_file->edit_user);
                throw ValidationException::withMessages(['message' => '<strong>'.$user->name.'</strong>さんが現在このファイルにサイン中です。同時にサインすることはできません。30分後にもう一度お試しください`']); 
            }

        } else {
            
            $msg_file->edit_flag = Carbon::now();
            $msg_file->edit_user = $auth_id;
            $msg_file->save();
            return response()->json($data);
        }
        return response()->json($data);
    }
    public function signFile(Request $request){
        $active_user = $this->active_user();
        foreach($request->file() as $file){           
            $newFile = messageFile::find($request->file_id);
            $set_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
            Storage::disk('local')->putFileAs(
                'shared_files/' . $request->board_id, $file, $set_path
            );
            $sizeAfter = Storage::disk('local')->size('shared_files/' . $request->board_id .'/'. $set_path);
            $newFile->size = $sizeAfter;
            $newFile->edit_flag = null;
            $newFile->save();
            
            $signUser = $newFile->signUsers()->where('user_id', $active_user->id)->first();
            if($newFile->multiple_flag == 2){
                $originalFile = messageFile::find($newFile->original_file_id);
                $originalSignUser = $originalFile->signUsers()->where('user_id', $active_user->id)->first();
                if($originalSignUser){
                    $originalSignUser->pivot->signed = true;
                    $originalSignUser->pivot->save();
                }
            }
            if ($signUser) {
                $signUser->pivot->signed = true;
                $signUser->pivot->save();
            }         
        }      
        return response()->json("success");
    }
    public function incomplete_check(Request $request) {
        $user = $this->active_user();
        $today = Carbon::today();
        $start_point = Carbon::parse('2023-03-13 00:00:00')->format('Y-m-d');
        $list = boardToUser::where('user_id', $user->id)
                            ->where('deleted_status', 0)
                            ->pluck('record_id');
        $messages = messageRecord::whereIn('record_id', $list)
        ->where('deleted_flag', 0)
        ->with('user')
        ->with(['message_files', 'message_files.unsignedUsers', 'message_files.signedUsers'])
        ->with('reactedUsers')
        ->with('checkedUsers')
        ->with('uncheckedUsers')
        ->with('emotedUsers')
        ->with('messageRemindUsers')
        ->whereHas('messageRemindUsers', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                    ->where('reminded', 1); // For reminded messages
        })
        ->orWhereHas('checkUsers', function ($query) use ($user, $start_point) {
            $query->where('user_id', $user->id)
                    ->where('checked', 0)
                    ->whereDate('check_request_at', '>', $start_point); // For check messages
        })
        ->orWhereHas('message_files', function ($query) use ($user) {
            $query->where('sign_flag', 1)
                    ->whereHas('unsignedUsers', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->where('cancel_flag', 0); // For comment_list_pre messages
                    });
        })
        ->get();
        $tasks = taskRecord::where('comp_flag', '=', 0)
                ->whereHas('task_users', function($q) use($user){
                    $q->where('user_id', $user->id)->where('comp_flag', 0);
                })
                ->with('to_users')
                ->whereDate('end_at', '<=', $today)
                ->orderBy('created_at', 'desc')->get();
        $data = [
            'messages' => $messages,
            'tasks' => $tasks
        ];
        return response()->json($data);
    }
    public function attachUpload(Request $request){
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        $ids = [];
        foreach($request->file() as $file ){
            $mime_type = $file->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];
            $file_extension = strtolower($file->getClientOriginalExtension());
            $path = '/temp_upload';     
            $file_name = $file->getClientOriginalName(); 
            $file_size = $file->getSize();   

            $newFile = messageFile::create([
                'name' => $file_name,
                'extension' => $file_extension,
                'user_id' => $auth_user_id,
                'mime_type' => $file_type,
            ]);

            $set_path = "$newFile->id.$file_extension";

            File::isDirectory(storage_path("app/$path")) or File::makeDirectory(storage_path("app/$path"), 0755, true, true);

            if($file_type == 'image'){
                $img = Image::read($file);
                $img->save(storage_path("app/$path/$set_path"), 30);

            }else{                
                Storage::disk('local')->putFileAs('/temp_upload', $file, $set_path);
            }

            $sizeAfter = File::size(storage_path("app/temp_upload/$set_path"));
            $newFile->update([ 'size' => $sizeAfter ]);
            $ids[] = $newFile;
                       
        }
        return response()->json($ids);        
    }
    public function removeTemp(Request $request){
        if($request->id){
            $file = messageFile::find($request->id);
                if($file){
                    $file->delete();
                    Storage::disk('local')->delete('temp_upload/'.$file->id.'.'.$file->extension);
                }
           
        }
    }
    private function user_onleave($user_id){
        return UserLeaveRecord::where('user_id', $user_id)
        ->where('active', 2)
        ->first();
    }
    public function get_messages(Request $request){
        $id = $request->message_id ?? null;
        $pagenate = 30 * $request->page_index;       
        $active_user = $request->override_user ?? $this->active_user();
        $auth_user_id = $active_user->id;
        $leavePeriod = $this->user_onleave($auth_user_id);
        $usercheck = boardToUser::where('user_id','=', $auth_user_id)->where('record_id', '=', $request->record_id)->first();   
        if(empty($usercheck)){
            throw ValidationException::withMessages(['message' => 'チャットメンバーではありません。']); 
        }
        $timeLimit = $usercheck->created_at;    
        $targetBoard = boardRecord::findOrFail($request->record_id);
        $messageFrom = $targetBoard->message_from;     
        $time_condition = $messageFrom == 0 && $timeLimit;   
        $view_from = $usercheck->view_from;
        $offset = $request->offset ?? null;
        $query = messageRecord::query()->where('record_id', $request->record_id)
        
        ->where('deleted_flag', 0)
        ->when($id, function($query) use($id){
            $query->where('id', $id);
        })
        ->when($view_from, function ($query) use ($view_from) {
            $query->where('created_at', '>=', $view_from);
        })
        ->when(!$view_from && $time_condition, function ($query) use ($timeLimit) {
            $query->where('created_at', '>=',  $timeLimit );
        })
        ->when($leavePeriod && $targetBoard->private_flag != 1, function ($query) use ($leavePeriod) {
            $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
        })
        ->when($targetBoard->private_flag !== 3, function ($query) {
            $query->withTrashed();
        })
        ->when($offset, function ($query) use ($offset) {
            $query->where('created_at', '>=', $offset);
        })
        ->with([
            'user',
            'actual_sender',
            'message_files.unsignedUsers',
            'message_files.signedUsers',
            'message_reply',
            'message_quot',
            'message_forward',
            'reactedUsers',
            'checkedUsers',
            'uncheckedUsers',
            'emotedUsers',
            'messageRemindUsers',
            'task'
        ]);
        $draft_messages = (clone $query)->where('draft_flag', 1)->orderBy('created_at', 'desc')->get();

        $comment_list_pre = $query
        ->where('draft_flag', 0)
        
        
        // ->latest('created_at')
        // ->orderBy('created_at', 'desc')
        ->orderByDesc('id') 
        ->take($pagenate)
        ->get();
        $comment_list = $draft_messages->merge(items: $comment_list_pre);
        $data = [
            'messages' => $comment_list,
        ];
        return response()->json($data);

    }
    public function chatAdd(Request $request){


        
        $active_user = $request->override_user ?? $this->active_user();
        $auth_user_id = $active_user->id;
        if($request->quot_flag == 1 && $request->reply_flag == 1){
            throw ValidationException::withMessages(['message' => 'commonError']);            
        }   
        $boardRecord = boardRecord::findOrFail($request->record_id);


        if($request->message_id){
            $chat = messageRecord::findOrFail($request->message_id);
        }else{
            $chat = new messageRecord;
        }           
            $chat->record_id = $request->record_id;
            $chat->user_id = $request->override_user_id ?? $auth_user_id;

            if(Auth::id() != $auth_user_id){
                $chat->actual_sender_id = Auth::id();
            }
            
            if($request->message){
                $chat->message = $request->message;
                $chat->message_text = strip_tags(htmlspecialchars_decode($request->message)); 
            }else{
                $chat->message = '';
                $chat->message_text = strip_tags(htmlspecialchars_decode('')); 
            }            
            
            $chat->emoji_flag = $this->containsOnlyEmojis($request->message);
            if($request->reply_flag == 1){
                $chat->reply_id = $request->reply_id;          
            }
            if($request->quot_flag == 1){
                $chat->quot_id = $request->quot_id;          
            }
            if($request->selected_quot_text){
                $chat->quot_message = $request->selected_quot_text;
            }
            if($request->forward_message_id){
                $chat->forward_id = $request->forward_message_id;
            }
            $chat->draft_flag = $request->draft_flag ?? 0;
            $chat->save();
            
            if($request->attached_temp_files){ 
                foreach($request->attached_temp_files as $item){
                    $path_shared_files = $request->record_id;       
                    $path_temp_files = 'shared_files/temp_upload';   
                    $file = messageFile::findOrFail($item['id']);
                    $file->board_id = $chat->record_id;

                    $file->message_id = $chat->id;                            
                    $file->save(); 
                    $path = 'shared_files/' . $chat->record_id;
                    File::isDirectory(storage_path('app') . '/' . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);             
                    

                    $srcPath = $file->id . '.' .$file->extension;
                    $destPath = $chat->record_id . '/' . $file->id . '_' . $file->user_id . '_' . $chat->id . '.' . $file->extension;
                    $temp_path = storage_path('app/temp_upload/' . $srcPath);
                    Storage::disk('local')->move('temp_upload/' .  $file->id . '.' .$file->extension, 'shared_files/' . $destPath);
                        
                    
                    
                }
            }
            if($request->sharing_files){   
                $path_shared_files = 'shared_files/' . $request->record_id;
                foreach($request->sharing_files as $file){
                   
                    $newFile = new messageFile;
                    $newFile->board_id = $chat['record_id'];
                    $newFile->message_id = $chat['id'];
                    $newFile->name = $file['record']['name'];
                    $newFile->extension = $file['record']['extension'];                    
                    $newFile->user_id = $auth_user_id;
                    $newFile->mime_type = $file['record']['mime_type'];  
                    $newFile->size = $file['record']['size'];      
                    $newFile->save(); 
                    $origin_path = 'shared_files/' . $file['record']['board_id'] . '/' . $file['record']['id']. '_' . $file['record']['user_id'] . '_' . $file['record']['message_id'] . '.' . $file['record']['extension'];
                    $msg_file_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
                    File::isDirectory(storage_path('app/shared_files/' . $request->record_id)) or File::makeDirectory(storage_path('app/shared_files/' . $request->record_id), 0755, true, true); 
                    Storage::disk('local')->copy($origin_path, $path_shared_files . '/' . $msg_file_path);
                }
            }
            $offset = $request->timestamp ?? Carbon::now()->toDateTimeString()  ;
            $instance = Carbon::parse($offset);
            $messageRecord = $this->get_messages(new Request([
                'page_index' => 1, 
                'record_id' => $request->record_id, 
                'offset' => $instance->toDateTimeString(),
                'override_user' => $request->override_user
            ]));          
            $boardRefresh = $boardRecord->load('last_message');
            $last_message = $boardRefresh->last_message;
            if ($chat->draft_flag === 1) {
                $data = [
                    "success" => true,
                    "u_id" => $request->u_id,
                    "data" => $chat,
                    "message" => $messageRecord->original,
                    "last_message" => $last_message,
                ];          
                return response()->json($data);
            }
            
            $not = $this->mentionAndNotify( $boardRecord, $active_user, $chat);
            $related_members = boardToUser::where('record_id','=', $request->record_id)->where('deleted_status', '=', 0)->where('user_id', '!=', $auth_user_id)->pluck('user_id');
            if(!$request->override_user_id){
                $update_last_message = boardToUser::where('record_id','=', $request->record_id)->where('user_id', '=', $auth_user_id)->update(["last_message" => $chat->id]);
            }    
            
            // SendPusher::dispatchAfterResponse($rebound);  
            $socket = array();
            array_push($socket, ["event" => "board:{$request->record_id}", "data" => []]);
            array_push($socket, ["event" => 'refresh:badge', "data" => $related_members]);
            array_push($socket, ["event" => 'refresh:board', "data" => $related_members]);
            $data = [
                "success" => true,
                "u_id" => $request->u_id,
                "data" => $chat,
                "socket" => $socket,
                "message" => $messageRecord->original,
                "notified" => $not,
                "last_message" => $last_message,
            ];          
            return response()->json($data);

    }
    private function mentionAndNotify($boardRecord, $user, $chat) {
        $boardRecord->touch();
        if($boardRecord->private_flag == 1){
            $restoreUsers = boardToUser::where('record_id','=', $boardRecord->id)->where('deleted_status', '=', 1)->get();
            if(!empty($restoreUsers)){
                foreach($restoreUsers as $restoreUser){
                    $restoreUser->deleted_status = 0;
                    $restoreUser->created_at = now();
                    $restoreUser->save();
                }
                $chat->touch();
            }
            
        }
        $syntax = '/\[To:(.*?)\:\]/';
        preg_match_all($syntax, $chat->message, $matches);
        $mentioned_targets = $matches[1];
        $mentioned_all = in_array('全員', $mentioned_targets);

        $query = $boardRecord->members()
        ->whereNot('users.id', Auth::id())
        ->where('users.on_leave', 0)
        ->when(!$mentioned_all, function($q) use($mentioned_targets) {
            $q->whereIn('users.name', $mentioned_targets);
        });
        $mentioned_users = $query->get();
        $notified_users = $query->wherePivot('notification', 1)->get();
        if(!empty($mentioned_users)){     
            $emails = collect($mentioned_users)->filter(function($user){
                return filter_var($user->email, FILTER_VALIDATE_EMAIL);
            })->pluck('email')->toArray();             
            $board = $boardRecord;              
            
            if(!empty($board) && $board->private_flag == 1){
                $b_title = $user->name;
                
            }else{
                $b_title = $board->title;
            }                                    
            $content = $chat->message_text;
            $block_flag = false;
            $blocked_words = ['password', 'PASSWORD', 'PW', 'pw','pass','PASS', 'パスワード','ﾊﾟｽﾜｰﾄﾞ', 'パス', 'ﾊﾟｽ'];
            foreach($blocked_words as $word){
                if (str_contains($chat->message_text, $word)) { 
                    $block_flag = true;
                }
            }         
            
            $mail_payload = array(
                "b_title" => $b_title,
                "content" => $content,
                "block_flag" => $block_flag,
                "board_id" => $board->id,
                "chat_id" => $chat->id,
                "mails" => $emails,
            );                
            SendEmail::dispatchAfterResponse($mail_payload);               

            $notify_ids = $notified_users->pluck('id')->toArray();
            $members = array_map(function ($userId) {
                return (string) $userId;
            }, $notify_ids);
            if(!empty($members)){
                $deep_link = url('board/' . $boardRecord->id);
                $icon = $user->icon_path 
                    ? url("content_api/profile_icon_migrated/$user->icon_path.webp") 
                    : url("user_default_thumbnail/" . urlencode(mb_substr($user->name, 0, 1)) . "/30/000000");
                
                $badge = url('/96x96.png');
                if(!empty($boardRecord) && $boardRecord->private_flag == 1){
                    $push_title = $user->name;
                    $body = 'メッセージが届きました';
                }else{
                    $push_title = $boardRecord->title;
                    $body = $user->name . 'さんからメッセージが届きました';
                }
                $payload = [
                    "body" => $body,
                    "title" => $push_title,
                    "link" => $deep_link,
                    "members" => $members,
                    "icon" => $icon,
                    "badge" => $badge,
                    "user_id" => $user->id,
                    "user_name" => $user->name,
                    "message" => $chat->message_text,
                ];
                SendNotification::dispatchAfterResponse($payload);
                return $payload;
            }
            return null;
            
        }
        return null;
    }
        
    public function chatDelete(Request $request){
        $active_user = $this->active_user();
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $auth_user_id = $active_user->id;
        $chat_record = messageRecord::findOrFail($request->id);
        if($auth_user_id !== $chat_record->user_id){
            throw ValidationException::withMessages(['message' => 'sufficientAdministrativePermission']);
        }
        $chat_record->reactedUsers()->detach();
        $chat_record->checkUsers()->detach();
        if ($chat_record->draft_flag === 1) {
            $chat_record->deleted_flag = 1;
            $chat_record->save();
        }
        $chat_record->delete();            
        $files = messageFile::where('message_id', '=', $chat_record->id)->get();
        if($files){                
            foreach($files as $file){             
                $path = 'shared_files/' . $chat_record->record_id . '/' . $file->id . '_' . $file->user_id . '_' . $chat_record->id . '.' . $file->extension;
                Storage::disk('local')->delete($path);
                $file->delete();
            }               
            
        }          
        $mutatedMessage = $this->message_refresh($chat_record);
        return response()->json($mutatedMessage);
        
         
    }
    public function draftSend(Request $request) {
        $request->validate([
            'id' => 'required',
        ]);
        $chat_record = messageRecord::findOrFail($request->id);
        
        if(!empty($chat_record) && $chat_record->draft_flag === 1 && $request->draft_flag === 0) {
            $socket = [];
            $data = [];
            
           
                $new_chat_record = $chat_record->replicate();
                $chat_record->deleted_flag = 1;
                $chat_record->save();
                $chat_record->delete();
                $new_chat_record->reserved_at = null;
                $new_chat_record->draft_flag = $request->draft_flag;
                $new_chat_record->created_at = now();
                $new_chat_record->save();
                $path_shared_files = 'shared_files/' . $new_chat_record->record_id;              
                foreach ($chat_record->message_files as $file) {
                    $newFile = new messageFile;
                    $newFile->board_id =  $new_chat_record->record_id;
                    $newFile->message_id =  $new_chat_record->id;
                    $newFile->name = $file->name;
                    $newFile->extension = $file->extension;                    
                    $newFile->user_id = $file->user_id;
                    $newFile->mime_type = $file->mime_type;  
                    $newFile->size = $file->size;      
                    $newFile->save(); 
                    $origin_path = 'shared_files/' . $file->board_id . '/' . $file->id. '_' . $file->user_id . '_' . $file->message_id . '.' . $file->extension;
                    $msg_file_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
                    Storage::disk('local')->move($origin_path, $path_shared_files . '/' . $msg_file_path);
                }
                $chat_record->message_files()->delete();
                // return response()->json($new_chat_record->board_record);
                $this->mentionAndNotify($new_chat_record->board_record, $new_chat_record->user, $new_chat_record);
                boardToUser::where('record_id', $new_chat_record->record_id)
                    ->where('user_id', $new_chat_record->user_id)
                    ->update(["last_message" => $new_chat_record->id]);
                
                $related_members = boardToUser::where('record_id', $new_chat_record->record_id)
                    ->where('deleted_status', 0)
                    ->where('user_id', '!=', $new_chat_record->user_id)
                    ->pluck('user_id');
                
                $messageRecord = $this->get_messages(new Request([
                    'page_index' => 1,
                    'record_id' => $new_chat_record->record_id,
                    'message_id' => $new_chat_record->id,
                    'override_user' => $request->user ?? null
                ]));
                
                $socket[] = ["event" => "board:{$new_chat_record->record_id}", "data" => []];
                $socket[] = ["event" => 'refresh:badge', "data" => $related_members];
                $socket[] = ["event" => 'refresh:board', "data" => $related_members];
                
                $data = [
                    "success" => true,
                    "u_id" => $new_chat_record->user_id,
                    "data" => $new_chat_record,
                    "socket" => $socket,
                    "message" => $messageRecord?->original
                ];
                
            
            
            $mutatedMessage = $this->message_refresh($new_chat_record);
            return response()->json($mutatedMessage);
                 
           
        }
    }
    public function set_message_schedule(Request $request) {
        $request->validate([
            'id' => 'required',
            'reserved_at' => 'required'
        ]);
        $message = messageRecord::findOrFail($request->id);

        if (!empty($message)) {
            $message->reserved_at = $request->reserved_at;
            $message->save();
        }
        return response()->json('success', 200);
    }
    public function chatEdit(Request $request){
        $active_user = $this->active_user();
        $validatedData = $request->validate([
            'id' => 'required',
            'message' => 'required'
        ]);
        $auth_user_id = $active_user->id;
        $chat_record = messageRecord::findOrFail($request->id);
        if($auth_user_id !== $chat_record->user_id){
            throw ValidationException::withMessages(['message' => 'sufficientAdministrativePermission']);
        }
        
        
        if(!empty($chat_record)){
            $chat_record->message = $request->message;
            $chat_record->message_text = strip_tags(htmlspecialchars_decode($request->message)); 
            $chat_record->emoji_flag = $this->containsOnlyEmojis($request->message);
            $chat_record->save();
        }
        $mutatedMessage = $this->message_refresh($chat_record);
        return response()->json($mutatedMessage);
        
    }
    public function checkSend(Request $request){

        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;        
        $message_record = messageRecord::findOrFail($request->message_id);
        $checkUser = $message_record->checkUsers()->where('user_id', $active_user->id)->first();
        if ($checkUser) {
            
            $checkUser->pivot->checked = true;
            $checkUser->pivot->save();
        } 
        $related_members = [];
        $related_members[] = $auth_user_id;
        $rebound = array(
            "board_members" => $related_members
        );
        // event(new MessageSent($rebound));
        $mutatedMessage = $this->message_refresh($message_record);
        return response()->json([
            "socket" => $rebound,
            "message" => $mutatedMessage
        ]);
        

    }
    public function update_board_badge(Request $request){
        $request->validate([
            'board_id' => 'required',
        ]);
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;

        $updateLastMessage = boardToUser::where('record_id','=', $request->board_id)->where('user_id','=', $auth_user_id)->first();
        if(!empty($updateLastMessage)){
            $lastMessageId = messageRecord::where('record_id', '=', $request->board_id)->orderBy('created_at', 'desc')->withTrashed()->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')->select('id')->first();
            if(!empty($lastMessageId)){
                $updateLastMessage->last_message = $lastMessageId->id;
                $updateLastMessage->save();
            }else{
                $updateLastMessage->last_act = now();
                $updateLastMessage->save();
            }           
        }
        $res = $this->get_board_badge();
        return $res;
        
        
    }
    public function get_board_badge(){       
        $linked = Auth::user()->linked()->get()->pluck('id')->toArray();
        array_push($linked, Auth::id());
        // return response()->json($linked); 
        $leavePeriod = $this->user_onleave(Auth::id());
        $list = [];
        foreach($linked as $user_id){
            $savedLastMessages = boardToUser::where('user_id', $user_id)
            ->where('deleted_status', 0)
            ->where('deleted_flag', 0)
            ->whereNull('left_at')
            ->whereHas('board', function ($q) {
                $q->where('deleted_flag', 0)->where('deleted_at', null);
            })
            ->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('on_leave', 0);
                })
                ->orWhereHas('board', function ($q) {
                    $q->where('private_flag', 1); 
                });
            })
            ->orderBy('record_id', 'desc')
            ->get();

            $result = [];
            foreach($savedLastMessages as $record){
                $last = $record->last_message;
                if(!empty($last)){
                    $unread_count = $record->messageRecords()
                    ->where(function ($query) {
                        $query->where('info_flag', '!=', 1)
                              ->where('info_flag', '!=', 2);
                    })
                    ->where('draft_flag', 0)
                    ->when($last, function ($q) use ($last) {
                        $q->where('id', '>', $last);
                    })
                    ->when($record->created_at, function ($q) use ($record) {
                        $q->where('created_at', '>=', $record->created_at);
                    })->when($leavePeriod, function ($query) use ($leavePeriod) {
                        $query->where(function ($q) use ($leavePeriod) {
                            $q->whereHas('board_record', function ($q) {
                                $q->where('private_flag', 1);
                            })
                            ->orWhereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
                        });
                    })->count();

                    if($unread_count > 0) {
                        $result[$record->record_id] = $unread_count;
                    }  
                }else{
                    if($record->last_act == null){
                        $result[$record->record_id] = 1;
                    }
                }               
                        
            }
            $data = array(
                "user_id" => $user_id,
                "list" => $result
            );
            array_push($list, $data);
        }


        return response()->json($list);       
    }
    
       
    
     
    public function pinBoard(Request $request){
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        if(!empty($auth_user_id) && !empty($request->group_id)){
            $record = boardToUser::where('record_id', '=', $request->group_id)->where('user_id', '=', $auth_user_id)->first();
            if(!empty($record)){
                if($record->pin_flag == 0){
                    $record->pin_flag = 1;
                    $record->save();
                }elseif($record->pin_flag == 1){
                    $record->pin_flag = 0;
                    $record->save();
                }
                
                return response()->json($record);
            }
        
        }
    }
    public function notification_board(Request $request) {
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;
        if(!empty($auth_user_id) && !empty($request->group_id)){
            $record = boardToUser::where('record_id', $request->group_id)->where('user_id', $auth_user_id)->first();
            if(!empty($record)){
                $record->notification = match ($record->notification) {
                    0 => 1,
                    1 => 0,
                };
                $record->save();                
                
                return response()->json($record);
            }
        
        }
    }
    public function sendMail($request){
        $active_user = $request['override_user'] ?? $this->active_user();
        $auth_user_id = $active_user->id;

        if(!empty($request['send_list']) && !empty($auth_user_id) && !empty($request['msg_id'])){
            $msg_id = $request['msg_id'];
            $content = '';
            $messageRecord1 = messageRecord::find($msg_id);
            if(!empty($messageRecord1)){                        
                $content = $messageRecord1->message_text;
            }
            $board = boardRecord::where('id', '=', $request['board_id'])->first();
            $subject = '';
            $b_title = '';
            $type = '';
            if(!empty($board) && $board->private_flag == 1){
                $b_title = $active_user->name;
                
            }else{
                $b_title = $board->title;
            }
            if($request['send_condition'] == 1)
            {
                $subject ='【確認依頼】' . $b_title;
                $type = 'confirm';
            }elseif($request['send_condition'] == 2){
                $subject = '【再確認依頼】' . $b_title;
                $messageRecord = messageRecord::find($msg_id);
                $type = 'reconfirm';
                if(!empty($messageRecord)){
                    $messageRecord->check_request_at = now();
                    $messageRecord->save();                         
                }
               
            }elseif($request['send_condition'] == 3){
                $subject ='【サイン依頼】' . $b_title;
                $type = 'sign';
            }
            $block_flag = false;
            $blocked_words = ['password', 'PASSWORD', 'PW', 'pw','pass','PASS', 'パスワード','ﾊﾟｽﾜｰﾄﾞ', 'パス', 'ﾊﾟｽ'];
            foreach($blocked_words as $word){
                if (str_contains($messageRecord1->message_text, $word)) { 
                    $block_flag = true;
                }
            }
            $mails = User::whereIn('id', $request['send_list'])->where('retire', 0)->where('on_leave', 0)->whereNotNull('email')->pluck('email')->toArray();
            foreach($mails as $to){
                if (filter_var($to, FILTER_VALIDATE_EMAIL)){
                    Mail::to($to)->send(new Confirm($b_title, $content, $block_flag, $request['board_id'], $request['msg_id'], $type));
                }
            }
     
            
            return response()->json($msg_id);   
        }
        return response()->json('error');   
    }
    public function send_reconfirm_email(Request $request){
        $mail = $this->sendMail($request);
        return $mail;
    }
    public function remindRequest(Request $request){
        $active_user = $this->active_user();
        $auth_user_id = $active_user->id;

        $message_remind = messageRemindUser::where('message_id', $request->id)->where('user_id', $auth_user_id)->first();

        if ($message_remind) {
            $message_remind->reminded = !$message_remind->reminded;
            $message_remind->save();
            return response()->json($message_remind->reminded ? true : false);
        } else {
            $remind_user = new messageRemindUser;
            $remind_user->message_id = $request->id;
            $remind_user->user_id = $auth_user_id;
            $remind_user->reminded = 1;
            $remind_user->save();
            return response()->json($remind_user->reminded ? true : false);
        }

        
    }    
    public function checkRequest(Request $request){

        $message = messageRecord::findOrFail($request->msg_id);
        if($request->type == 'confirm'){
            
            $message->check_flag = 1;
            $message->check_request_at = Carbon::now();
            $message->checkUsers()->sync($request->users);
            $message->save();
            $board = boardRecord::where('id', $message->record_id)->first(); 
            $req = [
                "send_list" => $request->users,
                "board_id" => $board->id,
                "msg_id" => $message->id,
                "send_condition" => 1,              
                "override_user" => $request->override_user
            ];
            $this->sendMail($req);
            
        }else if($request->type == 'sign'){
            $path_shared_files = "shared_files/{$request->board_id}";
            $messageFile = messageFile::findOrFail($request->msg_file_id);
            $messageFile->sign_flag = 1;
            $messageFile->signUsers()->sync($request->users);
            $messageFile->save();
            $record_id = $messageFile->board_id;
            $message_id = $messageFile->message_id;
            $req = [
                "send_list" => $request->users,
                "board_id" => $record_id,
                "msg_id" => $message_id,
                "send_condition" => 3,              
            ];
            $this->sendMail($req);
            if($request->prepare == true){
                $original_name = $messageFile->name;
                $messageFile->multiple_flag = 1;
                $existed_path = "{$messageFile->id}_{$messageFile->user_id}_{$messageFile->message_id}.{$messageFile->extension}";
                
                $other_users = $request->users;
                foreach($other_users as $user){
                    $messageFile_loop = new messageFile;
                    $messageFile_loop->message_id = $messageFile->message_id;
                    $messageFile_loop->user_id = $user;
                    $messageFile_loop->mime_type = $messageFile->mime_type;
                    $messageFile_loop->extension = $messageFile->extension;
                    $messageFile_loop->board_id = $messageFile->board_id;
                    $messageFile_loop->size = $messageFile->size;
                    $other_user = User::findOrFail($user);
                    $other_name = str_replace(' ', '', $other_user->name);
                    $messageFile_loop->name = $other_name . '_' . $original_name;
                    $messageFile_loop->sign_flag = 1;
                    $messageFile_loop->multiple_flag = 2;
                    $messageFile_loop->original_file_id = $messageFile->id;
                    $messageFile_loop->save();
                    $messageFile_loop->signUsers()->attach($user);
                    $new_path = "{$messageFile_loop->id}_{$messageFile_loop->user_id}_{$messageFile_loop->message_id}.{$messageFile_loop->extension}";
                    File::copy(storage_path('app/') . $path_shared_files . '/' . $existed_path , storage_path('app/') . $path_shared_files . '/' . $new_path ); 
                }
                $messageFile->save();
                return response()->json($messageFile);
            }
        }
        $mutatedMessage = $this->message_refresh($message);

        return response()->json($mutatedMessage);
            
        
    }
    public function sendReaction(Request $request){
        $active_user = $this->active_user();
        $message = messageRecord::with('reactedUsers')
        ->with('checkedUsers')
        ->with('uncheckedUsers')->findOrFail($request->id);
        if ($message->reactedUsers()->where('user_id', $active_user->id)->exists()) {
            $message->reactedUsers()->detach($active_user->id);            
        } else {
            $message->reactedUsers()->attach($active_user->id);            
        }

        $mutatedMessage = $mutatedMessage = $this->message_refresh($message);
        return response()->json($mutatedMessage);
    }    
   
    
    public function messageSearch(Request $request){

        $request->validate([
            'keyword' => 'required|min:2',
        ], [
            'keyword.required' => '検索キーワードを入力してください',
            'keyword.min' => '検索キーワードは2文字以上で入力してください',
        ]);
        $rawKeyword = $request->keyword;

        $normalizedKeyword = str_replace('＠', '@', $rawKeyword);

        if (preg_match('/@(\w+)/', $normalizedKeyword, $matches)) {
            $processedKeyword = "[To:{$matches[1]}:]";
        } else {
            $processedKeyword = $normalizedKeyword; 
        }        
        
        $keywords = preg_split('/[ \x{3000}]+/u', $processedKeyword);
        $active_user = $this->active_user();
        if($request->private_flag && $request->record_id){
            $list = boardRecord::where('id', $request->record_id)->whereHas('board_to_users', function($q) use( $active_user ){
                $q->where('user_id', $active_user->id)->where('deleted_status', '=', 0);
            })->get();
        }else{
            $list = boardRecord::whereHas('board_to_users', function($q) use($active_user){
                $q->where('user_id', $active_user->id)->where('deleted_status', '=', 0);
            })->get();
        }        
        $leavePeriod = $this->user_onleave($active_user->id);
        $result = new \Illuminate\Database\Eloquent\Collection;
        foreach($list as $board){
            if($board->private_flag == 0 || $board->private_flag == 3){
                $selfcheck = $board->board_to_users()->where('user_id', $active_user->id)->first();
                $time_limit = $selfcheck->created_at;
                $messageFrom = $board->message_from;     
                $time_condition = $messageFrom == 0 && $time_limit;   
                $view_from = $selfcheck->view_from;
                $comment_list_pre = messageRecord::when($time_condition && !$view_from, function ($query) use($time_limit) {
                    $query->where('created_at', '>=',  $time_limit );
                })
                ->when($view_from, function ($query) use ($view_from) {
                    $query->where('created_at', '>=', $view_from);
                })
                ->when($leavePeriod && $board->private_flag != 3, function ($query) use ($leavePeriod) {
                    $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
                })
                ->where('record_id', $board->id)
                ->where(function($query) use($keywords){
                    foreach($keywords as $keyword){
                        $query->where('message_text', 'LIKE', "%$keyword%");
                    }
                })
                // ->where('message_text', 'LIKE', '%' . $request->keyword . '%')
                ->whereHas('user')
                ->with('user')
                ->latest('created_at')
                ->select('id', 'user_id', 'record_id', 'created_at', 'message', 'message_text')
                ->get();
                $result = $result->merge($comment_list_pre);
            }else if($board->private_flag == 1){
                $selfcheck = boardToUser::where('record_id', '=', $board->id)->where('user_id', '=', $active_user->id)->first();
                if($selfcheck->created_at){
                    $comment_list_pre = messageRecord::where('record_id', $board->id)
                    ->where('created_at', '>=',  $selfcheck->created_at)->where('message_text', 'LIKE', '%' . $request->keyword . '%')
                    ->whereHas('user')->with('user')
                    ->latest('created_at')
                    ->select('id', 'user_id', 'record_id', 'created_at', 'message', 'message_text')
                    ->get();
                    $result = $result->merge($comment_list_pre);
                }else{                   
                    $comment_list_pre = messageRecord::where('record_id', $board->id)
                    ->where(function($query) use($keywords){
                        foreach($keywords as $keyword){
                            $query->where('message_text', 'LIKE', "%$keyword%");
                        }
                    })
                    ->whereHas('user')
                    ->with('user')
                    ->latest('created_at')
                    ->select('id', 'user_id', 'record_id', 'created_at', 'message', 'message_text')
                    ->get();
                    $result = $result->merge($comment_list_pre);
                }
            }
        }
        if($request->keyword){
            $history = searchHistoryRecord::where('user_id', $active_user->id)->where('content', $request->keyword)->first();
            if(!$history){
                $new_history = new searchHistoryRecord;
                $new_history->content = $request->keyword;
                $new_history->user_id = $active_user->id;
                $new_history->save();
            }else if($history){
                $history->touch();
            }
        }
        $per_page = 10;
        $div = $result->sortByDesc('created_at')->forPage($request->index, $per_page);
        $board_ids = $result->pluck('record_id')->toArray();
        $board_ids_unique = array_unique($board_ids);
        $indexed = array_count_values($board_ids);
        $t_list = [];
        foreach($div as $d){
            $t_list[] = $d;
        }
        $id_list = [];
        foreach($board_ids_unique as $id){
            $data01 = [
                "id" => $id,
                "occurence" => $indexed[$id]
            ];
            $id_list[] = $data01;
        }
        $pages = ceil(count($result)/$per_page);
        $backData = [
            "total" => count($result),
            "currentPage" => $request->index,
            "totalPage" => $pages,
            "data" => $t_list,
            "board_list" => $id_list
        ];
        return response()->json($backData);
    }
    public function getTargetMessage(Request $request){
        $active_user = $this->active_user();
        $target = messageRecord::findOrFail($request->id);
        $board = boardRecord::findOrFail($target->record_id);
        $board_user = boardToUser::where('record_id', $target->record_id)->where('user_id', $active_user->id)->first();
        if(empty($board_user)){
            throw ValidationException::withMessages(['message' => 'チャットメンバーではありません。']); 
        }
        $time_limit = $board_user->created_at;
            $messageFrom = $board->message_from;     
            $time_condition = $messageFrom == 0 && $time_limit;   
            $view_from = $board_user->view_from;
            $leavePeriod = $this->user_onleave($active_user->id);
            $pre = messageRecord::withTrashed()->where('record_id', '=', $target->record_id)->orderBy('created_at', 'desc')
            ->when($time_condition && !$view_from, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->when($view_from, function ($query) use ($view_from) {
                $query->where('created_at', '>=', $view_from);
            })
            ->when($leavePeriod && ($board->private_flag != 3 || $board->private_flag != 1), function ($query) use ($leavePeriod) {
                $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
            })
            ->where('created_at', '<', $target->created_at)            
            ->with([
                'user',
                'message_files.unsignedUsers',
                'message_files.signedUsers',
                'message_reply',
                'message_quot',
                'message_forward',
                'reactedUsers',
                'checkedUsers',
                'uncheckedUsers',
                'emotedUsers',
                'messageRemindUsers',
                'task'
            ])
            ->take(14)
            ->get();

            $next = messageRecord::withTrashed()->where('record_id', '=', $target->record_id)->orderBy('created_at', 'asc')->where('created_at', '>', $target->created_at)
            ->when($time_condition && !$view_from, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->when($view_from, function ($query) use ($view_from) {
                $query->where('created_at', '>=', $view_from);
            })
            ->when($leavePeriod && ($board->private_flag != 3 || $board->private_flag != 1), function ($query) use ($leavePeriod) {
                $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
            })
            ->with([
                'user',
                'message_files.unsignedUsers',
                'message_files.signedUsers',
                'message_reply',
                'message_quot',
                'message_forward',
                'reactedUsers',
                'checkedUsers',
                'uncheckedUsers',
                'emotedUsers',
                'messageRemindUsers',
                'task'
            ])
            ->take(15)->get()->reverse()->values();
    
            $target_q = messageRecord::withTrashed()->where('id', '=', $request->id)
            ->when($time_condition && !$view_from, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->when($view_from, function ($query) use ($view_from) {
                $query->where('created_at', '>=', $view_from);
            })
            ->when($leavePeriod && ($board->private_flag != 3 || $board->private_flag != 1), function ($query) use ($leavePeriod) {
                $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
            })
            ->with([
                'user',
                'message_files.unsignedUsers',
                'message_files.signedUsers',
                'message_reply',
                'message_quot',
                'message_forward',
                'reactedUsers',
                'checkedUsers',
                'uncheckedUsers',
                'emotedUsers',
                'messageRemindUsers',
                'task'
            ])
            ->get();
            $united = $next->merge($target_q)->merge($pre);
            
            return response()->json($united);
        

    }
    public function getAppend(Request $request){
        $active_user = $this->active_user();
        $last_message = messageRecord::withTrashed()->findOrFail($request->last_message_id);
        $targetBoard = boardRecord::findOrFail($last_message->record_id);
        $board_user = boardToUser::where('record_id', $targetBoard->id)->where('user_id', $active_user->id)->first();
        $time_limit = $board_user->created_at;
        $messageFrom = $targetBoard->message_from;     
        $time_condition = $messageFrom == 0 && $time_limit;
        $view_from = $board_user->view_from;
        $leavePeriod = $this->user_onleave($active_user->id);
        if($request->direction === 'down'){
            $bottom_messages = messageRecord::withTrashed()->where('record_id', '=', $last_message->record_id)
            ->where('created_at', '>', $last_message->created_at)
            ->when($time_condition && !$view_from, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->when($view_from, function ($query) use ($view_from) {
                $query->where('created_at', '>=', $view_from);
            })
            ->when($leavePeriod && ($targetBoard->private_flag != 3 || $targetBoard->private_flag != 1), function ($query) use ($leavePeriod) {
                $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
            })
            ->with([
                'user',
                'message_files.unsignedUsers',
                'message_files.signedUsers',
                'message_reply',
                'message_quot',
                'message_forward',
                'reactedUsers',
                'checkedUsers',
                'uncheckedUsers',
                'emotedUsers',
                'messageRemindUsers',
                'task'
            ])
            ->take(30)->get()->reverse()->values();


        }else if($request->direction === 'up'){
            $bottom_messages = messageRecord::withTrashed()->where('record_id', '=', $last_message->record_id)
            ->where('created_at', '<', $last_message->created_at)->orderBy('created_at', 'desc')
            ->when($time_condition && !$view_from, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->when($view_from, function ($query) use ($view_from) {
                $query->where('created_at', '>=', $view_from);
            })
            ->when($leavePeriod && ($targetBoard->private_flag != 3 || $targetBoard->private_flag != 1), function ($query) use ($leavePeriod) {
                $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
            })
            ->with('user')
            ->with('message_files')
            ->with('message_reply')
            ->with('message_quot')
            ->with('message_forward')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->take(30)->get();
        }
        return response()->json($bottom_messages);
    }
    public function getInstantUser(Request $request){
        $today = Carbon::now()->format('Y-m-d');
        $user = User::where('id', $request->id)->orWhere('name', $request->name)->where('id', '>', 105)->where('retire', 0)->with('today_weather')->select('id', 'name', 'phone_number', 'work_email', 'icon_path')->first();
        if($user){

            $res = [
                "found" => true,
                "user" => $user,
            ];
            return response()->json($res);
        }else{
            $res = [
                "user" => null,
                "found" => false
            ];
            return response()->json($res);
        }
        
    }

 
    public function cdnDocs(Request $request){     
        if($request->user_id){
            $user = User::findOrFail($request->user_id);
            if($request->keyword == $user->file_key){
                try {
                    $p1 = storage_path('app') . '/' . 'shared_files/'. $request->board_id . '/' . $request->path;  
                    return response()->file($p1);
                } catch (FileNotFoundException $exception) {
                    abort(404);
                }
            }else{
                abort(404);
            }
        }
        
        

    }


    public function getIconUp(Request $request ){
        // $active_user = $this->active_user();
        // $auth_user_id = $active_user->id;
        // if($request->hasFile('file')) {
        //     $file_path = date("YmdHis") . md5(uniqid());
        //     $file_extension = $request->file('file')->getClientOriginalExtension();
        //     $mime_type = $request->file('file')->getMimeType();
        //     $mime_type_array = explode('/',$mime_type);
        //     $file_type = $mime_type_array[0];
        //     $file_size = $request->file('file')->getSize();     

        //     $fileRecord = new Icons;
        //     $fileRecord->mime_type = $file_type;
        //     $fileRecord->extension = 'jpg';
        //     $fileRecord->user_id = $auth_user_id;
        //     $fileRecord->use_of = 'board';
        //     $fileRecord->save();
        //     $path = '/board_icon';
        //     $set_path = 'board'. '_' . $fileRecord->id  . '.jpg';
        //     $img = Image::read($request->file('file'));
        //     File::isDirectory(storage_path('app') . '/' . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                

        //     $save_path = (storage_path('app') . '/' . $path . '/' . $set_path);
        //     if($file_size > 2000000){
        //         $img->toJpeg(30)->save($save_path);
        //     }else{
        //         $img->save($save_path);  
        //     }         
        //     $ret = array ( 
        //         "set_path" =>  $set_path,
        //         "icon_path" => $fileRecord->id
        //     );
        //     return response()->json($ret);       
        // }
        if($request->hasFile('file')) {   
            $set_path = $this->sharedService->path_generator();
            $img = Image::read($request->file('file'))->scaleDown(200, 200);
            File::isDirectory(storage_path('app/board_icon_migrated')) or File::makeDirectory(storage_path('app/board_icon_migrated'), 0755, true, true);               
            $img->toWebp()->save(storage_path('app/board_icon_migrated/' . $set_path . '.webp'));               
            return response()->json($set_path);       
        }
        throw ValidationException::withMessages(['message' => 'ファイルは無効です。']);


    }   
   
    public function setAdminRole(Request $request){
        $active_user = $this->active_user();
        $validatedData = $request->validate([
            'record_id' => 'required',
            'user_id' => 'required',
            'flag' => 'required'
        ]);
        $checkBoard = boardRecord::findOrFail($request->record_id);
        $checkAdmin = $checkBoard->board_to_users()->where('user_id', $active_user->id)->where('admin_flag', 1)->exists();
        if($checkAdmin || $request->from_project){
           
            if($request->flag == 0){
                $countAdmins = $checkBoard->board_to_users()->where('admin_flag', 1)->count();
                if($countAdmins == 1){
                    throw ValidationException::withMessages(['message' => '管理者は少なくとも1人が必須です。']);
                }
            }
            
            $targetUser = $checkBoard->board_to_users()->where('user_id', $request->user_id)->first();
            if($targetUser){
                $targetUser->update(['admin_flag' => $request->flag]);
                return response()->json("complete", 200);
            }
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
        throw ValidationException::withMessages(['message' => 'commonError']);
    }
    public function removeGroupMember(Request $request){
        $active_user = $this->active_user();
        $validatedData = $request->validate([
            'record_id' => 'required',
            'user_id' => 'required',
        ]);
        $checkBoard = boardRecord::findOrFail($request->record_id);
        $checkAdmin = $checkBoard->board_to_users()->where('user_id', $active_user->id)->where('admin_flag', 1)->exists();
        if($checkAdmin){          
            
            
            $targetUser = $checkBoard->board_to_users()->where('user_id', $request->user_id)->first();
            if($targetUser){
                $targetUser->delete();
                $newUserRecord = User::find($request->user_id);
                if($newUserRecord){
                    
                    $createInfo = $this->sharedService->createInfoMessage($newUserRecord->name, $checkBoard->id, 'removed_members', $active_user->id);  
                    
                }
                $related_id = $checkBoard->board_to_users()->pluck('user_id')->toArray();
                $socket = array();
                array_push($socket, ["event" => 'refresh:badge', "data" => $related_id]);
                array_push($socket, ["event" => 'refresh:board', "data" => $related_id]); 
                // event(new MessageSent($rebound));
                return response()->json([
                    "socket" => $socket
                ]);
            }
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
        throw ValidationException::withMessages(['message' => 'commonError']);
    }
    public function groupAddMember(Request $request){
        $active_user = $this->active_user();
        $validatedData = $request->validate([
            'record_id' => 'required',
            'user_id' => 'required',
        ]);
        $checkBoard = boardRecord::findOrFail($request->record_id);
        $checkAdmin = $checkBoard->board_to_users()->where('user_id', $active_user->id)->exists();
        if($checkAdmin || $request->from_project){           
            
            
            $targetUser = $checkBoard->board_to_users()->where('user_id', $request->user_id)->first();
            if($targetUser){
                throw ValidationException::withMessages(['message' => '既にメンバーに追加されています。']);
            }else{
                $newUser = new boardToUser;
                $newUser->user_id = $request->user_id;
                $newUser->record_id = $request->record_id; 
                $newUser->view_from = $request->view_from; 
                $newUser->save();

                $newUserRecord = User::find($request->user_id);
                if($newUserRecord){                    
                    $createInfo = $this->sharedService->createInfoMessage($newUserRecord->name, $checkBoard->id, 'added_members', $active_user->id); 
                }
        
                $related_id = $checkBoard->board_to_users()->pluck('user_id')->toArray();
                $socket = array();
                array_push($socket, ["event" => 'refresh:badge', "data" => $related_id]);
                array_push($socket, ["event" => 'refresh:board', "data" => $related_id]); 
                // event(new MessageSent($rebound));
                return response()->json([
                    "socket" => $socket
                ]);
            }
        }
        throw ValidationException::withMessages(['message' => 'commonError']);
    }
    public function leaveBoard(Request $request){
        $active_user = $this->active_user();
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $board = boardRecord::findOrFail($request->id);
        $checkAdmin = $board->board_to_users()->where('user_id', $active_user->id)->where('admin_flag', 1)->exists();
        $checkHasOtherAdmins = $board->board_to_users()->where('user_id', '!=', $active_user->id)->where('admin_flag', 1)->exists();
        if($checkAdmin && !$checkHasOtherAdmins){
            throw ValidationException::withMessages(['message' => 'あなたはチャット管理者であるため、チャットを退出することはできません。 <br>管理者権限を別のメンバーに譲渡した後、もう一度お試しください']);
        }
        $board->board_to_users()->where('user_id', $active_user->id)->delete();
        $taskUser = taskUser::where('record_id', $board->id)->where('user_id', $active_user->id)->where('comp_flag', 0)->delete();

        $createInfo = $this->sharedService->createInfoMessage($active_user->name,$board->id, 'left_members', $active_user->id);   
        
        return response()->json("complete", 200); 

    }
    public function board_possible_users(Request $request){
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $all_users = User::where('deleted_flag', 0)
        ->where('retire', 0)
        ->whereNotIn('name', $ng_list)
        ->whereNotIn('id', $request->exclude)
        ->select('id', 'name', 'icon_path', 'icon_bg')
        ->get();
        return response()->json($all_users);
    }
    public function addable_board_members(Request $request){
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $exists = boardToUser::where('record_id', $request->record_id)->where('deleted_flag', 0)->pluck('user_id')->toArray();
        $all_users = User::where('deleted_flag', 0)
        ->where('retire', 0)
        ->whereNotIn('name', $ng_list)
        ->whereNotIn('id', $exists)
        ->select('id', 'name', 'icon_path', 'icon_bg')
        ->get();
        return response()->json($all_users);
    }
    public function pusher_auth(Request $request){
      
        $socket_id = $request['socket_id'];
        $channel_name = $request['channel_name'];
        $hash_prepare = $socket_id.'::user::{"id":"'.Auth::id().'"}';
        
        // $hash_prepare = $socket_id.':'.$channel_name;
        // $hmac = hash('sha256', $hash_prepare);

        $secret = config('app.pusher_secret');
        $key = config('app.pusher_key');
        // return response()->json($secret);


                // Your secret key
                // $secret = "7ad3773142a6692b25b8";

                // Your string to be signed
                $stringToSign = $socket_id.'::user::{"id":"'.Auth::id().'"}';
        
                // Creating the SHA256 hex digest using hash_hmac
                $signature = hash_hmac('sha256', $stringToSign, $secret);
        
                // Concatenating the result as per your Ruby code
                $auth = "{$key}:{$signature}";
        
                // Output the result
                // Log::info($auth);
        
                return response()->json(['auth' => $auth, "user_data" => '{"id":"'.Auth::id().'"}']);
    }
    public function pusher_subscribe(Request $request){
        $socket_id = $request['socket_id'];
        $channel_name = $request['channel_name'];
        // $hash_prepare = $socket_id.'::user::{"id":"'.Auth::id().'"}';
        
        $hash_prepare = $socket_id.':'.$channel_name;
        // $hmac = hash('sha256', $hash_prepare);

        $secret = config('app.pusher_secret');
        $key = config('app.pusher_key');
        
                // Creating the SHA256 hex digest using hash_hmac
                $signature = hash_hmac('sha256', $hash_prepare, $secret);
        
                // Concatenating the result as per your Ruby code
                $auth = "{$key}:{$signature}";
        
                // Output the result
                // Log::info($auth);
        
                return response()->json(['auth' => $auth]);
    }

    public function pusher_beamToken(Request $request){
        $beamsClient = new \Pusher\PushNotifications\PushNotifications(array(
            "instanceId" => config('app.pusher_instanceid'),
            "secretKey" => config('app.pusher_primary_key'),
          ));
        $userID = Auth::id(); // If you use a different auth system, do your checks here
        $userIDInQueryParam = $request['user_id'];

        if ($userID != $userIDInQueryParam) {
            return response('Inconsistent request', 401);
        } else {
            $beamsToken = $beamsClient->generateToken($userID);
            return response()->json($beamsToken);
        }
    }
    private function containsOnlyEmojis($text)
    {
        $emojiPattern = '/^[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2B50}\x{2B06}\x{2934}\x{2935}\x{2B05}\x{2194}\x{2195}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FE}\x{25FD}\x{25FC}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FE}\x{25FD}\x{25FC}\x{0023}\x{002A}\x{0030}-\x{0039}\x{20E3}\x{00A9}\x{00AE}\x{2122}\x{23F3}\x{24C2}\x{23E9}\x{23EA}\x{3030}\x{1F004}-\x{1F0CF}\x{1F170}-\x{1F251}]{1}$/u';
        return preg_match($emojiPattern, $text);
    }
    public function chat_mark_unread(Request $request){
        $request->validate([
            'message_id' => 'required',
            'board_id' => 'required',
            'user_id' => 'required'
        ]);
        $message_id = $request->message_id;
        $checkBoard = boardRecord::findOrFail($request->board_id);
      
        $previousRecord = MessageRecord::where('record_id', $request->board_id)
        ->where('id', '<', $message_id)
        ->orderByDesc('id')
        ->withTrashed()
        ->first();
        $val = $previousRecord ? $previousRecord->id : null;   
        $checkBoard->board_to_users()->where('user_id', $request->user_id)->update(['last_message' => $val]);      
      
        $related_id = $checkBoard->board_to_users()->pluck('user_id')->toArray();
        $socket = array();
        array_push($socket, ["event" => 'refresh:badge', "data" => $related_id]);
        array_push($socket, ["event" => 'refresh:board', "data" => $related_id]);  

        // event(new MessageSent($rebound));    
        return response()->json([
            "socket" => $socket
        ]);
        
    }
    public function update_view_from(Request $request) {
        $board_to_user = boardToUser::find($request->id);
        $board_to_user->view_from = $request->view_from;
        $board_to_user->save();

        return response()->json($board_to_user);
    }
    private function message_refresh(messageRecord $message){
        $message->refresh();
        $message->load([
            'user',
            'actual_sender',
            'message_files.unsignedUsers',
            'message_files.signedUsers',
            'message_reply',
            'message_quot',
            'message_forward',
            'reactedUsers',
            'checkedUsers',
            'uncheckedUsers',
            'emotedUsers',
            'messageRemindUsers',
            'task'
        ]);
        return $message;

    }

    public function send_emote(Request $request){
        $request->validate([
            'reaction' => 'required|integer',
            'id' => 'required',
        ]);
        $active_user = $this->active_user();
        $message = messageRecord::with('emotedUsers')->findOrFail($request->id);
        $existingEmote = $message->emotedUsers()->where('user_id', $active_user->id)->first();
        if ($existingEmote && $existingEmote->pivot->emote_id == $request->reaction) {
            $message->emotedUsers()->detach($active_user->id);            
        } else if($existingEmote){
            $message->emotedUsers()->updateExistingPivot($active_user->id, ['emote_id' => $request->reaction]);
        } else {
            $message->emotedUsers()->attach($active_user->id, ['emote_id' => $request->reaction]);  
            if(!$message->reactedUsers()->where('user_id', $active_user->id)->exists()){
                $message->reactedUsers()->attach($active_user->id);            
            }
        }

        $mutatedMessage = $this->message_refresh($message);
        return response()->json($mutatedMessage);
    }

}
