<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\userDetail;
use App\Models\Icons;
use App\Models\boardRecord;
use App\Models\boardToUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\SharedService;
class AdminAccountController extends Controller
{
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }
    public function index(Request $request){
        $accessable = [ 1, 2, 183 ];
        if (in_array(Auth::id(), $accessable)) {
            return view('adminAccount');
        }
        else{
            return redirect('home');  
        }
        
    }
    public function getUser(Request $request){
        $user_list = User::with('user_detail')->get();

        $data = [
            "user_list" => $user_list,
        ];

        return response()->json(
            $data
        );
    }
    public function addUser(Request $request){
        $validatedData = $request->validate(
            ['user_login' => 'unique:users,login,'],
            ['user_login.unique' => 'このログインIDはすでに登録されています']
        );

            $user = new User;
            $user->name = $request->user_name;
            $user->email = $request->user_email;
            $user->login = intval(strtotime("now"));
            $user->password = bcrypt($request->user_password);
            $user->save();


            $user_detail = new userDetail;
            $user_detail->phone = $request->user_phone;
            $user_detail->intro = $request->user_memo;
            $user_detail->company = $request->user_company;
            $user_detail->occupation = $request->user_occupation;
            $user_detail->profession = $request->user_profession;
            $user_detail->user_id = $user->id;
            $user_detail->save();

            $board = new boardRecord;
            $board->user_id = $user->id;
            $board->title = 'My chat';
            $board->private_flag = 3;
            $board->save();

            $self = new boardToUser;
            $self->record_id = $board->id;
            $self->user_id = $user->id;
            $self->invited_by = $user->id;
            $self->joined_at = now();
            $self->invited_at = now();
            $self->admin_flag = 1;
            $self->save();
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
                  
            $arr = [
                "message" => "success",
                "success" => true,
                "data" => $user,
                "detail" => $user_detail
            ]; 
            return response()->json($arr);
        
    }

    public function deleteUser(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
          
        if(empty($auth_user_id)){               
            return response()->json("loggedOut");
        }
        if(!empty($request->id)){

            $user = User::where('id', $request->id)->first();
            $user->delete();
            $user->save();

        }

        return response()->json();
    }
    public function editUser(Request $request){
        $root_path = base_path();
        $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('zclap', '', $root_path));

            $user = User::where('id', $request->user_id)->first();
            $user->name = $request->user_name;
            $user->email = $request->user_email;
            
            $user->password = bcrypt($request->user_password);
            $user->save();
            
          
            $user_detail = userDetail::where('user_id', $request->user_id)->first();
            $user_detail->company = $request->user_company;
            $user_detail->phone = $request->user_phone;
            $user_detail->occupation = $request->user_occupation;
            $user_detail->profession = $request->user_profession;
            $user_detail->intro = $request->user_memo;
            $user_detail->user_id = $user->id;
            $user_detail->save();
            
            $arr = [
                "message" => "success",
                "success" => true,
                "data" => $user,
                
            ]; 
            return response()->json($arr);
    }
    //
}
