# Système de Gestion des Tâches

## Date: 9 août 2026

## Résumé

Création d'un système complet de gestion des tâches avec assignation, priorités, échéances, et widget de suivi sur le dashboard.

---

## 1. Modèle Task

### 1.1 Structure
**Fichier:** `app/Models/Task.php`

**Attributs:**
- `titre` - Titre de la tâche
- `description` - Description détaillée
- `type` - Type (tache, rappel, appel, rdv)
- `statut` - Statut (a_faire, en_cours, terminee, annulee)
- `date_echeance` - Date d'échéance
- `date_realisation` - Date de réalisation
- `assigne_a` - ID de l'utilisateur assigné
- `prospect_id` - ID du prospect lié
- `partenaire_id` - ID du partenaire lié
- `client_id` - ID du client lié
- `rendez_vous_id` - ID du rendez-vous lié
- `opportunite_id` - ID de l'opportunité liée
- `appel_id` - ID de l'appel lié
- `created_by` - ID du créateur

**Constantes:**
- `TYPES` - Types de tâches disponibles
- `STATUTS` - Statuts disponibles

**Accesseurs:**
- `type_label` - Label du type
- `statut_label` - Label du statut
- `statut_color` - couleur du statut
- `est_en_retard` - Si la tâche est en retard
- `est_urgent` - Si la tâche est urgente (aujourd'hui)

**Méthodes métier:**
- `marquerEnCours()` - Marquer comme en cours
- `marquerTerminee()` - Marquer comme terminée
- `annuler($motif)` - Annuler avec motif

**Scopes:**
- `scopeAFaire()` - Tâches à faire
- `scopeEnCours()` - Tâches en cours
- `scopeTerminees()` - Tâches terminées
- `scopeEnRetard()` - Tâches en retard
- `scopeUrgentes()` - Tâches urgentes (aujourd'hui)
- `scopeAssigneesA($userId)` - Tâches assignées à un utilisateur
- `scopeParType($type)` - Tâches par type

**Relations:**
- `assigneA()` - Utilisateur assigné
- `createdBy()` - Créateur
- `prospect()` - Prospect lié
- `partenaire()` - Partenaire lié
- `client()` - Client lié
- `rendezVous()` - Rendez-vous lié
- `opportunite()` - Opportunité liée
- `appel()` - Appel lié

---

## 2. Migration

### 2.1 Table tasks
**Fichier:** `database/migrations/2026_06_24_131307_create_tasks_table.php`

**Colonnes:**
- id, titre, description, type, statut
- date_echeance, date_realisation
- assigne_a, created_by (FK users)
- prospect_id, partenaire_id, client_id (FK)
- rendez_vous_id, opportunite_id, appel_id (FK)
- timestamps, softDeletes

**Index:**
- statut + date_echeance
- assigne_a
- prospect_id, partenaire_id, client_id
- rendez_vous_id, opportunite_id, appel_id

---

## 3. TaskResource Filament

### 3.1 Resource
**Fichier:** `app/Filament/NsConseil/Resources/TaskResource.php`

**Navigation:**
- Groupe: Productivité
- Icône: heroicon-o-check-circle
- Sort: 1

**Form:**
- Section Informations générales: titre, description, type, statut, date_echeance, assigne_a
- Section Liaison: prospect, partenaire, client, rendez-vous, opportunité, appel

**Table:**
- Colonnes: titre, type, statut, échéance, assignée à, créée le
- Filtres: statut, type, assignée à, en retard, urgentes
- Actions: marquer en cours, marquer terminée, voir, modifier, supprimer
- Actions groupées: marquer comme terminées

**Pages:**
- ListTasks
- CreateTask (avec "Créer et ajouter une autre")
- EditTask

---

## 4. Widget TâchesDuJour

### 4.1 Widget
**Fichier:** `app/Filament/NsConseil/Widgets/TachesDuJourWidget.php`

**Statistiques affichées:**
1. **À faire** - Tâches en attente (gris)
2. **En cours** - Tâches en progression (warning)
3. **En retard** - Échéance dépassée (danger si > 0)
4. **Urgentes** - Pour aujourd'hui (danger si > 0)
5. **Terminées aujourd'hui** - Accomplissement (success)
6. **Total en cours** - Toutes les tâches (info)

**Configuration:**
- Sort: 1
- Column span: full
- Polling: 120s
- Visible pour tous les utilisateurs connectés

---

## 5. Intégration Dashboard

### 5.1 Dashboard
**Fichier:** `app/Filament/NsConseil/Pages/Dashboard.php`

**Ajout:**
- Widget TachesDuJourWidget en premier pour tous les utilisateurs connectés

---

## 6. Relations aux Modèles Existantants

### 6.1 Modèles mis à jour
Les modèles suivants ont maintenant une relation `tasks()`:

1. **Prospect** - `hasMany(Task::class)`
2. **Partenaire** - `hasMany(Task::class)`
3. **Client** - `hasMany(Task::class)`
4. **RendezVous** - `hasMany(Task::class)`
5. **Opportunite** - `hasMany(Task::class)`
6. **Appel** - `hasMany(Task::class)`

---

## 7. Instructions pour l'Utilisateur

### Créer une tâche
1. Aller dans "Productivité" > "Tâches"
2. Cliquer sur "Créer"
3. Remplir le titre et la description
4. Choisir le type et le statut
5. Définir la date d'échéance
6. Assigner à un utilisateur
7. Lier à une entité (prospect, partenaire, client, RDV, opportunité, appel)

### Suivre les tâches
1. Le widget TâchesDuJour s'affiche sur le dashboard
2. Les statistiques se mettent à jour toutes les 2 minutes
3. Les tâches en retard et urgentes sont signalées en rouge

### Gérer les tâches
1. Dans la liste des tâches
2. Cliquer sur "En cours" pour démarrer une tâche
3. Cliquer sur "Terminer" pour marquer comme terminée
4. Utiliser les filtres pour trouver des tâches spécifiques

---

## 8. Notes Importantes

- Le modèle Task existait déjà, il a été étendu avec de nouvelles relations
- La migration a été mise à jour pour inclure les nouveaux champs
- Le widget est visible pour tous les utilisateurs connectés
- Les tâches peuvent être liées à n'importe quelle entité du CRM
- Les actions rapides permettent de changer le statut sans ouvrir le formulaire
- Toutes les modifications sont non-breaking
