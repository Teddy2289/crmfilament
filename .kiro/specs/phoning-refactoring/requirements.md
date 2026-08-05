# Requirements Document

## Introduction

Ce document décrit les exigences fonctionnelles et non-fonctionnelles du refactoring du
module phoning (prospection téléphonique CSE). Le module actuel est concentré dans des
fichiers critiquement volumineux — `PhoningWorkflow.php` (**1 566 lignes**),
`phoning-workflow.blade.php` (~3 000+ lignes de HTML, CSS inline et Alpine.js),
`PhoningBackOffice.php` (**345 lignes**) et `phoning-back-office.blade.php` (**1 279 lignes**) —
qui nuisent à la maintenabilité et à la testabilité.

Le refactoring extrait la logique dans des Concerns Livewire, des Services PHP, des
composants Blade anonymes et des feuilles CSS dédiées, sans modifier aucun comportement
visible ni casser les tests existants.

---

## Glossary

- **PhoningWorkflow** : Page Livewire principale du module phoning, après refactoring ≤ 200 lignes
- **PhoningBackOffice** : Page Livewire du back-office superviseur, après refactoring ≤ 200 lignes
- **Concern** : Trait PHP Livewire encapsulant une responsabilité métier cohérente
- **PhoningQueueService** : Service PHP orchestrant la file d'appels (cache + builder)
- **PhoningResultService** : Service PHP gérant la logique de résultat d'appel (statut, commentaire, email preview)
- **PhoningContactUpdateService** : Service PHP gérant la mise à jour des modèles contact par type (updateProspect, updateArtisan, etc.)
- **PhoningEmailPreviewService** : Service PHP construisant les payloads d'aperçu email
- **PhoningQueueBuilder** : Service existant qui construit et priorise la file d'appels
- **HasContactQueue** : Concern gérant la file d'appels, la navigation, la recherche et la gestion des campagnes
- **HasCallSession** : Concern gérant la session d'appel Ringover et la supervision
- **HasEmailPreview** : Concern gérant l'aperçu et la confirmation d'envoi des emails
- **HasStatusResult** : Concern gérant la validation et la persistance du résultat d'appel
- **HasQueueManagement** : Concern gérant la réorganisation de file côté back-office
- **Appel** : Enregistrement Eloquent créé à chaque résultat d'appel soumis
- **StatutPhoning** : Code métier de résultat d'appel (rdv, bloc, std_nr, ncse_50, cse_hz, etc.)
- **contactQueue** : Tableau Livewire ordonné des contacts à appeler pour l'utilisateur courant
- **Prospect** : Modèle Eloquent de type contact principal dans le système
- **contactType** : Discriminateur de type de contact ('prospect', 'artisan', 'partenaire', 'client', 'particulier')
- **requestedContactId** : Propriété Livewire publique stockant l'id du contact demandé via paramètre URL
- **requestedContactType** : Propriété Livewire publique stockant le type du contact demandé via paramètre URL

---

## Requirements

### Requirement 1: Contraintes de taille des fichiers

**User Story:** En tant que développeur, je veux que chaque fichier PHP de présentation
et chaque template Blade reste sous un seuil de lignes défini, afin de maintenir une base
de code lisible et facilement maintenable.

#### Acceptance Criteria

1. THE PhoningWorkflow SHALL contain no more than 200 lines of PHP code after refactoring (point de départ : 1 566 lignes)
2. THE PhoningBackOffice SHALL contain no more than 200 lines of PHP code after refactoring (point de départ : 345 lignes)
3. THE phoning-workflow.blade.php orchestrator template SHALL contain no more than 300 lines after refactoring
4. THE phoning-back-office.blade.php orchestrator template SHALL contain no more than 300 lines after refactoring (point de départ : 1 279 lignes)
5. WHEN a Concern PHP file is created, THE Concern SHALL contain no more than 300 lines of PHP code; WHEN a Concern cannot be kept under 300 lines due to the volume of methods it must expose, THE Developer SHALL extract helper logic to a dedicated Service before committing
6. WHEN a Service PHP file is created, THE Service SHALL contain no more than 200 lines of PHP code
7. WHEN a Blade component file is created under components/phoning/, THE Blade_Component SHALL contain no more than 300 lines

---

