# Requirements Document

## Introduction

Ce document décrit les exigences pour l'intégration d'une couche API REST (et optionnellement GraphQL) dans le CRM Laravel/Filament existant. L'objectif est d'exposer les données du CRM à des clients externes — une application mobile (React Native ou Flutter) et une application front-end Vue.js — en suivant les standards OpenAPI, en implémentant une authentification sécurisée par tokens JWT/Sanctum, des autorisations granulaires calquées sur le système Spatie existant, ainsi que la pagination et le filtrage des ressources.

Le CRM gère des organisations (partenaires, prospects, clients), des campagnes de prospection, des rendez-vous, des tickets, des réclamations, des devis, des bons de commande, de la téléphonie (phoning) et des emails. L'API doit exposer ces ressources de façon cohérente et sécurisée sans perturber le fonctionnement de l'interface Filament existante.

---

## Glossaire

- **API** : Interface de programmation applicative exposant les ressources du CRM via HTTP.
- **API_Gateway** : Couche de routage Laravel qui reçoit et dispatche les requêtes HTTP entrantes vers les contrôleurs API.
- **Auth_Service** : Service responsable de l'émission et de la validation des tokens d'authentification (Sanctum / JWT).
- **Resource_Controller** : Contrôleur Laravel dédié à une ressource API (CRUD + filtrage + pagination).
- **Serializer** : Composant qui transforme un modèle Eloquent en réponse JSON structurée (et inversement pour la désérialisation).
- **OpenAPI_Doc** : Documentation de l'API générée automatiquement au format OpenAPI 3.x, lisible par Swagger UI.
- **Permission_Guard** : Couche d'autorisation qui vérifie les droits Spatie de l'utilisateur authentifié avant d'autoriser l'accès à une ressource ou un champ.
- **Rate_Limiter** : Mécanisme qui limite le nombre de requêtes par token/IP sur une fenêtre de temps donnée.
- **Prospect** : Entité CRM représentant une organisation cible avant conversion en Partenaire.
- **Partenaire** : Entité CRM représentant une organisation ayant signé un accord.
- **Client** : Bénéficiaire importé depuis Dolibarr, associé à un Partenaire.
- **Ticket** : Demande de support ou signalement lié à un Client ou Partenaire.
- **Reclamation** : Réclamation formelle (P8) liée à un Client ou Partenaire.
- **Devis** : Document commercial proposé à un Partenaire ou Client.
- **BonDeCommande** : Commande validée issue d'un Devis accepté.
- **CampagnePhoning** : Campagne de téléprospection regroupant des Prospects.
- **RendezVous** : Réunion commerciale planifiée, polymorphe (Prospect, Partenaire, Client).
- **Mobile_App** : Application cliente (React Native ou Flutter) consommant l'API.
- **Vue_App** : Application front-end Vue.js consommant l'API.

---

## Requirements

### Requirement 1 : Installation et configuration de la couche API

**User Story :** En tant qu'administrateur technique, je veux que Laravel soit configuré avec une couche API dédiée, afin que les clients mobiles et Vue.js puissent communiquer avec le CRM sans interférer avec l'interface Filament.

#### Acceptance Criteria

1. THE API_Gateway SHALL exposer toutes les routes API sous le préfixe `/api/v1/`.
2. THE API_Gateway SHALL retourner toutes les réponses au format `application/json`.
3. THE API_Gateway SHALL versionner les routes de façon à permettre l'introduction d'une version `/api/v2/` sans casser la version existante.
4. IF une route demandée n'existe pas, THEN THE API_Gateway SHALL retourner une réponse JSON avec le code HTTP 404 et un message d'erreur structuré.
5. IF une erreur interne non gérée se produit, THEN THE API_Gateway SHALL retourner une réponse JSON avec le code HTTP 500 et un identifiant d'erreur traçable sans exposer la stack trace.
6. THE API_Gateway SHALL activer les en-têtes CORS pour autoriser les origines des domaines configurés dans `config/cors.php`.

---

### Requirement 2 : Authentification par token

**User Story :** En tant qu'utilisateur de l'application mobile ou Vue.js, je veux m'authentifier avec mes identifiants CRM et obtenir un token, afin d'accéder aux ressources protégées de l'API.

#### Acceptance Criteria

