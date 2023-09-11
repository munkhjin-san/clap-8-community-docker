<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Models\User;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::BOARD;

    
    function validatePhone($phoneNumber)
    {
        $client = new Client(config('app.twilio_sid'), config('app.twilio_auth_token'));
        
        try {
            $client->lookups->v1->phoneNumbers($phoneNumber);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function sendResetLinkPhone(Request $request)
    {
        if(filter_var($request->login, FILTER_VALIDATE_EMAIL)){
           $user = User::where('login', $request->login)->orWhere('email', $request->login)->where(function ($query) {
                        $query->whereNull('social_login');
                    })->first();
           if($user){
                $email = $user->email;
                $token = Str::random(64);
                $otp = rand(100000, 999999);
                DB::table('password_resets')->insert(
                        ['email' => $email, 'token' => $token, 'created_at' => now(), 'otp' => $otp]
                );
                Mail::to($email)->send(new VerifyEmail($user, $otp, $request->lang)); 
                return response()->json([
                            'token' => $token], 200);
           }else{
                return response()->json(['message' => 'emailDoesntExist'], 422);
           } 
           
        }else if(preg_match("/^[\d]{8,14}$/", $request->login)){
            $user = User::where('login', $request->login)->orWhere('phone', $request->login)->first();

            if($user){
                $phone = $request->phone_prefix . $request->login;
                $this->validatePhone($request);
                $table_token = Str::random(64);

                DB::table('password_resets')->insert(
                    ['email' => $request->login, 'token' => $table_token, 'created_at' => now()]
                );

                $this->sendSms($phone, $table_token);

                return response()->json([
                    'token' => $table_token
                ]);
            }else{
                return response()->json(['message' => 'phoneDoesntExist'], 422);
            }
        }else{
            return response()->json(['message' => 'emailOrPhone'], 422);
        }
    }
    protected function sendSms($phone, $table_token)
    {
        $sid    = config('app.twilio_sid');
        $token  = config('app.twilio_auth_token');
        $twilio = new Client($sid, $token);
        $twilio_verify_sid = config('app.twilio_verify_sid');
        $twilio->verify->v2->services($twilio_verify_sid)
                    ->verifications
                    ->create($phone, "sms");
    }
}
