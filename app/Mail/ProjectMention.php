<?php

namespace App\Mail;

use App\Models\ProjectRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class ProjectMention extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The project record.
     */
    public readonly ProjectRecord $project;

    /**
     * The content of the mention.
     */
    public readonly string $content;

    /**
     * Whether the mention is blocked.
     */
    public readonly bool $blocked;

    /**
     * The URL related to the mention.
     */
    public readonly ?string $url;

    /**
     * The author of the mention.
     */
    public readonly ?User $author;

    /**
     * Create a new message instance.
     */
    public function __construct(
        ProjectRecord $project,
        string $content,
        bool $blocked = false,
        ?string $url = null,
        ?User $author = null
    ) {
        $this->project = $project;
        $this->content = $content;
        $this->blocked = $blocked;
        $this->url = $url;
        $this->author = $author;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【プロジェクト収支メンション】' . $this->project->name
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.project_mention',
            with: [
                'project' => $this->project,
                'content' => $this->blocked ? '[REDACTED]' : $this->content,
                'blocked' => $this->blocked,
                'url'     => $this->url,
                'author'  => $this->author,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
