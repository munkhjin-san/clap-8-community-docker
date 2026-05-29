<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class DailyReportReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $missingCount,
        public array $missingDays,
        public bool $isIncident
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isIncident
            ? '【重要】日報未申請インシデントのご連絡'
            : '【ご確認】日報未申請のお知らせ';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.daily_report_reminder');
    }

    public function attachments(): array
    {
        return [];
    }
}
