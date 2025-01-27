<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Summary extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $id;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details, $id)
    {
        $this->details = $details;
        $this->id = $id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
      
        return $this->view('emails.calendar.summary')
        ->subject('【スケジュール】ウエブ会議の要約が届きました。' )
        ->with([
            'details' => $this->details,
            'id' => $this->id,
        ]);
    }
}
