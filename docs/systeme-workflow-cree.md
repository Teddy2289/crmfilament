# Système de Workflow/Approbation

## Date: 9 août 2026

## Résumé

Création d'un système de workflow/approbation pour valider les prospects, partenaires et dossiers de formation. Le système utilise l'infrastructure existante WorkflowGroupe/WorkflowStep et ajoute le tracking des instances et l'historique des transitions.

---

## 1. Infrastructure Existante

### 1.1 WorkflowGroupe
**Fichier:** `app/Models/WorkflowGroupe.php`

**Attributs:**
- `model_type` - Type de modèle (prospect, partenaire, dossier_formation)
- `code` - Code unique du groupe
- `label` - Label affiché
- `ordre` - Ordre d'affichage
- `actif` - Si le groupe est actif

**Relations:**
- `workflowSteps()` - Étapes du workflow

### 1.2 WorkflowStep
**Fichier:** `app/Models/WorkflowStep.php`

**Attributs (existants + ajoutés):**
- `workflow_groupe_id` - FK vers WorkflowGroupe
- `label` - Label de l'étape
- `code` - Code unique
- `type` - Type (task, condition, action, notification, approval)
- `ordre` - Ordre de l'étape
- `config` - Configuration JSON
- `actif` - Si l'étape est active
- `est_final` - **NOUVEAU** - Si c'est l'étape finale

**Relations (ajoutées):**
- `instances()` - Instances de workflow à cette étape

---

## 2. Nouveaux Modèles Créés

### 2.1 WorkflowInstance
**Fichier:** `app/Models/WorkflowInstance.php`

**Attributs:**
- `workflow_groupe_id` - FK vers WorkflowGroupe
- `current_step_id` - FK vers WorkflowStep actuelle
- `instanceable_type` - Type polymorphique
- `instanceable_id` - ID polymorphique
- `statut` - Statut (en_cours, termine, annule)
- `date_debut` - Date de début
- `date_fin` - Date de fin
- `initiated_by` - ID de l'utilisateur initiateur

**Constantes:**
- `STATUTS` - Statuts disponibles

**Accesseurs:**
- `statut_label` - Label du statut
- `statut_color` - couleur du statut

**Méthodes métier:**
- `avancerVers(WorkflowStep $step, ?string $commentaire)` - Avancer vers une étape
- `terminer()` - Terminer le workflow
- `annuler(?string $motif)` - Annuler le workflow
- `demarrerPour(Model $instance, WorkflowGroupe $workflowGroupe)` - Démarrer un workflow

**Scopes:**
- `scopeEnCours()` - Instances en cours
- `scopeTermines()` - Instances terminées
- `scopePourInstance(Model $instance)` - Instances pour une entité

**Relations:**
- `workflowGroupe()` - Groupe de workflow
- `currentStep()` - Étape actuelle
- `instanceable()` - Entité polymorphique
- `initiatedBy()` - Utilisateur initiateur
- `histories()` - Historique des transitions

### 2.2 WorkflowHistory
**Fichier:** `app/Models/WorkflowHistory.php`

**Attributs:**
- `workflow_instance_id` - FK vers WorkflowInstance
- `from_step_id` - FK vers WorkflowStep de départ
- `to_step_id` - FK vers WorkflowStep d'arrivée
- `commentaire` - Commentaire de la transition
- `user_id` - ID de l'utilisateur

**Relations:**
- `workflowInstance()` - Instance de workflow
- `fromStep()` - Étape de départ
- `toStep()` - Étape d'arrivée
- `user()` - Utilisateur

---

## 3. Migrations

### 3.1 workflow_instances
**Fichier:** `database/migrations/2024_08_09_000002_create_workflow_instances_table.php`

**Colonnes:**
- workflow_groupe_id (FK)
- current_step_id (FK)
- instanceable_type, instanceable_id (polymorphique)
- statut, date_debut, date_fin
- initiated_by (FK users)
- timestamps, softDeletes

