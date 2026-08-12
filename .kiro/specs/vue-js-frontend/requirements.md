# Requirements Document

## Introduction

Ce document spécifie les exigences pour le développement d'un frontend Vue.js 3 moderne destiné à remplacer l'interface Filament/Blade actuelle du CRM. Le nouveau frontend consommera l'API REST existante (Laravel Sanctum) et reproduira les fonctionnalités et l'apparence des vues Blade actuelles tout en offrant une expérience utilisateur moderne de type SPA (Single Page Application).

Le système backend Laravel/Filament (crmfilament-dev) reste inchangé et expose déjà une API REST v1 documentée avec authentification Sanctum, rate limiting, et gestion de tokens avec expiration/refresh.

## Glossary

- **Vue_Frontend**: L'application Single Page Application (SPA) Vue.js 3 avec TypeScript
- **Backend_API**: L'API REST Laravel exposée sur /api/v1 avec authentification Sanctum
- **Sanctum_Token**: Jeton d'authentification Bearer émis par Laravel Sanctum (access token + refresh token)
- **Resource_Manager**: Composant Vue responsable de la gestion CRUD d'une ressource spécifique (prospects, partenaires, etc.)
- **State_Store**: Store Pinia centralisant l'état applicatif (authentification, données, cache)
- **Router**: Vue Router gérant la navigation et les routes de l'application
- **API_Client**: Service Axios configuré pour communiquer avec Backend_API
- **Form_Validator**: Système de validation des formulaires côté client
- **Data_Table**: Composant de tableau de données avec tri, filtres, pagination et actions
- **Dashboard_Widget**: Composant d'agrégation de statistiques affiché sur le tableau de bord
- **Theme_System**: Système de gestion des thèmes visuels (light/dark)
- **Enum_Service**: Service mappant les enums PHP backend vers des constantes TypeScript
- **Modal_Dialog**: Composant de dialogue modal réutilisable pour les formulaires et confirmations
- **Notification_Service**: Service d'affichage des notifications toast et alertes
- **Error_Handler**: Gestionnaire global des erreurs HTTP et applicatives
- **Loading_State**: Indicateur visuel de chargement (spinner, skeleton, overlay)
- **Permission_Guard**: Middleware vérifiant les permissions utilisateur avant d'afficher une route/composant

## Requirements

### Requirement 1: Architecture et Configuration du Projet

**User Story:** En tant que développeur, je veux un projet Vue.js 3 structuré avec TypeScript et les outils modernes, afin de garantir la maintenabilité et la qualité du code.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL use Vue 3 with Composition API and TypeScript
2. THE Vue_Frontend SHALL use Vite as build tool and development server
3. THE Vue_Frontend SHALL be organized with the following folder structure: /src/components, /src/views, /src/stores, /src/services, /src/composables, /src/types, /src/router, /src/assets
4. THE Vue_Frontend SHALL use Pinia for state management
5. THE Vue_Frontend SHALL use Vue Router for navigation with route guards
6. THE Vue_Frontend SHALL include ESLint and Prettier configuration for code quality
7. THE Vue_Frontend SHALL use a UI component library (PrimeVue or Vuetify recommended for consistency with Filament aesthetics)
8. THE Vue_Frontend SHALL support hot module replacement during development
9. THE Vue_Frontend SHALL generate optimized production builds with code splitting

### Requirement 2: Authentification et Gestion des Tokens

**User Story:** En tant qu'utilisateur, je veux me connecter de manière sécurisée avec mon email et mot de passe, afin d'accéder aux fonctionnalités du CRM.

#### Acceptance Criteria

