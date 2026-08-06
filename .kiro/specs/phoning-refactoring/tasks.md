# Implementation Plan: Refactoring du Module Phoning

## Overview

Décomposer `PhoningWorkflow.php` (**1 566 lignes**, 78 méthodes) et `phoning-workflow.blade.php` (~3 000+ lignes)
en couches bien séparées : 5 Concerns Livewire, 4 Services PHP, 5 composants Blade anonymes
et 2 fichiers CSS dédiés — sans modifier aucun comportement visible ni casser les tests existants.

`PhoningBackOffice.php` (**345 lignes**) est également refactorisé vers ≤ 200 lignes,
et `phoning-back-office.blade.php` (**1 279 lignes**) vers ≤ 300 lignes.

Le plan suit l'ordre de migration recommandé dans le design : chaque étape doit être verte
avant de passer à la suivante.

---

## Tasks

- [x] 1. Créer PhoningQueueService et le Concern HasContactQueue
  - [x] 1.1 Créer `App\Services\Phoning\PhoningQueueService`
    - Implémenter `getQueueForUser(userId, campagneId)` : lecture cache → `filterValidQueue` ou `buildDefaultQueue`, puis `prioriserFile` → `reserveQueueForUser`
    - Implémenter `saveQueueForUser(userId, queue)` : stockage cache clé `phoning_queue_user_{userId}` TTL 86 400 s
    - Implémenter `clearQueueForUser(userId)` : suppression clé cache
    - Implémenter `search(query)` : délégation à `PhoningContactSearchService`, max 50 résultats
    - Implémenter `findByPhone(phone)` : délégation à `PhoningContactSearchService::findByPhone()`, retourne tableau de contacts correspondants
    - Lancer `InvalidArgumentException` si `userId ≤ 0` ou `campagneId ≤ 0`
    - Capturer toute exception interne et retourner `[]` au lieu de propager
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10_

  - [ ]* 1.2 Écrire le test property-based pour `getQueueForUser()` — toujours tableau non-null
    - **Property 6 : `getQueueForUser()` ne retourne jamais null**
    - **Validates: Requirements 7.5**

  - [ ]* 1.3 Écrire les tests unitaires de `PhoningQueueService`
    - `getQueueForUser_retourneFileCachee` : cache hit → `filterValidQueue` appelé
    - `getQueueForUser_construitFileParDefaut` : cache miss → `buildDefaultQueue`
    - `saveQueueForUser_stockeEnCache` : clé et TTL corrects
    - `clearQueueForUser_supprimeCleCache`
    - `search_retourneVide_siQueryVide`
    - `findByPhone_delegueAContactSearchService` : vérifie délégation correcte
    - _Requirements: 7.1–7.10_

  - [x] 1.4 Créer le trait `App\Filament\NsConseil\Concerns\HasContactQueue`
    - Déclarer les propriétés Livewire : `contactQueue`, `currentContact`, `currentCampagneId`, `campagneFiltreId`, `progress`, `total`, `completed`, `searchQuery`, `searchResults`, `showSearchResults`, `requestedContactId`, `requestedContactType`, `selectedContactId`, `selectedContactType`
    - Implémenter `loadQueue()` : déléguer à `PhoningQueueService::getQueueForUser()`, affecter `currentContact`
    - Implémenter `refreshQueue()` : reset `currentContact` puis `loadQueue()`
    - Implémenter `loadNextContact()` : `array_shift` + `PhoningContactResolver::resolveModel()`, récursion si null
    - Implémenter `updatedSearchQuery()` : skip si `< 2` chars, sinon `PhoningQueueService::search()`
    - Implémenter `selectSearchResult(id, type)` : dédoublonnage + `array_unshift` + `loadNextContact()`
    - Implémenter `clearSearch()`
    - Calculer `progress = round(completed / total × 100)` quand `total > 0`, sinon `0`
    - Implémenter `skipCall()` : déplace le premier élément de `contactQueue` en dernière position sans créer d'enregistrement `Appel` et sans modifier le modèle contact
    - Implémenter `ensureRequestedContactPriority()` : retire tout item correspondant à `(requestedContactId, requestedContactType)` puis insère à la position 0
    - Déclarer les propriétés : `requestedContactId`, `requestedContactType`
    - Implémenter `selectCampagne(campagneId)` : affecter `campagneFiltreId = campagneId` puis appeler `loadQueue()`
    - Implémenter `clearCampagne()` : affecter `campagneFiltreId = null` puis appeler `loadQueue()`
    - Implémenter `getCampagnesDisponibles()` : déléguer à `PhoningQueueService` pour retourner la liste des campagnes actives accessibles à l'utilisateur courant
    - Implémenter `getCampagneInfo()` : retourner tableau `{name, count, progress}` pour `campagneFiltreId`, ou null si non défini
    - Implémenter `getContactsRestantsCount()` : retourner le nombre total de contacts restants, scopé à `campagneFiltreId` si défini
    - Implémenter `getCallHistory()` : retourner les 15 derniers enregistrements `Appel` pour le contact courant, triés par `created_at` décroissant
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10, 2.11, 2.12, 2.13, 2.14, 2.15, 2.16_

  - [ ]* 1.5 Écrire le test property-based pour l'intégrité de la file
    - **Property 1 : chaque item a `id > 0` et `type ∈ {'prospect','artisan','partenaire','client','particulier'}`**
    - **Validates: Requirements 12.1**

  - [ ]* 1.6 Écrire le test property-based pour l'unicité de la file
    - **Property 2 : pas de doublon `(type, id)` dans `contactQueue`**
    - **Validates: Requirements 12.2**

  - [ ]* 1.7 Écrire le test property-based pour la progression cohérente
    - **Property 3 : `progress = round(completed / total × 100)` et `completed ≤ total`**
    - **Validates: Requirements 2.8, 12.3, 12.4**

  - [x] 1.8 Wirer `HasContactQueue` dans `PhoningWorkflow` en remplacement du code extrait
    - Ajouter `use HasContactQueue;` dans `PhoningWorkflow`, supprimer les propriétés/méthodes migrées
    - `PhoningWorkflow` fait actuellement **1 566 lignes** (78 méthodes) ; après extraction de `HasContactQueue` (~30 méthodes/propriétés), il restera encore `HasCallSession`, `HasStatusResult` et `HasEmailPreview` à extraire avant d'atteindre la cible ≤ 200 lignes
    - Ne pas considérer cette tâche comme terminée si `PhoningWorkflow` n'a pas délégué au moins la file, la navigation et la recherche à `HasContactQueue`
    - Vérifier que les tests `CampagnePhoningQueueTest`, `PhoningWorkflowContactsRestantsTest` passent toujours
    - _Requirements: 1.1, 2.1, 13.1, 13.6_

