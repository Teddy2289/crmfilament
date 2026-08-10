# Implementation Plan: api-platform-integration

## Overview

Implémentation de la couche API REST (et optionnellement GraphQL) dans le CRM Laravel 12 / Filament 3. Le plan suit les composants décrits dans le design : infrastructure de base, authentification Sanctum, autorisations Spatie + Policies, contrôleurs de ressources, sérialisation, pagination/filtrage, rate limiting, documentation OpenAPI, et couche GraphQL optionnelle.

## Tasks

- [x] 1. Mettre en place l'infrastructure de base de l'API
  - Installer les dépendances Composer : `laravel/sanctum`, `spatie/laravel-query-builder`, `dedoc/scramble`
  - Publier et exécuter la migration Sanctum (`personal_access_tokens`)
  - Créer le fichier `routes/api.php` avec le squelette de routes `/api/v1/` (préfixe, groupe `auth:sanctum`, groupes `throttle:login` / `throttle:api`)
  - Créer `config/api.php` (TTLs, flag `graphql_enabled`, limites de rate limiting)
  - Créer le middleware `ForceJsonResponse` et l'enregistrer sur le groupe `api`
  - Configurer les en-têtes CORS dans `config/cors.php` pour autoriser les origines des clients mobile et Vue.js
  - Étendre `bootstrap/app.php` pour le handler d'exceptions JSON API (404, 422, 401, 403, 429, 500 avec `error_id`)
  - Ajouter le trait `HasApiTokens` au modèle `User`
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

- [x] 2. Implémenter l'authentification Sanctum (AuthController + AuthService)
  - [x] 2.1 Créer `AuthService` (émission paire access/refresh, révocation, logging)
    - Implémenter `issueTokenPair(User): array` — deux tokens Sanctum (`access` 24h, `refresh` 30j) avec abilities appropriées
    - Implémenter `revokeAccessToken(User): void` et `revokeAllTokens(User): void`
    - Consigner chaque émission, rafraîchissement et révocation dans les logs (user ID, IP, timestamp)
    - _Requirements: 2.1, 2.6, 2.7, 15.1, 15.4_

  - [ ]* 2.2 Écrire un test de propriété pour le cycle token
    - **Property 3: Refresh cycle produces a functional token**
    - **Validates: Requirements 2.5**

  - [ ]* 2.3 Écrire un test de propriété pour la révocation au logout
    - **Property 4: Logout revokes the token**
    - **Validates: Requirements 2.6**

  - [x] 2.4 Créer `LoginRequest` et `RefreshRequest` (validation des champs)
    - _Requirements: 2.1, 2.5_

  - [x] 2.5 Créer `AuthController` (login, refresh, logout, me)
    - `login` : valide les identifiants, retourne paire de tokens ou 401 générique
    - `refresh` : valide le refresh token (ability `[refresh]`), émet nouveau access token
    - `logout` : révoque le token courant, retourne 204
    - `me` : retourne l'utilisateur authentifié
    - Appliquer le middleware `throttle:login` sur `login` et `refresh`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [ ]* 2.6 Écrire les tests d'intégration d'authentification
    - Test: identifiants valides → 200 avec `access_token` et `refresh_token`
    - Test: identifiants invalides → 401 message générique
    - Test: token expiré → 401 avec code `token_expired`
    - Test: 11 tentatives rapides → 429 avec en-tête `Retry-After`
    - _Requirements: 2.1, 2.2, 2.4_

