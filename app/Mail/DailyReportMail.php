<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $rapport;

    public function __construct(array $rapport)
    {
        $this->rapport = $rapport;
    }

    public function envelope(): Envelope
    {
        $prenom = $this->rapport['user']->prenom ?? '';

        return new Envelope(
            subject: 'Rapport quotidien CRM'.($prenom ? " — {$prenom}" : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.daily-report',
            with: ['rapport' => $this->rapport],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
