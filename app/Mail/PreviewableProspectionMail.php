<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PreviewableProspectionMail extends Mailable
{
    protected string $subjectText;
    protected string $bodyHtml;

    public function __construct(string $subject, string $bodyHtml)
    {
        $this->subjectText = $subject;
        $this->bodyHtml = $bodyHtml;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectText);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'corps' => $this->bodyHtml,
        ]);
    }
}
