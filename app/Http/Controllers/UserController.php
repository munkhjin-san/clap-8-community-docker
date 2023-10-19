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
use App\Models\NiceRecord;
use App\Models\ClapRecord;
use App\Models\ChallengeRecord;
use App\Models\UserAlbum;
use Carbon\Carbon;

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
            return redirect('/user/'. Auth::id());
        }
        $data = $this->sharedService->getUserState($request->id, Auth::user());
        return view('user', ['data' => $data]);
            
        

        

    }
    public function saveSignature(Request $request){
        $auth_id = Auth::id();
        $user = User::findOrFail($auth_id);
        $unique_number = rand(1000, 9999); 
        $current_timestamp = time(); 
        $new_a_path = $current_timestamp . $unique_number; 
        $set_path = $user->id . '_' . $new_a_path . '.png';
        File::isDirectory(storage_path('app/user_signatures')) or File::makeDirectory(storage_path('app/user_signatures'), 0755, true, true);
        Storage::disk('local')->putFileAs(
            '/user_signatures', $request->sign, $set_path
        );  
        Storage::disk('local')->delete('user_signatures/' . $user->id . '_' . $user->sign_path . '.png');
        
        $user->sign_path = $new_a_path;
        $user->save();
        return response()->json($user);
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
        if (!Storage::disk('local')->exists('profile_icon')) {
            Storage::disk('local')->makeDirectory('profile_icon');
        }

        $old_icon = Icons::where('user_id', $auth_user_id)->where('use_of', 'profile')->first();

        $orgImage = json_decode($request->get('orgImage'), true);
        $imageUrl = $orgImage['url'];

        $imageData = base64_decode(preg_replace('#^data:\w+/\w+;base64,#i', '', $imageUrl));
        $org_img = Image::make($imageData)->orientate();
        
        $icon = new Icons;
                
        $icon->mime_type = 'image';
        $icon->extension = 'jpg';       
        $icon->user_id = $user->id;
        $icon->profile_id = $user->id;
        $icon->use_of = "profile";
        $icon->save();
        $org_path = $icon->id . '_' . $user->id . '_x.jpg';
        $path_for_org = storage_path('app/profile_icon/'.$org_path);
        $org_img->save($path_for_org);
        foreach($size_variants as $size){
            $img_rsz = $img->resize($size, $size);
            $set_path = $icon->id . '_' . $user->id . '_' . $size . '.jpg';
            $temp_path = storage_path('app/profile_icon/'.$set_path);
            $img_rsz->save($temp_path);
            if($old_icon){
                Storage::disk('local')->delete('profile_icon/' . $old_icon->id . '_' . $auth_user_id . '_' . $size . '.jpg');
               
            }

        }
        if($old_icon){
            $old_icon->delete();
        }
        $user->update(['icon_id' => $icon->id]);
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
            $user->awareness = $request->inputs['userAwareness'];         
            $user->phone_number = $request->inputs['userPhone'];
            $user->work_email = $request->inputs['userMail'];
           
            $user->motto = $request->inputs['userMotto'];
            $user->enjoy = $request->inputs['userEnjoy'];
            $user->recommend = $request->inputs['userRecommend'];
            $user->intro = $request->inputs['userIntro'];
                
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
            return response()->json($data);
        }
        abort(404);
        
    }
    public function deleteMov(Request $request){
        if(!empty($request)){
            $user_id_int = $request->delete_id;
            $path = '/user_files' . '/' . $user_id_int;
            $intro_record = userAlbum::where('deleted_flag', '=', 0)->where('user_id', $user_id_int)->where('intro_flag', '=', 1)->first();  
            if(!empty($intro_record)){
                $intro_record->delete();
                Storage::disk('local')->delete($path . '/' . $intro_record->path);
                return response()->json('saved');  
            }
        }
    }
    public function uploadMov(Request $request){
        $user_id_int = (int)$request->user_id;
        $intro_check = userAlbum::where('deleted_flag', 0)->where('user_id', $user_id_int)->where('intro_flag', '=', 1)->exists();  
        $auth_user_id = Auth::id();
          
        if(empty($auth_user_id)){               
            return response()->json("loggedOut");
        }
        if($intro_check){
            return response()->json("introExists");
        } 
        
       

        
        $path = '/user_files' . '/' . $user_id_int;
        $file_path = 'intro_' . $user_id_int;        
        $file_extension = $request->file('file')->getClientOriginalExtension();        
        $mime_type = $request->file('file')->getMimeType();      
        $file_size = $request->file('file')->getSize();
        $file_real_name = request()->file->getClientOriginalName();  
        if($file_extension !== 'mp4' && $file_extension !== 'MOV' && $file_extension !== 'mov' && $file_extension !== 'mkv'){
            return response()->json("typeError");
        } 
        File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . $path, 0755, true, true);                      
        
        $set_path = $file_path . '.' . $file_extension;
        Storage::disk('local')->putFileAs(
            $path, $request->file('file'), $set_path
        );
       
        
        $album = new userAlbum;
        $album->user_id = $user_id_int;
        $album->path = $set_path;
        $album->name = $file_real_name;
        $album->created_by = $auth_user_id;
        $album->mime_type = $mime_type;
        $album->extension = $file_extension; 
        $album->intro_flag = 1; 
        $album->save();
       
        return response()->json($set_path);
    }
    public function userFileUpload(Request $request){
        $ids = [];
        $path = $request['path'] . '/' . $request['user_id'];
        // return response()->json($path);
        foreach($request->file() as $file ){
            $file_path = date("YmdHis") . md5(uniqid());           
            $file_extension = $file->getClientOriginalExtension();
            $file_real_name = $file->getClientOriginalName();            
            $mime_type = $file->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];            
            $file_size = $file->getSize();  
            if($file_type == 'video' || $file_type == 'image'){
                $album = new UserAlbum;
                $album->path =  $file_path;
                $album->name = $file_real_name;
                $album->mime_type = $file_type;
                $album->extension = $file_extension;
                $album->intro_flag = 2;
                $album->created_by = Auth::id();
                $album->user_id = Auth::id();
                $album->save();
                $set_path = "{$album->id}_{$album->user_id}_{$file_path}.{$album->extension}";

                
                if($file_type == 'image' && $file_extension !== 'svg'){
                    $img = Image::make($file)->orientate();
                        
                    File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                      
                    $img->save(storage_path('app') . $path .'/'. $set_path, 30);  
                    
                }else{
                    Storage::disk('local')->putFileAs(
                        $path, $file, $set_path
                    );
                } 
                $ids[] = $album;   
            }                    
        }
        return response()->json($ids); 
    }
    private function delete_file_execute($list, $path){
        $files = UserAlbum::whereIn('id', $list)->get();
        foreach($files as $file){
            Storage::disk('local')->delete($path . '/' . $file->id . '_' . $file->user_id . '_' . $file->path . '.' . $file->extension);
            $file->delete();
        }
        return $files;
    }
    public function userDeleteFile(Request $request){
        $validatedData = $request->validate([
            'list' => 'required',
        ]);
        $result = $this->delete_file_execute($request->list, $request->path);
        return $result;
    }
    public function profile_get_update_user (Request $request){
        $today = Carbon::now()->format('Y-m-d');

        //ログインユーザー関連
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        $list = User::where('id', '=', $request->id)
        ->where('deleted_flag','=', 0)->with('positions')->with('offices')->with('icons')->with(['user_album' => function($q){
            $q->where('deleted_flag','=', 0);
        }])
        ->with(['weathers' => function($q) use($today){
            $q->where('type_id', 43)->where('date', $today);
        }])->with(['days_weathers' => function($q) use($today){
            $q->where('type_id', 43)->where('deleted_flag', 0)
            ->where('date', '<', $today)
            ->orderBy('date', 'desc')
            ->limit(5);
        }])
        ->select(
            'id',
            'name',
            'name_kana',
            'icon_id',
            'phone_number',
            'work_email',
            'motto',
            'intro',
            'recommend',
            'office_id',
            'position_id',
            'user_code',
            'enjoy',
            'awareness',
            'recommend',
            'color',
            'sign_path'
        )
        ->first();         

        return response()->json($list);
        
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
      
    public function getClaps(Request $request){
        $var_id = $request->id;

        if(!empty($request)){
            $niceTo = niceRecord::where('deleted_flag', '=', 0)->whereHas('to_users', function($q) use ($var_id){
                $q->where('user_id', $var_id);
            })->get()->pluck('clap_count')->sum();

            $niceFrom = niceRecord::where('deleted_flag', '=', 0)->where('user_id', '=', $var_id)->get()->pluck('clap_count')->sum();

            $allNiceClap = $niceTo + $niceFrom;

            $allchallengeClap = challengeRecord::where('deleted_flag', '=', 0)->whereHas('to_users', function($q) use ($var_id){
                $q->where('user_id', $var_id);
            })->get()->pluck('clap_count')->sum();           


            $allknowledgeClap = clapRecord::where('app_name', '=', 'knowledge')->where('to_users', '=', $request->id)->where('deleted_flag', '=', 0)->count();

            $sum = $allNiceClap + $allchallengeClap + $allknowledgeClap;

            $claps = [
                "nice" => $allNiceClap,
                "challenge" => $allchallengeClap,
                "knowledge" => $allknowledgeClap,
                "sum" => $sum
             ];

            return response()->json($claps);
        }
    }
}