1. WHEN an authenticated user's access token expires, THE Vue_Frontend SHALL automatically attempt to refresh it using the refresh token
2. WHEN the email and password are valid, THE API_Client SHALL send POST /api/v1/auth/login and THE State_Store SHALL store the access_token, refresh_token, and user information
3. WHEN the refresh token is invalid or expired, THE Vue_Frontend SHALL redirect the user to the login page and clear stored credentials
4. WHEN a user clicks logout, THE API_Client SHALL call POST /api/v1/auth/logout and THE State_Store SHALL clear all authentication data
5. THE API_Client SHALL include the Sanctum_Token as Bearer header in all authenticated requests
6. WHEN the Backend_API returns 401 Unauthorized with error code "token_expired", THE Vue_Frontend SHALL trigger token refresh flow before retrying the original request
7. THE State_Store SHALL persist authentication state in localStorage to survive page reloads
8. WHEN authentication state exists in localStorage on app initialization, THE Vue_Frontend SHALL validate the token by calling GET /api/v1/auth/me
9. THE Router SHALL redirect unauthenticated users attempting to access protected routes to the login page

### Requirement 3: Gestion des Prospects

**User Story:** En tant que téléprospecteur, je veux visualiser, créer, modifier et rechercher des prospects, afin de gérer ma campagne de prospection.

#### Acceptance Criteria

1. THE Resource_Manager SHALL fetch prospects list from GET /api/v1/prospects with pagination, sorting, and filtering parameters
2. WHEN a user submits the prospect creation form with valid data, THE Resource_Manager SHALL send POST /api/v1/prospects and THE Notification_Service SHALL display a success message
3. WHEN a user modifies a prospect and saves, THE Resource_Manager SHALL send PUT /api/v1/prospects/{id} and update the local state
4. WHEN a user deletes a prospect, THE Modal_Dialog SHALL display a confirmation dialog, and upon confirmation, THE Resource_Manager SHALL send DELETE /api/v1/prospects/{id}
5. THE Data_Table SHALL display prospects with columns: nom, prénom, entreprise, téléphone, email, statut, commercial assigné, date de création
6. THE Data_Table SHALL support client-side and server-side filtering on all displayed columns
7. THE Data_Table SHALL support sorting by clicking column headers
8. WHEN a user clicks on a prospect row, THE Router SHALL navigate to the prospect detail view
9. THE Form_Validator SHALL validate required fields (nom, téléphone or email) before submission
10. THE Vue_Frontend SHALL map ProspectStatut enum values to French labels (NOUVEAU → "Nouveau", A_RAPPELER → "À rappeler", etc.)

### Requirement 4: Gestion des Partenaires

**User Story:** En tant que commercial, je veux gérer les partenaires (entreprises clientes), consulter leurs contacts et rendez-vous, afin de suivre les relations commerciales.

#### Acceptance Criteria

1. THE Resource_Manager SHALL fetch partenaires list from GET /api/v1/partenaires with pagination and filtering
2. WHEN a user creates or updates a partenaire, THE Resource_Manager SHALL send POST or PUT to /api/v1/partenaires
3. THE Data_Table SHALL display partenaires with columns: raison sociale, SIRET, adresse, téléphone, email, type organisation, statut, commercial assigné
4. WHEN a user views a partenaire detail, THE Resource_Manager SHALL fetch related contacts from GET /api/v1/partenaires/{id}/contacts
5. WHEN a user views a partenaire detail, THE Resource_Manager SHALL fetch related rendez-vous from GET /api/v1/partenaires/{id}/rendez-vous
6. THE Form_Validator SHALL validate SIRET format (14 digits) before submission
7. THE Vue_Frontend SHALL map OrganizationType, OrganizationStatus, and OrganizationCategory enum values to French labels
8. THE partenaires resource SHALL NOT expose delete functionality (as per API specification)

### Requirement 5: Gestion des Appels

**User Story:** En tant que téléprospecteur, je veux enregistrer les résultats de mes appels téléphoniques, afin de tracer l'historique de communication avec les prospects.

#### Acceptance Criteria

1. WHEN a user records a call for a prospect, THE Resource_Manager SHALL send POST /api/v1/prospects/{id}/appel with call details (date, duration, result, notes)
2. THE Vue_Frontend SHALL provide a quick-access button to record calls from the prospect list and detail views
3. THE Form_Validator SHALL require call result (EventResult enum) and notes fields
4. THE Vue_Frontend SHALL map EventResult enum values to French labels (REPONDU → "Répondu", PAS_DE_REPONSE → "Pas de réponse", etc.)
5. WHEN a call is successfully recorded, THE Notification_Service SHALL display a success toast and THE Resource_Manager SHALL refresh the prospect's call history

