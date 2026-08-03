# Analyse des Requêtes N+1 et Plan d'Optimisation

## Problème Identifié

De nombreuses requêtes dans les widgets et resources Filament ne chargent pas les relations nécessaires, ce qui entraîne des problèmes N+1.

## Requêtes N+1 Identifiées

### 1. Widgets - ActiviteTraitementWidget

```php
// Actuel
$query = Prospect::query()
    ->when($isTp, fn ($q) => $q->where('teleprospecteur_id', $user->id));
```

**Relations manquantes:**
- `teleprospecteur` (utilisé pour le filtre)
- `commercial` (souvent affiché)

**Correction:**
```php
$query = Prospect::query()
    ->with(['teleprospecteur', 'commercial'])
    ->when($isTp, fn ($q) => $q->where('teleprospecteur_id', $user->id));
```

### 2. Widgets - CommercialAgendaWidget

```php
// Actuel
Prospect::query()
    ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id))
    ->whereIn('statut', [ProspectStatut::RP->value, ProspectStatut::RPC->value])
```

**Relations manquantes:**
- `commercial` (utilisé pour le filtre et affichage)

**Correction:**
```php
Prospect::query()
    ->with(['commercial'])
    ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id))
    ->whereIn('statut', [ProspectStatut::RP->value, ProspectStatut::RPC->value])
```

### 3. Widgets - CommercialKpiWidget

```php
// Actuel
$prospectQuery = Prospect::query()
    ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));
```

**Relations manquantes:**
- `commercial`

**Correction:**
```php
$prospectQuery = Prospect::query()
    ->with(['commercial'])
    ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));
```

### 4. Widgets - DirectionKpiWidget

```php
// Actuel
$prospectsActifs = Prospect::whereNotIn('statut', [...])->count();

$qfMois = Prospect::where('statut', ProspectStatut::QF->value)
    ->whereMonth('qf_valide_at', now()->month)
    ->whereYear('qf_valide_at', now()->year)
    ->count();
```

**Relations manquantes:**
- Aucune (count() ne nécessite pas de relations)

### 5. Widgets - ProspectionKpiWidget

```php
// Actuel
$appelsQuery = Appel::query()
    ->where('appelable_type', Prospect::class)
    ->when($isTp, fn ($q) => $q->where('user_id', $user->id));

$prospectQuery = Prospect::query()
    ->when($isTp, fn ($q) => $q->where('teleprospecteur_id', $user->id));
```

**Relations manquantes:**
- Appel: `user`, `appelable`
- Prospect: `teleprospecteur`

**Correction:**
```php
$appelsQuery = Appel::query()
    ->with(['user', 'appelable'])
    ->where('appelable_type', Prospect::class)
    ->when($isTp, fn ($q) => $q->where('user_id', $user->id));

$prospectQuery = Prospect::query()
    ->with(['teleprospecteur'])
    ->when($isTp, fn ($q) => $q->where('teleprospecteur_id', $user->id));
```

### 6. Widgets - ProspectionStatutsChart

```php
// Actuel
$query = Prospect::where('statut', $statut->value);

if ($isTp) {
    $query->where('teleprospecteur_id', $user->id);
}
```

**Relations manquantes:**
- `teleprospecteur`

**Correction:**
```php
$query = Prospect::with(['teleprospecteur'])
    ->where('statut', $statut->value);

if ($isTp) {
    $query->where('teleprospecteur_id', $user->id);
}
```

### 7. Widgets - RappelsDuJourWidget

```php
// Actuel
Prospect::query()
    ->whereDate('rappel_planifie_at', today())
    ->whereNotIn('statut', [...])
```

**Relations manquantes:**
- `commercial`, `teleprospecteur` (affichés dans le tableau)

**Correction:**
```php
Prospect::query()
    ->with(['commercial', 'teleprospecteur'])
    ->whereDate('rappel_planifie_at', today())
    ->whereNotIn('statut', [...])
```

### 8. Widgets - StatsOverviewWidget

```php
// Actuel
$partenaireQuery = Partenaire::query();
$prospectQuery = Prospect::query();

if ($user->hasRole('commercial')) {
    $partenaireQuery->where('commercial_id', $user->id);
    $prospectQuery->where('commercial_id', $user->id);
}
```

**Relations manquantes:**
- Partenaire: `commercial`
- Prospect: `commercial`

**Correction:**
```php
$partenaireQuery = Partenaire::query()->with(['commercial']);
$prospectQuery = Prospect::query()->with(['commercial']);

if ($user->hasRole('commercial')) {
    $partenaireQuery->where('commercial_id', $user->id);
    $prospectQuery->where('commercial_id', $user->id);
}
```

### 9. Widgets - TeamLeaderAlertsWidget

```php
// Actuel
$rappelsRetard = Prospect::where('rappel_planifie_at', '<', now())
    ->whereNotIn('statut', [...])
    ->count();
```

**Relations manquantes:**
- Aucune (count() ne nécessite pas de relations)

### 10. Resources - ListProspects

```php
// Actuel
return Prospect::query()->withoutTrashed();
```

**Relations manquantes:**
- `commercial`, `teleprospecteur`, `valide_par` (affichés dans le tableau)

**Correction:**
```php
return Prospect::query()
    ->with(['commercial', 'teleprospecteur', 'valide_par'])
    ->withoutTrashed();
```

## Plan de Correction

### Étape 1: Créer un Trait pour le eager loading commun

```php
// app/Traits/WithCommonEagerLoading.php
trait WithCommonEagerLoading
{
    protected function loadCommonProspectRelations($query)
    {
        return $query->with(['commercial', 'teleprospecteur', 'valide_par']);
    }

    protected function loadCommonPartenaireRelations($query)
    {
        return $query->with(['commercial', 'conseiller', 'entite']);
    }

    protected function loadCommonClientRelations($query)
    {
        return $query->with(['partenaire', 'commercial', 'parrain']);
    }
}
```

### Étape 2: Mettre à jour les widgets

Appliquer le trait et utiliser les méthodes de chargement.

### Étape 3: Mettre à jour les resources

Appliquer le trait aux pages de liste.

### Étape 4: Tester

Vérifier que le nombre de requêtes diminue avec Laravel DebugBar.

## Priorités

### Haute Priorité (Impact immédiat)
1. ListProspects - très utilisé
2. CommercialAgendaWidget - utilisé fréquemment
3. RappelsDuJourWidget - utilisé quotidiennement

### Moyenne Priorité
4. StatsOverviewWidget - dashboard
5. CommercialKpiWidget - dashboard
6. ProspectionKpiWidget - dashboard

### Basse Priorité
7. Autres widgets - moins utilisés

## Métriques de Succès

- Réduction du nombre de requêtes de 50%+
- Temps de chargement des pages réduit de 30%+
- Aucune requête N+1 détectée par Laravel DebugBar

## Outils de Détection

Utiliser Laravel DebugBar pour détecter les requêtes N+1:

```php
// Dans .env
DEBUGBAR_ENABLED=true
```

Ou utiliser Telescope pour analyser les requêtes.

## Prochaines Étapes

1. [ ] Créer le trait WithCommonEagerLoading
2. [ ] Mettre à jour ListProspects
3. [ ] Mettre à jour CommercialAgendaWidget
4. [ ] Mettre à jour RappelsDuJourWidget
5. [ ] Mettre à jour les autres widgets
6. [ ] Tester avec Laravel DebugBar
7. [ ] Mesurer les améliorations de performance
