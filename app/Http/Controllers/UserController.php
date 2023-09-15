<?php

declare(strict_types=1);

namespace App\Http\Controllers;
use DB;
use App\Models\User;
use App\Models\userDetail;
use App\Models\Tag;
use App\Models\Icons;

use App\Models\boardToUser;
use App\Models\taskUser;
use App\Models\boardRecord;
use App\Models\taskRecord;
use App\Models\messageReactedUser;
use App\Models\messageCheckUser;


use App\Events\Message;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\mailTest;
use Twilio\Rest\Client;
use App\Mail\VerifyEmail;
use stdClass;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\SharedService;
class UserController extends Controller{
    protected $sharedService;
    
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }

    public function index(Request $request){
        // $users = User::all();
        // foreach($users as $user){
        //     $user->q_token = Str::random(6);
        //     $user->save();
        // }
  
        // $client = new \GuzzleHttp\Client();        
        // $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode('https://glowd.mn/user/1');           
        // $response = $client->get($qrCodeUrl);    
        // Storage::put('qrcode.png', $response->getBody());    



        // return response()->file(storage_path('app/qrcode.png'));
        // $msg_id = 20;
        // $text = 'testmail';
        // $content = '';        
        // $name = '';
        // $b_title = '';
 

        // Mail::to('turuu2470@gmail.com')->send(new mailTest($name, $text, $content, $msg_id));
       
        


        $user_id = $request->id;
        if(empty($user_id)){
            return redirect('/profile/'. Auth::id());
        }
        $data = $this->sharedService->getUserState($request->id, Auth::user());
        return view('user', ['data' => $data]);
            
        

        

    }
    
    public function userPreIconUp(Request $request) {    
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        
        if($request->hasFile('file')) {          
            
            $file_extension = $request->file('file')->getClientOriginalExtension();
            $mime_type = $request->file('file')->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];         
            $file_size = $request->file('file')->getSize();       
            if($file_type !== 'image'){
                return response()->json("typeError");
            }
            // $set_path = $icon->id . '_' . $icon->profile_id . '_' . 'original' . '.' . $icon->extension;           
            $set_path = $auth_user_id . '_temp_'. $request->unique_id . '.jpg';
            $img = Image::make($request->file('file'))->encode('jpg', 75)->orientate();

            File::isDirectory(storage_path('app/profile_icon')) or File::makeDirectory(storage_path('app/profile_icon'), 0755, true, true);  
            if($file_size > 2000000){
                $img->save(storage_path('app/profile_icon/'.$set_path), 40);  
            }else{
                $img->save(storage_path('app/profile_icon/'.$set_path));  
            }
                    

            
          
            
            return response()->json($set_path);
        }
            return response()->json("no file");
       
    }
    public function croppedUp(Request $request) {    
        $user = Auth::user();
        $auth_user_id = Auth::id();

        $img = Image::make($request->file('croppedImage'))->fit(200);
        $unique_number = rand(1000, 9999); 
        $current_timestamp = time(); 
        $new_a_path = $current_timestamp . $unique_number;        

        $size_variants = [200, 120, 80, 45, 30, 25, 20, 15];
        foreach($size_variants as $size){
            $img_rsz = $img->resize($size, $size);               
            $set_path = $user->id . '_' . $new_a_path . '_' . $size . '.jpg';
            $temp_path = storage_path('app/temp/'.$set_path);
            $img_rsz->save($temp_path);
            if (!Storage::disk('s3')->exists('profile_icon')) {
                Storage::disk('s3')->makeDirectory('profile_icon');
            }
            Storage::disk('s3')->put('profile_icon/' . $user->id . '_' . $new_a_path . '_' . $size . '.jpg', file_get_contents($temp_path));
            unlink($temp_path); 
            Storage::disk('s3')->delete('profile_icon/' . $user->id . '_' . $user->a_path . '_' . $size . '.jpg');
        }
        $user->update(['a_path' => $new_a_path, 'a_version' => $user->a_version + 1]);
        return response()->json();
       
    }
    public function userPreIconDelete(Request $request) {
        $root_path = base_path();
        $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        File::delete($replaced . 'root/profile_icon/'.$request->file);
        return response()->json("deleted");
    }
    public function userIconCreate(Request $request) { 

            $create = $this->sharedService->createUserDefaultIcon(Auth::user());
            if($create){
                return response()->json("success");
            }          
     
            
            return response()->json($userName);
        
    }
    public function profileEdit(Request $request) { 

             
            $user = Auth::user();
            
            $user->name = $request->inputs['userName'];         
            
            
            $excludedChars = '/[^\p{L}\p{N}\s]/u'; 
            $tagNames = $request->inputs['selectedTags'];                
            $tagIds = collect($tagNames)->map(function ($tagName) use ($user, $excludedChars) {
                $tagName = preg_replace($excludedChars, '', $tagName);
                $tag = Tag::whereRaw('LOWER(name) = ?', [strtolower($tagName)])->first();
            
                if ($tag === null) {
                    $tag = Tag::create(['name' => $tagName]);
                }
            
                return $tag->id;
            });
            $user->tags()->sync($tagIds);
            $detachedTagIds = $user->tags()->whereNotIn('tags.id', $tagIds)->pluck('tags.id')->toArray();
            $user->tags()->detach($detachedTagIds);
            $userDetail = $user->user_detail ?? new userDetail;
            $userDetail->phone = $request->inputs['userPhone'];
            $userDetail->email = $request->inputs['userMail'];
            $userDetail->save();
            $userDetail->fill([
                'company' => $request->inputs['userCompany'],
                'occupation' => $request->inputs['userOccupation'],
                'profession' => $request->inputs['userProfession'],
                'intro' => $request->inputs['userIntro']
            ]);
            $user->user_detail()->save($userDetail);

            $user->save();
        
            return response()->json("saved");
        
    }
    public function loginEdit(Request $request){
        $user = Auth::user();

        
        if($request->phone_login){
            $tempuser = User::where('phone', $request->phone_login)->first();
            
            if($tempuser){
            return response()->json(['message' => 'phoneExist'], 422);
            }
            
            $codeSent = $this->verifyEdit($request, $request->phone_login);
            if($codeSent == true){
            $data = [
                "phoneOrMail" => $request->phone_login,
                "login" => $user->login,
                'prefix' => $request->country_code
            ];
            
            $user->save();
            return response()->json($data);
            } 
        } 
        if($request->email_login){
            $tempuser = User::where('email', $request->email_login)->where(function ($query) {
                $query->whereNull('social_login');
            })->first();

            if($tempuser){
                return response()->json(['message' => 'emailExist'], 422);
            }

            $codeSent = $this->verifyEdit($user, $request->email_login, $request->lang);
            if($codeSent == true){
                $data = [
                    "phoneOrMail" => $request->email_login,
                    "login" => $user->login,
                    'prefix' => $request->country_code
                ];
                $user->save();
                return response()->json($data);
            } 
        }
    }
    public function deleteEdit(Request $request){
        
        $user = Auth::user();         
        if(empty($request->phone_login) && !empty($user->email)){
            $user->phone = $request->phone_login;
            $user->login = $user->email;
            $user->phone_isVerified = 0;
        }
        if(empty($request->email_login) && !empty($user->phone)){
            $user->email = $request->email_login;
            $user->login = $user->phone;
            $user->email_verified_at = null;
        }
        
        $user->save();
    
        return response()->json("saved");
    }
    public function verifyEdit($request, $login, $lang){
        if(preg_match("/^[\d]{8,14}$/", $login)){
            $phone = $request->country_code . $login;
            $token = config('app.twilio_auth_token');
            $twilio_sid = config('app.twilio_sid');
            $twilio_verify_sid = config('app.twilio_verify_sid');
            $twilio = new Client($twilio_sid, $token);
            $twilio->verify->v2->services($twilio_verify_sid)
                ->verifications
                ->create($phone, "sms");
            
            return true;
        }else if(filter_var($login, FILTER_VALIDATE_EMAIL)){
            $min = 100000; 
            $max = 999999; 
            $otp = rand($min, $max);
            $request->otp = $otp;
            $request->save();
            $temp = new stdClass();
            $temp->email = $login;
            Mail::to($login)->send(new VerifyEmail($temp, $otp, $lang));
            
            return true;
        }
    }
    public function groupList(Request $request) {

        //ログインユーザー関連
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        $list = Group::where('user_id','=', $auth_user_id)->with(['user' => function($q){
            $q->with('icons');
        }])->with(['group_users' => function($q){
            $q->with('user');
        }])->orderBy('created_at', 'desc')->get();

        return response()->json($list);

    }


    public function groupAdd(Request $request) {
        //ログインユーザー情報取得
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        //nice_records ナレッジレコード保存
        if(!empty($request->group_name)){

            $group = new Group;
            $group->user_id = $auth_user_id;
            $group->name = $request->group_name;
            $group->save();

            //nice_to_users 中間テーブル保存処理
            if(!empty($request->group_member)){

                $group_members = $request->group_member;

                foreach($group_members as $group_member){

                    $groupUser = new groupUser;
                    $groupUser->groups_id = $group->id;
                    $groupUser->user_id = $group_member;
                    $groupUser->save();

                }

            }

        }

        return response()->json($request);

    }



    public function passChange(Request $request) {    
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'current' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $validator->setCustomMessages([
            'min' => __('min'),
            'confirmed' => __('confirmed'),
            'current_password' => __('currentPasswordIsNotMatch'),
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }    
        $user->password = bcrypt($request->password);
        $user->save();
    
        return response()->json(['message' => 'passwordUpdatedSuccessfully'], 200);
    }  
    public function generate_key(Request $request){

        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        $result = implode($pass);
        Auth::user()->file_key = $result;
        Auth::user()->save();
        return response()->json($result);  
        
    }
    public function cdnMovie(Request $request){
        try {
            $root_path = base_path();
            $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));           
            $p1 = $replaced . 'root/user_album/'. $request->folder_id . '/' . $request->path;  
            return response()->file($p1);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }
    public function getTags(Request $request){
        $tags = User::findOrFail($request->id)->tags;
        return response()->json($tags);
    }
    public function getSearchTags(Request $request){
        $tags = Tag::where('name', 'LIKE', '%' . $request->key . '%')->pluck('name')->toArray();
        return response()->json($tags);
    }
    public function getUpdateUser (Request $request){
        $data = $this->sharedService->getUserState($request->id, Auth::user());
        
        if(!empty($data)){
            $data->load('tags');
            return response()->json($data);
        }
        abort(404);
        
    }    
    public function setPrivacy (Request $request){     
        $validatedData = $request->validate([
            'value' => 'required',
        ]);   
        try {
            $request->user()->update(['is_public' => $request->value]);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
    }
    public function setColor (Request $request){     
        $validatedData = $request->validate([
            'value' => 'required',
        ]);   
        try {
            $request->user()->update(['color' => $request->value]);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
    }
    public function generateNewUserQrCode (Request $request){                
        try {
            $type = 'user_qr_code';
            $id = $request->user()->id;
            $current_token = $request->user()->q_token;
            $newCode = $this->sharedService->newUserQrCode($type, $id, $current_token);
            if($newCode){
                $request->user()->update(['q_token' => $newCode]);  
                return response()->json('success', 200);
            }
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
    }
    public function deleteAccount (Request $request){                
        $user = Auth::user();   
        $adminPrivilageBoards = boardRecord::where('private_flag', 0)->whereHas('board_to_users', function ($q){
            $q->where('user_id', Auth::id())->where('admin_flag', 1);
        })->with('board_to_users')->get();
        foreach($adminPrivilageBoards as $board){
            $hasOtherMember = $board->board_to_users()->where('user_id', '!=', Auth::id())->count();
            if($hasOtherMember){
                $hasOtherAdmin = $board->board_to_users()->where('user_id', '!=', Auth::id())->where('admin_flag', 1)->exists();
                if(!$hasOtherAdmin){
                    $board->board_to_users()
                    ->where('user_id', '!=', Auth::id())
                    ->where('admin_flag', 0)
                    
                    ->orderBy('id')
                    ->limit(1)
                    ->update(['admin_flag' => 1]);
                }
            }          
            

        }
        $allBoards = boardRecord::where('private_flag', 0)->whereHas('board_to_users', function ($q){
            $q->where('user_id', Auth::id());
        })->with('board_to_users')->get();
        foreach($allBoards as $board){
            $board->board_to_users()->where('user_id', Auth::id())->delete();
            $tasks = taskRecord::where('board_id', $board->id)->whereHas('task_users', function ($q){
                $q->where('user_id', Auth::id());
            })->has('task_users', '=', 1)->delete();
        }
        $taskMembers = taskUser::where('user_id', $user->id)->delete();
        $user->friends()->detach();
        $user->tags()->detach();
        $user->user_detail()->forceDelete();
        $reactions = $user->reactions()->detach();
        $checks = $user->checks()->detach();
        $size_variants = [200, 120, 80, 45, 30, 25, 20, 15];
        foreach($size_variants as $size){
            Storage::disk('s3')->delete('profile_icon/' . $user->id . '_' . $user->a_path . '_' . $size . '.jpg');   
        }
        Storage::disk('s3')->delete('user_qr_code/' . $user->q_token . '_' . $user->id . '.png');   
        $user->forceDelete();
        Auth::logout();
        return response()->json('success', 200);

    }
    public function setLanguage (Request $request){  
        $request->user()->update(['language' => $request->value]);
    }  
      
   
}
