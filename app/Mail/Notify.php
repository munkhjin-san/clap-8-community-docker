<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Notify extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($url, $content, $msg_id, $language, $chat_title, $from_name, $pattern)
    {
        $this->url = $url;
        $this->content = $content;
        $this->msg_id = $msg_id;
        $this->language = $language;
        $this->chat_title = $chat_title;
        $this->from_name = $from_name;
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
        $mention_subjects = [
            "en" => " mentioned you.",
            "ja" => "さんからメンションされました。",
            "mn" => " таныг чатад дурдсан байна."
        ];

        $confirm_subjects = [
            "en" => " sent Confirm Requx`est",
            "ja" => " さんから確認依頼が届きました。",
            "mn" => "-с Баталгаажуулах хүсэлт ирлээ."
        ];

        $sign_subjects = [
            "en" => " sent Digital Signature Request",
            "ja" => "さんからサイン依頼が届きました。",
            "mn" => "-с Гарын үсэг зурах хүсэлт ирлээ."
        ];

        $subjects = [
            "mention" => $mention_subjects,
            "confirm" => $confirm_subjects,
            "sign" => $sign_subjects 
        ];

        $subjectArray = $subjects[$this->pattern];
        // $subject = "[Glowddd]" . $this->from_name . $subjectArray[$this->language];
        $app_title = "[Glowd]";
        $sub = $app_title . ' ' . $this->from_name . $subjectArray[$this->language];
        return $this->view($path)
        ->subject($sub)
        ->with([
            'url' => $this->url,
            'msg_id' => $this->msg_id,
            'content' => $this->content,
            'language' => $this->language,
            'chat_title' => $this->chat_title
        ]);
    }
}
