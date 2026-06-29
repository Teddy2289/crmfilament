# Analyse des champs Excel vs CRM

## Vue d'ensemble
Analyse de la correspondance entre les champs des fichiers Excel d'importation et les modèles du CRM pour les Clients et Partenaires.

---

## Clients

### Modèle Client
Champs `$fillable` disponibles :
- source_sheet, ref_client, ref_clients, civilite, prenom, nom_tiers
- email, telephone, adresse, code_postal, ville, region, departement
- date_naissance, entreprise, type_tiers, avis_google
- etat, montant_cpf, ne_plus_contacter
- partenaire_id, parrain_id, commercial_id, notes_commerciales, extra_data

### Importer CRM LIKE
Champs Excel → Client :
- ✅ Civilité → civilite
- ✅ Tiers → nom_tiers
- ✅ Téléphone → telephone
- ✅ Email → email
- ✅ Ville → ville
- ✅ Code postal → code_postal
- ✅ Adresse → adresse
- ✅ Entreprise → entreprise
- ✅ Date de naissance → date_naissance
- ✅ Montant CPF → montant_cpf
- ✅ Ne plus contacter → ne_plus_contacter
- ✅ Avis Google → avis_google

**Statut** : Tous les champs Excel sont présents dans le modèle Client.

### Importer CRM 01FC
Champs Excel → Client :
- ✅ Tiers → nom_tiers
- ✅ Téléphone → telephone
- ✅ Email → email
- ✅ Ville → ville
- ✅ Code postal → code_postal
- ✅ Département → departement
- ✅ Entreprise → entreprise
- ✅ Date de naissance → date_naissance
- ✅ Type du tiers → type_tiers

**Statut** : Tous les champs Excel sont présents dans le modèle Client.

### Importer CRM AOPIA-ABO
Champs Excel → Client :
- ✅ Tiers → nom_tiers
- ✅ Téléphone → telephone
- ✅ Email → email
- ✅ Ville → ville
- ✅ Code postal → code_postal
- ✅ Adresse → adresse
- ✅ Entreprise → entreprise
- ✅ Date de naissance → date_naissance
- ✅ Montant cpf → montant_cpf
- ✅ Ne plus contacter → ne_plus_contacter

**Statut** : Tous les champs Excel sont présents dans le modèle Client.

---

## DossierFormation

### Modèle DossierFormation
Champs `$fillable` disponibles :
- ref_client, intitule_programme, entite_id, personne_id
- montant_ht, montant_cpf, date_vente
- statut_formation, no_dossier_edof, etat
- consultant_accueil_id, consultant_formateur_id

### Importer CRM LIKE
Champs Excel → DossierFormation :
- ✅ Réf. client → ref_client
- ✅ [extrait] → intitule_programme
- ✅ État → etat
- ✅ Statut formation → statut_formation
- ✅ Montant HT → montant_ht
- ✅ Date de vente → date_vente
- ✅ (C) N° dossier EDOF → no_dossier_edof
- ⚠️ Consultant 1er accueil → _consultant_accueil_nom (extra, pas consultant_accueil_id)
- ⚠️ Consultant Formateur → _consultant_formateur_nom (extra, pas consultant_formateur_id)
- ⚠️ _entite_code → extra (pas entite_id)

**Statut** : Les consultants sont stockés par nom dans extra_data au lieu d'être liés par ID. L'entité est stockée par code au lieu d'être liée par ID.

### Importer CRM 01FC
Champs Excel → DossierFormation :
- ✅ Réf. client → ref_client
- ✅ [extrait] → intitule_programme
- ✅ État → etat
- ✅ Statut formation → statut_formation
- ✅ Montant HT → montant_ht
- ✅ Dare de vente / Date de vente → date_vente
- ✅ (C) N° dossier EDOF → no_dossier_edof
- ⚠️ Consultant 1er Accueil → _consultant_accueil_nom (extra, pas consultant_accueil_id)
- ⚠️ Consultant Formateur → _consultant_formateur_nom (extra, pas consultant_formateur_id)
- ⚠️ _entite_code → extra (pas entite_id)

**Statut** : Même problème que CRM LIKE pour les consultants et l'entité.

