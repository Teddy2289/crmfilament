<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PreviewableProspectionMail extends Mailable
{
    public function __construct(
        public string $subject,
        public string $bodyHtml,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'corps' => $this->bodyHtml,
        ]);
    }
}