- [x] 3. Implémenter la couche d'autorisation (Policies + FieldPermissionService)
  - [x] 3.1 Créer `FieldPermissionService`
    - Implémenter `filterFields(array $data, string $role, string $resource, string $action): array`
    - Interroger les enregistrements `FieldPermission` et supprimer les champs `visible_view = false`
    - Retourner tous les champs si aucun enregistrement `FieldPermission` n'existe (comportement par défaut visible)
    - _Requirements: 3.4, 12.4_

  - [ ]* 3.2 Écrire un test de propriété pour le masquage de champs
    - **Property 6: FieldPermission masks hidden fields**
    - **Validates: Requirements 3.4, 12.4**

  - [x] 3.3 Créer les Policies pour toutes les ressources
    - `ProspectPolicy`, `PartenairePolicy`, `ClientPolicy`, `TicketPolicy`, `ReclamationPolicy`, `DevisPolicy`, `BonDeCommandePolicy`, `RendezVousPolicy`, `CampagnePhoningPolicy`
    - Déléguer à `$user->hasPermissionTo()` (Spatie)
    - Implémenter la restriction `Téléprospecteur` dans `ProspectPolicy::view()` et `ProspectPolicy::viewAny()`
    - Implémenter la gate `validerQf` via `CrmProfile.can_validate_qf`
    - Enregistrer les policies dans `AuthServiceProvider`
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6_

  - [ ]* 3.4 Écrire un test de propriété pour la gate de permissions
    - **Property 5: Permission gate enforces role access**
    - **Validates: Requirements 3.1, 3.2, 3.3**

  - [ ]* 3.5 Écrire un test de propriété pour l'isolation Téléprospecteur
    - **Property 7: Téléprospecteur scope isolation**
    - **Validates: Requirements 3.5**

  - [ ]* 3.6 Écrire un test de propriété pour la gate QF
    - **Property 8: QF validation respects CrmProfile gate**
    - **Validates: Requirements 3.6**

- [ ] 4. Checkpoint — Vérifier que les tests d'auth et d'autorisation passent
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Créer l'infrastructure partagée des contrôleurs et ressources
  - [x] 5.1 Créer `ApiController` (classe abstraite de base)
    - Implémenter `success(mixed $data, int $status = 200): JsonResponse`
    - Implémenter `error(string $message, int $status, array $errors = []): JsonResponse`
    - Implémenter `paginate(QueryBuilder|Builder $query, string $resourceClass): JsonResponse` avec cap à 100 éléments et métadonnées complètes
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 12.1, 12.2_

  - [ ]* 5.2 Écrire un test de propriété pour les métadonnées de pagination
    - **Property 12: Pagination metadata always present**
    - **Validates: Requirements 11.1, 11.4**

  - [ ]* 5.3 Écrire un test de propriété pour le cap `per_page`
    - **Property 13: per_page capped at 100**
    - **Validates: Requirements 11.3**

  - [ ]* 5.4 Écrire un test de propriété pour l'enveloppe des réponses de succès
    - **Property 14: Success response envelope**
    - **Validates: Requirements 12.1**

  - [ ]* 5.5 Écrire un test de propriété pour l'enveloppe des réponses d'erreur
    - **Property 15: Error response envelope**
    - **Validates: Requirements 12.2**

  - [ ]* 5.6 Écrire un test de propriété pour le Content-Type JSON
    - **Property 1: JSON Content-Type for all API responses**
    - **Validates: Requirements 1.2**

- [ ] 6. Implémenter le contrôleur et les ressources Prospects
  - [x] 6.1 Créer `ProspectResource` et `ProspectCollection`
    - Sérialiser tous les champs (id, nom, statut, siret, téléphone, email, ville, etc.)
    - Convertir les Enums en leur valeur de chaîne lisible (`.value`)
    - Appeler `FieldPermissionService::filterFields()` pour masquer les champs selon le rôle
    - Supporter les relations chargées (`commercial`, `teleprospecteur`, `campagne`) via `whenLoaded`
    - _Requirements: 12.3, 12.4, 12.5, 12.6_

  - [ ]* 6.2 Écrire un test de propriété pour la sérialisation des Enums
    - **Property 16: Enum fields serialized as strings**
    - **Validates: Requirements 12.3**

  - [ ]* 6.3 Écrire un test de propriété pour le round-trip de sérialisation
    - **Property 17: Serialization round-trip**
    - **Validates: Requirements 12.6**

  - [x] 6.4 Créer `StoreProspectRequest` et `UpdateProspectRequest`
    - Définir les règles de validation (champs obligatoires, formats)
    - _Requirements: 4.6_

  - [~] 6.5 Créer `ProspectController`
    - `index` : QueryBuilder avec filtres autorisés (`statut`, `campagne_id`, `search` via scope), tris (`created_at`, `nom`, `statut`, `updated_at`), includes, restriction scope Téléprospecteur
    - `show`, `store`, `update`, `destroy` : avec `$this->authorize()`
    - `enregistrerAppel` : valider `statut_phoning_id`, enregistrer l'appel, mettre à jour `ProspectStatut`
    - Retourner 422 si `statut_phoning_id` invalide
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 10.3, 10.4_

  - [ ]* 6.6 Écrire un test de propriété pour le filtre par statut
    - **Property 9: Filter by statut returns only matching records**
    - **Validates: Requirements 4.2**

  - [ ]* 6.7 Écrire les tests d'intégration ProspectController
    - Test: filtre `campagne_id`, filtre `search` (partiel, insensible à la casse)
    - Test: 404 sur ID inexistant, 422 sur payload invalide
    - Test: Téléprospecteur ne voit que ses Prospects
    - _Requirements: 4.3, 4.4, 4.5, 4.6_