### Importer CRM AOPIA-ABO
Champs Excel → DossierFormation :
- ✅ Réf. client → ref_client
- ✅ [extrait] → intitule_programme
- ✅ État → etat
- ✅ Statut formation → statut_formation
- ✅ Montant HT → montant_ht
- ✅ Date de vente → date_vente
- ✅ (C) N° dossier EDOF → no_dossier_edof
- ⚠️ Consultant Formateur → _consultant_formateur_nom (extra, pas consultant_formateur_id)
- ⚠️ _entite_code → extra (pas entite_id)

**Statut** : Pas de Consultant 1er accueil dans cet onglet. Même problème pour le consultant formateur et l'entité.

---

## HeuresFormation

### Modèle HeuresFormation
Champs `$fillable` disponibles :
- dossier_id, heures_obligatoires, heures_complementaires, heures_elearning
- total_heures, heures_realisees, heures_restantes

### Importer CRM LIKE
Champs Excel → HeuresFormation :
- ✅ Heures de formation obligatoires → heures_obligatoires
- ✅ Heures de formation complémentaires → heures_complementaires
- ✅ Heures d'E-learning → heures_elearning
- ✅ Nombre d'heures de formation → total_heures
- ✅ Heures réalisées → heures_realisees
- ✅ Heures restantes → heures_restantes

**Statut** : Tous les champs Excel sont présents dans le modèle HeuresFormation.

### Importer CRM 01FC
Champs Excel → HeuresFormation :
- ❌ Heures de formation obligatoires → NON PRÉSENT
- ❌ Heures de formation complémentaires → NON PRÉSENT
- ❌ Heures d'E-learning → NON PRÉSENT
- ✅ Nombre d'heures de formation → total_heures
- ✅ Heures réalisées → heures_realisees
- ✅ Heures restantes → heures_restantes

**Statut** : 3 champs manquants (obligatoires, complémentaires, elearning).

### Importer CRM AOPIA-ABO
Champs Excel → HeuresFormation :
- ✅ Heures de formation obligatoires → heures_obligatoires
- ✅ Heures de formation complémentaires → heures_complementaires
- ❌ Heures d'E-learning → NON PRÉSENT
- ✅ Nombre d'heures de formation → total_heures
- ✅ Heures réalisées → heures_realisees
- ✅ Heures restantes → heures_restantes

**Statut** : 1 champ manquant (elearning).

---

## PlanningFormation

### Modèle PlanningFormation
Champs `$fillable` disponibles :
- dossier_id, date_lancement, date_debut, date_fin_theorique
- date_certification, date_questionnaire_chaud

### Importer CRM LIKE
Champs Excel → PlanningFormation :
- ✅ (F) Date de lancement → date_lancement
- ✅ (C) Date début de formation → date_debut
- ✅ (C) Date de fin formation théorique → date_fin_theorique
- ✅ Date certification → date_certification
- ✅ Date envoi questionnaire à chaud → date_questionnaire_chaud

**Statut** : Tous les champs Excel sont présents dans le modèle PlanningFormation.

### Importer CRM 01FC
Champs Excel → PlanningFormation :
- ✅ (F) Date de lancement → date_lancement
- ✅ (C) Date début de formation → date_debut
- ✅ (C) Date de fin formation théorique → date_fin_theorique
- ✅ Date certification → date_certification
- ✅ Date envoi questionnaire à chaud → date_questionnaire_chaud

**Statut** : Tous les champs Excel sont présents dans le modèle PlanningFormation.

### Importer CRM AOPIA-ABO
Champs Excel → PlanningFormation :
- ✅ Date de lancement → date_lancement
- ✅ (C) Date début de formation → date_debut
- ✅ (C) Date de fin formation théorique → date_fin_theorique
- ✅ Date certification → date_certification
- ✅ Date envoi questionnaire à chaud → date_questionnaire_chaud

**Statut** : Tous les champs Excel sont présents dans le modèle PlanningFormation.

---

## Partenaires

### Modèle Partenaire
Champs `$fillable` disponibles (partiel) :
- nom, statut, type, origine, annee_signature, date_signature
- nb_ventes, derniere_vente, ventes_2025, ventes_2026
- derniere_permanence, nbre_perm_2025, nbre_perm_2026
- entite_id, entreprise_id, commercial_id, conseiller_id
- parrain_partenaire_id, prospect_id, entreprise_mere_id
- telephone, email, adresse, code_postal, ville, pays
- secteur_activite, nb_salaries (effectif)
- notes_commerciales, ne_plus_contacter, extra_data