### Requirement 2: Extraction du Concern HasContactQueue

**User Story:** En tant que développeur, je veux extraire la logique de gestion de la
file d'appels dans un Concern dédié, afin d'isoler cette responsabilité et de la rendre
testable indépendamment.

#### Acceptance Criteria

1. THE HasContactQueue SHALL be created as a PHP trait in the namespace App\Filament\NsConseil\Concerns
2. THE HasContactQueue SHALL expose the public Livewire properties: contactQueue, currentContact, currentCampagneId, campagneFiltreId, progress, total, completed, searchQuery, searchResults, showSearchResults, requestedContactId, requestedContactType, selectedContactId, selectedContactType
3. WHEN loadQueue() is called, THE HasContactQueue SHALL delegate queue retrieval to PhoningQueueService::getQueueForUser() and set currentContact to the resolved first entry or null if the returned array is empty
4. WHEN refreshQueue() is called, THE HasContactQueue SHALL set currentContact to null, then call loadQueue() to reload the queue from scratch
5. WHEN loadNextContact() is called and contactQueue is non-empty, THE HasContactQueue SHALL remove the first entry from contactQueue via array_shift, resolve its model via PhoningContactResolver::resolveModel(), and assign the result to currentContact; WHEN resolveModel() returns null, THE HasContactQueue SHALL call loadNextContact() recursively with the remaining queue
6. WHEN updatedSearchQuery() is triggered and searchQuery has fewer than 2 characters, THE HasContactQueue SHALL set searchResults to an empty array and showSearchResults to false without calling any service; WHEN searchQuery has 2 or more characters, THE HasContactQueue SHALL populate searchResults within 300ms without blocking the Livewire lifecycle
7. WHEN selectSearchResult(id, type) is called, THE HasContactQueue SHALL remove any existing entry with the same (type, id) pair from contactQueue, prepend the selected contact to the front of contactQueue, and call loadNextContact() to set currentContact
8. WHEN total equals 0, THE HasContactQueue SHALL set progress to 0; WHEN total is greater than 0, THE HasContactQueue SHALL set progress to round(completed / total × 100) whenever completed or total changes
9. WHEN skipCall() is called and contactQueue is non-empty, THE HasContactQueue SHALL move the first entry of contactQueue to the last position without creating an Appel record and without modifying any contact model
10. WHEN ensureRequestedContactPriority() is called and requestedContactId is non-null, THE HasContactQueue SHALL remove any existing entry matching (requestedContactId, requestedContactType) from contactQueue and prepend a new entry for that contact at position 0
11. WHEN selectCampagne(campagneId) is called with a valid campagneId, THE HasContactQueue SHALL set campagneFiltreId to campagneId and call loadQueue() to rebuild the queue restricted to that campaign
12. WHEN clearCampagne() is called, THE HasContactQueue SHALL set campagneFiltreId to null and call loadQueue() to rebuild the queue across all available campaigns
13. THE HasContactQueue::getCampagnesDisponibles() SHALL return the list of active campaigns accessible to the current user, as returned by PhoningQueueService
14. THE HasContactQueue::getCampagneInfo() SHALL return an array with stats (name, count, progress) for the campaign identified by campagneFiltreId, or null if campagneFiltreId is null
15. THE HasContactQueue::getContactsRestantsCount() SHALL return the total count of remaining callable contacts, scoped to campagneFiltreId if set, or across all campaigns otherwise
16. THE HasContactQueue::getCallHistory() SHALL return the last 15 Appel records for the current contact, ordered by created_at descending

---

### Requirement 3: Extraction du Concern HasCallSession

**User Story:** En tant que développeur, je veux extraire la logique de session d'appel
Ringover dans un Concern dédié, afin d'isoler la gestion du cycle de vie des appels et
du mode supervision.

#### Acceptance Criteria

