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
use App\Models\CalendarRecord;
use App\Models\messageRemindUser;
use App\Events\Message;
use App\Models\customFieldDataRecord;
use App\Models\messageSignUser;
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
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Completions\CreateResponse;
use App\Mail\Mention;
use App\Mail\Confirm;
use Hash;
use App\Jobs\SendNotification;
class BoardController extends Controller
{
    protected $sharedService;
    
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }
    public function index(Request $request){ 

        // $q = '文章を修正してください。';

        // $full = $q . 'Hallo how you r';
        // // return $full;
        // $result = OpenAI::chat()->create([
        //     'model' => 'gpt-3.5-turbo',
        //     // 'model' => 'gpt-4',
        //     'messages' => [
        //         ['role' => 'assistant', 'content' => $full],
        //     ],
        //     'max_tokens' => 5000,
        //     'temperature' => 0.8
        // ]);
        // // return response()->json($result );

        // $answer = $result['choices'][0]['message']['content'];
        // echo $answer;
        // return;
        
        // $messages = messageRecord::where('id', '>', 0)->forceDelete();
        // return $messages;
        

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
        // echo(url()->full());
        // return;
        $id = $request->query('id');
        $m = $request->query('m');
        if($id && $m){
            $newUrl = url('board/' . $id . '?m=' . $m);
            return redirect($newUrl);
        }
        
        $date = null;
        $name = $request->name;
        $id = $request->id;
        if($name && $name == 'calendar' && $id){
            
            $find = CalendarRecord::where('id', $id)->first();
            if(!empty($find)){
                $date = Carbon::parse($find->date_start)->format('Y-m-d');
            //     echo($date);
            // return;
                //echo $date; 
                // return;
            }
        }
        $no_partner_zone = ['knowledge', 'nice', 'challenge', 'work', 'support'];
        if(in_array($name, $no_partner_zone) && Auth::user()->partner_flag == 1){
            return redirect('board');
        }   
        // echo $id; 
        // return;
        $today = Carbon::now()->format('Y-m-d');     
        
        $user = auth()->user()->load(['weathers' => function($q) use($today){
            $q->where('type_id', 43)->where('date', $today);
        }]);
       
        return view('board')->with(array('initialDate'=> $date, 'user' => $user));

    } 
    public function get_possible_board_list(){
        $list = boardRecord::where('private_flag', 0)->whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::id());
        })->with(['board_to_users' => function($q){
            $q->whereHas('user')
            ->with('user')
            ->select('user_id', 'record_id');
        }])
        ->with(['icons' => function($q){
            $q->select('id','extension');
        }])
        ->orderBy('updated_at', 'desc')
        ->get();
        return response()->json($list);
    }
    public function getAllMessage(Request $request) {       
        
        $list = boardRecord::whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::id())->where('deleted_status', 0);
        })->with('user')
        ->with(['icons' => function($q){
            $q->select('id','extension');
        }])->with(['board_to_users' => function($q){
            $q->whereHas('user')
            ->with('user');
        }])
        ->with('last_message')->orderBy('updated_at', 'desc')
        ->get()
        ->values();


        // $list->map(function ($item) use($list) {            
        //     if($item->private_flag == 0 || $item->private_flag == 3){
        //         $message = messageRecord::where('record_id', $item->id)->latest('created_at')->select('id', 'message', 'message_text', 'record_id', 'info_flag')->with('message_files')->first();
        //         $item['last_message'] = $message;
        //         // $item->load(['board_to_users' => function($q){
        //         //     $q->whereHas('user')
        //         //     ->with('user');
        //         // }]);
        //     }else if($item->private_flag == 1){
        //         $selfcheck = boardToUser::where('record_id', '=', $item->id)->where('user_id', '=', Auth::id())->first();
                
        //         $correspond = $this->sharedService->getUserState($item->board_to_users()->where('user_id', '!=', Auth::id())->first()->user_id, Auth::user()); 
                
        //         if($selfcheck->created_at){
        //             $item['deleted'] = 'yes';
        //             $message = messageRecord::where('record_id', $item->id)->where('created_at', '>=',  $selfcheck->created_at)->latest('created_at')->with('message_files')->select('id', 'message', 'message_text', 'record_id', 'info_flag')->first();
        //             $item['last_message'] = $message;
                    
        //         }else{
        //             $message = messageRecord::where('record_id', $item->id)->latest('created_at')->select('id', 'message', 'message_text', 'record_id', 'info_flag')->with('message_files')->first();
        //             $item['last_message'] = $message;
        //         }
        //         // $item->load(['board_to_users' => function($q){
        //         //     $q->with('user');
        //         // }]);
                
                
        //     }else{
        //         // $item->load(['board_to_users' => function($q){
        //         //     $q->with('user');
        //         // }]);
        //     }
        //     return $item;
        // });

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
    public function create_new_board(Request $request){

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
                        $restoreUsers = $checkCurrentBoard->board_to_users()->where('deleted_status', 1)->update([
                            'deleted_status' => 0,
                            'created_at' => now()
                        ]);                        
                        $arr = [
                            "restored" => $restoreUsers,
                            "message" => "existAndAccessable",
                            "success" => true,
                            "data" => $checkCurrentBoard
                        ];   
                        $checkCurrentBoard->touch(); 
                        return response()->json($arr);
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
            


            
            if($defaultTitle == null){
                $board->title = $request->title;
            }elseif($defaultTitle == 'NoTitle'){
                $board->title = $defaultTitle;
            }    
            $board->save();           
            
            $new_members = [];

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
           
            if(empty($file_id_array) && $request->private_flag !== 1){
                try {
                    $createIcon = $this->sharedService->createBoardDefaultIcon($board, Auth::id());             
                   
                    if ($createIcon) {
                        $board->save();
                    } else {
                        $board->delete();
                        throw ValidationException::withMessages(['message' => $createIcon]);
                    }   
                } catch (\Exception $e) {           
                    $board->delete();       
                    throw ValidationException::withMessages(['message' => $createIcon]);
                }               

            }
            if(!empty($file_id_array)){
                $board->icon_id = $request->icon_id;
                $board->save();
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
            if($request->icon_delete_flag == 1 || ($request->title !== $board->title && empty($request->icon_id))){
                $update_icon = true;
            }
            $board->title = $request->title; 
            if(!empty($request->icon_id)){
                if($board->icon_id){
                    $rmv = Icons::where('record_id', '=', $board->id)->where('use_of', '=', 'board')->get();
                    if($rmv){
                        foreach($rmv as $del){
                            Storage::disk('local')->delete('board_icon/board_' . $del->id . '.' . $del->extension);
                            $del->delete();
                        }
                                
                    }  
                }
                $board->icon_id = $request->icon_id;
                $icon = Icons::findOrFail($request->icon_id)->update(['record_id' => $board->id]);
            }else{   
                if($update_icon){                             
                                 
                    try {
                        $createIcon = $this->sharedService->createBoardDefaultIcon($board, Auth::id());             
                       
                        if ($createIcon) {
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
        }
    }
    public function cancelSignature(Request $request){
        $auth_id = Auth::id();
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
    public function saveSignature(Request $request){
        $auth_id = Auth::id();
        $user = User::findOrFail($auth_id);
        $unique_number = rand(1000, 9999); 
        $current_timestamp = time(); 
        $new_a_path = $current_timestamp . $unique_number; 
        $set_path = $auth_id . '_' . $new_a_path . '.png';
        if (!Storage::disk('local')->exists('user_signature')) {
            Storage::disk('local')->makeDirectory('user_signature');
        }
        Storage::disk('local')->put('user_signature/' . $set_path, file_get_contents($request->sign));
        Storage::disk('local')->delete('user_signature/' . $auth_id . '_' . $user->sign_path . '.png');
        $user->sign_path = $new_a_path;
        $user->save();
        return response()->json($user);
    }
    public function getEditUser(Request $request){
        $auth_id = Auth::id();
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
            $root_path = base_path();
            $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));            
            $path = $replaced . 'shared_files/' . $request->board_id;
            $set_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
            File::delete($path.'/'.$set_path);
            Storage::disk('local')->putFileAs(
                'shared_files/' . $request->board_id, $file, $set_path
            );
            $sizeAfter = Storage::disk('local')->size('shared_files/' . $request->board_id .'/'. $set_path);
            $newFile->size = $sizeAfter;
            $newFile->edit_flag = null;
            $newFile->save();
            
            $signUser = $newFile->signUsers()->where('user_id', Auth::id())->first();
            if($newFile->multiple_flag == 2){
                $originalFile = messageFile::find($newFile->original_file_id);
                $originalSignUser = $originalFile->signUsers()->where('user_id', Auth::id())->first();
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
    public function getUnsignedUsers(Request $request){
        $auth_id = Auth::id();   
        $list = boardRecord::whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::id())->where('deleted_status', 0);
        })->pluck('id')->toArray();
                 
        $comment_list_pre = messageRecord::whereIn('record_id', $list)
        ->whereHas('message_files', function ($query) use ($auth_id) {
            $query->where('sign_flag', 1)->whereHas('unsignedUsers', function ($q) use ($auth_id) {
                $q->where('user_id', $auth_id)->where('cancel_flag', 0);
            });
        })
        ->with('user')
        ->with(['message_files', 'message_files.unsignedUsers', 'message_files.signedUsers'])
        ->with('reactedUsers')
        ->with('checkedUsers')
        ->with('uncheckedUsers')
        ->get();
           
        
        $data = [
            "message_list" => $comment_list_pre
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
        $timeLimit = $usercheck->created_at;    
        $targetBoard = boardRecord::findOrFail($request->record_id);
        $messageFrom = $targetBoard->message_from;     
        $time_condition = $messageFrom == 0 && $timeLimit;   
        
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
        ->with('messageRemindUsers')
        ->with('memo')
        ->with('task')
        ->latest('created_at')
        ->take($pagenate)
        ->get();

        return response()->json($comment_list_pre);

    }
    public function chatAdd(Request $request){   
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
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
                    $msg_file_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
                    File::isDirectory(storage_path('app/shared_files/' . $request->record_id)) or File::makeDirectory(storage_path('app/shared_files/' . $request->record_id), 0755, true, true); 
                    Storage::disk('local')->copy($file['path'], $path_shared_files . '/' . $msg_file_path);
                }
            }
            // if($request->imported_files){           
            //     foreach($request->imported_files as $file){
            //         $path_shared_files = 'shared_files/' . $request->record_id;     
            //         $path_managed_files = 'managed_files/' . $file['record_id'];   
            //         $newFile = new messageFile;
            //         $newFile->board_id = $chat['record_id'];
            //         $newFile->message_id = $chat['id'];
            //         $newFile->name = $file['name'] . '.' . $file['extension'];
            //         $newFile->extension = $file['extension'];
                    
            //         $newFile->user_id = $auth_user_id;
            //         $newFile->mime_type = $file['mime_type'];  
            //         $newFile->size = $file['size'];      
            //         $newFile->save(); 
            //         File::isDirectory(storage_path('app/shared_files/' . $request->record_id)) or File::makeDirectory(storage_path('app/shared_files/' . $request->record_id), 0755, true, true); 

            //         $app_file_path = $file['path'] . '.' .$file['extension'];
            //         $msg_file_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
            //         // File::copy(storage_path('app') . '/' . $path_managed_files . '/' . $app_file_path , storage_path('app') . '/' . $path_shared_files . '/' . $msg_file_path );                    
            //         Storage::disk('local')->copy($path_managed_files . '/' . $app_file_path, $path_shared_files . '/' . $msg_file_path);
            //         $newFile->save(); 
            //     }
            // }
            // if($request->forwarded_files){             
            //     foreach($request->forwarded_files as $file){
            //         $path_shared_files = $request->record_id;     
            //         $path_managed_files = $file['board_id'];   
            //         $newFile = new messageFile;
            //         $newFile->board_id = $chat->record_id;
            //         $newFile->message_id = $chat->id;
            //         $newFile->name = $file['name'];
            //         $newFile->extension = $file['extension'];
                    
            //         $newFile->user_id = $auth_user_id;
            //         $newFile->mime_type = $file['mime_type'];  
            //         $newFile->size = $file['size'];      
            //         $newFile->save(); 
            //         File::isDirectory(storage_path('app/shared_files/' . $request->record_id)) or File::makeDirectory(storage_path('app/shared_files/' . $request->record_id), 0755, true, true); 
            //         $app_file_path = $file['id'] . '_' .$file['user_id'] . '_' . $file['message_id'] . '.' . $file['extension'];
            //         $msg_file_path = $newFile->id . '_' . $newFile->user_id . '_' . $newFile->message_id . '.' . $newFile->extension;
            //         $sourcePath = 'shared_files/' . $path_managed_files . '/' . $app_file_path;
            //         $destinationPath = 'shared_files/' . $path_shared_files . '/' . $msg_file_path;
            //         Storage::disk('local')->copy($sourcePath, $destinationPath);
                    
            //         $newFile->save(); 
            //     }
            // }
          
            
            $boardRecord->touch();
            if($boardRecord->private_flag == 1){
                $restoreUsers = boardToUser::where('record_id','=', $request->record_id)->where('deleted_status', '=', 1)->get();
                if(!empty($restoreUsers)){
                    foreach($restoreUsers as $restoreUser){
                        $restoreUser->deleted_status = 0;
                        $restoreUser->created_at = now();
                        $restoreUser->save();
                    }
                    $chat->touch();
                }
                
            }
            if(!empty($request->mentioned_users)){                  
                $board = boardRecord::where('id', '=', $request->record_id)->first();              
                
                if(!empty($board) && $board->private_flag == 1){
                    $b_title = $auth_user->name;
                    
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
                
                $mails = User::whereIn('id', $request->mentioned_users)->whereNotNull('email')->pluck('email')->toArray();
                foreach($mails as $to){
                    Mail::to($to)->send(new Mention($b_title, $content, $block_flag, $board->id, $chat->id));
                }
            }
            $related_members = boardToUser::where('record_id','=', $request->record_id)->where('deleted_status', '=', 0)->where('user_id', '!=', $auth_user_id)->pluck('user_id');
            $update_last_message = boardToUser::where('record_id','=', $request->record_id)->where('user_id', '=', $auth_user_id)->update(["last_message" => $chat->id]);
            $rebound = array(
                "type" => "new_message",
                "board_members" => $related_members,
                "board_id" => $request->record_id,
                "sender" => $auth_user_id,
            ); 
            
            
            event(new MessageSent($rebound));         
            $data = [
                "success" => true,
                "u_id" => $request->u_id,
                "data" => $chat
            ];
            $members = $related_members->map(function ($userId) {
                return (string) $userId;
            })->toArray();
            
            $deep_link = url('board/' . $request->record_id);
            $icon = url('content_api/profile_icon/' . $auth_user->icon_id . '_' . $auth_user->id . '_200.jpg');
            $badge = url('/96x96.png');
            if(!empty($boardRecord) && $boardRecord->private_flag == 1){
                $push_title = $auth_user->name;
                if($request->attached_temp_files && $chat->message_text == null){
                    $body = 'ファイルメッセージ';
                }else{
                    $body = $chat->message_text;
                }
            }else{
                $push_title = $boardRecord->title;
                if($request->attached_temp_files && $chat->message_text == null){
                    $body = $auth_user->name . ':' . 'ファイルメッセージ';
                }else{
                    $body = $auth_user->name . ':' . $chat->message_text;
                }
            }
            
            $payload = [
                "body" => $body,
                "title" => $push_title,
                "link" => $deep_link,
                "members" => $members,
                "icon" => $icon,
                "badge" => $badge,
                "user_id" => $auth_user_id,
                "user_name" => Auth::user()->name,
                "message" => $chat->message_text,
                "members_int" => $related_members->toArray(),
            ];
            SendNotification::dispatchAfterResponse($payload);
            
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
                $path = 'shared_files/' . $chat_record->record_id . '/' . $file->id . '_' . $file->user_id . '_' . $chat_record->id . '.' . $file->extension;
                Storage::disk('local')->delete($path);
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
                    }else{
                        $updateLastMessage->last_act = now();
                        $updateLastMessage->save();
                        return 'gg';
                    }
                    
                    
                }
        }
        
    }
    public function notificationGet(Request $request){        

       
        $savedLastMessages = boardToUser::where('user_id', Auth::id())
            ->where('deleted_status', 0)
            ->where('deleted_flag', 0)
            ->whereNull('left_at')
            ->whereHas('board', function ($q) {
                $q->where('deleted_flag', 0)->where('deleted_at', null);
            })
            ->orderBy('record_id', 'desc')
            ->get();

        $result = [];
        foreach($savedLastMessages as $record){
            $last = $record->last_message;
            if(!empty($last)){
                $unread_count = $record->messageRecords()->where('info_flag', '!=', 1)
                ->when($last, function ($q) use ($last) {
                    $q->where('id', '>', $last);
                })
                ->when($record->created_at, function ($q) use ($record) {
                    $q->where('created_at', '>=', $record->created_at);
                })->count();

                if($unread_count > 0) {
                    $result[$record->record_id] = $unread_count;
                }  
            }else{
                if($record->last_act == null){
                    $result[$record->record_id] = 1;
                }
                // $result[$record->record_id] = 1;
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
                $list1 = taskRecord::where('board_id', '=', $request->record_id)
                ->whereNotNull('end_at')
                ->with('to_users')
                ->orderBy('created_at', 'desc')->get();

                $list2 = taskRecord::where('board_id', '=', $request->record_id)
                ->whereNull('end_at')
                ->with('to_users')
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
        
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        
        if(!empty($request) && !empty($auth_user_id)){            
            $list = taskUser::where('record_id', '=', $request->task_id)->where('user_id', '=', $auth_user_id)->first();
            $list->comp_flag = $request->comp_flag;
            if($request->late_answer){
                $list->late_answer = $request->late_answer;
            }
            if($request->late_answer_custom){
                $list->late_answer_custom = $request->late_answer_custom;
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
                $allTasks = taskRecord::where('comp_flag', '=', 0)->whereNotNull('end_at')->where('board_id', '=', $id)->whereHas('task_users', function($q){
                    $q->where('user_id', Auth::user()->id)->where('comp_flag', '=', 0);
                })->count();
                $result[$id] = $allTasks;
            }
            
        }
        return response()->json($result);
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
                return response()->json($task);
            }
    }
    public function sendMail($request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        if(!empty($request['send_list']) && !empty($auth_user_id) && !empty($request['msg_id'])){
            $msg_id = $request['msg_id'];
            $content = '';
            $messageRecord1 = messageRecord::find($msg_id);
            if(!empty($messageRecord1)){                        
                $content = $messageRecord1->message_text;
            }
            $board = boardRecord::where('id', '=', $request['board_id'])->first();
            $subject;
            $b_title;
            $type;
            if(!empty($board) && $board->private_flag == 1){
                $b_title = $auth_user->name;
                
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
            $mailList = [];
            $block_flag = false;
            $blocked_words = ['password', 'PASSWORD', 'PW', 'pw','pass','PASS', 'パスワード','ﾊﾟｽﾜｰﾄﾞ', 'パス', 'ﾊﾟｽ'];
            foreach($blocked_words as $word){
                if (str_contains($messageRecord1->message_text, $word)) { 
                    $block_flag = true;
                }
            }
            $mails = User::whereIn('id', $request['send_list'])->whereNotNull('email')->pluck('email')->toArray();
            foreach($mails as $to){
                Mail::to($to)->send(new Confirm($b_title, $content, $block_flag, $request['board_id'], $request['msg_id'], $type));
            }
     
            
            return response()->json($msg_id);   
        }
        return response()->json('error');   
    }
    public function send_reconfirm_email(Request $request){
        $mail = $this->sendMail($request);
        return $mail;
    }
    public function getRemindMessage(){
        $list = boardRecord::whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::id())->where('deleted_status', 0);
        })->pluck('id')->toArray();
        $user = Auth::user();
        $remindedMessages = messageRecord::whereIn('record_id', $list)
            ->whereHas('messageRemindUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('reminded', 1);
            })
            ->where('deleted_flag', 0)
            ->with('messageRemindUsers')
            ->with('user')
            ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->get();
            
        return response()->json($remindedMessages);
    }
    public function remindRequest(Request $request){
        $auth_user_id = Auth::id();

        $message_remind = messageRemindUser::where('message_id', $request->id)->where('user_id', $auth_user_id)->first();

        if ($message_remind) {
            $message_remind->reminded = !$message_remind->reminded;
            $message_remind->save();
            return response()->json($message_remind->reminded);
        } else {
            $remind_user = new messageRemindUser;
            $remind_user->message_id = $request->id;
            $remind_user->user_id = $auth_user_id;
            $remind_user->reminded = 1;
            $remind_user->save();
            return response()->json($remind_user->reminded);
        }

        
    }
    public function getUncheckedMessage(Request $request){
        // $user = Auth::user();
        // $start_point = Carbon::parse('2023-03-13 00:00:00')->format('Y-m-d');
        // $checkMessages = messageRecord::whereHas('checkUsers', function ($query) use ($user) {
        //         $query->where('user_id', $user->id)
        //               ->where('checked', 0);
        //     })->whereHas('board_record', function($q){
        //         $q->whereHas('board_to_users', function($q){
        //             $q->where('user_id', Auth::id())->where('deleted_flag', '=', '0')->where('deleted_status', '=', 0);
        //         });
        //     })
        //     ->whereDate('check_request_at', '>', $start_point)
        //     ->where('deleted_flag', '0')
        //     ->with('messageRemindUsers')
        //     ->with('user')
        //     ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
        //     ->with('reactedUsers')
        //     ->with('checkedUsers')
        //     ->with('uncheckedUsers')
        //     ->get();
            
        // return response()->json($checkMessages);

        $user = Auth::user();
        $start_point = Carbon::parse('2023-03-13 00:00:00')->format('Y-m-d');
        $list = boardRecord::whereHas('board_to_users', function($q){
            $q->where('user_id', Auth::id())->where('deleted_status', 0);
        })->pluck('id')->toArray();
        $checkMessages = messageRecord::
            whereIn('record_id', $list)
            ->whereHas('checkUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('checked', 0);
            })
            ->whereDate('check_request_at', '>', $start_point)
            ->where('deleted_flag', '0')
            ->with('messageRemindUsers')
            ->with('user')
            ->with('message_files', 'message_files.unsignedUsers', 'message_files.signedUsers')
            ->with('reactedUsers')
            ->with('checkedUsers')
            ->with('uncheckedUsers')
            ->get();

        // return response()->json($list);
        return response()->json($checkMessages);
    }
    public function checkRequest(Request $request){

        $path_shared_files = 'shared_files/' . $request->board_id;
        if($request->type == 'confirm'){
            $message = messageRecord::findOrFail($request->msg_id);
            $message->check_flag = 1;
            $message->check_request_at = Carbon::now();
            $message->checkUsers()->attach($request->users);
            $message->save();
            $board = boardRecord::where('id', '=', $message->record_id)->first(); 
            $req = [
                "send_list" => $request->users,
                "board_id" => $board->id,
                "msg_id" => $message->id,
                "send_condition" => 1,              

            ];
            $this->sendMail($req);
            
        }else if($request->type == 'sign'){
            $messageFile = messageFile::findOrFail($request->msg_file_id);
            $messageFile->sign_flag = 1;
            $messageFile->signUsers()->attach($request->users);
            $messageFile->save();
            $record_id = $messageFile->board_id;
            $message_id = $messageFile->message_id;
            $content = messageRecord::findOrFail($message_id)->message_text;
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
                $existed_path = $messageFile->id . '_' . $messageFile->user_id . '_' . $messageFile->message_id . '.' . $messageFile->extension;
                
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
                    $new_path = $messageFile_loop->id . '_' . $messageFile_loop->user_id . '_' . $messageFile_loop->message_id . '.' . $messageFile_loop->extension;
                    File::copy(storage_path('app/') . $path_shared_files . '/' . $existed_path , storage_path('app/') . $path_shared_files . '/' . $new_path ); 
                }
                $messageFile->save();
                return response()->json($messageFile);
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
        $validatedData = $request->validate([
            'qualified_users' => 'required',
            'board_id' => 'required',

        ]);
        if(!$request->edit_id){
            $infoMessage = new messageRecord;
            $infoMessage->user_id = Auth::id();
            $infoMessage->info_flag = 2;
            $infoMessage->record_id = $request->board_id;
            $infoMessage->message = '新しいタスクが追加されました。';
            $infoMessage->message_text = '新しいタスクが追加されました。';
            $infoMessage->save();
        }

        $end_time = '00:00:00';
        // if($request->show_time){
        //     $end_time = $request->task_end_time;
        // }

        
        
        if($request->edit_id){
            $task = taskRecord::findOrFail($request->edit_id);
        }else{
            $task = new taskRecord;
        }
        

        $task->user_id = Auth::id();
        $task->updated_user = Auth::id();
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

        
        $task->remarks = $request->remarks;
        $task->board_id = $request->board_id;

        $task->save();
       

        $task->to_users()->syncWithPivotValues($request->qualified_users, ['updated_at' => now()]);
        $related_members = boardToUser::where('record_id','=', $request->board_id)->where('deleted_status', '=', 0)->where('user_id', '!=', Auth::id())->pluck('user_id');
        if(!$request->edit_id){
            $update_last_message = boardToUser::where('record_id','=', $request->board_id)->where('user_id', '=', Auth::id())->update(["last_message" => $infoMessage->id]);
        }
        $rebound = array(
            "type" => "new_message",
            "board_members" => $related_members,
            "board_id" => $request->record_id,
            "sender" => Auth::id()
        );                      
        event(new MessageSent($rebound)); 
                      
        return response()->json($task->id);
                



        


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
                $time_limit = $selfcheck->created_at;
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
        $time_limit = $board_user->created_at;
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
            ->with('memo')
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
            ->with('memo')
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
            ->with('memo')
            ->get();
            $united = $next->merge($target_q)->merge($pre);
            
            return response()->json($united);
        

    }
    public function getAppend(Request $request){
        $last_message = messageRecord::withTrashed()->findOrFail($request->last_message_id);
        $targetBoard = boardRecord::findOrFail($last_message->record_id);
        $board_user = boardToUser::where('record_id', $targetBoard->id)->where('user_id', Auth::id())->first();
        $time_limit = $board_user->created_at;
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
            ->with('memo')
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
            ->with('memo')
            ->take(30)->get();
        }
        return response()->json($bottom_messages);
    }
    public function getInstantUser(Request $request){
        $today = Carbon::now()->format('Y-m-d');
        $user = User::where('id', $request->id)->with(['weathers' => function($q) use ($today){
            $q->where('type_id', 43)->where('date', $today);
        }])->select('id', 'name', 'phone_number', 'work_email', 'icon_id')->first();
        if($user){
            // $data = $this->sharedService->getUserState($request->id, Auth::user());

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
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if($request->hasFile('file')) {
            $file_path = date("YmdHis") . md5(uniqid());
            $file_extension = $request->file('file')->getClientOriginalExtension();
            $mime_type = $request->file('file')->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];
            $file_size = $request->file('file')->getSize();     

            $fileRecord = new Icons;
            $fileRecord->mime_type = $file_type;
            $fileRecord->extension = 'jpg';
            $fileRecord->user_id = $auth_user_id;
            $fileRecord->use_of = 'board';
            $fileRecord->save();
            $path = '/board_icon';
            $set_path = 'board'. '_' . $fileRecord->id  . '.jpg';
            $img = Image::make($request->file('file'))->encode('jpg')->orientate();
            File::isDirectory(storage_path('app') . '/' . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                

            $save_path = (storage_path('app') . '/' . $path . '/' . $set_path);
            if($file_size > 2000000){
                $img->save(($save_path), 30);
            }else{
                $img->save($save_path);  
            }         
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
    public function set_editing_memo(Request $request){

        // if(memoRecord::where('board_id', $request->board_id))->whereNotNull('editing')->->where('editing', '=', Auth::user()->name)
        $memo = memoRecord::findOrFail($request->id);
        if($memo->editing && $memo->editing !== Auth::user()->name){
            throw ValidationException::withMessages(['message' => '現在'.$memo->editing.'さんが編集中です。']);
        }
        if($request->value == true){
            memoRecord::where('id', $request->id)->update([
                "editing" => Auth::user()->name
            ]);
        }else{
            memoRecord::where('board_id', $request->board_id)->where('editing', '=', Auth::user()->name)->update([
                "editing" => null
            ]);
        }      
        // $rebound = array(
        //     "memo_updated" => [
        //         "board_id" => $request->board_id,
        //         "from" => Auth::id()
        //     ]
        // );
        // event(new MessageSent($rebound));
        return response()->json();
    }
    public function addMemo(Request $request ){
        $validatedData = $request->validate([
            'board_id' => 'required',
            'text' => 'required',
        ]);
        $infoMessage = new messageRecord;
        $infoMessage->user_id = Auth::id();
        $infoMessage->info_flag = 2;
        $infoMessage->record_id = $request->board_id;
        $infoMessage->message = '新しいノートが追加されました。';
        $infoMessage->message_text = '新しいノートが追加されました。';
        $infoMessage->save();
        $memo = new memoRecord;
        $memo->user_id = Auth::id();
        $memo->board_id = $request->board_id;
        $memo->message_id = $infoMessage->id;
        $memo->content = $request->text;
        $memo->save();
        $boardRecord = boardRecord::findOrFail($request->board_id);

        $related_members = boardToUser::where('record_id','=', $request->board_id)->where('deleted_status', '=', 0)->where('user_id', '!=', Auth::id())->pluck('user_id');
        $update_last_message = boardToUser::where('record_id','=', $request->board_id)->where('user_id', '=', Auth::id())->update(["last_message" => $infoMessage->id]);
        $rebound = array(
            "type" => "new_message",
            "board_members" => $related_members,
            "board_id" => $request->board_id,
            "sender" => Auth::id()
        );                      
        event(new MessageSent($rebound));  
        $rebound = array(
            "memo_updated" => [
                "board_id" => $memo->board_id,
                "from" => Auth::id()
            ]
        );
        event(new MessageSent($rebound));       


        return response()->json($memo);
    }
    public function editMemo(Request $request ){
        $validatedData = $request->validate([
            'id' => 'required',
            'text' => 'required',
        ]);
        // $check_editing = memoRecord::where('board_id', $request->board_id)->whereNotNull('editing')->where('editing', '=', Auth::user()->name)->first();
        $memo = memoRecord::findOrFail($request->id);
        if($memo->editing && $memo->editing !== Auth::user()->name){
            throw ValidationException::withMessages(['message' => '現在'.$memo->editing.'さんが編集中です。']);
        }
        $memo->content = $request->text;
        $memo->timestamps = false;
        $memo->edit_user = Auth::id();
        $memo->editing = null;
        $memo->save();
        // $boardRecord = boardRecord::findOrFail($memo->board_id);
        $rebound = array(
            "memo_updated" => [
                "board_id" => $memo->board_id,
                "from" => Auth::id()
            ]
        );
        event(new MessageSent($rebound));
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
                ->with('to_users')
                ->whereDate('end_at', '<=', $today)
                ->orderBy('created_at', 'desc')->get();
        
        return response()->json($list);
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
                    
                    $createInfo = $this->sharedService->createInfoMessage($newUserRecord->name, $checkBoard->id, 'removed_members', Auth::id());  
                    
                }
                $related_id = $checkBoard->board_to_users()->pluck('user_id');
                $rebound = array(
                    "new_board_members" => $related_id->toArray()
                );
                event(new MessageSent($rebound));
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
                throw ValidationException::withMessages(['message' => '既にメンバーに追加されています。']);
            }else{
                $newUser = new boardToUser;
                $newUser->user_id = $request->user_id;
                $newUser->record_id = $request->record_id;  
                $newUser->save();

                $newUserRecord = User::find($request->user_id);
                if($newUserRecord){                    
                    $createInfo = $this->sharedService->createInfoMessage($newUserRecord->name, $checkBoard->id, 'added_members', Auth::id()); 
                }
                $related_id = $checkBoard->board_to_users()->pluck('user_id');
                $rebound = array(
                    "new_board_members" => $related_id->toArray()
                );
                event(new MessageSent($rebound));
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

        $createInfo = $this->sharedService->createInfoMessage(Auth::user()->name,$board->id, 'left_members', Auth::id());   
        
        return response()->json("complete", 200); 

    }
    public function board_possible_users(Request $request){
        $ng_list = ['推し', '知人', '家族', '友人', '関係者', 'お知らせアカウント'];
        $all_users = User::where('deleted_flag', 0)
        ->where('retire', 0)
        ->whereNotIn('name', $ng_list)
        ->select('id', 'name', 'icon_id')
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
        ->select('id', 'name', 'icon_id')
        ->get();
        return response()->json($all_users);
    }
    public function get_review_text(Request $request){

        $q = '文章を修正してください。';

        $full = $q . $request->text;
        // return $full;
        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-16k',
            // 'model' => 'gpt-4',
            'messages' => [
                ['role' => 'assistant', 'content' => $full],
            ],
            'max_tokens' => 5000,
            'temperature' => 0.8
        ]);
        // return response()->json($result );

        $answer = $result['choices'][0]['message']['content'];
        return $answer;
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





}
