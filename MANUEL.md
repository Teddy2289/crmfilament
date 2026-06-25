# Manuel CRM Filament - Déploiement & Développement

## 📋 Table des matières

- [1. Manuel de Déploiement](#1-manuel-de-déploiement)
  - [1.1 Prérequis](#11-prérequis)
  - [1.2 Installation](#12-installation)
  - [1.3 Configuration](#13-configuration)
  - [1.4 Mise en Production](#14-mise-en-production)
  - [1.5 Mise à jour](#15-mise-à-jour)
- [2. Manuel de Développement](#2-manuel-de-développement)
  - [2.1 Structure du Projet](#21-structure-du-projet)
  - [2.2 Ajout d'un Nouveau Module](#22-ajout-dun-nouveau-module)
  - [2.3 Conventions de Code](#23-conventions-de-code)
  - [2.4 Tests](#24-tests)
  - [2.5 Workflow Git](#25-workflow-git)
- [3. Modules Existants](#3-modules-existants)
- [4. Bonnes Pratiques](#4-bonnes-pratiques)

---

## 1. Manuel de Déploiement

### 1.1 Prérequis

**Serveur:**
- PHP 8.2 ou supérieur
- MySQL 8.0 ou MariaDB 10.6+
- Composer 2.x
- Node.js 18+ et npm 9+
- Redis (optionnel, pour queue/cache)
- Laragon (environnement de développement recommandé)

**Extensions PHP:**
- php-mbstring
- php-xml
- php-curl
- php-zip
- php-gd
- php-mysql
- php-bcmath

### 1.2 Installation

#### Installation locale (Laragon)

```bash
# 1. Cloner le repository
git clone https://github.com/votre-org/crmfilament.git
cd crmfilament

# 2. Installer les dépendances
composer install
npm install

# 3. Configuration de l'environnement
cp .env.example .env
php artisan key:generate

# 4. Configurer la base de données dans .env
# DB_DATABASE=filamentcrm
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Exécuter les migrations
php artisan migrate

# 6. Lancer les seeders
php artisan db:seed --class=DatabaseSeeder

# 7. Compiler les assets
npm run build

# 8. Lancer le serveur de développement
php artisan serve
```

#### Installation production

```bash
# 1. Cloner sur le serveur
git clone https://github.com/votre-org/crmfilament.git
cd crmfilament

# 2. Installer les dépendances en mode production
composer install --no-dev --optimize-autoloader
npm ci --production

# 3. Configuration
cp .env.example .env
php artisan key:generate
# Éditer .env avec les variables de production

# 4. Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Migrations
php artisan migrate --force

# 6. Permissions
chmod -R 775 storage bootstrap/cache

# 7. Compiler les assets
npm run build
```

### 1.3 Configuration

**Variables d'environnement essentielles:**

```env
APP_NAME="CRM Filament"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://crm.votre-domaine.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=filamentcrm
DB_USERNAME=crm_user
DB_PASSWORD=votre_password_secure

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="${APP_NAME}"

# Filesystem
FILESYSTEM_DISK=public

# Queue
QUEUE_CONNECTION=database
# ou redis pour la production
```

### 1.4 Mise en Production

**Avec Supervisor (Queue Workers):**

```ini
# /etc/supervisor/conf.d/crm-worker.conf
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

**Avec Cron (Scheduler):**

```bash
# Ajouter au crontab
* * * * * cd /var/www/crmfilament && php artisan schedule:run >> /dev/null 2>&1
```

**Nginx Configuration:**

```nginx
server {
    listen 80;
    server_name crm.votre-domaine.com;
    root /var/www/crmfilament/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 1.5 Mise à jour

```bash
# 1. Récupérer les dernières modifications
git pull origin main

# 2. Mettre à jour les dépendances
composer update
npm update

# 3. Exécuter les migrations
php artisan migrate --force

# 4. Nettoyer les caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 5. Recompiler les assets
npm run build

# 6. Redémarrer les workers (si nécessaire)
php artisan queue:restart
```

---

## 2. Manuel de Développement

### 2.1 Structure du Projet

```
crmfilament/
├── app/
│   ├── Enums/                    # Énumérations PHP 8.1
│   ├── Filament/                 # Resources Filament
│   │   ├── NsConseil/           # Panel principal
│   │   ├── SuperAdmin/          # Panel administration
│   │   └── Allopro/             # Panel Allopro
│   ├── Http/
│   │   ├── Controllers/         # Contrôleurs API/Web
│   │   └── Middleware/          # Middleware personnalisés
│   ├── Jobs/                    # Jobs Laravel Queue
│   ├── Mail/                    # Mailables
│   ├── Models/                  # Modèles Eloquent
│   ├── Services/                # Services métier
│   │   ├── Aopia/              # Services AOPIA
│   │   └── Crm/                # Services CRM
│   └── Support/                 # Classes utilitaires
├── database/
│   ├── migrations/              # Migrations BDD
│   ├── seeders/                 # Seeders
│   └── data/                    # Données de référence
├── resources/
│   ├── views/                   # Vues Blade
│   ├── stubs/                   # Templates/Stub
│   └── css/                     # Styles Tailwind
├── routes/
│   ├── web.php                  # Routes web
│   ├── api.php                  # Routes API
│   └── console.php              # Commandes console
└── tests/                       # Tests
```

### 2.2 Ajout d'un Nouveau Module

#### Étape 1: Créer le modèle

```bash
php artisan make:model ModuleName -m
```

**Exemple de modèle:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleName extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }
}
```

#### Étape 2: Créer la migration

```bash
php artisan make:migration create_module_names_table
```

```php
Schema::create('module_names', function (Blueprint $table) {
    $table->id();
    $table->string('nom');
    $table->text('description')->nullable();
    $table->boolean('actif')->default(true);
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();
});
```

#### Étape 3: Créer la Resource Filament

```bash
php artisan make:filament-resource ModuleName --generate --panel=NsConseil
```

Cela crée:
- `ModuleResource.php` - Configuration de la resource
- `Pages/ListModuleNames.php` - Liste
- `Pages/CreateModuleName.php` - Création
- `Pages/EditModuleName.php` - Édition

#### Étape 4: Personnaliser la Resource

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('nom')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->columnSpanFull(),
            Toggle::make('actif')
                ->default(true),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('nom')
                ->searchable()
                ->sortable(),
            TextColumn::make('description')
                ->limit(50),
            ToggleColumn::make('actif'),
        ])
        ->filters([
            // Filtres personnalisés
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ]);
}
```

#### Étape 5: Créer un Service (si logique métier)

```bash
# Créer manuellement dans app/Services/Crm/
```

```php
<?php

namespace App\Services\Crm;

use App\Models\ModuleName;

class ModuleNameService
{
    public function creer(array $data): ModuleName
    {
        return ModuleName::create($data);
    }

    public function mettreAJour(ModuleName $module, array $data): ModuleName
    {
        $module->update($data);
        return $module->fresh();
    }

    public function supprimer(ModuleName $module): void
    {
        $module->delete();
    }
}
```

#### Étape 6: Créer des Tests

```bash
php artisan make:test ModuleNameTest
```

```php
public function test_peut_creer_un_module()
{
    $module = ModuleName::factory()->create([
        'nom' => 'Test Module',
    ]);

    $this->assertDatabaseHas('module_names', [
        'nom' => 'Test Module',
    ]);
}
```

#### Étape 7: Lancer les migrations et tests

```bash
php artisan migrate
php artisan test --filter ModuleNameTest
```

### 2.3 Conventions de Code

**Nommage:**
- **Classes:** PascalCase (ex: `ProspectService`)
- **Méthodes:** camelCase (ex: `creerProspect`)
- **Variables:** camelCase (ex: `$nomClient`)
- **Constantes:** UPPER_SNAKE_CASE (ex: `MAX_ITEMS`)
- **Tables:** snake_case (ex: `prospects`)
- **Colonnes:** snake_case (ex: `date_creation`)

**Style:**
- Utiliser Laravel Pint pour le formatage:
  ```bash
  ./vendor/bin/pint
  ```
- Respecter PSR-12
- Indentation: 4 espaces
- Longueur max ligne: 120 caractères

**Commentaires:**
- Docblocks pour les classes et méthodes publiques
- Commentaires inline pour la logique complexe
- Éviter les commentaires évidents

```php
/**
 * Crée un nouveau prospect avec les données fournies.
 *
 * @param array $data Données du prospect
 * @return Prospect
 */
public function creer(array $data): Prospect
{
    // Validation des données avant création
    $validated = $this->valider($data);
    
    return Prospect::create($validated);
}
```

### 2.4 Tests

**Types de tests:**
- **Unit tests:** Tests de méthodes isolées
- **Feature tests:** Tests de fonctionnalités complètes
- **Browser tests:** Tests avec Dusk (optionnel)

**Lancer les tests:**

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter ProspectTest

# Avec coverage
php artisan test --coverage
```

**Structure des tests:**

```php
<?php

namespace Tests\Feature;

use App\Models\Prospect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_creer_un_prospect()
    {
        $response = $this->actingAs($user)
            ->post(route('prospects.store'), [
                'nom' => 'Test Company',
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('prospects', [
            'nom' => 'Test Company',
        ]);
    }
}
```

### 2.5 Workflow Git

**Branches:**
- `main` - Production
- `develop` - Développement
- `feature/nom-feature` - Nouvelle fonctionnalité
- `fix/nom-bug` - Correction de bug
- `hotfix/nom-hotfix` - Correction urgente en production

**Processus:**

```bash
# 1. Créer une branche feature
git checkout -b feature/ajout-module-phoning

# 2. Travailler sur la feature
git add .
git commit -m "feat: ajouter module phoning"

# 3. Pusher la branche
git push origin feature/ajout-module-phoning

# 4. Créer une Pull Request sur GitHub/GitLab

# 5. Après review et merge, supprimer la branche
git checkout develop
git branch -d feature/ajout-module-phoning
```

**Messages de commit (Conventional Commits):**

```
feat: ajouter la génération de fiches Word
fix: corriger le bug de validation email
docs: mettre à jour le README
refactor: optimiser le service ProspectService
test: ajouter tests pour le module Phoning
chore: mettre à jour les dépendances
```

---

## 3. Modules Existants

### 3.1 Phoning Workflow
- **Fichier:** `app/Filament/NsConseil/Pages/PhoningWorkflow.php`
- **Service:** `app/Support/CsePhoningWorkflow.php`
- **Modèles:** `Prospect`, `Appel`, `StatutPhoning`
- **Description:** Gestion du workflow de téléprospection CSE

### 3.2 Fiches Word
- **Service:** `app/Services/Crm/FicheWordService.php`
- **Job:** `app/Jobs/GenerateFicheWordJob.php`
- **Modèles:** `TemplateFiche`, `Appel`
- **Description:** Génération automatique de fiches Word (bleue, jaune, verte)

### 3.3 Opportunities
- **Resource:** `app/Filament/NsConseil/Resources/OpportuniteResource.php`
- **Modèle:** `app/Models/Opportunite.php`
- **Description:** Gestion des opportunités commerciales

### 3.4 Weekly Report
- **Command:** `app/Console/Commands/SendWeeklyReport.php`
- **Service:** `app/Services/Crm/WeeklyReportService.php`
- **Description:** Rapport hebdomadaire automatique

---

## 4. Bonnes Pratiques

### 4.1 Sécurité
- Toujours valider les entrées utilisateur
- Utiliser les policies Laravel pour les autorisations
- Ne jamais stocker de mots de passe en clair
- Utiliser HTTPS en production
- Limiter les permissions des fichiers

### 4.2 Performance
- Utiliser eager loading pour les relations
- Mettre en cache les données statiques
- Utiliser les jobs pour les tâches longues
- Optimiser les requêtes SQL avec indexes
- Utiliser pagination pour les listes

### 4.3 Maintenance
- Garder les dépendances à jour
- Documenter les changements majeurs
- Faire des backups réguliers
- Surveiller les logs d'erreurs
- Faire des revues de code régulières

### 4.4 Documentation
- Commenter le code complexe
- Mettre à jour ce manuel lors de changements
- Documenter les API avec OpenAPI
- Garder le README à jour

---

## 5. Dépannage

### 5.1 Problèmes courants

**Erreur: MissingAppKeyException (APP_KEY manquant):**
```bash
php artisan key:generate
```

**Erreur de migration:**
```bash
php artisan migrate:rollback
php artisan migrate
```

**Cache corrompu:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Queue worker bloqué:**
```bash
php artisan queue:restart
```

**Problème de permissions:**
```bash
chmod -R 775 storage bootstrap/cache
```

### 5.2 Logs

Les logs sont situés dans `storage/logs/`:
- `laravel.log` - Logs généraux
- `worker.log` - Logs des queue workers

---

## 6. Seeders (Données de référence)

## 6.1 Liste Complète des Seeders

Le CRM utilise 14 seeders pour initialiser les données de référence. Ils sont exécutés dans l'ordre défini dans `DatabaseSeeder.php`.

### Seeders Principaux (Obligatoires)

| Seeder | Taille | Description | Dépendances |
|--------|--------|-------------|-------------|
| **RolesAndPermissionsSeeder** | 1.6 KB | Crée les rôles (admin, superviseur, commercial, téléprospecteur, etc.) et permissions Spatie. Charge les profils CRM depuis `database/seeders/data/crm_profiles.php` | Aucune |
| **UsersSeeder** | 3.9 KB | Crée les utilisateurs par défaut avec leurs rôles et secteurs. Mot de passe par défaut: `changeme123` | RolesAndPermissionsSeeder |
| **CrmSettingSeeder** | 559 B | Initialise les paramètres globaux du CRM depuis `database/seeders/data/crm_settings.php` | Aucune |
| **WorkflowGroupeSeeder** | - | Crée les groupes de workflow (Appel non abouti, Élu CSE joint, etc.) depuis `database/seeders/data/workflow_groupes.php` | Aucune |
| **PipelineStatutSeeder** | 836 B | Initialise les statuts de pipeline (nouveau, en_cours, converti, etc.) depuis `database/seeders/data/pipeline_statuts.php` | Aucune |
| **StatutPhoningSeeder** | 4.5 KB | Crée les statuts de phoning pour prospects, partenaires, clients et opportunités depuis `database/seeders/data/statuts_phoning_prospect.php` | WorkflowGroupeSeeder |
| **EmailTemplateSeeder** | 11.7 KB | Initialise 16 templates d'emails (confirmation RDV, rappels, bienvenue, factures, etc.) | Aucune |

### Seeders Module Fiches Word

| Seeder | Taille | Description | Dépendances |
|--------|--------|-------------|-------------|
| **TemplateFicheSeeder** | 4.4 KB | Importe les 3 modèles Word (bleue, jaune, verte) depuis `resources/stubs/fiche-templates/` vers storage | Aucune |

### Seeders AlloPro (Optionnel)

| Seeder | Taille | Description | Dépendances |
|--------|--------|-------------|-------------|
| **AlloproUsersSeeder** | 4.1 KB | Utilisateurs spécifiques AlloPro 24/24 | RolesAndPermissionsSeeder |
| **ArtisanSeeder** | 3.8 KB | Données de test pour artisans | Aucune |
| **DiagnosticSeeder** | 2.7 KB | Diagnostics pour module AlloPro | Aucune |
| **FixAlexSeeder** | 2.3 KB | Fix de données spécifique (temporaire) | Aucune |

### Seeders Additionnels

| Seeder | Taille | Description | Utilisation |
|--------|--------|-------------|-------------|
| **FicheTemplateSeeder** | 2.9 KB | Système de templates de fiches (ancien) | Optionnel - Système legacy |
| **CrmProfileSeeder** | 1.9 KB | Profils CRM (appelé automatiquement par RolesAndPermissionsSeeder) | Interne |

## 6.2 Exécution des Seeders

**Exécuter tous les seeders:**
```bash
php artisan db:seed
```

**Exécuter un seeder spécifique:**
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=TemplateFicheSeeder
php artisan db:seed --class=StatutPhoningSeeder
```

**Recharger les données de référence (sans perdre les données utilisateurs):**
```bash
php artisan db:seed --force
```

## 6.3 Fichiers de Données

Les seeders chargent leurs données depuis les fichiers suivants:

- `database/seeders/data/crm_profiles.php` - Profils et rôles CRM
- `database/seeders/data/crm_settings.php` - Paramètres globaux
- `database/seeders/data/workflow_groupes.php` - Groupes de workflow
- `database/seeders/data/pipeline_statuts.php` - Statuts de pipeline
- `database/seeders/data/statuts_phoning_prospect.php` - Statuts de phoning prospects
- `resources/stubs/fiche-templates/` - Modèles Word pour les fiches

## 6.4 Ordre d'Exécution Recommandé

Pour une installation fraîche:

```bash
# 1. Migrations
php artisan migrate

# 2. Seeders principaux
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=UsersSeeder
php artisan db:seed --class=CrmSettingSeeder
php artisan db:seed --class=WorkflowGroupeSeeder
php artisan db:seed --class=PipelineStatutSeeder
php artisan db:seed --class=StatutPhoningSeeder
php artisan db:seed --class=EmailTemplateSeeder

# 3. Module Fiches Word
php artisan db:seed --class=TemplateFicheSeeder

# 4. (Optionnel) AlloPro
php artisan db:seed --class=AlloproUsersSeeder
php artisan db:seed --class=ArtisanSeeder
```

Ou simplement:
```bash
php artisan migrate
php artisan db:seed
```

---

## 6.5 Import Dolibarr

Le CRM peut importer les clients depuis un export Excel de Dolibarr.

**Commande d'import:**
```bash
php artisan dolibarr:import-clients path/to/export_dolibarr.xlsx
```

**Colonnes Excel attendues:**
- `civilite` - Civilité (M/Mme)
- `nom` - Nom du client
- `prenom` - Prénom du client
- `date_naissance` - Date de naissance (format Excel ou dd/mm/YYYY)
- `adresse` - Adresse
- `code_postal` - Code postal
- `ville` - Ville
- `departement` - Département
- `telephone` - Téléphone
- `email` - Email (utilisé comme clé unique)
- `partenaire_nom` - Nom du partenaire d'origine (recherche partielle)
- `statut_formation` - Statut de formation (En formation / Terminé)
- `heures_formation` - Nombre d'heures de formation
- `nombre_parrainages` - Nombre de parrainages réalisés

**Comportement:**
- Les clients sont créés ou mis à jour via l'email (clé unique)
- Le partenaire est recherché par nom (correspondance partielle)
- Les heures de formation et parrainages sont stockés dans `extra_data`
- L'import logue les créations, mises à jour et erreurs

---

# 7. Tests E2E (Laravel Dusk)

## 7.1 Configuration

Laravel Dusk est installé et configuré pour les tests E2E. ChromeDriver est automatiquement téléchargé.

## 7.2 Lancer les tests

```bash
# Lancer tous les tests E2E
php artisan dusk

# Lancer un test spécifique
php artisan dusk --filter test_user_can_login

# Lancer en mode headless (sans interface graphique)
php artisan dusk --headless
```

## 7.3 Tests disponibles

### LoginTest
- `test_user_can_login`: Test de connexion utilisateur Filament
- `test_user_cannot_login_with_wrong_password`: Test d'échec de connexion
- `test_user_can_logout`: Test de déconnexion

### PartenaireCrudTest
- `test_can_create_partenaire`: Test de création d'un partenaire
- `test_can_view_partenaire`: Test de lecture d'un partenaire
- `test_can_edit_partenaire`: Test de modification d'un partenaire
- `test_can_delete_partenaire`: Test de suppression d'un partenaire
- `test_partenaire_validation`: Test de validation du formulaire
- `test_can_search_partenaire`: Test de recherche de partenaire

### ProspectCrudTest
- `test_can_create_prospect`: Test de création d'un prospect
- `test_can_view_prospect`: Test de lecture d'un prospect
- `test_can_edit_prospect`: Test de modification d'un prospect
- `test_can_delete_prospect`: Test de suppression d'un prospect
- `test_prospect_validation`: Test de validation du formulaire
- `test_can_change_prospect_statut`: Test de changement de statut
- `test_can_search_prospect`: Test de recherche de prospect

### PhoningWorkflowTest
- `test_can_access_phoning_workflow`: Test d'accès à la page workflow
- `test_can_load_queue`: Test de chargement de la file d'attente
- `test_can_call_prospect`: Test d'appel d'un prospect
- `test_can_change_phoning_statut`: Test de changement de statut phoning
- `test_qf_validation_blocks`: Test de validation des éléments bloquants QF
- `test_can_create_rdv_from_workflow`: Test de création de RDV depuis workflow
- `test_can_record_call_with_note`: Test d'enregistrement d'appel avec note
- `test_can_go_to_next_prospect`: Test de passage au prospect suivant
- `test_can_reset_queue`: Test de réinitialisation de la file d'attente
- `test_can_filter_by_phoning_statut`: Test de filtrage par statut phoning

## 7.4 Environnement de test

Les tests utilisent une base de données SQLite en mémoire (`database/database.sqlite`). Les migrations sont automatiquement exécutées avant chaque test.

## 7.5 Screenshots

En cas d'échec de test, des screenshots sont automatiquement générés dans `tests/Browser/screenshots/`.

---

# 8. Contact Support

Pour toute question ou problème:
- **Email:** support@votre-domaine.com
- **Documentation:** https://docs.votre-domaine.com
- **Issues:** https://github.com/votre-org/crmfilament/issues

---

*Dernière mise à jour: 24 Juin 2026*
