# Recommandations d'Amélioration - CRM Filament

## 1. Architecture et Relations

### 1.1 Relation Client-Partenaire Ambiguë
**Problème actuel:**
- Double relation: `partenaire_id` (FK directe) + `belongsToMany`
- Crée confusion sur quelle relation utiliser

**Recommandation:**
```php
// Option 1: Unifier la relation
// Conserver uniquement belongsToMany avec pivot table explicite
// Avantages: Flexibilité, historique des rattachements
// Inconvénients: Migration nécessaire

// Option 2: Clarifier l'usage
// - partenaire_id: Partenaire principal (actuel)
// - belongsToMany: Partenaires secondaires/historiques
// Ajouter documentation explicite dans les modèles
```

### 1.2 Proposition liée par ref_client
**Problème actuel:**
- Proposition utilise `ref_client` au lieu de `client.id`
- Incohérent avec le reste de l'application
- Performance: index string vs index integer

**Recommandation:**
```php
// Migration pour ajouter client_id
Schema::table('Propositions', function (Blueprint $table) {
    $table->foreignId('client_id')->nullable()->after('ref_client');
    $table->index('client_id');
});

// Transition progressive
// 1. Ajouter client_id nullable
// 2. Populer client_id depuis ref_client
// 3. Mettre à jour le code pour utiliser client_id
// 4. Rendre client_id required
// 5. Supprimer ref_client
```

### 1.3 Relations Morphiques Multiples
**Problème actuel:**
- Appel, RendezVous, Document utilisent des relations morphiques
- Difficile à typer et à maintenir

**Recommandation:**
```php
// Option 1: Tables de liaison explicites
// Créer des tables pivot pour chaque relation
// prospect_appels, partenaire_appels, client_appels
// Avantages: Typage fort, indexes explicites, contraintes FK

// Option 2: Interface commune
// Définir une interface Appelable, Rdvable, Documentable
// Avantages: Type safety, auto-completion

// Option 3: Garder morphiques mais documenter
// Ajouter des méthodes helper dans les modèles
// ex: $prospect->appels(), $partenaire->appels()
```

---

## 2. Performance

### 2.1 Indexation Manquante
**Problème actuel:**
- Certains champs fréquemment queryés ne sont pas indexés

**Recommandation:**
```php
// Ajouter indexes stratégiques
Schema::table('prospects', function (Blueprint $table) {
    $table->index(['statut', 'teleprospecteur_id']); // Composite pour filtres
    $table->index('rappel_planifie_at'); // Pour rappels en retard
    $table->index('type_pressenti'); // Pour filtres par type
});

Schema::table('partenaires', function (Blueprint $table) {
    $table->index(['statut', 'entite_id']); // Composite
    $table->index('type'); // Pour filtres par type
    $table->index('date_modification_statut'); // Pour à relancer
});

Schema::table('clients', function (Blueprint $table) {
    $table->index(['etat', 'partenaire_id']); // Composite
    $table->index('ne_plus_contacter'); // Pour contactables
});
```

### 2.2 Requêtes N+1
**Problème actuel:**
- Risque de N+1 dans les Resources Filament

**Recommandation:**
```php
// Dans les Resources, utiliser eager loading
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
        ])
        ->defaultSort('created_at', 'desc')
        ->modifyQueryUsing(fn ($query) => $query->with([
            'partenaire',
            'commercial',
            'teleprospecteur'
        ]));
}

// Pour les relations morphiques
->modifyQueryUsing(fn ($query) => $query->with([
    'appels' => fn ($q) => $q->latest()->limit(5),
    'documents' => fn ($q) => $q->latest()->limit(3),
]));
```

### 2.3 Cache Nomenclature Partenaire
**Problème actuel:**
- Index en mémoire non persistant
- Perdu à chaque reboot

**Recommandation:**
```php
// Utiliser Redis ou cache Laravel
public static function nomenclatureIndex(): array
{
    $cacheKey = 'partenaire_nomenclature_index';
    
    return Cache::remember($cacheKey, now()->addHours(6), function () {
        // ... logique actuelle
    });
}

// Invalider le cache lors des modifications
protected static function booted(): void
{
    static::saved(fn () => Cache::forget('partenaire_nomenclature_index'));
    static::deleted(fn () => Cache::forget('partenaire_nomenclature_index'));
}
```

---

## 3. Sécurité

### 3.1 Validation des Données
**Problème actuel:**
- Validation principalement dans les Forms Filament
- Pas de validation au niveau Model

