<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Facture;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RelanceFactureMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(public Facture $facture)
    {
        $this->templateKey = 'facture.relance';

        $artisan = $facture->bonDeCommande?->artisan ?? $facture->artisan ?? null;

        $this->templateVariables = [
            'artisan_prenom_nom' => $artisan?->nom_complet ?? $artisan?->nom ?? '',
            'raison_sociale'     => $artisan?->raison_sociale ?? '',
            'facture_numero'     => $facture->numero ?? $facture->reference ?? '#' . $facture->id,
            'montant_ttc'        => number_format((float) ($facture->montant_ttc ?? 0), 2, ',', ' ') . ' €',
            'jours_retard'       => $facture->jours_retard ?? 0,
            'echeance_date'      => $facture->date_echeance?->format('d/m/Y') ?? '—',
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
