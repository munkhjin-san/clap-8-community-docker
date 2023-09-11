<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use App\Models\tempUser;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use stdClass;
use DB;

class PhoneVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:3,1')->only('verify');
        $this->middleware('throttle:3,1')->only('sendCodeAgain');
    }
    // Verify phone number
    protected function verify(Request $request)
    {
        try {
            $this->middleware('throttle:3,1')->only('verify');
            $current_date_time = date('Y-m-d H:i:s');
            if($request->password_login){
                $data = $request->validate([
                    'verification_code' => ['required', 'numeric'],
                    'password_login' => ['required', 'string'],
                    'phone_prefix' => ['required', 'string']
                ]);
                if(filter_var($data['password_login'], FILTER_VALIDATE_EMAIL)){
                    $passworduser = PasswordReset::where('token', $request->token)->first();
                    $created_date_time = $passworduser->created_at;
                    $interval = $created_date_time->diff($current_date_time);
                    $minutes = $interval->format('%i');
                    if($minutes > 10){
                        $min = 100000; 
                        $max = 999999; 
                        $otp = rand($min, $max);
                        DB::table('password_resets')
                        ->where('token', '=', $request->token)
                        ->update([
                            'created_at' => now(),
                            'otp' => $otp
                        ]);
                        return response()->json(['message' => __('codeExpired')], 422);
                    }
                    if($passworduser->otp == $data['verification_code']){
                        $data = [
                            'login' => $passworduser->email
                        ];
                        return response()->json($data, 200);
                    }else{
                        return response()->json([
                            'message' => __('wrongCode')
                        ], 422);
                    }
                }else{   
                    $phone = $data['phone_prefix'] . $data['password_login'];
                    $token = config('app.twilio_auth_token');
                    $twilio_sid = config('app.twilio_sid');
                    $twilio_verify_sid = config('app.twilio_verify_sid');
                    $twilio = new Client($twilio_sid, $token);
                    $verification = $twilio->verify->v2->services($twilio_verify_sid)
                        ->verificationChecks
                        ->create(array('code' => $data['verification_code'], 'to' => $phone));
                        
                    if ($verification->valid) {
                        $passworduser = PasswordReset::where('token', $request->token)->first();
                        $data = [
                            'login' => $passworduser->email
                        ];
                        return response()->json($data, 200); 
                    }
                    return response()->json([
                        'message' => __('wrongCode')
                    ], 422);
                } 
            }else if($request->phone){
                $data = $request->validate([
                    'verification_code' => ['required', 'numeric'],
                    'phone' => ['required', 'string'],
                    'phone_prefix' => ['required', 'string']
                ]);
                
                if(filter_var($data['phone'], FILTER_VALIDATE_EMAIL)){
                    $tempuser = tempUser::where('token', $request->token)->first();
                    $created_date_time = $tempuser->created_at;
                    $interval = $created_date_time->diff($current_date_time);
                    $minutes = $interval->format('%i');
                    if($minutes > 10){
                        $min = 100000; 
                        $max = 999999; 
                        $otp = rand($min, $max);
                        $tempuser->otp = $otp;
                        $tempuser->created_at = $current_date_time;
                        $tempuser->save();
                        return response()->json(['message' => __('codeExpired')], 422);
                    }
                    if($tempuser->otp == $data['verification_code']){
                        $data = [
                            'id' => $tempuser->id
                        ];
                        return response()->json($data, 200);
                    }else{
                        return response()->json([
                            'message' => __('wrongCode')
                        ], 422);
                    }
                }else{   
                    $phone = $data['phone_prefix'] . $data['phone'];
                    $token = config('app.twilio_auth_token');
                    $twilio_sid = config('app.twilio_sid');
                    $twilio_verify_sid = config('app.twilio_verify_sid');
                    $twilio = new Client($twilio_sid, $token);
                    $verification = $twilio->verify->v2->services($twilio_verify_sid)
                        ->verificationChecks
                        ->create(array('code' => $data['verification_code'], 'to' => $phone));
                    if ($verification->valid) {
                        // $user = tap(User::where('phone', $data['phone']))->update(['phone_isVerified' => true]);
                        /* Authenticate user */
                        // Auth::login($user->first());
                        // return redirect()->route('board')->with(['message' => 'Phone number verified']);
                        $tempuser = tempUser::where('token', $request->token)->first();
                        $data = [
                            'id' => $tempuser->id
                        ];
                        return response()->json($data, 200); 
                    }
                    // return back()->with(['phone' => $data['phone'], 'error' => 'Нэг удаагийн код буруу байна!']);
                    return response()->json([
                        'message' => __('wrongCode')
                    ], 422);
                } 
            }else if($request->editData){
                $data = $request->validate([
                    'verification_code' => ['required', 'numeric'],
                    'editData' => ['required', 'string'],
                    'phone_prefix' => ['required', 'string']
                ]);
                
                if(filter_var($data['editData'], FILTER_VALIDATE_EMAIL)){
                    $user = User::where('login', $request->login_token)->first();
                    if($user->otp == $data['verification_code']){
                        $user->email = $request->editData;
                        $user->email_verified_at = date('Y-m-d H:i:s');
                        $user->save();
                        return response()->json('saved');
                    }else{
                        return response()->json([
                            'message' => __('wrongCode')
                        ], 422);
                    }
                }else{   
                    $phone = $data['phone_prefix'] . $data['editData'];
                    $token =  config('app.twilio_auth_token');
                    $twilio_sid = config('app.twilio_sid');
                    $twilio_verify_sid = config('app.twilio_verify_sid');
                    $twilio = new Client($twilio_sid, $token);
                    $verification = $twilio->verify->v2->services($twilio_verify_sid)
                        ->verificationChecks
                        ->create(array('code' => $data['verification_code'], 'to' => $phone));
                    if ($verification->valid) {
                        // $user = tap(User::where('phone', $data['phone']))->update(['phone_isVerified' => true]);
                        /* Authenticate user */
                        // Auth::login($user->first());
                        // return redirect()->route('board')->with(['message' => 'Phone number verified']);
                        $user = User::where('login', $request->login_token)->first();
                        $user->phone = $request->editData;
                        $user->phone_prefix = $request->phone_prefix;
                        $user->phone_isVerified = true;
                        $user->save();
                        return response()->json('saved'); 
                    }
                    // return back()->with(['phone' => $data['phone'], 'error' => 'Нэг удаагийн код буруу байна!']);
                    return response()->json([
                        'message' => __('wrongCode')
                    ], 422);
                } 
            }

            
        }catch (ThrottleRequestsException $exception) {
            // Handle the exception here, for example:
            return response()->json([
                'message' => __('Хэт их оролдлого хийж байгаа тул :seconds секундийн дараа дахин хийнэ үү.', [
                    'seconds' => $exception->getHeaders()['Retry-After'] ?? 60
                ])
            ], 429);
        }
        
    }

    // Send code again
    public function sendCodeAgain(Request $request)
    {   
        try {
            $this->middleware('throttle:3,1')->only('sendCodeAgain');

            // Get the current user
            // $user = Auth::user();
            
            // if($user){
            //     $phone = $user->phone_prefix . $user->phone;
            //     $encoded_phone_prefix = urlencode($user->phone_prefix);
            //     $url = $encoded_phone_prefix . '&phone=' . $user->phone;
            // }
            if($request->password_login){
                $data = $request->validate([
                    'password_login' => ['required', 'string'],
                    'phone_prefix' => ['required', 'string']
                ]);
                $passworduser = PasswordReset::where('token', $request->token)->first();
                if(filter_var($data['password_login'], FILTER_VALIDATE_EMAIL)){
                    Mail::to($data['password_login'])->send(new VerifyEmail($passworduser, $passworduser->otp, $request->lang));
                }else if($data['phone_prefix']){
                    $phone = $data['phone_prefix'] . $data['password_login'];
                    // Generate a new code with Twilio Verify API
                    $twilio_verify_sid = config('app.twilio_verify_sid');
                    $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                    $verification = $twilio->verify->v2->services($twilio_verify_sid)
                        ->verifications
                        ->create($phone, 'sms');
                }
                $data = [
                    'otp' => $passworduser->otp,
                    'message' => __('newCode')
                ];
                // Redirect back to the verification page
                return response()->json($data);
            }else if($request->phone){
                $data = $request->validate([
                    'phone' => ['required', 'string'],
                    'phone_prefix' => ['required', 'string']
                ]);
                $user = tempUser::where('token', $request->token)->first();
                if(filter_var($data['phone'], FILTER_VALIDATE_EMAIL)){
                    Mail::to($data['phone'])->send(new VerifyEmail($user, $user->otp, $request->lang));
                }else if($data['phone_prefix']){
                    $phone = $data['phone_prefix'] . $data['phone'];
                    // Generate a new code with Twilio Verify API
                    $twilio_verify_sid = config('app.twilio_verify_sid');
                    $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                    $verification = $twilio->verify->v2->services($twilio_verify_sid)
                        ->verifications
                        ->create($phone, 'sms');
                }
                // Redirect back to the verification page
                return response()->json(['message' => __('newCode')]);
            }else if($request->editData){
                $data = $request->validate([
                    'editData' => ['required', 'string'],
                    'phone_prefix' => ['required', 'string']
                ]);
                $temp = new stdClass();
                $temp->email = $request->editData;
                $user = User::where('login', $request->login_token)->first();
                if(filter_var($data['editData'], FILTER_VALIDATE_EMAIL)){
                    Mail::to($data['editData'])->send(new VerifyEmail($temp, $user->otp, $request->lang));
                }else if($data['phone_prefix']){
                    $phone = $data['phone_prefix'] . $data['editData'];
                    // Generate a new code with Twilio Verify API
                    $twilio_verify_sid = config('app.twilio_verify_sid');
                    $twilio = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
                    $verification = $twilio->verify->v2->services($twilio_verify_sid)
                        ->verifications
                        ->create($phone, 'sms');
                }
                // Redirect back to the verification page
                return response()->json(['message' => __('newCode')]);
            }
            
        }catch (ThrottleRequestsException $exception) {
            // Handle the exception here, for example:
            return response()->json([
                'message' => __('Too many attempts. Please try again in :seconds seconds.', [
                    'seconds' => $exception->getHeaders()['Retry-After'] ?? 60
                ])
            ], 429);
        }
    }
}
