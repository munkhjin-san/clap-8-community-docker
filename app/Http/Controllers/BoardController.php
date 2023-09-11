<?php

namespace App\Http\Controllers;

use DB;

use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\User;
use App\Models\Icons;
use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\memoRecord;
use App\Models\appRememberRecord;
use App\Models\searchHistoryRecord;
use App\Models\taskRecord;
use App\Models\taskUser;
use App\Events\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Mail\Notify;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Pusher\Pusher;
use App\Events\MessageSent;
use App\Events\ablyMessage;
use Aws\Sns\SnsClient;
use Aws\Ses\SesClient;
use App\Services\SharedService;
use App\Models\userDetail;
class BoardController extends Controller
{
    protected $sharedService;
    
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }
    public function index(Request $request){ 


        // $accountId = 'L3W-GByoTl2mpGHNg012IA';
        // $clientId = 'z52yTnebQw6CcsupLdAOrA';
        // $clientSecret = 'cnNw9wc2LeZCRWmbfJO6xe79h1YML4F3';
        // $accountMail = 'glowd.zoom1@gmail.com';

        
        // $headers = [
        //     'Authorization' => 'Basic ' . base64_encode($clientId.':'.$clientSecret),
        //     'Content-type' => 'application/json',
        // ];

        // $url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id='.$accountId;

        // $response = Http::withHeaders($headers)->post($url);

        
        // if ($response->status() === 200) {
        //     // Check if the response has an "access_token" attribute
        //     $data = $response->json();
        //     if (isset($data['access_token'])) {
        //         // Handle the case where the response is valid
              

        //         $access_token = $data['access_token'];
        //         // return $access_token;
        //         $zoom_url = 'http://api.zoom.us/v2/users/glowd.zoom1@gmail.com/meetings';
        //         $data_to_zoom_api = array(
        //             'topic' => 'Tumur meeting 1',
        //             'type' => '2',
        //             'duration' => '60',
        //             'start_time' => '2023-09-05T20:02:00Z',
        //             'timezone' => 'Asia/Tokyo',
        //             //20230531 ここ編集
        //             'default_password' => 'true',
        //             'settings' => array(
        //               'use_pmi' => 'false'
        //             )
        //         );
        //         $headers_create = [
        //             'Authorization' => 'Bearer ' . $access_token,
        //             'Content-type' => 'application/json',
        //             // 'content' => json_encode($data_to_zoom_api)
        //         ];

        //         $create = Http::withHeaders($headers_create)->post($zoom_url);

        //         return $create->json();
        //     } else {
        //         // Handle the case where the "access_token" attribute is missing
        //         return response()->json(['error' => 'Access token not found in the response'], 400);
        //     }
        // } else {
        //     // Handle the case where the response status is not 200
        //     return response()->json(['error' => 'Invalid response status'], $response->status());
        // }
        // return 'error';
        return view('board');
    } 
    public function getPossibleMembers(Request $request) {  
        // $all_users = User::where('id', '!=', Auth::id())
        // ->select('id AS value', 'name AS label')
        // ->get();
        $all_users = Auth::user()->friends;
        $friends = [];
        foreach($all_users as $user){
            $friends[] = [
                "value" => $user->id,
                "label" => $user->name,
                "a_path" => $user->a_path,
                "a_version" => $user->a_version
            ];
        }
        return response()->json($friends);
    }
      
    //表示処理
    public function getAllMessage(Request $request) {        
        //ログインユーザー関連
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        //無限スクロール用


        //最新のレコードID取得
        // $first_record = boardRecord::orderBy('created_at', 'desc')->first();
        //無限スクロール用一覧取得
        $block_list = Auth::user()->blockedUsers()->pluck('id')->toArray();
        
        $list = boardRecord::whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::id())->where('deleted_status', 0);
        })->with('user')
        ->with(['icons' => function($q){
            $q->select('id','extension');
        }])->orderBy('updated_at', 'desc')
        ->get()
        ->reject(function ($item) use($block_list) {     
            return $item->private_flag == 1 && in_array($item->board_to_users()->where('user_id', '!=', Auth::id())->first()->user_id, $block_list);
        })
        ->values();


        $list->map(function ($item) use($list) {            
            if($item->private_flag == 0 || $item->private_flag == 3){
                $message = messageRecord::where('record_id', $item->id)->latest('created_at')->select('id', 'message', 'message_text', 'record_id', 'info_flag')->with('message_files')->first();
                $item['last_message'] = $message;
                $item->load(['board_to_users' => function($q){
                    $q->whereHas('user')
                    ->with('user');
                }]);
            }else if($item->private_flag == 1){
                $selfcheck = boardToUser::where('record_id', '=', $item->id)->where('user_id', '=', Auth::id())->first();
                
                $correspond = $this->sharedService->getUserState($item->board_to_users()->where('user_id', '!=', Auth::id())->first()->user_id, Auth::user()); 
                
                if($selfcheck->joined_at){
                    $item['deleted'] = 'yes';
                    $message = messageRecord::where('record_id', $item->id)->where('created_at', '>=',  $selfcheck->joined_at)->latest('created_at')->with('message_files')->select('id', 'message', 'message_text', 'record_id', 'info_flag')->first();
                    $item['last_message'] = $message;
                    
                }else{
                    $message = messageRecord::where('record_id', $item->id)->latest('created_at')->select('id', 'message', 'message_text', 'record_id', 'info_flag')->with('message_files')->first();
                    $item['last_message'] = $message;
                }
                $item->load(['board_to_users' => function($q){
                    $q->with('user');
                }]);
                
                $item["is_friend"] = $correspond ? $correspond["is_friend"] : false;
                $item["is_blocked"] = $correspond ? $correspond["is_blocked"] : false;
                $item["is_blocked_by"] = $correspond ? $correspond["is_blocked_by"] : false;
                $item["is_waiting"] = $correspond ? $correspond["is_waiting"] : false;
                
            }else{
                $item->load(['board_to_users' => function($q){
                    $q->with('user');
                }]);
            }
            return $item;
        });

        return response()->json($list);
        
    }
    public function postRestoreMessage(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $user = boardToUser::where('record_id', '=', $request->id)->where('user_id', '=', $auth_user_id)->first();
        if($user && $user->deleted_status == 1){
            $user->deleted_status = 0;
            $user->save();
            $board = boardRecord::find($request->id);
            $board->touch();
        }
        return response()->json($request);
    }    
    public function postAddMessage(Request $request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
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
                    ->whereHas('board_to_users', function($q){
                        $q->where('user_id', '=', Auth::user()->id)->withTrashed();
                    })->whereHas('board_to_users', function($q)use($correspondId){
                        $q->where('user_id', '=', $correspondId)->withTrashed();
                    })->first();


                    if(!empty($checkCurrentBoard)){ 
                        $restoreUsers = $checkCurrentBoard->board_to_users();
                        if(!empty($restoreUsers)){
                            $restored = false;
                            foreach($restoreUsers as $restoreUser){
                                
                                if($restoreUser->deleted_status){
                                    $restored = true;
                                    $restoreUser->deleted_status = null;
                                    $restoreUser->delete();
                                }
                                
                            }
                            $arr = [
                                "restored" => $restored,
                                "message" => "existAndAccessable",
                                "success" => true,
                                "data" => $checkCurrentBoard
                            ];   
                            $checkCurrentBoard->touch(); 
                            return response()->json($arr);
                        } 
                    }
                $defaultTitle = 'NoTitle';                   
            }            
            if($usersCount > 1 && empty($request->title)){
                $arr = [
                    "message" => "titleNeeded",
                    "success" => false
                ];
                return response()->json($arr);
            }
            
            $board = new boardRecord;
            $board->user_id = $auth_user_id;
            $board->private_flag = $request->private_flag;
            if($request->private_flag == 0){
                $board->able_join = $request->able_join;
                $board->message_from = $request->message_from;
            }else{
                $board->able_join = 2;
                $board->message_from = 0;
            }
            


            
            if($defaultTitle == null){
                $board->title = $request->title;
            }elseif($defaultTitle == 'NoTitle'){
                $board->title = $defaultTitle;
            }    
            $board->last_activity = now();
            $board->save();           
            
            $new_members = [];

            $to_users = $request->to_users;
            array_unshift($to_users, $auth_user_id);
            foreach($to_users as $to_user){
                $boardToUser = new boardToUser;
                $boardToUser->record_id = $board->id;
                $boardToUser->user_id = $to_user;    
                $boardToUser->joined_at = now();
                $boardToUser->invited_at = now();
                $boardToUser->invited_by = Auth::id();
                if($to_user == $auth_user_id){
                    $boardToUser->admin_flag = 1;
                    $boardToUser->member_status = 1;
                }                
                $boardToUser->save();
                $initialMember = User::where('id', $to_user)->select('id', 'name')->first();
                if($initialMember){
                    $new_members[] = $initialMember->name;
                }

            }            
           
            if(empty($file_id_array) && $request->private_flag !== 1){
                try {
                    $createIcon = $this->sharedService->createBoardDefaultIcon($board, Auth::id());             
                   
                    if ($createIcon) {
                        $board->a_version = 0;
                        $board->save();
                    } else {
                        $board->delete();
                        throw ValidationException::withMessages(['message' => 'Icon create failed.']);
                    }   
                } catch (\Exception $e) {           
                    $board->delete();       
                    throw ValidationException::withMessages(['message' => 'Icon create failed.']);
                }               

            }
            if($request->private_flag == 0){

                $type = 'board_qr_code';
                try {
                    $newQrCode = $this->sharedService->newUserQrCode($type, $board->id, null);
                    if($newQrCode){
                        $board->update(['q_token' => $newQrCode]);
                    }
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['message' => 'commonError']);
                }

                
            } 
            if(!empty($file_id_array)){
                $board->icon_id = $request->icon_id;
                $board->a_version = $board->a_version + 1;
                $board->save();
            }       
            if($request->private_flag == 0){
                $createInfo = $this->sharedService->createInfoMessage($new_members, $board->id, 'invited_members', Auth::id());  
            }
            
            $arr = [
                "message" => "success",
                "success" => true,
                "data" => $board
            ];   
            $related_id = boardToUser::where('record_id', '=', $board->id)->pluck('user_id');
            $rebound = array(
                "new_board_members" => $related_id->toArray()
            );
            event(new MessageSent($rebound));
            return response()->json($arr);
            
        }else{
            $arr = [
                "message" => "empty",
                "success" => false
            ];   
            return response()->json($arr);

        }

    }
    //編集処理
    public function postEditMessage(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        
            $auth_user = Auth::user();
            $auth_user_id = Auth::id();
        
            $rec_id = $request->id;

            $board = boardRecord::findOrFail($request->id);
            $checkAdmin = $board->board_to_users()->where('user_id', Auth::id())->where('admin_flag', 1)->exists();
            if(!$checkAdmin){
                throw ValidationException::withMessages(['message' => 'Sufficient administrative permission.']);
            }
            $update_icon = false;
            if($request->icon_delete_flag == 1 || ($request->title !== $board->title && empty($request->icon_id) && $board->a_version == 0)){
                $update_icon = true;
            }
            $board->title = $request->title; 
            $board->able_join = $request->able_join;
            $board->message_from = $request->message_from;
            if(!empty($request->icon_id)){
                if($board->icon_id){
                    $rmv = Icons::where('record_id', '=', $board->id)->where('use_of', '=', 'board')->get();
                    if($rmv){
                        foreach($rmv as $del){
                            Storage::disk('s3')->delete('board_icon/board_' . $del->id . '.' . $del->extension);
                            $del->delete();
                        }
                                
                    }  
                }
                $board->icon_id = $request->icon_id;
                $board->a_version = $board->a_version + 1;
                $icon = Icons::findOrFail($request->icon_id)->update(['record_id' => $board->id]);
            }else{   
                if($update_icon){                             
                                 
                    try {
                        $createIcon = $this->sharedService->createBoardDefaultIcon($board, Auth::id());             
                       
                        if ($createIcon) {
                            $board->a_version = 0;
                            $board->save();
                        } else {
                            throw ValidationException::withMessages(['message' => 'Icon create failed.']);
                        }   
                    } catch (\Exception $e) {          
                        throw ValidationException::withMessages(['message' => 'Icon create failed.']);
                    }    
                }
            }
             
            $board->timestamps = false;
            $board->last_activity = now();
            $board->save();       
            $related_id = boardToUser::where('record_id', '=', $request->id)->pluck('user_id');
            $rebound = array(
                "new_board_members" => $related_id->toArray()
            );
            event(new MessageSent($rebound));
            return response()->json("saved");         


    }
    public function postDeleteMessage(Request $request){
        
        if(!empty($request)){
            
                $board = boardRecord::findOrFail($request->id);

                if(!empty($board)){
                    if($board->private_flag == 0){
                        $admin_access = $board->board_to_users()->where('user_id', $request->user()->id)->where('admin_flag', 1)->exists();
                        if(!$admin_access){
                            throw ValidationException::withMessages(['message' => 'Sufficient administrative permission.']);
                        }
                        $createIcon = $this->sharedService->removeBoard($board);     
                        return response()->json($createIcon);
                    }else if($board->private_flag == 1){
                        $member_access = $board->board_to_users()->where('user_id', $request->user()->id)->first();
                        if(!empty($member_access)){
                            $member_access->deleted_status = 1;
                            $member_access->save();
                            return response()->json('success');
                        }
                    }
                }

                // if(!empty($board) && $board->private_flag == 0){
                //     $board->delete();
                //     $board->save();
                //     $users = boardToUser::where('record_id', '=', $request->id)->get();
                //     foreach( $users as $user ){
                //         $user->delete();
                //         $user->save();
                //     }
                    
                //     //corresponding message delete 
                //     return response()->json('deleted');
                // }elseif(!empty($board) && $board->private_flag == 1){
                //     $user = boardToUser::where('record_id', '=', $request->id)->where('user_id', '=', $auth_user_id)->first();
                //     $user->joined_at = now();
                //     $user->deleted_status = 1;
                //     $user->save();
                //     return response()->json($user);
                // }
                
           
            
            
        }

        
    
    }
    public function saveSignature(Request $request){
        $auth_id = Auth::id();
        $user_detail = userDetail::where('user_id', $auth_id)->first();
        $unique_number = rand(1000, 9999); 
        $current_timestamp = time(); 
        $new_a_path = $current_timestamp . $unique_number; 
        $set_path = $user_detail->user_id . '_' . $new_a_path . '.png';
        if (!Storage::disk('s3')->exists('user_signature')) {
            Storage::disk('s3')->makeDirectory('user_signature');
        }
        Storage::disk('s3')->put('user_signature/' . $set_path, file_get_contents($request->sign));
        Storage::disk('s3')->delete('user_signature/' . $user_detail->user_id . '_' . $user_detail->sign_path . '.png');
        $user_detail->sign_path = $new_a_path;
        $user_detail->save();
        return response()->json($user_detail);
    }
    public function getEditUser(Request $request){
        $auth_id = Auth::id();
        $msg_file = messageFile::findOrFail($request->file_id);
        $data = [];
        $user_detail = userDetail::where('user_id', $auth_id)->first(); 
        if($user_detail->sign_path != null){
            $path = $user_detail->user_id . '_' . $user_detail->sign_path . '.png';
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
                $data = [
                    'user' => $user,
                ];
                return response()->json($data);
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
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(empty($auth_user_id)){               
            return response()->json("loggedOut");
        }
        foreach($request->file() as $file){           
            $newFile = messageFile::find($request->file_id);
            $path = storage_path('app') . '/' . 'shared_files/' . $request->board_id;
            $set_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
            Storage::disk('s3')->delete('message_files/' . $request->board_id . '/' . $set_path);
            Storage::disk('s3')->put( 'message_files/' . $request->board_id . '/' .  $set_path, file_get_contents($file));
            $sizeAfter = Storage::disk('s3')->size('message_files/' . $request->board_id . '/' .  $set_path);
            $newFile->size = $sizeAfter;
            $newFile->edit_flag = null;
            $newFile->save();
            $signUser = $newFile->signUsers()->where('user_id', Auth::id())->first();
            if ($signUser) {
                
                $signUser->pivot->signed = true;
                $signUser->pivot->save();
            }        
        }       
        return response()->json("success");
    }
    public function getUnsignedUsers(Request $request){
        $auth_id = Auth::id();   
        $list = boardRecord::whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::id());
        })->get();          
        $result = new \Illuminate\Database\Eloquent\Collection;
        foreach($list as $board){
                $selfcheck = boardToUser::where('record_id', '=', $board->id)->where('user_id', '=', Auth::id())->first();
                $comment_list_pre = messageRecord::whereHas('message_files', function ($query) use ($auth_id) {
                    $query->where('sign_flag', 1)->where('removed_at', null)->whereHas('unsignedUsers', function ($q) use ($auth_id) {
                        $q->where('user_id', $auth_id);
                    });
                })
                ->with('user')
                ->with(['message_files', 'message_files.unsignedUsers', 'message_files.signedUsers'])
                ->get();
                $result = $result->merge($comment_list_pre);
           
        }
        $data = [
            "message_list" => $result
        ];
        return response()->json($data);
    }
    public function attachUpload(Request $request){
        // return response()->json($request);
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $ids = [];
        foreach($request->file() as $file ){
            $mime_type = $file->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];
            $file_extension = $file->getClientOriginalExtension();
            $path = '/temp_upload';     
            $file_name = $file->getClientOriginalName(); 
            $file_size = $file->getSize();            
            $newFile = new messageFile;
            $newFile->name = $file_name;
            $newFile->extension = $file_extension;
            
            $newFile->user_id = $auth_user_id;
            $newFile->mime_type = $file_type;            
            $newFile->save(); 

        
            if($file_type == 'image'){
                $img = Image::make($file)->orientate();
                $set_path = $newFile->id . '.' . $file_extension;
                File::isDirectory(storage_path('app') . '/' . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                      
                $img->save(storage_path('app') . '/' . $path .'/'. $set_path, 30);  

            }else{                           
                File::isDirectory(storage_path('app') . '/' . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);   
                $set_path = $newFile->id . '.' .$file_extension;
                Storage::disk('local')->putFileAs(
                    '/temp_upload', $file, $set_path
                );
                // return response()->json("ffffff");
            }
            $sizeAfter = File::size(storage_path('app/temp_upload/' . $request->board_id .'/'. $set_path));
            
            $newFile->size = $sizeAfter;
            $newFile->save(); 
            $ids[] = $newFile;
                       
        }
        return response()->json($ids);        
        return response()->json("nofile");
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
    public function getCommentList(Request $request){
        $pagenate = 30 * $request->page_index;       
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $usercheck = boardToUser::where('user_id','=', $auth_user_id)->where('record_id', '=', $request->record_id)->first();       
        $timeLimit = $usercheck->joined_at;    
        $targetBoard = boardRecord::findOrFail($request->record_id);
        $messageFrom = $targetBoard->message_from;     
        $time_condition = $messageFrom == 0 && $timeLimit;   
        if($usercheck->member_status == 0){
            return response()->json([]);
        }
        $comment_list_pre = messageRecord::withTrashed()
        ->where('record_id', $request->record_id)
        ->when($time_condition, function ($query) use ($timeLimit) {
            $query->where('created_at', '>=',  $timeLimit );
        })
        ->with('user')
        ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
        ->with('message_reply')
        ->with('message_quot')
        ->with('message_forward')
        ->with('reactedUsers')
        ->with('checkedUsers')
        ->with('uncheckedUsers')
        ->latest('created_at')
        ->take($pagenate)
        ->get();

        return response()->json($comment_list_pre);

    }
    public function chatAdd(Request $request)
    {   
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if($request->quot_flag == 1 && $request->reply_flag == 1){
            throw ValidationException::withMessages(['message' => 'commonError']);            
        }   
        $boardRecord = boardRecord::findOrFail($request->record_id);
        if($boardRecord->private_flag == 1){
            $targetUserId = $boardRecord->board_to_users()->where('user_id', '!=', Auth::id())->withTrashed()->first();
            if($targetUserId){
                $targetUser = $this->sharedService->getUserState($targetUserId->user_id, Auth::user());
                if(!$targetUser || ($targetUser->is_blocked || $targetUser->is_blocked_by)){
                    throw ValidationException::withMessages(['message' => 'unableToSendMessageDueBlockAction']);  
                }
            }
        }     
        if($request->message_id){
            $chat = messageRecord::findOrFail($request->message_id);
        }else{
            $chat = new messageRecord;
        }           
            $chat->record_id = $request->record_id;
            $chat->user_id = $auth_user_id;
            
            if($request->message){
                $chat->message = $request->message;
                $chat->message_text = strip_tags(htmlspecialchars_decode($request->message)); 
            }else{
                $chat->message = '';
                $chat->message_text = strip_tags(htmlspecialchars_decode('')); 
            }            
            
            $chat->emoji_flag = $request->emoji_flag;
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
            $chat->save();
            
            if($request->attached_temp_files){ 
                try {                   
                    
                    foreach($request->attached_temp_files as $item){
                        $path_shared_files = $request->record_id;       
                        $path_temp_files = 'shared_files/temp_upload';   
                        $file = messageFile::findOrFail($item['id']);
                        $file->board_id = $chat->record_id;

                        $file->message_id = $chat->id;                            
                        $file->save(); 
                        if (!Storage::disk('s3')->exists('message_files/' . $path_shared_files)) {
                            Storage::disk('s3')->makeDirectory('message_files/' . $path_shared_files);
                        }
                        

                        $srcPath = $file->id . '.' .$file->extension;
                        $destPath = $file->id . '_' . $file->user_id . '_' . $chat->id . '.' . $file->extension;
                        $temp_path = storage_path('app/temp_upload/' . $srcPath);
                        Storage::disk('s3')->put( 'message_files/' . $path_shared_files . '/' .  $destPath, file_get_contents($temp_path));
                        if($file->mime_type == 'image'){
                            $thumb_50_path = $file->id . '_' . $file->user_id . '_' . $chat->id . '_50.' . $file->extension;
                            $thumb_100_path = $file->id . '_' . $file->user_id . '_' . $chat->id . '_100.' . $file->extension;
                            $thumbnail = Image::make($temp_path)->fit(50, 50, function ($constraint) {
                                $constraint->upsize();
                            });
                            $thumb_stream = $thumbnail->stream();        
                            $thumbnail_big = Image::make($temp_path)->fit(100, 100, function ($constraint) {
                                $constraint->upsize();
                            });
                            $thumbBig_stream = $thumbnail_big->stream();
                            if (!Storage::disk('s3')->exists('message_files/' . $path_shared_files . '/thumbs')) {
                                Storage::disk('s3')->makeDirectory('message_files/' . $path_shared_files . '/thumbs');
                            }
                            Storage::disk('s3')->put('message_files/' . $path_shared_files . '/thumbs/' .  $thumb_50_path, $thumb_stream);
                            Storage::disk('s3')->put('message_files/' . $path_shared_files . '/thumbs/' .  $thumb_100_path, $thumbBig_stream);

                        }
                        unlink($temp_path);  
                            
                        
                        
                    }
                }   
                catch (\Exception $e) {           
                    $chat->forceDelete();       
                    throw ValidationException::withMessages(['message' => 'failedToUploadFile']);
                } 
            }
            if($request->imported_files){           
                foreach($request->imported_files as $file){
                    $path_shared_files = 'shared_files/' . $request->record_id;     
                    $path_managed_files = 'managed_files/' . $file->board_id;   
                    $newFile = new messageFile;
                    $file->board_id = $chat->record_id;
                    $newFile->message_id = $chat->id;
                    $newFile->name = $file['name'] . '.' . $file['extension'];
                    $newFile->extension = $file['extension'];
                    
                    $newFile->user_id = $auth_user_id;
                    $newFile->mime_type = $file['mime_type'];  
                    $newFile->size = $file['size'];      
                    $newFile->save(); 
                    if (!Storage::disk('s3')->exists('message_files/' . $path_shared_files)) {
                        Storage::disk('s3')->makeDirectory('message_files/' . $path_shared_files);
                    }  

                    $app_file_path = $file['path'] . '.' .$file['extension'];
                    $msg_file_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
                    File::copy(storage_path('app') . '/' . $path_managed_files . '/' . $app_file_path , storage_path('app') . '/' . $path_shared_files . '/' . $msg_file_path );                    
                    $newFile->save(); 
                }
            }
            if($request->forwarded_files){             
                foreach($request->forwarded_files as $file){
                    $path_shared_files = $request->record_id;     
                    $path_managed_files = $file['board_id'];   
                    $newFile = new messageFile;
                    $newFile->board_id = $chat->record_id;
                    $newFile->message_id = $chat->id;
                    $newFile->name = $file['name'];
                    $newFile->extension = $file['extension'];
                    
                    $newFile->user_id = $auth_user_id;
                    $newFile->mime_type = $file['mime_type'];  
                    $newFile->size = $file['size'];      
                    $newFile->save(); 
                    if (!Storage::disk('s3')->exists('message_files/' . $path_shared_files)) {
                        Storage::disk('s3')->makeDirectory('message_files/' . $path_shared_files);
                    } 
                    $app_file_path = $file['id'] . '_' .$file['user_id'] . '_' . $file['message_id'] . '.' . $file['extension'];
                    $msg_file_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
                    $sourcePath = 'message_files/' . $path_managed_files . '/' . $app_file_path;
                    $destinationPath = 'message_files/' . $path_shared_files . '/' . $msg_file_path;
                    Storage::disk('s3')->copy($sourcePath, $destinationPath);
                    
                    $newFile->save(); 
                }
            }
          
            
            $boardRecord->touch();
            if($boardRecord->private_flag == 1){
                $restoreUsers = boardToUser::where('record_id','=', $request->record_id)->where('deleted_status', '=', 1)->get();
                if(!empty($restoreUsers)){
                    foreach($restoreUsers as $restoreUser){
                        $restoreUser->deleted_status = 0;
                        $restoreUser->joined_at = now();
                        $restoreUser->invited_at = now();
                        $restoreUser->member_status = 0;
                        $restoreUser->invited_by = Auth::id();
                        $restoreUser->save();
                    }
                    $chat->touch();
                }
                
            }
            if(!empty($request->mentioned_users)){               
                
               
                $msg_id = $chat->id;
                $url = url('/chat/' . $request->record_id . '/?m=' . $chat->id);           
                $chat_title = $boardRecord->private_flag == 1 ? $auth_user->name : $boardRecord->title;
                                                   
                    
                $content = $chat->message_text;                                                
                foreach($request->mentioned_users as $user){
                    $target_user = User::where('id','=', $user)->select('id', 'email', 'language')->first();
                    $language = $target_user->language ? $target_user->language : 'en';
                    if(!empty($target_user->email)){
                        Mail::to($target_user->email)
                        ->send(new Notify(
                            $url, 
                            $content, 
                            $msg_id, 
                            $language, 
                            $chat_title, 
                            $auth_user->name,
                            'mention'
                        ));
                    }
                    
                
                }
            }
            $related_members = boardToUser::where('record_id','=', $request->record_id)->where('deleted_status', '=', 0)->where('user_id', '!=', $auth_user_id)->pluck('user_id');
            $update_last_message = boardToUser::where('record_id','=', $request->record_id)->where('user_id', '=', $auth_user_id)->update(["last_message" => $chat->id]);
            $rebound = array(
                "type" => "new_message",
                "board_members" => $related_members,
                "board_id" => $request->record_id,
                "sender" => $auth_user_id
            );
            event(new MessageSent($rebound));
            $data = [
                "success" => true,
                "u_id" => $request->u_id,
                "data" => $chat
            ];
            $boardRecord->update(["last_activity" => now()]);
            return response()->json($data);
             
        // }

    }    
    public function chatDelete(Request $request){

        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $auth_user_id = Auth::id();
        $chat_record = messageRecord::findOrFail($request->id);
        if($auth_user_id !== $chat_record->user_id){
            throw ValidationException::withMessages(['message' => 'sufficientAdministrativePermission']);
        }
        $chat_record->reactedUsers()->detach();
        $chat_record->checkUsers()->detach();
        $chat_record->delete();            
        $files = messageFile::where('message_id', '=', $chat_record->id)->get();
        if($files){                
            foreach($files as $file){             
                $path = 'message_files/' . $chat_record->record_id . '/' . $file->id . '_' . $file->user_id . '_' . $chat_record->id . '.' . $file->extension;
                Storage::disk('s3')->delete($path);
                $file->delete();
            }               
            
        }          
        return response()->json('success', 200);
        
         
    }
    public function chatEdit(Request $request){

        $validatedData = $request->validate([
            'id' => 'required',
            'message' => 'required'
        ]);
        $auth_user_id = Auth::id();
        $chat_record = messageRecord::findOrFail($request->id);
        if($auth_user_id !== $chat_record->user_id){
            throw ValidationException::withMessages(['message' => 'sufficientAdministrativePermission']);
        }
        
        
        $chat_record = messageRecord::findOrFail($request->id);
     
        if(!empty($chat_record)){
            $chat_record->message = $request->message;
            $chat_record->message_text = strip_tags(htmlspecialchars_decode($request->message)); 
            $chat_record->save();
        }
        return response()->json('success', 200);
        
    }
    public function checkSend(Request $request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();        
        $message_record = messageRecord::findOrFail($request->message_id);
        $checkUser = $message_record->checkUsers()->where('user_id', Auth::id())->first();
        if ($checkUser) {
            
            $checkUser->pivot->checked = true;
            $checkUser->pivot->save();
        } 
        $related_members = [];
        $related_members[] = $auth_user_id;
        $rebound = array(
            "board_members" => $related_members
        );
        event(new MessageSent($rebound));
        return response()->json();
        

    }
    public function notificationUpdate(Request $request){
        if(!empty($request)){
            
            // return response()->json($request);
            $auth_user = Auth::user();
            $auth_user_id = Auth::id();

            $updateLastMessage = boardToUser::where('record_id','=', $request->board_id)->where('user_id','=', $auth_user_id)->first();
                if(!empty($updateLastMessage)){
                    $lastMessageId = messageRecord::where('record_id', '=', $request->board_id)->orderBy('created_at', 'desc')->withTrashed()->select('id')->first();
                        if(!empty($lastMessageId)){
                            $updateLastMessage->last_message = $lastMessageId->id;
                            $updateLastMessage->save();
                            return response()->json($lastMessageId->id);
                        }
                    
                    
                }
        }
        
    }
    public function notificationGet(Request $request){        

       
        $savedLastMessages = boardToUser::where('user_id', Auth::id())
            ->where('member_status', 1)
            ->where('deleted_status', 0)
            ->orderBy('record_id', 'desc')
            ->get();

        $result = [];
        foreach($savedLastMessages as $record){
            $last = $record->last_message;
            $unread_count = $record->messageRecords()
            ->when($last, function ($q) use ($last) {
                $q->where('id', '>', $last);
            })
            ->when($record->joined_at, function ($q) use ($record) {
                $q->where('created_at', '>=', $record->joined_at);
            })->count();

            if($unread_count > 0) {
                $result[$record->record_id] = $unread_count;
            }               
        }
        return response()->json($result);       
    }
    public function getTask(Request $request){
        
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $nowDate = now();
        if(!empty($request) && !empty($auth_user_id)){      
            if($request->flag == 0){
                $list = taskRecord::where('board_id', '=', $request->record_id)
                ->with('task_users')
                ->orderBy('end_at', 'asc')->get();
            }     
            
            return response()->json($list);       
        
        }else{
            return response()->json('error');
        }
        
    } 
    public function completeTask(Request $request){
        
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        
        if(!empty($request) && !empty($auth_user_id)){            
            $list = taskUser::where('record_id', '=', $request->task_id)->where('user_id', '=', $auth_user_id)->first();
            $list->comp_flag = $request->comp_flag;
            if($request->late_answer){
                $list->late_answer = $request->late_answer;
            }
            $list->save();
            // #20201202_0013 Tumur　通知機能追加
            $task_record = taskRecord::find($request->task_id);
            
            $allCount = taskUser::where('record_id', '=', $request->task_id)->count();
            if($allCount > 0){
                $completedCount = taskUser::where('record_id', '=', $request->task_id)->where('comp_flag', '=', 1)->count();
                if($allCount == $completedCount){
                    $task = taskRecord::find($request->task_id);
                    $task->comp_flag = 1;
                    $task->save();
                    // if($infos){
                    //     foreach($infos as $info){
                    //         $info->delete();
                    //         $info->save();
                    //     }
                    // }
                }else{
                    $task = taskRecord::find($request->task_id);
                    $task->comp_flag = 0;
                    $task->save();
                    // if($infos){
                    //     foreach($infos as $info){
                    //         $info->delete();
                    //         $info->save();
                    //     }
                    // }
                }
                
            }
            
            $related_members = [];
            $related_members[] = $auth_user_id;
            $rebound = array(
                "info_update_id" => $related_members
            );
            event(new MessageSent($rebound));
            
            return response()->json('saved');     
            
        }
        return response()->json('loggedOut');  
        
    }    
    public function notifyTask(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $result = [];
        
        $allBoard = boardRecord::whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::user()->id)->where('deleted_status','=', 0);
        })->pluck('id')->toArray();
        if(!empty($allBoard)){
            foreach($allBoard as $id){
                $allTasks = taskRecord::where('comp_flag', '=', 0)->where('board_id', '=', $id)->whereHas('task_users', function($q){
                    $q->where('user_id', Auth::user()->id)->where('comp_flag', '=', 0);
                })->count();
                $result[$id] = $allTasks;
            }
            
        }
        return response()->json($result);
    }   
    public function tabUpdate(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if($auth_user_id){

            if($request->tab == 0){
                $other_tabs = boardGroup::where('user_id', '=', $auth_user_id)->where('active_flag', '=', 1)->get();
                if($other_tabs){
                    foreach($other_tabs as $remove_tab){
                        $remove_tab->active_flag = 0;
                        $remove_tab->save();
                    }
                }
            }
            else if($request->tab == -1){
                $groupTab = boardGroup::where('user_id', '=', $auth_user_id)->where('name', '=', 'group_default')->first();
                if($groupTab){
                    $groupTab->active_flag = 1;
                    $groupTab->save();
                    $other_tabs = boardGroup::where('user_id', '=', $auth_user_id)->where('id', '!=', $groupTab->id)->where('active_flag', '=', 1)->get();
                    if($other_tabs){
                        foreach($other_tabs as $remove_tab){
                            $remove_tab->active_flag = 0;
                            $remove_tab->save();
                        }
                    }
                }else{
                    $newGroupTab = new boardGroup;
                    $newGroupTab->user_id = $auth_user_id;
                    $newGroupTab->name = 'group_default';
                    $newGroupTab->active_flag = 1;
                    $newGroupTab->hide_flag = 1;
                    $newGroupTab->save();
                    $other_tabs = boardGroup::where('user_id', '=', $auth_user_id)->where('id', '!=', $newGroupTab->id)->where('active_flag', '=', 1)->get();
                    if($other_tabs){
                        foreach($other_tabs as $remove_tab){
                            $remove_tab->active_flag = 0;
                            $remove_tab->save();
                        }
                    }
                }
            }else if($request->tab == -2){
                // $group = boardGroup::firstOrCreate(['user_id' =>  $auth_user_id], ['name' => 'private_default'], ['active_flag' => 1]);
                $groupTab = boardGroup::where('user_id', '=', $auth_user_id)->where('name', '=', 'private_default')->first();
                if($groupTab){
                    $groupTab->active_flag = 1;
                    $groupTab->save();
                    $other_tabs = boardGroup::where('user_id', '=', $auth_user_id)->where('id', '!=', $groupTab->id)->where('active_flag', '=', 1)->get();
                    if($other_tabs){
                        foreach($other_tabs as $remove_tab){
                            $remove_tab->active_flag = 0;
                            $remove_tab->save();
                        }
                    }
                }else{
                    $newGroupTab = new boardGroup;
                    $newGroupTab->user_id = $auth_user_id;
                    $newGroupTab->name = 'private_default';
                    $newGroupTab->active_flag = 1;
                    $newGroupTab->hide_flag = 1;
                    $newGroupTab->save();
                    $other_tabs = boardGroup::where('user_id', '=', $auth_user_id)->where('id', '!=', $newGroupTab->id)->where('active_flag', '=', 1)->get();
                    if($other_tabs){
                        foreach($other_tabs as $remove_tab){
                            $remove_tab->active_flag = 0;
                            $remove_tab->save();
                        }
                    }
                }
                
                
            }
            else{
                $tab = boardGroup::find($request->tab);
                if($tab){
                    $other_tabs = boardGroup::where('user_id', '=', $auth_user_id)->where('active_flag', '=', 1)->get();
                    if($other_tabs){
                        foreach($other_tabs as $remove_tab){
                            $remove_tab->active_flag = 0;
                            $remove_tab->save();
                        }
                    }
                    $tab->active_flag = 1;
                    $tab->save();
                }
            }
            
        return response()->json($request);
        }
        
        
    }
    public function addGroup(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(!empty($auth_user_id) && !empty($request->title) && !empty($request->group_list)){
            $group = new boardGroup;
            $group->name = $request->title;
            $group->user_id = $auth_user_id;
            $group->board_list = $request->group_list;
            $other_tabs = boardGroup::where('user_id', '=', $auth_user_id)->where('active_flag', '=', 1)->get();
                if($other_tabs){
                    foreach($other_tabs as $remove_tab){
                        $remove_tab->active_flag = 0;
                        $remove_tab->save();
                    }
                }
            $group->active_flag = 1;
            $group->save();
            return response()->json($request);
        }
            
        
    }
    public function editGroup(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        
        if(!empty($auth_user_id) && !empty($request->title) && !empty($request->group_list) && !empty($request->group_id)){
            $group = boardGroup::where('id', '=', $request->group_id)->first();
            if($group){
                $group->name = $request->title;            
                $group->board_list = $request->group_list;
                $other_tabs = boardGroup::where('id', '!=', $request->group_id)->where('user_id', '=', $auth_user_id)->where('active_flag', '=', 1)->get();
                    if($other_tabs){
                        foreach($other_tabs as $remove_tab){
                            $remove_tab->active_flag = 0;
                            $remove_tab->save();
                        }
                    }
                $group->active_flag = 1;
                $group->save();
                return response()->json($group);
            }
            
        }
            
        
    }
    public function getGroup(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(!empty($auth_user_id)){
            $group = boardGroup::where('user_id', '=', $auth_user_id)->where('name', '!=', '')->get();
            return response()->json($group);
        }
            
        
    }
    public function deleteGroup(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(!empty($auth_user_id)){
            $group = boardGroup::where('id', '=', $request->group_id)->where('user_id', '=', $auth_user_id)->first();
            if($group){
                $group->delete();                
                $group->save();
            }
            return response()->json('success');
        }
            
        
    }
    public function pinBoard(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
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
    public function taskEdit(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
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
                $related_members = [];
                $related_members[] = $auth_user_id;
                $rebound01 = array(
                    "info_update_id" => $related_members
                );
                event(new MessageSent($rebound01));
                $rebound = array(
                    "updateId" => $request->board_id
                );
                $boardRecord = boardRecord::findOrFail($task->board_id);
                $boardRecord->update(["last_activity" => now()]);
                event(new MessageSent($rebound));
                return response()->json($task);
            }
            
        }
    }
    public function taskDelete(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
            $task = taskRecord::find($request->task_id);
            if($task){
                $task->delete();
                $boardRecord = boardRecord::findOrFail($task->board_id);
                $boardRecord->update(["last_activity" => now()]);
                return response()->json($task);
            }
    }
    public function checkRequest(Request $request){


        if($request->type == 'confirm'){
            $message = messageRecord::findOrFail($request->msg_id);
            $message->check_flag = 1;
            
            $message->checkUsers()->attach($request->users);
            $message->save();
            $record_id = $message->record_id;
            $message_id = $message->id;
            $content = $message->message_text;
            
        }else if($request->type == 'sign'){
            $messageFile = messageFile::findOrFail($request->msg_file_id);
            $messageFile->sign_flag = 1;
            $messageFile->signUsers()->attach($request->users);
            $messageFile->save();
            $record_id = $messageFile->board_id;
            $message_id = $messageFile->message_id;
            $content = messageRecord::findOrFail($message_id)->message_text;
            
        }
        $boardRecord = boardRecord::findOrFail($record_id);
        $url = url('/chat/' . $request->record_id . '/?m=' . $message_id);           
        $chat_title = $boardRecord->private_flag == 1 ? Auth::user()->name : $boardRecord->title;
        $pattern = $request->type;                                                         
        foreach($request->users as $user){
            $target_user = User::where('id','=', $user)->select('id', 'email', 'language')->first();
            $language = $target_user->language ? $target_user->language : 'en';
            if(!empty($target_user->email)){
                Mail::to($target_user->email)
                ->send(new Notify(
                    $url, 
                    $content, 
                    $message_id, 
                    $language, 
                    $chat_title, 
                    Auth::user()->name,
                    $pattern
                ));
            }
            
        
        }
        return response()->json();
            
        
    }
    public function sendReaction(Request $request){
        $message = messageRecord::with('reactedUsers')
        ->with('checkedUsers')
        ->with('uncheckedUsers')->findOrFail($request->id);
        if ($message->reactedUsers()->where('user_id', Auth::id())->exists()) {
            $message->reactedUsers()->detach(Auth::id());            
        } else {
            $message->reactedUsers()->attach(Auth::id());            
        }

        $message = $message->fresh();
        $message->load('reactedUsers', 'checkedUsers', 'uncheckedUsers');
        return response()->json($message);
        // $auth_user = Auth::user();
        // $auth_user_id = Auth::id();  
        // if($auth_user_id){
        //     $message = messageRecord::find($request->id);
        //     if($message){
        //         if($message->reacted_users){
        //             $list = explode(',',$message->reacted_users);
        //             if(in_array($auth_user_id, $list)) {
                        
        //                 $reacted = array_map("intval", explode(",", $message->reacted_users));            
        //                 unset($reacted[array_search($auth_user_id, $reacted)]);
        //                 $reacted_subbed = implode(",",$reacted);
        //                 $message->reacted_users = $reacted_subbed;
        //                 $message->save();
                        
                        
        //             }else{
        //                 $check_list = array_map("intval", explode(",", $message->reacted_users));
        //                 $check_list[] = $auth_user_id;
        //                 $new_list = implode(",",$check_list);
                        
        //                 $message->reacted_users = $new_list;
        //                 $message->save();
                        
        //             }
        //         }else{
        //             $message->reacted_users = $auth_user_id;
        //             $message->save();
                    
        //         }
        //         return response()->json($message);

                
                
        //     }
        // }
    }    
    public function addTask(Request $request){
        
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        if(!empty($request->title) && $auth_user_id){
                    // taskcreate
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
                    //ここから保存処理
                    $schedule = new taskRecord;
                    // $schedule->type = 1;

                    // $schedule->release_flag = 1;

                    // $schedule->repetition_flag = 0;

                    $schedule->user_id = $auth_user_id;
                    $schedule->updated_user = $auth_user_id;

                    $schedule->title = $request->title;

                    $schedule->end_at = $combinedDT;

                    $schedule->remarks = $request->remarks;

                    // $schedule->color = $request->color;
                    // from board
                    $schedule->board_id = $request->board_id;

                    $schedule->save();
                    $rebound = array(
                        "updateId" => $request->board_id
                    );
                    //knowledge_to_users 中間テーブル保存処理
                    if(!empty($request->qualified_users)){

                        $qualified_users = $request->qualified_users;

                        foreach($qualified_users as $qualified_user){

                            $taskUser = new taskUser;
                            $taskUser->record_id = $schedule->id;
                            $taskUser->user_id = $qualified_user;
                            $taskUser->save();

                        }                                
                    }                   
                    
                    event(new MessageSent($rebound));
                    $boardRecord = boardRecord::findOrFail($request->board_id);
                    $boardRecord->update(["last_activity" => now()]);
                    return response()->json($schedule->id);
                // }



        }


    }
    public function updateTask(Request $request){
        $task = taskRecord::findOrFail($request->task_id);
        $task->update(['end_at' => $request->date, 'updated_user' => Auth::id()]);
        return response()->json($request);
    }
    public function messageSearch(Request $request){
        if($request->private_flag && $request->record_id){
            $list = boardRecord::where('id', $request->record_id)->whereHas('board_to_users', function($q){
                $q->where('user_id', Auth::id())->where('deleted_status', '=', 0);
            })->get();
        }else{
            $list = boardRecord::whereHas('board_to_users', function($q){
                $q->where('user_id', Auth::id())->where('deleted_status', '=', 0);
            })->get();
        }        
        
        $result = new \Illuminate\Database\Eloquent\Collection;
        foreach($list as $board){
            if($board->private_flag == 0 || $board->private_flag == 3){
                $selfcheck = $board->board_to_users()->where('user_id', Auth::id())->first();
                $time_limit = $selfcheck->joined_at;
                $messageFrom = $board->message_from;     
                $time_condition = $messageFrom == 0 && $time_limit;   
                $comment_list_pre = messageRecord::when($time_condition, function ($query) use($time_limit) {
                    $query->where('created_at', '>=',  $time_limit );
                })
                ->where('record_id', $board->id)
                ->where('message_text', 'LIKE', '%' . $request->keyword . '%')
                ->whereHas('user')
                ->with('user')
                ->latest('created_at')
                ->select('id', 'user_id', 'record_id', 'created_at', 'message', 'message_text')
                ->get();
                $result = $result->merge($comment_list_pre);
            }else if($board->private_flag == 1){
                $selfcheck = boardToUser::where('record_id', '=', $board->id)->where('user_id', '=', Auth::id())->first();
                if($selfcheck->joined_at){
                    $comment_list_pre = messageRecord::where('record_id', $board->id)
                    ->where('created_at', '>=',  $selfcheck->joined_at)->where('message_text', 'LIKE', '%' . $request->keyword . '%')
                    ->whereHas('user')->with('user')
                    ->latest('created_at')
                    ->select('id', 'user_id', 'record_id', 'created_at', 'message', 'message_text')
                    ->get();
                    $result = $result->merge($comment_list_pre);
                }else{                   
                    $comment_list_pre = messageRecord::where('record_id', $board->id)
                    ->where('message_text', 'LIKE', '%' . $request->keyword . '%')
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
            $history = searchHistoryRecord::where('user_id', Auth::id())->where('content', $request->keyword)->first();
            if(!$history){
                $new_history = new searchHistoryRecord;
                $new_history->content = $request->keyword;
                $new_history->user_id = Auth::id();
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
        $target = messageRecord::findOrFail($request->id);
        $board = boardRecord::findOrFail($target->record_id);
        $board_user = boardToUser::where('record_id', $target->record_id)->where('user_id', Auth::id())->first();
        $time_limit = $board_user->joined_at;
            $messageFrom = $board->message_from;     
            $time_condition = $messageFrom == 0 && $time_limit;   

            $pre = messageRecord::withTrashed()->where('record_id', '=', $target->record_id)->orderBy('created_at', 'desc')
            ->when($time_condition, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->where('created_at', '<', $target->created_at)            
            ->with('user')
            ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
            ->with('message_reply')
            ->with('message_quot')
            ->with('message_forward')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->take(14)
            ->get();

            $next = messageRecord::withTrashed()->where('record_id', '=', $target->record_id)->orderBy('created_at', 'asc')->where('created_at', '>', $target->created_at)
            ->when($time_condition, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->with('user')
            ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
            ->with('message_reply')
            ->with('message_quot')
            ->with('message_forward')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->take(15)->get()->reverse()->values();
    
            $target_q = messageRecord::withTrashed()->where('id', '=', $request->id)
            ->when($time_condition, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->with('user')
            ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
            ->with('message_reply')
            ->with('message_quot')
            ->with('message_forward')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->get();
            $united = $next->merge($target_q)->merge($pre);
            
            return response()->json($united);
        

    }
    public function getAppend(Request $request){
        $last_message = messageRecord::withTrashed()->findOrFail($request->last_message_id);
        $targetBoard = boardRecord::findOrFail($last_message->record_id);
        $board_user = boardToUser::where('record_id', $targetBoard->id)->where('user_id', Auth::id())->first();
        $time_limit = $board_user->joined_at;
        $messageFrom = $targetBoard->message_from;     
        $time_condition = $messageFrom == 0 && $time_limit;   
        if($request->direction === 'down'){
            $bottom_messages = messageRecord::withTrashed()->where('record_id', '=', $last_message->record_id)
            ->where('created_at', '>', $last_message->created_at)
            ->when($time_condition, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
            })
            ->with('user')
            ->with('message_files')
            ->with('message_reply')
            ->with('message_quot')
            ->with('message_forward')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->take(30)->get()->reverse()->values();


        }else if($request->direction === 'up'){
            $bottom_messages = messageRecord::withTrashed()->where('record_id', '=', $last_message->record_id)
            ->where('created_at', '<', $last_message->created_at)->orderBy('created_at', 'desc')
            ->when($time_condition, function ($query) use ($time_limit) {
                $query->where('created_at', '>=',  $time_limit );
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
        $user = User::where('id', $request->id)->select('id', 'name', 'phone', 'email', 'a_path', 'a_version')->first();
        if($user){
            $data = $this->sharedService->getUserState($request->id, Auth::user());

            $res = [
                "found" => true,
                "user" => $data,
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
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if($request->hasFile('file')) {
            $file_path = date("YmdHis") . md5(uniqid());
            //ファイル拡張子取得
            $file_extension = $request->file('file')->getClientOriginalExtension();
            //ファイルMIMEタイプ取得
            $mime_type = $request->file('file')->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];
            //ファイルサイズ取得
            $file_size = $request->file('file')->getSize();     

            $fileRecord = new Icons;
            // $fileRecord->path =  $file_path;
            $fileRecord->mime_type = $file_type;
            $fileRecord->extension = 'jpg';
            // $fileRecord->size = $file_size;
            $fileRecord->user_id = $auth_user_id;
            $fileRecord->use_of = 'board';
            $fileRecord->save();

            $set_path = 'board'. '_' . $fileRecord->id  . '.jpg';
            $img = Image::make($request->file('file'))->encode('jpg')->orientate();
            // $path = 'board_icon';
            // File::isDirectory(storage_path('app/board_icon')) or File::makeDirectory(storage_path('app/board_icon'), 0755); 
            
            // $set_path = 'board' . '_' . $fileRecord->id . '.' . 'jpg';
            if (!Storage::disk('s3')->exists('board_icon')) {
                Storage::disk('s3')->makeDirectory('board_icon');
            }
            $temp_path = storage_path('app/temp/'.$set_path);
            // $img->save($temp_path);
            

            
            if($file_size > 2000000){
                $img->save(($temp_path), 30);
            }else{
                $img->save($temp_path);  
            }       
            Storage::disk('s3')->put( 'board_icon/board_' . $fileRecord->id . '.jpg', file_get_contents($temp_path));
            unlink($temp_path);     
            $temp_url = Storage::disk('s3')->url('board_icon/board_' . $fileRecord->id . '.jpg');
            $ret = array ( 
                "set_path" =>  $set_path,
                "icon_id" => $fileRecord->id
            );
            return response()->json($ret);       
        }

    }    
    public function getMemo(Request $request){ 
        $list = memoRecord::where('board_id', $request->record_id)->with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($list);
    }
    public function addMemo(Request $request ){
        $validatedData = $request->validate([
            'board_id' => 'required',
            'text' => 'required',
        ]);
        $memo = new memoRecord;
        $memo->user_id = Auth::id();
        $memo->board_id = $request->board_id;
        $memo->message_id = $request->message_id;
        $memo->content = $request->text;
        $memo->save();
        $boardRecord = boardRecord::findOrFail($request->board_id);
        $boardRecord->update(["last_activity" => now()]);

        return response()->json($memo);
    }
    public function editMemo(Request $request ){
        $validatedData = $request->validate([
            'id' => 'required',
            'text' => 'required',
        ]);
        $memo = memoRecord::findOrFail($request->id);
        $memo->content = $request->text;
        $memo->timestamps = false;
        $memo->save();
        $boardRecord = boardRecord::findOrFail($memo->board_id);
        $boardRecord->update(["last_activity" => now()]);
        return response()->json($memo);
    }
    public function deleteMemo(Request $request ){
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $memo = memoRecord::findOrFail($request->id);
        $memo->delete();
        $memo->save();
        $boardRecord = boardRecord::findOrFail($memo->board_id);
        $boardRecord->update(["last_activity" => now()]);
        return response()->json($memo);
    }
    public function updateRemember(Request $request ){
        $validatedData = $request->validate([
            'index' => 'required',
            'value' => 'required',
        ]);
        $remember = appRememberRecord::where('user_id', Auth::id())->first();
        if(empty($remember)){
            $remember = new appRememberRecord;
        }
        $remember[$request->index] = $request->value;
        $remember->user_id = Auth::id();
        $remember->save();
        return response()->json($remember);
    }
    public function getIncompletedTasks(Request $request ){
        $today = Carbon::today();
        $list = taskRecord::where('comp_flag', '=', 0)
        ->whereHas('task_users', function($q){
            $q->where('user_id', Auth::id())->where('comp_flag', 0);
        })        
        ->whereDate('end_at', '<', $today)
        ->select('id', 'board_id', 'comp_flag', 'created_at', 'end_at', 'title', 'user_id', 'remarks')        
        ->with(['task_users' => function($q){
            $q->with(['user' => function($q){
                $q->with(['icons' => function($q){
                    $q->select('id', 'extension', 'profile_id', 'user_id');
                }])->select('id', 'name', 'a_path');
            }]);
        }])->orderBy('created_at', 'desc')->get();
        return response()->json($list);
    }
    public function respondInviteRequest(Request $request){
        $validatedData = $request->validate([
            'target' => 'required',
            'response' => 'required',
        ]);
        $target = $request->target;
        $targetBoard = boardRecord::findOrFail($target);
        if($request->response == 1){
            $selfRecord = boardToUser::where('record_id', $target)->where('user_id', Auth::id())->where('member_status', 0)->first();
            if(!empty($selfRecord)){
                $selfRecord->member_status = 1;
                $selfRecord->joined_at = $selfRecord->invited_at;
                $selfRecord->save();
                if($targetBoard->private_flag == 1){
                    $correspond = $targetBoard->board_to_users()->where('user_id', '!=', Auth::id())->first();
                    if($correspond){
                        $target_user = User::findOrFail($correspond->user_id);
                        if (!Auth::user()->friends()->where('friend_id', $target_user->id)->exists()) {
                            Auth::user()->friends()->attach($target_user->id, ['created_at' => now(), 'updated_at' => now(), 'status' => 1]);
                        }
                        $target_user->friends()->where('friend_id', Auth::id())->update(['status' => 1]);
                    }
                }
                
                return response()->json("respondConfirmed", 200);
            }
            throw ValidationException::withMessages(['message' => 'commonError']);
            
        }else if($request->response == 0){
            
            if($targetBoard->private_flag == 0){
                $selfRecord = boardToUser::where('record_id', $target)->where('user_id', Auth::id())->where('member_status', 0)->first();
                if(!empty($selfRecord)){
                    $selfRecord->delete();                   

                    $createInfo = $this->sharedService->createInfoMessage([Auth::user()->name], $targetBoard->id, 'rejected_request', Auth::id());  
                    return response()->json("respondDeleted", 200);
                }else{
                    throw ValidationException::withMessages(['message' => 'commonError']);
                }
                
            }else if($targetBoard->private_flag == 1){
                $delete = $this->sharedService->removeBoard($targetBoard);
                return response()->json($delete, 200);
            }
            throw ValidationException::withMessages(['message' => 'commonError']);
            
        }
        
    }
    public function respondJoinRequest(Request $request){
        $validatedData = $request->validate([
            'target_id' => 'required',
            'response' => 'required',
            'record_id' => 'required'
        ]);        
        $checkPrivilage = boardToUser::where('record_id', $request->record_id)->where('user_id', Auth::id())->where('member_status', 1)->exists();
        if(!$checkPrivilage){
            throw ValidationException::withMessages(['message' => 'sufficientAdministrativePermission']);
        }
        $selfRecord = boardToUser::where('record_id', $request->record_id)->where('user_id', $request->target_id)->where('member_status', 0)->first();
        if(!empty($selfRecord)){
            if($request->response == 1){
                $selfRecord->member_status = 1;
                
                $newUserRecord = User::find($selfRecord->user_id);
                if($newUserRecord){                    
                    $createInfo = $this->sharedService->createInfoMessage([$newUserRecord->name], $selfRecord->record_id, 'added_members', Auth::id());                      
                }
                $selfRecord->joined_at = now();
                $selfRecord->save();
                $checkBoard = boardRecord::findOrFail($request->record_id);
                $related_id = $checkBoard->board_to_users()->pluck('user_id');
                $rebound = array(
                    "new_board_members" => $related_id->toArray()
                );
                event(new MessageSent($rebound));
                return response()->json("respondConfirmed", 200);
            }else if($request->response == 0){
                $selfRecord->delete();
                return response()->json("respondConfirmed", 200);
            }
        }
        throw ValidationException::withMessages(['message' => 'commonError']);      
        
    }
    public function cancelJoinRequest(Request $request){
        $validatedData = $request->validate([
            'target' => 'required',
        ]);        

        $selfRecord = boardToUser::where('record_id', $request->target)->where('user_id', Auth::id())->where('member_status', 0)->first();
        if(!empty($selfRecord)){
            
            $selfRecord->delete();
            return response()->json("respondConfirmed", 200);
           
        }
        throw ValidationException::withMessages(['message' => 'commonError']);      
        
    }
    public function setAdminRole(Request $request){
        $validatedData = $request->validate([
            'record_id' => 'required',
            'user_id' => 'required',
            'flag' => 'required'
        ]);
        $checkBoard = boardRecord::findOrFail($request->record_id);
        $checkAdmin = $checkBoard->board_to_users()->where('user_id', Auth::id())->where('admin_flag', 1)->exists();
        if($checkAdmin){
           
            if($request->flag == 0){
                $countAdmins = $checkBoard->board_to_users()->where('admin_flag', 1)->count();
                if($countAdmins == 1){
                    throw ValidationException::withMessages(['message' => 'atleastOneAdminIsNeeded']);
                }
            }
            
            $targetUser = $checkBoard->board_to_users()->where('user_id', $request->user_id)->first();
            if($targetUser){
                $targetUser->admin_flag = $request->flag;
                $targetUser->save();
                return response()->json("complete", 200);
            }
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
        throw ValidationException::withMessages(['message' => 'commonError']);
    }
    public function removeGroupMember(Request $request){
        $validatedData = $request->validate([
            'record_id' => 'required',
            'user_id' => 'required',
        ]);
        $checkBoard = boardRecord::findOrFail($request->record_id);
        $checkAdmin = $checkBoard->board_to_users()->where('user_id', Auth::id())->where('admin_flag', 1)->exists();
        if($checkAdmin){          
            
            
            $targetUser = $checkBoard->board_to_users()->where('user_id', $request->user_id)->first();
            if($targetUser){
                $targetUser->delete();
                $newUserRecord = User::find($request->user_id);
                if($newUserRecord){
                    
                    $createInfo = $this->sharedService->createInfoMessage([$newUserRecord->name], $checkBoard->id, 'removed_members', Auth::id());  
                    
                }
                $related_id = $checkBoard->board_to_users()->pluck('user_id');
                $rebound = array(
                    "new_board_members" => $related_id->toArray()
                );
                event(new MessageSent($rebound));
                $checkBoard->update(["last_activity" => now()]);
                return response()->json("complete", 200);
            }
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
        throw ValidationException::withMessages(['message' => 'commonError']);
    }
    public function groupAddMember(Request $request){
        $validatedData = $request->validate([
            'record_id' => 'required',
            'user_id' => 'required',
        ]);
        $checkBoard = boardRecord::findOrFail($request->record_id);
        $checkAdmin = $checkBoard->board_to_users()->where('user_id', Auth::id())->exists();
        if($checkAdmin){          
            
            
            $targetUser = $checkBoard->board_to_users()->where('user_id', $request->user_id)->first();
            if($targetUser){
                throw ValidationException::withMessages(['message' => 'memberAlreadyExists']);
            }else{
                $newUser = new boardToUser;
                $newUser->user_id = $request->user_id;
                $newUser->record_id = $request->record_id;
                $newUser->invited_by = Auth::id();
                $newUser->joined_at = now();
                $newUser->invited_at = now();
                $newUser->save();

                $newUserRecord = User::find($request->user_id);
                if($newUserRecord){                    
                    $createInfo = $this->sharedService->createInfoMessage([$newUserRecord->name], $checkBoard->id, 'invited_members', Auth::id()); 
                }
                $related_id = $checkBoard->board_to_users()->pluck('user_id');
                $rebound = array(
                    "new_board_members" => $related_id->toArray()
                );
                event(new MessageSent($rebound));
                $checkBoard->update(["last_activity" => now()]);
                return response()->json("complete", 200);   
            }
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
        throw ValidationException::withMessages(['message' => 'commonError']);
    }
    public function leaveBoard(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $board = boardRecord::findOrFail($request->id);
        $checkAdmin = $board->board_to_users()->where('user_id', Auth::id())->where('admin_flag', 1)->exists();
        $checkHasOtherAdmins = $board->board_to_users()->where('user_id', '!=', Auth::id())->where('admin_flag', 1)->exists();
        if($checkAdmin && !$checkHasOtherAdmins){
            throw ValidationException::withMessages(['message' => 'atleastOneAdminIsRequired']);
        }
        $board->board_to_users()->where('user_id', Auth::id())->delete();
        $taskUser = taskUser::where('record_id', $board->id)->where('user_id', Auth::id())->where('comp_flag', 0)->delete();

        $createInfo = $this->sharedService->createInfoMessage([Auth::user()->name],$board->id, 'left_members', Auth::id());   
        
        return response()->json("complete", 200); 

    }






}