1. THE HasCallSession SHALL be created as a PHP trait in the namespace App\Filament\NsConseil\Concerns
2. THE HasCallSession SHALL expose the public Livewire properties: ringoverCallId, ringoverCallStartedAt, ringoverCallEndedAt, incomingCallMatches, incomingCallPhone, supervisedUserId, isSupervisorMode
3. WHEN callNow() is called, THE HasCallSession SHALL dispatch the Livewire event 'ringover-call' with the current contact's telephone number
4. WHEN the Livewire event 'ringover-call-lifecycle' is received, THE HasCallSession SHALL update ringoverCallId, ringoverCallStartedAt, and ringoverCallEndedAt with the provided values
5. WHEN the Livewire event 'search-incoming-call' is received with a phone number, THE HasCallSession SHALL populate incomingCallMatches with contacts matching that phone number
6. WHEN selectSupervisedUser(userId) is called, THE HasCallSession SHALL set supervisedUserId and isSupervisorMode to true, then reload the queue for the supervised user
7. WHEN resetToSelf() is called, THE HasCallSession SHALL set isSupervisorMode to false, set supervisedUserId to null, and reload the queue for the authenticated user

---

### Requirement 4: Extraction du Concern HasEmailPreview

**User Story:** En tant que développeur, je veux extraire la logique d'aperçu email dans
un Concern dédié, afin d'isoler le cycle de vie modal preview/confirmation avant envoi.

#### Acceptance Criteria

1. THE HasEmailPreview SHALL be created as a PHP trait in the namespace App\Filament\NsConseil\Concerns
2. THE HasEmailPreview SHALL expose the public Livewire properties: showEmailPreview, emailPreviewConfirmed, emailPreviewRecipient, emailPreviewSubject, emailPreviewBody, emailPreviewOriginalSubject, emailPreviewOriginalBody
3. WHEN openEmailPreview() is called, THE HasEmailPreview SHALL delegate payload construction to PhoningEmailPreviewService::buildPayload() and set showEmailPreview to true
4. WHEN confirmEmailPreview() is called, THE HasEmailPreview SHALL set emailPreviewConfirmed to true, set showEmailPreview to false, then call submitResult() to proceed with persistence
5. WHEN cancelEmailPreview() is called, THE HasEmailPreview SHALL set showEmailPreview to false and emailPreviewConfirmed to false without persisting any result
6. WHEN syncEmailPreviewContent(subject, body, recipient) is called, THE HasEmailPreview SHALL update the live preview properties without affecting the original subject and body
7. IF emailPreviewConfirmed equals true, THEN THE HasEmailPreview SHALL ensure showEmailPreview equals false
8. WHEN resetEmailPreviewState() is called, THE HasEmailPreview SHALL reset all preview properties to their default empty/false values
9. WHEN updatedStatutResultat() is triggered, THE HasEmailPreview SHALL call resetEmailPreviewState() to clear any previously opened preview for the previous statut
10. THE HasEmailPreview::getPreviewMailableForStatut(statut) SHALL return the appropriate Mailable instance for statuts in {'rdv', 'bloc', 'ncse_50', 'cse_hz'} by delegating to the factory methods buildPreviewRdvMailable(), PriseContactBlocMail, ContactSansCSEMail, and GenericProspectionMail respectively, and SHALL return null for any other statut
11. THE HasEmailPreview::buildPreviewRdvMailable(prospect) SHALL return a ConfirmationRdvProspectMail built with a temporary RendezVous hydrated from the current form fields (rappel_date, rappel_heure, lieu_rdv, interlocuteur_*); WHEN rappel_date is empty, THE HasEmailPreview SHALL return null
12. THE HasEmailPreview::resolvePreviewRecipient(statut) SHALL return the interlocuteur_email of the current prospect when non-empty, falling back to fallback_interlocuteur_email, then to localPreviewFallbackEmail() in non-production environments
13. THE HasEmailPreview::buildProspectionMailContext(?rdv) SHALL return the context array required by ProspectionMailService, merging the current form fields as overrides for the email preview

---

### Requirement 5: Extraction du Concern HasStatusResult

**User Story:** En tant que développeur, je veux extraire la logique de validation et de
persistance du résultat d'appel dans un Concern dédié, afin que la soumission d'un
résultat soit un processus isolé, testable et sans effets de bord non contrôlés.

#### Acceptance Criteria