- [x] 2. Checkpoint — File d'appels (étape 1)
  - Lancer `php artisan test --filter="PhoningQueueService|CampagnePhoningQueueTest|PhoningQueueBuilderInterleaveTest|PhoningWorkflowContactsRestantsTest"` et vérifier passage complet.
  - Vérifier que `HasContactQueue` expose bien `skipCall()`, `getCampagnesDisponibles()`, `getCampagneInfo()`, `getContactsRestantsCount()`, `getCallHistory()`.
  - Vérifier que `PhoningQueueService::findByPhone()` délègue correctement à `PhoningContactSearchService`.
  - Demander confirmation à l'utilisateur avant de continuer.

- [x] 3. Créer le Concern HasCallSession
  - [x] 3.1 Créer le trait `App\Filament\NsConseil\Concerns\HasCallSession`
    - Déclarer les propriétés : `ringoverCallId`, `ringoverCallStartedAt`, `ringoverCallEndedAt`, `incomingCallMatches`, `incomingCallPhone`, `supervisedUserId`, `isSupervisorMode`
    - Implémenter `callNow()` : dispatch `ringover-call` avec le numéro du contact courant
    - Implémenter `updateRingoverCallLifecycle(callId, startedAt, endedAt)` avec attribut `#[On('ringover-call-lifecycle')]`
    - Implémenter `searchIncomingCallMatch(phone, targetType, targetId)` avec attribut `#[On('search-incoming-call')]`
    - Implémenter `selectSupervisedUser(userId)` : affecter `supervisedUserId`, `isSupervisorMode = true`, recharger file
    - Implémenter `resetToSelf()` : reset supervision, recharger file pour l'utilisateur authentifié
    - Gérer la réception de `callId = null` : reset des trois propriétés sans exception
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 14.6_

  - [x] 3.2 Wirer `HasCallSession` dans `PhoningWorkflow`
    - Ajouter `use HasCallSession;`, supprimer code migré
    - _Requirements: 1.1, 13.6_