**Recommandation:**
```php
// Ajouter des Rules dans les Models
class Prospect extends Model
{
    protected static $rules = [
        'email' => 'email|nullable',
        'telephone' => 'string|nullable',
        'siret' => 'string|size:14|nullable',
        'nb_salaries' => 'integer|min:0|nullable',
    ];

    public function validate(array $data): bool
    {
        return Validator::make($data, static::$rules)->passes();
    }
}

// Ou utiliser un package comme laravel-eloquent-validation
```

### 3.2 Sanitization des Inputs
**Problème actuel:**
- Pas de sanitization automatique
- Risque XSS, injection SQL

**Recommandation:**
```php
// Utiliser des Accessors/Mutators
class Prospect extends Model
{
    public function setNomAttribute($value)
    {
        $this->attributes['nom'] = strip_tags(trim($value));
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower(filter_var($value, FILTER_SANITIZE_EMAIL));
    }

    public function setTelephoneAttribute($value)
    {
        // Normaliser le format de téléphone
        $this->attributes['telephone'] = preg_replace('/[^0-9+]/', '', $value);
    }
}
```

### 3.3 Permissions Granulaires
**Problème actuel:**
- Permissions basées sur les rôles
- Pas de permissions au niveau champ/action

**Recommandation:**
```php
// Utiliser le système existant FieldPermission
// Mais l'étendre pour inclure:
// - Permissions par action (create, update, delete, view)
// - Permissions par champ (read, write)
// - Permissions par entité (scope géographique)

// Exemple:
class PartenairePolicy
{
    public function update(User $user, Partenaire $partenaire): bool
    {
        if ($user->isSuperAdmin()) return true;
        
        // Commercial ne peut modifier que ses partenaires
        if ($user->isCommercial()) {
            return $partenaire->commercial_id === $user->id;
        }
        
        return false;
    }
}
```

---

## 4. Code Quality

### 4.1 Duplication de Code
**Problème actuel:**
- Méthodes similaires dans plusieurs modèles
- Scopes répétés

**Recommandation:**
```php
// Créer des Traits
trait HasContactable
{
    public function scopeContactables(Builder $query): Builder
    {
        return $query->where('ne_plus_contacter', false)
            ->where(function (Builder $q) {
                $q->whereNotNull('email')
                    ->orWhereNotNull('telephone');
            });
    }

    public function getAdresseCompleteAttribute(): string
    {
        return collect([$this->adresse, $this->code_postal, $this->ville])
            ->filter()
            ->implode(', ');
    }
}

// Utiliser dans Client, Prospect, etc.
class Client extends Model
{
    use HasContactable;
}
```

### 4.2 Complexité des Méthodes
**Problème actuel:**
- Méthodes longues (ex: convertirEnPartenaire)
- Difficile à tester

**Recommandation:**
```php
// Extraire dans des Services
class ProspectConversionService
{
    public function convertirEnPartenaire(Prospect $prospect): Partenaire
    {
        return DB::transaction(function () use ($prospect) {
            $partenaire = $this->creerPartenaire($prospect);
            $this->migrerContacts($prospect, $partenaire);
            $this->marquerProspectConverti($prospect, $partenaire);
            
            return $partenaire;
        });
    }

    protected function creerPartenaire(Prospect $prospect): Partenaire
    {
        // Logique de création
    }

    protected function migrerContacts(Prospect $prospect, Partenaire $partenaire): void
    {
        // Logique de migration
    }

    protected function marquerProspectConverti(Prospect $prospect, Partenaire $partenaire): void
    {
        // Logique de marquage
    }
}
```

### 4.3 Tests Manquants
**Problème actuel:**
- Pas de tests visibles dans l'analyse
- Risque de régressions

**Recommandation:**
```php
// Créer des tests unitaires
class ProspectTest extends TestCase
{
    public function test_peut_convertir_en_partenaire()
    {
        $prospect = Prospect::factory()->qualified()->create();
        $partenaire = $prospect->convertirEnPartenaire();
        
        $this->assertInstanceOf(Partenaire::class, $partenaire);
        $this->assertEquals(OrganizationStatus::SigneAccordCadre, $partenaire->statut);
        $this->assertNotNull($prospect->fresh()->converti_partenaire_id);
    }

    public function test_ne_peut_pas_convertir_si_non_qualifie()
    {
        $prospect = Prospect::factory()->create(['statut' => ProspectStatut::AC]);
        
        $this->expectException(Exception::class);
        $prospect->convertirEnPartenaire();
    }
}

// Tests d'intégration pour les workflows
class ConversionWorkflowTest extends TestCase
{
    public function test_workflow_complet_conversion()
    {
        // Test du workflow complet
    }
}
```

---

## 5. Fonctionnalités

