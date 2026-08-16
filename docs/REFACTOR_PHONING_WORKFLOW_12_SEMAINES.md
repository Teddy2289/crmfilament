# Plan de Refonte PhoningWorkflow.php - 12 Semaines

## Objectif Global
Transformer `PhoningWorkflow.php` de contrôleur monolithique (1883 lignes) en orchestratrice légère qui délègue à des services métier spécialisés.

---

## Résumé des Priorités
1. **Semaines 1-3** : File d'appels + contact courant (PhoningQueueService)
2. **Semaines 4-6** : Logique de statut et persistance (ProspectStatusTransitionService + ProspectCallPersistenceService)
3. **Semaines 7-8** : Planification des rappels (ReminderSchedulingService)
4. **Semaines 9-10** : Preview email (EmailPreviewService)
5. **Semaines 11-12** : Ringover (RingoverIntegrationService) + validation finale

---

## Semaine 1 : Préparation et Infrastructure

### Objectifs
- Mettre en place l'infrastructure de services
- Créer les dossiers et structure de base
- Préparer les tests

### Tâches
#### Jour 1-2 : Structure des services
- [ ] Créer le dossier `app/Services/Phoning/`
- [ ] Créer les fichiers vides pour les 6 services :
  - `PhoningQueueService.php`
  - `ProspectStatusTransitionService.php`
  - `ReminderSchedulingService.php`
  - `ProspectCallPersistenceService.php`
  - `RingoverIntegrationService.php`
  - `EmailPreviewService.php`
- [ ] Créer le dossier `tests/Unit/Services/Phoning/`
- [ ] Configurer l'autoloading si nécessaire

#### Jour 3-4 : Tests infrastructure
- [ ] Créer un test unitaire de base pour chaque service (vide mais fonctionnel)
- [ ] Vérifier que les tests passent
- [ ] Configurer PHPUnit pour les tests de services

#### Jour 5 : Documentation initiale
- [ ] Documenter la responsabilité de chaque service dans les PHPDoc
- [ ] Créer un diagramme d'architecture haute niveau
- [ ] Préparer l'environnement de développement

### Livrables
- Structure de services créée
- Tests infrastructure en place
- Documentation initiale des responsabilités

---

## Semaine 2 : Extraction PhoningQueueService (Partie 1)

### Objectifs
- Extraire la logique de construction de file
- Isoler les méthodes de base de gestion de queue

### Tâches
#### Jour 1-2 : Construction de file
- [ ] Extraire `buildDefaultQueue()` vers `PhoningQueueService::buildDefaultQueue()`
- [ ] Extraire `filterValidQueue()` vers `PhoningQueueService::filterValidQueue()`
- [ ] Extraire `prioriserFile()` vers `PhoningQueueService::prioritizeQueue()`
- [ ] Créer les tests unitaires pour ces méthodes

#### Jour 3-4 : Réservation et cache
- [ ] Extraire la logique de cache de file vers le service
- [ ] Extraire `reserveQueueForUser()` déjà existant dans `PhoningQueueBuilder`
- [ ] Unifier la logique de réservation dans `PhoningQueueService`
- [ ] Tests de réservation de file

#### Jour 5 : Intégration progressive
- [ ] Modifier `PhoningWorkflow::loadQueue()` pour utiliser le nouveau service
- [ ] Garder l'ancienne méthode en commentaire comme fallback
- [ ] Tests d'intégration avec la page Livewire

### Livrables
- `PhoningQueueService` avec méthodes de base
- Tests unitaires couvrant la construction de file
- Intégration partielle dans PhoningWorkflow

---

## Semaine 3 : Extraction PhoningQueueService (Partie 2)

### Objectifs
- Finaliser l'extraction de la logique de file
- Gérer le chargement du prochain contact

### Tâches
#### Jour 1-2 : Chargement du prochain contact
- [ ] Extraire `loadNextContact()` vers `PhoningQueueService::loadNextContact()`
- [ ] Extraire `ensureRequestedContactPriority()` vers le service
- [ ] Gérer la logique de file vide dans le service
- [ ] Tests pour le chargement de contact

#### Jour 3-4 : Gestion des priorités
- [ ] Centraliser la logique de priorisation RAPL-ELU et rappels en retard
- [ ] Extraire `selectSearchResult()` logique d'ajout en file
- [ ] Tests de priorisation