**Index:**
- instanceable_type + instanceable_id
- workflow_groupe_id
- current_step_id
- statut

### 3.2 workflow_histories
**Fichier:** `database/migrations/2024_08_09_000003_create_workflow_histories_table.php`

**Colonnes:**
- workflow_instance_id (FK)
- from_step_id (FK)
- to_step_id (FK)
- commentaire
- user_id (FK users)
- timestamps

**Index:**
- workflow_instance_id
- user_id

### 3.3 workflow_steps (mise à jour)
**Fichier:** `database/migrations/2026_06_28_070806_create_workflow_steps_table.php`

**Ajout:**
- `est_final` - Boolean pour marquer l'étape finale

---

## 4. Relations aux Modèles Existantants

### 4.1 Modèles mis à jour
Les modèles suivants ont maintenant des relations workflow:

1. **Prospect** - `morphOne(WorkflowInstance::class, 'instanceable')` + `morphMany(WorkflowHistory::class, 'instanceable')`
2. **Partenaire** - `morphOne(WorkflowInstance::class, 'instanceable')` + `morphMany(WorkflowHistory::class, 'instanceable')`
3. **DossierFormation** - `morphOne(WorkflowInstance::class, 'instanceable')` + `morphMany(WorkflowHistory::class, 'instanceable')`

---

## 5. Actions Filament

### 5.1 ProspectResource
**Fichier:** `app/Filament/NsConseil/Resources/ProspectResource.php`

**Action ajoutée:**
- `demarrer_workflow` - Démarrer le workflow de validation
  - Visible si: pas d'instance workflow ET statut = RPC
  - Recherche le workflow groupe pour 'prospect'
  - Démarre l'instance via `WorkflowInstance::demarrerPour()`

### 5.2 PartenaireResource
**Fichier:** `app/Filament/NsConseil/Resources/PartenaireResource.php`

**Action ajoutée:**
- `demarrer_workflow` - Démarrer le workflow de validation
  - Visible si: pas d'instance workflow ET statut = EnCours
  - Recherche le workflow groupe pour 'partenaire'
  - Démarre l'instance via `WorkflowInstance::demarrerPour()`

### 5.3 DossierFormationResource
**Fichier:** `app/Filament/NsConseil/Resources/DossierFormationResource.php`

**Action ajoutée:**
- `demarrer_workflow` - Démarrer le workflow de validation
  - Visible si: pas d'instance workflow ET etat = en_cours
  - Recherche le workflow groupe pour 'dossier_formation'
  - Démarre l'instance via `WorkflowInstance::demarrerPour()`

---

## 6. Instructions pour l'Utilisateur

### Configuration des Workflows
1. Créer des WorkflowGroupes dans la base de données:
   - model_type: 'prospect', 'partenaire', 'dossier_formation'
   - code: code unique
   - label: label affiché
   - actif: true

2. Créer des WorkflowSteps pour chaque groupe:
   - workflow_groupe_id: FK vers le groupe
   - label: label de l'étape
   - code: code unique
   - type: type d'étape
   - ordre: ordre de l'étape
   - est_final: true pour la dernière étape

### Démarrer un Workflow
1. Aller dans la ressource (Prospect, Partenaire, DossierFormation)
2. Cliquer sur "Démarrer validation"
3. Le workflow démarre automatiquement à la première étape

### Suivre les Workflows
- L'instance de workflow est liée à l'entité
- L'historique des transitions est enregistré
- Le statut de l'instance est mis à jour automatiquement

---

## 7. Notes Importantes

- Le système utilise l'infrastructure WorkflowGroupe/WorkflowStep existante
- Les relations sont polymorphiques pour flexibilité
- L'historique des transitions est automatiquement enregistré
- Les actions Filament déclenchent le workflow automatiquement
- Les notifications informent l'utilisateur du succès ou de l'échec
- Toutes les modifications sont non-breaking
- Le système est extensible à d'autres entités
