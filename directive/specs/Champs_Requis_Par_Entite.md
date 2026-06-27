# Champs requis par entite - CRM AOPIA / LIKE Formation

**Version**: 1.1
**Date**: 26 Juin 2026
**Reference**: implementation Laravel/Filament `crmfilament`

---

## 1. Regle generale

Ce document liste les champs metier importants et les regles de droits par champ.

Les champs techniquement disponibles sont definis par les models Eloquent et les formulaires Filament. Les droits par champ sont centralises dans:

```text
app/Support/AccessRightsCatalog.php
```

Format des permissions champ:

```text
fields.{entity}.{field}.{action}
```

Actions supportees:

| Action | Sens |
|---|---|
| `show` | autorise l'affichage ou la lecture |
| `create` | autorise la saisie a la creation |
| `edit` | autorise la modification |
| `flux` | autorise l'usage dans un flux/workflow |
| `all` | autorise toutes les actions |

---

## 2. Prospects

Entite: `prospects`
Model: `App\Models\Prospect`

### Champs metier requis

| Champ | Usage | Remarque |
|---|---|---|
| `nom` | identification | requis pour identifier la fiche |
| `type_pressenti` | qualification | CSE, syndicat, entreprise, autre |
| `telephone` | phoning | prioritaire pour import et appels |
| `departement` | affectation | utile pour secteur et campagne |
| `statut` | pipeline | enum `ProspectStatut` |
| `teleprospecteur_id` | affectation phoning | selon campagne |
| `commercial_id` | relais commercial | requis pour suivi RDV |
| `campagne_id` | import/campagne | si import Top 500 |
| `motif_ko` | sortie KO | requis si statut KO |
| `rappel_planifie_at` | rappel | requis si rappel planifie |

### Champs utiles aux droits par champ

| Champ | Droits typiques |
|---|---|
| `nom` | show, create, edit, all |
| `telephone` | show, create, edit, flux |
| `email` | show, create, edit |
| `adresse`, `code_postal`, `ville` | show, create, edit |
| `siret` | show, create, edit |
| `statut` | show, edit, flux |
| `teleprospecteur_id` | show, edit, flux |
| `commercial_id` | show, edit, flux |
| `interlocuteur_nom`, `interlocuteur_email` | show, create, edit |
| `description` | show, create, edit |

### QF - 7 elements bloquants

| # | Element |
|---|---|
| 1 | RDV cree avec date, heure et lieu |
| 2 | Email confirmation CSE envoye |
| 3 | Champs obligatoires de la fiche renseignes |
| 4 | Fiche recap generee |
| 5 | Enregistrement audio disponible |
| 6 | Email invitation agenda envoye au commercial |
| 7 | Validation Team Leader |

---

## 3. Partenaires

Entite: `partenaires`
Model: `App\Models\Partenaire`

### Champs metier requis

| Champ | Usage | Remarque |
|---|---|---|
| `nom` | nom public | peut etre genere par nomenclature |
| `entreprise` | organisation | source fichier partenaire |
| `nom_retenu` | nom commercial | utilise pour matching |
| `type` | type partenaire | enum `OrganizationType` |
| `statut` | cycle partenaire | enum `OrganizationStatus` |
| `adresse`, `code_postal`, `ville` | localisation | requis pour secteur et RDV |
| `departement` | secteur | filtrage commercial |
| `commercial_id` | responsable | affectation commerciale |
| `conseiller_id` | suivi | selon fichier partenaire |
| `date_signature` | suivi accord | si signe |
| `date_convention` | activation | si convention engagement |

### Champs utiles aux droits par champ

| Champ | Droits typiques |
|---|---|
| `nom`, `entreprise`, `nom_retenu` | show, create, edit |
| `siret` | show, create, edit |
| `type`, `statut` | show, edit, flux |
| `telephone`, `email` | show, create, edit |
| `adresse`, `code_postal`, `ville` | show, create, edit |
| `commercial_id`, `conseiller_id` | show, edit, flux |
| `date_signature`, `date_convention` | show, edit |
| `notes`, `commentaires` | show, create, edit |

---

## 4. Clients