### Requirement 6: Gestion des Rendez-vous

**User Story:** En tant que commercial, je veux créer, visualiser et modifier des rendez-vous avec les partenaires, afin de planifier mes actions commerciales.

#### Acceptance Criteria

1. THE Resource_Manager SHALL fetch rendez-vous list from GET /api/v1/rendez-vous with date range filtering
2. WHEN a user creates a rendez-vous, THE Resource_Manager SHALL send POST /api/v1/rendez-vous with required fields (titre, date_debut, date_fin, type, statut, partenaire_id)
3. WHEN a user updates a rendez-vous, THE Resource_Manager SHALL send PUT /api/v1/rendez-vous/{id}
4. THE Data_Table SHALL display rendez-vous with columns: titre, date, heure, type, statut, partenaire, commercial
5. THE Vue_Frontend SHALL provide a calendar view (month/week/day) to visualize rendez-vous
6. THE Form_Validator SHALL validate that date_fin is after date_debut
7. THE Vue_Frontend SHALL map RendezVousType and RendezVousStatut enum values to French labels
8. THE rendez-vous resource SHALL NOT expose delete functionality (as per API specification)
9. WHEN a user selects a date range in the calendar, THE Resource_Manager SHALL filter rendez-vous accordingly

### Requirement 7: Gestion des Tickets

**User Story:** En tant qu'agent support, je veux créer, visualiser et mettre à jour des tickets de support, afin de gérer les demandes clients.

#### Acceptance Criteria

1. THE Resource_Manager SHALL support full CRUD operations on GET/POST/PUT/DELETE /api/v1/tickets
2. THE Data_Table SHALL display tickets with columns: numéro, titre, priorité, statut, client, assigné à, date de création, date de mise à jour
3. WHEN a user creates a ticket, THE Form_Validator SHALL require titre, description, priorité, and statut fields
4. THE Vue_Frontend SHALL map TicketStatut and NiveauPriorite enum values to French labels
5. WHEN a user updates ticket status, THE Resource_Manager SHALL send PUT /api/v1/tickets/{id} and refresh the ticket detail view
6. THE Data_Table SHALL support filtering by statut, priorité, and assigné

### Requirement 8: Gestion des Réclamations

**User Story:** En tant que responsable qualité, je veux traiter les réclamations clients, afin d'assurer la satisfaction et le suivi des problèmes.

#### Acceptance Criteria

1. THE Resource_Manager SHALL fetch réclamations from GET /api/v1/reclamations with pagination
2. WHEN a user creates or updates a réclamation, THE Resource_Manager SHALL send POST or PUT to /api/v1/reclamations
3. THE Data_Table SHALL display réclamations with columns: numéro, objet, statut, client, date de réclamation, date de résolution
4. THE Form_Validator SHALL require objet, description, and statut fields
5. THE Vue_Frontend SHALL map StatutReclamation enum values to French labels
6. THE réclamations resource SHALL NOT expose delete functionality (as per API specification)

### Requirement 9: Consultation des Clients

**User Story:** En tant que commercial, je veux consulter les informations des clients et leurs dossiers de formation, afin de suivre leur parcours.

#### Acceptance Criteria

1. THE Resource_Manager SHALL fetch clients list from GET /api/v1/clients (read-only)
2. THE Data_Table SHALL display clients with columns: nom, prénom, email, téléphone, entreprise, statut
3. WHEN a user views a client detail, THE Resource_Manager SHALL fetch the client from GET /api/v1/clients/{id}
4. WHEN a user views a client detail, THE Resource_Manager SHALL fetch related dossiers from GET /api/v1/clients/{id}/dossiers-formation
5. THE clients resource SHALL be read-only (no create, update, or delete operations)

### Requirement 10: Consultation des Devis et Bons de Commande

**User Story:** En tant que commercial, je veux consulter les devis et bons de commande, afin de suivre les transactions commerciales.

#### Acceptance Criteria

