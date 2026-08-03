# Historique des Appels Ringover - Documentation d'Implémentation

## Vue d'ensemble

L'historique des appels Ringover permet de visualiser tous les appels téléphoniques liés aux fiches CRM (Prospects, Partenaires, Clients) avec les informations de synchronisation Ringover, les statuts de phoning et les détails techniques.

## Intégration Ringover Existante

Le CRM dispose déjà d'une intégration complète avec Ringover:

### Service de Synchronisation

**Fichier**: `app/Services/RingoverCallSyncService.php`

**Fonctionnalités:**
- Synchronisation automatique des appels via webhook
- Association automatique aux fiches CRM par numéro de téléphone
- Mapping des statuts Ringover vers les résultats d'appels CRM
- Extraction et validation des tags Ringover
- Synchronisation des statuts de phoning

### Modèle Appel

**Fichier**: `app/Models/Appel.php`

**Champs Ringover:**
- `ringover_call_id`: ID unique de l'appel Ringover
- `ringover_user_id`: ID de l'utilisateur Ringover
- `ringover_number_id`: ID du numéro Ringover
- `ringover_agent_nom`: Nom de l'agent Ringover
- `ringover_tags`: Tags Ringover (array)
- `ringover_department_tag`: Tag département
- `ringover_status_tag`: Tag statut
- `ringover_tag_validation`: Validation des tags
- `ringover_tag_is_complete`: Tags complets
- `ringover_payload`: Payload complet Ringover
- `ringover_synced_at`: Date de synchronisation
- `ringover_webhook_received_at`: Date de réception du webhook
- `ringover_sync_source`: Source de synchronisation (webhook/sync)

**Champs Phoning:**
- `phoning_status`: Statut de phoning (ok, ko, rdv, supp, cse_hz)
- `phoning_result`: Résultat de phoning
- `phoning_notes`: Notes de phoning
- `phoning_completed_at`: Date de complétion
- `phoning_skipped_at`: Date de skip
- `phoning_agent_id`: ID de l'agent

**Relations:**
- `appelable`: Relation polymorphe vers Prospect, Partenaire, Client
- `user`: Utilisateur CRM associé
- `campagne`: Campagne de phoning

## Composants Créés

### Widget HistoriqueAppelsWidget

**Fichier**: `app/Filament/Widgets/HistoriqueAppelsWidget.php`

**Colonnes affichées:**
1. **Date**: Date/heure de l'appel + durée
2. **Type**: Type d'appel (Appel, Permanence, Presentation)
3. **Résultat**: Résultat CRM (Realise, NonAbouti, Rappel, Annule, Decale)
4. **Utilisateur**: Utilisateur CRM + Agent Ringover
5. **Direction**: Entrant/Sortant
6. **Statut Phoning**: Statut de phoning Ringover (ok, ko, rdv, supp, cse_hz)
7. **Résultat Phoning**: Résultat détaillé de phoning
8. **Commentaire**: Commentaire de l'appel
9. **Enregistrement**: Indicateur d'enregistrement audio

**Fonctionnalités:**
- Filtrage par modèle et ID (Prospect, Partenaire, Client)
- Affichage des 10 derniers appels
- Badges colorés pour les statuts
- Tooltips pour les enregistrements
- État vide si aucun appel

### Intégration dans les Fiches CRM

**Pages modifiées:**
- `ViewProspect`: Widget ajouté
- `ViewPartenaire`: Widget ajouté
- `ViewClient`: Widget ajouté

**Utilisation:**
```php
protected function getHeaderWidgets(): array
{
    return [
        HistoriqueAppelsWidget::make([
            'modelType' => Prospect::class,
            'modelId' => $this->record->id,
        ]),
    ];
}
```

## Flux de Données Ringover

### 1. Appel via Ringover

```
Ringover → Webhook → RingoverWebhookController → ProcessRingoverWebhook
```

### 2. Synchronisation

```
RingoverCallSyncService::sync()
  ↓
Extraction des données (call_id, user, phone)
  ↓
Résolution de la cible CRM (par numéro de téléphone)
  ↓
Mapping des statuts Ringover → CRM
  ↓
Validation des tags Ringover
  ↓
Création/Mise à jour de l'Appel
  ↓
Synchronisation du statut de phoning
```

### 3. Association Automatique

Le service recherche automatiquement la fiche CRM correspondante en utilisant:
- Numéro de téléphone normalisé
- Recherche dans plusieurs champs (telephone, telephone_alt, etc.)
- Recherche dans plusieurs modèles (Prospect, Partenaire, Client, Opportunite)

## Statuts de Phoning

### Codes de Statut

| Code | Signification | Couleur Badge |
|------|---------------|---------------|
| `ok` | Appel réussi | Vert (success) |
| `ko` | Appel échoué | Rouge (danger) |
| `rdv` | Rendez-vous pris | Bleu (info) |
| `supp` | Supprimé de la file | Orange (warning) |
| `cse_hz` | Hors zone CSE | Gris (gray) |

### Mapping Ringover → Phoning