- [x] 4. Créer PhoningResultService et le Concern HasStatusResult
  - [x] 4.1 Créer `App\Services\Phoning\PhoningResultService`
    - Implémenter `applyResult(contact, type, statut, fields)` dans une transaction DB : update statut + création `Appel`
    - Vérifier la réservation cache avant toute écriture ; lancer `AuthorizationException` si absente
    - Retourner l'instance `Appel` avec `id` peuplé
    - Implémenter `shouldPreviewEmail(statut, contactType)` : `true` ssi `statut ∈ {'rdv','bloc','ncse_50','cse_hz'}`
    - Implémenter `getStatutLabel(statut, contactType)` : libellé `StatutPhoning` ou fallback = statut brut
    - Implémenter `isCommentRequired(statut, contactType)` : lecture champ `note_obligatoire` sur `StatutPhoning`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_

  - [ ]* 4.2 Écrire les tests unitaires de `PhoningResultService`
    - `applyResult_creeEnregistrementAppel` : un `Appel` créé avec bon statut
    - `applyResult_rollbackSiException` : transaction annulée en cas d'erreur
    - `shouldPreviewEmail_vraiPourStatutsEmail` : rdv/bloc/ncse_50/cse_hz → true
    - `shouldPreviewEmail_fauxPourStatutsSansEmail` : std_nr → false
    - `isCommentRequired_retourneChampBDD`
    - _Requirements: 8.1–8.8_

  - [ ]* 4.3 Écrire le test property-based pour `shouldPreviewEmail()` — total et booléen
    - **Property 7 : `shouldPreviewEmail()` retourne un booléen pour tout code statut**
    - **Validates: Requirements 8.5, 8.6**

  - [x] 4.4.a Créer `App\Services\Phoning\PhoningContactUpdateService`
    - Implémenter `updateContact(contact, type, statut, fields)` : dispatch vers la méthode appropriée selon `type` (`updateProspect`, `updateArtisan`, `updatePartenaire`, `updateParticulier`, `updateClient`)
    - Implémenter `updateProspect(prospect, statut, fields)` : mise à jour statut pipeline, sauvegarde interlocuteur, logique STD_NR/tentatives, appel à `appliquerRappelProspect()` si applicable, envoi mail via `ProspectionMailService`
    - Implémenter `updateArtisan(artisan, statut, fields)` : mise à jour statut et champs artisan selon le statut phoning
    - Implémenter `updatePartenaire(partenaire, statut, fields)` : mise à jour statut et champs partenaire selon le statut phoning
    - Implémenter `updateParticulier(particulier, statut, fields)` : mise à jour statut et champs particulier selon le statut phoning
    - Implémenter `updateClient(client, statut, fields)` : mise à jour statut et champs client selon le statut phoning
    - Implémenter `creerRendezVous(prospect, fields)` : crée un enregistrement `RendezVous` si `rappel_date` est fourni dans `fields`
    - Implémenter `appliquerRappelProspect(prospect, fields)` : programme `rappel_planifie_at` sur le prospect
    - Implémenter `splitFullName(fullName)` : découpe un nom complet en tableau `['prenom' => ..., 'nom' => ...]`
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7, 16.8, 16.9, 16.10, 16.11_

  - [ ]* 4.4.b Écrire les tests unitaires de `PhoningContactUpdateService`
    - `updateContact_dispatchVersBonneMethodeSelonType` : vérifie le dispatch pour chaque type
    - `creerRendezVous_neCreePasDeRdvSiRappelDateAbsent` : `rappel_date` vide → pas de création
    - `appliquerRappelProspect_programmeRappelPlanifieAt`
    - `splitFullName_decoupe_correctement` : "Jean Dupont" → `{prenom: 'Jean', nom: 'Dupont'}`
    - _Requirements: 16.1–16.11_


  - [x] 4.5 Créer le trait `App\Filament\NsConseil\Concerns\HasStatusResult`
    - Déclarer les propriétés : `statut_resultat`, `commentaires`, `rappel_date`, `rappel_heure`, `nom_interlocuteur_standard`, `creneaux_permanence_cse`, `email_general_standard`, `interlocuteur_nom`, `interlocuteur_fonction`, `interlocuteur_telephone`, `interlocuteur_email`, `interlocuteur_add_nom`, `interlocuteur_add_fonction`, `interlocuteur_add_telephone`, `interlocuteur_add_email`, `lieu_rdv`, `invitation_agenda_envoyee`, `enregistrement_appel_joint`, `enregistrement_raison`, `besoins_exprimes`, `objections_soulevees`, `points_attention_rdv`, `presence_cse`, `jour_dispo_appel`, `lastAppelId`
    - Implémenter `submitResult()` selon l'algorithme formel du design (validation → preview → persistance via `PhoningContactUpdateService::updateContact()` + `PhoningResultService::applyResult()` → dispatch jobs → notification → avancer file)
    - L'appel à `applyContactUpdate()` doit déléguer à `PhoningContactUpdateService::updateContact()` plutôt qu'un `match` inline sur `contactType`
    - Implémenter `validateResultForm()` : règles commentaires (obligatoire + 5–2000 chars), format RFC 5321 emails
    - Implémenter `updatedStatutResultat()` : réévaluation `commentaireRequis`, mise à jour état UI sans persistance, appel à `resetEmailPreviewState()`
    - Implémenter `enregistrerAppel()`, `applyContactUpdate()`, `checkCampagneCompletion()`, `dispatchFicheGenerationJob()`
    - Implémenter `saveInterlocuteur()` : persiste les champs interlocuteur (`nom`, `fonction`, `telephone`, `email` et les variantes `interlocuteur_add_*`) sur le modèle `Prospect` courant sans créer d'enregistrement `Appel` et sans soumettre le résultat complet
    - Implémenter `getSelectedStatus()` : retourner le modèle `StatutPhoning` correspondant à `statut_resultat` pour le `contactType` courant, ou null si vide
    - Implémenter `getStatutsPhoning()` : retourner la liste des `StatutPhoning` formatés pour la vue (champs : `code`, `libelle`, `icone`, `couleur`, `groupe`, `pipeline_label`)
    - Implémenter `getStatutsPhoningGroupes()` : retourner les statuts groupés par cas (regroupement CSE v2)
    - Implémenter `getPipelineTransitionPreview()` : retourner un aperçu de la transition pipeline qui sera appliquée pour `statut_resultat` courant, ou null si aucune transition définie
    - Implémenter `compterTentativesNonAbouties()` : retourner le nombre de tentatives NRP/std_nr pour le contact courant dans la session de campagne courante
    - Implémenter `getTentativesAppel()` : exposer le même compteur que `compterTentativesNonAbouties()` comme propriété calculée publique pour la vue
    - Implémenter `getRappelStatusCodes()` : retourner le tableau des codes `StatutPhoning` qui déclenchent un rappel
    - Conserver `getStatusValidationCodes()` public pour rétrocompatibilité (`PhoningWorkflowStatusesTest`)
    - Capturer `Throwable` de `FicheGenerationService::genererAutoParStatut()` sans re-throw
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 5.11, 5.12, 5.13, 5.14, 5.15, 5.16, 5.17, 5.18, 13.1, 14.1, 14.4_

  - [ ]* 4.6 Écrire le test property-based pour la résilience sur exception de persistance
    - **Property 9 : `contactQueue` reste inchangée si `applyResult()` lève une exception**
    - **Validates: Requirements 14.1**

  - [x] 4.7 Wirer `HasStatusResult` dans `PhoningWorkflow`
    - Ajouter `use HasStatusResult;`, supprimer code migré
    - Vérifier que `PhoningWorkflowStatusesTest` passe (getStatusValidationCodes() reste accessible)
    - _Requirements: 1.1, 5.10, 13.1, 13.6_

