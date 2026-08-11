# Workflow: Gestion des Accès Droits

## Description
Guide pour gérer les droits d'accès aux entités et champs du CRM par rôle utilisateur.

## Prérequis
- Accès administrateur au panel Filament
- Connaissance des rôles existants (administrateur, team_leader, commercial, teleprospecteur, etc.)

## Étapes

### 1. Identifier l'entité à modifier
Les entités principales du CRM:
- Prospects
- Partenaires
- Clients
- Appels
- Rendez-vous
- Opportunités
- DocumentKnowledge

### 2. Définir les permissions par rôle

#### Niveau Entité (CRUD)
- **View**: Accès en lecture seule à l'entité
- **Create**: Permission de créer des enregistrements
- **Edit**: Permission de modifier des enregistrements
- **Delete**: Permission de supprimer des enregistrements

#### Niveau Champ
- **Show**: Afficher le champ dans le formulaire/tableau
- **Edit**: Permettre la modification du champ

### 3. Modifier la Resource Filament

#### Exemple: Modifier les permissions ProspectResource

```php
// app/Filament/NsConseil/Resources/ProspectResource.php

public static function canViewAny(): bool
{
    return auth()->user()?->can('view_any_prospects') 
        || auth()->user()?->hasRole(['administrateur', 'team_leader', 'commercial']);
}

public static function canCreate(): bool
{
    return auth()->user()?->can('create_prospects')
        || auth()->user()?->hasRole(['administrateur', 'team_leader', 'commercial']);
}

public static function canEdit($record): bool
{
    return auth()->user()?->can('edit_prospects')
        || auth()->user()?->hasRole(['administrateur', 'team_leader'])
        || ($record->teleprospecteur_id === auth()->id());
}

public static function canDelete($record): bool
{
    return auth()->user()?->can('delete_prospects')
        || auth()->user()?->hasRole(['administrateur']);
}
```

### 4. Gérer la visibilité des champs par rôle

```php
// Dans form() ou table()

TextInput::make('nom')
    ->visible(fn () => auth()->user()?->hasRole(['administrateur', 'team_leader', 'commercial']))
    ->disabled(fn () => !auth()->user()?->can('edit_prospects'));

TextInput::make('notes_internes')
    ->visible(fn () => auth()->user()?->hasRole(['administrateur', 'team_leader']));
```

### 5. Créer des permissions personnalisées

#### Via le panel Administration
1. Accéder à Administration > Permissions
2. Créer une nouvelle permission (ex: `view_sensitive_data`)
3. Assigner la permission aux rôles appropriés

#### Via seeder
```php
// database/seeders/RolesAndPermissionsSeeder.php

$permission = Permission::firstOrCreate([
    'name' => 'view_sensitive_data',
    'guard_name' => 'web',
]);

$role = Role::findByName('team_leader');
$role->givePermissionTo($permission);
```

### 6. Tester les permissions

1. Se connecter avec un utilisateur du rôle testé
2. Vérifier l'accès aux entités
3. Vérifier la visibilité des champs
4. Tester les actions CRUD autorisées/interdites

## Bonnes pratiques

- Toujours utiliser les méthodes `can*` de Filament pour les permissions
- Privilégier la visibilité conditionnelle des champs plutôt que leur suppression
- Documenter les permissions dans les commentaires du code
- Tester les permissions pour chaque rôle après modification
- Utiliser les rôles existants avant d'en créer de nouveaux

## Rôles existants et permissions par défaut

| Rôle | Prospects | Partenaires | Appels | RDV | Admin |
|------|----------|-------------|--------|-----|-------|
| administrateur | Full | Full | Full | Full | Full |
| team_leader | View/Edit | View/Edit | View/Edit | View/Edit | View |
| commercial | View/Edit/Create | View/Edit/Create | View/Create | View/Edit | - |
| teleprospecteur | View/Edit/Create | View | View/Create | - | - |
| operateur_n1 | View | View | View | - | - |
| back_office | View/Edit | View/Edit | View/Edit | View/Edit | - |

## Exemples d'utilisation

### Masquer un champ pour les téléprospecteurs
```php
TextInput::make('marge_commerciale')
    ->visible(fn () => !auth()->user()?->hasRole('teleprospecteur'));
```

### Désactiver l'édition pour les opérateurs
```php
Select::make('statut')
    ->disabled(fn () => auth()->user()?->hasRole('operateur_n1'));
```

### Afficher uniquement aux administrateurs
```php
TextInput::make('notes_admin')
    ->visible(fn () => auth()->user()?->hasRole('administrateur'));
```

## Dépannage

### Problème: Les permissions ne s'appliquent pas
- Vérifier que le cache de permissions est vidé: `php artisan cache:clear`
- Vérifier que l'utilisateur a bien le rôle attendu
- Vérifier les permissions dans la table `permissions`

### Problème: Les champs restent visibles
- Vérifier que la condition `visible()` est correcte
- Vérifier qu'il n'y a pas de conflit avec d'autres conditions
- Tester avec un utilisateur différent du même rôle
