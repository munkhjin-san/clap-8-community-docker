<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Confirm extends Mailable
{
    use Queueable, SerializesModels;
    public $content;
    public $blocked;
    public $board_id;
    public $type;
    public $message_id;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $content, $blocked, $board_id, $message_id, $type)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->blocked = $blocked;
        $this->board_id = $board_id;
        $this->message_id = $message_id;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
      
        return $this->view('emails.board.' . $this->type)
        ->subject($this->subject)
        ->with([
            'content' => $this->content,
            'blocked' => $this->blocked,
            'board_id' => $this->board_id,
            'message_id' => $this->message_id
        ]);
    }
}
