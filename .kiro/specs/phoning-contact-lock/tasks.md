# Implementation Plan: phoning-contact-lock

## Overview

Implémentation en PHP/Laravel du verrouillage de fiches (ContactLock) et de l'exclusion des rappels non échus dans le module de téléprospection. Les modifications touchent `PhoningQueueBuilder`, `CampagnePhoning`, `PhoningQueueService`, `HasContactQueue` et la vue Blade du workflow.

## Tasks

- [x] 1. Ajouter les méthodes de verrouillage dans `PhoningQueueBuilder`
  - [x] 1.1 Implémenter `acquireContactLock(int $userId, string $type, int $id): bool`
    - Utiliser `Cache::add()` pour l'acquisition atomique avec TTL 30 min
    - Retourner `true` si le verrou est acquis ou si l'utilisateur est déjà propriétaire (reentrant)
    - Émettre `Log::debug` si acquis, `Log::info` si conflit avec un autre propriétaire
    - _Requirements: 1.1, 1.3, 1.4, 7.1, 7.2_

  - [x] 1.2 Implémenter `renewContactLock(int $userId, string $type, int $id): bool`
    - Vérifier que `Cache::get($key) === $userId` avant de renouveler
    - Appeler `Cache::put($key, $userId, now()->addMinutes(30))` si propriétaire
    - Émettre `Log::debug` sur renouvellement réussi
    - Retourner `false` si l'utilisateur n'est pas propriétaire
    - _Requirements: 2.1, 2.2_

  - [x] 1.3 Enrichir `releaseQueueReservationForUser()` avec log `debug`
    - La méthode existe déjà ; ajouter `Log::debug('ContactLock libéré', [..., 'action' => 'release'])` après le `Cache::forget()`
    - S'assurer que la libération ne se fait que si `(int) Cache::get($key) === $userId`
    - _Requirements: 3.5, 7.1_

  - [x] 1.4 Modifier `reserveQueueForUser()` — porter le TTL de 15 à 30 minutes
    - Remplacer `now()->addMinutes(15)` par `now()->addMinutes(30)` sur les deux appels dans la méthode
    - _Requirements: 6.2_

  - [ ]* 1.5 Écrire les tests de propriétés PBT pour `PhoningQueueBuilder` — verrous
    - **Property 1 : Acquisition atomique du verrou** — pour tout `(userId, type, id)`, si `acquireContactLock` retourne `true` alors `Cache::get(key) === userId`; si `userA` détient le verrou, `acquireContactLock(userB, ...)` retourne `false` et la clé reste `userA`
    - **Validates: Requirements 1.1, 1.3, 1.4**
    - **Property 3 : Renouvellement — ownership check** — `renewContactLock(userB, ...)` retourne `false` et ne modifie pas le TTL ; `renewContactLock(userA, ...)` retourne `true` et la clé reste `userA`
    - **Validates: Requirements 2.1, 2.2**
    - **Property 4 : Release uniquement par le propriétaire** — `releaseQueueReservationForUser(userB, ...)` ne supprime pas la clé si `userB ≠ userA`
    - **Validates: Requirements 3.5**
    - Minimum 100 itérations par propriété
    - Tag : `// Feature: phoning-contact-lock, Property {N}: {description}`