1. THE HasStatusResult SHALL be created as a PHP trait in the namespace App\Filament\NsConseil\Concerns
2. THE HasStatusResult SHALL expose the public Livewire properties for the result form: statut_resultat, commentaires, rappel_date, rappel_heure, nom_interlocuteur_standard, creneaux_permanence_cse, email_general_standard, interlocuteur_nom, interlocuteur_fonction, interlocuteur_telephone, interlocuteur_email, interlocuteur_add_nom, interlocuteur_add_fonction, interlocuteur_add_telephone, interlocuteur_add_email, lieu_rdv, invitation_agenda_envoyee, enregistrement_appel_joint, enregistrement_raison, besoins_exprimes, objections_soulevees, points_attention_rdv, presence_cse, jour_dispo_appel, lastAppelId
3. WHEN submitResult() is called and form validation fails, THE HasStatusResult SHALL display field-level Livewire validation errors (commentaires: required and between 5–2000 characters when PhoningResultService::isCommentRequired() returns true; email fields: valid RFC 5321 format when non-empty) and SHALL NOT advance the contactQueue
4. WHEN submitResult() is called and currentContact is null, THE HasStatusResult SHALL return immediately without performing any validation, persistence, or queue mutation
5. WHEN submitResult() is called and PhoningResultService::shouldPreviewEmail() returns true and emailPreviewConfirmed is false, THE HasStatusResult SHALL call openEmailPreview() and SHALL NOT persist the result
6. WHEN submitResult() is called and all validations pass and emailPreviewConfirmed is true or shouldPreviewEmail() returns false, THE HasStatusResult SHALL call PhoningContactUpdateService::updateContact() (which dispatches to the correct update method by contactType) and then PhoningResultService::applyResult(), creating exactly one Appel record in the database
7. WHEN PhoningResultService::applyResult() throws an exception, THE HasStatusResult SHALL display a Filament danger notification and SHALL NOT advance the contactQueue
8. WHEN submitResult() completes successfully, THE HasStatusResult SHALL remove the first entry from contactQueue, increment completed by 1, and call loadNextContact()
9. WHEN updatedStatutResultat() is triggered, THE HasStatusResult SHALL re-evaluate commentaireRequis by calling PhoningResultService::isCommentRequired(statut_resultat, contactType) and update showEmailPreviewWarning and other UI-only state properties without persisting any data; THE HasStatusResult SHALL also call resetEmailPreviewState()
10. THE HasStatusResult::getStatusValidationCodes() SHALL remain publicly accessible to preserve backward compatibility with existing unit tests
11. THE HasStatusResult::saveInterlocuteur() SHALL persist the interlocuteur fields (nom, fonction, telephone, email and the interlocuteur_add_* variants) to the current Prospect model without creating an Appel record and without submitting the full call result
12. THE HasStatusResult::getSelectedStatus() SHALL return the StatutPhoning model instance matching statut_resultat for the current contactType, or null if statut_resultat is empty
13. THE HasStatusResult::getStatutsPhoning() SHALL return the list of StatutPhoning records formatted for the view, including at minimum the fields: code, libelle, icone, couleur, groupe, pipeline_label
14. THE HasStatusResult::getStatutsPhoningGroupes() SHALL return the statuts grouped by cas (workflow CSE v2 grouping), as required by the status-panel Blade component
15. THE HasStatusResult::getPipelineTransitionPreview() SHALL return a preview of the pipeline transition that will be applied when the current statut_resultat is submitted, or null if no transition is defined for that statut
16. THE HasStatusResult::compterTentativesNonAbouties() SHALL return the count of NRP/std_nr attempts recorded for the current contact in the current campaign session
17. THE HasStatusResult::getTentativesAppel() SHALL return the same count as compterTentativesNonAbouties(), exposed as a public computed property for use in the view
18. THE HasStatusResult::getRappelStatusCodes() SHALL return the array of StatutPhoning codes that trigger a rappel (i.e. require rappel_date to be set)

---

### Requirement 6: Extraction du Concern HasQueueManagement

**User Story:** En tant que superviseur, je veux gérer la réorganisation de la file
d'appels dans le back-office via un Concern dédié, afin de pouvoir prioriser et
nettoyer la file sans modifier la logique principale de prospection.

#### Acceptance Criteria

