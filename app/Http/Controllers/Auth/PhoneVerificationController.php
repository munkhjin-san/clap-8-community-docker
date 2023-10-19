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
    
    
}
