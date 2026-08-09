# Implementation Plan: Email Mailbox Switcher

## Overview

Enrichit la page `ListEmails` du panel NsConseil avec un sélecteur de boîte mail active (`MailboxSwitcherService` + modifications `ListEmails`). La persistance se fait en session HTTP Laravel, sans migration de base de données.

## Tasks

- [x] 1. Créer le service `MailboxSwitcherService`
  - [x] 1.1 Créer `app/Services/Email/MailboxSwitcherService.php` avec l'interface et l'implémentation
    - Implémenter `getAvailableMailboxes(int $userId): Collection` — scope `EmailConfiguration::forUser()` + globales, triées (personnelles en premier, globales ensuite)
    - Implémenter `resolveActiveMailbox(int $userId): ?EmailConfiguration` — priorité session → fallback première config → null
    - Implémenter `switchMailbox(int $configId): void` — `session(['active_mailbox_id' => $configId])`
    - Implémenter `buildOptionLabel(EmailConfiguration $config): string` — retourne `"{from_name} <{email}>"` ou `"{email}"`, avec indicateur visuel si `is_global`
    - _Requirements : 1.1, 1.2, 1.3, 2.1, 2.2, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 6.3_

  - [ ]* 1.2 Écrire le test property-based pour la Property 1 (label complet)
    - **Property 1 : Le sélecteur affiche les informations complètes de chaque configuration**
    - Pour toute `EmailConfiguration`, `buildOptionLabel()` doit contenir l'adresse email ; si `from_name` est renseigné, il doit également apparaître
    - **Validates : Requirements 1.1, 6.3**

  - [ ]* 1.3 Écrire le test property-based pour la Property 2 (unicité → disabled)
    - **Property 2 : Unicité = sélecteur désactivé**
    - Pour tout utilisateur avec exactement 1 config active, `getAvailableMailboxes()` retourne exactement 1 élément
    - **Validates : Requirements 1.2, 6.2**

  - [ ]* 1.4 Écrire le test property-based pour la Property 3 (liste complète)
    - **Property 3 : Toutes les configurations accessibles sont listées**
    - Pour tout utilisateur avec N > 1 configs actives, `getAvailableMailboxes()` retourne exactement N éléments
    - **Validates : Requirements 2.1**

  - [ ]* 1.5 Écrire le test property-based pour la Property 4 (round-trip session)
    - **Property 4 : Basculement mémorisé en session (round-trip)**
    - Pour tout `$id` valide, `switchMailbox($id)` doit entraîner `session()->get('active_mailbox_id') === $id`
    - **Validates : Requirements 2.2, 3.1**

  - [ ]* 1.6 Écrire le test property-based pour la Property 5 (ordre de tri)
    - **Property 5 : Ordre de tri — personnelles avant globales**
    - Pour tout ensemble mixte de configs, `getAvailableMailboxes()` place tous les `is_global = false` avant tous les `is_global = true`
    - **Validates : Requirements 2.5**

  - [ ]* 1.7 Écrire le test property-based pour la Property 7 (résolution session valide)
    - **Property 7 : Résolution de la boîte active avec session valide**
    - Pour tout `active_mailbox_id` en session pointant vers une config encore active et accessible, `resolveActiveMailbox()` retourne exactement cette config
    - **Validates : Requirements 3.2**

  - [ ]* 1.8 Écrire le test property-based pour la Property 8 (fallback)
    - **Property 8 : Fallback vers la première config disponible**
    - Pour tout `active_mailbox_id` invalide, inactif ou inaccessible en session, `resolveActiveMailbox()` retourne la première config disponible selon l'ordre de tri, ou `null`
    - **Validates : Requirements 3.3, 3.4**

- [x] 2. Checkpoint — S'assurer que tous les tests du service passent, demander à l'utilisateur en cas de question.

- [x] 3. Modifier `ListEmails` — Injection du service et header action Mailbox Switcher
  - [x] 3.1 Injecter `MailboxSwitcherService` dans `ListEmails` et exposer les méthodes helper
    - Injecter via `app(MailboxSwitcherService::class)` ou le constructeur
    - Ajouter `getActiveMailboxLabel(): string`, `getMailboxOptions(): array`, `getAvailableMailboxCount(): int`
    - _Requirements : 1.1, 1.4, 6.1_

  - [x] 3.2 Ajouter la header action `mailbox_switcher` dans `getHeaderActions()`
    - Composant `Actions\Action` avec un `Forms\Components\Select` (options, default depuis session, disabled si ≤ 1 config)
    - Action : appel à `switchMailbox()` puis `$this->resetPage()`
    - Indicateur visuel pour les configs globales (`is_global = true`) — badge ou icône dans le label
    - _Requirements : 1.4, 2.1, 2.2, 2.3, 2.4, 6.1, 6.2, 6.3, 6.4_