- [x] 5. Checkpoint — Persistance résultats (étapes 3–4)
  - Lancer `php artisan test --filter="PhoningResultService|PhoningContactUpdateService|PhoningWorkflowStatusesTest"` et vérifier passage complet.
  - Vérifier que `HasStatusResult` expose bien `saveInterlocuteur()`, `getSelectedStatus()`, `getStatutsPhoning()`, `getStatutsPhoningGroupes()`, `getPipelineTransitionPreview()`, `compterTentativesNonAbouties()`, `getTentativesAppel()`, `getRappelStatusCodes()`.
  - Vérifier que `applyContactUpdate()` délègue à `PhoningContactUpdateService::updateContact()` et n'utilise pas de `match` inline.
  - Vérifier que `PhoningWorkflow.php` a réduit significativement son nombre de lignes après les extractions 1, 3 et 4.
  - Demander confirmation à l'utilisateur avant de continuer.

- [x] 6. Créer PhoningEmailPreviewService et le Concern HasEmailPreview
  - [x] 6.1 Créer `App\Services\Phoning\PhoningEmailPreviewService`
    - Implémenter `buildPayload(statut, contact, formFields)` : crée le Mailable, résout destinataire, extrait sujet/corps
    - Retourner `null` si statut hors de l'ensemble déclencheur email
    - Implémenter `makeMailable(statut, contact, fields)` : factory vers les Mailables existants
    - Implémenter `resolveRecipient(statut, contact)` : email valide ou null
    - Postcondition : si résultat non null, `subject` non vide et `strip_tags(body)` non vide
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

  - [ ]* 6.2 Écrire les tests unitaires de `PhoningEmailPreviewService`
    - `buildPayload_retourneNullSiStatutSansEmail` : std_nr → null
    - `buildPayload_retournePayloadComplet` : rdv → `{recipient, subject, body}` non vides
    - `resolveRecipient_retourneEmailOuNull`
    - _Requirements: 9.1–9.6_

  - [ ]* 6.3 Écrire le test property-based pour `buildPayload()` — complétude du payload
    - **Property 8 : payload non-null ⟹ `subject` non vide et `strip_tags(body)` non vide ; statut non-déclencheur ⟹ null**
    - **Validates: Requirements 9.2, 9.3, 9.4**

  - [x] 6.4 Créer le trait `App\Filament\NsConseil\Concerns\HasEmailPreview`
    - Déclarer les propriétés : `showEmailPreview`, `emailPreviewConfirmed`, `emailPreviewRecipient`, `emailPreviewSubject`, `emailPreviewBody`, `emailPreviewOriginalSubject`, `emailPreviewOriginalBody`
    - Implémenter `openEmailPreview()` : déléguer à `PhoningEmailPreviewService::buildPayload()`, set `showEmailPreview = true`
    - Implémenter `confirmEmailPreview()` : `emailPreviewConfirmed = true`, `showEmailPreview = false`, puis appeler `submitResult()`
    - Implémenter `cancelEmailPreview()` : `showEmailPreview = false`, `emailPreviewConfirmed = false`
    - Implémenter `syncEmailPreviewContent(subject, body, recipient)` : mise à jour propriétés live sans toucher aux originaux
    - Implémenter `resetEmailPreviewState()` : reset toutes propriétés preview
    - Maintenir invariant : `emailPreviewConfirmed = true ⟹ showEmailPreview = false`
    - Conserver `getEmailPreviewPayload()` public pour rétrocompatibilité (`PhoningWorkflowPreviewTest`)
    - Implémenter `getPreviewMailableForStatut(statut)` : factory retournant le Mailable approprié pour `{'rdv','bloc','ncse_50','cse_hz'}` — délègue vers `buildPreviewRdvMailable()`, `PriseContactBlocMail`, `ContactSansCSEMail`, `GenericProspectionMail` — retourne null pour tout autre statut
    - Implémenter `buildPreviewRdvMailable(prospect)` : construit un `ConfirmationRdvProspectMail` avec un `RendezVous` temporaire hydraté depuis les champs courants (`rappel_date`, `rappel_heure`, `lieu_rdv`, `interlocuteur_*`) ; retourne null si `rappel_date` est vide
    - Implémenter `resolvePreviewRecipient(statut)` : retourne `interlocuteur_email` si non vide, sinon `fallback_interlocuteur_email`, sinon `localPreviewFallbackEmail()` en environnement non-production
    - Implémenter `buildProspectionMailContext(?rdv)` : retourner le tableau de contexte requis par `ProspectionMailService`, en fusionnant les champs du formulaire courant comme overrides pour le preview
    - Implémenter `localPreviewFallbackEmail()` : retourner l'email de fallback local pour le preview en non-production
    - Implémenter `getMailableSubject(mailable)` : extraire le sujet du Mailable via `invokeProtectedMethod`
    - Implémenter `getMailableBody(mailable)` : extraire le corps HTML du Mailable via `invokeProtectedMethod`
    - Implémenter `invokeProtectedMethod(object, method, args)` : utilitaire de réflexion pour invoquer des méthodes protégées (usage interne preview uniquement)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10, 4.11, 4.12, 4.13, 13.2, 14.3_

  - [ ]* 6.5 Écrire le test property-based pour l'invariant preview conditionnelle
    - **Property 4 : `emailPreviewConfirmed = true ⟹ showEmailPreview = false` pour toute séquence confirm/cancel/open**
    - **Validates: Requirements 4.7**

  - [x] 6.6 Wirer `HasEmailPreview` dans `PhoningWorkflow`
    - Ajouter `use HasEmailPreview;`, supprimer code migré
    - Vérifier que `PhoningWorkflowPreviewTest` passe (getEmailPreviewPayload() reste accessible)
    - _Requirements: 1.1, 13.2, 13.6_

