# Telephonie - Ringover

**Date**: 27 Juin 2026
**Statut**: document de cadrage et d'implementation Laravel Ringover.

---

## Situation actuelle

Le CDC et les workflows AOPIA mentionnent Ringover et la regle:

```text
DEP_XX + tag statut obligatoire par appel
```

Dans le code Laravel, les elements actifs sont:

- `app/Services/RingoverService.php`
- `app/Console/Commands/SyncRingoverCalls.php`
- `app/Filament/NsConseil/Pages/RingoverDashboard.php`
- widgets Ringover du panel Ns Conseil
- champs `ringover_*` sur `app/Models/Appel.php`, `app/Models/Ticket.php` et `app/Models/User.php`
- setting CRM `prospection.ringover_rule`

Les anciennes colonnes de telephonie sont renommees vers `ringover_*` par migration de transition.

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

## Configuration

Variables `.env` attendues:

```env
RINGOVER_API_TOKEN=
RINGOVER_AUTH_SCHEME=Bearer
RINGOVER_BASE_URL=https://public-api.ringover.com/v2
RINGOVER_TIMEOUT=10
RINGOVER_DIAL_URL_TEMPLATE=tel:{phone}
```

`RINGOVER_DIAL_URL_TEMPLATE` doit etre ajuste avec l'URL click-to-call Ringover du compte si l'ouverture directe du softphone est souhaitee.

---

## A completer si synchronisation temps reel demandee

1. controller webhook public avec validation de signature;
2. normalisation des evenements vers `appels`;
3. mapping utilisateur Ringover -> User Laravel;
4. test d'idempotence webhook;
5. consommation des tags Ringover dans le workflow phoning.

---

## Verification

Commandes utiles:

```powershell
rg -n "Ringover|ringover" app routes database tests
php artisan test --filter RoleAccessRightsTest
```
