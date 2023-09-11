<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;

class tempUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'token', 'which', 'value', 'otp', 'phone_prefix'
    ];
    public function sendEmailVerificationNotification()
    {
        if($this->value){
            Mail::to($this->value)->send(new VerifyEmail($this));
        }
    }
}