1. THE Resource_Manager SHALL fetch devis list from GET /api/v1/devis (read-only)
2. THE Resource_Manager SHALL fetch bons de commande list from GET /api/v1/bons-de-commande (read-only)
3. THE Data_Table SHALL display devis with columns: numéro, date, montant HT, montant TTC, statut, client
4. THE Data_Table SHALL display bons de commande with columns: numéro, date, montant HT, montant TTC, statut, client
5. THE Vue_Frontend SHALL map StatutDevis and StatutBonDeCommande enum values to French labels
6. WHEN a user clicks on a devis or bon de commande, THE Router SHALL navigate to the detail view displaying full information

### Requirement 11: Gestion des Campagnes de Phoning

**User Story:** En tant que manager de téléprospection, je veux consulter les campagnes de phoning et leurs prospects associés, afin de suivre l'avancement des campagnes.

#### Acceptance Criteria

1. THE Resource_Manager SHALL fetch campagnes list from GET /api/v1/campagnes-phoning (read-only)
2. WHEN a user views a campagne detail, THE Resource_Manager SHALL fetch the campagne from GET /api/v1/campagnes-phoning/{id}
3. WHEN a user views a campagne detail, THE Resource_Manager SHALL fetch associated prospects from GET /api/v1/campagnes-phoning/{id}/prospects
4. THE Data_Table SHALL display campagnes with columns: nom, date début, date fin, statut, nombre de prospects, nombre d'appels
5. THE Vue_Frontend SHALL map StatutCampagneProspection enum values to French labels

### Requirement 12: Tableau de Bord et Widgets

**User Story:** En tant qu'utilisateur, je veux visualiser un tableau de bord avec des statistiques et KPI pertinents selon mon rôle, afin d'avoir une vue d'ensemble de mon activité.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL display a dashboard home page with role-specific Dashboard_Widget components
2. WHERE the user has role "commercial", THE Vue_Frontend SHALL display widgets for: rendez-vous du jour, opportunités en cours, pipeline partenaires, objectifs du mois
3. WHERE the user has role "teleprospecteur", THE Vue_Frontend SHALL display widgets for: appels du jour, prospects à rappeler, taux de conversion, campagnes actives
4. WHERE the user has role "direction", THE Vue_Frontend SHALL display widgets for: KPI globaux, performance par commercial, chiffre d'affaires, nombre de partenaires
5. THE Dashboard_Widget SHALL fetch data from appropriate API endpoints based on widget type
6. THE Dashboard_Widget SHALL display Loading_State while fetching data
7. WHEN dashboard data fetch fails, THE Error_Handler SHALL display an error message and retry button
8. THE Vue_Frontend SHALL refresh dashboard widgets automatically every 5 minutes

### Requirement 13: Système de Navigation et Menu

**User Story:** En tant qu'utilisateur, je veux naviguer facilement entre les différentes sections du CRM, afin d'accéder rapidement aux fonctionnalités dont j'ai besoin.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL display a sidebar navigation menu with sections grouped by functionality (Prospection, Commercial, Support, Administration)
2. THE Router SHALL highlight the active menu item based on the current route
3. THE Permission_Guard SHALL hide menu items for which the user lacks permission
4. THE Vue_Frontend SHALL provide a collapsible sidebar to maximize content space
5. THE Vue_Frontend SHALL display a top navigation bar with: user profile dropdown, notifications icon, theme toggle, search bar
6. WHEN a user clicks the user profile dropdown, THE Vue_Frontend SHALL display options for: Mon profil, Paramètres, Déconnexion
7. THE Router SHALL support breadcrumb navigation showing the current page hierarchy

### Requirement 14: Système de Recherche Globale

**User Story:** En tant qu'utilisateur, je veux effectuer une recherche globale dans toutes les entités du CRM, afin de trouver rapidement une information.

#### Acceptance Criteria

1. WHEN a user types in the global search bar, THE Vue_Frontend SHALL send search queries to relevant API endpoints after 300ms debounce
2. THE Vue_Frontend SHALL display search results grouped by entity type (Prospects, Partenaires, Clients, Rendez-vous, Tickets)
3. WHEN a user selects a search result, THE Router SHALL navigate to the detail view of the selected entity
4. THE Vue_Frontend SHALL highlight matching text in search results
5. THE Vue_Frontend SHALL display a "Voir tous les résultats" link for each entity type when more than 5 results exist
6. WHEN no results are found, THE Vue_Frontend SHALL display "Aucun résultat trouvé" message

