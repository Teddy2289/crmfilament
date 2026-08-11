<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PreviewableProspectionMail extends Mailable
{
    public function __construct(
        protected string $subjectOverride,
        protected string $bodyOverride,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectOverride);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'corps' => $this->bodyOverride,
        ]);
    }
}