1. THE HasQueueManagement SHALL be created as a PHP trait in the namespace App\Filament\NsConseil\Concerns
2. THE HasQueueManagement SHALL expose the public Livewire properties: prospectList, selectedIds, filterStatut, filterDept, filterRappelOnly
3. WHEN loadProspects() is called for a selected user, THE HasQueueManagement SHALL populate prospectList with the formatted queue for that user
4. WHEN reorderFromDrag(orderedIds) is called, THE HasQueueManagement SHALL reorder prospectList to match orderedIds and persist the new order via saveQueue()
5. WHEN moveUp(prospectId) is called, THE HasQueueManagement SHALL swap the prospect at position i with the prospect at position i-1, unless the prospect is already at position 0
6. WHEN moveDown(prospectId) is called, THE HasQueueManagement SHALL swap the prospect at position i with the prospect at position i+1, unless the prospect is already at the last position
7. WHEN moveToTop(prospectId) is called, THE HasQueueManagement SHALL move the prospect to index 0 in prospectList and persist the order
8. WHEN moveToBottom(prospectId) is called, THE HasQueueManagement SHALL move the prospect to the last index in prospectList and persist the order
9. WHEN moveSelectedToTop() is called, THE HasQueueManagement SHALL move all prospects whose id is in selectedIds to the front of prospectList, preserving their relative order
10. WHEN removeSelected() is called, THE HasQueueManagement SHALL remove all prospects whose id is in selectedIds from prospectList and persist the order
11. WHEN resetOrder() is called, THE HasQueueManagement SHALL clear the user's queue cache and call loadProspects() to rebuild the default order
12. WHEN applyFilters() is called, THE HasQueueManagement SHALL filter prospectList according to filterStatut, filterDept, and filterRappelOnly without persisting the filter state to cache

---

### Requirement 7: Création du PhoningQueueService

**User Story:** En tant que développeur, je veux un service centralisé orchestrant la
file d'appels, afin que la logique de cache et de construction de file ne soit plus
dupliquée entre PhoningWorkflow et PhoningBackOffice.

#### Acceptance Criteria

1. THE PhoningQueueService SHALL be created in the namespace App\Services\Phoning
2. WHEN getQueueForUser(userId, campagneId) is called and a cache entry exists for key "phoning_queue_user_{userId}", THE PhoningQueueService SHALL call PhoningQueueBuilder::filterValidQueue() on the cached data
3. WHEN getQueueForUser(userId, campagneId) is called and no cache entry exists, THE PhoningQueueService SHALL call PhoningQueueBuilder::buildDefaultQueue(userId, campagneId)
4. THE PhoningQueueService SHALL call PhoningQueueBuilder::prioriserFile() before PhoningQueueBuilder::reserveQueueForUser() on every getQueueForUser() call regardless of cache state
5. THE PhoningQueueService::getQueueForUser() SHALL always return an array and SHALL never return null; IF an internal exception occurs, THE PhoningQueueService SHALL catch it and return an empty array
6. WHEN saveQueueForUser(userId, queue) is called, THE PhoningQueueService SHALL store the queue in cache under key "phoning_queue_user_{userId}" with a TTL of 86400 seconds
7. WHEN clearQueueForUser(userId) is called, THE PhoningQueueService SHALL delete the cache key "phoning_queue_user_{userId}"
8. WHEN search(query) is called and query is non-empty, THE PhoningQueueService SHALL delegate to PhoningContactSearchService and return an array of at most 50 matching contacts; WHEN query is empty, THE PhoningQueueService SHALL return an empty array immediately without calling PhoningContactSearchService
9. IF userId is less than or equal to 0, THEN THE PhoningQueueService SHALL throw an InvalidArgumentException in getQueueForUser()
10. IF campagneId is provided and is less than or equal to 0, THEN THE PhoningQueueService SHALL throw an InvalidArgumentException in getQueueForUser()

---

### Requirement 8: Création du PhoningResultService

**User Story:** En tant que développeur, je veux un service dédié à la logique de
résultat d'appel (statut, commentaire obligatoire, déclencheur email), séparé de la mise
à jour des modèles contact, afin d'avoir deux services de taille raisonnable (~200 lignes
chacun) à la place d'un unique service monolithique.

#### Acceptance Criteria