### Requirement 15: Gestion des Erreurs et Notifications

**User Story:** En tant qu'utilisateur, je veux être informé clairement des succès et erreurs de mes actions, afin de comprendre l'état du système.

#### Acceptance Criteria

1. WHEN an API request succeeds (2xx status), THE Notification_Service SHALL display a success toast with appropriate message
2. WHEN an API request fails with 4xx error, THE Error_Handler SHALL display an error notification with the error message returned by Backend_API
3. WHEN an API request fails with 5xx error, THE Error_Handler SHALL display a generic error message and log details to console
4. WHEN network connection is lost, THE Error_Handler SHALL display "Erreur de connexion. Vérifiez votre réseau." message
5. WHEN rate limiting is triggered (429 status), THE Error_Handler SHALL display "Trop de requêtes. Veuillez patienter." message with retry countdown
6. THE Notification_Service SHALL auto-dismiss success toasts after 3 seconds
7. THE Notification_Service SHALL require manual dismissal for error notifications
8. THE Vue_Frontend SHALL log all errors to browser console with request/response details for debugging

### Requirement 16: Système de Thèmes et Apparence

**User Story:** En tant qu'utilisateur, je veux personnaliser l'apparence de l'interface (thème clair/sombre), afin de travailler dans de bonnes conditions visuelles.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL support light and dark theme modes
2. WHEN a user toggles theme mode, THE Theme_System SHALL update all components immediately and persist the preference in localStorage
3. THE Theme_System SHALL detect system preference (prefers-color-scheme) on first visit if no saved preference exists
4. THE Vue_Frontend SHALL use a color palette consistent with modern CRM interfaces (blues, grays, accent colors)
5. THE Vue_Frontend SHALL maintain WCAG AA contrast ratios in both light and dark modes
6. THE Theme_System SHALL support custom brand colors configurable via CSS variables

### Requirement 17: Formulaires et Validation

**User Story:** En tant qu'utilisateur, je veux que les formulaires valident mes saisies en temps réel, afin de corriger les erreurs avant soumission.

#### Acceptance Criteria

1. THE Form_Validator SHALL validate required fields and display inline error messages below each field
2. THE Form_Validator SHALL validate email format using regex pattern
3. THE Form_Validator SHALL validate phone number format (French mobile/landline patterns)
4. THE Form_Validator SHALL validate SIRET format (exactly 14 digits)
5. THE Form_Validator SHALL validate date ranges (end date after start date)
6. WHEN validation fails, THE Form_Validator SHALL disable the submit button and display summary error message at top of form
7. WHEN a user corrects an invalid field, THE Form_Validator SHALL remove the error message immediately
8. THE Vue_Frontend SHALL display loading state on submit button during API request to prevent double submission

### Requirement 18: Pagination, Tri et Filtrage

**User Story:** En tant qu'utilisateur, je veux paginer, trier et filtrer les listes de données, afin de trouver facilement l'information recherchée dans de grands ensembles.

#### Acceptance Criteria

1. THE Data_Table SHALL support server-side pagination with configurable page size (10, 25, 50, 100 items per page)
2. THE Data_Table SHALL display pagination controls: first page, previous, page numbers, next, last page, and total items count
3. WHEN a user clicks a column header, THE Data_Table SHALL toggle sort order (asc/desc) and send sort parameters to Backend_API
4. THE Data_Table SHALL display sort indicators (up/down arrows) on sorted columns
5. THE Data_Table SHALL provide filter controls above each column (text input, select dropdown, date range picker)
6. WHEN a user applies filters, THE Data_Table SHALL send filter parameters to Backend_API and reset pagination to page 1
7. THE Data_Table SHALL display active filter badges with clear buttons
8. THE Data_Table SHALL persist pagination, sort, and filter state in URL query parameters to support bookmarking

### Requirement 19: Responsive Design et Mobile

