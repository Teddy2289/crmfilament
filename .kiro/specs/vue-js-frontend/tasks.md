# Implementation Plan: Vue.js Frontend CRM

## Overview

This plan converts the Vue.js CRM frontend design into incremental coding tasks, building the project from the ground up in `c:\laragon\www\crmfila\crm-vue`. Each task builds on the previous one, ending with a fully wired, tested SPA that consumes the Laravel API at `crmfilament-dev`.

All code targets **TypeScript + Vue 3 Composition API** with PrimeVue 4, Pinia, Vue Router 4, Axios, VeeValidate + Zod, vue-i18n, fast-check, and Playwright as specified in the design document.

---

## Tasks

- [x] 1. Scaffold the Vite + Vue 3 + TypeScript project
  - Run `npm create vite@latest crm-vue -- --template vue-ts` inside `c:\laragon\www\crmfila\`
  - Replace the generated `src/` with the folder structure defined in the design: `components/`, `views/`, `stores/`, `services/`, `composables/`, `types/`, `router/`, `assets/styles/`, `locales/`, `utils/`
  - Create placeholder `index.ts` barrel files in each directory
  - Configure `vite.config.ts`: path aliases (`@/` → `src/`), `server.proxy` pointing to `VITE_API_BASE_URL`
  - Create `.env.example` with `VITE_API_BASE_URL=http://localhost/api/v1`
  - Create `.env` with `VITE_API_BASE_URL=http://localhost/api/v1`
  - Add `tsconfig.json` with strict mode, path aliases, and `compilerOptions.types: ["vitest/globals"]`
  - _Requirements: 1.1, 1.2, 1.3, 28.2_

- [x] 2. Install and configure all dependencies
  - [x] 2.1 Install runtime dependencies
    - `npm install pinia vue-router@4 axios primevue@4 @primevue/themes primeicons vee-validate zod @vee-validate/zod vue-i18n @vueuse/core`
    - `npm install --save-dev vitest @vitejs/plugin-vue vue-test-utils jsdom @vitest/coverage-v8 fast-check playwright @playwright/test eslint prettier @typescript-eslint/eslint-plugin @typescript-eslint/parser eslint-plugin-vue`
    - _Requirements: 1.4, 1.5, 1.7, 27.1, 27.4_

  - [x] 2.2 Configure ESLint and Prettier
    - Create `.eslintrc.cjs` with `@typescript-eslint` and `eslint-plugin-vue` rules, `vue/setup-compiler-macros: true`
    - Create `.prettierrc` with standard settings (single quotes, trailing commas, 2-space indent)
    - Add `lint` and `format` scripts to `package.json`
    - _Requirements: 1.6, 27.5_

  - [x] 2.3 Configure Vitest
    - Create `vitest.config.ts` as specified in design: `environment: jsdom`, `globals: true`, coverage thresholds (70% lines/functions/branches), include paths for stores/services/composables/utils
    - Add `test`, `test:coverage` scripts to `package.json`
    - _Requirements: 27.1, 27.3_

  - [x] 2.4 Configure Playwright
    - Run `npx playwright install`
    - Create `playwright.config.ts` with `baseURL: 'http://localhost:5173'`, Chromium browser, `webServer` block pointing to `npm run dev`
    - Create `e2e/` directory at project root
    - Add `test:e2e` script to `package.json`
    - _Requirements: 27.4_

- [ ] 3. Implement TypeScript types, enums, and utilities
  - [x] 3.1 Create core TypeScript types
    - Implement `src/types/api.types.ts`: `PaginatedResponse<T>`, `ApiError`, `TableQueryParams` interfaces
    - Implement `src/types/auth.types.ts`: `AuthUser`, `AuthTokens`, `LoginCredentials`, `AuthState`
    - Implement `src/types/prospect.types.ts`, `partenaire.types.ts`, `rendezVous.types.ts`, `ticket.types.ts`, `reclamation.types.ts`, `client.types.ts`, `campagne.types.ts` from the design data models
    - _Requirements: 1.1, 26.1_

  - [x] 3.2 Implement TypeScript enums mirroring PHP backend
    - Implement `src/types/enums.ts` with all enums from design: `ProspectStatut`, `EventResult`, `RendezVousType`, `RendezVousStatut`, `TicketStatut`, `NiveauPriorite`, `OrganizationType`, `OrganizationStatus`, `OrganizationCategory`, `StatutReclamation`, `StatutDevis`, `StatutBonDeCommande`, `StatutCampagneProspection`
    - _Requirements: 26.6, 3.10, 4.7, 5.4, 6.7, 7.4, 8.5, 10.5, 11.5_

  - [-] 3.3 Implement enum utilities with French labels and colors
    - Implement `src/utils/enum.utils.ts`: `EnumMeta` interface, `PROSPECT_STATUT_META`, `EVENT_RESULT_META`, `RENDEZ_VOUS_TYPE_META`, `RENDEZ_VOUS_STATUT_META`, `TICKET_STATUT_META`, `NIVEAU_PRIORITE_META`, `ORGANISATION_TYPE_META`, `ORGANISATION_STATUS_META`, `ORGANISATION_CATEGORY_META`, `STATUT_RECLAMATION_META`, `STATUT_DEVIS_META`, `STATUT_BON_DE_COMMANDE_META`, `STATUT_CAMPAGNE_META`, and the generic `getEnumMeta()` function
    - _Requirements: 3.10, 4.7, 5.4, 6.7, 7.4, 8.5, 10.5, 11.5_

  - [ ]* 3.4 Write property test for enum completeness (Property 9)
    - **Property 9: Complétude des labels d'enums**
    - Create `src/utils/__tests__/enum.property.test.ts`
    - Use `fc.constantFrom(...Object.values(EnumX))` for each enum; assert `getEnumMeta()` returns `label.length > 0 && color.length > 0`
    - Run exhaustively for each enum (numRuns = enum size)
    - **Validates: Requirements 3.10, 4.7, 5.4, 6.7, 7.4, 8.5, 10.5, 11.5**

  - [-] 3.5 Implement validation utility functions
    - Implement `src/utils/validators.ts`: `validateEmail(s)`, `validatePhone(s)` (French mobile/landline), `validateSiret(s)`, `validateDateRange(start, end)` — all return `{ valid: boolean; message?: string }`
    - Implement `src/utils/date.utils.ts`: `formatDateFR(date)`, `formatDateTimeFR(date)`, `parseISODate(s)` using `Intl.DateTimeFormat` with `fr-FR` locale
    - _Requirements: 17.2, 17.3, 17.4, 17.5, 22.4_

  - [ ]* 3.6 Write property tests for validators (Properties 6, 7, 8, 10, 11)
    - Create `src/utils/__tests__/validators.property.test.ts`
    - **Property 7: SIRET validation** — assert 14-digit strings pass, all others fail (numRuns: 100)
    - **Property 8: Date range validation** — assert `date_fin <= date_debut` fails, `date_fin > date_debut` passes (numRuns: 100)
    - **Property 10: Email validation** — assert non-matching strings fail, valid email patterns pass (numRuns: 100)
    - **Property 11: Phone validation** — assert non-French patterns fail, valid 10-digit/+33 patterns pass (numRuns: 100)
    - **Validates: Requirements 17.2, 17.3, 17.4, 17.5**

