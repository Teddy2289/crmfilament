# Requirements Document

## Introduction

Ce document couvre deux comportements à implémenter dans le module de phoning (prospection téléphonique CSE) :

1. **Verrouillage de fiche pendant le travail** : quand un téléprospecteur ouvre une fiche dans `PhoningWorkflow`, la fiche est verrouillée pour les autres utilisateurs jusqu'à ce qu'il la quitte (soumission du résultat, passage au suivant, ou fermeture de page). Tout autre téléprospecteur qui tenterait de charger ce même contact est automatiquement exclu de sa file et passe au contact suivant.

2. **Exclusion des rappels non échus** : si un rappel est planifié sur une fiche dans une campagne de prospection, la fiche ne doit plus apparaître dans la file d'appels tant que la date/heure du rappel n'est pas atteinte. Elle réapparaît automatiquement dès que ce moment est passé.

Le mécanisme de réservation cache (`phoning_queue_reservation_{type}_{id}`) existe déjà partiellement dans `PhoningQueueBuilder` mais souffre d'un TTL trop court, d'un renouvellement absent et d'un filtrage insuffisant.

## Glossary

- **ContactLock** : verrou de session posé en cache sur une fiche pendant qu'un téléprospecteur la travaille activement dans `PhoningWorkflow`.
- **PhoningQueueBuilder** : service responsable de la construction, de la réservation et du filtrage de la file d'appels.
- **PhoningQueueService** : orchestrateur de la file d'appels ; point d'entrée unique pour `HasContactQueue`.
- **HasContactQueue** : trait Livewire gérant la file d'appels côté page `PhoningWorkflow`.
- **HasSubmitResult** : trait Livewire gérant la soumission du résultat d'appel et la libération du verrou.
- **CampagnePhoning** : modèle représentant une campagne de prospection téléphonique.
- **Prospect** : fiche de type prospect ; possède un champ `rappel_planifie_at` (datetime nullable).
- **Rappel_Planifie_At** : date/heure à partir de laquelle un contact redevient appelable après un rappel planifié.
- **File_D_Appels** : séquence ordonnée de contacts à appeler, propre à chaque téléprospecteur.
- **ContactReservation** : entrée cache `phoning_queue_reservation_{type}_{id}` indiquant quel utilisateur détient le verrou d'une fiche.
- **Teleprospecteur** : utilisateur actif dans la page `PhoningWorkflow`.

## Requirements

### Requirement 1: Verrouillage automatique au chargement

**User Story:** En tant que téléprospecteur, je veux que la fiche que j'ai ouverte soit automatiquement verrouillée pour moi dès son chargement, afin qu'aucun autre téléprospecteur ne puisse travailler simultanément sur le même contact.

#### Acceptance Criteria

1. WHEN `HasContactQueue` charge un contact via `loadNextContact()`, THE `PhoningQueueBuilder` SHALL poser un `ContactLock` sur cette fiche avec un TTL de 30 minutes.
2. WHEN le `ContactLock` d'une fiche est déjà détenu par un autre `Teleprospecteur`, THE `PhoningQueueBuilder` SHALL exclure cette fiche de la `File_D_Appels` du `Teleprospecteur` demandeur et passer automatiquement au contact suivant.
3. WHEN un `ContactLock` est posé sur une fiche, THE `PhoningQueueBuilder` SHALL enregistrer l'identifiant du `Teleprospecteur` propriétaire dans la `ContactReservation` correspondante.
4. IF deux `Teleprospecteur` tentent simultanément de poser un verrou sur la même fiche, THEN THE `PhoningQueueBuilder` SHALL garantir l'atomicité via `Cache::add()` de sorte qu'un seul verrou soit accordé ; le `Teleprospecteur` dont la tentative échoue passe silencieusement au contact suivant sans notification.
5. IF tous les contacts restants de la `File_D_Appels` d'un `Teleprospecteur` sont verrouillés par d'autres, THEN THE `PhoningQueueBuilder` SHALL retourner une file vide et laisser le `Teleprospecteur` gérer la situation.

### Requirement 2: Renouvellement du verrou actif

**User Story:** En tant que téléprospecteur, je veux que le verrou sur la fiche que je consulte soit maintenu tant que je suis actif, afin qu'une session longue ne libère pas accidentellement la fiche à un autre.

#### Acceptance Criteria

1. WHEN un `Teleprospecteur` est actif sur une fiche dans `PhoningWorkflow` depuis la moitié du TTL, THE `HasContactQueue` SHALL renouveler le `ContactLock` pour un nouveau TTL de 30 minutes.
2. WHEN `HasContactQueue` renouvelle un `ContactLock`, THE `PhoningQueueBuilder` SHALL vérifier que la `ContactReservation` appartient toujours au même `Teleprospecteur` avant d'effectuer le renouvellement.
3. IF la `ContactReservation` n'appartient plus au `Teleprospecteur` au moment du renouvellement, ou si la vérification échoue pour toute autre raison, THEN THE `HasContactQueue` SHALL notifier le `Teleprospecteur` que la fiche a été libérée et passer au contact suivant.

### Requirement 3: Libération systématique du verrou

**User Story:** En tant que téléprospecteur, je veux que le verrou sur une fiche soit systématiquement libéré dès que j'ai terminé de la traiter ou que je passe au contact suivant, afin que les autres téléprospecteurs puissent accéder à ce contact.

#### Acceptance Criteria

