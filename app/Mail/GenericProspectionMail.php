<?php
namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class GenericProspectionMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(string $templateKey, array $variables = [])
    {
        $this->templateKey = $templateKey;
        $this->templateVariables = $variables;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->getRenderedSubject());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'corps' => $this->getRenderedBody(),
        ]);
    }
}