- [ ] 4. Set up i18n and translations
  - [-] 4.1 Configure vue-i18n with French locale
    - Create `src/locales/fr.json` with all translation keys from the design: `common`, `auth`, `nav`, `prospects`, `partenaires`, `appels`, `rendezVous`, `tickets`, `reclamations`, `clients`, `devis`, `bonsCommande`, `campagnes`, `dashboard`, `errors`, `enums` (all enum labels for all 13 enum types), `validation`, `table`, `export`, `search`, `offline`
    - Create `src/plugins/i18n.ts`: `createI18n({ locale: 'fr-FR', fallbackLocale: 'fr-FR', messages: { 'fr-FR': frMessages } })`
    - Register plugin in `src/main.ts`
    - _Requirements: 22.1, 22.2, 22.3, 22.4, 22.5, 22.6, 22.7_

- [ ] 5. Implement the API client and auth service
  - [-] 5.1 Implement Axios API client with interceptors
    - Create `src/services/api.client.ts` with `axios.create()` configured: `baseURL: import.meta.env.VITE_API_BASE_URL`, `timeout: 30_000`, JSON headers
    - Add request interceptor: attach `Authorization: Bearer {accessToken}` from auth store; implement request deduplication via `pendingRequests` Map with `AbortController`
    - Add response interceptor: handle 401/`token_expired` → queue failed requests, call `auth.refreshAccessToken()`, replay queued requests with new token; on refresh failure call `auth.clearAuth()` and redirect to `/login`
    - Implement `withRetry<T>(fn, maxAttempts=3, baseDelayMs=500)` with exponential backoff in `src/utils/retry.utils.ts` — skip retry for 4xx except 429
    - _Requirements: 2.1, 2.5, 2.6, 26.1, 26.2, 26.4, 26.5_

  - [ ]* 5.2 Write property test for exponential retry (Property 16)
    - Create `src/utils/__tests__/retry.property.test.ts`
    - **Property 16: Retry exponentiel sur erreurs réseau**
    - Use fast-check to generate failing functions with 5xx/network errors; assert `withRetry()` calls the function at most 3 times and delays follow the 500ms/1000ms/2000ms pattern
    - Assert 4xx errors (non-429) are NOT retried
    - **Validates: Requirements 26.4**

  - [~] 5.3 Implement auth service
    - Create `src/services/auth.service.ts`: `login(credentials)`, `logout()`, `refresh(token)`, `me()` — each wrapping `apiClient` calls to `/auth/login`, `/auth/logout`, `/auth/refresh`, `/auth/me`
    - Handle Laravel 422 error mapping (extract `errors` field from response)
    - _Requirements: 2.2, 2.3, 2.4, 26.3_