#### Jour 5 : Nettoyage et validation
- [ ] Supprimer l'ancienne logique de PhoningWorkflow (commentaires)
- [ ] Valider que le workflow fonctionne toujours
- [ ] Tests end-to-end de la file d'appels

### Livrables
- `PhoningQueueService` complet et testé
- PhoningWorkflow allégée des responsabilités de file
- Workflow validé

---

## Semaine 4 : Extraction ProspectStatusTransitionService

### Objectifs
- Centraliser les règles de transition de statut
- Isoler la logique métier de changement de statut

### Tâches
#### Jour 1-2 : Règles de transition
- [ ] Analyser les mappings statut_phoning → pipeline_statut
- [ ] Créer `ProspectStatusTransitionService::determineNextStatus()`
- [ ] Extraire la logique de `updateProspect()` liée au statut
- [ ] Tests des règles de transition

#### Jour 3-4 : Validation des statuts
- [ ] Créer `ProspectStatusTransitionService::validateTransition()`
- [ ] Centraliser la logique de commentaire obligatoire
- [ ] Extraire `getStatusValidationCodes()` vers le service
- [ ] Tests de validation

#### Jour 5 : Intégration
- [ ] Modifier `PhoningWorkflow::updateProspect()` pour utiliser le service
- [ ] Intégrer pour les autres types (artisan, partenaire, etc.)
- [ ] Tests d'intégration

### Livrables
- `ProspectStatusTransitionService` opérationnel
- Logique de statut centralisée
- Tests couvrant les transitions

---

## Semaine 5 : Extraction ProspectCallPersistenceService

### Objectifs
- Isoler la logique de persistance des appels
- Gérer l'enregistrement et les opérations post-appel

### Tâches
#### Jour 1-2 : Enregistrement d'appel
- [ ] Extraire `enregistrerAppel()` vers `ProspectCallPersistenceService::recordCall()`
- [ ] Gérer la création de l'objet Appel
- [ ] Extraire la logique de mapping EventResult
- [ ] Tests d'enregistrement

#### Jour 3-4 : Opérations post-appel
- [ ] Extraire `dispatchFicheGenerationJob()` vers le service
- [ ] Gérer la création de rendez-vous dans le service
- [ ] Centraliser la logique de mise à jour de contact
- [ ] Tests des opérations post-appel

#### Jour 5 : Intégration
- [ ] Modifier `submitResult()` pour utiliser le service
- [ ] Nettoyer PhoningWorkflow de la logique de persistance
- [ ] Tests d'intégration complets

### Livrables
- `ProspectCallPersistenceService` complet
- Logique de persistance isolée
- Tests couvrant l'enregistrement

---

## Semaine 6 : Unification statut + persistance

### Objectifs
- Finaliser l'intégration des services de statut et persistance
- Valider le cœur métier

### Tâches
#### Jour 1-2 : Refactor des méthodes update*
- [ ] Refactor `updateArtisan()` pour utiliser les services
- [ ] Refactor `updatePartenaire()` pour utiliser les services
- [ ] Refactor `updateParticulier()` pour utiliser les services
- [ ] Refactor `updateClient()` pour utiliser les services

#### Jour 3-4 : Nettoyage submitResult()
- [ ] Simplifier `submitResult()` pour n'être qu'orchestration
- [ ] Extraire toute la logique métier vers les services
- [ ] Tests d'orchestration

#### Jour 5 : Validation_bloc métier
- [ ] Tests end-to-end du workflow de soumission
- [ ] Validation des transitions de statut
- [ ] Validation de la persistance
- [ ] Documentation mise à jour

### Livrables
- Bloc métier statut/persistance unifié
- `submitResult()` simplifié
- Workflow validé

---

## Semaine 7 : Extraction ReminderSchedulingService

### Objectifs
- Centraliser la logique de planification de rappels
- Unifier les règles de Prospect.php et PhoningWorkflow.php

### Tâches
#### Jour 1-2 : Normalisation des dates
- [ ] Extraire `normaliserDateRappel()` de Prospect.php vers le service
- [ ] Extraire `estWeekEnd()` vers le service
- [ ] Créer `ReminderSchedulingService::normalizeReminderDate()`
- [ ] Tests de normalisation

#### Jour 3-4 : Planification automatique
- [ ] Centraliser la logique de `programmerRappel()` de Prospect.php
- [ ] Extraire `appliquerRappelProspect()` vers le service
- [ ] Gérer les règles de délai automatique
- [ ] Tests de planification

