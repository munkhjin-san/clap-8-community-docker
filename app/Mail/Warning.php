<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Warning extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($board_id, $board_title, $last_act, $delete_date, $language, $pattern)
    {
        $this->board_id = $board_id;
        $this->board_title = $board_title;
        $this->last_act = $last_act;
        $this->delete_date = $delete_date;
        $this->language = $language;
        $this->pattern = $pattern;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
        $path = 'emails.' . $this->pattern . '.' . $this->language;
        $chat_deletion_warning = [
            "en" => "Chat Deletion Warning",
            "ja" => "チャット自動削除のご連絡",
            "mn" => "Чатийг устгах талаар"
        ];
        $subjects = [
            "chat_deletion_warning" => $chat_deletion_warning,
        ];

        $subjectArray = $subjects[$this->pattern];
        // $subject = "[Glowddd]" . $this->from_name . $subjectArray[$this->language];
        $app_title = "[Glowd]";
        $sub = $app_title . ' ' . $subjectArray[$this->language];
        return $this->view($path)
        ->subject($sub)
        ->with([
            'board_id' => $this->board_id,
            'board_title' => $this->board_title,
            'last_act' => $this->last_act,
            'delete_date' => $this->delete_date,
            'language' => $this->language,
            'pattern' => $this->pattern
        ]);
    }
}
