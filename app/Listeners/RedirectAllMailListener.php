<?php
namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

class RedirectAllMailListener
{
    public function handle(MessageSending $event): void
    {
        // Garde-fou : ne jamais rediriger les mails en production, même si
        // MAIL_REDIRECT_ALL_TO traîne par erreur dans le .env du serveur.
        if (app()->environment('production')) {
            return;
        }

        $redirectTo = config('mail.redirect_all_to');

        if (! $redirectTo) {
            return;
        }

        /** @var Email $message */
        $message = $event->message;

        $original = collect($message->getTo())
            ->merge($message->getCc())
            ->merge($message->getBcc())
            ->map(fn ($addr) => $addr->getAddress())
            ->implode(', ');

        $message->getHeaders()->addTextHeader('X-Original-To', $original ?: '(aucun)');

        $message->to($redirectTo);
        $message->getHeaders()->remove('Cc');
        $message->getHeaders()->remove('Bcc');

        $subject = $message->getSubject();
        $message->subject('[TEST → ' . $original . '] ' . $subject);
    }
}