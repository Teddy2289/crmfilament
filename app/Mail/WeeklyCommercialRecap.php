<?php

namespace App\Mail;

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
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $stats,
        public Carbon $startDate,
        public Carbon $endDate
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Récapitulatif hebdomadaire - Commercial',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-commercial-recap',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
