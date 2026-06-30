<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Prospect;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PriseContactBlocMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(
        public Prospect $prospect,
        public array $coordsElu = [],
    ) {
        $this->templateKey = 'prospect.prise_contact_bloc';

        $this->templateVariables = [
            'prospect_nom' => $this->prospect->nom,
            'elu_nom' => $this->coordsElu['nom'] ?? $this->prospect->interlocuteur_nom ?? '',
            'elu_prenom' => $this->coordsElu['prenom'] ?? '',
            'elu_fonction' => $this->coordsElu['fonction'] ?? $this->prospect->interlocuteur_fonction ?? '',
            'elu_email' => $this->coordsElu['email'] ?? $this->prospect->interlocuteur_email ?? '',
            'elu_telephone' => $this->coordsElu['telephone'] ?? $this->prospect->interlocuteur_telephone ?? '',
            'teleprospecteur_nom' => auth()->user()?->nom_complet ?? '',
            'entreprise_nom' => $this->prospect->nom,
            'disponibilites' => $this->coordsElu['disponibilites'] ?? '',
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
