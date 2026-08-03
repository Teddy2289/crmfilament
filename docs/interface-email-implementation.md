# Interface Email - Documentation d'Implémentation

## Vue d'ensemble

Le système d'emails du CRM permet d'envoyer et de recevoir des emails directement depuis l'interface Filament, avec synchronisation IMAP automatique. **La configuration est gérée dynamiquement via le back-office, sans valeurs statiques.**

## Composants Créés

### 1. Modèles

#### Email (`app/Models/Email.php`)
Modèle principal pour gérer les emails envoyés et reçus.

**Champs principaux:**
- `type`: sent, received, draft
- `folder`: inbox, sent, drafts, trash, archive
- `message_id`: ID unique du message
- `from_email`, `from_name`: Expéditeur
- `to_email`, `cc_email`, `bcc_email`: Destinataires
- `subject`, `body_text`, `body_html`: Contenu
- `attachments`: JSON des pièces jointes
- `priority`: low, normal, high
- `labels`: Étiquettes personnalisées

**Scopes utiles:**
```php
Email::inbox()->unread()->recent()->get();
Email::sent()->highPriority()->get();
Email::drafts()->forUser($userId)->get();
```

#### ReceivedEmail (`app/Models/ReceivedEmail.php`)
Modèle spécifique pour les emails reçus (optionnel, peut être fusionné avec Email).

### 2. Migrations

#### `create_emails_table.php`
Crée la table `emails` avec tous les champs nécessaires.

#### `add_email_password_to_users_table.php`
Ajoute les champs `email_password` et `email_last_sync` à la table `users`.

### 3. Resource Filament

#### EmailResource (`app/Filament/NsConseil/Resources/EmailResource.php`)
Resource Filament complète avec:
- Liste des emails avec onglets par dossier
- Filtres (dossier, type, priorité, non lus, pièces jointes)
- Actions rapides (marquer lu/non lu, archiver, corbeille)
- Formulaire d'envoi avec éditeur HTML
- Actions de réponse/transfert

#### Pages
- `ListEmails`: Liste avec onglets (Boîte de réception, Envoyés, Brouillons, Corbeille, Archives)
- `CreateEmail`: Formulaire de création avec envoi direct
- `EditEmail`: Modification des brouillons
- `ViewEmail`: Visualisation avec actions de réponse

### 4. Service IMAP

#### ImapService (`app/Services/Email/ImapService.php`)
Service pour la synchronisation IMAP.

**Fonctionnalités:**
- Connexion au serveur IMAP
- Synchronisation des emails reçus
- Traitement des pièces jointes
- Détection automatique de la priorité
- Envoi d'emails via SMTP

**Utilisation:**
```php
$service = new ImapService($user);
$stats = $service->syncEmails(); // Synchronise les emails
```

### 5. Commande de Synchronisation

#### SyncEmails (`app/Console/Commands/SyncEmails.php`)
Commande Artisan pour synchroniser les emails.

**Utilisation:**
```bash
# Synchroniser toutes les configurations actives
php artisan emails:sync

# Synchroniser une configuration spécifique
php artisan emails:sync --config=1

# Synchroniser avec une limite personnalisée
php artisan emails:sync --limit=100

# Synchroniser toutes les configurations (y compris globales)
php artisan emails:sync --all
```

### 6. Configuration Dynamique

#### EmailConfiguration (`app/Models/EmailConfiguration.php`)
Modèle pour gérer les configurations email dynamiques.

**Champs principaux:**
- `user_id`: Utilisateur concerné (null si globale)
- `is_global`: Configuration globale pour tous les utilisateurs
- `imap_host`, `imap_port`, `imap_encryption`, `imap_protocol`: Configuration IMAP
- `smtp_host`, `smtp_port`, `smtp_encryption`: Configuration SMTP
- `email`, `password`: Identifiants (mot de passe chiffré automatiquement)
- `from_name`: Nom d'expéditeur
- `sync_enabled`: Activation de la synchronisation
- `sync_interval`: Intervalle en minutes
- `sync_limit`: Nombre d'emails à synchroniser
- `last_sync_at`: Date de dernière synchronisation
- `active`: Statut de la configuration

