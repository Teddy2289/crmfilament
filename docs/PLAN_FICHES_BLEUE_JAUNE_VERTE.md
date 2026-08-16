# Plan d'Action - Implémentation des Fiches Bleue, Jaune et Verte selon Directive AOPIA

## Analyse de l'Existant

### Système Actuel
Le système dispose déjà d'une infrastructure de génération de fiches :

- **Service** : `FicheGenerationService` - Génère des documents Word à partir de templates
- **Modèles** : `FicheTemplate` - Définit les templates et leurs mappings
- **Types** : 3 types déjà définis (bleue, jaune, verte)
- **Jobs** : 
  - `SendFicheJauneJ7Job` - Envoi différé J+7 pour fiches jaunes
  - `SendFicheVerteCommercialJob` - Envoi immédiat pour fiches vertes
- **Intégration** : Lié à `StatutPhoning` via le champ `fiche_type`

### Workflow Actuel dans PhoningWorkflow.php
```php
// Ligne 686-708 dans PhoningWorkflow.php
$this->dispatchFicheGenerationJob();

// Auto-génération des fiches Word liées au statut phoning
if ($this->contactType === 'prospect' && $this->currentContact instanceof Prospect) {
    try {
        $ficheService = app(FicheGenerationService::class);
        $docs = $ficheService->genererAutoParStatut(
            $this->statut_resultat,
            $this->currentContact,
            $this->currentContact->rendezVous()->latest('date_heure')->first()
        );
        // ... notification des fiches générées
    } catch (\Throwable) {
        // Ne pas bloquer le workflow si la génération échoue
    }
}
```

---

## Directive AOPIA - Analyse des Besoins

### Fiche Bleue - Récapitulatif RDV Pris
**Déclencheur** : Statut `RDV` (Rendez-vous confirmé)

**Contenu requis** :
- Informations entreprise (raison sociale, secteur, effectif, adresse)
- Interlocuteur CSE (nom, fonction, téléphone, email)
- Détails RDV (date, heure, lieu)
- Téléprospecteur et commercial assignés
- Notes de l'appel

**Actions associées** :
- Mail de confirmation au prospect
- Invitation calendrier au commercial
- Enregistrement de l'appel
- Copie aux équipes internes

### Fiche Jaune - CSE Pas Intéressé (Rappel J+7)
**Déclencheur** : Statut `CSE-NI` (CSE non intéressé)

**Contenu requis** :
- Informations entreprise
- Interlocuteur CSE (nom, prénom, téléphone, mail, fonction)
- Date de l'appel
- Commentaires/Motif de refus
- **Rappel commercial à J+7**

**Actions associées** :
- Envoi différé J+7 au commercial assigné
- Tags : `CSE-NI` + département

### Fiche Verte - RDV à Conclure
**Déclencheurs** : 
- Statut `BLOC2` (Toujours bloqué après rappel)
- Statut `NCSE-50` (Pas de CSE, < 50 salariés)

**Contenu requis** :
- Informations entreprise
- Interlocuteur identifié (délégué personnel ou élu)
- Motif de blocage ou absence CSE
- Coordonnées complètes pour relance