1. WHEN un utilisateur soumet des identifiants valides à `POST /api/v1/auth/login`, THE Auth_Service SHALL retourner un token d'accès et un token de rafraîchissement signés.
2. WHEN un utilisateur soumet des identifiants invalides à `POST /api/v1/auth/login`, THE Auth_Service SHALL retourner une réponse HTTP 401 avec un message d'erreur générique sans préciser lequel des deux champs est incorrect.
3. WHEN un utilisateur envoie un token d'accès valide dans l'en-tête `Authorization: Bearer <token>`, THE Auth_Service SHALL authentifier la requête.
4. WHEN un token d'accès expiré est reçu, THE Auth_Service SHALL retourner une réponse HTTP 401 avec le code `token_expired`.
5. WHEN un utilisateur envoie un token de rafraîchissement valide à `POST /api/v1/auth/refresh`, THE Auth_Service SHALL émettre un nouveau token d'accès avec une durée de validité renouvelée.
6. WHEN un utilisateur appelle `POST /api/v1/auth/logout`, THE Auth_Service SHALL révoquer le token d'accès courant.
7. THE Auth_Service SHALL stocker les tokens via Laravel Sanctum en réutilisant le système d'utilisateurs existant (`users` table) sans créer de nouvelle table d'utilisateurs.

---

### Requirement 3 : Autorisations calquées sur les rôles Spatie

**User Story :** En tant qu'administrateur, je veux que les autorisations API respectent les rôles et permissions Spatie existants, afin que les droits d'accès soient cohérents entre l'interface Filament et l'API.

#### Acceptance Criteria

1. WHEN une requête API authentifiée est reçue, THE Permission_Guard SHALL vérifier les permissions Spatie de l'utilisateur avant d'exécuter l'action.
2. IF un utilisateur tente d'accéder à une ressource pour laquelle son rôle ne lui accorde pas la permission `view`, THEN THE Permission_Guard SHALL retourner une réponse HTTP 403.
3. IF un utilisateur tente de créer ou modifier une ressource sans la permission correspondante (`create`, `edit`), THEN THE Permission_Guard SHALL retourner une réponse HTTP 403.
4. THE Permission_Guard SHALL respecter les `FieldPermission` existants pour masquer ou interdire l'écriture sur les champs sensibles selon le rôle de l'utilisateur.
5. WHILE un utilisateur possède le rôle `Téléprospecteur`, THE Permission_Guard SHALL restreindre l'accès aux seuls Prospects et CampagnePhoning assignés à cet utilisateur.
6. THE Permission_Guard SHALL restreindre les actions de validation du statut QF (`validerQf`) aux seuls utilisateurs dont le `CrmProfile` a `can_validate_qf = true`.

---

### Requirement 4 : Ressources API — Prospects

**User Story :** En tant qu'utilisateur de l'application mobile ou Vue.js, je veux lire et gérer les prospects depuis l'API, afin d'alimenter les interfaces de prospection.

#### Acceptance Criteria

1. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/prospects`, `GET /api/v1/prospects/{id}`, `POST /api/v1/prospects`, `PUT /api/v1/prospects/{id}`, et `DELETE /api/v1/prospects/{id}`.
2. WHEN une requête `GET /api/v1/prospects` est reçue avec un paramètre `filter[statut]`, THE Resource_Controller SHALL retourner uniquement les Prospects dont le `ProspectStatut` correspond à la valeur fournie.
3. WHEN une requête `GET /api/v1/prospects` est reçue avec un paramètre `filter[campagne_id]`, THE Resource_Controller SHALL retourner uniquement les Prospects appartenant à la CampagnePhoning spécifiée.
4. WHEN une requête `GET /api/v1/prospects` est reçue avec les paramètres `filter[search]`, THE Resource_Controller SHALL retourner les Prospects dont le nom, le SIRET ou le téléphone correspond à la recherche, en utilisant une correspondance partielle insensible à la casse.
5. IF un `prospect_id` inexistant est fourni dans une requête `GET /api/v1/prospects/{id}`, THEN THE Resource_Controller SHALL retourner une réponse HTTP 404.
6. WHEN une requête `POST /api/v1/prospects` est reçue avec des données invalides (champ obligatoire manquant ou format incorrect), THE Resource_Controller SHALL retourner une réponse HTTP 422 avec la liste des erreurs de validation par champ.

---

### Requirement 5 : Ressources API — Partenaires

**User Story :** En tant qu'utilisateur de l'application mobile ou Vue.js, je veux accéder aux données des partenaires depuis l'API, afin de consulter et gérer le portefeuille partenaires.

#### Acceptance Criteria

1. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/partenaires`, `GET /api/v1/partenaires/{id}`, `POST /api/v1/partenaires`, `PUT /api/v1/partenaires/{id}`.
2. WHEN une requête `GET /api/v1/partenaires` est reçue avec `filter[statut]`, THE Resource_Controller SHALL retourner uniquement les Partenaires correspondant à l'`OrganizationStatus` fourni.
3. WHEN une requête `GET /api/v1/partenaires` est reçue avec `filter[type]`, THE Resource_Controller SHALL retourner uniquement les Partenaires correspondant à l'`OrganizationType` fourni.
4. WHEN une requête `GET /api/v1/partenaires/{id}/contacts` est reçue, THE Resource_Controller SHALL retourner la liste des `ContactPartenaire` liés au Partenaire, paginée.
5. WHEN une requête `GET /api/v1/partenaires/{id}/rendez-vous` est reçue, THE Resource_Controller SHALL retourner la liste des `RendezVous` liés au Partenaire, triée par date décroissante.
6. IF un `partenaire_id` inexistant est fourni, THEN THE Resource_Controller SHALL retourner une réponse HTTP 404.

