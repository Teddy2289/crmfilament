# Guide de developpement - traduction EspoCRM vers Laravel

**Date**: 26 Juin 2026
**Statut**: le guide EspoCRM original est historique. Le developpement courant se fait dans Laravel/Filament.

---

## Regle principale

Ne pas ajouter de fichiers `custom/Espo/...` pour le projet `crmfilament`, sauf demande explicite de travailler sur une instance EspoCRM separee.

Pour le projet actuel, utiliser:

| Besoin | Emplacement Laravel |
|---|---|
| Model | `app/Models` |
| Resource Filament Ns Conseil | `app/Filament/NsConseil/Resources` |
| Resource Filament AlloPro | `app/Filament/Allopro/Resources` |
| Resource Super Admin | `app/Filament/SuperAdmin/Resources` |
| Service metier AOPIA | `app/Services/Aopia` |
| Service CRM transversal | `app/Services/Crm` |
| Permission / droits | `app/Support/AccessRightsCatalog.php` |
| Seeder | `database/seeders` et `database/seeders/data` |
| Migration | `database/migrations` |
| Test backend | `tests/Feature` |
| Test navigateur | `tests/e2e` |

---

## Ajouter ou modifier une fonctionnalite

1. Lire le model, la resource Filament et le service existant.
2. Verifier les directives dans `directive/specs`.
3. Ajouter la logique metier dans un service, enum, seeder ou setting quand c'est possible.
4. Garder la resource Filament comme couche UI, pas comme source principale de regles metier.
5. Mettre a jour le catalogue de droits si une nouvelle entite ou un nouveau champ doit etre controle par role.
6. Ajouter un test cible.

---

## Ajouter une entite

Etapes recommandees:

```powershell
php artisan make:model NomEntite -m
php artisan make:filament-resource NomEntite --panel=ns-conseil
php artisan test --filter NomEntiteTest
```

Adapter selon le panel:

- `--panel=ns-conseil`
- `--panel=allopro`
- `--panel=super-admin`

---

## Ajouter des droits

1. Ajouter le module ou champ dans `AccessRightsCatalog`.
2. Laisser `RolesAndPermissionsSeeder` creer les permissions.
3. Verifier `RoleResource` si l'affichage necessite un libelle particulier.
4. Ajouter ou adapter `RoleAccessRightsTest`.

Commande:

```powershell
php artisan test --filter RoleAccessRightsTest
```

---

## Ajouter un import Excel

1. Placer l'importeur dans la resource concernee, dossier `Import`.
2. Utiliser PhpSpreadsheet.
3. Centraliser le mapping dans un importer/resolver.
4. Gerer deduplication et rapport erreurs.
5. Tester avec les fichiers dans `directive/archive`.

---

## Commandes utiles

```powershell
php -l app\Path\ChangedFile.php
php artisan test --filter SomeFocusedTest
php artisan test
npm run e2e
npm run build
rg -n "motif de recherche" app tests database directive
```