#### Jour 5 : Intégration
- [ ] Modifier Prospect.php pour utiliser le service
- [ ] Modifier PhoningWorkflow pour utiliser le service
- [ ] Tests d'intégration
- [ ] Suppression de la logique dupliquée

### Livrables
- `ReminderSchedulingService` complet
- Logique de rappel unifiée
- Plus de duplication entre Prospect et PhoningWorkflow

---

## Semaine 8 : Finalisation ReminderSchedulingService

### Objectifs
- Finaliser le service de rappels
- Gérer les cas particuliers et edge cases

### Tâches
#### Jour 1-2 : Cas particuliers
- [ ] Gérer les rappels en retard
- [ ] Gérer les rappels manuels vs automatiques
- [ ] Gérer les règles de tentative max
- [ ] Tests des cas particuliers

#### Jour 3-4 : Notifications de rappel
- [ ] Centraliser la logique de notification de retard
- [ ] Gérer les règles de retry
- [ ] Tests de notifications

#### Jour 5 : Validation et documentation
- [ ] Tests end-to-end des rappels
- [ ] Documentation du service
- [ ] Validation que les règles sont bien centralisées

### Livrables
- `ReminderSchedulingService` finalisé
- Tests complets
- Documentation à jour

---

## Semaine 9 : Extraction EmailPreviewService

### Objectifs
- Isoler la logique de preview email
- Sélection et rendu des mailables

### Tâches
#### Jour 1-2 : Sélection de mailable
- [ ] Extraire `getPreviewMailableForStatut()` vers le service
- [ ] Extraire `buildPreviewRdvMailable()` vers le service
- [ ] Créer `EmailPreviewService::getMailableForStatus()`
- [ ] Tests de sélection

#### Jour 3-4 : Rendu de preview
- [ ] Extraire `getMailableSubject()` vers le service
- [ ] Extraire `getMailableBody()` vers le service
- [ ] Gérer la logique de `invokeProtectedMethod()`
- [ ] Tests de rendu

#### Jour 5 : Destinataires
- [ ] Extraire `resolvePreviewRecipient()` vers le service
- [ ] Gérer les fallbacks d'email
- [ ] Tests de résolution de destinataire

### Livrables
- `EmailPreviewService` avec logique de sélection et rendu
- Tests couvrant les différents types de mailables

---

## Semaine 10 : Finalisation EmailPreviewService

### Objectifs
- Finaliser le service d'email preview
- Intégration avec le workflow

### Tâches
#### Jour 1-2 : Contexte de prospection
- [ ] Extraire `buildProspectionMailContext()` vers le service
- [ ] Gérer la synchronisation du contenu modifié
- [ ] Tests de contexte

#### Jour 3-4 : Intégration UI
- [ ] Modifier `openEmailPreview()` pour utiliser le service
- [ ] Modifier `syncEmailPreviewContent()` pour utiliser le service
- [ ] Modifier `confirmEmailPreview()` pour utiliser le service
- [ ] Tests d'intégration UI

#### Jour 5 : Nettoyage
- [ ] Nettoyer PhoningWorkflow de la logique email
- [ ] Tests end-to-end de preview email
- [ ] Documentation

### Livrables
- `EmailPreviewService` complet
- PhoningWorkflow allégée de la logique email
- Workflow email validé

---

## Semaine 11 : Extraction RingoverIntegrationService

### Objectifs
- Isoler l'intégration Ringover
- Gérer le cycle de vie des appels

### Tâches
#### Jour 1-2 : Initialisation d'appel
- [ ] Extraire `callNow()` vers le service
- [ ] Extraire `captureRingoverDialedPhone()` vers le service
- [ ] Gérer l'initialisation du cycle Ringover
- [ ] Tests d'initialisation

#### Jour 3-4 : Recherche et matching
- [ ] Extraire `searchIncomingCallMatch()` vers le service
- [ ] Gérer la logique d'association automatique
- [ ] Tests de matching

#### Jour 5 : Cycle de vie
- [ ] Extraire `updateRingoverCallLifecycle()` vers le service
- [ ] Gérer les états started/ended
- [ ] Tests du cycle de vie

### Livrables
- `RingoverIntegrationService` avec logique de base
- Tests couvrant l'intégration Ringover