---

### Requirement 6 : Ressources API — Clients

**User Story :** En tant qu'utilisateur de l'application mobile ou Vue.js, je veux accéder aux données des clients bénéficiaires, afin de consulter leur profil et leur historique.

#### Acceptance Criteria

1. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/clients`, `GET /api/v1/clients/{id}`, `GET /api/v1/clients/{id}/dossiers-formation`.
2. WHEN une requête `GET /api/v1/clients` est reçue avec `filter[partenaire_id]`, THE Resource_Controller SHALL retourner uniquement les Clients associés au Partenaire spécifié.
3. WHEN une requête `GET /api/v1/clients` est reçue avec `filter[search]`, THE Resource_Controller SHALL retourner les Clients dont le nom, le prénom ou la référence client correspond à la recherche, en utilisant une correspondance partielle insensible à la casse.
4. THE Resource_Controller SHALL exposer les données Client en lecture seule (GET uniquement), car la source de vérité reste Dolibarr.

---

### Requirement 7 : Ressources API — Tickets et Réclamations

**User Story :** En tant qu'utilisateur de l'application mobile ou Vue.js, je veux consulter et créer des tickets et réclamations depuis l'API, afin de gérer le support client.

#### Acceptance Criteria

1. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/tickets`, `GET /api/v1/tickets/{id}`, `POST /api/v1/tickets`, `PUT /api/v1/tickets/{id}`.
2. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/reclamations`, `GET /api/v1/reclamations/{id}`, `POST /api/v1/reclamations`, `PUT /api/v1/reclamations/{id}`.
3. WHEN une requête `GET /api/v1/tickets` est reçue avec `filter[statut]`, THE Resource_Controller SHALL retourner uniquement les Tickets correspondant au `TicketStatut` fourni.
4. WHEN une requête `POST /api/v1/tickets` est reçue, THE Resource_Controller SHALL associer le ticket à l'utilisateur authentifié comme `created_by`.
5. IF une requête de mise à jour de statut de réclamation est reçue avec un statut non autorisé pour le rôle de l'utilisateur, THEN THE Permission_Guard SHALL retourner une réponse HTTP 403.

---

### Requirement 8 : Ressources API — Devis et Bons de Commande

**User Story :** En tant qu'utilisateur de l'application mobile ou Vue.js, je veux consulter les devis et bons de commande, afin de suivre l'état commercial.

#### Acceptance Criteria

1. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/devis`, `GET /api/v1/devis/{id}`.
2. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/bons-de-commande`, `GET /api/v1/bons-de-commande/{id}`.
3. WHEN une requête `GET /api/v1/devis` est reçue avec `filter[statut]`, THE Resource_Controller SHALL retourner uniquement les Devis correspondant au `StatutDevis` fourni.
4. WHEN une requête `GET /api/v1/devis` est reçue avec `filter[partenaire_id]`, THE Resource_Controller SHALL retourner uniquement les Devis associés au Partenaire spécifié.
5. THE Resource_Controller SHALL exposer les Devis et BonsDeCommande en lecture seule pour les rôles `Téléprospecteur` et `Opérateur N1`.

---

### Requirement 9 : Ressources API — Rendez-vous et Agenda

**User Story :** En tant qu'utilisateur de l'application mobile ou Vue.js, je veux consulter et gérer mon agenda de rendez-vous, afin de suivre mes activités commerciales en déplacement.

#### Acceptance Criteria

1. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/rendez-vous`, `GET /api/v1/rendez-vous/{id}`, `POST /api/v1/rendez-vous`, `PUT /api/v1/rendez-vous/{id}`.
2. WHEN une requête `GET /api/v1/rendez-vous` est reçue, THE Resource_Controller SHALL retourner uniquement les RendezVous assignés à l'utilisateur authentifié, sauf si son rôle lui confère une visibilité élargie.
3. WHEN une requête `GET /api/v1/rendez-vous` est reçue avec `filter[date_debut]` et `filter[date_fin]`, THE Resource_Controller SHALL retourner uniquement les RendezVous dont la date est comprise dans l'intervalle fourni (bornes incluses).
4. WHEN une requête `POST /api/v1/rendez-vous` est reçue avec des données valides, THE Resource_Controller SHALL créer le RendezVous et retourner une réponse HTTP 201 avec la ressource créée.
5. IF la date de fin d'un RendezVous est antérieure à sa date de début dans une requête de création ou de modification, THEN THE Resource_Controller SHALL retourner une réponse HTTP 422 avec le message `date_fin must be after date_debut`.