1. WHEN `HasSubmitResult` termine la soumission d'un résultat d'appel, THE `PhoningQueueBuilder` SHALL libérer le `ContactLock` de la fiche traitée via `releaseQueueReservationForUser()`.
2. WHEN `HasContactQueue` charge un nouveau contact via `loadNextContact()` et qu'un `ContactLock` était détenu sur le contact précédent par ce `Teleprospecteur`, THE `PhoningQueueBuilder` SHALL libérer ce verrou précédent ; si aucun verrou précédent n'était détenu, THE `PhoningQueueBuilder` SHALL ne rien faire.
3. WHEN `HasContactQueue` effectue un `skipCall()`, THE `PhoningQueueBuilder` SHALL libérer le `ContactLock` du contact ignoré.
4. WHEN la page `PhoningWorkflow` est détruite (lifecycle Livewire), THE `HasContactQueue` SHALL libérer le `ContactLock` du contact courant si un verrou est détenu ; si aucun verrou n'est détenu, THE `HasContactQueue` SHALL ne rien faire.
5. THE `PhoningQueueBuilder` SHALL ne libérer un `ContactLock` que si l'identifiant du `Teleprospecteur` demandeur correspond à celui enregistré dans la `ContactReservation`.

### Requirement 4: Filtrage des fiches verrouillées en cache

**User Story:** En tant que téléprospecteur, je veux que la validation de ma file filtre automatiquement les fiches déjà en cours de traitement par un collègue, afin d'éviter les doublons lors du rechargement de ma file.

#### Acceptance Criteria

1. WHEN `PhoningQueueBuilder::filterValidQueue()` est appelée avec un `userId`, THE `PhoningQueueBuilder` SHALL exclure de la file toute fiche dont le `ContactLock` est détenu par un `Teleprospecteur` différent de ce `userId`.
2. WHEN `PhoningQueueBuilder::filterValidQueue()` est appelée sans `userId`, THE `PhoningQueueBuilder` SHALL ne pas appliquer le filtrage par `ContactLock` et conserver le comportement actuel.
3. THE `PhoningQueueBuilder` SHALL accepter un paramètre `userId` optionnel dans `filterValidQueue()` pour permettre le filtrage des verrous appartenant à d'autres `Teleprospecteur`.

### Requirement 5: Exclusion des rappels non échus

**User Story:** En tant que téléprospecteur, je veux que les fiches dont le rappel est planifié dans le futur n'apparaissent pas dans ma file d'appels, afin de ne contacter ces prospects qu'au moment prévu.

#### Acceptance Criteria

1. WHEN `CampagnePhoning::buildQueueQuery()` construit la requête de la file, THE `CampagnePhoning` SHALL exclure les fiches de type `prospect` dont `rappel_planifie_at` est renseigné et supérieur à `now()`.
2. WHEN `PhoningQueueBuilder::filterValidQueue()` valide la file en cache, THE `PhoningQueueBuilder` SHALL exclure les fiches de type `prospect` dont `rappel_planifie_at` est renseigné et supérieur à `now()`.
3. WHEN la date/heure du `Rappel_Planifie_At` d'un `Prospect` est atteinte ou dépassée, THE `CampagnePhoning` SHALL inclure automatiquement ce `Prospect` dans la `File_D_Appels` lors de la prochaine construction de file.
4. THE `PhoningQueueBuilder` SHALL ne pas exclure les fiches dont `rappel_planifie_at` est null.
5. THE `PhoningQueueBuilder` SHALL ne pas exclure les fiches dont `rappel_planifie_at` est dans le passé, car elles sont priorisées en tête de file par `prioriserFile()`.

### Requirement 6: Construction de file sans rappels ni verrous étrangers

**User Story:** En tant que téléprospecteur, je veux que ma file initiale soit construite sans les fiches rappel non échues ni les fiches verrouillées par d'autres, afin de démarrer directement sur des contacts appelables.

#### Acceptance Criteria

1. WHEN `PhoningQueueBuilder::buildDefaultQueue()` est appelée, THE `PhoningQueueBuilder` SHALL déléguer l'exclusion des rappels non échus à `CampagnePhoning::buildQueueQuery()`.
2. WHEN `PhoningQueueBuilder::reserveQueueForUser()` traite la file construite, THE `PhoningQueueBuilder` SHALL exclure les fiches dont le `ContactLock` est détenu par un autre `Teleprospecteur`.
3. THE `PhoningQueueBuilder` SHALL appliquer les filtres dans l'ordre suivant : exclusion rappels non échus, puis exclusion fiches verrouillées par autrui, puis priorisation des rappels échus.

### Requirement 7: Observabilité des verrous

**User Story:** En tant que superviseur, je veux pouvoir identifier quelles fiches sont actuellement verrouillées et par qui, afin de diagnostiquer les situations de blocage.

#### Acceptance Criteria

1. WHEN un `ContactLock` est posé ou libéré, THE `PhoningQueueBuilder` SHALL émettre une entrée de log de niveau `debug` contenant le type de contact, l'identifiant de la fiche, l'identifiant du `Teleprospecteur` et l'action effectuée (`lock`, `renew`, `release`).
2. IF un `ContactLock` ne peut pas être posé car la fiche est déjà verrouillée par un autre `Teleprospecteur`, THEN THE `PhoningQueueBuilder` SHALL émettre une entrée de log de niveau `info` indiquant le conflit.

### Requirement 8: Priorisation des rappels échus

**User Story:** En tant que téléprospecteur, je veux que les fiches dont le rappel est échu continuent d'être priorisées en tête de file, afin de respecter mes engagements de rappel.

#### Acceptance Criteria

1. THE `PhoningQueueBuilder` SHALL continuer à placer en tête de `File_D_Appels` les fiches dont `rappel_planifie_at` est dans le passé ou le jour courant, conformément à la logique existante de `prioriserFile()`.
2. WHEN `PhoningQueueBuilder::filterValidQueue()` exclut un rappel non échu, THE `PhoningQueueBuilder` SHALL ne pas affecter le classement des autres fiches dans la file.
