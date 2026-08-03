# Architecture du CRM - Graph des Relations pour IA

## Vue d'ensemble du système

Ce CRM gère le cycle de vie complet des relations commerciales :
- Prospection (Prospects)
- Partenariats (Partenaires)
- Clients et Formations (Clients, Propositions, DossierFormation)
- Activités commerciales (Appels, Rendez-vous, Documents)

---

## Entités Principales

### 1. **User** (Utilisateurs)
- Rôles: Super Admin, Admin, Commercial, Téléprospecteur, Opérateur N1, Back-Office, Responsable Plateau
- Secteurs: Nord, Sud, Est, Ouest, Île-de-France, National
- Intégrations: Google, Ringover

**Relations:**
- `hasMany` Prospect (teleprospecteur_id, commercial_id, valide_par)
- `hasMany` Partenaire (commercial_id)
- `hasMany` RendezVous (commercial_id, teleprospecteur_id)
- `hasMany` Appel (user_id)
- `belongsToMany` GroupeTelepro
- `hasMany` Ticket (operateur_id)
- `belongsTo` CrmProfile

---

### 2. **Prospect** (Prospects à qualifier)
- Statuts: AC, STD_NR, STD_Joint, CSE_NR, RP, RPC, KO, QF
- Types pressentis: CSE, Artisan, Direct
- Workflow de qualification: AC → STD_Joint → RP/RPC → QF

**Relations:**
- `belongsTo` User (teleprospecteur_id, commercial_id, valide_par)
- `belongsTo` CampagnePhoning (campagne_id)
- `belongsTo` Partenaire (converti_partenaire_id)
- `morphMany` Appel
- `morphMany` RendezVous
- `morphMany` Document
- `morphMany` HistoriqueInteractionUser
- `hasOne` Opportunite

**Conversion:** Peut être converti en Partenaire via `convertirEnPartenaire()`

---

### 3. **Partenaire** (Organisations partenaires)
- Types: CSE, Syndicat, Entreprise Directe, Association
- Statuts: À prospecter, En cours prospection, RDV en cours, Signé accord cadre, Convention engagement, Refus
- Nomenclature automatique: [Entreprise] [Ville] [Département] [Type]

**Relations:**
- `belongsTo` EntiteCommerciale (entite_id)
- `belongsTo` Entreprise (entreprise_id)
- `belongsTo` Partenaire (entreprise_mere_id, parrain_partenaire_id)
- `belongsTo` User (commercial_id)
- `belongsTo` Consultant (conseiller_id)
- `belongsTo` Prospect (prospect_id)
- `hasMany` Partenaire (filiales, filleuls)
- `hasMany` Client (clients, personnes)
- `hasMany` ContactPartenaire
- `hasOne` AdresseCse
- `hasOne` Tarification
- `hasOne` ActiviteVente
- `hasOne` ActivitePermanence
- `hasOne` RemboursementEmployeur
- `hasMany` HistoriqueConseiller
- `hasMany` AutresInterlocuteurs
- `morphMany` Appel
- `morphMany` RendezVous
- `morphMany` Document
- `morphMany` HistoriqueInteractionUser

---

### 4. **Client** (Personnes clientes)
- États: prospect, en_cours, termine, certifie, abandonne
- Référence unique: ref_client (CLI-YYYYMMDD-XXXXXX)
- Lien CPF: montant_cpf

**Relations:**
- `belongsTo` Partenaire (partenaire_id)
- `belongsTo` Parrain (parrain_id)
- `belongsTo` User (commercial_id)
- `hasMany` Proposition (via ref_client)
- `belongsToMany` Partenaire (relation many-to-many)
- `hasOne` ActiviteVente (via partenaire_id)
- `hasMany` DossierFormation (personne_id)
- `morphMany` RendezVous
- `morphMany` Document

**Note:** Relation avec Partenaire est bidirectionnelle:
- `partenaire_id` FK directe (belongsToMany)
- `belongsToMany` pour relation many-to-many

---

### 5. **Proposition** (Propositions de formation)
- Liée à Client via ref_client (pas ID)
- États: En cours, Terminée, Annulée, Planifiée
- Suivi des heures: nb_heures_formation, heures_realisees, heures_restantes

**Relations:**
- `belongsTo` Client (via ref_client)

---