---

## Semaine 12 : Finalisation et Validation

### Objectifs
- Finaliser RingoverIntegrationService
- Validation globale du refactor
- Documentation et livrables finaux

### Tâches
#### Jour 1-2 : Finalisation Ringover
- [ ] Compléter `RingoverIntegrationService`
- [ ] Intégration complète dans PhoningWorkflow
- [ ] Tests d'intégration Ringover
- [ ] Nettoyage de PhoningWorkflow

#### Jour 3-4 : Validation globale
- [ ] Tests end-to-end complets du workflow
- [ ] Tests de performance
- [ ] Validation que rien n'est cassé
- [ ] Tests de régression

#### Jour 5 : Documentation et livrables
- [ ] Documentation de tous les services
- [ ] Mise à jour de AGENTS.md avec les commandes de test
- [ ] Diagramme d'architecture final
- [ ] Rapport de refactor

### Livrables
- `RingoverIntegrationService` complet
- PhoningWorkflow transformée en orchestratrice
- Tests complets et validés
- Documentation complète

---

## Architecture Cible

### Structure des Services

```
app/Services/Phoning/
├── PhoningQueueService.php
│   ├── buildDefaultQueue()
│   ├── filterValidQueue()
│   ├── prioritizeQueue()
│   ├── loadNextContact()
│   ├── reserveQueueForUser()
│   └── ensureRequestedContactPriority()
│
├── ProspectStatusTransitionService.php
│   ├── determineNextStatus()
│   ├── validateTransition()
│   ├── getStatusValidationCodes()
│   └── isCommentRequired()
│
├── ReminderSchedulingService.php
│   ├── normalizeReminderDate()
│   ├── scheduleReminder()
│   ├── scheduleAutoReminder()
│   ├── isWeekend()
│   └── handleOverdueReminders()
│
├── ProspectCallPersistenceService.php
│   ├── recordCall()
│   ├── createAppointment()
│   ├── dispatchFicheGeneration()
│   └── updateContactFields()
│
├── RingoverIntegrationService.php
│   ├── initiateCall()
│   ├── captureDialedPhone()
│   ├── searchIncomingCallMatch()
│   └── updateCallLifecycle()
│
└── EmailPreviewService.php
    ├── getMailableForStatus()
    ├── renderPreview()
    ├── resolveRecipient()
    └── buildMailContext()
```

### PhoningWorkflow.php (Cible)

La page ne contient plus que :
- État UI (propriétés publiques)
- Méthodes d'orchestration (délégation aux services)
- Gestion des événements Livewire
- Actions Filament

---

## Métriques de Succès

### Avant Refactor
- Taille : 1883 lignes
- Responsabilités : 6+ (contrôleur, service, orchestrateur, état UI, intégration)
- Méthodes > 50 lignes : ~15
- Couverture de tests : minimale

### Après Refactor
- Taille PhoningWorkflow : ~400 lignes (orchestration uniquement)
- Responsabilités : 1 (orchestration UI)
- Services créés : 6 spécialisés
- Méthodes > 50 lignes : 0
- Couverture de tests : >80% pour les services

---

## Risques et Mitigations

### Risque 1 : Régression fonctionnelle
- **Mitigation** : Tests end-to-end avant chaque semaine
- **Mitigation** : Conserver l'ancien code en commentaire pendant 2 semaines

### Risque 2 : Complexité de migration
- **Mitigation** : Approche progressive par priorité
- **Mitigation** : Intégration progressive avec fallback

### Risque 3 : Perte de contexte métier
- **Mitigation** : Documentation détaillée de chaque service
- **Mitigation** : Revue de code après chaque semaine

---

## Checklist de Validation Finale

- [ ] Tous les services créés et testés
- [ ] PhoningWorkflow réduite à <500 lignes
- [ ] Tests unitaires >80% de couverture
- [ ] Tests end-to-end passent
- [ ] Aucune régression détectée
- [ ] Documentation complète
- [ ] Diagramme d'architecture à jour
- [ ] AGENTS.md mis à jour
- [ ] Performance maintenue ou améliorée
- [ ] Code review validée

---

## Notes

- Ce plan peut être ajusté selon les contraintes du projet
- Chaque semaine peut être divisée en 2 sprints de 2.5 jours
- Les tests sont prioritaires sur l'implémentation
- La documentation se fait en parallèle du code