**Actions associées** :
- Envoi immédiat au commercial
- Pas de délai fixe (au fil de l'eau)

---

## Écart Analyse - Existant vs Directive

### ✅ Ce qui fonctionne déjà
1. **Infrastructure de génération** : `FicheGenerationService` opérationnel
2. **Mapping des statuts** : `StatutPhoning.fiche_type` correctement configuré
3. **Jobs différés** : `SendFicheJauneJ7Job` et `SendFicheVerteCommercialJob` en place
4. **Types de fiches** : Les 3 types (bleue, jaune, verte) sont définis

### ⚠️ Points à améliorer/implémenter
1. **Contenu des fiches** : Les templates Word doivent correspondre exactement aux spécifications
2. **Champs spécifiques** : Certains champs de la directive peuvent manquer dans le mapping
3. **Workflow d'envoi** : Vérifier que les workflows d'envoi correspondent aux besoins
4. **Notification utilisateur** : Améliorer le feedback lors de la génération

---

## Plan d'Action

### Phase 1 : Validation des Templates (Semaine 1)

#### Objectif
S'assurer que les templates Word existants correspondent aux spécifications de la directive.

#### Tâches
- [ ] **1.1** Localiser les templates Word actuels (fichiers .docx)
- [ ] **1.2** Comparer le contenu des templates avec les spécifications de la directive
- [ ] **1.3** Identifier les champs manquants ou incorrects dans chaque template
- [ ] **1.4** Mettre à jour les templates si nécessaire

#### Livrables
- Templates Word à jour pour les 3 types de fiches
- Document de mapping champs ↔ placeholders

---

### Phase 2 : Validation du Mapping (Semaine 1-2)

#### Objectif
S'assurer que le mapping des placeholders dans `FicheTemplate` correspond aux besoins.

#### Tâches
- [ ] **2.1** Vérifier les `FicheTemplate` existants en base de données
- [ ] **2.2** Valider le mapping `placeholders` pour chaque type de fiche
- [ ] **2.3** Ajouter les champs spécifiques de la directive si manquants :
  - Pour fiche bleue : lieu_rdv, invitation_agenda_envoyee, besoins_exprimes, objections_soulevees, points_attention_rdv
  - Pour fiche jaune : motif_ko, date_rappel_j7
  - Pour fiche verte : presence_cse, jour_dispo_appel, motif_pas_cse
- [ ] **2.4** Tester la génération avec des données réelles

#### Livrables
- Mapping à jour dans la base de données
- Tests de génération validés

---

### Phase 3 : Amélioration du Workflow de Génération (Semaine 2)

#### Objectif
Optimiser le déclenchement de la génération dans `PhoningWorkflow.php`.

#### Tâches
- [ ] **3.1** Extraire la logique de génération de `PhoningWorkflow.php` vers un service dédié
- [ ] **3.2** Créer `PhoningFicheGenerationService` pour orchestrer la génération
- [ ] **3.3** Améliorer la gestion des erreurs (au lieu du silent catch)
- [ ] **3.4** Ajouter des logs détaillés pour le debugging
- [ ] **3.5** Implémenter un système de retry en cas d'échec

#### Livrables
- `PhoningFicheGenerationService` créé et intégré
- Meilleure gestion des erreurs
- Logs détaillés

---

### Phase 4 : Validation des Jobs d'Envoi (Semaine 2-3)

#### Objectif
S'assurer que les jobs d'envoi différé fonctionnent correctement.

#### Tâches
- [ ] **4.1** Tester `SendFicheJauneJ7Job` avec un scénario réel
- [ ] **4.2** Vérifier le calcul de la date J+7
- [ ] **4.3** Tester `SendFicheVerteCommercialJob` avec un scénario réel
- [ ] **4.4** Valider que les destinataires sont corrects (commercial assigné)
- [ ] **4.5** Vérifier le contenu des emails envoyés

#### Livrables
- Jobs d'envoi validés
- Scénarios de test documentés

---

### Phase 5 : Intégration UI et Feedback (Semaine 3)

#### Objectif
Améliorer l'interface utilisateur pour le suivi des fiches.

#### Tâches
- [ ] **5.1** Ajouter un indicateur visuel dans `PhoningWorkflow` quand une fiche est générée
- [ ] **5.2** Permettre le téléchargement direct de la fiche depuis l'interface
- [ ] **5.3** Afficher l'historique des fiches générées pour un prospect
- [ ] **5.4** Ajouter des notifications de succès/échec de génération

#### Livrables
- Interface améliorée avec feedback utilisateur
- Historique des fiches accessible

---

### Phase 6 : Tests et Documentation (Semaine 3-4)

#### Objectif
Valider l'ensemble du système et documenter les processus.

#### Tâches
- [ ] **6.1** Créer des tests unitaires pour `PhoningFicheGenerationService`
- [ ] **6.2** Créer des tests d'intégration pour le workflow complet
- [ ] **6.3** Documenter le processus de génération dans `AGENTS.md`
- [ ] **6.4** Créer un guide utilisateur pour les téléprospecteurs
- [ ] **6.5** Former les utilisateurs au nouveau workflow

#### Livrables
- Tests complets
- Documentation à jour
- Utilisateurs formés

---

## Spécifications Techniques Détaillées

### Mapping des Champs - Fiche Bleue

#### Template Word requis
```yaml
Placeholders requises:
  - ${RAISON_SOCIALE}
  - ${SECTEUR_ACTIVITE}
  - ${NB_SALARIES}
  - ${ADRESSE_COMPLETE}
  - ${INTERLOCUTEUR_NOM}
  - ${INTERLOCUTEUR_FONCTION}
  - ${INTERLOCUTEUR_TELEPHONE}
  - ${INTERLOCUTEUR_EMAIL}
  - ${RDV_DATE_HEURE}
  - ${RDV_LIEU}
  - ${TELEPROSPECTEUR}
  - ${COMMERCIAL}
  - ${DATE_APPEL}
  - ${BESOINS_EXPRIMES}
  - ${OBJECTIONS_SOULEVEES}
  - ${POINTS_ATTENTION_RDV}
  - ${INVITATION_AGENDA_ENVOYEE}
```

#### Correspondance Prospect ↔ Template
```php
'${BESOINS_EXPRIMES}' => 'besoins_exprimes',          // Nouveau champ
'${OBJECTIONS_SOULEVEES}' => 'objections_soulevees',  // Nouveau champ
'${POINTS_ATTENTION_RDV}' => 'points_attention_rdv',  // Nouveau champ
'${INVITATION_AGENDA_ENVOYEE}' => 'invitation_agenda_envoyee', // Nouveau champ
```

### Mapping des Champs - Fiche Jaune

#### Template Word requis
```yaml
Placeholders requises:
  - ${RAISON_SOCIALE}
  - ${SECTEUR_ACTIVITE}
  - ${NB_SALARIES}
  - ${ADRESSE_COMPLETE}
  - ${INTERLOCUTEUR_NOM}
  - ${INTERLOCUTEUR_FONCTION}
  - ${INTERLOCUTEUR_TELEPHONE}
  - ${INTERLOCUTEUR_EMAIL}
  - ${TELEPROSPECTEUR}
  - ${COMMERCIAL}
  - ${DATE_APPEL}
  - ${MOTIF_REFUS}
  - ${DATE_RAPPEL_J7}
```

#### Correspondance Prospect ↔ Template
```php
'${MOTIF_REFUS}' => 'motif_ko',              // Champ existant
'${DATE_RAPPEL_J7}' => 'date_rappel_j7',     // Calculé : date_appel + 7 jours
```

### Mapping des Champs - Fiche Verte

#### Template Word requis
```yaml
Placeholders requises:
  - ${RAISON_SOCIALE}
  - ${SECTEUR_ACTIVITE}
  - ${NB_SALARIES}
  - ${ADRESSE_COMPLETE}
  - ${INTERLOCUTEUR_NOM}
  - ${INTERLOCUTEUR_FONCTION}
  - ${INTERLOCUTEUR_TELEPHONE}
  - ${INTERLOCUTEUR_EMAIL}
  - ${TELEPROSPECTEUR}
  - ${COMMERCIAL}
  - ${DATE_APPEL}
  - ${PRESENCE_CSE}
  - ${JOUR_DISPO_APPEL}
  - ${MOTIF_PAS_CSE}
```

#### Correspondance Prospect ↔ Template
```php
'${PRESENCE_CSE}' => 'presence_cse',         // Nouveau champ
'${JOUR_DISPO_APPEL}' => 'jour_dispo_appel', // Nouveau champ
'${MOTIF_PAS_CSE}' => 'motif_pas_cse',       // Calculé selon statut
```

---

## Implémentation Technique

### 1. Création de PhoningFicheGenerationService

```php
<?php

namespace App\Services\Phoning;

use App\Models\Appel;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Services\Aopia\FicheGenerationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PhoningFicheGenerationService
{
    public function __construct(
        private FicheGenerationService $ficheService
    ) {}

    /**
     * Génère les fiches automatiques pour un appel et notifie l'utilisateur.
     */
    public function genererFichesPourAppel(Appel $appel): array
    {
        if (!$appel->fiche_type || !$appel->appelable instanceof Prospect) {
            return [];
        }

        try {
            $rdv = $appel->appelable->rendezVous()
                ->latest('date_heure')
                ->first();

            $documents = $this->ficheService->genererAutoParStatut(
                $appel->phoning_status,
                $appel->appelable,
                $rdv
            );

            if (!empty($documents)) {
                $this->notifierGeneration($documents, $appel);
                Log::info("Fiches générées pour l'appel #{$appel->id}", [
                    'statut' => $appel->phoning_status,
                    'nb_documents' => count($documents),
                ]);
            }

            return $documents;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération de fiches pour l'appel #{$appel->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Notification d'erreur à l'utilisateur
            $this->notifierErreur($appel, $e);
            
            return [];
        }
    }

    private function notifierGeneration(array $documents, Appel $appel): void
    {
        $noms = collect($documents)->pluck('nom_fichier')->implode(', ');
        
        Notification::make()
            ->title('Fiches générées automatiquement')
            ->body($noms)
            ->info()
            ->send();
    }

    private function notifierErreur(Appel $appel, \Exception $e): void
    {
        Notification::make()
            ->title('Erreur de génération de fiche')
            ->body('La fiche n\'a pas pu être générée. L\'erreur a été loguée.')
            ->danger()
            ->send();
    }
}
```

### 2. Migration pour les nouveaux champs Prospect

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            // Champs pour fiche bleue
            $table->text('besoins_exprimes')->nullable();
            $table->text('objections_soulevees')->nullable();
            $table->text('points_attention_rdv')->nullable();
            $table->boolean('invitation_agenda_envoyee')->default(false);
            
            // Champs pour fiche verte
            $table->string('presence_cse')->nullable();
            $table->string('jour_dispo_appel')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn([
                'besoins_exprimes',
                'objections_soulevees',
                'points_attention_rdv',
                'invitation_agenda_envoyee',
                'presence_cse',
                'jour_dispo_appel',
            ]);
        });
    }
};
```

### 3. Mise à jour de PhoningWorkflow.php

```php
// Remplacer les lignes 686-708 par :

