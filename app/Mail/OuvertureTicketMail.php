<?php

namespace App\Mail;

use App\Mail\Traits\HasEmailTemplate;
use App\Models\Ticket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OuvertureTicketMail extends Mailable
{
    use HasEmailTemplate;

    public function __construct(public Ticket $ticket)
    {
        $this->templateKey = 'ticket.ouverture';

        $this->templateVariables = [
            'contact_prenom_nom' => $ticket->contact_nom ?? '',
            'ticket_reference'   => $ticket->reference ?? '#' . $ticket->id,
            'ticket_objet'       => $ticket->objet ?? $ticket->titre ?? '',
            'ticket_priorite'    => $ticket->priorite_label ?? $ticket->priorite ?? '',
            'operateur_nom'      => $ticket->operateur?->nom_complet
                                    ?? ($ticket->operateur ? "{$ticket->operateur->prenom} {$ticket->operateur->nom}" : ''),
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
