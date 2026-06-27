# Modele de projet - CRM AOPIA / LIKE Formation (Laravel + Filament)

**Version**: 1.1
**Date**: 26 Juin 2026
**Sources**: CDC EspoCRM v1.0, comptes-rendus, fichiers Excel reels, implementation Laravel `crmfilament`

---

## 1. Objectif

Le projet `crmfilament` est le CRM commercial AOPIA / LIKE Formation, implemente en Laravel/Filament a partir d'un cahier des charges EspoCRM initial.

Le CRM couvre:

- prospection CSE, syndicats, entreprises;
- gestion des partenaires;
- gestion des clients beneficiaires importes depuis Dolibarr;
- appels, RDV, phoning, rappels, fiches recap et emails;
- reporting et dashboards;
- administration des roles, droits, statuts et dictionnaires.

Hors perimetre CRM commercial:

- facturation;
- paiements;
- suivi pedagogique formateur;
- devis Dolibarr.

---

## 2. Stack actuelle

| Element | Implementation |
|---|---|
| Backend | Laravel 12, PHP 8.3 |
| Back-office | Filament 3.3 |
| Permissions | Spatie Permission + catalogue AOPIA |
| Etats metier | Enums Laravel + dictionnaires BDD |
| Imports | PhpSpreadsheet |
| PDF / fiches | Dompdf + services fiches |
| Calendrier | Google Calendar, Microsoft Graph selon besoin |
| Tests backend | PHPUnit / Pest via `php artisan test` |
| Tests navigateur | Playwright (`tests/e2e`) |

Le CDC EspoCRM reste une reference metier. Les chemins `custom/Espo/...` sont historiques et ne doivent pas etre utilises pour le developpement Laravel courant.

---

## 3. Panels Filament

| Panel | URL | Perimetre |
|---|---|---|
| `ns-conseil` | `/ns-conseil` | CRM AOPIA / LIKE |
| `allopro` | `/allopro` | Centre de contact artisans |
| `admin` | `/admin` | Administration generale |
| `super-admin` | `/super-admin` | Users, roles, permissions, profils, parametres, dictionnaires |

---

## 4. Arborescence fonctionnelle

```text
CRM NS Conseil
  Pipeline
    Opportunites
    Prospects
    Partenaires
  Contacts
    Contacts partenaires
    Autres interlocuteurs
  Activites
    Appels
    Rendez-vous
    Workflow phoning
    Campagnes phoning
  Clients & Formations
    Clients beneficiaires
    Propositions
    Dossiers formation
    Parrainage
  Administration
    Statuts phoning
    Pipeline statuts
    Groupes workflow
    Imports
    Templates
  Droits
    Roles
    Permissions par module
    Permissions par champ
```

---

## 5. Entites principales

### 5.1 Prospect

Model: `App\Models\Prospect`
Resource: `app/Filament/NsConseil/Resources/ProspectResource.php`

Role: fiche de prospection avant conversion partenaire.

Champs et concepts majeurs:

- `nom`, `raison_sociale`, `type_pressenti`;
- `telephone`, `telephone_alt`, `email`;
- adresse, code postal, ville, departement;
- `teleprospecteur_id`, `commercial_id`;
- `statut` via `ProspectStatut`;
- `campagne_id`;
- champs interlocuteur standard, CSE, syndicat, dirigeant;
- `qf_valide`, `valide_par`, `qf_valide_at`;
- `motif_ko`, `rappel_planifie_at`.

Statuts principaux:

| Code | Sens |
|---|---|
| `AC` | A contacter |
| `STD_NR` | Standard non repondu |
| `STD_Joint` | Standard joint |
| `CSE_NR` | CSE non joint |
| `RP` | Rappel planifie |
| `RPC` | RDV a planifier / contact qualifie |
| `KO` | Hors cible ou refus |
| `QF` | Qualifie apres validation |

Services et supports:

- `app/Services/Aopia/AopiaProspectWorkflowService.php`
- `app/Support/CsePhoningWorkflow.php`
- `app/Filament/NsConseil/Pages/PhoningWorkflow.php`

### 5.2 Partenaire

Model: `App\Models\Partenaire`
Resource: `app/Filament/NsConseil/Resources/PartenaireResource.php`

Role: compte partenaire signe ou cible partenaire.