**User Story:** En tant qu'utilisateur mobile, je veux accéder au CRM depuis ma tablette ou smartphone, afin de travailler en déplacement.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL be responsive and adapt layout to screen sizes: mobile (<768px), tablet (768-1024px), desktop (>1024px)
2. WHEN viewport width is mobile, THE Vue_Frontend SHALL display a hamburger menu icon and collapsible sidebar
3. WHEN viewport width is mobile, THE Data_Table SHALL stack columns vertically or use horizontal scroll with fixed first column
4. THE Vue_Frontend SHALL use touch-friendly UI elements with minimum 44px touch targets
5. THE Vue_Frontend SHALL support common mobile gestures (swipe to delete, pull to refresh) where appropriate
6. THE Vue_Frontend SHALL optimize images and assets for mobile bandwidth

### Requirement 20: Gestion des Permissions et Rôles

**User Story:** En tant qu'administrateur, je veux que les fonctionnalités soient accessibles selon les rôles utilisateurs, afin de sécuriser l'accès aux données sensibles.

#### Acceptance Criteria

1. THE Permission_Guard SHALL verify user role before rendering protected routes and components
2. THE Vue_Frontend SHALL fetch user permissions from GET /api/v1/auth/me response on login
3. THE State_Store SHALL maintain current user roles and permissions in application state
4. WHERE the user lacks permission for an action, THE Vue_Frontend SHALL hide the corresponding UI element (button, menu item, tab)
5. WHEN a user attempts to access a route without permission, THE Router SHALL redirect to an "Accès refusé" page
6. THE Vue_Frontend SHALL support role-based view customization (commercial vs téléprospecteur vs direction vs support)

### Requirement 21: Export de Données

**User Story:** En tant qu'utilisateur, je veux exporter les listes de données en format Excel ou CSV, afin d'analyser les données hors ligne.

#### Acceptance Criteria

1. WHEN a user clicks export button on a Data_Table, THE Vue_Frontend SHALL send export request to Backend_API with current filters and sorting
2. THE Vue_Frontend SHALL provide export format options: Excel (.xlsx) and CSV
3. WHEN export is processing, THE Vue_Frontend SHALL display Loading_State with progress message
4. WHEN export is complete, THE Vue_Frontend SHALL trigger browser download of the generated file
5. THE Vue_Frontend SHALL support export for: prospects, partenaires, appels, rendez-vous, opportunités
6. WHEN export fails, THE Error_Handler SHALL display error message with reason

### Requirement 22: Internationalisation (i18n)

**User Story:** En tant qu'utilisateur, je veux que l'interface soit en français, avec possibilité d'ajouter d'autres langues à l'avenir.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL use vue-i18n for internationalization support
2. THE Vue_Frontend SHALL default to French (fr-FR) locale
3. THE Vue_Frontend SHALL translate all UI labels, buttons, messages, and error texts
4. THE Vue_Frontend SHALL format dates, times, numbers, and currency according to French locale conventions
5. THE Vue_Frontend SHALL store translation keys in structured JSON files under /src/locales/fr.json
6. THE Enum_Service SHALL provide French translations for all backend enum values
7. THE Vue_Frontend SHALL support future addition of English locale without code changes

### Requirement 23: Performance et Optimisation

**User Story:** En tant qu'utilisateur, je veux une application rapide et réactive, afin de travailler efficacement sans attentes inutiles.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL achieve initial page load time under 2 seconds on standard broadband connection
2. THE Vue_Frontend SHALL implement lazy loading for routes to split code bundles by feature
3. THE State_Store SHALL implement caching strategy for frequently accessed data (enum values, user permissions) with configurable TTL
4. THE Vue_Frontend SHALL implement virtual scrolling for Data_Table with more than 100 rows
5. THE API_Client SHALL implement request deduplication to prevent duplicate simultaneous requests to same endpoint
6. THE Vue_Frontend SHALL prefetch likely next navigation targets on hover
7. WHEN a user navigates back to a previously visited list view, THE State_Store SHALL restore cached data if fresh (less than 30 seconds old)
8. THE Vue_Frontend SHALL implement debouncing (300ms) for search and filter inputs to reduce API calls

### Requirement 24: Gestion du Mode Hors Ligne