### 6. **DossierFormation** (Dossiers de formation)
- Statuts: a_venir, en_cours, termine, valide, reporte, interrompu, annule, abandon
- États: brouillon, en_cours, soumis, approuve, rejete, cloture, termine
- Lié à Client (personne_id) et EntiteCommerciale

**Relations:**
- `belongsTo` Client (personne_id)
- `belongsTo` EntiteCommerciale (entite_id)
- `belongsTo` Consultant (consultant_accueil_id, consultant_formateur_id)
- `hasOne` HeuresFormation
- `hasOne` PlanningFormation

---

### 7. **EntiteCommerciale** (Entités commerciales)
- Structure organisationnelle
- Code et nom

**Relations:**
- `hasMany` Consultant
- `hasMany` CampagnePhoning
- `hasMany` Partenaire
- `hasMany` DossierFormation

---

### 8. **CampagnePhoning** (Campagnes d'appels)
- Cibles: prospects, partenaires, clients
- Assignation: GroupeTelepro ou User spécifique
- Critères de filtrage par type de cible

**Relations:**
- `belongsTo` EntiteCommerciale
- `belongsTo` GroupeTelepro
- `belongsTo` User
- `hasMany` Prospect

---

### 9. **GroupeTelepro** (Groupes de téléprospecteurs)
- Organisation des téléprospecteurs par équipes

**Relations:**
- `belongsToMany` User
- `hasMany` CampagnePhoning

---

## Entités de Support

### Activités et Interactions
- **Appel** (morphMany sur Prospect, Partenaire)
- **RendezVous** (morphMany sur Prospect, Partenaire, Client)
- **Document** (morphMany sur Prospect, Partenaire, Client)
- **HistoriqueInteractionUser** (morphMany sur Prospect, Partenaire)

### Partenaires - Détails
- **ContactPartenaire** (contacts du partenaire)
- **AdresseCse** (adresse spécifique CSE)
- **Tarification** (tarifs du partenaire)
- **HistoriqueConseiller** (historique des conseillers)
- **AutresInterlocuteurs** (interlocuteurs additionnels)

### Activités Commerciales
- **ActiviteVente** (statistiques ventes par partenaire)
- **ActivitePermanence** (statistiques permanence)
- **RemboursementEmployeur** (remboursements)

### Formation
- **HeuresFormation** (suivi heures par dossier)
- **PlanningFormation** (planning formation)

### Autres
- **Parrain** (parrains de clients)
- **Consultant** (conseillers/formateurs)
- **Entreprise** (entreprises mères)
- **Opportunite** (opportunités commerciales)
- **Ticket** (tickets support)
- **ReclamationP8** (réclamations)
- **RapportSatisfactionP6** (satisfaction)

---

## Flux Métier Principaux

### 1. Flux Prospection → Partenariat
```
Prospect (QF validé) 
  → convertirEnPartenaire()
  → Partenaire (Signé accord cadre)
  → ContactPartenaire (migration contacts)
```

### 2. Flux Partenariat → Client
```
Partenaire
  → Client (partenaire_id)
  → Proposition (ref_client)
  → DossierFormation (personne_id)
```

### 3. Flux Formation
```
Client
  → Proposition
  → DossierFormation
  → HeuresFormation
  → PlanningFormation
```

### 4. Flux Activités
```
User (Téléprospecteur/Commercial)
  → Appel (sur Prospect/Partenaire)
  → RendezVous (sur Prospect/Partenaire/Client)
  → Document (sur Prospect/Partenaire/Client)
```

---

## Clés de Relation Importantes

### Ref Client vs ID
- **Client.ref_client**: Référence unique utilisée pour lier Proposition
- **Client.id**: ID primaire pour lier DossierFormation
- **Proposition.ref_client**: Lien vers Client (pas ID)

### Partenaire ↔ Client
- **Client.partenaire_id**: FK directe (belongsTo)
- **Client.partenaires()**: belongsToMany (relation many-to-many)
- **Partenaire.clients()**: hasMany
- **Partenaire.personnes()**: hasMany (alias)

### Morph Relations
- **Appel**: appelable_type/id (Prospect, Partenaire)
- **RendezVous**: rdvable_type/id (Prospect, Partenaire, Client)
- **Document**: documentable_type/id (Prospect, Partenaire, Client)
- **HistoriqueInteractionUser**: interactable_type/id (Prospect, Partenaire)