1. THE PhoningResultService SHALL be created in the namespace App\Services\Phoning with no more than 200 lines
2. WHEN applyResult(contact, type, statut, fields) is called, THE PhoningResultService SHALL create one Appel record atomically within a single database transaction
3. WHEN applyResult() completes without exception, THE PhoningResultService SHALL return the created Appel instance with its id populated
4. WHEN applyResult() is called and no cache reservation exists for the pair (type, contact.id) under the current authenticated user's id, THE PhoningResultService SHALL throw an AuthorizationException before performing any database write
5. WHEN shouldPreviewEmail(statut, contactType) is called with statut in {'rdv', 'bloc', 'ncse_50', 'cse_hz'}, THE PhoningResultService SHALL return true
6. WHEN shouldPreviewEmail(statut, contactType) is called with a statut not in {'rdv', 'bloc', 'ncse_50', 'cse_hz'}, THE PhoningResultService SHALL return false
7. WHEN getStatutLabel(statut, contactType) is called with a statut that exists in StatutPhoning for the given contactType, THE PhoningResultService SHALL return the non-empty label string stored in StatutPhoning::libelle; WHEN statut does not exist in StatutPhoning for the given contactType, THE PhoningResultService SHALL return the statut string itself as a fallback
8. THE PhoningResultService::isCommentRequired(statut, contactType) SHALL return true if and only if the matching StatutPhoning record has note_obligatoire set to true; WHEN no matching StatutPhoning record exists, THE PhoningResultService SHALL return false

---

### Requirement 9: Création du PhoningEmailPreviewService

**User Story:** En tant que développeur, je veux un service dédié à la construction des
payloads d'aperçu email, afin d'isoler la création des Mailables et la résolution des
destinataires hors de la couche Livewire.

#### Acceptance Criteria

1. THE PhoningEmailPreviewService SHALL be created in the namespace App\Services\Phoning
2. WHEN buildPayload(statut, contact, formFields) is called with a statut that triggers an email, THE PhoningEmailPreviewService SHALL return an array with non-empty keys 'recipient', 'subject', and 'body'
3. WHEN buildPayload(statut, contact, formFields) is called with a statut that does not trigger an email, THE PhoningEmailPreviewService SHALL return null
4. THE PhoningEmailPreviewService::buildPayload() result SHALL satisfy: result.subject is not empty AND strip_tags(result.body) is not empty WHEN result is not null
5. WHEN makeMailable(statut, contact, fields) is called with a statut not in the email-triggering set, THE PhoningEmailPreviewService SHALL return null
6. WHEN resolveRecipient(statut, contact) is called, THE PhoningEmailPreviewService SHALL return a valid email address string or null

---

### Requirement 10: Découpage des templates Blade en composants anonymes

**User Story:** En tant que développeur, je veux découper les templates Blade monolithiques
en composants Blade anonymes réutilisables, afin que chaque zone de l'interface ait une
responsabilité unique et une taille contrôlée.

#### Acceptance Criteria

1. THE contact-panel.blade.php component SHALL be created at resources/views/components/phoning/contact-panel.blade.php and accept the props: contact, contactType, queueCount, progress, isSupervisorMode
2. THE status-panel.blade.php component SHALL be created at resources/views/components/phoning/status-panel.blade.php and accept the props: statuts, selectedStatut, commentaires, rappelDate, rappelHeure
3. THE email-preview.blade.php component SHALL be created at resources/views/components/phoning/email-preview.blade.php and accept the props: emailPreviewSubject, emailPreviewBody, emailPreviewRecipient
4. THE ringover-widget.blade.php component SHALL be created at resources/views/components/phoning/ringover-widget.blade.php and accept the props: phone, nrCount, maxNr, callId
5. THE queue-table.blade.php component SHALL be created at resources/views/components/phoning/queue-table.blade.php and accept the props: prospects, selectedIds
6. WHEN any phoning Blade component is rendered with a minimal valid props array, THE Blade_Component SHALL render without throwing a PHP exception or a Blade compilation error
7. THE phoning-workflow.blade.php orchestrator SHALL include contact-panel, status-panel, email-preview, and ringover-widget via x-phoning:: component tags
8. THE phoning-back-office.blade.php orchestrator SHALL include queue-table via the x-phoning::queue-table component tag

---

### Requirement 11: Extraction des styles CSS en fichiers dédiés

