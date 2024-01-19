<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportToAdmin extends Mailable
{
    use Queueable, SerializesModels;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($id, $title, $description, $hasFile)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->hasFile = $hasFile;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
        $path = 'emails.report_to_admin';
        $report_subjects = "【Glowd.app】お問い合わせ。";
        return $this->view($path)
        ->subject($report_subjects)
        ->with([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'has_file' => $this->hasFile
        ]);
    }
}
