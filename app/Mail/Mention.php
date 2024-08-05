<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Mention extends Mailable 
{
    use Queueable, SerializesModels;

    public $subject;
    public $content;
    public $blocked;
    public $board_id;
    public $message_id;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $content, $blocked, $board_id, $message_id)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->blocked = $blocked;
        $this->board_id = $board_id;
        $this->message_id = $message_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
      
        return $this->view('emails.board.mention')
        ->subject('【メンション】' . $this->subject)
        ->with([
            'content' => $this->content,
            'blocked' => $this->blocked,
            'board_id' => $this->board_id,
            'message_id' => $this->message_id
        ]);
    }
}