- [x] 7. Créer le Concern HasQueueManagement et refactorer PhoningBackOffice
  - [x] 7.1 Créer le trait `App\Filament\NsConseil\Concerns\HasQueueManagement`
    - Déclarer les propriétés : `prospectList`, `selectedIds`, `filterStatut`, `filterDept`, `filterRappelOnly`
    - Implémenter `loadProspects()` : peuple `prospectList` avec la file formatée de l'utilisateur sélectionné
    - Implémenter `reorderFromDrag(orderedIds)` : réordonner `prospectList` selon `orderedIds` + `saveQueue()`
    - Implémenter `moveUp`, `moveDown`, `moveToTop`, `moveToBottom` avec gestion des bornes
    - Implémenter `moveSelectedToTop()` : déplacer en tête en conservant l'ordre relatif
    - Implémenter `removeSelected()` : supprimer les IDs sélectionnés + `saveQueue()`
    - Implémenter `resetOrder()` : vider cache + `loadProspects()`
    - Implémenter `applyFilters()` / `clearFilters()` : filtrage en mémoire sans persistance cache
    - Implémenter `saveQueue()`, `formatProspect()`, `applyFiltersToCollection()`, `findIndex()`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 6.10, 6.11, 6.12_

  - [ ]* 7.2 Écrire le test property-based pour la réorganisation drag-and-drop
    - **Property 11 : `reorderFromDrag(orderedIds)` est une permutation de la liste originale**
    - **Validates: Requirements 6.4**

  - [x] 7.3 Wirer `HasQueueManagement` dans `PhoningBackOffice`
    - Ajouter `use HasQueueManagement;`, supprimer code migré
    - Vérifier que `PhoningBackOffice` reste ≤ 200 lignes (point de départ : 345 lignes)
    - _Requirements: 1.2, 13.4, 13.5, 13.6_

