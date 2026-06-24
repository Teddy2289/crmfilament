<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Prospect;
use App\Models\RendezVous;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmationRdvCseMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(
        public Prospect   $prospect,
        public RendezVous $rdv,
    ) {
        $this->templateKey = 'rdv.confirmation_cse';

        $dateHeure = \Carbon\Carbon::parse($this->rdv->date_heure);

        $this->templateVariables = [
            'cse_prenom'             => $this->prospect->interlocuteur_nom ?? '',
            'rdv_date'               => $dateHeure->format('d/m/Y'),
            'rdv_heure'              => $dateHeure->format('H:i'),
            'rdv_jour'               => ucfirst($dateHeure->locale('fr')->isoFormat('dddd')),
            'rdv_lieu'               => $this->rdv->lieu ?? $this->rdv->adresse_lieu ?? '',
            'responsable_prenom_nom' => $this->rdv->commercial?->nom_complet
                                        ?? ($this->rdv->commercial ? "{$this->rdv->commercial->prenom} {$this->rdv->commercial->nom}" : ''),
            'teleprospecteur_prenom' => auth()->user()?->prenom ?? '',
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
