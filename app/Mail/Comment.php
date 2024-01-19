<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Comment extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $content, $comment_id, $app_name, $record_id)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->comment_id = $comment_id;
        $this->app_name = $app_name;
        $this->record_id = $record_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
      
        return $this->view('emails.post.comment')
        ->subject($this->subject)
        ->with([
            'content' => $this->content,
            'comment_id' => $this->comment_id,
            'record_id' => $this->record_id,
            'app_name' => $this->app_name
        ]);
    }
}