- [x] 8. Checkpoint — Concerns et Services complets (étapes 1–7)
  - Lancer `php artisan test` (suite complète) et vérifier zéro régression : `PhoningWorkflowPreviewTest`, `PhoningWorkflowStatusesTest`, `CampagnePhoningQueueTest`, `PhoningQueueBuilderInterleaveTest`, `PhoningWorkflowContactsRestantsTest`.
  - Vérifier les limites de lignes : `PhoningWorkflow ≤ 200`, `PhoningBackOffice ≤ 200`.
  - Vérifier que `PhoningContactUpdateService` existe et expose `updateContact()`, `creerRendezVous()`, `appliquerRappelProspect()`.
  - Vérifier que `HasEmailPreview` expose les méthodes `getPreviewMailableForStatut()`, `buildPreviewRdvMailable()`, `resolvePreviewRecipient()`, `buildProspectionMailContext()`.
  - Demander confirmation à l'utilisateur avant de continuer.

- [x] 9. Découper phoning-workflow.blade.php en composants Blade anonymes
  - [x] 9.1 Créer `resources/views/components/phoning/contact-panel.blade.php`
    - Déclarer `@props(['contact', 'contactType', 'queueCount', 'progress', 'isSupervisorMode'])`
    - Extraire la carte identité entreprise, la barre de contact et la progression depuis le blade monolithique
    - Vérifier rendu sans exception avec un tableau de props minimal
    - _Requirements: 10.1, 10.6, 1.7_

  - [x] 9.2 Créer `resources/views/components/phoning/status-panel.blade.php`
    - Déclarer `@props(['statuts', 'selectedStatut', 'commentaires', 'rappelDate', 'rappelHeure'])`
    - Extraire les onglets cas, chips statuts et rappel box
    - _Requirements: 10.2, 10.6, 1.7_

  - [x] 9.3 Créer `resources/views/components/phoning/email-preview.blade.php`
    - Déclarer `@props(['emailPreviewSubject', 'emailPreviewBody', 'emailPreviewRecipient'])`
    - Extraire la modal d'aperçu email éditable avec bindings `wire:model.live`
    - _Requirements: 10.3, 10.6, 1.7_

  - [x] 9.4 Créer `resources/views/components/phoning/ringover-widget.blade.php`
    - Déclarer `@props(['phone', 'nrCount', 'maxNr', 'callId'])`
    - Extraire l'iframe Ringover, la boîte NR et les infos d'appel entrant
    - _Requirements: 10.4, 10.6, 1.7_

  - [x] 9.5 Mettre à jour `phoning-workflow.blade.php` pour utiliser les composants
    - Remplacer le HTML inline par `<x-phoning::contact-panel ...>`, `<x-phoning::status-panel ...>`, `<x-phoning::email-preview ...>`, `<x-phoning::ringover-widget ...>`
    - Vérifier que le template orchestrateur reste ≤ 300 lignes (point de départ ~3 000+ lignes)
    - _Requirements: 1.3, 10.7_