- [ ] 6. Implement Pinia stores
  - [~] 6.1 Implement auth store
    - Create `src/stores/auth.store.ts` as designed: `user`, `accessToken`, `refreshToken` refs; `isAuthenticated` computed; `setTokens()`, `clearAuth()`, `login()`, `logout()`, `refreshAccessToken()`, `initializeFromStorage()` actions; localStorage persistence with `crm_access_token` / `crm_refresh_token` keys
    - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.7, 2.8_

  - [ ]* 6.2 Write property tests for auth store (Properties 3, 4)
    - Create `src/stores/__tests__/auth.store.property.test.ts`
    - **Property 3: Logout vide complètement l'état d'authentification** — generate random token pairs, call `clearAuth()`, assert all state fields null and localStorage empty (numRuns: 100)
    - **Property 4: Round-trip persistance auth localStorage** — generate random token pairs, call `setTokens()`, assert localStorage values equal originals (numRuns: 100)
    - **Validates: Requirements 2.4, 2.7**

  - [~] 6.3 Implement UI store
    - Create `src/stores/ui.store.ts`: `theme` ref (init from localStorage or `prefers-color-scheme`), `sidebarOpen` ref, `notifications` ref; `toggleTheme()` (updates localStorage + `.dark` class on `<html>`), `addNotification()`, `removeNotification()`
    - _Requirements: 16.1, 16.2, 16.3, 15.6, 15.7_

  - [ ]* 6.4 Write property test for theme persistence (Property 13)
    - Create `src/stores/__tests__/ui.store.property.test.ts`
    - **Property 13: Persistance du thème dans localStorage**
    - Use fast-check to set theme to arbitrary value, call `toggleTheme()`, assert `localStorage.getItem('crm_theme')` equals the new toggled value (numRuns: 100)
    - **Validates: Requirements 16.2**

  - [~] 6.5 Implement resource stores (prospects, partenaires, rendezVous, tickets, reclamations, clients, campagnes)
    - Create `src/stores/prospects.store.ts` following the resource store pattern from design: `items`, `current`, `meta`, `loading`, `lastFetchAt` refs; `isCacheValid()`, `fetchList()`, `fetchOne()`, `create()`, `update()`, `remove()` actions; 30s TTL cache logic
    - Repeat pattern for `partenaires.store.ts`, `rendezVous.store.ts`, `tickets.store.ts`, `reclamations.store.ts`, `clients.store.ts` (read-only: `fetchList`, `fetchOne` only), `campagnes.store.ts` (read-only)
    - _Requirements: 3.1–3.4, 4.1–4.2, 6.1–6.3, 7.1, 7.5, 8.1–8.2, 9.1–9.4, 11.1–11.3, 23.3, 23.7_

  - [ ]* 6.6 Write property test for cache idempotence (Property 17)
    - Create `src/stores/__tests__/prospects.store.property.test.ts`
    - **Property 17: Idempotence du cache (TTL)**
    - Mock `prospectsService.list()`; call `fetchList()` once, then call again within 30s without `forceRefresh`; assert `prospectsService.list()` was called exactly once (numRuns: 100)
    - **Validates: Requirements 23.3, 23.7**

- [ ] 7. Implement Vue Router with guards
  - [~] 7.1 Configure routes and navigation guards
    - Create `src/router/index.ts` with all routes from design: `/login` (public), `/` with `AppLayout` as parent (authenticated), all child routes: `dashboard`, `prospects`, `prospects/:id`, `partenaires`, `partenaires/:id`, `appels`, `rendez-vous`, `rendez-vous/calendar`, `tickets`, `tickets/:id`, `reclamations`, `reclamations/:id`, `clients`, `clients/:id`, `devis`, `devis/:id`, `bons-commande`, `bons-commande/:id`, `campagnes`, `campagnes/:id`, `errors/403`, `errors/404`
    - Implement `beforeEach` guard: skip guard for `requiresAuth: false`; redirect unauthenticated to `/login?redirect=...`; check `to.meta.permission` against `auth.user.permissions` and redirect to `forbidden` if missing
    - Add `meta: { permission: '...' }` to all resource routes matching the design
    - _Requirements: 2.9, 20.1, 20.5, 23.2_

  - [ ]* 7.2 Write property test for route guard (Property 5)
    - Create `src/router/__tests__/router.guard.property.test.ts`
    - **Property 5: Route guard redirige les utilisateurs non authentifiés**
    - Use fast-check to generate arbitrary protected route paths; assert unauthenticated navigation results in redirect to `login` with `redirect` query param preserving original path (numRuns: 100)
    - **Validates: Requirements 2.9, 20.5**

- [ ] 8. Implement core composables
  - [~] 8.1 Implement useAuth composable
    - Create `src/composables/useAuth.ts` wrapping the auth store: expose `user`, `isAuthenticated`, `login()`, `logout()`, `hasRole(role)`
    - _Requirements: 2.2, 2.4, 20.6_

  - [~] 8.2 Implement usePermissions composable
    - Create `src/composables/usePermissions.ts`: `hasPermission(permission: string): boolean` (reads from `auth.user.permissions`), `hasRole(role: string): boolean`, `canView(resource)`, `canCreate(resource)`, `canEdit(resource)`, `canDelete(resource)` helpers
    - _Requirements: 20.1, 20.2, 20.3, 20.4_

  - [ ]* 8.3 Write property test for permission guard (Property 14)
    - Create `src/composables/__tests__/usePermissions.property.test.ts`
    - **Property 14: Permission guard cache les éléments non autorisés**
    - Use fast-check to generate user permission sets and arbitrary permission strings; assert `hasPermission(p)` returns `true` iff `p` is in the user's permissions array (numRuns: 100)
    - **Validates: Requirements 20.1, 20.4**

  - [~] 8.4 Implement useDataTable composable
    - Create `src/composables/useDataTable.ts`: manage `page`, `perPage`, `sortBy`, `sortDir`, `filters` state; `buildQueryParams()` to create `TableQueryParams`; sync state to/from URL query params via `vue-router`; `resetPagination()` on filter change
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 18.8_

  - [ ]* 8.5 Write property test for table state URL round-trip (Property 12)
    - Create `src/composables/__tests__/useDataTable.property.test.ts`
    - **Property 12: Round-trip état tableau dans l'URL**
    - Use fast-check to generate arbitrary `TableQueryParams` objects; encode to URL query params, decode back, assert equality (modulo string coercion of numbers) (numRuns: 100)
    - **Validates: Requirements 18.8**

  - [~] 8.6 Implement useNotifications composable
    - Create `src/composables/useNotifications.ts`: `notifySuccess(message)` (auto-dismiss after 3s), `notifyError(message)` (manual dismiss), `notifyWarning(message)` — all delegate to `ui.store.addNotification()`
    - _Requirements: 15.1, 15.2, 15.6, 15.7, 25.8_

  - [~] 8.7 Implement useTheme, useDebounce, useOffline composables
    - Create `src/composables/useTheme.ts`: wrap `ui.store.toggleTheme()`, expose `currentTheme`, `isDark`
    - Create `src/composables/useDebounce.ts`: `useDebounce(value, delay=300)` returning a debounced ref
    - Create `src/composables/useOffline.ts` as designed: `isOffline` ref, `online`/`offline` window event listeners, failed request queue with retry on reconnect
    - _Requirements: 16.1, 16.2, 16.3, 23.8, 24.1, 24.2, 24.3, 24.4, 24.5_

