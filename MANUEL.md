# Manuel CRM Filament - Deploiement, developpement et exploitation

**Projet**: CRM AOPIA / LIKE Formation / AlloPro
**Stack**: Laravel 12, PHP 8.3, Filament 3.3
**Derniere mise a jour**: 26 Juin 2026

---

## 1. Prerequis

### Serveur

- PHP 8.3 ou superieur compatible Laravel 12
- MySQL 8.0 ou MariaDB 10.6+
- Composer 2.x
- Node.js 22+ recommande, npm inclus
- Laragon pour le developpement local Windows
- Redis optionnel pour cache/queue en production

### Extensions PHP

- mbstring
- xml
- curl
- zip
- gd
- mysql/pdo_mysql
- bcmath
- intl
- fileinfo

---

## 2. Installation locale

Dans Laragon, le projet est attendu dans:

```powershell
C:\laragon\www\crmfilament
```

Installation:

```powershell
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

URL locale habituelle:

```text
http://crmfilament.test/
```

Panels:

| Panel | URL |
|---|---|
| Ns Conseil | `/ns-conseil` |
| AlloPro | `/allopro` |
| Admin | `/admin` |
| Super Admin | `/super-admin` |

---

## 3. Mise en production

Commandes types:

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Sur Linux, verifier les droits:

```bash
chmod -R 775 storage bootstrap/cache
```

Scheduler:

```cron
* * * * * cd /var/www/crmfilament && php artisan schedule:run >> /dev/null 2>&1
```

Queue worker via Supervisor:

```ini
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/crmfilament/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/crmfilament/storage/logs/worker.log
```

---

## 4. Structure du projet

```text
app/
  Enums/                  Statuts et types fixes
  Filament/
    NsConseil/            CRM AOPIA / LIKE
    Allopro/              Centre de contact artisans
    SuperAdmin/           Users, roles, dictionnaires, settings
  Imports/                Imports Laravel generiques
  Jobs/                   Jobs queue et rappels
  Models/                 Modeles Eloquent
  Services/
    Aopia/                Workflows AOPIA
    Crm/                  Settings, profils, reporting, fiches
  Support/                Catalogues droits et helpers

database/
  migrations/             Schema BDD
  seeders/                Seeders
  seeders/data/           Donnees de reference CRM

directive/
  specs/                  Specs et docs metier actives
  archive/                Sources historiques, Excel, CR reunions

tests/
  Feature/                Tests backend
  Browser/                Tests Dusk historiques
  e2e/                    Tests Playwright
