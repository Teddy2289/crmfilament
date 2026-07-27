@component('mail::message')
# Fiche Verte - RDV à conclure

Bonjour,

Un contact nécessite une relance de votre part suite à un appel de prospection CSE.

## Détails de l'appel
- **Date et heure** : {{ $appel->date_heure->format('d/m/Y H:i') }}
- **Statut** : {{ $appel->phoning_result ?? $appel->phoning_status }}
- **Entreprise** : {{ $appel->appelable->nom ?? 'Non spécifié' }}
- **Téléphone** : {{ $appel->appelable->telephone ?? 'Non spécifié' }}

## Action requise
Selon la procédure CSE, ce contact doit être repris par vos soins (élu toujours injoignable après blocage standard, ou entreprise sans CSE). La fiche détaillée est jointe à cet email en format Word (.docx) avec toutes les coordonnées collectées par le téléprospecteur.

Cordialement,
Votre système CRM
@endcomponent
