<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;


    public $user;
    public $otp;
    public $lang;

    /**
     * Create a new message instance.
     *
     * @param  mixed  $user
     * @param  int  $otp
     * @param  string  $lang
     * @return void
     */
    public function __construct($user, $otp, $lang)
    {
        $this->user = $user;
        $this->otp = $otp;
        $this->lang = $lang;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = null;
        $subject = null;
        $view = null;

        switch ($this->lang) {
            case 'en':
                $email = $this->user->email ?? $this->user->value ?? $this->user;
                $subject = '[Glowd] This email is your verification code';
                $view = 'emails.verify-email-en';
                break;
            case 'ja':
                $email = $this->user->email ?? $this->user->value ?? $this->user;
                $subject = '[Glowd]このメールは確認コードです';
                $view = 'emails.verify-email-ja';
                break;
            case 'mn':
                $email = $this->user->email ?? $this->user->value ?? $this->user;
                $subject = '[Glowd] Энэхүү имэйл нь таны баталгаажуулах код юм';
                $view = 'emails.verify-email-mn';
                break;
        }
        return $this->to($email)
            ->subject($subject)
            ->view($view)
            ->with('otp', $this->otp);
    }
}