---

### Requirement 10 : Ressources API — Campagnes Phoning

**User Story :** En tant que téléprospecteur ou team leader, je veux accéder aux campagnes de phoning depuis l'application mobile, afin de réaliser mes appels en mobilité.

#### Acceptance Criteria

1. THE Resource_Controller SHALL exposer les endpoints `GET /api/v1/campagnes-phoning`, `GET /api/v1/campagnes-phoning/{id}`, `GET /api/v1/campagnes-phoning/{id}/prospects`.
2. WHEN une requête `GET /api/v1/campagnes-phoning/{id}/prospects` est reçue, THE Resource_Controller SHALL retourner la liste paginée des Prospects de la campagne triée par priorité de phoning.
3. WHEN une requête `POST /api/v1/prospects/{id}/appel` est reçue avec un `statut_phoning_id` valide, THE Resource_Controller SHALL enregistrer l'appel et mettre à jour le `ProspectStatut` selon les règles métier du workflow phoning.
4. IF le `statut_phoning_id` fourni n'existe pas dans la table `statut_phonings`, THEN THE Resource_Controller SHALL retourner une réponse HTTP 422 avec le message `statut_phoning_id invalide`.
5. WHILE une CampagnePhoning a le statut `archivée`, THE Resource_Controller SHALL retourner les prospects de la campagne en lecture seule sans autoriser les modifications.

---

### Requirement 11 : Pagination et tri

**User Story :** En tant que développeur d'application mobile ou Vue.js, je veux que toutes les listes de l'API soient paginées et triables, afin de garantir des performances acceptables même avec de grands volumes de données.

#### Acceptance Criteria

1. THE Resource_Controller SHALL paginer toutes les réponses de liste avec une taille de page par défaut de 25 éléments.
2. WHEN une requête reçoit le paramètre `per_page`, THE Resource_Controller SHALL utiliser cette valeur avec un maximum autorisé de 100 éléments par page.
3. IF le paramètre `per_page` dépasse 100, THEN THE Resource_Controller SHALL silencieusement appliquer la valeur maximale de 100.
4. THE Resource_Controller SHALL inclure dans chaque réponse paginée les métadonnées `total`, `per_page`, `current_page`, `last_page`, `next_page_url` et `prev_page_url`.
5. WHEN une requête reçoit le paramètre `sort` (ex. `sort=-created_at`), THE Resource_Controller SHALL trier les résultats par le champ indiqué, le préfixe `-` indiquant un ordre décroissant.
6. IF un paramètre `sort` référence un champ non autorisé au tri, THEN THE Resource_Controller SHALL retourner une réponse HTTP 422 avec le message `Le champ de tri <champ> n'est pas autorisé`.

---

### Requirement 12 : Sérialisation et structure des réponses

**User Story :** En tant que développeur d'application mobile ou Vue.js, je veux des réponses JSON structurées et cohérentes, afin de simplifier l'intégration côté client.

#### Acceptance Criteria

1. THE Serializer SHALL structurer toutes les réponses de succès avec une enveloppe `{ "data": ..., "meta": ... }`.
2. THE Serializer SHALL structurer toutes les réponses d'erreur avec une enveloppe `{ "message": "...", "errors": { ... } }`.
3. THE Serializer SHALL convertir les valeurs des Enums PHP (ex. `ProspectStatut`, `OrganizationType`) en leur valeur de chaîne lisible dans les réponses JSON.
4. THE Serializer SHALL omettre les champs pour lesquels l'utilisateur n'a pas la permission `show` selon les `FieldPermission` existants.
5. WHERE le client demande l'inclusion de relations (paramètre `include`), THE Serializer SHALL inclure les relations demandées sous leur clé dédiée dans l'objet `data`, dans la limite des relations autorisées.
6. FOR ALL models Eloquent sérialisés puis désérialisés, THE Serializer SHALL produire un objet fonctionnellement équivalent à l'original (propriété de round-trip).

