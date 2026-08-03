# Historique Global des Appels - Documentation d'Implémentation

## Vue d'ensemble

L'historique global des appels permet de consulter tous les appels téléphoniques du CRM dans une vue centralisée, avec des filtres avancés et des liens vers les fiches CRM associées. Cette Resource remplace le widget par fiche pour offrir une vue d'ensemble complète des appels.

## Composants Créés

### Resource AppelResource

**Fichier**: `app/Filament/NsConseil/Resources/AppelResource.php`

**Navigation**:
- **Groupe**: AOPIA
- **Icône**: `heroicon-o-phone`
- **Label**: Historique des appels
- **Permission requise**: `view_historique_appels`

**Colonnes de la liste**:
1. **Date**: Date/heure de l'appel + durée
2. **Type**: Type d'appel (Appel, Permanence, Presentation)
3. **Résultat**: Résultat CRM (Realise, NonAbouti, Rappel, Annule, Decale)
4. **Entité**: Type de fiche CRM liée (Prospect, Partenaire, Client, Opportunité)
5. **Nom**: Nom de l'entité liée + Agent Ringover
6. **Direction**: Entrant/Sortant
7. **Statut Phoning**: Statut de phoning Ringover (ok, ko, rdv, supp, cse_hz)
8. **Enregistrement**: Indicateur d'enregistrement audio

**Filtres disponibles**:
- **Type**: Appel, Permanence, Presentation
- **Résultat**: Réalisé, Non abouti, Rappel, Annulé, Décalé
- **Direction**: Entrant, Sortant
- **Statut Phoning**: OK, KO, RDV, Supprimé, Hors zone CSE
- **Entité**: Prospect, Partenaire, Client, Opportunité
- **Avec enregistrement**: Filtre pour les appels avec enregistrement audio

**Form de détail**:
- **Informations de l'appel**: Date/heure, Type, Résultat, Direction, Durée
- **Informations Ringover**: ID Ringover, Agent Ringover, Tags Ringover
- **Statuts de phoning**: Statut, Résultat, Date de complétion
- **Détails**: Commentaire, Enregistrement audio

### Pages

#### ListAppels

**Fichier**: `app/Filament/NsConseil/Resources/AppelResource/Pages/ListAppels.php`

- Liste tous les appels avec filtres
- Pas de création manuelle d'appels (création automatique via Ringover)
- Tri par défaut: date_heure descendant

#### ViewAppel

**Fichier**: `app/Filament/NsConseil/Resources/AppelResource/Pages/ViewAppel.php`

- Affiche les détails d'un appel
- **Action "Voir la fiche"**: Redirige vers la fiche CRM liée (Prospect, Partenaire, Client, Opportunité)
- Ouvre la fiche dans un nouvel onglet

## Permissions

### Permission view_historique_appels