### 5.1 Automatisation des Workflows
**Problème actuel:**
- Workflows manuels
- Pas d'automatisation

**Recommandation:**
```php
// Créer des Jobs/Commands
class ConvertirQualifiesEnPartenaires extends Command
{
    protected $signature = 'crm:convertir-qualifies';
    
    public function handle()
    {
        Prospect::qualifies()
            ->where('qf_valide', true)
            ->whereNull('converti_partenaire_id')
            ->each(fn ($prospect) => $prospect->convertirEnPartenaire());
    }
}

// Scheduler dans app/Console/Kernel.php
$schedule->command('crm:convertir-qualifies')->daily();
```

### 5.2 Notifications et Alertes
**Problème actuel:**
- Pas de système de notifications visible
- Rappels en retard non notifiés

**Recommandation:**
```php
// Créer des Notifications
class RappelEnRetardNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Rappel en retard')
            ->line("Le prospect {$notifiable->nom} a un rappel en retard");
    }
}

// Job pour vérifier les rappels
class VerifierRappelsEnRetard implements ShouldDispatch
{
    public function handle()
    {
        Prospect::rappelEnRetard()
            ->whereNull('rappel_notifie_retard_at')
            ->each(function ($prospect) {
                $prospect->teleprospecteur->notify(
                    new RappelEnRetardNotification($prospect)
                );
                $prospect->update(['rappel_notifie_retard_at' => now()]);
            });
    }
}
```

### 5.3 Reporting Avancé
**Problème actuel:**
- KPIs basiques
- Pas de reporting avancé

**Recommandation:**
```php
// Créer des Reports
class PerformanceReport
{
    public function generate(DateTime $debut, DateTime $fin): array
    {
        return [
            'prospection' => $this->getProspectionStats($debut, $fin),
            'conversion' => $this->getConversionStats($debut, $fin),
            'formation' => $this->getFormationStats($debut, $fin),
            'geographique' => $this->getGeoStats($debut, $fin),
        ];
    }

    protected function getProspectionStats(DateTime $debut, DateTime $fin): array
    {
        return [
            'prospects_creates' => Prospect::whereBetween('created_at', [$debut, $fin])->count(),
            'taux_qualification' => Prospect::getTauxQualification(),
            'appels_effectues' => Appel::whereBetween('created_at', [$debut, $fin])->count(),
        ];
    }
}

// Export Excel/PDF
class ExportPerformanceReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $report = new PerformanceReport();
        $data = $report->generate(now()->subMonth(), now());
        
        Excel::store(new PerformanceExport($data), 'reports/performance.xlsx');
    }
}
```

---

## 6. Base de Données

### 6.1 Normalisation
**Problème actuel:**
- Champs répétés (ex: contacts dans Prospect)
- Redondance de données

**Recommandation:**
```php
// Extraire les contacts dans une table dédiée
Schema::create('prospect_contacts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prospect_id')->constrained()->onDelete('cascade');
    $table->string('type'); // dirigeant, cse_secretaire, syndicat_responsable
    $table->string('nom')->nullable();
    $table->string('prenom')->nullable();
    $table->string('fonction')->nullable();
    $table->string('email')->nullable();
    $table->string('telephone')->nullable();
    $table->timestamps();
});

// Simplifier la table prospects
// Supprimer: dirigeant_*, cse_*, syndicat_*
```

### 6.2 Soft Deletes Consistents
**Problème actuel:**
- Certains modèles ont soft deletes, d'autres non
- Incohérent

**Recommandation:**
```php
// Standardiser: tous les modèles principaux avec soft deletes
// Models concernés:
// - User (déjà)
// - Prospect (déjà)
// - Partenaire (déjà)
// - Client (déjà)
// - Proposition (déjà)
// - DossierFormation (déjà)
// - Ajouter pour: ContactPartenaire, AdresseCse, Tarification, etc.
```

### 6.3 Audit Trail
**Problème actuel:**
- Pas d'historique des modifications
- Difficile de tracer qui a fait quoi

**Recommandation:**
```php
// Utiliser un package comme spatie/laravel-activitylog
class Prospect extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    
    protected static $logName = 'prospect';
}

// Ou créer une table d'audit custom
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->string('model_type');
    $table->unsignedBigInteger('model_id');
    $table->string('action'); // created, updated, deleted
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->timestamp('created_at');
});
```

---

## 7. API

### 7.1 API REST
**Problème actuel:**
- Pas d'API visible
- Difficile d'intégrer avec d'autres systèmes

