# Etat d'avancement du projet CRM Filament

**Projet**: CRM AOPIA / LIKE Formation / AlloPro
**Stack actuelle**: Laravel 12, PHP 8.3, Filament 3.3
**Derniere mise a jour**: 26 Juin 2026

---

## Resume executif

Le projet est maintenant une implementation Laravel/Filament du CDC EspoCRM initial. Les documents EspoCRM restent des sources metier historiques, mais le developpement courant se fait dans `app/`, `database/`, `resources/`, `routes/` et `tests/`.

| Domaine | Etat | Notes |
|---|---:|---|
| Prospects et phoning CSE | Operationnel | Workflow, statuts, appels, QF, rappels et conversion partenaire |
| Partenaires | Operationnel | Fiche partenaire, contacts, tables satellites, activites et imports MAJ |
| Clients Dolibarr | Operationnel | Import multi-feuilles et deduplication par reference client/fallback identite |
| Opportunites | Operationnel avec alignements restants | CDC conserve comme reference pour statuts/sources |
| RDV, appels, emails, fiches | Operationnel | Fiches PDF/Word, mails, ICS, calendrier selon configuration |
| Droits d'acces | Operationnel | Gestion par role, module et champ |
| Tests droits | Operationnel | `RoleAccessRightsTest` couvre full/selectif/champs |
| Documentation | En cours de normalisation | Fichiers Markdown actifs remis a jour en UTF-8 |

---

## Points livres recemment

### 1. Interface de gestion des droits

La gestion des droits est disponible dans `Super Admin > Roles` via `RoleResource`.

Un role peut etre configure en:

- `Acces complet`: synchronise toutes les permissions du catalogue.
- `Acces selectif`: selection manuelle des droits modules et des droits champs.

Les droits sont centralises dans `app/Support/AccessRightsCatalog.php` et utilises par les resources via `app/Support/UsesResourcePermissions.php`.

### 2. Droits par entite/module

Le catalogue gere les droits par module pour les panels:

- Ns Conseil: prospects, partenaires, clients, opportunites, appels, RDV, campagnes, statuts, imports.
- AlloPro: tickets et ressources associees.
- Super Admin: users, roles, profils CRM, parametres et dictionnaires.

### 3. Droits par champ

Les droits champ utilisent le format:

```text
fields.{entity}.{field}.{action}
```

Actions supportees:

| Action | Role |
|---|---|
| `show` | lire le champ |
| `create` | renseigner le champ a la creation |
| `edit` | modifier le champ |
| `flux` | utiliser le champ dans les flux et workflows |
| `all` | autoriser toutes les actions du champ |

Les formulaires filtrent les donnees non autorisees a la creation et a l'edition pour Prospects, Partenaires, Clients et Tickets AlloPro.

### 4. Tests par role

Le test `tests/Feature/RoleAccessRightsTest.php` verifie:

- role en acces complet;
- role selectif Ns Conseil;
- role selectif AlloPro;
- presence du catalogue de droits champs;
- actions `show`, `create`, `edit`, `flux`, `all`;
- filtrage des donnees interdites a la creation et a l'edition.

---

## Imports Excel

| Import | Etat | Implementation |
|---|---:|---|
| Prospects Top 500 | Operationnel | `ProspectImporter`, `ProspectImportResolver` |
| Partenaires MAJ | Operationnel | `PartenaireImportResolver`, feuille `MAJ` |
| Clients Dolibarr | Operationnel | `BaseClientImporter`, `CrmLikeImporter`, `CrmAopiaAboImporter`, `Crm01FcImporter` |

Les fichiers source de test sont dans `directive/archive/`. Les regles de mapping sont documentees dans `directive/specs/Modele_Projet_AOPIA_Laravel.md`.

---

## Documentation active

| Fichier | Etat actuel |
|---|---|
| `README.md` | Mis a jour: stack, panels, droits, imports, commandes |
| `MANUEL.md` | A maintenir comme manuel technique courant |
| `directive/specs/Modele_Projet_AOPIA_Laravel.md` | Source principale de mapping CDC vers Laravel |
| `directive/specs/MANUEL_UTILISATION.md` | Manuel utilisateur Ns Conseil |
| `directive/specs/Champs_Requis_Par_Entite.md` | Champs requis et droits par champ |
| `tests/e2e/README.md` | Guide Playwright actuel |

Les fichiers dans `directive/archive/` restent historiques et ne doivent pas etre corriges comme documentation courante, sauf demande explicite.

---

## Backlog priorise

| Priorite | Sujet | Notes |
|---|---|---|
| P0 | Verifier l'application visuelle des droits `show` dans toutes les infolists/tables | Le filtrage create/edit est en place; l'affichage peut etre durci resource par resource |
| P0 | Finaliser la documentation utilisateur des roles | Ajouter captures ou parcours reels si besoin |
| P1 | Alignement final Opportunites CDC | Statuts/sources a confirmer avec metier |
| P1 | Base de connaissances | Le besoin existe dans le CDC, implementation a confirmer |
| P1 | Reporting hebdomadaire complet | Verifier le contenu final attendu par profil |
| P2 | Outlook/Microsoft Graph | Connecteur present cote dependances, besoin metier a confirmer |

---

## Commandes de verification recommandees

```powershell
php artisan test --filter RoleAccessRightsTest
php artisan test
npm run e2e
npm run build
```

---

## Conclusion

L'etat courant du CRM est exploitable pour les modules centraux AOPIA: prospects, partenaires, clients, phoning, imports et administration des droits. Le prochain effort utile est de verrouiller l'affichage des champs (`show`) dans toutes les vues utilisateur, puis d'ajouter des parcours de test navigateur pour un role complet et un role selectif.
