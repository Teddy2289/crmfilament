# Manuel d'utilisation - CRM AOPIA / LIKE Formation

**Version**: 1.1
**Date**: 26 Juin 2026
**Panel principal**: Ns Conseil (`/ns-conseil`)
**Administration**: Super Admin (`/super-admin`)

---

## 1. Connexion

Acceder au CRM via l'URL locale ou production fournie par l'administrateur.

URLs locales usuelles:

| Panel | URL |
|---|---|
| Ns Conseil | `http://crmfilament.test/ns-conseil` |
| AlloPro | `http://crmfilament.test/allopro` |
| Super Admin | `http://crmfilament.test/super-admin` |

L'utilisateur se connecte avec son email professionnel et son mot de passe.

---

## 2. Dashboard

Le dashboard affiche les indicateurs selon le role de l'utilisateur:

- prospects a traiter;
- rappels du jour;
- appels recents;
- RDV a venir;
- indicateurs commerciaux;
- alertes Team Leader;
- widgets Ringover ou calendrier selon configuration.

La visibilite depend des droits du role et du profil CRM.

---

## 3. Prospects

Chemin:

```text
Ns Conseil > Prospects
```

Actions possibles selon droits:

- lister les prospects;
- ouvrir une fiche;
- creer une fiche;
- modifier une fiche;
- enregistrer des appels;
- planifier un rappel;
- valider QF;
- convertir en partenaire.

Statuts principaux:

| Code | Sens |
|---|---|
| `AC` | A contacter |
| `STD_NR` | Standard non repondu |
| `STD_Joint` | Standard joint |
| `CSE_NR` | CSE non joint |
| `RP` | Rappel planifie |
| `RPC` | RDV a planifier |
| `KO` | Hors cible / refus |
| `QF` | Qualifie apres validation |

La validation QF reste reservee aux profils autorises, generalement Team Leader ou administrateur.
La conversion en partenaire est disponible uniquement apres QF valide. Une fois converti, le prospect quitte la liste active, reste archive, et le partenaire garde le lien vers la fiche source.

---

## 4. Workflow phoning

Chemin:

```text
Ns Conseil > Workflow Phoning
```

Le workflow permet de:

1. charger une file de prospects;
2. enregistrer le resultat d'appel;
3. appliquer un statut phoning;
4. programmer un rappel;
5. creer un RDV;
6. generer les fiches et emails associes selon le cas.

Les statuts phoning sont configurables dans les dictionnaires CRM.

Regle metier telephonie:

```text
DEP_XX + tag statut obligatoire par appel
```

---

## 5. Partenaires

Chemin:

```text
Ns Conseil > Partenaires
```

Une fiche partenaire contient:

- informations generales;
- type et statut;
- commercial et conseiller;
- adresse et departement;
- nomenclature interne automatique `[Type] [Entreprise ou nom] [Ville]`;
- informations CSE;
- informations syndicat;
- contacts partenaires;
- activites, permanences, ventes;
- historique des interactions.

Cycle courant:

```text
A prospecter -> En cours -> RDV en cours -> Signe accord cadre -> Convention engagement
```

---

## 6. Clients

Chemin:

```text
Ns Conseil > Clients
```

Les clients sont principalement importes depuis Dolibarr.

La fiche client contient:

- reference client;
- identite;
- telephone, email, adresse;
- entreprise;
- partenaire rattache;
- statut de contact;
- propositions et formations;
- parrainage.

Le filtre `Partenaire non rattache` affiche les clients importes dont la nomenclature partenaire source n'a pas ete reconnue automatiquement. Ces fiches doivent etre rapprochees manuellement avec le bon partenaire.

Les droits peuvent empecher la creation ou la modification de certains champs sensibles.

---

## 7. Opportunites

Chemin:

```text
Ns Conseil > Opportunites
```

Une opportunite sert a suivre un signal faible avant qu'il devienne un prospect.

Actions possibles:

- creer une opportunite;
- passer en evaluation;
- qualifier;
- convertir en prospect lorsque l'opportunite est qualifiee;
- marquer perdue avec une raison.

Apres conversion, l'opportunite quitte la liste active, reste archivee dans l'onglet `Converties`, et le prospect conserve le lien vers l'opportunite d'origine.

---

## 8. Appels et RDV

### Appels

Les appels peuvent etre lies a plusieurs entites: prospect, partenaire, client ou opportunite.

Informations principales:

- date et heure;
- resultat;
- duree;
- statut phoning;
- audio si disponible;
- agent;
- campagne.

### Rendez-vous

Un RDV contient:

- date et heure;
- lieu structure;
- interlocuteur;
- commercial;
- type et statut;
- synchronisation calendrier si active.

---

## 9. Historique des interactions

Les fiches principales affichent ou enregistrent l'historique selon les resources:

- consultation;
- creation;
- modification;
- appel;
- RDV;
- email;
- conversion.

Cet historique sert a la tracabilite commerciale.

---

## 10. Base de connaissances

Chemin:

```text
Ns Conseil > Administration > Base de connaissances
```