- [x] 2. Modifier `filterValidQueue()` dans `PhoningQueueBuilder`
  - [x] 2.1 Ajouter le paramètre `?int $userId = null` à la signature
    - Mettre à jour la signature : `filterValidQueue(array $queue, ?int $userId = null): array`
    - _Requirements: 4.3_

  - [x] 2.2 Ajouter le filtre « verrous étrangers » dans `filterValidQueue()`
    - Si `$userId` est fourni, exclure les items dont `Cache::get("phoning_queue_reservation_{type}_{id}")` est défini et différent de `$userId`
    - Les items non verrouillés et les items verrouillés par `$userId` lui-même sont conservés
    - _Requirements: 4.1, 4.2_

  - [x] 2.3 Ajouter le filtre « rappels non échus » dans `filterValidQueue()`
    - Pour les items de type `prospect`, charger `rappel_planifie_at` et exclure ceux où `rappel_planifie_at IS NOT NULL AND rappel_planifie_at > now()`
    - Préserver l'ordre relatif des items non exclus
    - _Requirements: 5.2, 8.2_

  - [ ]* 2.4 Écrire les tests de propriétés PBT pour `filterValidQueue()`
    - **Property 7 : filterValidQueue avec userId exclut les verrous étrangers** — aucun item dont la clé cache vaut un autre userId ne doit apparaître dans le résultat ; items non verrouillés et items verrouillés par `userId` sont conservés
    - **Validates: Requirements 4.1**
    - **Property 8 : filterValidQueue sans userId ne filtre pas les verrous** — comportement identique à l'actuel, aucun item supprimé sur base d'un verrou cache
    - **Validates: Requirements 4.2**
    - **Property 10 : Exclusion rappels non échus dans filterValidQueue** — exclut exactement les items `prospect` avec `rappel_planifie_at > now()` ; l'ordre relatif des items restants est préservé
    - **Validates: Requirements 5.2, 8.2**

  - [ ]* 2.5 Écrire les tests de propriétés PBT pour `reserveQueueForUser()` — verrous étrangers
    - **Property 2 : Filtrage des verrous étrangers dans la file** — `reserveQueueForUser(userId, queue)` ne retourne aucun item dont la clé cache est définie avec une valeur ≠ `userId` ; si tous les items sont verrouillés par d'autres, le résultat est `[]`
    - **Validates: Requirements 1.2, 1.5, 6.2**

- [x] 3. Modifier `CampagnePhoning::buildQueueQuery()` — exclusion rappels non échus
  - [x] 3.1 Ajouter la clause de filtrage rappel dans `buildQueueQuery()`
    - Dans la branche `type_entite === 'prospects'` (via `applyProspectQueueFilters()` ou directement dans `buildQueueQuery()`), ajouter :
      ```php
      $query->where(function ($q) {
          $q->whereNull('rappel_planifie_at')
            ->orWhere('rappel_planifie_at', '<=', now());
      });
      ```
    - Vérifier que la clause ne s'applique pas aux types `partenaire` et `client`
    - _Requirements: 5.1, 5.3, 5.4, 5.5_

  - [ ]* 3.2 Écrire les tests de propriétés PBT pour `buildQueueQuery()`
    - **Property 9 : Exclusion rappels non échus dans buildQueueQuery** — pour tout ensemble de prospects avec `rappel_planifie_at` ∈ {null, passé, futur}, seuls ceux avec `rappel_planifie_at IS NULL OR rappel_planifie_at <= now()` apparaissent dans la requête
    - **Validates: Requirements 5.1, 5.3, 5.4, 5.5**

- [x] 4. Checkpoint — vérifier le pipeline `buildDefaultQueue`
  - Vérifier que l'ordre des filtres dans `buildDefaultQueue` → `prioriserFile` → `reserveQueueForUser` (via `PhoningQueueService`) est correct : exclusion rappels non échus (délégué à `buildQueueQuery`), puis exclusion verrous étrangers (dans `reserveQueueForUser`), puis priorisation rappels échus (`prioriserFile`)
  - Lancer `php artisan test --filter PhoningQueue` pour s'assurer qu'aucune régression n'est introduite
  - _Requirements: 6.1, 6.2, 6.3_

- [x] 5. Modifier `PhoningQueueService::getQueueForUser()`
  - [x] 5.1 Passer `$userId` à `filterValidQueue()` sur le chemin cache hit
    - Remplacer `$this->builder->filterValidQueue($cached)` par `$this->builder->filterValidQueue($cached, $userId)`
    - _Requirements: 4.1, 4.2_