use App\Services\Phoning\PhoningFicheGenerationService;

// Dans submitResult() :

$this->enregistrerAppel();

// Génération des fiches via le nouveau service
if ($this->lastAppelId) {
    $appel = Appel::find($this->lastAppelId);
    if ($appel) {
        app(PhoningFicheGenerationService::class)
            ->genererFichesPourAppel($appel);
    }
}
```

---

## Checklist de Validation

### Templates
- [ ] Template Word fiche bleue à jour avec tous les champs
- [ ] Template Word fiche jaune à jour avec tous les champs
- [ ] Template Word fiche verte à jour avec tous les champs
- [ ] Placeholders correctement positionnés dans les templates

### Base de données
- [ ] Migration exécutée pour les nouveaux champs Prospect
- [ ] FicheTemplate mis à jour pour chaque type
- [ ] Mapping placeholders validé
- [ ] StatutPhoning.fiche_type correctement configuré

### Services
- [ ] PhoningFicheGenerationService créé
- [ ] Intégré dans PhoningWorkflow.php
- [ ] Gestion des erreurs améliorée
- [ ] Logs détaillés implémentés

### Jobs
- [ ] SendFicheJauneJ7Job testé et validé
- [ ] SendFicheVerteCommercialJob testé et validé
- [ ] Calcul des dates correct
- [ ] Destinataires corrects

### Interface
- [ ] Notification de génération visible
- [ ] Téléchargement direct fonctionnel
- [ ] Historique des fiches accessible
- [ ] Feedback utilisateur clair

### Tests
- [ ] Tests unitaires pour le service
- [ ] Tests d'intégration pour le workflow
- [ ] Scénarios de test documentés
- [ ] Tests de régression passent

### Documentation
- [ ] AGENTS.md mis à jour
- [ ] Guide utilisateur créé
- [ ] Documentation technique complète
- [ ] Utilisateurs formés

---

## Risques et Mitigations

### Risque 1 : Templates Word non conformes
- **Mitigation** : Validation approfondie en Phase 1
- **Mitigation** : Création de templates de référence

### Risque 2 : Mapping incomplet
- **Mitigation** : Comparaison systématique avec la directive
- **Mitigation** : Tests avec données réelles

### Risque 3 : Jobs d'envoi non fonctionnels
- **Mitigation** : Tests approfondis en Phase 4
- **Mitigation** : Monitoring des jobs en production

### Risque 4 : Impact performance
- **Mitigation** : Génération asynchrone via jobs
- **Mitigation** : Monitoring des temps de génération

---

## Suivi et Mesures

### KPIs à suivre
- Taux de réussite de génération des fiches
- Temps moyen de génération
- Taux d'ouverture des emails de rappel J+7
- Feedback utilisateur sur la qualité des fiches

### Fréquence de revue
- Hebdomadaire pendant l'implémentation
- Mensuelle après mise en production