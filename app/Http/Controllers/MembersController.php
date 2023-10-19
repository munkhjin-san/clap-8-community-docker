<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userDetail;
use App\Models\Icons;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\messageRecord;
use App\Models\Tag;
use App\Models\Friend;
use App\Events\Message;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Validation\ValidationException;
use DB;
use Transliterator;
use App\Services\SharedService;
class MembersController extends Controller
{
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }
    public function createIcons(){
        return;
        $faker = Faker::create('ja_JP');


       
        $ids = array();
        for ($i = 0; $i <= 100; $i++) {
            

        

            $user = new User;
            $user->email = $faker->safeEmail();    
            $user->login = $faker->safeEmail();           
            $user->email_verified_at = now(); 
            $user->is_public = 1;
            $user->name =$faker->kanaName();
            $user->password = Hash::make('12345678');
            $user->save();

            $userDetail = new userDetail;
            $userDetail->user_id = $user->id;
            $userDetail->company = $faker->company;
            $userDetail->intro = $faker->paragraph;
            $userDetail->occupation = $faker->jobTitle;
            $userDetail->profession = $faker->randomElement($array = array (
                'Lawyer',
                'Doctor',
                'Engineer',
                'Programmer',
                'Teacher',
                'Accountant',
                'Architect',
                'Writer',
                'Journalist'
            ));
            $userDetail->save();
            try {
                $createIcon = $this->sharedService->createUserDefaultIcon($user);             
               
                if ($createIcon) {
                    $user->save();
                } else {
                    $user->delete();
                    throw ValidationException::withMessages(['message' => 'Icon create failed.']);
                }   
            } catch (\Exception $e) {           
                $user->delete();       
                throw ValidationException::withMessages(['message' => 'Icon create failed.']);
            } 
            
            try {
                $type = 'user_qr_code';
                $id = $user->id;
                $current_token = $user->q_token;
                $newCode = $this->sharedService->newUserQrCode($type, $id, $current_token);
                if($newCode){
                    $user->update(['q_token' => $newCode]);  
                }
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages(['message' => 'commonError']);
            }
            echo $user->id;
            echo "<br>";
            
        
        }
        return;


        // Get a user by ID
        // $user = User::find(1);

        // // Create a new tag and save it to the database
        // $tag = new Tag;
        // $tag->name = 'New Tag';
        // $tag->save();

        // // Attach the tag to the user
        // $user->tags()->attach($tag->id);
        // $users = User::all();
        // foreach($users as $user){
        //      $this->createUserDefaultIcon($user, 0);
        // //     $unique_number = rand(1000, 9999); 
        // // $current_timestamp = time(); 
        // // $new_a_path = $current_timestamp . $unique_number;     
        // // $userName = $user->name;
        // // $firstChar = mb_strtoupper(mb_substr($userName, 0, 1, "UTF-8"));    
        // //     echo $firstChar;
        // }
        // return;
    }
    public function index(Request $request){
        return view('members');
    }
    public function getFriends(Request $request){
        // $users = Auth::user()->friends()->with('user_detail')->with('tags')->get();
        // return response()->json($users);
        $tagIds = $request->tag;
        $userId = Auth::id();
        $key = $request->key;
        $usersQuery = Auth::user()->friends()            
            ->when($key, function ($query, $key ) {
                $query->where(function ($query) use ($key) {
                    $query->where('name', 'like', "%$key%")
                        ->orWhereHas('tags', function ($query) use ($key) {
                            $query->where('name', 'like', "%$key%");
                        })
                        ->orWhereHas('user_detail', function ($query) use ($key) {
                            $query->where('company', 'like', "%$key%")
                            ->orWhere('company', 'like', "%$key%")
                            ->orWhere('profession', 'like', "%$key%")
                            ->orWhere('email', 'like', "%$key%")
                            ->orWhere('phone', 'like', "%$key%");
                        });
                });
            });
            if(!empty($tagIds)){           
        
                foreach ($tagIds as $tagId) {
                    $usersQuery->whereHas('tags', function ($query) use ($tagId) {
                        $query->where('tags.id', $tagId);
                    });
                }  
                
            }
        $users = $usersQuery->with('user_detail')->with('tags')->withPivot(['status', 'created_at'])->get();

        $friend_requests = Friend::where('friend_id', Auth::id())->where('status', 0)->with('user')->get();
        $res = ['friends' => $users, 'requests' => $friend_requests];
        return response()->json($res);

        // $usersQuery = User::where('id', '!=', $userId)
        //     ->whereExists(function ($query) use ($userId) {
        //         $query->select(DB::raw(1))
        //             ->from('friends')
        //             ->whereRaw('users.id = friends.friend_id')
        //             ->where('friends.user_id', $userId)
        //             ->where('friends.status', 1);
        //     })
            
        //     ->when($key, function ($query, $key ) {
        //         $query->where(function ($query) use ($key) {
        //             $query->where('name', 'like', "%$key%")
        //                 ->orWhere('email', 'like', "%$key%")
        //                 ->orWhere('phone', 'like', "%$key%")
        //                 ->orWhereHas('tags', function ($query) use ($key) {
        //                     $query->where('name', 'like', "%$key%");
        //                 })
        //                 ->orWhereHas('user_detail', function ($query) use ($key) {
        //                     $query->where('company', 'like', "%$key%")
        //                     ->orWhere('company', 'like', "%$key%")
        //                     ->orWhere('profession', 'like', "%$key%");
        //                 });
        //         });
        //     });

        // if(!empty($tagIds)){           
        
        //     foreach ($tagIds as $tagId) {
        //         $usersQuery->whereHas('tags', function ($query) use ($tagId) {
        //             $query->where('tags.id', $tagId);
        //         });
        //     }  
            
        // }
        // $users = $usersQuery->select('id', 'name', 'q_token', 'icon_id')->with('user_detail')->with('tags')->get();

        return response()->json($users);
    }
    public function getList(Request $request){
        $tagIds = $request->tag;
        $userId = Auth::id();
        $key = $request->key;
        $usersQuery = User::where('is_public', 1)
            ->where('id', '!=', $userId)
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('friends')
                    ->whereRaw('users.id = friends.friend_id')
                    ->where('friends.user_id', $userId);
            })        
            ->when($key, function ($query, $key ) {
                $query->where(function ($query) use ($key) {
                    $query->where('name', 'like', "%$key%")
                        ->orWhere('email', 'like', "%$key%")
                        ->orWhere('phone', 'like', "%$key%")
                        ->orWhereHas('tags', function ($query) use ($key) {
                            $query->where('name', 'like', "%$key%");
                        })
                        ->orWhereHas('user_detail', function ($query) use ($key) {
                            $query->where('company', 'like', "%$key%")
                            ->orWhere('company', 'like', "%$key%")
                            ->orWhere('profession', 'like', "%$key%");
                        });
                });
            });

        if(!empty($tagIds)){           
        
            foreach ($tagIds as $tagId) {
                $usersQuery->whereHas('tags', function ($query) use ($tagId) {
                    $query->where('tags.id', $tagId);
                });
            }  
            
        }
        $users = $usersQuery->select('id', 'name', 'q_token', 'icon_id')->with('user_detail')->with('tags')->paginate(30);
        return response()->json($users);
        
    }
    private function createUserDefaultIcon($user, $delete){
                
                                 
        $unique_number = rand(1000, 9999); 
        $current_timestamp = time(); 
        $new_a_path = $current_timestamp . $unique_number;     
        $userName = $user->name;
        $firstChar = mb_strtoupper(mb_substr($userName, 0, 1, "UTF-8"));        
        $input = array("#000");
        $random = $input[array_rand($input, 1)];
        $img = Image::canvas(200, 200, $random);

        $img->text($firstChar, 100, 100, function ($font) {
            $font->file(storage_path('app') . '/font/NotoSansCJKBold.otf');
            $font->size(130);
            $font->color('#fff');
            $font->align('center');
            $font->valign('middle');
            
        });

        $size_variants = [200, 120, 80, 45, 30, 25, 20, 15];
        foreach($size_variants as $size){
            $img_rsz = $img->resize($size, $size);
            Storage::disk('s3')->delete('profile_icon/' . $user->id . '_' . $user->a_path . '_' . $size . '.jpg');   
            $set_path = $user->id . '_' . $new_a_path . '_' . $size . '.jpg';
            $temp_path = storage_path('app/temp/'.$set_path);
            $img_rsz->save($temp_path);
            if (!Storage::disk('s3')->exists('profile_icon')) {
                Storage::disk('s3')->makeDirectory('profile_icon');
            }
            Storage::disk('s3')->put('profile_icon/' . $user->id . '_' . $new_a_path . '_' . $size . '.jpg', file_get_contents($temp_path));
            unlink($temp_path); 
        }
        $user->update(['a_path' => $new_a_path]);
        return true;
    }
    public function invite(Request $request){
        return;
        if(empty($request->token) || empty($request->id)){
            abort(404);
            return;
        }
        if($request->id == Auth::id()){
            return redirect()->route('board');
        }

        $targetUserExist = User::where('id', $request->id)->where('q_token', $request->token)->exists();
        if(!$targetUserExist){
            return redirect()->route('board');
        }
        echo "exists";
        $targetId = $request->id;
        

        $boardRecord = BoardRecord::where('private_flag', 1)
        ->whereHas('board_to_users', function ($query) use ($targetId) {
            $query->where('user_id', $targetId);                
        })->whereHas('board_to_users', function ($query) {
            $query->where('user_id', Auth::id());                
        })->first();
        if(!empty($boardRecord)){
            $restore_users = $boardRecord->board_to_users()->where('deleted_status', 1)->get();
            // if(!empty($restore_users)){
            if(!$restore_users->isEmpty()){
                foreach($restore_users as $restore){
                    $restore->deleted_status = 0;
                    $restore->created_at = now();
                    $restore->save();
                }
                
            }
            return redirect(url('/chat/' . $boardRecord->id));
        }
        else{
            $board = new boardRecord;
            $board->user_id = Auth::id();
            $board->private_flag = 1;  
            $board->title = 'NoTitle';
            $board->save();           
            

            $to_users = [Auth::id(), $targetId];
            foreach($to_users as $to_user){
                $boardToUser = new boardToUser;
                $boardToUser->record_id = $board->id;
                $boardToUser->user_id = $to_user;    
                $boardToUser->admin_flag = 1;      
                
                 
                $boardToUser->save();

            }  
            
            $user = Auth::user();
            $friend = User::find($targetId);
            if (!$user->friends()->where('friend_id', $friend->id)->exists()) {
                $user->friends()->attach($friend, ['created_at' => now(), 'updated_at' => now()]);
            } else {
                return;
            }
            return redirect(url('/chat/' . $board->id));

        }
        

      



        return;
        

        
    }
    public function joinRequest(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
        ]);

        $targetId = $request->id;
        $targetBoard = boardRecord::findOrFail($targetId);
        if($targetBoard->able_join == 2){
            throw ValidationException::withMessages(['message' => 'groupIsPrivate']);
        }
        $checkExist = boardToUser::where('record_id', $targetId)->where('user_id', Auth::id())->exists();

        if($checkExist){
            throw ValidationException::withMessages(['message' => 'alreadyMember']);
        }
        else{
            $boardToUser = new boardToUser;
            $boardToUser->user_id = Auth::id();
            $boardToUser->record_id = $targetId;
            
            $boardToUser->created_at = now();
            $boardToUser->save();  
            $result = [ "message" => 'directJoin'];
            $createInfo = $this->sharedService->createInfoMessage([Auth::user()->name], $targetId, 'added_members', Auth::id()); 
            

            return response()->json($result, 200);         
            

        }
    }
    public function chatRequest(Request $request){
        // $validatedData = $request->validate([
        //     'id' => 'required',
        // ]);
        echo 'here';
        echo $request->id;

        $targetId = $request->id;
        $user = $this->sharedService->getUserState($request->id, Auth::user());
        echo $user->name;
        if(!empty($user)){
            if($user->is_blocked || $user->is_blocked_by){
                abort(404);
            }

        }
        // echo $request->root();
        // return;
        $boardRecord = BoardRecord::where('private_flag', 1)
        ->whereHas('board_to_users', function ($query) use ($targetId) {
            $query->where('user_id', $targetId);                
        })->whereHas('board_to_users', function ($query) {
            $query->where('user_id', Auth::id());                
        })->first();
        if(!empty($boardRecord)){
            $restore_users = $boardRecord->board_to_users()->where('deleted_status', 1)->get();
            // if(!empty($restore_users)){
            if(!$restore_users->isEmpty()){
                foreach($restore_users as $restore){
                    $restore->deleted_status = 0;
                    $restore->created_at = now();
                    $restore->save();
                }
                // $res = [
                //     'status' => 'success',
                //     'message' => 'restored',
                //     'id' => $boardRecord->id
                // ];
                // return response()->json($res);
                // return redirect($request->root() . '/chat/' . $boardRecord->id);

            }
            // $res = [
            //     'status' => 'success',
            //     'message' => 'exists',
            //     'id' => $boardRecord->id
            // ];
            // return response()->json($res);
            return redirect($request->root() . '/chat/' . $boardRecord->id);
        }
        else{
            $board = new boardRecord;
            $board->user_id = Auth::id();
            $board->private_flag = 1;  
            $board->title = 'NoTitle';
            $board->save();           
            

            $to_users = [Auth::id(), $targetId];
            foreach($to_users as $to_user){
                $boardToUser = new boardToUser;
                $boardToUser->record_id = $board->id;
                $boardToUser->user_id = $to_user;    
                $boardToUser->admin_flag = 1;      
                
                 
                $boardToUser->save();

            }  
            
            $user = Auth::user();
            $friend = User::find($targetId);
            if (!$user->friends()->where('friend_id', $friend->id)->exists()) {
                $user->friends()->attach($friend, ['created_at' => now(), 'updated_at' => now()]);
            } 
            // $res = [
            //     'status' => 'success',
            //     'message' => 'created',
            //     'id' => $board->id
            // ];
            // return response()->json($res);
            return redirect($request->root() . '/chat/' . $board->id);

        }
    }
    public function checkJoin(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
            'token' => 'required'
        ]);
        $target_chat = boardRecord::where('id', $request->id)->where('q_token', $request->token)->with(['icons' => function($q){
            $q->select('id','extension');
        }])->first();
        if($target_chat->able_join == 2){
            throw ValidationException::withMessages(['message' => 'groupIsPrivate']);
        }
        if(!empty($target_chat)){        
            $check_exists = $target_chat->board_to_users()->where('user_id', Auth::id())->exists();

            $res = [
                "chat" => $target_chat,
                "isMember" => $check_exists
            ];
            return response()->json($res);
        }else{
            throw ValidationException::withMessages(['message' => 'userNotFound']);
        }
    }
    public function checkInvite(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
            'token' => 'required'
        ]);
        
        $target_user = User::where('id', $request->id)->where('q_token', $request->token)->exists();
        if($target_user){        
            // $isFriend = Auth::user()->friends()->where('friend_id', $target_user->id)->where('status', 1)->exists();
            // $targetId = $target_user->id;
            // $hasBoard = BoardRecord::where('private_flag', 1)
            // ->whereHas('board_to_users', function ($query) use ($targetId) {
            //     $query->where('user_id', $targetId)->where('deleted_status', 0);                
            // })->whereHas('board_to_users', function ($query) {
            //     $query->where('user_id', Auth::id())->where('deleted_status', 0);                
            // })->first();
            // $res = [
            //     "user" => $target_user,
            //     "isFriend" => $isFriend,
            //     "hasChat" => $hasBoard
            // ];
            $data = $this->sharedService->getUserState($request->id, Auth::user());
            if($data && $data->is_blocked_by){
                throw ValidationException::withMessages(['message' => 'userNotFound']);
            }
            return response()->json($data);
        }else{
            throw ValidationException::withMessages(['message' => 'userNotFound']);
        }
        
    }
    

    public function getPossibleMemberList(Request $request){
        $key = $request->key;
        $userId = Auth::id();
        $notInclude = $request->exc ? $request->exc : [];
        $friendQuery = User::whereNotIn('id', $notInclude)->where('id', '!=', $userId)->where('retire', 0)->where('id', '>', 105)->select('id', 'name','icon_id')->get();

        
        return response()->json($friendQuery);


    }
    public function respondPartnerRequest(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
            'decision' => 'required'
        ]);
        $target_user = User::findOrFail($request->id);
        if($request->decision == 1){
            if (!Auth::user()->friends()->where('friend_id', $target_user->id)->exists()) {
                Auth::user()->friends()->attach($target_user->id, ['created_at' => now(), 'updated_at' => now(), 'status' => 1]);
            }
            $target_user->friends()->where('friend_id', Auth::id())->update(['status' => 1]);
            return response()->json('success', 200);
        }else if($request->decision == 0){
            Auth::user()->friends()->where('friend_id', $target_user->id)->detach();
            $target_user->friends()->where('friend_id', Auth::id())->detach();
            return response()->json('success', 200);
        }
        
    }
    public function toggleFriend (Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
            'token' => 'required'
        ]);
        $target_user = User::where('id', $request->id)->where('q_token', $request->token)->select('id', 'name', 'icon_id')->first();
        $user = Auth::user();
        if(!empty($target_user)){   
            if (!$user->friends()->where('friend_id', $target_user->id)->exists()) {
                $check_correspond = $target_user->friends()->where('friend_id', Auth::id())->where('status', 1)->exists() ? 1 : 0;
                $user->friends()->attach($target_user, ['created_at' => now(), 'updated_at' => now(), 'status' => $check_correspond]);
                $res = ["message" => "memberAttachSuccess"];
                if($check_correspond == 0){
                    $rebound = array(
                        "partner_request_to" => $target_user->id
                    );
                    event(new MessageSent($rebound));
                }
                return response()->json($res, 200);
            } else {
                $user->friends()->detach($target_user);
                $res = ["message" => "memberDetachSuccess"];
                return response()->json($res, 200);
            }            
        }
        throw ValidationException::withMessages(['message' => 'userNotFound']);
    }
    public function getPartnerRequests (Request $request){
        $friend_requests = Friend::where('friend_id', Auth::id())->where('status', 0)->with('user')->count();
        return response()->json($friend_requests);
    }
   

}