---

### Requirement 13 : Documentation OpenAPI automatique

**User Story :** En tant que développeur d'application mobile ou Vue.js, je veux que l'API soit documentée automatiquement au format OpenAPI 3.x, afin de pouvoir intégrer l'API sans avoir à lire le code source.

#### Acceptance Criteria

1. THE OpenAPI_Doc SHALL être accessible à l'URL `/api/documentation` (Swagger UI) en environnement non-production.
2. THE OpenAPI_Doc SHALL lister tous les endpoints exposés avec leurs paramètres, corps de requête et codes de réponse possibles.
3. THE OpenAPI_Doc SHALL documenter les schémas de toutes les ressources principales (Prospect, Partenaire, Client, Ticket, Reclamation, Devis, BonDeCommande, RendezVous, CampagnePhoning).
4. WHEN le code source des contrôleurs ou des FormRequest est modifié, THE OpenAPI_Doc SHALL être régénérable via une commande artisan sans intervention manuelle sur le fichier de spécification.
5. IF l'environnement d'exécution est `production`, THEN THE OpenAPI_Doc SHALL retourner une réponse HTTP 403 pour protéger la documentation contre l'accès non autorisé.

---

### Requirement 14 : Rate Limiting

**User Story :** En tant qu'administrateur, je veux que l'API limite le nombre de requêtes par token, afin de protéger le CRM contre les abus et les attaques par force brute.

#### Acceptance Criteria

1. THE Rate_Limiter SHALL limiter les requêtes authentifiées à 1 000 requêtes par heure par token.
2. THE Rate_Limiter SHALL limiter les tentatives de connexion (`POST /api/v1/auth/login`) à 10 tentatives par minute par adresse IP.
3. WHEN la limite de requêtes est atteinte, THE Rate_Limiter SHALL retourner une réponse HTTP 429 avec l'en-tête `Retry-After` indiquant le nombre de secondes avant réinitialisation.
4. THE Rate_Limiter SHALL inclure les en-têtes `X-RateLimit-Limit` et `X-RateLimit-Remaining` dans chaque réponse API authentifiée.

---

### Requirement 15 : Sécurité des tokens et gestion des sessions

**User Story :** En tant qu'administrateur, je veux que les tokens soient sécurisés et révocables, afin de pouvoir réagir rapidement en cas de compromission.

#### Acceptance Criteria

1. THE Auth_Service SHALL attribuer à chaque token émis une durée d'expiration maximale de 24 heures pour les tokens d'accès et de 30 jours pour les tokens de rafraîchissement.
2. IF un token de rafraîchissement révoqué ou expiré est utilisé, THEN THE Auth_Service SHALL retourner une réponse HTTP 401 avec le code `refresh_token_invalid`.
3. THE Auth_Service SHALL permettre à un administrateur de révoquer tous les tokens d'un utilisateur via `DELETE /api/v1/admin/users/{id}/tokens`.
4. THE Auth_Service SHALL consigner dans les logs applicatifs chaque émission, rafraîchissement et révocation de token avec l'identifiant utilisateur, l'adresse IP et l'horodatage.

---

### Requirement 16 : Support GraphQL (optionnel)

**User Story :** En tant que développeur Vue.js, je veux pouvoir utiliser GraphQL pour interroger le CRM, afin de récupérer exactement les champs dont j'ai besoin en une seule requête.

#### Acceptance Criteria

1. WHERE l'option GraphQL est activée dans la configuration, THE API_Gateway SHALL exposer un endpoint GraphQL à `/api/graphql`.
2. WHERE l'option GraphQL est activée, THE API_Gateway SHALL exposer un playground GraphQL à `/api/graphql-playground` en environnement non-production uniquement.
3. WHERE l'option GraphQL est activée, THE Permission_Guard SHALL appliquer les mêmes règles d'autorisation Spatie aux requêtes GraphQL qu'aux requêtes REST.
4. WHERE l'option GraphQL est activée, THE Rate_Limiter SHALL appliquer les mêmes limites de débit aux requêtes GraphQL qu'aux requêtes REST authentifiées.