**Méthodes utiles:**
```php
// Récupérer la configuration d'un utilisateur
$config = EmailConfiguration::forUser($userId)->active()->first();

// Tester la connexion
$result = $config->testConnection();

// Activer/désactiver la synchronisation
$config->enableSync();
$config->disableSync();

// Mettre à jour la dernière synchronisation
$config->updateLastSync();
```

#### EmailConfigurationResource (`app/Filament/SuperAdmin/Resources/EmailConfigurationResource.php`)
Resource Filament pour gérer les configurations email (accessible uniquement aux Super Admins).

**Fonctionnalités:**
- Création de configurations globales ou par utilisateur
- Configuration IMAP/SMTP complète
- Test de connexion en un clic
- Activation/désactivation de la synchronisation
- Gestion des paramètres de synchronisation

## Installation

### 1. Installer les dépendances

```bash
composer require webklex/laravel-imap
```

### 2. Exécuter les migrations

```bash
php artisan migrate
```

### 3. Configurer le scheduler

Dans `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('emails:sync')->everyFiveMinutes();
}
```

### 4. Créer les configurations email

Via l'interface Filament (Super Admin):
1. Aller dans "Configuration" → "Configurations Email"
2. Cliquer sur "Nouvelle configuration"
3. Choisir entre:
   - **Configuration globale**: S'applique à tous les utilisateurs
   - **Configuration utilisateur**: Spécifique à un utilisateur
4. Remplir les champs IMAP/SMTP
5. Tester la connexion avec le bouton "Tester connexion"
6. Activer la synchronisation si nécessaire

## Utilisation

### Envoyer un email

1. Aller dans le menu "Communication" → "Emails"
2. Cliquer sur "Nouvel email"
3. Remplir le formulaire:
   - Destinataire (obligatoire)
   - CC/CCI (optionnel)
   - Sujet (obligatoire)
   - Message (éditeur HTML)
   - Pièces jointes (à implémenter)
4. Cliquer sur "Envoyer" ou "Sauvegarder brouillon"

### Répondre à un email

1. Ouvrir un email reçu
2. Cliquer sur "Répondre" ou "Répondre à tous"
3. Le formulaire pré-rempli s'ouvre
4. Modifier et envoyer

### Gérer les emails

**Actions disponibles:**
- Marquer comme lu/non lu
- Archiver
- Déplacer vers la corbeille
- Restaurer depuis la corbeille
- Supprimer définitivement

**Dossiers:**
- Boîte de réception: Emails reçus
- Envoyés: Emails envoyés
- Brouillons: Emails en cours de rédaction
- Corbeille: Emails supprimés
- Archives: Emails archivés

### Étiquettes

Les emails peuvent avoir des étiquettes personnalisées:
- Ajouter via le formulaire
- Suggestions: urgent, important, suivi, facture, contrat

### Priorité

Les emails peuvent avoir une priorité:
- Haute: Emails importants
- Normale: Emails standard
- Basse: Emails moins importants

## Synchronisation IMAP

### Configuration Gmail

Pour Gmail, il faut:
1. Activer l'accès IMAP dans les paramètres Gmail
2. Utiliser un "Mot de passe d'application" si 2FA activé
3. Ou utiliser "Moins sécurisé" (non recommandé)

**Dans l'interface de configuration:**
- Hôte IMAP: `imap.gmail.com`
- Port IMAP: `993`
- Chiffrement: `SSL`
- Hôte SMTP: `smtp.gmail.com`
- Port SMTP: `587`
- Chiffrement: `TLS`

### Configuration Outlook

Pour Outlook/Office 365:
- Hôte IMAP: `outlook.office365.com`
- Port IMAP: `993`
- Chiffrement: `SSL`
- Hôte SMTP: `smtp.office365.com`
- Port SMTP: `587`
- Chiffrement: `TLS`

### Configuration Exchange

Pour Exchange:
- Hôte IMAP: `exchange.server.com`
- Port IMAP: `993`
- Chiffrement: `SSL`
- Hôte SMTP: `smtp.exchange.com`
- Port SMTP: `587`
- Chiffrement: `TLS`

## Sécurité

### Chiffrement du mot de passe

