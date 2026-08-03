# Analyse des Relations Morphiques

## Vue d'ensemble

Le CRM utilise plusieurs relations polymorphiques (morphiques) pour lier des entités à des données transversales comme les documents, appels, rendez-vous, emails et interactions.

## Relations Morphiques Identifiées

### 1. Document (documentable)

**Modèles liés:**
- Prospect
- Partenaire
- Client
- Opportunite
- Artisan
- ArtisanProspection
- ContactParticulier
- AffaireIntervention

**Relation:**
```php
public function documents()
{
    return $this->morphMany(Document::class, 'documentable');
}
```

**Schéma:**
```sql
documents
- id
- documentable_type (VARCHAR)
- documentable_id (BIGINT)
- ...
```

**Utilisation:**
- Stockage de fichiers liés à n'importe quelle entité
- Factures, contrats, CV, etc.

### 2. RendezVous (rdvable)

**Modèles liés:**
- Prospect
- Partenaire
- Client
- Opportunite
- Artisan
- ArtisanProspection
- ContactPartenaire
- ContactParticulier

**Relation:**
```php
public function rendezVous()
{
    return $this->morphMany(RendezVous::class, 'rdvable');
}
```

**Schéma:**
```sql
rendez_vous
- id
- rdvable_type (VARCHAR)
- rdvable_id (BIGINT)
- ...
```

**Utilisation:**
- Planification de RDV avec n'importe quelle entité
- RDV de prospection, permanence, présentation

### 3. Appel (appelable)

**Modèles liés:**
- Prospect
- Partenaire
- Client
- Opportunite
- ContactPartenaire
- ContactParticulier

**Relation:**
```php
public function appels()
{
    return $this->morphMany(Appel::class, 'appelable');
}
```

**Schéma:**
```sql
appels
- id
- appelable_type (VARCHAR)
- appelable_id (BIGINT)
- ...
```

**Utilisation:**
- Historique des appels Ringover
- Tracking des appels avec n'importe quelle entité

### 4. SentEmail (emailable)

**Modèles liés:**
- Prospect
- Partenaire
- Artisan
- Ticket

**Relation:**
```php
public function sentEmails()
{
    return $this->morphMany(SentEmail::class, 'emailable');
}
```

**Schéma:**
```sql
sent_emails
- id
- emailable_type (VARCHAR)
- emailable_id (BIGINT)
- ...
```

**Utilisation:**
- Historique des emails envoyés
- Tracking des communications

### 5. HistoriqueInteractionUser (interactable)

**Modèles liés:**
- Prospect
- Partenaire

**Relation:**
```php
public function historiqueInteractions()
{
    return $this->morphMany(HistoriqueInteractionUser::class, 'interactable');
}
```

**Schéma:**
```sql
historique_interaction_users
- id
- interactable_type (VARCHAR)
- interactable_id (BIGINT)
- ...
```

**Utilisation:**
- Tracking des interactions utilisateur
- Historique des actions sur les entités

## Problèmes Identifiés

### 1. Incohérence des noms de colonnes

- `documentable_type` / `documentable_id` (standard Laravel)
- `rdvable_type` / `rdvable_id` (abréviation non standard)
- `appelable_type` / `appelable_id` (standard Laravel)
- `emailable_type` / `emailable_id` (standard Laravel)
- `interactable_type` / `interactable_id` (standard Laravel)

**Recommandation:** Standardiser sur le pattern Laravel complet (`rendez_vousable` au lieu de `rdvable`)

### 2. Indexes manquants

Les tables morphiques devraient avoir des indexes composés sur `(type, id)` pour optimiser les requêtes.

**Recommandation:** Ajouter des indexes sur toutes les tables morphiques

### 3. Pas de contraintes d'intégrité

Les relations morphiques ne peuvent pas avoir de contraintes FK réelles, ce qui peut entraîner des données orphelines.

**Recommandation:** Implémenter des jobs de nettoyage périodiques

### 4. Documentation insuffisante

Il n'y a pas de documentation claire sur quels modèles sont liés à quelles relations morphiques.

**Recommandation:** Créer une documentation centralisée

## Recommandations

### 1. Standardiser les noms de colonnes

**Migration requise:**
```php
Schema::table('rendez_vous', function (Blueprint $table) {
    $table->renameColumn('rdvable_type', 'rendez_vousable_type');
    $table->renameColumn('rdvable_id', 'rendez_vousable_id');
});
```

**Mise à jour des modèles:**
```php
public function rendezVous()
{
    return $this->morphMany(RendezVous::class, 'rendez_vousable');
}
```

### 2. Ajouter des indexes stratégiques

```php
Schema::table('documents', function (Blueprint $table) {
    $table->index(['documentable_type', 'documentable_id'], 'documentable_index');
});

Schema::table('rendez_vous', function (Blueprint $table) {
    $table->index(['rdvable_type', 'rdvable_id'], 'rdvable_index');
});

// Idem pour appels, sent_emails, historique_interaction_users
```

### 3. Créer une interface pour les entités morphiques

```php
interface MorphableEntity
{
    public function documents();
    public function rendezVous();
    public function appels();
    public function sentEmails();
    public function historiqueInteractions();
}
```

### 4. Créer un trait pour les relations morphiques communes

```php
trait HasMorphRelations
{
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }

    public function appels()
    {
        return $this->morphMany(Appel::class, 'appelable');
    }

    public function sentEmails()
    {
        return $this->morphMany(SentEmail::class, 'emailable');
    }

    public function historiqueInteractions()
    {
        return $this->morphMany(HistoriqueInteractionUser::class, 'interactable');
    }
}
```

### 5. Implémenter un système de validation des relations morphiques

Créer un middleware ou un observer pour vérifier que les types morphiques sont valides.

## Plan de Migration

### Étape 1: Créer l'interface et le trait
- Créer `MorphableEntity` interface
- Créer `HasMorphRelations` trait

### Étape 2: Ajouter les indexes
- Créer une migration pour ajouter les indexes composés
- Exécuter la migration

### Étape 3: Standardiser les noms (optionnel)
- Évaluer l'impact sur le code existant
- Créer une migration si nécessaire
- Mettre à jour tous les modèles

### Étape 4: Appliquer le trait aux modèles
- Ajouter le trait aux modèles concernés
- Supprimer les méthodes dupliquées

### Étape 5: Tests
- Tester toutes les relations morphiques
- Vérifier les performances
- Valider l'intégrité des données

## Priorités

### Haute Priorité
1. Ajouter les indexes stratégiques
2. Créer le trait `HasMorphRelations`
3. Appliquer le trait aux modèles

### Moyenne Priorité
4. Standardiser les noms de colonnes
5. Créer l'interface `MorphableEntity`

### Basse Priorité
6. Implémenter la validation des types morphiques
7. Créer des jobs de nettoyage

## Prochaines Étapes

1. [ ] Créer la migration pour les indexes
2. [ ] Créer le trait HasMorphRelations
3. [ ] Appliquer le trait aux modèles
4. [ ] Tester les relations
5. [ ] Documenter les changements