- [ ] 7. Implémenter les contrôleurs Partenaires, Clients
  - [x] 7.1 Créer `PartenaireResource`, `PartenaireCollection`, `StorePartenaireRequest`, `UpdatePartenaireRequest`
    - Sérialiser les champs, convertir Enums (`OrganizationStatus`, `OrganizationType`)
    - _Requirements: 12.3_

  - [~] 7.2 Créer `PartenaireController`
    - `index` : filtres `statut`, `type` ; tri par défaut
    - `show`, `store`, `update` (pas de `destroy`)
    - `contacts` : liste paginée des `ContactPartenaire` liés
    - `rendezVous` : liste des `RendezVous` liés, triée par date décroissante
    - Retourner 404 sur ID inexistant
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [x] 7.3 Créer `ClientResource`, `ClientCollection`
    - Lecture seule (GET uniquement) — la source de vérité reste Dolibarr
    - _Requirements: 6.4_

  - [~] 7.4 Créer `ClientController`
    - `index` : filtres `partenaire_id`, `search` (nom, prénom, référence client — partiel insensible à la casse)
    - `show`, `dossiersFormation`
    - _Requirements: 6.1, 6.2, 6.3_

  - [ ]* 7.5 Écrire les tests d'intégration Partenaires/Clients
    - Test: filtres `statut`, `type` pour Partenaires
    - Test: Client en lecture seule (POST → 405)
    - Test: filtre `search` partiel insensible à la casse pour Clients
    - _Requirements: 5.2, 5.3, 6.2, 6.3, 6.4_

- [ ] 8. Implémenter les contrôleurs Tickets, Réclamations, Devis, BonsDeCommande
  - [ ] 8.1 Créer `TicketResource`, `StoreTicketRequest`, `UpdateTicketRequest`, `TicketController`
    - `index` : filtre `statut` (`TicketStatut`)
    - `store` : associer `created_by` à l'utilisateur authentifié
    - `show`, `update`
    - _Requirements: 7.1, 7.3, 7.4_

  - [~] 8.2 Créer `ReclamationResource`, `StoreReclamationRequest`, `UpdateReclamationRequest`, `ReclamationController`
    - Vérification du rôle sur la mise à jour du statut (403 si non autorisé)
    - _Requirements: 7.2, 7.5_

  - [~] 8.3 Créer `DevisResource` et `DevisController` (lecture seule)
    - `index` : filtres `statut` (`StatutDevis`), `partenaire_id`
    - `show`
    - Lecture seule pour les rôles `Téléprospecteur` et `Opérateur N1`
    - _Requirements: 8.1, 8.3, 8.4, 8.5_

  - [~] 8.4 Créer `BonDeCommandeResource` et `BonDeCommandeController` (lecture seule)
    - `index`, `show`
    - _Requirements: 8.2_

  - [ ]* 8.5 Écrire les tests d'intégration Tickets/Réclamations/Devis/BDC
    - Test: `created_by` automatique sur `POST /tickets`
    - Test: mise à jour de statut Réclamation sans permission → 403
    - Test: filtres Devis (`statut`, `partenaire_id`)
    - _Requirements: 7.4, 7.5, 8.3, 8.4_

