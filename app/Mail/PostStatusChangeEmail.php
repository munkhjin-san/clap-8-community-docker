<?php

namespace App\Mail;

use App\Models\PostRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PostStatusChangeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public PostRecord $post;

    /**
     * Create a new message instance.
     */
    public function __construct(PostRecord $post)
    {
        $this->post = $post;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $title = $this->post->title ?? '';
        $subjectTitle = filled($title) ? Str::limit($title, 40) : ('チャレンジ#' . $this->post->id);

        return $this->view('emails.post.status_change')
            ->subject('【チャレンジ】ステータスが更新されました：' . $subjectTitle)
            ->with([
                'post' => $this->post,
            ]);
    }
}