- [x] 10. Découper phoning-back-office.blade.php et créer queue-table
  - [x] 10.1 Créer `resources/views/components/phoning/queue-table.blade.php`
    - Déclarer `@props(['prospects', 'selectedIds'])`
    - Extraire le tableau triable SortableJS avec les boutons de réorganisation
    - _Requirements: 10.5, 10.6, 1.7_

  - [x] 10.2 Mettre à jour `phoning-back-office.blade.php` pour utiliser `x-phoning::queue-table`
    - Remplacer le HTML inline par `<x-phoning::queue-table :prospects="$prospectList" wire:model="selectedIds" />`
    - Vérifier que le template orchestrateur reste ≤ 300 lignes (point de départ 1 279 lignes)
    - _Requirements: 1.4, 10.8_

- [ ] 11. Extraire les styles CSS en fichiers dédiés compilés par Vite
  - [x] 11.1 Créer `resources/css/phoning-workflow.css`
    - Déplacer tous les blocs `<style>` inline de `phoning-workflow.blade.php` vers ce fichier (~850 lignes de classes `.pw-*`)
    - Ajouter `@vite('resources/css/phoning-workflow.css')` dans le template orchestrateur
    - Supprimer tous les blocs `<style>` restants du template
    - _Requirements: 11.1, 11.3, 11.5, 11.6_

  - [x] 11.2 Créer `resources/css/phoning-back-office.css`
    - Déplacer tous les blocs `<style>` inline de `phoning-back-office.blade.php` vers ce fichier (~958 lignes de classes `.pbo-*`)
    - Ajouter `@vite('resources/css/phoning-back-office.css')` dans le template orchestrateur
    - Supprimer tous les blocs `<style>` restants du template
    - _Requirements: 11.2, 11.4, 11.5, 11.6_

  - [x] 11.3 Déclarer les nouveaux points d'entrée CSS dans `vite.config.js`
    - Ajouter `resources/css/phoning-workflow.css` et `resources/css/phoning-back-office.css` au tableau `input`
    - Lancer `npm run build` pour vérifier compilation sans erreur
    - _Requirements: 11.5_