- [ ] 9. Implémenter le contrôleur RendezVous
  - [~] 9.1 Créer `RendezVousResource`, `StoreRendezVousRequest`, `UpdateRendezVousRequest`
    - `StoreRendezVousRequest` et `UpdateRendezVousRequest` : règle de validation `date_fin after:date_debut`
    - _Requirements: 9.5_

  - [~] 9.2 Créer `RendezVousController`
    - `index` : retourner uniquement les RendezVous de l'utilisateur authentifié (sauf rôle à visibilité élargie) ; filtres `date_debut` et `date_fin` (bornes incluses)
    - `show`, `store` (201 + ressource créée), `update`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [ ]* 9.3 Écrire un test de propriété pour le filtre de plage de dates
    - **Property 11: Date range filter returns only in-range RendezVous**
    - **Validates: Requirements 9.3**

  - [ ]* 9.4 Écrire un test de propriété pour la validation de payload invalide
    - **Property 10: Invalid POST payload returns 422 with field errors**
    - **Validates: Requirements 4.6, 9.5**

  - [ ]* 9.5 Écrire les tests d'intégration RendezVous
    - Test: scope utilisateur authentifié sur `GET /rendez-vous`
    - Test: `POST` valide → 201 avec ressource créée
    - Test: `date_fin` antérieure à `date_debut` → 422
    - _Requirements: 9.2, 9.4, 9.5_

- [~] 10. Checkpoint — Vérifier que tous les tests de ressources passent
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Implémenter le contrôleur CampagnesPhoning + endpoint Appel
  - [~] 11.1 Créer `CampagnePhoningResource` et `CampagnePhoningController`
    - `index`, `show`
    - `prospects` : liste paginée des Prospects de la campagne, triée par priorité de phoning ; lecture seule si campagne `archivée`
    - _Requirements: 10.1, 10.2, 10.5_

  - [~] 11.2 Créer `EnregistrerAppelRequest` et relier à `ProspectController::enregistrerAppel()`
    - Valider `statut_phoning_id` (existence dans `statut_phonings`) → 422 si invalide
    - Enregistrer l'appel, mettre à jour `ProspectStatut` selon les règles métier
    - _Requirements: 10.3, 10.4_

  - [ ]* 11.3 Écrire les tests d'intégration Campagnes Phoning
    - Test: prospects de campagne triés par priorité
    - Test: campagne archivée → mise à jour bloquée (lecture seule)
    - Test: `statut_phoning_id` invalide → 422
    - _Requirements: 10.2, 10.4, 10.5_