```

---

## 5. Modules metier

| Module | Fichiers principaux |
|---|---|
| Prospects | `app/Models/Prospect.php`, `app/Filament/NsConseil/Resources/ProspectResource.php` |
| Partenaires | `app/Models/Partenaire.php`, `app/Filament/NsConseil/Resources/PartenaireResource.php` |
| Clients | `app/Models/Client.php`, `app/Filament/NsConseil/Resources/ClientResource.php` |
| Opportunites | `app/Models/Opportunite.php`, `app/Filament/NsConseil/Resources/OpportuniteResource.php` |
| Appels | `app/Models/Appel.php` |
| RDV | `app/Models/RendezVous.php` |
| Statuts phoning | `app/Models/StatutPhoning.php` |
| Roles et droits | `app/Support/AccessRightsCatalog.php`, `app/Filament/SuperAdmin/Resources/RoleResource.php` |

---

## 6. Gestion des droits d'acces

L'interface est dans:

```text
Super Admin > Roles
```

Chaque role peut etre configure en:

- `Acces complet`: synchronisation de toutes les permissions connues.
- `Acces selectif`: choix manuel des droits module et champ.

### Droits module

Les droits module couvrent les ressources Filament principales: consulter, creer, modifier, supprimer, importer ou administrer selon l'entite.

### Droits champ

Format technique:

```text
fields.{entity}.{field}.{action}
```

Actions:

| Action | Effet |
|---|---|
| `show` | lecture du champ |
| `create` | creation avec ce champ |
| `edit` | modification de ce champ |
| `flux` | usage dans les workflows/flux |
| `all` | toutes les actions du champ |

Les formulaires utilisent `filterFormDataForFieldPermissions()` pour retirer les champs interdits avant creation ou edition.

Verification rapide:

```powershell
php artisan test --filter RoleAccessRightsTest
```

---

## 7. Seeders et donnees de reference

Commande generale:

```powershell
php artisan db:seed
```

Seeders importants:

| Seeder | Role |
|---|---|
| `RolesAndPermissionsSeeder` | Cree/synchronise roles et permissions Spatie |
| `UsersSeeder` | Cree les utilisateurs initiaux |
| `CrmSettingSeeder` | Charge les parametres CRM |
| `CrmProfileSeeder` | Charge les profils CRM |
| `WorkflowGroupeSeeder` | Charge les groupes workflow |
| `PipelineStatutSeeder` | Charge les statuts pipeline |
| `StatutPhoningSeeder` | Charge les statuts d'appel par model_type |
| `EmailTemplateSeeder` | Charge les templates emails |
| `TemplateFicheSeeder` | Charge les templates de fiches |

Donnees source:

```text
database/seeders/data/
```

---

## 8. Imports Excel

### Prospects Top 500

Code:

```text
app/Filament/NsConseil/Resources/ProspectResource/Import/
```

Deduplication: telephone prioritaire, sinon nom + departement.

### Partenaires MAJ

Code:

```text
app/Filament/NsConseil/Resources/PartenaireResource/Import/
```

Feuille cible: `MAJ`.

### Clients Dolibarr

Code:

```text
app/Filament/NsConseil/Resources/ClientResource/Import/
```

Feuilles reconnues:

- `CRM LIKE`
- `CRM AOPIA-ABO`
- `CRM 01FC`

Deduplication: `ref_client`, puis fallback nom + prenom + date de naissance.

Commande CLI historique disponible:

```powershell
php artisan dolibarr:import-clients path\to\export.xlsx
```

---

## 9. Tests

### Tests backend

```powershell
php artisan test
php artisan test --filter RoleAccessRightsTest
```

### Tests Playwright

```powershell
npm run e2e
npm run e2e:headed
npm run e2e:report
```

Le dossier `tests/e2e/README.md` documente les variables disponibles.

### Tests Dusk

`tests/Browser/` contient encore des tests Dusk historiques. Playwright est le parcours E2E principal pour le panel Ns Conseil.

---

## 10. Commandes de maintenance

```powershell
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
php artisan queue:restart
npm run build
```

Logs:

```text
storage/logs/laravel.log
```

---

## 11. Workflow de developpement

1. Lire la resource Filament, le model, le service et les tests existants.
2. Modifier le plus petit perimetre possible.
3. Ajouter ou adapter un test cible si le comportement change.
4. Lancer au minimum le test filtre correspondant.
5. Mettre a jour la documentation si la surface utilisateur ou le workflow change.

Commandes utiles:

```powershell
php -l app\Path\ChangedFile.php
php artisan test --filter NomDuTest
npm run build
```

---

## 12. Depannage

| Probleme | Commande / action |
|---|---|
| APP_KEY manquant | `php artisan key:generate` |
| Cache incoherent | `php artisan optimize:clear` |
| Vues Filament anciennes | `php artisan view:clear` |
| Permissions Spatie non a jour | relancer `php artisan db:seed --class=RolesAndPermissionsSeeder` |
| Assets obsoletes | `npm run build` |
| Queue bloquee | `php artisan queue:restart` |

---

## 13. Documents de reference

| Document | Usage |
|---|---|
| `README.md` | Vue projet rapide |
| `ETAT_AVANCEMENT.md` | Etat courant |
| `directive/specs/Modele_Projet_AOPIA_Laravel.md` | Modele fonctionnel Laravel |
| `directive/specs/Champs_Requis_Par_Entite.md` | Champs requis et regles de droits |
| `directive/specs/MANUEL_UTILISATION.md` | Manuel utilisateur |
| `tests/e2e/README.md` | Tests Playwright |
