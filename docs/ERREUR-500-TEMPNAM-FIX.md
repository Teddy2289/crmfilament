# Analyse de l'Erreur 500 - `tempnam(): file created in the system's temporary directory`

## Date : 2026-08-13
## Statut : RÉSOLU

---

## Symptôme
```
ErrorException: tempnam(): file created in the system's temporary directory 
(View: .../vendor/filament/actions/resources/views/components/action.blade.php)
```

Code d'erreur : HTTP 500

---

## Cause Racine

L'erreur se produit lors de la compilation des vues Blade par Laravel/Livewire/Filament.

### Stack Trace Clé :
```
1. Illuminate\Filesystem\Filesystem::replace() → tempnam()
2. Illuminate\View\Component::createBladeViewFromString()
3. Filament → action.blade.php compilation
```

### Pourquoi ?

Laravel utilise `tempnam()` pour créer des fichiers temporaires lors de la compilation des vues. Lorsque :

1. **Répertoire `/tmp` système** n'est pas accessible ou writable par le processus PHP
2. **OU** Les permissions du répertoire `/tmp` ne permettent pas au processus de créer des fichiers
3. **OU** La variable d'environnement `TMPDIR` n'est pas définie correctement

PHP émet un avertissement (converti en ErrorException) au lieu de créer le fichier.

---

## Problèmes Observés

### 1. Permissions Incohérentes du Storage
```
storage/framework/cache/    → owner: mbl
storage/framework/views/    → owner: mbl
storage/framework/sessions/ → owner: mbl
storage/backups/            → owner: mbl
```

Alors que le projet est exécuté par l'utilisateur `contact`, causant des conflits.

### 2. Configuration de Cache
```env
CACHE_STORE=array  # ← Stockage en mémoire, problématique
# CACHE_STORE=file  # ← Commenté
```

### 3. Accès au Répertoire Système `/tmp`
- Le répertoire n'est pas défini explicitement pour Laravel
- PHP cherche à utiliser le `/tmp` système
- Les permissions peuvent être restrictives en fonction de l'utilisateur d'exécution

---

## Solutions Appliquées

### ✅ Étape 1 : Corriger les Permissions du Storage
```bash
sudo chown -R contact:contact /home/contact/web/manage.ns-conseil.com/storage/
sudo chmod -R g+w /home/contact/web/manage.ns-conseil.com/storage/
```

### ✅ Étape 2 : Nettoyer les Caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### ✅ Étape 3 : Créer un Répertoire Temporaire Dédié
```bash
mkdir -p /home/contact/web/manage.ns-conseil.com/storage/tmp
chmod 777 /home/contact/web/manage.ns-conseil.com/storage/tmp
```

### ✅ Étape 4 : Configurer PHP pour Utiliser le Répertoire Local
Modifié `/public/index.php` :
```php
// Set custom temp directory to avoid permission issues with system /tmp
@putenv('TMPDIR=' . realpath(__DIR__.'/../storage/tmp'));
ini_set('upload_tmp_dir', realpath(__DIR__.'/../storage/tmp'));
```

### ✅ Étape 5 : Ajouter Configuration Filesystem
Mis à jour `/config/filesystem.php` avec la section temporaire.

---

## Vérifications

- ✅ Les permissions de `storage/` sont maintenant cohérentes
- ✅ Laravel dispose d'un répertoire temporaire accessible
- ✅ Les vues compilées seront stockées dans `storage/framework/views/`
- ✅ Les fichiers temporaires seront créés dans `storage/tmp/`

---

## Recommandations Additionnelles

### 1. Configuration de Cache pour Production
Remplacer dans `.env` :
```env
# De :
CACHE_STORE=array

# À :
CACHE_STORE=database
# OU
CACHE_STORE=file
```

### 2. Nettoyage Régulier
```bash
# Nettoyer les fichiers temporaires anciens
find storage/tmp -type f -mtime +7 -delete

# Nettoyer les vues compilées
php artisan view:clear
```

### 3. Monitoring
- Surveiller la taille de `storage/framework/views/`
- Mettre en place un cron pour nettoyer les fichiers temporaires
- Vérifier les permissions du storage mensuellement

### 4. Fichiers à Vérifier en Production
- `.env` - Configuration correcte
- `/public/index.php` - Directive TMPDIR
- `/config/filesystem.php` - Configuration complète
- Permissions de `/storage/` - Propriétaire et groupe corrects

---

## Points de Contact

Pour toute modification future concernant :
- Le répertoire temporaire
- Les permissions du storage
- La configuration de cache
- Les permissions des fichiers compilés

Référer à ce document.
