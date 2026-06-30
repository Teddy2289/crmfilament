<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Prospect;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactSansCSEMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(
        public Prospect $prospect,
        public array $coordsContact = [],
    ) {
        $this->templateKey = 'prospect.contact_sans_cse';

        $this->templateVariables = [
            'prospect_nom' => $this->prospect->nom,
            'contact_nom' => $this->coordsContact['nom'] ?? '',
            'contact_prenom' => $this->coordsContact['prenom'] ?? '',
            'contact_fonction' => $this->coordsContact['fonction'] ?? '',
            'contact_email' => $this->coordsContact['email'] ?? '',
            'contact_telephone' => $this->coordsContact['telephone'] ?? '',
            'teleprospecteur_nom' => auth()->user()?->nom_complet ?? '',
            'entreprise_nom' => $this->prospect->nom,
            'nb_salaries' => $this->coordsContact['nb_salaries'] ?? '',
        ];
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

    public function attachments(): array
    {
        return [];
    }
}
