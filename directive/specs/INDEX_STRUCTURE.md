# Index et structure documentaire - CRM AOPIA / LIKE Formation

**Date**: 26 Juin 2026
**Projet courant**: Laravel 12 + Filament 3.3 (`crmfilament`)

---

## 1. Documentation courante

| Document | Role |
|---|---|
| `../../README.md` | Vue projet, stack, panels, commandes rapides |
| `../../ETAT_AVANCEMENT.md` | Etat d'avancement courant et backlog |
| `../../MANUEL.md` | Manuel technique de deploiement/developpement |
| `Modele_Projet_AOPIA_Laravel.md` | Reference fonctionnelle Laravel/Filament |
| `MANUEL_UTILISATION.md` | Manuel utilisateur Ns Conseil et Super Admin |
| `Champs_Requis_Par_Entite.md` | Champs requis et droits par champ |
| `../../tests/e2e/README.md` | Tests navigateur Playwright |

---

## 2. Documentation source ou historique

Ces fichiers restent utiles pour comprendre les decisions metier, mais ne decrivent pas toujours l'implementation Laravel actuelle.

| Document | Statut |
|---|---|
| `CDC_CRM_EspoCRM_AOPIA (2).md` | CDC historique EspoCRM, source metier |
| `Guide_Developpement_EspoCRM.md` | Guide historique EspoCRM, non applicable directement au code Laravel |
| `Manuel_Integration_Ringover.md` | Manuel fonctionnel et technique Ringover |
| `split-account-table.md` | Ancienne strategie EspoCRM AccountDetails; traduite en tables satellites Laravel |
| `directive/archive/*.md` | Comptes-rendus et syntheses historiques |

---

## 3. Structure Laravel actuelle

```text
app/
  Models/                  Entites Eloquent
  Filament/
    NsConseil/             CRM AOPIA / LIKE
    Allopro/               Centre de contact artisans
    SuperAdmin/            Administration, roles, settings
  Services/
    Aopia/                 Workflows AOPIA
    Crm/                   Profils, settings, fiches, reporting
  Support/                 AccessRightsCatalog, helpers permissions

database/
  migrations/              Schema
  seeders/                 Seeders
  seeders/data/            Donnees de reference

directive/
  specs/                   Documentation metier et technique
  archive/                 Sources historiques et fichiers Excel

tests/
  Feature/                 Tests backend
  Browser/                 Tests Dusk historiques
  e2e/                     Tests Playwright courants
```

---

## 4. Fichiers techniques importants

| Sujet | Fichiers |
|---|---|
| Droits d'acces | `app/Support/AccessRightsCatalog.php`, `app/Support/UsesResourcePermissions.php` |
| Interface roles | `app/Filament/SuperAdmin/Resources/RoleResource.php` |
| Tests droits | `tests/Feature/RoleAccessRightsTest.php` |
| Prospects | `app/Models/Prospect.php`, `app/Filament/NsConseil/Resources/ProspectResource.php` |
| Partenaires | `app/Models/Partenaire.php`, `app/Filament/NsConseil/Resources/PartenaireResource.php` |
| Clients | `app/Models/Client.php`, `app/Filament/NsConseil/Resources/ClientResource.php` |
| Base de connaissances | `app/Models/DocumentKnowledge.php`, `app/Filament/NsConseil/Resources/DocumentKnowledgeResource.php`, `database/seeders/DocumentKnowledgeSeeder.php` |
| Imports clients | `app/Filament/NsConseil/Resources/ClientResource/Import/` |
| Imports partenaires | `app/Filament/NsConseil/Resources/PartenaireResource/Import/` |
| Imports prospects | `app/Filament/NsConseil/Resources/ProspectResource/Import/` |
| Workflow phoning | `app/Filament/NsConseil/Pages/PhoningWorkflow.php`, `app/Support/CsePhoningWorkflow.php` |
| Calendrier | `app/Services/GoogleCalendarService.php`, `app/Observers/RendezVousObserver.php` |
| Ringover | `app/Services/RingoverService.php`, `app/Services/RingoverCallSyncService.php`, `app/Services/RingoverTagService.php`, `app/Services/RingoverUserMapper.php`, `app/Http/Controllers/RingoverWebhookController.php`, `app/Console/Commands/SyncRingoverCalls.php`, `tests/Feature/RingoverAdvancedIntegrationTest.php` |

---

## 5. Regles de mise a jour documentaire

- Mettre a jour `README.md` pour les changements visibles de stack, panels ou commandes.
- Mettre a jour `ETAT_AVANCEMENT.md` pour les livraisons et le backlog.
- Mettre a jour `MANUEL.md` pour les commandes, deploiement, tests ou architecture technique.
- Mettre a jour `MANUEL_UTILISATION.md` pour les changements d'interface utilisateur.
- Mettre a jour `Champs_Requis_Par_Entite.md` pour les champs, transitions et droits par champ.
- Ne pas modifier les comptes-rendus d'archive sauf demande explicite; ils servent de trace historique.

---

## 6. Commandes utiles

```powershell
php artisan test --filter RoleAccessRightsTest
php artisan test
npm run e2e
npm run build
rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**'
```
