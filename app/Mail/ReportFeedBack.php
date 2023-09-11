<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportFeedBack extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($id, $title, $description, $language)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->language = $language;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
        $path = 'emails.report.' . $this->language;
        $report_subjects = [
            "en" => "Regarding Your Reported Problem",
            "ja" => "お問い合わせありがとうございます",
            "mn" => "Мэдэгдлийг хүлээн авлаа"
        ];

        $app_title = "[Glowd]";
        $sub = $app_title . ' ' . $report_subjects[$this->language];
        return $this->text($path)
        ->subject($sub)
        ->with([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description
        ]);
    }
}
