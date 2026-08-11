# Decoupage de la fiche partenaire - reference Laravel

**Date**: 26 Juin 2026
**Statut**: traduction Laravel de l'ancienne specification EspoCRM `AccountDetails`

---

## Objectif historique

La specification initiale EspoCRM proposait de decouper la table `Account` pour eviter l'erreur MySQL `Row size too large (> 8126)`.

Dans le projet Laravel actuel, il ne faut pas creer `custom/Espo/Custom/Resources/metadata/entityDefs/AccountDetails.json`. Le decoupage est traite par des models et tables satellites autour de `partenaires`.

---

## Mapping Laravel actuel

| Besoin EspoCRM historique | Implementation Laravel |
|---|---|
| Compte partenaire principal | `partenaires` / `App\Models\Partenaire` |
| Contacts dirigeants, CSE, syndicat | `contact_partenaires`, `autres_interlocuteurs` |
| Adresse CSE separee | `adresse_cses` |
| Tarification | `tarifications` |
| Activite ventes | `activite_ventes` |
| Activite permanences | `activite_permanences` |
| Remboursements employeur | `remboursements_employeur` |
| Historique conseiller | `historique_conseillers` |

---

## Regle de developpement

Pour une nouvelle information partenaire:

1. verifier si elle appartient a la fiche `partenaires`;
2. si elle est volumineuse ou repetable, preferer une table satellite;
3. exposer la relation dans `PartenaireResource`;
4. ajouter le champ au catalogue de droits si l'information doit etre controlee par role;
5. ajouter un test si la regle de creation, edition ou import change.

---

## Fichiers utiles

- `app/Models/Partenaire.php`
- `app/Filament/NsConseil/Resources/PartenaireResource.php`
- `app/Filament/NsConseil/Resources/PartenaireResource/Import/`
- `app/Support/AccessRightsCatalog.php`