Le module regroupe les procedures, scripts, FAQ/objections, modeles mails et modeles de fiche recap.

Droits principaux:

- teleprospecteur: lecture uniquement;
- Team Leader: lecture et edition;
- commercial: aucun acces;
- administrateur: acces total.

Les documents peuvent etre classes par type, categorie, visibilite publique et ordre d'affichage. Le fichier joint est obligatoire a la creation et reste optionnel lors de la modification des documents seedes sans fichier.

---

## 11. Administration des droits d'acces

Chemin:

```text
Super Admin > Roles & Permissions
```

Cette interface permet de creer ou modifier un role.

### 11.1 Mode Tout

Le mode `Tout` donne au role toutes les permissions du catalogue CRM:

- toutes les entites;
- tous les modules;
- tous les champs;
- toutes les actions connues.

A utiliser pour les administrateurs ou super administrateurs uniquement.

### 11.2 Mode Selectif par entite/module

Le mode selectif affiche deux onglets:

| Onglet | Usage |
|---|---|
| Entites et modules | cocher les modules et actions autorises |
| Champs | cocher les droits champ par champ |

Exemple module:

- `AOPIA - Prospects - Lister`
- `AOPIA - Prospects - Voir`
- `AOPIA - Prospects - Creer`
- `AOPIA - Prospects - Modifier`
- `AOPIA - Prospects - Valider QF`

### 11.3 Droits par champ

Actions disponibles:

| Action | Autorise |
|---|---|
| Voir | lecture du champ |
| Creer | saisie du champ pendant la creation |
| Modifier | modification du champ apres creation |
| Flux | usage du champ dans les workflows |
| Tout | toutes les actions du champ |

Exemple:

- Autoriser `Nom - Voir` et `Nom - Modifier` permet de voir et modifier le nom.
- Autoriser `Email - Voir` seulement permet de lire l'email sans le modifier.
- Autoriser `Statut - Flux` permet de l'utiliser dans un workflow si la resource le supporte.

Si aucun droit champ n'est configure pour une entite, les droits module restent le comportement principal.

Les champs relationnels affiches dans les listes ou fiches, par exemple `Commercial - Nom`, suivent le droit du champ source comme `commercial_id`.

---

## 12. Themes

Chemin:

```text
Super Admin > Themes
```

Le theme par defaut est le theme natif Filament. Le style EspoCRM historique peut etre active sur un theme specifique avec le champ `Style interface = EspoCRM legacy`.

Options principales:

- `Style interface`: `Filament natif` ou `EspoCRM legacy`;
- `Appliquer les couleurs du theme`: applique les couleurs choisies au panel;
- `CSS personnalise`: ajoute du CSS seulement pour le theme selectionne.

Pour revenir au rendu Filament standard, selectionner `Filament natif par defaut` dans le widget de theme ou utiliser un theme avec `Style interface = Filament natif` et couleurs desactivees.

---

## 13. Tests par role a effectuer

Apres modification d'un role, tester au minimum:

1. Connexion avec un utilisateur qui possede le role.
2. Acces au bon panel.
3. Acces ou blocage des modules attendus.
4. Creation d'une fiche autorisee.
5. Edition d'un champ autorise.
6. Tentative d'edition d'un champ interdit.
7. Verification du workflow si le droit `flux` est utilise.

Tests automatises disponibles:

```powershell
php artisan test --filter RoleAccessRightsTest
npx playwright test tests/e2e/role-field-visibility.spec.js
```

---

## 14. Imports

Les imports se font depuis les actions des resources Filament ou les commandes dediees selon le cas.

| Import | Emplacement |
|---|---|
| Prospects Top 500 | Resource Prospects |
| Partenaires MAJ | Resource Partenaires |
| Clients Dolibarr | Resource Clients / commande historique |

Les imports clients rattachent automatiquement le client au partenaire si la valeur source correspond exactement a la nomenclature interne, au nom ou au nom retenu du partenaire. Les valeurs non reconnues restent conservees en metadata pour traitement manuel.

Commande clients historique:

```powershell
php artisan dolibarr:import-clients path\to\export.xlsx
```

---

## 15. FAQ

### Je ne vois pas un module

Verifier le role dans `Super Admin > Roles & Permissions`. Le module doit etre coche ou le role doit etre en mode `Tout`.

### Je vois une fiche mais un champ ne s'enregistre pas

Verifier les droits champ `create` ou `edit` sur l'entite concernee.

### Je peux lire un champ mais pas le modifier

C'est normal si seul le droit `show` est coche.

### Je ne peux pas valider QF

Verifier le droit module `prospects.valider_qf` et le profil CRM associe.

### Un import ne reconnait pas la feuille Excel

Verifier le nom exact de la feuille attendue et le type d'import selectionne.

---

## 14. Support

Pour un probleme metier, contacter le responsable CRM interne. Pour un probleme technique, fournir:

- URL du panel;
- role utilisateur;
- action tentee;
- message d'erreur;
- fichier Excel si le probleme concerne un import.
