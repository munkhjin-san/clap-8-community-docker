<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class mailTest extends Mailable
{
    use Queueable, SerializesModels;

    protected $title;
    protected $text;
    public $msg_id;
    public $content;
    public $block_flag;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name='テスト', $text='テストです。', $content = '', $block_flag='', $msg_id='NULL')
    {
        $this->title = $name;
        $this->text = $text;
        $this->msg_id = $msg_id;
        $this->content = $content;
        $this->block_flag = $block_flag;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.test_mail')
        ->text('emails.test_mail_plain')
        ->subject($this->title)
        ->with([
            'text' => $this->text,
            'msg_id' => $this->msg_id,
            'content' => $this->content,
            'block_flag' => $this->block_flag,
          ]);
    }
}
