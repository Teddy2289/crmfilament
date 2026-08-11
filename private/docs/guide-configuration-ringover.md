# Guide de Configuration Ringover

## Étape 1: Configuration des variables d'environnement

Accédez à **Super Admin > Variables .env** et configurez les variables Ringover :

### Variables obligatoires

#### RINGOVER_API_TOKEN
- **Description**: Clé API Ringover pour l'intégration téléphonie
- **Comment obtenir**: Connectez-vous à votre compte Ringover > Paramètres > API > Générer une clé API
- **Format**: Chaîne de caractères (ex: `abc123xyz789`)
- **Sensible**: Oui (masqué par défaut)

#### RINGOVER_WEBHOOK_SECRET
- **Description**: Secret pour valider les webhooks Ringover entrants
- **Comment obtenir**: Créez un secret sécurisé (ex: utilisez `openssl rand -base64 32`)
- **Format**: Chaîne de caractères
- **Sensible**: Oui (masqué par défaut)

### Variables optionnelles

#### RINGOVER_REGION
- **Description**: Région de l'API Ringover
- **Valeurs possibles**: `europe` (par défaut) ou `us`
- **Choisir**: Sélectionnez `us` si votre compte Ringover est hébergé aux États-Unis

#### RINGOVER_MONITORING_ENABLED
- **Description**: Active le monitoring Ringover pour accès étendu aux données d'équipe
- **Valeurs**: `true` ou `false`
- **Recommandation**: Activez si vous avez besoin d'accéder aux données d'équipe complètes

#### RINGOVER_TIMEOUT
- **Description**: Délai d'attente maximum pour les requêtes API Ringover
- **Valeur par défaut**: 10 secondes
- **Recommandation**: Augmentez si vous rencontrez des timeouts fréquents

## Étape 2: Configuration Laravel Reverb (WebSocket)

Pour le temps réel, configurez les variables Reverb :

### Variables obligatoires

#### REVERB_APP_ID
- **Description**: ID de l'application Laravel Reverb
- **Comment obtenir**: Créez une application Reverb ou utilisez l'ID par défaut
- **Format**: Chaîne de caractères

#### REVERB_APP_KEY
- **Description**: Clé de l'application Laravel Reverb
- **Sensible**: Oui

#### REVERB_APP_SECRET
- **Description**: Secret de l'application Laravel Reverb
- **Sensible**: Oui

### Variables optionnelles

#### REVERB_HOST
- **Description**: Adresse du serveur Laravel Reverb
- **Valeur par défaut**: `127.0.0.1`

#### REVERB_PORT
- **Description**: Port du serveur Laravel Reverb
- **Valeur par défaut**: `8080`

#### REVERB_SCHEME
- **Description**: Protocole de connexion Reverb
- **Valeurs**: `http` ou `https`
- **Valeur par défaut**: `http`

## Étape 3: Configuration des Webhooks Ringover

Accédez à **Super Admin > Webhooks** et créez des webhooks pour les événements Ringover :

### Événements disponibles

1. **ringover.call.ringing** - Appel sonne
   - Déclenché quand un appel commence à sonner
   - Utile pour afficher une notification en temps réel

2. **ringover.call.answered** - Appel décroché
   - Déclenché quand un appel est décroché
   - Utile pour démarrer des actions automatiques

3. **ringover.call.hangup** - Appel terminé
   - Déclenché quand un appel se termine
   - Utile pour enregistrer les détails de l'appel

### Configuration d'un webhook

1. Cliquez sur "Créer un webhook"
2. Remplissez les champs :
   - **Nom**: Nom descriptif (ex: "Ringover - Appels sonnants")
   - **URL**: URL de destination (ex: votre endpoint externe)
   - **Événement**: Sélectionnez l'événement Ringover souhaité
   - **Actif**: Cochez pour activer le webhook

3. Configuration avancée (optionnelle) :
   - **Secret de signature**: Utilisez le même que RINGOVER_WEBHOOK_SECRET
   - **En-têtes HTTP**: Ajoutez des en-têtes personnalisés si nécessaire
   - **Utilisateur associé**: Lier à un utilisateur CRM spécifique

## Étape 4: Vérification de l'installation

### Test de connexion API

```bash
php artisan tinker
>>> app(\App\Services\RingoverService::class)->testConnection()
```

### Test du webhook

1. Créez un webhook de test
2. Déclenchez un appel depuis Ringover
3. Vérifiez les logs Laravel : `tail -f storage/logs/laravel.log`

### Test du temps réel

1. Assurez-vous que Reverb est démarré
2. Ouvrez la console du navigateur sur une page du CRM
3. Écoutez l'événement : `Echo.channel('ringover.calls').listen('call.ringing', (e) => console.log(e))`
4. Déclenchez un appel depuis Ringover
5. Vérifiez que l'événement est reçu

## Dépannage

### Erreur 401 Unauthorized
- Vérifiez que RINGOVER_API_TOKEN est correct
- Activez RINGOVER_MONITORING_ENABLED si nécessaire

### Erreur 429 Rate Limit
- Le middleware RingoverRateLimit protège contre les abus
- Attendez le délai spécifié dans le header Retry-After

### Webhook non reçu
- Vérifiez que RINGOVER_WEBHOOK_SECRET correspond
- Vérifiez les logs Laravel pour les erreurs de validation
- Assurez-vous que le webhook est actif dans Ringover

### Temps réel non fonctionnel
- Vérifiez que Reverb est démarré
- Vérifiez les variables REVERB_*
- Vérifiez que le navigateur supporte WebSocket

## Sécurité

- Ne partagez jamais vos secrets (API token, webhook secret, Reverb secret)
- Utilisez des secrets forts (minimum 32 caractères)
- Activez HTTPS en production
- Limitez l'accès aux webhooks par IP si possible
- Surveillez les logs pour détecter les activités suspectes
