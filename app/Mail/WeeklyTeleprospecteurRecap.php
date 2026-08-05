<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyTeleprospecteurRecap extends Mailable
{
    use Queueable, SerializesModels, HasEmailTemplate;

    public function __construct(
        public User $user,
        public array $stats,
        public Carbon $startDate,
        public Carbon $endDate
    ) {
        $this->templateKey = 'recap.weekly_teleprospecteur';

        $this->templateVariables = [
            'prenom' => $this->user->prenom ?? '',
            'nom' => $this->user->nom ?? '',
            'start_date' => $this->startDate->format('d/m/Y'),
            'end_date' => $this->endDate->format('d/m/Y'),
            'appels_realises' => $this->stats['appels_realises'] ?? '',
            'prospects_contactes' => $this->stats['prospects_contactes'] ?? '',
            'rdv_planifies' => $this->stats['rdv_planifies'] ?? '',
            'conversions_qf' => $this->stats['conversions_qf'] ?? '',
            'conversions_partenaire' => $this->stats['conversions_partenaire'] ?? '',
        ];
    }

    public function envelope(): Envelope
    {
        try {
            return new Envelope(subject: $this->getRenderedSubject());
        } catch (\Throwable $e) {
            return new Envelope(subject: 'Récapitulatif hebdomadaire - Téléprospecteur');
        }
    }

    public function content(): Content
    {
        try {
            return new Content(view: 'emails.template', with: [
                'corps' => $this->getRenderedBody(),
            ]);
        } catch (\Throwable $e) {
            return new Content(view: 'emails.weekly-teleprospecteur-recap', with: [
                'user' => $this->user,
                'stats' => $this->stats,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ]);
        }
    }

    public function attachments(): array
    {
        return [];
    }
}