### Importer Partenaire (43 colonnes)
Champs Excel → Partenaire :
- ✅ Entité → entite_code (extra)
- ✅ ENTREPRISE → entreprise (extra)
- ✅ NOM RETENU → nom
- ✅ Nb salariés → nb_salaries
- ✅ Statut → statut
- ✅ Année → annee_signature
- ✅ Date signature → date_signature
- ✅ Nb ventes → nb_ventes
- ✅ Dernière vente → derniere_vente
- ✅ Ventes 2025 → ventes_2025
- ✅ Ventes 2026 → ventes_2026
- ✅ Dernière perm. → derniere_permanence
- ✅ Nbre perm. 2025 → nbre_perm_2025
- ✅ Nbre perm. 2026 → nbre_perm_2026
- ✅ TYPE → type
- ✅ Origine → origine
- ✅ PARRAIN/MARRAINE → parrain (extra)
- ✅ Conseiller → conseiller (extra)
- ✅ Ancien conseiller → ancien_conseiller (extra)
- ✅ Mandataire/VDI → statut_vdi (extra)
- ✅ Dept conseiller → dept_conseiller (extra)
- ⚠️ Adresse CSE → adresse_cse (extra, pas dans Partenaire)
- ⚠️ Code postal CSE → cp_cse (extra, pas dans Partenaire)
- ⚠️ Commune CSE → commune_cse (extra, pas dans Partenaire)
- ✅ Nom du contact → contact_nom (extra)
- ✅ Prénom du contact → contact_prenom (extra)
- ✅ Fonction du contact → contact_fonction (extra)
- ✅ Mail → email
- ✅ Tél portable → telephone
- ✅ Tél fixe → telephone_fixe (extra)
- ✅ Préf. contact → contact_preference (extra)
- ✅ Autres interlocuteurs → autres_interlocuteurs (extra)
- ✅ Parrainage entreprise ? → parrainage_entreprise (extra)
- ✅ Possibilité permanence ? → possibilite_perm (extra)
- ✅ Réplicable → replicable (extra)
- ✅ Prix du PC → prix_pc (extra)
- ✅ Aopia (part) → part_aopia (extra)
- ✅ Tarifs → tarifs (extra)
- ✅ Part CSE → part_cse (extra)
- ✅ Part salarié → part_salarie (extra)
- ✅ Tarifs affichage comm → tarifs_affichage (extra)
- ✅ Adresse facturation → adresse_facturation (extra)
- ✅ COMMENTAIRES → commentaires (extra)

**Statut** : De nombreux champs sont stockés dans extra_data au lieu d'avoir des colonnes dédiées dans le modèle Partenaire. Les informations CSE (Adresse CSE, Code postal CSE, Commune CSE) ne sont pas dans le modèle Partenaire mais pourraient être dans un modèle AdresseCse.

---

## Résumé des incohérences

### Critiques
1. **DossierFormation** : Les consultants sont stockés par nom dans extra_data au lieu d'être liés par ID aux consultants réels.
2. **DossierFormation** : L'entité est stockée par code dans extra_data au lieu d'être liée par ID à EntiteCommerciale.
3. **HeuresFormation** : CRM 01FC manque 3 champs (obligatoires, complémentaires, elearning).
4. **HeuresFormation** : CRM AOPIA-ABO manque 1 champ (elearning).
5. **Partenaire** : De nombreux champs sont dans extra_data au lieu d'avoir des colonnes dédiées.

### Recommandations
1. **Créer une logique de liaison** pour convertir les noms de consultants en IDs de consultants réels.
2. **Créer une logique de liaison** pour convertir les codes d'entité en IDs d'EntiteCommerciale.
3. **Accepter les champs manquants** dans CRM 01FC et AOPIA-ABO (ces onglets n'ont pas ces colonnes).
4. **Évaluer** si les champs extra_data des partenaires doivent être déplacés vers des colonnes dédiées ou rester dans extra_data.
5. **Vérifier** si le modèle AdresseCse existe et est utilisé pour les informations CSE des partenaires.
