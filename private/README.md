# CRM AOPIA / LIKE Formation

CRM commercial Laravel/Filament pour AOPIA, LIKE Formation et le domaine AlloPro.

## Stack

- PHP 8.3
- Laravel 12
- Filament 3.3
- MySQL en environnement principal, SQLite pour certains tests
- Spatie Permission pour les roles et droits
- PhpSpreadsheet pour les imports Excel
- Dompdf et generation de fiches
- Google Calendar / Microsoft Graph selon les integrations activees
- Playwright pour les tests navigateur du panel Ns Conseil

## Panels

| Panel | URL | Usage |
|---|---|---|
| Ns Conseil | `/ns-conseil` | CRM AOPIA / LIKE: prospects, partenaires, clients, appels, RDV, phoning |
| AlloPro | `/allopro` | Centre de contact artisans et tickets |
| Admin | `/admin` | Administration generale |
| Super Admin | `/super-admin` | Utilisateurs, roles, permissions, profils, dictionnaires et parametres CRM |

## Modules principaux

- Prospects: pipeline phoning, statuts CSE, appels, rappels, QF et conversion partenaire.
- Partenaires: fiche partenaire, contacts, CSE, syndicat, dirigeant, activites MEA, permanences.
- Clients: imports Dolibarr multi-feuilles, rattachement partenaire, deduplication par reference client.
- Opportunites: sas de detection avant conversion en prospect.
- Appels et RDV: historique polymorphe, fiches recap, emails et synchronisation calendrier.
- Imports Excel: Top 500 prospects, MAJ partenaires, exports clients Dolibarr.
- Droits d'acces: mode global ou selectif par module, puis par champ.

## Droits d'acces

La gestion des droits se fait dans `Super Admin > Roles`.

Un role peut etre configure en deux modes:

- `Acces complet`: toutes les permissions connues sont synchronisees.
- `Acces selectif`: l'administrateur coche uniquement les modules et champs autorises.

Les droits module couvrent les entites principales: prospects, partenaires, clients, opportunites, appels, RDV, campagnes, statuts, utilisateurs et tickets AlloPro.

Les droits par champ sont geres par entite et par action:

| Action | Effet |
|---|---|
| `show` | autorise la lecture du champ |
| `create` | autorise la valeur du champ a la creation |
| `edit` | autorise la modification du champ |
| `flux` | autorise l'usage du champ dans les vues de flux/workflow |
| `all` | autorise toutes les actions du champ |

Les formulaires Prospects, Partenaires, Clients et Tickets filtrent les donnees de creation et modification selon ces droits.

Fichiers principaux:

- `app/Support/AccessRightsCatalog.php`
- `app/Support/UsesResourcePermissions.php`
- `app/Filament/SuperAdmin/Resources/RoleResource.php`
- `tests/Feature/RoleAccessRightsTest.php`

## Imports Excel

Les importeurs utilisent PhpSpreadsheet et les resolvers Filament du panel Ns Conseil.

| Import | Source | Code |
|---|---|---|
| Prospects Top 500 | fichiers departementaux Top 500 | `app/Filament/NsConseil/Resources/ProspectResource/Import/` |
| Partenaires MAJ | feuille `MAJ` du fichier partenaires | `app/Filament/NsConseil/Resources/PartenaireResource/Import/` |
| Clients Dolibarr | feuilles `CRM LIKE`, `CRM AOPIA-ABO`, `CRM 01FC` | `app/Filament/NsConseil/Resources/ClientResource/Import/` |

Les fichiers source d'exemple sont conserves dans `directive/archive/`.

## Installation locale

```powershell
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

Avec Laragon, le projet est attendu dans `C:\laragon\www\crmfilament` et l'URL locale est generalement `http://crmfilament.test/`.

## Commandes utiles

```powershell
php artisan test
php artisan test --filter RoleAccessRightsTest
npm run e2e
npm run e2e:headed
npm run build
php artisan view:clear
php artisan config:clear
```

## Documentation

| Document | Role |
|---|---|
| `ETAT_AVANCEMENT.md` | Avancement courant du projet |
| `MANUEL.md` | Manuel technique de deploiement, developpement et tests |
| `directive/specs/Modele_Projet_AOPIA_Laravel.md` | Modele fonctionnel Laravel/Filament |
| `directive/specs/MANUEL_UTILISATION.md` | Manuel utilisateur du panel Ns Conseil |
| `directive/specs/Champs_Requis_Par_Entite.md` | Champs requis et droits par champ |
| `tests/e2e/README.md` | Lancement des tests Playwright |

## Licence

Projet proprietaire AOPIA / LIKE Formation / NS Conseil.
