@php
    $date = $rdv->date_heure;
    $commercial = $rdv->commercial;
@endphp
<x-mail::message>
# Confirmation de votre rendez-vous

Bonjour {{ $prospect->interlocuteur_nom }},

Comme convenu lors de notre échange, je vous confirme votre rendez-vous avec notre Responsable de Secteur :

<x-mail::table>
| | |
|:---|:---|
| **Date** | {{ $date->format('d/m/Y') }} |
| **Heure** | {{ $date->format('H:i') }} |
| **Lieu** | {{ $rdv->lieu ?: $rdv->adresse_lieu ?: 'À confirmer' }} |
| **Votre interlocuteur** | {{ $commercial?->prenom }} {{ $commercial?->nom ?? 'Responsable de Secteur' }} |
</x-mail::table>

Notre Responsable de Secteur vous présentera les modalités de formation pour vos collègues ainsi que des exemples de communications déjà mises en place dans d'autres entreprises de votre département.

N'hésitez pas à me contacter si vous souhaitez modifier ce créneau.

Cordialement,<br>
{{ $teleprospecteur->prenom }} {{ $teleprospecteur->nom }}<br>
{{ config('app.name') }}
</x-mail::message>