Ajoutée au catalogue `AccessRightsCatalog`:
- **Module**: historique_appels
- **Label**: AOPIA - Historique des appels
- **Panel**: ns-conseil
- **Permission**: view_historique_appels (Voir l'historique)

Cette permission contrôle l'accès à la Resource AppelResource via la méthode `canViewAny()`.

## Intégration avec Ringover

La Resource utilise les données existantes du modèle `Appel` qui est synchronisé avec Ringover via `RingoverCallSyncService`.

### Champs Ringover utilisés:
- `ringover_call_id`: ID unique de l'appel Ringover
- `ringover_agent_nom`: Nom de l'agent Ringover
- `ringover_tags`: Tags Ringover
- `phoning_status`: Statut de phoning
- `phoning_result`: Résultat de phoning
- `phoning_completed_at`: Date de complétion

### Relation polymorphe:
La Resource utilise la relation `appelable` pour lier les appels aux fiches CRM:
- `App\Models\Prospect`
- `App\Models\Partenaire`
- `App\Models\Client`
- `App\Models\Opportunite`

## Utilisation

### Accès à l'historique

1. Naviguer vers le panel NS-Conseil
2. Cliquer sur "Historique des appels" dans le groupe AOPIA
3. La liste des appels s'affiche avec les filtres

### Filtrage des appels

Utiliser les filtres disponibles pour:
- Filtrer par type d'appel
- Filtrer par résultat
- Filtrer par direction (entrant/sortant)
- Filtrer par statut de phoning
- Filtrer par type d'entité
- Filtrer les appels avec enregistrement

### Consultation d'un appel

1. Cliquer sur le bouton "Voir" d'un appel
2. Les détails de l'appel s'affichent
3. Cliquer sur "Voir la fiche" pour accéder à la fiche CRM liée

### Recherche

- Recherche par nom de l'entité liée
- Tri par date, type, résultat, direction

## Personnalisation

### Modifier les colonnes affichées

Modifier la méthode `table()` dans `AppelResource.php`:

```php
public function table(Table $table): Table
{
    return $table
        ->columns([
            // Ajouter/retirer des colonnes
        ]);
}
```

### Ajouter des filtres personnalisés

Ajouter dans la méthode `table()`:

```php
->filters([
    Tables\Filters\Filter::make('custom_filter')
        ->label('Filtre personnalisé')
        ->query(fn ($query) => $query->where('champ', 'valeur')),
])
```

### Modifier les couleurs des badges

Modifier les méthodes `formatStateUsing` et `color` dans les colonnes:

```php
Tables\Columns\BadgeColumn::make('phoning_status')
    ->colors([
        'success' => 'ok',
        'danger' => 'ko',
        // Ajouter vos couleurs personnalisées
    ])
```

### Ajouter des actions dans la liste

Ajouter dans la méthode `table()`:

```php
->actions([
    Tables\Actions\ViewAction::make(),
    Tables\Actions\Action::make('ecouter')
        ->label('Écouter')
        ->icon('heroicon-o-play')
        ->url(fn ($record) => $record->enregistrement_audio)
        ->openUrlInNewTab()
        ->visible(fn ($record) => $record->enregistrement_audio),
])
```

## Sécurité

### Permissions

La Resource vérifie que l'utilisateur a la permission `view_historique_appels`:

```php
public static function canViewAny(): bool
{
    return auth()->user()?->can('view_historique_appels') ?? false;
}
```

Pour restreindre l'accès, assigner cette permission aux rôles appropriés dans le panel Super Admin.

### Données sensibles

Les données d'appels incluent:
- Numéros de téléphone (via les fiches CRM liées)
- Enregistrements audio
- Commentaires d'appels
- Tags Ringover

Assurez-vous que:
- Les permissions sont correctement configurées
- Les enregistrements audio ne sont accessibles qu'aux utilisateurs autorisés
- Les logs d'accès sont activés

## Performance

### Optimisation

La Resource utilise:
- Indexes sur `date_heure`, `appelable_type`, `appelable_id`
- Pagination par défaut
- Recherche optimisée par nom

### Nettoyage

Pour éviter une croissance excessive de la table `appels`, envisagez:
- Un job de nettoyage périodique (ex: supprimer les appels > 6 mois)
- Une politique de rétention configurable
- Un archivage des anciens appels

## Différences avec le widget par fiche

| Caractéristique | Widget par fiche | Resource globale |
|-----------------|------------------|------------------|
| **Portée** | Appels d'une seule fiche | Tous les appels du CRM |
| **Navigation** | Intégré à la fiche | Menu séparé |
| **Filtres** | Aucun | Multiples filtres avancés |
| **Recherche** | Limitée à la fiche | Recherche globale |
| **Export** | Non | Possible à ajouter |
| **Statistiques** | Non | Possible à ajouter |

## Roadmap

### Fonctionnalités futures

- [ ] Export de l'historique des appels (CSV, Excel)
- [ ] Statistiques d'appels par période
- [ ] Graphiques de fréquence d'appels
- [ ] Écoute des enregistrements directement dans la Resource
- [ ] Annotations sur les appels (notes internes)
- [ ] Alertes sur les appels manqués
- [ ] Rapports d'appels par utilisateur
- [ ] Comparaison des appels par entité
- [ ] Intégration avec le workflow de phoning

## Ressources

- **Documentation Ringover**: `docs/guide-configuration-ringover.md`
- **Spécifications**: `directive/specs/Manuel_Integration_Ringover.md`
- **Service de synchronisation**: `app/Services/RingoverCallSyncService.php`
- **Modèle Appel**: `app/Models/Appel.php`
- **Catalogue des permissions**: `app/Support/AccessRightsCatalog.php`
