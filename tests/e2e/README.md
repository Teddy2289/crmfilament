# Tests E2E Playwright

Ce dossier contient les tests navigateur Playwright du panel NS Conseil.

## Lancer les tests

```powershell
npm run e2e
```

La configuration prepare une SQLite dediee (`database/playwright.sqlite`) avec `migrate:fresh --seed`, puis demarre `php -S` sur `127.0.0.1:8001` si `PLAYWRIGHT_BASE_URL` n'est pas defini.

## Premiere installation

```powershell
npm run e2e:install
```

## Identifiants E2E

Par defaut, les tests authentifies utilisent l'utilisateur seede par `UsersSeeder`:

```text
a.florek@ns-conseil.com / changeme123
```

Pour utiliser un autre compte:

```powershell
$env:PLAYWRIGHT_E2E_EMAIL = "user@example.com"
$env:PLAYWRIGHT_E2E_PASSWORD = "secret"
npm run e2e
```

## Variables utiles

- `PLAYWRIGHT_BASE_URL`: cible une application deja lancee.
- `PLAYWRIGHT_DB_DATABASE`: change le fichier SQLite E2E.
- `PLAYWRIGHT_REUSE_SERVER=1`: autorise la reutilisation d'un serveur deja lance.
- `PLAYWRIGHT_SKIP_DB_PREP=1`: saute `migrate:fresh --seed`.
- `PLAYWRIGHT_START_SERVER=0`: empeche Playwright de demarrer Laravel.
- `PLAYWRIGHT_PORT=8002`: change le port du serveur Laravel lance par Playwright.
