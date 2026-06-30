<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Prospect;
use App\Models\RendezVous;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmationRdvProspectMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(
        public Prospect $prospect,
        public RendezVous $rdv,
    ) {
        $this->templateKey = 'prospect.confirmation_rdv';

        $dateHeure = \Carbon\Carbon::parse($this->rdv->date_heure);

        $this->templateVariables = [
            'prospect_nom' => $this->prospect->nom,
            'prospect_prenom' => $this->prospect->interlocuteur_nom ?? '',
            'cse_fonction' => $this->prospect->interlocuteur_fonction ?? '',
            'rdv_date' => $dateHeure->format('d/m/Y'),
            'rdv_heure' => $dateHeure->format('H:i'),
            'rdv_jour' => ucfirst($dateHeure->locale('fr')->isoFormat('dddd')),
            'rdv_lieu' => $this->rdv->lieu ?? $this->rdv->adresse_lieu ?? '',
            'commercial_nom' => $this->rdv->commercial?->nom_complet
                ?? ($this->rdv->commercial ? "{$this->rdv->commercial->prenom} {$this->rdv->commercial->nom}" : ''),
            'teleprospecteur_nom' => auth()->user()?->nom_complet ?? '',
            'entreprise_nom' => $this->prospect->nom,
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