- [x] 4. Modifier `ListEmails::getTableQuery()` pour filtrer par boîte active
  - [x] 4.1 Remplacer le filtre `user_id = auth()->id()` par la logique basée sur `resolveActiveMailbox()`
    - Si `is_global = true` : `where('from_email', $config->email)`
    - Si `is_global = false` : `where('user_id', auth()->id())` (+ optionnellement `from_email`)
    - Si `null` : retourner une requête vide ou afficher un message d'absence de config
    - _Requirements : 4.1, 4.2, 4.3_

  - [ ]* 4.2 Écrire le test property-based pour la Property 6 (filtrage cohérent)
    - **Property 6 : Filtrage des emails cohérent avec la boîte active**
    - Pour toute `EmailConfiguration` active, la requête ne retourne aucun email d'une autre boîte
    - **Validates : Requirements 4.1, 4.2, 4.3**

- [x] 5. Modifier `ListEmails::syncMailbox()` pour cibler la boîte active
  - [x] 5.1 Remplacer `EmailConfiguration::forUser()` par `resolveActiveMailbox()`
    - Si `null` : afficher une notification `warning` sans invoquer `ImapService`
    - Si exception `\Throwable` : `Log::error()` + notification `danger` avec `$e->getMessage()`
    - Succès : inclure `$stats['synced']` dans la notification de succès
    - _Requirements : 5.1, 5.2, 5.4_

  - [ ]* 5.2 Écrire le test property-based pour la Property 9 (sync cible boîte active)
    - **Property 9 : La synchronisation cible uniquement la boîte active**
    - Pour toute `EmailConfiguration` active, `syncMailbox()` instancie `ImapService` avec exactement cette config et aucune autre
    - **Validates : Requirements 5.1**

  - [ ]* 5.3 Écrire le test property-based pour la Property 10 (notification contient le count)
    - **Property 10 : La notification de succès contient le nombre d'emails synchronisés**
    - Pour toute valeur de `stats['synced']`, la notification de succès doit inclure ce nombre dans son message
    - **Validates : Requirements 5.3**

  - [ ]* 5.4 Écrire les tests d'exemple pour les cas limites de `syncMailbox()`
    - Aucune config active → notification `warning` affichée, `ImapService` non invoqué
    - Exception IMAP → notification `danger` affichée, `Log::error()` appelé
    - _Requirements : 5.2, 5.4_

- [x] 6. Gérer l'état « aucune configuration » dans `ListEmails`
  - [x] 6.1 Afficher le message « Aucune boîte mail configurée » dans le sélecteur quand `resolveActiveMailbox()` retourne `null`
    - Désactiver le bouton "Synchroniser" dans ce cas
    - _Requirements : 1.3, 5.2_

  - [ ]* 6.2 Écrire le test d'exemple pour l'affichage sans configuration
    - Vérifie que le label du sélecteur affiche le message d'absence et que le bouton Synchroniser est désactivé
    - _Requirements : 1.3, 5.2_

- [x] 7. Gérer la remise à zéro de la pagination après changement de boîte
  - [x] 7.1 S'assurer que `$this->resetPage()` est bien appelé dans l'action `mailbox_switcher` après le switch
    - _Requirements : 4.4_

  - [ ]* 7.2 Écrire le test d'exemple pour le reset de pagination
    - Après un switch de boîte, vérifier que la pagination revient à la page 1
    - _Requirements : 4.4_

- [x] 8. Checkpoint final — S'assurer que tous les tests passent, demander à l'utilisateur en cas de question.

## Notes

- Les tâches marquées `*` sont optionnelles et peuvent être ignorées pour un MVP plus rapide.
- Chaque tâche référence les requirements spécifiques pour la traçabilité.
- Les propriétés PBT utilisent la librairie **Eris** (`giorgiosironi/eris`) avec un minimum de 100 itérations.
- Aucune migration de base de données n'est nécessaire — la persistance est entièrement en session.
- L'indicateur visuel des configs globales (🌐 ou badge HTML) nécessite `->allowHtml()` sur le Select Filament.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "1.4", "1.5", "1.6", "1.7", "1.8", "3.1"] },
    { "id": 2, "tasks": ["3.2", "4.1"] },
    { "id": 3, "tasks": ["4.2", "5.1", "6.1", "7.1"] },
    { "id": 4, "tasks": ["5.2", "5.3", "5.4", "6.2", "7.2"] }
  ]
}
```
