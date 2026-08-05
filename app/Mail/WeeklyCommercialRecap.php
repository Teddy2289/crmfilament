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

class WeeklyCommercialRecap extends Mailable
{
    use Queueable, SerializesModels, HasEmailTemplate;

    public function __construct(
        public User $user,
        public array $stats,
        public Carbon $startDate,
        public Carbon $endDate
    ) {
        $this->templateKey = 'recap.weekly_commercial';

        $this->templateVariables = [
            'prenom' => $this->user->prenom ?? '',
            'nom' => $this->user->nom ?? '',
            'start_date' => $this->startDate->format('d/m/Y'),
            'end_date' => $this->endDate->format('d/m/Y'),
            'stats' => $this->stats,
        ];
    }

    public function envelope(): Envelope
    {
        try {
            return new Envelope(subject: $this->getRenderedSubject());
        } catch (\Throwable $e) {
            return new Envelope(subject: 'Récapitulatif hebdomadaire - Commercial');
        }
    }

    public function content(): Content
    {
        try {
            return new Content(view: 'emails.template', with: [
                'corps' => $this->getRenderedBody(),
            ]);
        } catch (\Throwable $e) {
            return new Content(view: 'emails.weekly-commercial-recap', with: [
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