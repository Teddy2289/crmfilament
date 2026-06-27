# Telephonie - Ringover / Aircall

**Date**: 26 Juin 2026
**Statut**: document de cadrage. L'implementation Laravel actuelle est principalement Aircall, avec une regle metier Ringover conservee dans les settings CRM.

---

## Situation actuelle

Le CDC et les workflows AOPIA mentionnent Ringover et la regle:

```text
DEP_XX + tag statut obligatoire par appel
```

Dans le code Laravel actuel, les elements implementes visibles sont surtout:

- `app/Services/AircallService.php`
- `app/Console/Commands/SyncAircallCalls.php`
- `app/Filament/NsConseil/Pages/AircallDashboard.php`
- widgets Aircall du panel Ns Conseil
- champs Aircall sur `app/Models/Appel.php`
- setting CRM `prospection.ringover_rule`

Ce document ne doit donc pas etre lu comme une preuve que toute l'integration Ringover EspoCRM historique est active dans Laravel.

---

## Regles metier a conserver

| Regle | Usage |
|---|---|
| Tag departement `DEP_XX` | rattacher l'appel a une zone |
| Tag statut | alimenter `statut_phonings` et le workflow |
| Audio appel | requis pour QF selon workflow |
| Historique appel | cree dans `appels` |
| Agent | rattachement a l'utilisateur CRM si possible |

---

## Si Ringover doit etre implemente en Laravel

Prevoir:

1. service API Ringover equivalent a `AircallService`;
2. configuration `.env` pour API key et webhook secret;
3. controller webhook public avec validation signature;
4. normalisation des evenements vers `appels`;
5. mapping utilisateur Ringover -> User Laravel;
6. test d'idempotence webhook;
7. mise a jour du workflow phoning pour consommer les tags.

---

## Verification actuelle

Commandes utiles:

```powershell
rg -n "Ringover|Aircall|Telephony" app routes database tests
php artisan test --filter RoleAccessRightsTest
```