**User Story:** En tant que développeur, je veux extraire les styles CSS inline des
templates Blade vers des fichiers CSS dédiés compilés par Vite, afin de réduire la taille
des réponses Livewire et de bénéficier du cache navigateur par fingerprinting.

#### Acceptance Criteria

1. THE phoning-workflow.css file SHALL be created at resources/css/phoning-workflow.css containing all styles extracted from phoning-workflow.blade.php (environ 850 lignes de classes .pw-* extraites du bloc @push('styles'))
2. THE phoning-back-office.css file SHALL be created at resources/css/phoning-back-office.css containing all styles extracted from phoning-back-office.blade.php (environ 958 lignes de classes .pbo-* extraites du bloc @push('styles'))
3. THE phoning-workflow.blade.php orchestrator SHALL reference phoning-workflow.css via a @vite directive instead of inline <style> blocks
4. THE phoning-back-office.blade.php orchestrator SHALL reference phoning-back-office.css via a @vite directive instead of inline <style> blocks
5. WHEN the Vite build runs after extraction, THE CSS_Build SHALL compile phoning-workflow.css and phoning-back-office.css without errors
6. THE phoning-workflow.blade.php and phoning-back-office.blade.php templates SHALL contain no inline <style> blocks after CSS extraction

---

### Requirement 12: Intégrité de la file d'appels

**User Story:** En tant que téléprospecteur, je veux que la file d'appels reste
cohérente et sans doublons, afin de ne jamais appeler deux fois le même contact dans
la même session.

#### Acceptance Criteria

1. THE contactQueue SHALL only contain entries where id is greater than 0 and type is one of {'prospect', 'artisan', 'partenaire', 'client', 'particulier'}
2. THE contactQueue SHALL contain no duplicate entries where both type and id are equal
3. WHEN total is greater than 0, THE HasContactQueue SHALL maintain progress equal to round(completed / total × 100)
4. THE HasContactQueue SHALL ensure completed is less than or equal to total at all times
5. WHEN getQueueForUser() returns a queue, THE PhoningQueueService SHALL ensure all items with rappel_date on or before today appear before items with no rappel_date

---

### Requirement 13: Rétrocompatibilité et neutralité comportementale

**User Story:** En tant que développeur, je veux que le refactoring ne modifie aucun
comportement observable par l'utilisateur final et ne casse aucun test existant, afin de
livrer la restructuration sans régression fonctionnelle.

#### Acceptance Criteria

1. THE PhoningWorkflow::getStatusValidationCodes() method SHALL remain publicly accessible after refactoring
2. THE HasEmailPreview::getEmailPreviewPayload() method SHALL remain publicly accessible after refactoring
3. WHEN PhoningWorkflow::mount() is called with an empty queue, THE PhoningWorkflow SHALL not throw any exception
4. WHEN PhoningBackOffice::loadProspects() is called without a selected user, THE PhoningBackOffice SHALL return an empty array without throwing any exception
5. THE HasRoleAccess trait SHALL remain applied to both PhoningWorkflow and PhoningBackOffice after refactoring
6. WHEN the refactoring is complete, all PHPUnit tests that existed before the refactoring SHALL pass without modification to the test files; this includes PhoningWorkflowPreviewTest, PhoningWorkflowStatusesTest, CampagnePhoningQueueTest, PhoningQueueBuilderInterleaveTest, and PhoningWorkflowContactsRestantsTest
7. WHEN a user interacts with PhoningWorkflow after refactoring, THE PhoningWorkflow SHALL produce identical visible behavior to the pre-refactoring version

---

### Requirement 14: Gestion des erreurs et résilience du workflow

**User Story:** En tant que téléprospecteur, je veux que les erreurs techniques
n'interrompent pas ma session de prospection, afin de continuer à travailler même
lorsqu'un sous-système est défaillant.

#### Acceptance Criteria