Les mots de passe sont automatiquement chiffrés lors de la sauvegarde et déchiffrés lors de la récupération grâce aux hooks du modèle EmailConfiguration:

```php
// Chiffrement automatique dans EmailConfiguration::saving()
// Déchiffrement automatique dans EmailConfiguration::retrieved()
```

Le mot de passe n'est jamais stocké en clair dans la base de données.

### Permissions

La Resource EmailConfiguration utilise le système de permissions Filament et est accessible uniquement aux Super Admins:

```php
public static function canViewAny(): bool
{
    return Auth::user()?->isSuperAdmin() ?? false;
}
```

La Resource Email (interface utilisateur) peut être configurée avec des permissions spécifiques si nécessaire.

## Personnalisation

### Ajouter des champs personnalisés

Modifier la migration et le modèle Email:
```php
// Migration
$table->string('custom_field')->nullable();

// Modèle
protected $fillable = ['custom_field'];
```

### Personnaliser les filtres

Ajouter dans `EmailResource::table()`:
```php
Tables\Filters\Filter::make('custom_filter')
    ->label('Filtre personnalisé')
    ->query(fn ($query) => $query->where('custom_field', 'value')),
```

### Personnaliser les actions

Ajouter dans `EmailResource::table()`:
```php
Tables\Actions\Action::make('custom_action')
    ->label('Action personnalisée')
    ->icon('heroicon-o-star')
    ->action(fn ($record) => $record->customMethod()),
```

## Dépannage

### Erreur de connexion IMAP

**Problème:** "Impossible de se connecter au serveur email"

**Solutions:**
1. Vérifier les identifiants IMAP
2. Vérifier la configuration du pare-feu
3. Tester avec un client IMAP externe
4. Vérifier les logs: `storage/logs/laravel.log`

### Erreur d'envoi SMTP

**Problème:** "Erreur envoi email"

**Solutions:**
1. Vérifier la configuration SMTP
2. Vérifier les identifiants SMTP
3. Vérifier le port et l'encryption
4. Tester avec un client SMTP externe

### Pièces jointes non téléchargées

**Problème:** Les pièces jointes ne s'affichent pas

**Solutions:**
1. Vérifier les permissions du dossier `storage/app/emails`
2. Vérifier l'espace disque disponible
3. Vérifier la taille maximale des fichiers (PHP upload_max_filesize)

### Synchronisation lente

**Problème:** La synchronisation prend trop de temps

**Solutions:**
1. Réduire `IMAP_SYNC_LIMIT`
2. Augmenter l'intervalle de synchronisation
3. Optimiser les indexes de la table emails
4. Utiliser un cache pour les messages déjà traités

## Roadmap

### Fonctionnalités futures

- [ ] Upload de pièces jointes depuis l'interface
- [ ] Templates d'emails
- [ ] Signatures email
- [ ] Règles de filtrage automatique
- [ ] Recherche plein texte dans les emails
- [ ] Notifications pour les emails importants
- [ ] Statistiques d'emails
- [ ] Intégration avec les entités CRM (Prospect, Partenaire, Client)
- [ ] Réponses automatiques
- [ ] Calendrier d'envoi différé

## Intégration CRM

### Lier les emails aux entités

Les emails peuvent être liés aux entités CRM via la relation morphique `emailable`:

```php
// Lier un email à un prospect
$prospect->emails()->create([
    'subject' => 'Suivi prospect',
    'body_html' => '<p>Bonjour,</p>',
    'type' => Email::TYPE_SENT,
]);

// Récupérer les emails d'un prospect
$emails = $prospect->emails;
```

### Envoyer un email depuis une entité

Ajouter une action dans les Resources:
```php
Tables\Actions\Action::make('send_email')
    ->label('Envoyer email')
    ->icon('heroicon-o-envelope')
    ->url(fn ($record) => route('filament.ns-conseil.resources.emails.create', [
        'emailable_type' => Prospect::class,
        'emailable_id' => $record->id,
    ])),
```

## Support

Pour toute question ou problème, consulter:
- Logs Laravel: `storage/logs/laravel.log`
- Documentation Filament: https://filamentphp.com/docs
- Documentation PHP-IMAP: https://github.com/Webklex/laravel-imap