Champs et concepts majeurs:

- nom, entreprise, nom retenu;
- type via `OrganizationType`;
- statut via `OrganizationStatus`;
- commercial, conseiller, entite commerciale;
- SIRET, adresse, departement;
- date de signature, date de convention;
- contacts partenaires;
- tables satellites: adresse CSE, tarification, activite vente, activite permanence, remboursements, historique conseillers, autres interlocuteurs.

Cycle courant:

```text
a_prospecter -> en_cours_prospection -> rdv_en_cours -> signe_accord_cadre -> convention_engagement
```

`refus` peut etre repris selon decision metier.

### 5.3 Client

Model: `App\Models\Client`
Resource: `app/Filament/NsConseil/Resources/ClientResource.php`

Role: beneficiaire importe depuis Dolibarr.

Champs et concepts majeurs:

- `ref_client`;
- civilite, nom, prenom ou nom tiers;
- date de naissance;
- telephone, email, adresse;
- entreprise;
- partenaire rattache;
- `ne_plus_contacter`;
- propositions et dossiers formation;
- parrainage.

Deduplication:

1. `ref_client` si present;
2. fallback nom + prenom + date de naissance.

### 5.4 Opportunite

Model: `App\Models\Opportunite`
Resource: `app/Filament/NsConseil/Resources/OpportuniteResource.php`

Role: sas de detection avant prospection active.

Fonctions principales:

- creation d'une opportunite depuis un signal faible;
- qualification;
- conversion en prospect;
- perte avec raison.

### 5.5 Appel

Model: `App\Models\Appel`

Role: historique d'appel polymorphe lie a Prospect, Partenaire, Opportunite ou Client.

Points importants:

- type et resultat;
- date/heure et duree;
- statut phoning;
- audio;
- lien Ringover;
- campagne;
- agent.

### 5.6 Rendez-vous

Model: `App\Models\RendezVous`
Resource: `app/Filament/NsConseil/Resources/RendezVousResource.php`

Role: RDV commercial ou activite planifiee.

Points importants:

- date/heure;
- lieu structure;
- interlocuteur;
- commercial et teleprospecteur;
- synchronisation calendrier;
- fiches recap et invitations.

---

## 6. Imports Excel

### 6.1 Prospects Top 500

Dossier:

```text
app/Filament/NsConseil/Resources/ProspectResource/Import/
```

Classes:

- `ProspectImporter`
- `ProspectImportResolver`

Regles:

- lit les fichiers Top 500 departementaux;
- mappe conseiller, departement, etat, commentaires, coordonnees;
- deduplication par telephone, puis nom + departement.

### 6.2 Partenaires MAJ

Dossier:

```text
app/Filament/NsConseil/Resources/PartenaireResource/Import/
```

Classe:

- `PartenaireImportResolver`

Regles:

- feuille cible `MAJ`;
- mappe entite, entreprise, nom retenu, statut, type, conseiller, mandataire, adresse CSE, contacts, ventes et permanences;
- cree ou met a jour les tables satellites quand necessaire.

### 6.3 Clients Dolibarr

Dossier:

```text
app/Filament/NsConseil/Resources/ClientResource/Import/
```

Classes:

- `BaseClientImporter`
- `CrmLikeImporter`
- `CrmAopiaAboImporter`
- `Crm01FcImporter`
- `ImportResolver`

Feuilles reconnues:

| Feuille | Usage |
|---|---|
| `CRM LIKE` | clients LIKE |
| `CRM AOPIA-ABO` | clients AOPIA abonnement |
| `CRM 01FC` | clients 01FC |

---

## 7. Droits d'acces

### 7.1 Interface

Chemin:

```text
Super Admin > Roles & Permissions
```

Un role propose deux modes:

| Mode | Effet |
|---|---|
| `Tout` | toutes les permissions du catalogue sont attribuees |
| `Selectif par entite/module` | choix manuel des droits modules et champs |

### 7.2 Droits module

Source de verite:

```text
app/Support/AccessRightsCatalog.php::modules()
```

Modules couverts:

