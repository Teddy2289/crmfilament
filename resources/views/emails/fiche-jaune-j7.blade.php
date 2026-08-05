@component('mail::message')
# Fiche Jaune J+7 - Rappel Commercial

Bonjour {{ $destinataire?->prenom ?? 'Collaborateur' }},

Un rappel commercial est dû ce jour : le CSE contacté il y a 7 jours n'était pas intéressé lors du premier appel, la procédure prévoit une relance à J+7.

## Détails de l'appel initial
- **Date et heure** : {{ $appel->date_heure->format('d/m/Y H:i') }}
- **Type d'appel** : {{ $appel->typeLabel }}
- **Résultat** : CSE pas intéressé (CSE-NI)
- **Interlocuteur** : {{ $appel->appelable->nom ?? 'Non spécifié' }}
- **Téléphone** : {{ $appel->appelable->telephone ?? 'Non spécifié' }}

## Action requise
En tant que Responsable de Secteur assigné à ce dossier, il vous revient de reprendre contact avec cet interlocuteur.

La fiche détaillée est jointe à cet email en format Word (.docx). Vous y trouverez toutes les informations nécessaires pour préparer votre rappel.

@if(!empty($signature_name))
Cordialement,  
{{ $signature_name }}  
@if(!empty($signature_phone))
Tél. {{ $signature_phone }}  
@endif
@else
Cordialement,  
Votre système CRM
@endif
@endcomponent
