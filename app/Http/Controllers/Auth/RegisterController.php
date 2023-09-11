<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Intervention\Image\Facades\Image;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;
use App\Models\Icons;
use App\Models\boardRecord;
use App\Models\boardToUser;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;
use Twilio\Rest\Verify\V2\ServiceContext;
use App\Models\tempUser;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Models\PasswordReset;
use App\Services\SharedService;
use Illuminate\Validation\ValidationException;
use App\Models\userDetail;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::BOARD;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
        $this->middleware('guest');
        $this->middleware('throttle:3,1')->only('guestregister');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $request = request();
        return Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
       
        $request = request();
        if($request->user_id){
            $tempUser = tempUser::findOrFail($data['user_id']);
            $user = new User;
            $user->login = intval(strtotime("now"));
            if($tempUser->which == 0){
                $user->email = $tempUser->value;
                $user->email_verified_at = date('Y-m-d H:i:s');
            }else if($tempUser->which == 1){
                $user->phone = $tempUser->value;
                $user->phone_isVerified = true;
            }
            // $user->phone_prefix = $tempUser->phone_prefix;
            $user->name = $data['name'];     
            $user->password = Hash::make($data['password']);
            $user->is_public = 1;
            $user->save();
            
            
            $userdetail = new userDetail;
            $userdetail->user_id = $user->id;
            $userdetail->save();

            $board = new boardRecord;
            $board->user_id = $user->id;
            $board->title = 'My chat';
            $board->private_flag = 3;
            $board->last_activity = now();
            $board->save();

            $self = new boardToUser;
            $self->record_id = $board->id;
            $self->user_id = $user->id;
            $self->invited_by = $user->id;
            $self->joined_at = now();
            $self->member_status = 1;
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
            $tempUser->delete();
        }else if($request->user_login){
            if(filter_var($request->user_login, FILTER_VALIDATE_EMAIL)){
                $user = User::where('email', $request->user_login)->where(function ($query) {
                    $query->whereNull('social_login');
                })->first();
                $user->password = Hash::make($data['password']);
                $user->save();
            }else{
                $user = User::where('login', $request->user_login)->first();
                $user->password = Hash::make($data['password']);
                $user->save();
                
                
            }
        }

        return $user;

        
        
    }
    public function register(Request $request)
    {
        if($request->user_id){
            $this->validator($request->all())->validate();
        }
        $user = $this->create($request->all());
        if (!$user) {
            return response()->json(['message' => 'Дээрх мэйл хаяг эсвэл утасны дугаар бүртгэлтэй байна.'], 422);
        }
        Auth::guard('web')->login($user);

        return response()->json($user);
        // event(new Registered($user));
        // if($user->email){
        //     Auth::guard('web')->login($user);
        //     return response()->json(['redirect' => '/email/verify']);
        // }else if($user->phone){
        //     $encoded_phone_prefix = urlencode($user->phone_prefix);
        //     $encoded_phone = urlencode($user->phone);
        //     $url = $encoded_phone_prefix . '&phone=' . $encoded_phone;
        //     return response()->json(['redirect' => '/phone/verify?phone_prefix=' . $url]);
        // }
    }

    public function guestregister(Request $data)
    {
        if(filter_var($data['login'], FILTER_VALIDATE_EMAIL)){
            $user = User::where('login', $data['login'])
            ->orWhere('email', $data['login'])
            ->where(function ($query) {
                $query->whereNull('social_login');
            })
            ->first();
            if ($user) {
                // Redirect the user to a specific page with an error message
                return response()->json(['message' => 'emailExist'], 422);
            }

           
            $min = 100000; 
            $max = 999999; 
            $otp = rand($min, $max);
            $user = tempUser::create([
                'which' => 0,
                'token' => $data['token'],
                'value' => $data['login'],
                'otp' => $otp
            ]);
            Mail::to($data['value'])->send(new VerifyEmail($user, $otp, $data['lang']));
        } else if(preg_match("/^[\d]{8,14}$/", $data['login'])){
            $user = User::where('login', $data['login'])->orWhere('phone', $data['login'])->first();
            
            if ($user) {
                // Redirect the user to a specific page with an error message
                return response()->json(['message' => 'phoneExist'], 422);
            }
            $phone = $data['phone_prefix'] . $data['login'];
            if(preg_match("/^\+?[0-9]{10,14}$/", $phone)){
                $token = config('app.twilio_auth_token');
                $twilio_sid = config('app.twilio_sid');
                $twilio_verify_sid = config('app.twilio_verify_sid');
                $twilio = new Client($twilio_sid, $token);
                $twilio->verify->v2->services($twilio_verify_sid)
                    ->verifications
                    ->create($phone, "sms");   
                $user = tempUser::create([
                    'which' => 1,
                    'token' => $data['token'],
                    'value' => $data['login'],
                    'phone_prefix' => $data['phone_prefix'],
                ]);
            }else{
                return response()->json(['message' => 'validPhone'], 422);
            }
            
        }else{
            return response()->json(['message' => 'emailOrPhone'], 422);
        }
        return response()->json($user->token);
    }
}