**User Story:** En tant qu'utilisateur, je veux être informé quand je perds la connexion, et pouvoir continuer à consulter les données déjà chargées.

#### Acceptance Criteria

1. WHEN network connection is lost, THE Error_Handler SHALL display a persistent banner "Vous êtes hors ligne"
2. WHEN network connection is restored, THE Error_Handler SHALL dismiss the offline banner and attempt to retry failed requests
3. WHILE offline, THE Vue_Frontend SHALL allow read-only access to cached data in State_Store
4. WHILE offline, THE Vue_Frontend SHALL disable and gray out all form submit buttons and action buttons
5. WHEN a user attempts an action while offline, THE Notification_Service SHALL display "Action impossible hors ligne" message

### Requirement 25: Accessibilité (A11y)

**User Story:** En tant qu'utilisateur avec handicap, je veux pouvoir naviguer et utiliser le CRM avec des technologies d'assistance, afin d'être autonome.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL support full keyboard navigation with visible focus indicators
2. THE Vue_Frontend SHALL provide ARIA labels and roles for all interactive elements
3. THE Modal_Dialog SHALL trap focus within the dialog and return focus to trigger element on close
4. THE Data_Table SHALL announce sort and filter changes to screen readers
5. THE Form_Validator SHALL associate error messages with form fields via aria-describedby
6. THE Vue_Frontend SHALL maintain sufficient color contrast (WCAG AA) for text and interactive elements
7. THE Vue_Frontend SHALL provide skip navigation links to main content
8. THE Notification_Service SHALL use ARIA live regions to announce notifications to screen readers

### Requirement 26: Intégration avec le Backend Existant

**User Story:** En tant que développeur, je veux que le frontend Vue.js communique correctement avec l'API Laravel existante, afin d'assurer la compatibilité complète.

#### Acceptance Criteria

1. THE API_Client SHALL configure base URL to Backend_API endpoint (configurable via environment variable)
2. THE API_Client SHALL include appropriate headers: Authorization Bearer token, Accept application/json, Content-Type application/json
3. THE API_Client SHALL handle Laravel validation errors (422 status) and map field errors to form fields
4. THE API_Client SHALL implement automatic retry with exponential backoff for failed requests (up to 3 attempts)
5. THE API_Client SHALL implement request timeout of 30 seconds
6. THE Enum_Service SHALL generate TypeScript enums from PHP enum classes in app/Enums/ directory
7. THE Vue_Frontend SHALL respect API rate limiting (60 requests/minute for auth endpoints, standard rate for others)
8. WHEN Backend_API returns structured error response, THE Error_Handler SHALL extract and display the error message field

### Requirement 27: Tests et Qualité

**User Story:** En tant que développeur, je veux que le code frontend soit testé, afin de garantir la fiabilité et faciliter la maintenance.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL include unit tests for all composables and services using Vitest
2. THE Vue_Frontend SHALL include component tests for critical components (forms, data tables, authentication) using Vue Test Utils
3. THE Vue_Frontend SHALL maintain minimum 70% code coverage for business logic (stores, services, composables)
4. THE Vue_Frontend SHALL include E2E tests for critical user flows (login, create prospect, create rendez-vous) using Playwright or Cypress
5. THE Vue_Frontend SHALL pass all ESLint rules without warnings in production build
6. THE Vue_Frontend SHALL pass TypeScript strict type checking without errors

### Requirement 28: Documentation

**User Story:** En tant que développeur, je veux une documentation claire du projet Vue.js, afin de faciliter l'onboarding et la maintenance.

#### Acceptance Criteria

1. THE Vue_Frontend SHALL include a README.md with: project overview, setup instructions, development commands, build instructions, deployment guide
2. THE Vue_Frontend SHALL document all environment variables required in a .env.example file
3. THE Vue_Frontend SHALL include JSDoc comments for all public functions, composables, and services
4. THE Vue_Frontend SHALL include a CONTRIBUTING.md with coding standards and PR process
5. THE Vue_Frontend SHALL maintain a CHANGELOG.md tracking all notable changes between versions
6. THE Vue_Frontend SHALL include Storybook documentation for all reusable UI components