Entite: `clients`
Model: `App\Models\Client`

### Champs metier requis

| Champ | Usage | Remarque |
|---|---|---|
| `ref_client` | deduplication | cle prioritaire Dolibarr |
| `nom_tiers` | identification | peut contenir nom/prenom source |
| `date_naissance` | dedup fallback | avec nom/prenom |
| `telephone` | contact | si present |
| `email` | contact | si present |
| `adresse`, `code_postal`, `ville` | localisation | import Dolibarr |
| `partenaire_id` | rattachement | matching partenaire |
| `ne_plus_contacter` | exclusion commerciale | doit etre respecte |

### Champs utiles aux droits par champ

| Champ | Droits typiques |
|---|---|
| `ref_client` | show, create |
| `civilite`, `nom_tiers` | show, create, edit |
| `email`, `telephone` | show, create, edit |
| `adresse`, `code_postal`, `ville` | show, create, edit |
| `date_naissance` | show, create, edit |
| `entreprise` | show, create, edit |
| `etat` | show, edit, flux |
| `montant_cpf` | show seulement ou masque selon politique metier |
| `ne_plus_contacter` | show, edit, flux |
| `partenaire_id` | show, edit |
| `notes_commerciales` | show, create, edit |

---

## 5. Tickets AlloPro

Entite: `tickets`
Model: `App\Models\Ticket`

### Champs utiles aux droits par champ

| Champ | Droits typiques |
|---|---|
| `reference` | show |
| `contact_particulier_id` | show, create, edit |
| `artisan_id` | show, edit, flux |
| `operateur_id` | show, edit |
| `statut` | show, edit, flux |
| `niveau_priorite` | show, create, edit, flux |
| `corps_de_metier` | show, create, edit, flux |
| `rdv_planifie_at` | show, edit, flux |
| `rappel_promise_at` | show, edit, flux |
| `ringover_call_id` | show |
| `source_appel` | show, create |
| `notes` | show, create, edit |

---

## 6. Appels

Model: `App\Models\Appel`

Champs fonctionnels importants:

| Champ | Usage |
|---|---|
| `appelable_type`, `appelable_id` | lien polymorphe |
| `type` | appel, permanence, presentation |
| `resultat` | resultat generique |
| `phoning_result` | code statut phoning |
| `date_heure` | horodatage |
| `duree_secondes` | duree |
| `enregistrement_audio` | audio QF |
| `ringover_call_id` | lien telephonie |
| `campagne_id` | campagne phoning |
| `user_id` | agent |

---

## 7. Rendez-vous

Model: `App\Models\RendezVous`

Champs fonctionnels importants:

| Champ | Usage |
|---|---|
| `rdvable_type`, `rdvable_id` | lien polymorphe |
| `date_heure` | date et heure |
| `lieu`, `adresse`, `code_postal`, `ville` | lieu structure |
| `interlocuteur_nom` | contact RDV |
| `commercial_id` | commercial assigne |
| `teleprospecteur_id` | createur ou suivi phoning |
| `type` | type RDV |
| `statut` | etat RDV |
| `google_event_id` | sync calendrier |

---

## 8. Regles de transition principales

| Transition | Conditions minimales |
|---|---|
| AC -> en cours / appel traite | appel enregistre avec date, heure et resultat |
| Standard non repondu | tentatives selon configuration CRM |
| RP / RPC -> RDV | date RDV, lieu et interlocuteur |
| RDV -> QF | 7 elements bloquants valides |
| Tout statut -> KO | motif KO renseigne |
| Prospect -> Partenaire | QF valide et role autorise |
| Refus -> reprise | note de reprise recommandee |

---

## 9. Tests a maintenir

Test automatise principal:

```powershell
php artisan test --filter RoleAccessRightsTest
npx playwright test tests/e2e/role-field-visibility.spec.js
```

Scenarios a couvrir lors des evolutions:

- role complet;
- role selectif par module;
- role selectif par champ;
- interdiction create/edit sur champ;
- affichage show dans les vues sensibles;
- champs relationnels affiches rattaches a leur cle etrangere;
- usage flux dans le workflow phoning.