- [~] 9. Checkpoint — Core infrastructure
  - Ensure all tests pass (run `npm run test`). Ensure TypeScript compiles without errors (`npm run build`). Ask the user if questions arise before continuing.

- [ ] 10. Implement resource services
  - [~] 10.1 Implement all API services
    - Create `src/services/prospects.service.ts`: `list(params)`, `get(id)`, `create(payload)`, `update(id, payload)`, `delete(id)`, `recordCall(id, payload)`
    - Create `src/services/partenaires.service.ts`: `list(params)`, `get(id)`, `create(payload)`, `update(id, payload)`, `getContacts(id)`, `getRendezVous(id)`
    - Create `src/services/rendezVous.service.ts`: `list(params)`, `get(id)`, `create(payload)`, `update(id, payload)`
    - Create `src/services/tickets.service.ts`: `list(params)`, `get(id)`, `create(payload)`, `update(id, payload)`, `delete(id)`
    - Create `src/services/reclamations.service.ts`: `list(params)`, `get(id)`, `create(payload)`, `update(id, payload)`
    - Create `src/services/clients.service.ts`: `list(params)`, `get(id)`, `getDossiers(id)`
    - Create `src/services/campagnes.service.ts`: `list(params)`, `get(id)`, `getProspects(id)`
    - Create `src/services/search.service.ts`: `globalSearch(query)` — parallel requests to prospects/partenaires/clients/rendezVous/tickets endpoints
    - _Requirements: 3.1–3.4, 4.1–4.5, 5.1, 6.1–6.3, 7.1, 8.1–8.2, 9.1–9.4, 10.1–10.2, 11.1–11.3, 14.1, 21.1_

  - [~] 10.2 Implement export service
    - Create `src/services/export.service.ts`: `exportResource(resource, format, params)` — sends export request to backend, triggers `window.URL.createObjectURL()` browser download on response
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5_

- [ ] 11. Implement theme system and global CSS
  - [~] 11.1 Configure PrimeVue theme and CSS variables
    - Configure `src/main.ts`: register `PrimeVue` with Lara preset, `darkModeSelector: '.dark'`, `cssLayer: true`; register `ToastService`, `ConfirmationService`, `DialogService`
    - Create `src/assets/styles/main.css` with CSS custom properties from design: `--crm-primary`, `--crm-secondary`, `--crm-success`, `--crm-warning`, `--crm-danger`, `--crm-surface`, `--crm-text`, `--crm-border`, and dark mode overrides under `.dark`
    - Import `primeicons/primeicons.css` and `primevue/resources/themes/lara-light-blue/theme.css` in `main.css`
    - _Requirements: 16.1, 16.4, 16.5, 16.6_

- [ ] 12. Implement layout components
  - [~] 12.1 Implement AppLayout
    - Create `src/components/layout/AppLayout.vue`: responsive grid layout with `AppSidebar` and `AppTopbar`; `<router-view>` as main content slot; apply dark/light theme class on `<html>`
    - Apply responsive breakpoints: sidebar hidden on mobile (<768px), hamburger menu visible
    - _Requirements: 13.1, 13.4, 19.1, 19.2_

  - [~] 12.2 Implement AppSidebar
    - Create `src/components/layout/AppSidebar.vue`: navigation items grouped by section (Prospection: Prospects, Appels, Campagnes; Commercial: Partenaires, Rendez-vous, Devis, Bons de commande; Support: Tickets, Réclamations, Clients; Administration); use `usePermissions()` to hide items user cannot access; highlight active route via `useRoute()`; collapsible via `ui.store.sidebarOpen`
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 20.3, 20.4_

  - [~] 12.3 Implement AppTopbar
    - Create `src/components/layout/AppTopbar.vue`: hamburger/expand button for sidebar; global search input (calls `search.service.ts` with 300ms debounce); user profile dropdown (Mon profil / Paramètres / Déconnexion); theme toggle button; notifications icon (badge count)
    - _Requirements: 13.5, 13.6, 14.1, 16.1_

  - [~] 12.4 Implement AppBreadcrumb
    - Create `src/components/layout/AppBreadcrumb.vue`: reads route meta `breadcrumb` array and renders PrimeVue `Breadcrumb` component; use `useRoute()` to generate breadcrumb items dynamically
    - _Requirements: 13.7_