**Recommandation:**
```php
// Créer une API REST avec Laravel API Resources
// routes/api.php
Route::apiResource('prospects', ProspectController::class);
Route::apiResource('partenaires', PartenaireController::class);
Route::apiResource('clients', ClientController::class);

// Resources pour transformer les données
class ProspectResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'statut' => $this->statut->value,
            'teleprospecteur' => new UserResource($this->teleprospecteur),
            'created_at' => $this->created_at,
        ];
    }
}
```

### 7.2 Webhooks
**Problème actuel:**
- Pas de webhooks
- Impossible de notifier des systèmes externes

**Recommandation:**
```php
// Créer un système de webhooks
Schema::create('webhooks', function (Blueprint $table) {
    $table->id();
    $table->string('event'); // prospect.created, partenaire.updated
    $table->string('url');
    $table->boolean('active')->default(true);
    $table->timestamps();
});

// Dispatcher les webhooks lors des événements
class ProspectObserver
{
    public function created(Prospect $prospect)
    {
        Webhook::dispatch('prospect.created', $prospect);
    }
}
```

---

## 8. UX/UI

### 8.1 Recherche Globale
**Problème actuel:**
- Recherche par entité seulement
- Pas de recherche globale

**Recommandation:**
```php
// Implémenter une recherche globale avec Scout
class Prospect extends Searchable
{
    public function toSearchableArray()
    {
        return [
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'ville' => $this->ville,
        ];
    }
}

// Filament Global Search
public static function getGloballySearchableAttributes(): array
{
    return ['nom', 'email', 'telephone'];
}
```

### 8.2 Tableaux de Bord Personnalisables
**Problème actuel:**
- KPIs fixes
- Pas de personnalisation

**Recommandation:**
```php
// Créer des widgets personnalisables
class CustomKpiWidget extends Widget
{
    protected static string $view = 'filament.widgets.custom-kpi';
    
    public function getViewData(): array
    {
        return [
            'value' => $this->calculateKpi(),
            'label' => $this->getLabel(),
        ];
    }
}

// Permettre à l'utilisateur de configurer son dashboard
// Stocker la configuration dans user.preferences
```

---

## 9. Documentation et Maintenance

### 9.1 Documentation API
**Problème actuel:**
- Pas de documentation API
- Difficile pour les développeurs

**Recommandation:**
```php
// Utiliser Swagger/OpenAPI
// Installer: composer require darkaonline/l5-swagger
// Ajouter les annotations PHPDoc

/**
 * @OA\Info(
 *     title="CRM API",
 *     version="1.0.0",
 *     description="API pour le CRM Filament"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/prospects",
 *     summary="Liste des prospects",
 *     @OA\Response(response=200, description="Liste des prospects")
 * )
 */
public function index()
{
    // ...
}
```

### 9.2 Changelog
**Problème actuel:**
- Pas de changelog
- Difficile de suivre les évolutions

**Recommandation:**
```markdown
# CHANGELOG.md

## [Unreleased]
### Added
- Nouvelle fonctionnalité X
- Nouvelle fonctionnalité Y

### Changed
- Modification de la relation A
- Modification du workflow B

### Fixed
- Correction du bug C
- Correction de la performance D

### Deprecated
- L'ancienne méthode E sera supprimée dans la v2.0

## [1.0.0] - 2026-08-01
### Added
- Version initiale
```

---

## 10. Priorités Recommandées

### Court Terme (1-2 semaines)
1. **Indexation** - Ajouter les indexes manquants
2. **Validation** - Ajouter la validation au niveau Model
3. **Sanitization** - Ajouter la sanitization des inputs
4. **Tests** - Créer des tests pour les workflows critiques

### Moyen Terme (1-2 mois)
1. **Refactoring Relation Client-Partenaire** - Unifier la relation
2. **Refactoring Proposition** - Migrer ref_client vers client_id
3. **Services** - Extraire la logique métier dans des Services
4. **Notifications** - Implémenter le système de notifications

### Long Terme (3-6 mois)
1. **API REST** - Créer une API complète
2. **Webhooks** - Implémenter les webhooks
3. **Reporting** - Créer des reports avancés
4. **Normalisation** - Normaliser la base de données

---

## Conclusion

Le CRM a une architecture solide mais plusieurs domaines peuvent être améliorés:

**Critiques:**
- Relation Client-Partenaire ambiguë
- Utilisation de ref_client vs ID
- Manque de tests
- Pas d'API

**Positifs:**
- Architecture claire
- Workflows bien définis
- Bonne séparation des responsabilités
- Utilisation de Laravel best practices

Les recommandations ci-dessus visent à améliorer la maintenabilité, la performance et la sécurité du CRM tout en préparant le terrain pour de nouvelles fonctionnalités.
