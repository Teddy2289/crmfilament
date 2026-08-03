# Historique des Modifications - Documentation d'Implémentation

## Vue d'ensemble

Le système d'historique des modifications permet de tracer toutes les modifications apportées aux entités CRM (Prospects, Partenaires, Clients, Contacts Partenaires) avec les anciennes et nouvelles valeurs, l'utilisateur responsable et la date de modification.

## Composants Créés

### 1. Modèle HistoriqueModification

**Fichier**: `app/Models/HistoriqueModification.php`

**Champs principaux:**
- `model_type`, `model_id`: Relation polymorphe vers l'entité modifiée
- `user_id`: Utilisateur ayant effectué la modification
- `champ`: Champ modifié (null pour création/suppression)
- `ancienne_valeur`: Valeur avant modification (JSON)
- `nouvelle_valeur`: Valeur après modification (JSON)
- `type_modification`: creation, modification, suppression, restauration
- `date_modification`: Date/heure de la modification

**Types de modification:**
- `creation`: Création d'une nouvelle entité
- `modification`: Modification d'un champ spécifique
- `suppression`: Suppression (soft delete) d'une entité
- `restauration`: Restauration d'une entité supprimée

**Méthodes utiles:**
```php
// Enregistrer une modification de champ
HistoriqueModification::enregistrerModification($model, 'nom', 'Ancien nom', 'Nouveau nom');

// Enregistrer une création
HistoriqueModification::enregistrerCreation($model);

// Enregistrer une suppression
HistoriqueModification::enregistrerSuppression($model);
```

**Scopes:**
```php
// Pour un modèle spécifique
HistoriqueModification::pourModel(Prospect::class, $id)->get();

// Pour un utilisateur spécifique
HistoriqueModification::pourUser($userId)->get();

// Par type de modification
HistoriqueModification::parType('modification')->get();

// Par champ
HistoriqueModification::parChamp('statut')->get();

// Récents
HistoriqueModification::recent()->get();
```

### 2. Migration

**Fichier**: `database/migrations/2026_08_01_000005_create_historique_modifications_table.php`

Crée la table `historique_modifications` avec:
- Relation polymorphe vers les modèles
- Clé étrangère vers users
- Indexes optimisés pour les requêtes fréquentes

### 3. Événements de Tracking

Les modèles suivants ont été équipés de tracking automatique via les événements Eloquent:

#### Prospect (`app/Models/Prospect.php`)
**Champs suivis:**
- nom, type_pressenti, departement, telephone, telephone_alt
- email, adresse, code_postal, ville, siret, secteur_activite
- statut, commercial_id, difficile, qf_valide, notes

**Événements:**
- `created`: Enregistre la création complète
- `updated`: Enregistre chaque champ modifié
- `deleted`: Enregistre la suppression
- `restored`: Enregistre la restauration

#### Partenaire (`app/Models/Partenaire.php`)
**Champs suivis:**
- nom, entreprise, nom_retenu, siret, type, nomenclature_interne
- adresse, code_postal, ville, departement, telephone, email
- secteur_activite, nb_salaries, chiffre_affaires, statut
- commercial_id, notes

#### Client (`app/Models/Client.php`)
**Champs suivis:**
- ref_client, civilite, prenom, nom_tiers, email, telephone
- adresse, code_postal, ville, region, departement, date_naissance
- entreprise, type_tiers, avis_google, etat, montant_cpf
- ne_plus_contacter, partenaire_id, parrain_id, commercial_id, notes_commerciales

#### ContactPartenaire (`app/Models/ContactPartenaire.php`)
**Champs suivis:**
- civilite, nom, prenom, fonction, nom_syndicat, service
- email, telephone_direct, telephone_mobile, telephone_perso
- email_perso, preference_contact, date_naissance, notes
- est_principal, est_decisionnaire, niveau_influence, canal_prefere

### 4. Resource Filament

**Fichier**: `app/Filament/NsConseil/Resources/HistoriqueModificationResource.php`

**Fonctionnalités:**
- Liste de toutes les modifications avec filtres
- Filtres par type de modèle, type de modification, utilisateur, date
- Visualisation détaillée des modifications
- Lien vers l'enregistrement modifié (Prospect, Partenaire, Client)
- Lecture seule (pas de création/modification manuelle)

**Pages:**
- `ListHistoriqueModifications`: Liste avec filtres
- `ViewHistoriqueModification`: Détail avec lien vers l'entité

### 5. Widget

**Fichier**: `app/Filament/Widgets/HistoriqueModificationsWidget.php`

**Fonctionnalités:**
- Affichage des 10 dernières modifications
- Filtrable par modèle et ID
- Intégrable dans les pages de vue des entités
- Tableau compact avec date, type, utilisateur, champ, valeurs

**Utilisation dans les pages de vue:**
```php
protected function getHeaderWidgets(): array
{
    return [
        HistoriqueModificationsWidget::make([
            'modelType' => Prospect::class,
            'modelId' => $this->record->id,
        ]),
    ];
}
```

## Installation

### 1. Exécuter la migration

```bash
php artisan migrate
```

### 2. Configurer les permissions

Ajouter la permission `view_historique_modifications` aux rôles qui doivent voir l'historique:

```php
// Dans le seeder de permissions
Permission::create(['name' => 'view_historique_modifications', 'guard_name' => 'web']);

// Assigner aux rôles
$role->givePermissionTo('view_historique_modifications');
```

## Utilisation

### Consultation de l'historique global

1. Aller dans "Administration" → "Historique des modifications"
2. Utiliser les filtres pour affiner la recherche:
   - Type de modèle (Prospect, Partenaire, Client, Contact)
   - Type de modification (Création, Modification, Suppression, Restauration)
   - Utilisateur
   - Période de dates
3. Cliquer sur une modification pour voir les détails
4. Cliquer sur "Voir l'enregistrement" pour accéder à l'entité

### Consultation de l'historique d'une fiche

L'historique s'affiche automatiquement en haut des pages de vue:
- Prospect → Voir → Widget "Historique des modifications"
- Partenaire → Voir → Widget "Historique des modifications"
- Client → Voir → Widget "Historique des modifications"

### Ajouter le tracking à un nouveau modèle

1. Ajouter la méthode `boot()` au modèle:
```php
protected static function boot()
{
    parent::boot();

    static::created(function ($model) {
        HistoriqueModification::enregistrerCreation($model);
    });

    static::updated(function ($model) {
        $champsSuivis = ['champ1', 'champ2', 'champ3'];
        
        foreach ($champsSuivis as $champ) {
            if ($model->isDirty($champ)) {
                HistoriqueModification::enregistrerModification(
                    $model,
                    $champ,
                    $model->getOriginal($champ),
                    $model->$champ
                );
            }
        }
    });

    static::deleted(function ($model) {
        HistoriqueModification::enregistrerSuppression($model);
    });

    static::restored(function ($model) {
        HistoriqueModification::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'user_id' => auth()->id(),
            'champ' => null,
            'ancienne_valeur' => null,
            'nouvelle_valeur' => $model->toArray(),
            'type_modification' => 'restauration',
            'date_modification' => now(),
        ]);
    });
}
```

2. Exécuter les migrations si nécessaire

### Personnaliser les champs suivis

Modifier le tableau `$champsSuivis` dans la méthode `boot()` du modèle pour inclure ou exclure des champs.

## Sécurité

### Permissions

La Resource HistoriqueModification utilise le système de permissions Filament:

```php
public static function canViewAny(): bool
{
    return Auth::user()?->can('view_historique_modifications') ?? false;
}
```

Le widget vérifie également les permissions:

```php
public static function canView(): bool
{
    return auth()->user()?->can('view_historique_modifications') ?? false;
}
```

### Données sensibles

Les valeurs sont stockées en JSON dans la base de données. Assurez-vous que:
- Les permissions sont correctement configurées
- Les logs d'accès sont activés
- Les données sensibles ne sont pas exposées dans les exports

## Performance

### Indexes

La table `historique_modifications` dispose d'indexes sur:
- `(model_type, model_id)`: Recherche rapide par entité
- `user_id`: Recherche rapide par utilisateur
- `type_modification`: Filtrage par type
- `champ`: Filtrage par champ
- `date_modification`: Tri chronologique

### Nettoyage

Pour éviter une croissance excessive de la table, envisagez:
- Un job de nettoyage périodique (ex: supprimer les entrées > 1 an)
- Une politique de rétention configurable
- Un archivage des anciennes entrées

## Dépannage

### L'historique ne s'affiche pas

**Vérifications:**
1. L'utilisateur a la permission `view_historique_modifications`
2. Le modèle a bien les événements de tracking configurés
3. Les migrations ont été exécutées
4. Le widget est bien ajouté à la page de vue

### Les modifications ne sont pas enregistrées

**Vérifications:**
1. Le champ est bien dans le tableau `$champsSuivis`
2. L'utilisateur est authentifié (`auth()->id()` retourne un ID)
3. La modification est bien validée et sauvegardée
4. Les hooks Eloquent ne sont pas désactivés

### Performance lente sur la liste

**Solutions:**
1. Ajouter des filtres pour réduire le nombre d'entrées
2. Limiter le nombre d'entrées affichées
3. Activer la pagination
4. Nettoyer les anciennes entrées

## Roadmap

### Fonctionnalités futures

- [ ] Export CSV de l'historique
- [ ] Comparaison visuelle entre ancienne et nouvelle valeur
- [ ] Recherche plein texte dans les valeurs
- [ ] Statistiques de modifications par utilisateur
- [ ] Alertes sur les modifications sensibles
- [ ] Historique des modifications en temps réel (WebSocket)
- [ ] Possibilité de restaurer une version précédente
- [ ] Annotations sur les modifications (commentaire explicatif)

## Intégration CRM

### Historique des interactions

Le système d'historique des modifications complète le système `HistoriqueInteractionUser` qui trace les interactions utilisateur (consultation, appel, RDV, email, etc.).

**Différences:**
- `HistoriqueModification`: Traces les changements de données (champs, valeurs)
- `HistoriqueInteractionUser`: Traces les actions utilisateur (consultation, appel, RDV)

Les deux systèmes peuvent être utilisés ensemble pour une traçabilité complète.
