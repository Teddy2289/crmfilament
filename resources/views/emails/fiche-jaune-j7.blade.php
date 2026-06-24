@component('mail::message')
# Fiche Jaune J+7 - Rappel Commercial

Bonjour {{ $appel->user ? $appel->user->prenom : $appel->phoning_agent->prenom ?? 'Collaborateur' }},

Une fiche jaune J+7 a été générée pour un appel que vous avez effectué il y a 7 jours.

## Détails de l'appel
- **Date et heure** : {{ $appel->date_heure->format('d/m/Y H:i') }}
- **Type d'appel** : {{ $appel->typeLabel }}
- **Résultat** : CSE pas intéressé (CSE-NI)
- **Interlocuteur** : {{ $appel->prospect->nom ?? $appel->partenaire->nom ?? 'Non spécifié' }}
- **Téléphone** : {{ $appel->prospect->telephone ?? $appel->partenaire->telephone ?? 'Non spécifié' }}

## Action requise
Cette fiche jaune indique que le CSE n'était pas intéressé lors de l'appel initial. Selon la procédure, vous devez rappeler ce contact dans les 7 jours suivant ce premier contact (donc aujourd'hui).

La fiche détaillée est jointe à cet email en format Word (.docx). Vous y trouverez toutes les informations nécessaires pour préparer votre rappel.

Cordialement,
Votre système CRM
@endcomponent