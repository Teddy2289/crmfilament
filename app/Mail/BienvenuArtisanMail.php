<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Artisan;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BienvenuArtisanMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(public Artisan $artisan)
    {
        $this->templateKey = 'artisan.bienvenue';

        $this->templateVariables = [
            'artisan_prenom_nom' => $artisan->nom_complet ?? $artisan->nom ?? '',
            'raison_sociale'     => $artisan->raison_sociale ?? '',
            'metier'             => $artisan->metier_label ?? $artisan->metier ?? '',
            'conseiller_nom'     => $artisan->conseiller?->nom_complet
                                    ?? ($artisan->conseiller ? "{$artisan->conseiller->prenom} {$artisan->conseiller->nom}" : ''),
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
