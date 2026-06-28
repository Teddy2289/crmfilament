# Réorganisation des Services CRM

## Vue d'ensemble

Ce document décrit la réorganisation des fonctionnalités similaires du CRM en services dédiés pour améliorer la maintenabilité et la réutilisabilité du code.

## Services Créés

### 1. ViewManagementService
**Emplacement** : `app/Services/Crm/ViewManagementService.php`

**Responsabilités** :
- Gestion des vues personnalisées par utilisateur
- Sauvegarde et suppression de vues
- Gestion de la vue actuelle en session
- Récupération des vues disponibles (par défaut + personnalisées)
- Application de la vue par défaut

**Utilisé par** :
- `ViewSelectorWidget` : Sélecteur de vues dans les pages de liste

**Méthodes principales** :
- `getCurrentView()` : Récupère la vue actuelle depuis la session
- `setCurrentView(string $view)` : Définit la vue actuelle
- `getAvailableViews()` : Retourne les vues disponibles (list, kanban, personnalisées)
- `saveView(array $data)` : Sauvegarde une vue personnalisée
- `deleteView(int $viewId)` : Supprime une vue personnalisée
- `getDefaultView()` : Récupère la vue par défaut de l'utilisateur
- `applyDefaultView()` : Applique la vue par défaut si définie

### 2. SearchAndRelationService
**Emplacement** : `app/Services/Crm/SearchAndRelationService.php`

**Responsabilités** :
- Recherche globale transverse (prospects, clients, partenaires, entreprises)
- Recherche par téléphone, email, nom, ref_client, siret, siren
- Identification des relations entre entités
- Recherche d'entités liées par coordonnées communes

**Utilisé par** :
- `GlobalSearch` : Page de recherche globale

**Méthodes principales** :
- `searchGlobal(string $query)` : Recherche dans toutes les entités
- `searchProspects(string $query)` : Recherche dans les prospects
- `searchClients(string $query)` : Recherche dans les clients
- `searchPartenaires(string $query)` : Recherche dans les partenaires
- `searchEntreprises(string $query)` : Recherche dans les entreprises
- `findRelatedEntities(string $type, int $id)` : Trouve les entités liées
- `findRelatedToProspect(Prospect $prospect)` : Relations pour un prospect
- `findRelatedToClient(Client $client)` : Relations pour un client
- `findRelatedToPartenaire(Partenaire $partenaire)` : Relations pour un partenaire

### 3. PermissionService
**Emplacement** : `app/Services/Crm/PermissionService.php`

**Responsabilités** :
- Gestion des permissions de champs par rôle
- Vérification de visibilité des champs (list, view, edit)
- Vérification du mode lecture seule
- Définition des permissions de champs
- Récupération des permissions pour un rôle et contexte

**Utilisé par** :
- `HasFieldPermissions` : Trait pour appliquer les permissions dans les ressources
- `FieldPermissionResource` : Interface back office pour gérer les permissions

**Méthodes principales** :
- `canViewField(string $field, string $context)` : Vérifie la visibilité
- `isFieldReadOnly(string $field)` : Vérifie si lecture seule
- `getFieldPermissionsForRole(string $role)` : Permissions pour un rôle
- `setFieldPermission(array $data)` : Définit une permission
- `deleteFieldPermission(string $role, string $field)` : Supprime une permission
- `getFieldsForContext(string $context)` : Champs visibles pour un contexte
- `getReadOnlyFields()` : Champs en lecture seule

## Composants Mis à Jour

### ViewSelectorWidget
**Avant** : Logique directe dans le widget
**Après** : Utilisation de `ViewManagementService`

**Avantages** :
- Séparation des responsabilités
- Réutilisabilité dans d'autres contextes
- Tests unitaires facilités

### GlobalSearch
**Avant** : Méthodes de recherche directes dans la page
**Après** : Utilisation de `SearchAndRelationService`

**Avantages** :
- Logique de recherche centralisée
- Facilité d'ajout de nouvelles entités
- Réutilisation dans d'autres pages

### HasFieldPermissions Trait
**Avant** : Appels directs au modèle `FieldPermission`
**Après** : Utilisation de `PermissionService`

**Avantages** :
- Couche d'abstraction supplémentaire
- Gestion simplifiée du rôle utilisateur
- Facilité d'extension

## Structure des Services CRM

```
app/Services/Crm/
├── ViewManagementService.php      # Gestion des vues personnalisées
├── SearchAndRelationService.php   # Recherche et relations entre entités
├── PermissionService.php           # Gestion des permissions de champs
├── CrmProfileService.php          # Gestion des profils CRM
├── CrmSettingsService.php         # Gestion des paramètres CRM
├── PipelineStatutService.php      # Gestion des statuts de pipeline
├── FicheWordService.php           # Génération de fiches Word
└── WeeklyReportService.php        # Rapports hebdomadaires
```

## Avantages de la Réorganisation

1. **Maintenabilité** : Logique métier centralisée et facile à trouver
2. **Réutilisabilité** : Services utilisables dans plusieurs contextes
3. **Testabilité** : Services isolés faciles à tester unitairement
4. **Extensibilité** : Facilité d'ajout de nouvelles fonctionnalités
5. **Clarté** : Séparation claire des responsabilités

## Prochaines Étapes

- Intégrer les services dans les ressources (Prospect, Client, Partenaire)
- Créer des tests unitaires pour les services
- Documenter les patterns d'utilisation des services
- Étendre les services pour de nouvelles fonctionnalités