- [ ] 12. Configurer le Rate Limiting et les en-têtes de sécurité
  - [~] 12.1 Configurer les rate limiters dans `bootstrap/app.php` (ou `RouteServiceProvider`)
    - `api` : 1 000 requêtes/heure par token/IP → 429 avec `Retry-After`
    - `login` : 10 tentatives/minute par IP → 429 avec `Retry-After`
    - Inclure `X-RateLimit-Limit` et `X-RateLimit-Remaining` dans les réponses authentifiées
    - _Requirements: 14.1, 14.2, 14.3, 14.4_

  - [ ]* 12.2 Écrire un test de propriété pour les en-têtes de rate limit
    - **Property 18: Rate limit headers present on authenticated responses**
    - **Validates: Requirements 14.4**

  - [~] 12.3 Créer `Admin\UserTokenController` (révocation de tous les tokens d'un utilisateur)
    - `DELETE /api/v1/admin/users/{user}/tokens` protégé par le middleware de rôle `super_admin|administrateur`
    - _Requirements: 15.3_

  - [ ]* 12.4 Écrire les tests d'intégration Rate Limiting / Admin Tokens
    - Test: 11 tentatives de login → 429 avec en-tête `Retry-After`
    - Test: admin révoque tous les tokens → requêtes suivantes 401
    - _Requirements: 14.2, 15.3_

- [ ] 13. Implémenter le logging des événements token
  - [~] 13.1 Ajouter le logging dans `AuthService` pour login, refresh, logout
    - Consigner user ID, adresse IP, horodatage dans les logs applicatifs
    - _Requirements: 15.4_

  - [ ]* 13.2 Écrire un test de propriété pour le logging des événements token
    - **Property 19: Token events are logged**
    - **Validates: Requirements 15.4**

  - [~] 13.3 Créer le middleware `EnsureTokenIsNotExpired` et l'appliquer sur les routes protégées
    - Retourner 401 avec code `token_expired` si le token d'accès est expiré
    - Retourner 401 avec code `refresh_token_invalid` si le refresh token est révoqué ou expiré
    - _Requirements: 2.4, 15.2_

- [ ] 14. Configurer la documentation OpenAPI (dedoc/scramble)
  - [~] 14.1 Publier et configurer `config/scramble.php`
    - Pointer sur `api/v1`, définir titre et version
    - Bloquer l'accès en production (403)
    - _Requirements: 13.1, 13.5_

  - [ ]* 14.2 Écrire les tests d'intégration OpenAPI
    - Test: `GET /api/documentation` → 200 en non-production
    - Test: `GET /api/documentation` → 403 en environnement production simulé
    - _Requirements: 13.1, 13.5_

- [ ] 15. Implémenter le support GraphQL optionnel (nuwave/lighthouse)
  - [~] 15.1 Installer `nuwave/lighthouse` et créer `graphql/schema.graphql`
    - Conditionner l'activation sur `config('api.graphql_enabled')`
    - Exposer `/api/graphql` et `/api/graphql-playground` (non-production uniquement)
    - Définir les types `Prospect`, `Partenaire` et les queries paginées avec `@paginate @guard`
    - _Requirements: 16.1, 16.2_

  - [~] 15.2 Créer le middleware Lighthouse appliquant les permissions Spatie aux requêtes GraphQL
    - Appliquer les mêmes règles d'autorisation qu'en REST
    - Appliquer les mêmes limites de rate limiting (`throttle:api`)
    - _Requirements: 16.3, 16.4_

  - [ ]* 15.3 Écrire les tests d'intégration GraphQL
    - Test: query `prospects` avec Sanctum token → 200
    - Test: query sans token → 401
    - Test: playground désactivé en production
    - _Requirements: 16.1, 16.2, 16.3_

- [~] 16. Checkpoint final — Vérifier que toute la suite de tests passe
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Les tâches marquées `*` sont optionnelles et peuvent être ignorées pour un MVP plus rapide
- Chaque tâche référence les exigences spécifiques pour la traçabilité
- Les propriétés de test (Property N) correspondent aux propriétés définies dans le document de design
- Les tests de propriétés utilisent PestPHP avec `->with()` et des datasets générés (min. 100 itérations)
- Les tests unitaires valident des exemples spécifiques et des cas limites
- Le code Filament existant (`routes/web.php`, `app/Filament/**`) ne doit pas être modifié

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1"] },
    { "id": 1, "tasks": ["2.1", "2.4", "3.1", "5.1"] },
    { "id": 2, "tasks": ["2.5", "3.3", "5.2", "5.3", "5.4", "5.5", "5.6"] },
    { "id": 3, "tasks": ["2.2", "2.3", "2.6", "3.2", "3.4", "3.5", "3.6", "6.1", "6.4", "7.1", "7.3", "8.1", "8.2", "8.3", "8.4", "9.1"] },
    { "id": 4, "tasks": ["6.2", "6.3", "6.5", "7.2", "7.4", "9.2", "11.1", "11.2", "12.1", "12.3", "13.1", "13.3"] },
    { "id": 5, "tasks": ["6.6", "6.7", "7.5", "8.5", "9.3", "9.4", "9.5", "11.3", "12.2", "12.4", "13.2", "14.1"] },
    { "id": 6, "tasks": ["14.2", "15.1"] },
    { "id": 7, "tasks": ["15.2"] },
    { "id": 8, "tasks": ["15.3"] }
  ]
}
```