- [x] 6. Modifier `HasContactQueue` — gestion du verrou dans le cycle de vie
  - [x] 6.1 Modifier `loadNextContact()` — libération du verrou précédent + acquisition du nouveau
    - Avant `array_shift($this->contactQueue)`, libérer le verrou du `currentContact` courant via `releaseQueueReservationForUser()` si `currentContact !== null`
    - Après avoir résolu le nouveau contact, appeler `acquireContactLock(Auth::id(), $type, $id)`
    - Si `acquireContactLock` retourne `false`, appeler `loadNextContact()` récursivement (skip silencieux)
    - _Requirements: 1.1, 1.2, 3.2_

  - [x] 6.2 Modifier `skipCall()` — libération du verrou avant le skip
    - Libérer le verrou du `currentContact` courant via `releaseQueueReservationForUser()` avant de déplacer l'item en fin de file
    - Gérer le cas `currentContact === null` et `contactQueue === []` sans erreur
    - _Requirements: 3.3_

  - [x] 6.3 Ajouter `renewCurrentContactLock()` — renouvellement actif du verrou
    - Vérifier que `currentContact !== null` et que `type`/`id` sont valides
    - Appeler `app(PhoningQueueBuilder::class)->renewContactLock(Auth::id(), $type, $id)`
    - Si `false` est retourné : émettre une notification Filament `warning` ("Fiche libérée — Le verrou sur cette fiche a expiré. Passage au contact suivant.") puis appeler `loadNextContact()`
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 6.4 Ajouter `dehydrate()` — libération du verrou à la fermeture de la page
    - Si `currentContact !== null`, appeler `releaseQueueReservationForUser(Auth::id(), $type, $id)`
    - Si `currentContact === null`, ne rien faire
    - _Requirements: 3.4_

  - [ ]* 6.5 Écrire les tests de propriétés PBT pour `HasContactQueue` — cycle de vie du verrou
    - **Property 5 : Release systématique sur action** — après `skipCall()` ou chargement du contact suivant via `loadNextContact()`, la clé cache du contact précédent doit être absente
    - **Validates: Requirements 3.2, 3.3**
    - **Property 6 : Transition de contact** — après chargement de B depuis [A, B], la clé de A est absente et la clé de B vaut `userId`
    - **Validates: Requirements 3.2**

  - [ ]* 6.6 Écrire les tests unitaires pour `HasContactQueue` — cas d'intégration
    - `loadNextContact` skip récursif si le contact est verrouillé par un autre
    - `dehydrate` libère le verrou courant
    - `renewCurrentContactLock` envoie la notification Filament si le verrou est perdu et passe au suivant
    - _Requirements: 1.2, 2.3, 3.4_

- [x] 7. Modifier la vue Blade `phoning-workflow.blade.php` — polling wire:poll
  - [x] 7.1 Ajouter le polling `wire:poll.300000ms` sur `renewCurrentContactLock`
    - Envelopper le contenu principal du composant dans un `<div wire:poll.300000ms="renewCurrentContactLock">` ou utiliser l'attribut `#[Poll(300000)]` sur la méthode (Livewire v3)
    - Vérifier que le polling ne crée pas de re-render complet de la page (utiliser `wire:poll.keep-alive` si nécessaire)
    - _Requirements: 2.1_

- [x] 8. Checkpoint final — Vérifier l'ensemble de la feature
  - Lancer la suite de tests : `php artisan test --filter PhoningContactLock`
  - Vérifier les logs `debug`/`info` lors de l'acquisition, du renouvellement et de la libération de verrous
  - S'assurer que la file vide est gérée correctement quand tous les contacts sont verrouillés
  - Assurer qu'aucune régression n'est introduite sur `PhoningQueueService` et `HasContactQueue`

## Notes

- Les tâches marquées `*` sont optionnelles (tests PBT et tests d'intégration) ; elles peuvent être ignorées pour un MVP rapide
- Chaque tâche référence les exigences spécifiques pour la traçabilité
- L'ordre d'implémentation suit la dépendance : Builder → CampagnePhoning → Service → Trait → Blade
- Les tests PBT utilisent des générateurs manuels Pest avec minimum 100 itérations par propriété
- Tag format pour les tests PBT : `// Feature: phoning-contact-lock, Property {N}: {description}`
- Aucune migration de base de données n'est nécessaire : `rappel_planifie_at` existe déjà sur `Prospect`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["2.1", "3.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "1.5", "3.2"] },
    { "id": 3, "tasks": ["2.4", "2.5", "5.1"] },
    { "id": 4, "tasks": ["6.1", "6.2", "6.3", "6.4"] },
    { "id": 5, "tasks": ["6.5", "6.6", "7.1"] },
    { "id": 6, "tasks": [] }
  ]
}
```
