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
RINGOVER_WEBHOOK_SECRET=
```

`RINGOVER_DIAL_URL_TEMPLATE` doit etre ajuste avec l'URL click-to-call Ringover du compte si l'ouverture directe du softphone est souhaitee.

---

## Synchronisation temps reel

Le webhook Laravel est disponible sur:

```text
/api/ringover/webhook
```

Protection:

- si `RINGOVER_WEBHOOK_SECRET` est vide, le webhook accepte les requetes;
- si `RINGOVER_WEBHOOK_SECRET` est renseigne, le secret doit etre transmis via `X-Ringover-Webhook-Secret`, `X-Webhook-Secret`, bearer token ou query `secret`.

Normalisation:

- `RingoverCallSyncService` centralise la creation/mise a jour des appels pour la commande et le webhook;
- `RingoverTagService` extrait `DEP_XX` et le statut CSE;
- `RingoverUserMapper` mappe `ringover_user_id` / `ringover_email` vers `users`;
- les champs `ringover_tags`, `ringover_department_tag`, `ringover_status_tag`, `ringover_tag_validation`, `ringover_tag_is_complete`, `ringover_payload`, `ringover_synced_at`, `ringover_webhook_received_at` et `ringover_sync_source` tracent la synchronisation.

Le dashboard Ringover affiche un diagnostic:

- URL webhook;
- token API et secret webhook configures ou non;
- appels Ringover synchronises;
- appels avec tags complets ou incomplets;
- utilisateurs Ringover mappes ou non mappes.

Les utilisateurs CRM exposent les champs `ringover_user_id` et `ringover_email` dans Super Admin > Utilisateurs.

---

## Verification

Commandes utiles:

```powershell
rg -n "Ringover|ringover" app routes database tests
php artisan test --filter RingoverAdvancedIntegrationTest
php artisan test --filter RingoverIntegrationTest
php artisan test --filter RoleAccessRightsTest
```