| Module | Panel |
|---|---|
| prospects | Ns Conseil |
| partenaires | Ns Conseil |
| clients | Ns Conseil |
| activites | Ns Conseil |
| rapports | Ns Conseil |
| tickets | AlloPro |
| fiche_p2 | AlloPro |
| artisans | AlloPro |
| reclamations | AlloPro |
| rapports_satisfaction | AlloPro |
| prospection_artisans | AlloPro |
| dashboard | AlloPro |

### 7.3 Droits par champ

Source de verite:

```text
app/Support/AccessRightsCatalog.php::fieldModules()
```

Format:

```text
fields.{entity}.{field}.{action}
```

Actions:

| Action | Sens |
|---|---|
| `show` | affichage / lecture |
| `create` | saisie a la creation |
| `edit` | modification |
| `flux` | usage dans workflow ou flux |
| `all` | toutes les actions |

Entites actuellement exposees aux droits champ:

| Entite | Panel |
|---|---|
| prospects | Ns Conseil |
| partenaires | Ns Conseil |
| clients | Ns Conseil |
| tickets | AlloPro |

Comportement:

- si aucun droit champ n'est configure pour une entite, le comportement module reste applique;
- si des droits champ existent pour une entite, les champs non autorises sont filtres a la creation et a l'edition;
- `all` donne toutes les actions pour le champ;
- `view` est normalise vers `show`, `update` vers `edit`.

### 7.4 Tests

Test principal:

```text
tests/Feature/RoleAccessRightsTest.php
```

Commande:

```powershell
php artisan test --filter RoleAccessRightsTest
```

Le test couvre:

- acces complet;
- acces selectif Ns Conseil;
- acces selectif AlloPro;
- generation du catalogue;
- droits `show`, `create`, `edit`, `flux`, `all`;
- filtrage des donnees interdites.

---

## 8. Telephonie et calendrier

### Ringover

Implementation actuelle:

- `app/Services/RingoverService.php`
- `app/Console/Commands/SyncRingoverCalls.php`
- widgets Ringover dans Ns Conseil
- champs Ringover dans `app/Models/Appel.php`

La directive metier conserve la regle:

```text
DEP_XX + tag statut obligatoire par appel
```

Cette regle est stockee dans les settings CRM.

### Calendrier

Implementation principale:

- `app/Services/GoogleCalendarService.php`
- `app/Observers/RendezVousObserver.php`
- `app/Services/CreneauPropositionService.php`

---

## 9. Workflows automatiques

| Workflow | Implementation |
|---|---|
| Validation QF | `AopiaProspectWorkflowService` |
| Email RDV | `AopiaMailTemplateService` |
| ICS | `AopiaIcsService` |
| Fiche PDF | `Aopia/FicheGenerationService` |
| Fiche Word | `Crm/FicheWordService`, `GenerateFicheWordJob` |
| Fiche jaune J+7 | `SendFicheJauneJ7Job` |
| Rappel RP | `SendRappelRpJob` |
| Rappel STD-NR J+2 | `SendRappelStdNrJob` |
| Reporting hebdo | `SendWeeklyReport`, `SendWeeklyReportJob`, `WeeklyReportService` |

---

## 10. Documents source

| Document | Statut |
|---|---|
| `CDC_CRM_EspoCRM_AOPIA (2).md` | Source historique CDC |
| `Champs_Requis_Par_Entite.md` | Reference champs et droits |
| `MANUEL_UTILISATION.md` | Manuel utilisateur courant |
| `Manuel_Integration_Ringover.md` | Historique fonctionnel Ringover |
| `Guide_Developpement_EspoCRM.md` | Historique EspoCRM |
| `split-account-table.md` | Historique de decoupage Account, traduit en tables satellites Laravel |
| `directive/archive/` | Archives et fichiers Excel source |

---

## 11. Backlog technique

| Priorite | Sujet |
|---|---|
| P0 | Appliquer `show` aux infolists/tables sensibles de toutes les resources |
| P0 | Ajouter un test navigateur pour un role selectif par champ |
| P1 | Finaliser les webhooks Ringover et le mapping des tags si synchronisation temps reel demandee |
| P1 | Finaliser l'alignement Opportunites avec les libelles metier definitifs |
| P1 | Clarifier le module base de connaissances attendu |
| P2 | Completer la documentation avec captures apres validation UI |

---

## 12. Commandes utiles

```powershell
php artisan test --filter RoleAccessRightsTest
php artisan test
npm run e2e
npm run build
```
