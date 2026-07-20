@php
    $date = $rdv->date_heure;
    $nom = $prospect->raison_sociale ?: $prospect->nom;
@endphp
<x-mail::message>
# Rendez-vous AOPIA — {{ $nom }}

Bonjour,

Tu trouveras ci-dessous tous les éléments pour ton rendez-vous. Merci d'accepter l'invitation agenda ci-jointe.

<x-mail::table>
| | |
|:---|:---|
| **Date** | {{ $date->format('d/m/Y') }} à {{ $date->format('H:i') }} |
| **Lieu** | {{ $rdv->lieu ?: $rdv->adresse_lieu ?: 'À confirmer' }} |
| **Contact CSE** | {{ $prospect->interlocuteur_nom }} — {{ $prospect->interlocuteur_fonction }} |
| **Téléphone CSE** | {{ $prospect->interlocuteur_telephone }} |
| **Email CSE** | {{ $prospect->interlocuteur_email }} |
| **Entreprise** | {{ $nom }} — {{ $prospect->secteur_activite }} — {{ $prospect->nb_salaries }} salariés |
</x-mail::table>

@if ($prospect->description)
## Points clés
{{ $prospect->description }}
@endif

Le RDV a été confirmé par email au CSE. Les pièces jointes incluent la fiche récap et l'enregistrement audio (si disponibles).

Cordialement,<br>
{{ $teleprospecteur->prenom }} {{ $teleprospecteur->nom }}<br>
{{ config('app.name') }}
</x-mail::message>
