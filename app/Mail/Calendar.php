<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Calendar extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $subject;
    public $type;

    public $is_temp;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details, $subject, $type, $is_temp)
    {
        $this->details = $details;
        $this->subject = $subject;
        $this->type = $type;
        $this->is_temp = $is_temp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
      
        return $this->view('emails.calendar.created')
        ->subject('【カレンダー】' . $this->subject)
        ->with([
            'details' => $this->details,
            'type' => $this->type,
            'is_temp' => $this->is_temp,
        ]);
    }
}