---

## Statuts et Workflows

### Prospect Workflow
```
AC (À contacter)
  ↓
STD_NR (Standard non référencé) / CSE_NR (CSE non référencé)
  ↓
STD_Joint (Standard joint)
  ↓
RP (Réponse positive) / RPC (Réponse positive CSE)
  ↓
QF (Qualifié) → Conversion Partenaire
  ↓
KO (Refus)
```

### Partenaire Workflow
```
À prospecter
  ↓
En cours prospection
  ↓
RDV en cours
  ↓
Signé accord cadre
  ↓
Convention engagement
  ↓
Refus
```

### Client Workflow
```
prospect
  ↓
en_cours
  ↓
termine
  ↓
certifie
  ↓
abandonne
```

### Formation Workflow
```
a_venir
  ↓
en_cours
  ↓
termine / valide
  ↓
reporte / interrompu / annule / abandon
```

---

## Permissions et Rôles

### Rôles User
- **super_admin**: Accès total
- **administrateur**: Administration
- **commercial**: Gestion partenaires et clients
- **teleprospecteur**: Prospection et appels
- **operateur_n1**: Opérations support
- **back_office**: Back-office
- **responsable_plateau**: Supervision

### Accès Panels
- **admin**: Panel principal
- **allopro**: Panel Allopro
- **ns-conseil**: Panel NS Conseil
- **partenaires-pme**: Panel Partenaires PME

---

## Intégrations Externes

### Google
- User.google_token: Authentification
- GoogleToken: Stockage tokens

### Ringover
- User.ringover_user_id: ID utilisateur Ringover
- User.ringover_email: Email Ringover
- Appel: Intégration appels téléphoniques

---

## Données Techniques

### Soft Deletes
Modèles avec soft deletes:
- Partenaire, Client, Prospect, Proposition, DossierFormation
- User, EntiteCommerciale, Consultant, Parrain

### JSON Fields
- Client.extra_data: Données additionnelles
- Proposition.extra_data: Données additionnelles
- User.google_token: Token OAuth

### Enums
- OrganizationType: CSE, Syndicat, EntrepriseDirecte, Association
- OrganizationStatus: Statuts partenaire
- ProspectStatut: Statuts prospect

---

## Index et Performance

### Index Stratégiques
- clients.ref_client (index)
- clients.partenaire_id (index)
- clients.parrain_id (index)
- partenaires.entite_id (index)
- prospects.teleprospecteur_id (index)
- prospects.commercial_id (index)
- propositions.ref_client (index)

### Optimisations
- Nomenclature indexée pour Partenaire (cache en mémoire)
- Scopes optimisés pour KPIs
- Relations chargées avec eager loading quand nécessaire

---

## KPIs et Statistiques

### Prospect KPIs
- Total, Actifs, À relancer, Qualifiés, KO
- Taux qualification, Taux KO
- Répartition par statut, par type

### Partenaire KPIs
- Total, Actifs, Conventionnés
- À relancer (jours sans contact)

### Client KPIs
- Total, Contactables, Non contactables
- Avec propositions, Sans propositions
- Avec CPF, Nouveaux du mois
- Répartition par région

### Proposition KPIs
- Total, Actives, Terminées, Annulées
- En retard, Heures formées, Heures restantes
- Taux completion
- Répartition par formateur, par état

---

## Notes pour l'IA

### Points d'attention
1. **Ref Client**: Les Propositions sont liées aux Clients via ref_client, pas ID
2. **Relation Partenaire-Client**: Double relation (FK + belongsToMany)
3. **Morph Relations**: Plusieurs entités utilisent des relations polymorphiques
4. **Conversion Prospect→Partenaire**: Workflow automatique avec migration des contacts
5. **Nomenclature Partenaire**: Index en mémoire pour performances

### Règles métier
- Un Prospect QF validé peut être converti en Partenaire
- Un Client peut avoir plusieurs Propositions (via ref_client)
- Un Partenaire peut avoir plusieurs Clients (personnes)
- Les activités (Appels, RDV, Documents) sont polymorphiques
- Les statuts suivent des workflows stricts

### Considérations techniques
- Soft deletes activés sur la plupart des modèles
- JSON fields pour données flexibles
- Enums pour statuts typés
- Cache role_cache pour performances User
- Boot hooks pour synchronisation (ActiviteVente, etc.)
