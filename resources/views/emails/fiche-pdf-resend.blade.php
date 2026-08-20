@component('mail::message')
# Renvoi de fiche de prospection

Bonjour,

Vous trouverez en pièce jointe la **fiche {{ $type }}** générée pour **{{ $contact }}**.

Cette fiche a été renvoyée depuis le CRM à la demande de l’équipe commerciale.

Cordialement,

{{ config('app.name') }}
@endcomponent