- [ ] 13. Implement shared UI components
  - [~] 13.1 Implement AppDataTable
    - Create `src/components/common/AppDataTable.vue`: wraps PrimeVue `DataTable`; accepts `columns`, `items`, `meta`, `loading` props; emits `page-change`, `sort-change`, `filter-change` events; renders pagination controls (first/prev/pages/next/last + total count); renders column filter controls (text input, select dropdown, date range picker); displays sort indicator arrows; shows active filter badges with clear buttons; supports virtual scrolling when `items.length > 100`
    - Use `useDataTable()` composable internally
    - Add `aria-sort` attributes and announce sort/filter changes via `aria-live`
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 18.8, 23.4, 25.4_

  - [~] 13.2 Implement AppModal
    - Create `src/components/common/AppModal.vue`: wraps PrimeVue `Dialog`; props: `visible`, `title`, `size`; emits `close`; traps focus within dialog; returns focus to trigger on close; ARIA `role="dialog"`, `aria-modal="true"`, `aria-labelledby`
    - _Requirements: 25.3_

  - [~] 13.3 Implement AppConfirm, AppBadge, AppLoading
    - Create `src/components/common/AppConfirm.vue`: wraps PrimeVue `ConfirmDialog` for delete confirmations
    - Create `src/components/common/AppBadge.vue`: renders colored badge using `getEnumMeta()` for color; props: `value`, `enumMeta`; WCAG AA contrast colors
    - Create `src/components/common/AppLoading.vue`: skeleton loader and full-screen overlay spinner variants; props: `type: 'skeleton' | 'spinner' | 'overlay'`, `visible`
    - _Requirements: 3.4, 15.8, 25.6_

- [ ] 14. Implement authentication views
  - [~] 14.1 Implement LoginView
    - Create `src/views/auth/LoginView.vue`: email + password form using VeeValidate + Zod schema; validate email format on blur; show inline field errors; disable submit during loading with spinner; call `useAuthStore().login()`; redirect to `?redirect` param or `/dashboard` on success; display API error messages via `useNotifications()`
    - Add `data-testid` attributes: `submit-btn`, `email-input`, `password-input`, `email-error`, `password-error`
    - _Requirements: 2.2, 17.1, 17.2, 17.8, 25.1, 25.2, 25.5_

  - [ ]* 14.2 Write unit tests for LoginView
    - Create `src/views/auth/__tests__/LoginView.test.ts`
    - Test: submit button disabled when form is empty; email error shown on blur with invalid email; calls `authStore.login()` with correct credentials; redirects to dashboard on success; shows error notification on API failure
    - _Requirements: 2.2, 17.1_

- [ ] 15. Implement form components with validation
  - [~] 15.1 Implement ProspectForm
    - Create `src/components/forms/ProspectForm.vue`: fields: nom (required), prenom, entreprise, telephone (French format), email (email format), statut (select with `ProspectStatut` enum), commercial_id (select), notes; Zod schema validation; `aria-describedby` on each field linking to error message span; disable submit on validation failure; show error summary at top on submit attempt; emit `submitted` with payload on success
    - Add `data-testid` attributes on key elements
    - _Requirements: 3.9, 17.1, 17.3, 25.1, 25.5_

  - [ ]* 15.2 Write property test for prospect required field validation (Property 6)
    - Create `src/components/forms/__tests__/ProspectForm.property.test.ts`
    - **Property 6: Validation des champs obligatoires prospect**
    - Use fast-check to generate form states where `nom` is blank AND both `telephone` and `email` are blank; assert form validation rejects the submission and submit button is disabled (numRuns: 100)
    - **Validates: Requirements 3.9, 17.1**

  - [~] 15.3 Implement PartenaireForm
    - Create `src/components/forms/PartenaireForm.vue`: fields: raison_sociale (required), siret (14-digit validation), adresse, telephone, email, type_organisation (select), statut (select), categorie (select), commercial_id; Zod schema; inline errors with `aria-describedby`
    - _Requirements: 4.6, 17.4, 25.5_

  - [ ]* 15.4 Write unit tests for PartenaireForm SIRET validation
    - Create `src/components/forms/__tests__/PartenaireForm.test.ts`
    - Test: SIRET with 13 chars shows error; SIRET with 15 chars shows error; SIRET with non-digits shows error; valid 14-digit SIRET passes
    - _Requirements: 4.6, 17.4_

  - [~] 15.5 Implement RendezVousForm, AppelForm, TicketForm, ReclamationForm
    - Create `src/components/forms/RendezVousForm.vue`: titre (required), date_debut (required), date_fin (required, must be after date_debut via Zod `.refine()`), type (select), statut (select), partenaire_id (required), notes
    - Create `src/components/forms/AppelForm.vue`: date (required), duree, resultat (EventResult select, required), notes (required)
    - Create `src/components/forms/TicketForm.vue`: titre (required), description (required), priorite (NiveauPriorite select, required), statut (TicketStatut select, required), client_id, assigne_id
    - Create `src/components/forms/ReclamationForm.vue`: objet (required), description (required), statut (StatutReclamation select, required), client_id
    - All forms: Zod schemas, inline errors, disable on loading, `aria-describedby`
    - _Requirements: 6.6, 5.3, 7.3, 8.4, 17.1, 17.5, 25.5_

- [~] 16. Checkpoint — UI components and forms
  - Ensure all form validation tests pass. Ensure TypeScript strict mode passes. Ask the user if questions arise before continuing.

