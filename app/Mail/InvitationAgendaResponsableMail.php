<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Services\Aopia\AopiaIcsService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvitationAgendaResponsableMail extends Mailable
{
    use HasEmailTemplate;

    // Cc systématiques verrouillés (règle R4)
    const CC_FIXES = [
        'bruno@ns-conseil.com',
        'nirina@ns-conseil.com',
    ];

    public function __construct(
        public Prospect   $prospect,
        public RendezVous $rdv,
        public ?string    $fichePdfPath = null,
        public ?string    $audioPath = null,
    ) {
        $this->templateKey = 'rdv.invitation_responsable';

        $dateHeure = \Carbon\Carbon::parse($this->rdv->date_heure);

        $this->templateVariables = [
            'responsable_prenom'      => $this->rdv->commercial?->prenom ?? '',
            'rdv_date'                => $dateHeure->format('d/m/Y'),
            'rdv_heure'               => $dateHeure->format('H:i'),
            'rdv_jour'                => ucfirst($dateHeure->locale('fr')->isoFormat('dddd')),
            'rdv_lieu'                => $this->rdv->lieu ?? $this->rdv->adresse_lieu ?? '',
            'cse_prenom_nom'          => $this->prospect->interlocuteur_nom ?? '',
            'cse_prenom'              => $this->prospect->interlocuteur_nom ?? '',
            'cse_fonction'            => $this->prospect->interlocuteur_fonction ?? '',
            'cse_email'               => $this->prospect->interlocuteur_email ?? '',
            'cse_telephone_direct'    => $this->prospect->interlocuteur_telephone ?? '',
            'raison_sociale'          => $this->prospect->raison_sociale ?? $this->prospect->nom ?? '',
            'secteur_activite'        => $this->prospect->secteur_activite ?? '',
            'effectif'                => $this->prospect->nb_salaries ?? '',
            'notes_appel'             => $this->prospect->description ?? '',
            'teleprospecteur_prenom'  => auth()->user()?->prenom ?? '',
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getRenderedSubject(),
            cc: self::CC_FIXES,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'corps' => $this->getRenderedBody(),
        ]);
    }

    public function attachments(): array
    {
        $attachments = [];

        // Fichier .ics (règle R6)
        $icsContent = app(AopiaIcsService::class)->generateForRendezVous($this->rdv);
        $attachments[] = Attachment::fromData(
            fn () => $icsContent,
            'invitation-rdv.ics'
        )->withMime('text/calendar');

        // Fiche récap PDF
        if ($this->fichePdfPath && file_exists($this->fichePdfPath)) {
            $attachments[] = Attachment::fromPath($this->fichePdfPath)
                ->withMime('application/pdf');
        }

        // Enregistrement audio
        if ($this->audioPath && file_exists($this->audioPath)) {
            $attachments[] = Attachment::fromPath($this->audioPath);
        }

        return $attachments;
    }
}