- [x] 12. Nettoyage du code mort et vérification des limites de lignes
  - [x] 12.1 Supprimer le code mort dans `PhoningWorkflow.php` et `PhoningBackOffice.php`
    - Supprimer propriétés, méthodes et blocs commentés désormais entièrement couverts par les Concerns
    - Vérifier `PhoningWorkflow ≤ 200 lignes` (depuis 1 566 lignes) et `PhoningBackOffice ≤ 200 lignes` (depuis 345 lignes)
    - _Requirements: 1.1, 1.2_

  - [x] 12.2 Vérifier les limites de lignes de tous les fichiers créés
    - Chaque Concern PHP ≤ 300 lignes, chaque Service PHP ≤ 200 lignes, chaque composant Blade ≤ 300 lignes
    - `phoning-workflow.blade.php ≤ 300 lignes` (depuis ~3 000+ lignes), `phoning-back-office.blade.php ≤ 300 lignes` (depuis 1 279 lignes)
    - _Requirements: 1.5, 1.6, 1.7_

  - [x] 12.3 Écrire les tests d'intégration de rétrocompatibilité
    - `PhoningWorkflow::mount()` sans exception si file vide
    - `PhoningBackOffice::loadProspects()` retourne `[]` sans téléprospecteur sélectionné
    - Rendu sans erreur du composant `contact-panel` avec props minimaux
    - _Requirements: 13.3, 13.4, 13.6, 15.4_

  - [ ]* 12.4 Écrire le test property-based pour l'invariant de réservation cache
    - **Property 10 : chaque contact de la file active a une clé `phoning_queue_reservation_{type}_{id}` dont la valeur est `userId`**
    - **Validates: Requirements 7.6**

  - [ ]* 12.5 Écrire le test property-based pour la validité du résultat avant persistance
    - **Property 5 : `statut_resultat ∈ StatutPhoning::pluck('code')` avant tout appel à `applyResult()`**
    - **Validates: Requirements 8.2, 8.3**

- [-] 13. Checkpoint final — Suite de tests complète (étape 12)
  - Lancer `php artisan test` (suite complète) et vérifier zéro échec.
  - Vérifier que tous les fichiers respectent leurs limites de lignes définies.
  - Vérifier la liste complète des tests préexistants : `PhoningWorkflowPreviewTest`, `PhoningWorkflowStatusesTest`, `CampagnePhoningQueueTest`, `PhoningQueueBuilderInterleaveTest`, `PhoningWorkflowContactsRestantsTest` — tous doivent passer sans modification des fichiers de test.
  - Demander confirmation à l'utilisateur avant de livrer.


---

## Notes

- Les tâches marquées `*` sont optionnelles et peuvent être omises pour un MVP rapide
- Chaque tâche référence les exigences spécifiques pour la traçabilité
- Les checkpoints garantissent une validation incrémentale à chaque étape critique
- Les tests property-based valident les propriétés d'exactitude universelles définies dans le design
- Les tests unitaires valident les exemples spécifiques et les cas limites
- L'ordre des tâches suit le plan de migration recommandé dans le design (étapes 1–10)
- `PhoningWorkflow.php` fait **1 566 lignes** au départ (pas ~600) — prévoir un refactoring en plusieurs passes successives correspondant à l'extraction de chaque Concern
- `PhoningContactUpdateService` est un nouveau service (Requirement 16) qui soulage `PhoningResultService` et `HasStatusResult` qui deviendraient sinon trop longs
- `HasEmailPreview` porte maintenant les méthodes de factory Mailable (`getPreviewMailableForStatut`, `buildPreviewRdvMailable`, `resolvePreviewRecipient`, `buildProspectionMailContext`) extraites directement de `PhoningWorkflow`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "1.4"] },
    { "id": 2, "tasks": ["1.5", "1.6", "1.7", "1.8", "3.1", "4.1"] },
    { "id": 3, "tasks": ["3.2", "4.2", "4.3", "4.4.a", "6.1"] },
    { "id": 4, "tasks": ["4.4.b", "4.5", "6.2", "6.3", "7.1"] },
    { "id": 5, "tasks": ["4.6", "4.7", "6.4", "7.2"] },
    { "id": 6, "tasks": ["6.5", "6.6", "7.3"] },
    { "id": 7, "tasks": ["9.1", "9.2", "9.3", "9.4", "10.1"] },
    { "id": 8, "tasks": ["9.5", "10.2"] },
    { "id": 9, "tasks": ["11.1", "11.2"] },
    { "id": 10, "tasks": ["11.3", "12.1"] },
    { "id": 11, "tasks": ["12.2", "12.3", "12.4", "12.5"] }
  ]
}
```