Les tags Ringover sont analysés et validés par `RingoverTagService`:
- Extraction du tag de statut
- Validation du tag de département
- Détermination de la complétude des tags
- Synchronisation automatique du statut de phoning

## Utilisation

### Consultation de l'historique des appels

1. Ouvrir une fiche CRM (Prospect, Partenaire ou Client)
2. Le widget "Historique des appels" s'affiche automatiquement
3. Les 10 derniers appels sont listés
4. Chaque appel affiche:
   - Date et durée
   - Type et résultat
   - Utilisateur CRM et agent Ringover
   - Direction (entrant/sortant)
   - Statut de phoning
   - Indicateur d'enregistrement

### Filtres et Recherche

Le widget peut être personnalisé pour:
- Afficher plus d'appels (modifier `limit(10)`)
- Filtrer par période
- Filtrer par type d'appel
- Filtrer par statut de phoning

### Ajout à d'autres modèles

Pour ajouter l'historique des appels à un autre modèle:

```php
// Dans la page de vue du modèle
protected function getHeaderWidgets(): array
{
    return [
        HistoriqueAppelsWidget::make([
            'modelType' => VotreModele::class,
            'modelId' => $this->record->id,
        ]),
    ];
}
```

Assurez-vous que le modèle a une relation polymorphe avec `Appel`:
```php
// Dans le modèle
public function appels()
{
    return $this->morphMany(Appel::class, 'appelable');
}
```

## Personnalisation

### Modifier les colonnes affichées

Modifier la méthode `table()` dans `HistoriqueAppelsWidget.php`:

```php
public function table(Table $table): Table
{
    return $table
        ->query($query->latest('date_heure')->limit(10))
        ->columns([
            // Ajouter/retirer des colonnes
        ]);
}
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

### Ajouter des actions

Ajouter des actions dans la table:

```php
->actions([
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

Le widget vérifie que l'utilisateur est authentifié:

```php
public static function canView(): bool
{
    return auth()->check();
}
```

Pour restreindre l'accès, ajouter une vérification de permission:

```php
public static function canView(): bool
{
    return auth()->user()?->can('view_appels_history') ?? false;
}
```

### Données sensibles

Les données Ringover incluent:
- Numéros de téléphone
- Enregistrements audio
- Commentaires d'appels

Assurez-vous que:
- Les permissions sont correctement configurées
- Les enregistrements audio ne sont accessibles qu'aux utilisateurs autorisés
- Les logs d'accès sont activés

## Performance

### Optimisation

Le widget utilise:
- Limitation à 10 appels (modifiable)
- Indexes sur `appelable_type`, `appelable_id`, `date_heure`
- Recherche optimisée par numéro de téléphone

### Nettoyage

Pour éviter une croissance excessive de la table `appels`, envisagez:
- Un job de nettoyage périodique (ex: supprimer les appels > 6 mois)
- Une politique de rétention configurable
- Un archivage des anciens appels

## Dépannage

### Les appels ne s'affichent pas

**Vérifications:**
1. L'utilisateur est authentifié
2. La relation polymorphe est correcte
3. Les appels existent dans la base de données
4. Le widget est bien ajouté à la page de vue

### Les appels Ringover ne se synchronisent pas

**Vérifications:**
1. Le webhook Ringover est configuré
2. Les clés API Ringover sont valides
3. Le service `RingoverCallSyncService` fonctionne
4. Les numéros de téléphone sont corrects dans les fiches CRM

### Le statut de phoning ne s'affiche pas

**Vérifications:**
1. Les tags Ringover sont configurés
2. Le service `RingoverTagService` fonctionne
3. Le champ `phoning_status` est rempli
4. La colonne est visible dans le widget

## Intégration avec Historique des Modifications

Les deux systèmes d'historique sont complémentaires:

| Système | Utilisation |
|---------|-------------|
| HistoriqueModifications | Traces les changements de données (champs, valeurs) |
| HistoriqueAppelsWidget | Traces les interactions téléphoniques (appels, statuts) |

Ils peuvent être utilisés ensemble pour une traçabilité complète des interactions avec les fiches CRM.

## Roadmap

### Fonctionnalités futures

- [ ] Écoute des enregistrements directement dans le widget
- [ ] Filtrage avancé par période et statut
- [ ] Export de l'historique des appels
- [ ] Statistiques d'appels par fiche
- [ ] Graphiques de fréquence d'appels
- [ ] Synchronisation bidirectionnelle (CRM → Ringover)
- [ ] Annotations sur les appels (notes internes)
- [ ] Alertes sur les appels manqués
- [ ] Intégration avec le workflow de phoning

## Ressources

- **Documentation Ringover**: `docs/guide-configuration-ringover.md`
- **Spécifications**: `directive/specs/Manuel_Integration_Ringover.md`
- **Service de synchronisation**: `app/Services/RingoverCallSyncService.php`
- **Service de tags**: `app/Services/RingoverTagService.php`
- **Modèle Appel**: `app/Models/Appel.php`
