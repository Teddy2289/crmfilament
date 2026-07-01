<?php
namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

class RedirectAllMailListener
{
    public function handle(MessageSending $event): void
    {
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