- [ ] 17. Implement resource list and detail views
  - [~] 17.1 Implement Prospects module views
    - Create `src/views/prospects/ProspectsListView.vue`: use `useProspectsStore()` and `useDataTable()`; render `AppDataTable` with columns (nom, prenom, entreprise, telephone, email, statut via `AppBadge`, commercial, created_at); export button; "Nouveau prospect" button (opens `AppModal` with `ProspectForm`); quick-call button per row; row click navigates to detail; use `usePermissions()` to show/hide create/delete buttons
    - Create `src/views/prospects/ProspectDetailView.vue`: fetch prospect by id; show all fields; call history section (list of appels); "Enregistrer un appel" button (opens `AppModal` with `AppelForm`); "Modifier" button; `AppBreadcrumb`
    - _Requirements: 3.1–3.10, 5.2, 20.4_

  - [~] 17.2 Implement Partenaires module views
    - Create `src/views/partenaires/PartenairesListView.vue`: `AppDataTable` with columns (raison_sociale, siret, adresse, telephone, email, type_organisation via `AppBadge`, statut, commercial); "Nouveau partenaire" button; no delete button (API spec)
    - Create `src/views/partenaires/PartenaireDetailView.vue`: partenaire fields; related contacts tab (fetched from `/partenaires/{id}/contacts`); related rendez-vous tab
    - _Requirements: 4.1–4.8_

  - [~] 17.3 Implement Appels list view
    - Create `src/views/appels/AppelsListView.vue`: `AppDataTable` with prospect name, date, duration, result via `AppBadge`, notes; filter by prospect, result, date range; no create button here (calls created from prospect views)
    - _Requirements: 5.1–5.5_

  - [~] 17.4 Implement Rendez-vous module views
    - Create `src/views/rendez-vous/RendezVousListView.vue`: `AppDataTable` with columns (titre, date_debut, date_fin, type, statut via `AppBadge`, partenaire, commercial); date range filter; "Nouveau RDV" button; no delete button; tab/link to calendar view
    - Create `src/views/rendez-vous/RendezVousCalendarView.vue`: PrimeVue `FullCalendar` or custom month/week/day calendar; fetch rendez-vous for selected date range; click event opens detail modal; create button per day cell
    - _Requirements: 6.1–6.9_

  - [~] 17.5 Implement Tickets module views
    - Create `src/views/tickets/TicketsListView.vue`: `AppDataTable` with columns (numero, titre, priorite via `AppBadge`, statut, client, assigne, created_at, updated_at); filter by statut, priorite, assigne; "Nouveau ticket" button; delete button (with `AppConfirm`)
    - Create `src/views/tickets/TicketDetailView.vue`: all ticket fields; status update button; `AppBreadcrumb`
    - _Requirements: 7.1–7.6_

  - [~] 17.6 Implement Réclamations module views
    - Create `src/views/reclamations/ReclamationsListView.vue`: `AppDataTable` with columns (numero, objet, statut via `AppBadge`, client, date_reclamation, date_resolution); "Nouvelle réclamation" button; no delete button
    - Create `src/views/reclamations/ReclamationDetailView.vue`
    - _Requirements: 8.1–8.6_

  - [~] 17.7 Implement Clients module views (read-only)
    - Create `src/views/clients/ClientsListView.vue`: `AppDataTable` with columns (nom, prenom, email, telephone, entreprise, statut); no create/edit/delete buttons
    - Create `src/views/clients/ClientDetailView.vue`: client info + dossiers de formation tab
    - _Requirements: 9.1–9.5_

  - [~] 17.8 Implement Devis and Bons de commande views (read-only)
    - Create `src/views/devis/DevisListView.vue` + `DevisDetailView.vue`: columns (numero, date, montant_ht, montant_ttc, statut via `AppBadge`, client); read-only
    - Create `src/views/bons-commande/BonsCommandeListView.vue` + `BonCommandeDetailView.vue`: same column pattern for bons de commande
    - _Requirements: 10.1–10.6_

  - [~] 17.9 Implement Campagnes module views (read-only)
    - Create `src/views/campagnes/CampagnesListView.vue`: `AppDataTable` with columns (nom, date_debut, date_fin, statut via `AppBadge`, nb_prospects, nb_appels); read-only
    - Create `src/views/campagnes/CampagneDetailView.vue`: campagne info + prospects tab
    - _Requirements: 11.1–11.5_

  - [~] 17.10 Implement error views
    - Create `src/views/errors/NotFoundView.vue`: 404 page with back-to-dashboard link
    - Create `src/views/errors/ForbiddenView.vue`: 403 page with permission message
    - _Requirements: 20.5_

- [ ] 18. Implement Dashboard and widgets
  - [~] 18.1 Implement DashboardView with role-based widgets
    - Create `src/views/dashboard/DashboardView.vue`: use `usePermissions()` / `hasRole()` to render role-specific widget grid; commercial role: `WidgetRendezVousDuJour`, `WidgetOpportunitesEnCours`, pipeline widget, objectifs widget; teleprospecteur role: `WidgetAppelsDuJour`, `WidgetProspectsARappeler`, taux de conversion widget, campagnes actives widget; direction role: `WidgetKpiGlobaux`, performance widget, CA widget, nb partenaires widget; auto-refresh every 5 minutes via `setInterval`
    - _Requirements: 12.1–12.8_

  - [~] 18.2 Implement dashboard widget components
    - Create `src/components/dashboard/WidgetCard.vue`: base card wrapper with title, loading state (`AppLoading`), error state (error message + retry button)
    - Create `src/components/dashboard/WidgetAppelsDuJour.vue`: fetches today's calls count from appropriate endpoint
    - Create `src/components/dashboard/WidgetProspectsARappeler.vue`: fetches prospects with `statut = RP` count
    - Create `src/components/dashboard/WidgetRendezVousDuJour.vue`: fetches today's rendez-vous
    - Create `src/components/dashboard/WidgetKpiGlobaux.vue`: fetches global KPIs for direction role
    - All widgets: show `AppLoading` while fetching; show error + retry on failure
    - _Requirements: 12.1–12.8_

