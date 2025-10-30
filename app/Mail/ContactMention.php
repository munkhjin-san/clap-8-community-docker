<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactRecord;
use App\Models\User;
class ContactMention extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * The project record.
     */
    public readonly ContactRecord $contact;

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
    /**
     * Create a new message instance.
     */
    public function __construct(
        ContactRecord $contact,
        string $content,
        bool $blocked = false,
        ?string $url = null,
        ?User $author = null
    ) {
        $this->contact = $contact;
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
            subject: '【コンタクトコメントメンション】' . $this->contact->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact_mention',
            with: [
                'project' => $this->contact,
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