1. WHEN PhoningResultService::applyResult() throws an exception, THE HasStatusResult SHALL display a Filament danger notification and SHALL leave contactQueue unchanged
2. WHEN PhoningContactResolver::resolveModel() returns null for a contact in the queue, THE HasContactQueue SHALL silently skip that contact by calling loadNextContact() with the next entry
3. WHEN PhoningEmailPreviewService::buildPayload() returns null, THE HasEmailPreview SHALL bypass the preview step and allow HasStatusResult to proceed directly to persistence
4. WHEN FicheGenerationService::genererAutoParStatut() throws a Throwable, THE HasStatusResult SHALL catch the exception without re-throwing it and SHALL NOT display an error notification
5. WHEN filterValidQueue() returns an empty array, THE HasContactQueue SHALL set currentContact to null and display an informational notification to the user
6. IF a Ringover lifecycle event is received with a null callId, THEN THE HasCallSession SHALL reset ringoverCallId, ringoverCallStartedAt, and ringoverCallEndedAt to null without throwing an exception

---

### Requirement 15: Plan de migration par étapes vérifiables

**User Story:** En tant que développeur, je veux que la migration soit découpée en
étapes indépendantes, chacune validée par les tests avant de passer à la suivante, afin
de limiter les risques de régression lors du refactoring.

#### Acceptance Criteria

1. WHEN step 1 (HasContactQueue + PhoningQueueService) is complete, THE PHPUnit_Test_Suite SHALL pass before proceeding to step 2
2. WHEN step 3 (PhoningResultService + PhoningContactUpdateService + HasStatusResult) is complete, THE PHPUnit_Test_Suite SHALL pass all result-related tests before proceeding to step 4
3. WHEN step 6 (Blade component extraction for phoning-workflow) is complete, THE Blade_Component SHALL render without errors in a browser before proceeding to step 7
4. WHEN step 10 (final step) is complete, THE PHPUnit_Test_Suite SHALL pass all existing and new tests with zero failures
5. WHEN any migration step leaves a file with more lines than its defined limit, THE Developer SHALL refactor the file before committing that step

---

### Requirement 16: Création du PhoningContactUpdateService

**User Story:** En tant que développeur, je veux un service dédié à la mise à jour des
modèles contact par type (Prospect, Artisan, Partenaire, Particulier, Client), afin de
séparer la logique de persistance des modèles de la logique d'enregistrement du résultat
d'appel et de maintenir chaque service sous 200 lignes.

#### Acceptance Criteria

1. THE PhoningContactUpdateService SHALL be created in the namespace App\Services\Phoning with no more than 200 lines
2. THE PhoningContactUpdateService SHALL expose a public method updateContact(contact, type, statut, fields) that dispatches to the appropriate type-specific update method based on the value of type
3. WHEN updateContact() is called with type 'prospect', THE PhoningContactUpdateService SHALL call updateProspect(prospect, statut, fields) which updates the Prospect model's statut_pipeline, interlocuteur fields, rappel_planifie_at, email, and tentatives_std_nr counter according to the business rules of the given statut
4. WHEN updateContact() is called with type 'artisan', THE PhoningContactUpdateService SHALL call updateArtisan(artisan, statut, fields) which updates the ArtisanProspection model's statut_campagne field
5. WHEN updateContact() is called with type 'partenaire', THE PhoningContactUpdateService SHALL call updatePartenaire(partenaire, statut, fields) which updates the ContactPartenaire model's relevant fields
6. WHEN updateContact() is called with type 'particulier', THE PhoningContactUpdateService SHALL call updateParticulier(particulier, statut, fields) which updates the ContactParticulier model's relevant fields
7. WHEN updateContact() is called with type 'client', THE PhoningContactUpdateService SHALL call updateClient(client, statut, fields) which updates the Client model's relevant fields
8. WHEN updateProspect() determines that the statut requires a rappel (statut code is in getRappelStatusCodes()), THE PhoningContactUpdateService SHALL call creerRendezVous(prospect, fields) to create a RendezVous record if rappel_date is provided in fields
9. THE PhoningContactUpdateService::creerRendezVous(prospect, fields) SHALL create a RendezVous record in the database with rdvable_type set to Prospect::class, rdvable_id set to prospect.id, date_heure derived from fields['rappel_date'] and fields['rappel_heure'], and the interlocuteur fields populated from the form fields
10. THE PhoningContactUpdateService::appliquerRappelProspect(prospect, fields) SHALL set rappel_planifie_at on the Prospect model from the combined rappel_date and rappel_heure values in fields, and SHALL save the model
11. WHEN updateContact() is called with an unrecognized type, THE PhoningContactUpdateService SHALL throw an InvalidArgumentException with a message indicating the unrecognized type