- [ ] 19. Implement global search
  - [~] 19.1 Implement global search with debouncing
    - Create the search results dropdown in `AppTopbar.vue`: when user types ≥ 2 chars, call `search.service.globalSearch(query)` after 300ms debounce; display results grouped by entity type (Prospects, Partenaires, Clients, Rendez-vous, Tickets) in PrimeVue `OverlayPanel`; highlight matching text using a `highlightMatch(text, query)` utility; show "Voir tous les résultats" link when entity has > 5 results; navigate to entity detail on selection; show "Aucun résultat trouvé" when empty
    - _Requirements: 14.1–14.6, 23.8_

- [ ] 20. Implement error handling and notification system
  - [~] 20.1 Implement global error handler
    - Create `src/composables/useErrorHandler.ts`: `handleApiError(error: AxiosError)` — dispatch to correct notification based on status code: 401 (non-token-expired) → redirect to login; 403 → "Accès refusé"; 404 → "Ressource introuvable"; 422 → map field errors; 429 → "Trop de requêtes" toast with retry countdown; 5xx → generic error + `console.error`; network error → "Erreur de connexion. Vérifiez votre réseau."
    - Register global `axios` error interceptor fallback in `api.client.ts` after auth interceptor
    - _Requirements: 15.2, 15.3, 15.4, 15.5, 15.8, 26.8_

  - [~] 20.2 Implement notification toast component
    - Wire PrimeVue `Toast` component in `App.vue`; configure for ARIA live region (`aria-live="polite"` for success, `aria-live="assertive"` for error); auto-dismiss success after 3000ms; require manual dismiss for errors
    - Implement offline banner: persistent `<div>` in `AppLayout.vue` that shows when `useOffline().isOffline` is true, with message "Vous êtes hors ligne"; dismiss when online
    - Implement offline action prevention: `useOffline()` returns `disabledWhileOffline` computed; apply to all form submit buttons and action buttons
    - _Requirements: 15.1, 15.6, 15.7, 24.1, 24.2, 24.4, 24.5, 25.8_

- [ ] 21. Implement export functionality
  - [~] 21.1 Implement export buttons and flow
    - Add export button (split button with Excel/.xlsx and CSV options) to `AppDataTable.vue` toolbar; on click call `export.service.exportResource(resource, format, currentFilters)`; show `AppLoading` overlay during export; trigger browser download via `Blob` URL on completion; show error toast on failure
    - _Requirements: 21.1–21.6_

- [ ] 22. Implement offline mode and accessibility
  - [~] 22.1 Implement offline mode support
    - Wire `useOffline()` in `App.vue`; when `isOffline` is true: disable all form submit buttons, action buttons (create/update/delete), and show "Action impossible hors ligne" toast when user tries an action; allow reading from store cache; dismiss banner and retry queued requests when back online
    - _Requirements: 24.1–24.5_

  - [~] 22.2 Implement accessibility features
    - Add `skip to main content` link at top of `AppLayout.vue` (visually hidden, visible on focus)
    - Ensure all `<button>` elements have accessible labels (aria-label where text is not present)
    - Ensure `AppDataTable` announces sort/filter changes with `aria-live="polite"`
    - Ensure all form error messages are linked via `aria-describedby` (verify in forms)
    - Ensure `AppModal` focus trap is working and returns focus on close
    - Run `axe-core` accessibility checks in component tests
    - _Requirements: 25.1–25.8_

- [~] 23. Checkpoint — Full feature integration
  - Ensure all modules render correctly. Run full test suite (`npm run test`). Run TypeScript build (`npm run build`). Fix any lint errors. Ask the user if questions arise before continuing.

- [ ] 24. Write property-based tests for API client behaviors
  - [~] 24.1 Write property test for Bearer token presence (Property 2)
    - Create `src/services/__tests__/api.client.property.test.ts`
    - **Property 2: Bearer token présent dans toutes les requêtes authentifiées**
    - Mock auth store with arbitrary `accessToken` values; intercept Axios requests; assert every request config has `Authorization: Bearer {token}` header when `isAuthenticated` is true (numRuns: 100)
    - **Validates: Requirements 2.5**

  - [~] 24.2 Write property test for token refresh interceptor (Property 1)
    - Add to `src/services/__tests__/api.client.property.test.ts`
    - **Property 1: Token refresh déclenché sur 401/token_expired**
    - Use fast-check to generate request configs; simulate 401 response with `code: "token_expired"`; assert the interceptor calls refresh endpoint exactly once and replays original request with new token (numRuns: 50)
    - **Validates: Requirements 2.1, 2.6**

  - [~] 24.3 Write property test for Laravel 422 error mapping (Property 15)
    - Create `src/composables/__tests__/useFormValidator.property.test.ts`
    - **Property 15: Mapping des erreurs 422 sur les champs de formulaire**
    - Use fast-check to generate arbitrary `Record<string, string[]>` error objects; call form error mapper; assert each error is assigned to the matching field key and no other fields are affected (numRuns: 100)
    - **Validates: Requirements 26.3**

- [ ] 25. Write E2E tests with Playwright
  - [ ]* 25.1 Write E2E test for login flow
    - Create `e2e/auth.spec.ts`
    - Test: navigate to `/login`, fill valid credentials, submit, assert redirect to `/dashboard`
    - Test: fill invalid credentials, assert error message shown, stay on `/login`
    - Test: navigate to protected route when unauthenticated, assert redirect to `/login` with `redirect` query param
    - _Requirements: 2.2, 2.9, 27.4_

  - [ ]* 25.2 Write E2E test for prospect CRUD
    - Create `e2e/prospects.spec.ts`
    - Test: login, navigate to `/prospects`, click "Nouveau prospect", fill form, submit, assert success toast and new row in table
    - Test: click on prospect row, assert navigation to detail view
    - Test: open edit modal, change `statut`, save, assert updated badge in list
    - _Requirements: 3.1–3.9, 27.4_

  - [ ]* 25.3 Write E2E test for rendez-vous creation
    - Create `e2e/rendez-vous.spec.ts`
    - Test: login, navigate to `/rendez-vous`, create new rendez-vous with valid dates, assert success and appearance in list
    - Test: attempt to set `date_fin` before `date_debut`, assert validation error
    - _Requirements: 6.1–6.6, 27.4_

  - [ ]* 25.4 Write E2E test for theme toggle
    - Create `e2e/theme.spec.ts`
    - Test: click theme toggle, assert `<html>` has class `dark`; click again, assert class removed; reload page, assert theme persists from localStorage
    - _Requirements: 16.1, 16.2, 27.4_

- [ ] 26. Implement responsive design refinements
  - [~] 26.1 Implement responsive layout for mobile and tablet
    - In `AppLayout.vue`: add CSS media queries / PrimeFlex grid classes for mobile (<768px), tablet (768–1024px), desktop (>1024px)
    - Ensure sidebar collapses to off-canvas drawer on mobile; hamburger toggles it
    - In `AppDataTable.vue`: on mobile, enable horizontal scroll with fixed first column; minimum 44px row height for touch targets
    - Add `touch-action` styles and swipe gesture support where specified
    - _Requirements: 19.1–19.6_

- [ ] 27. Write documentation
  - [~] 27.1 Create README.md, .env.example, and JSDoc
    - Create `README.md` in `crm-vue/` with: project overview, tech stack, prerequisites, setup instructions (`npm install`, configure `.env`), development commands (`npm run dev`), build instructions (`npm run build`), test commands (`npm run test`, `npm run test:e2e`), deployment guide
    - Verify `.env.example` exists with all required variables
    - Add JSDoc comments to all public functions in: `src/services/*.ts`, `src/composables/*.ts`, `src/stores/*.ts`, `src/utils/*.ts`
    - Create `CONTRIBUTING.md` with branch naming, commit conventions, PR checklist
    - _Requirements: 28.1, 28.2, 28.3, 28.4_

- [~] 28. Final checkpoint — Full build and test suite
  - Run full test suite with coverage: `npm run test:coverage`. Ensure ≥ 70% coverage for stores/services/composables/utils. Run E2E tests: `npm run test:e2e`. Run TypeScript build: `npm run build`. Fix any ESLint warnings (`npm run lint`). Ask the user if questions arise.

---

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP delivery
- All code targets `c:\laragon\www\crmfila\crm-vue` — a **separate** project from the Laravel backend
- The backend API is at `c:\laragon\www\crmfila\crmfilament-dev` and must not be modified
- Each task references specific requirements for full traceability
- Checkpoints (tasks 9, 16, 23, 28) ensure incremental validation throughout the build
- Property tests use **fast-check** with `numRuns: 100` minimum, covering all 17 design properties
- Unit tests use **Vitest + Vue Test Utils**; E2E tests use **Playwright**
- The 17 correctness properties from the design are distributed across tasks:
  - Property 1 → task 24.2 | Property 2 → task 24.1 | Property 3 → task 6.2
  - Property 4 → task 6.2 | Property 5 → task 7.2 | Property 6 → task 15.2
  - Property 7 → task 3.6 | Property 8 → task 3.6 | Property 9 → task 3.4
  - Property 10 → task 3.6 | Property 11 → task 3.6 | Property 12 → task 8.5
  - Property 13 → task 6.4 | Property 14 → task 8.3 | Property 15 → task 24.3
  - Property 16 → task 5.2 | Property 17 → task 6.6

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["2.1"] },
    { "id": 1, "tasks": ["2.2", "2.3", "2.4"] },
    { "id": 2, "tasks": ["3.1", "3.2"] },
    { "id": 3, "tasks": ["3.3", "3.5", "4.1", "5.1"] },
    { "id": 4, "tasks": ["3.4", "3.6", "5.2", "5.3", "6.1", "6.3"] },
    { "id": 5, "tasks": ["6.2", "6.4", "6.5", "7.1", "8.1", "8.2", "11.1"] },
    { "id": 6, "tasks": ["6.6", "7.2", "8.3", "8.4", "8.5", "8.6", "8.7", "10.1", "10.2"] },
    { "id": 7, "tasks": ["12.1", "12.2", "12.3", "12.4", "13.1", "13.2", "13.3", "14.1"] },
    { "id": 8, "tasks": ["14.2", "15.1", "15.3", "15.5"] },
    { "id": 9, "tasks": ["15.2", "15.4", "17.1", "17.2", "17.3", "17.4", "17.5", "17.6", "17.7", "17.8", "17.9", "17.10"] },
    { "id": 10, "tasks": ["18.1", "18.2", "19.1", "20.1", "20.2", "21.1", "22.1", "22.2"] },
    { "id": 11, "tasks": ["24.1", "24.2", "24.3", "26.1"] },
    { "id": 12, "tasks": ["25.1", "25.2", "25.3", "25.4", "27.1"] }
  ]
}
```
