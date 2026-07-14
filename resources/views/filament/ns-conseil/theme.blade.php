<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800;1,14..32,400&display=swap" rel="stylesheet">

<style>
/* ════════════════════════════════════════════════════════
   NS CONSEIL — Thème "Marine & Doré"
   Filament v3 — Juillet 2026
   Palette extraite du visuel NS CONSEIL :
   #2C4A5E (bleu marine) · #3F8FA3 (teal titre) · #E8B873 (doré/tan)
   #D9A455 (doré accent) · #FFFFFF
════════════════════════════════════════════════════════ */

:root {
    /* Couleurs de marque (inchangées) */
    --ns-primary: #2C4A5E;
    --ns-primary-dark: #1E3444;
    --ns-teal: #3F8FA3;
    --ns-gold: #E8B873;
    --ns-gold-accent: #D9A455;

    /* Neutres & tokens de cohérence — dérivés, pas de nouvelle couleur de marque */
    --ns-text: #1e293b;
    --ns-text-muted: #64748b;
    --ns-border: #e2e8f0;
    --ns-border-soft: #f1f5f9;
    --ns-surface: #ffffff;
    --ns-surface-muted: #f8fafc;
    --ns-bg: #f4f7fb;

    --ns-radius-sm: 6px;
    --ns-radius-md: 8px;
    --ns-radius-lg: 10px;
    --ns-radius-xl: 12px;

    --ns-shadow-xs: 0 1px 3px rgba(30, 52, 68, 0.04);
    --ns-shadow-sm: 0 2px 6px rgba(30, 52, 68, 0.08);
    --ns-shadow-md: 0 6px 16px rgba(30, 52, 68, 0.10);
    --ns-shadow-lg: 0 12px 36px rgba(30, 52, 68, 0.18);
}

/* ── Base typographie ─────────────────────────────────── */
.fi-body {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
    font-size: 13.5px !important;
    line-height: 1.5 !important;
    letter-spacing: -0.01em !important;
    color: var(--ns-text) !important;
    background: var(--ns-bg) !important;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

h1, h2, h3, .fi-header-heading, .fi-modal-heading, .fi-section-header-heading {
    font-weight: 700 !important;
    letter-spacing: -0.02em !important;
}

/* ── Sidebar — dégradé bleu marine ────────────────────── */
.fi-sidebar {
    background: linear-gradient(180deg, var(--ns-primary) 0%, var(--ns-primary-dark) 100%) !important;
    border-right: none !important;
    box-shadow: 2px 0 12px rgba(44, 74, 94, 0.15) !important;
}

.fi-sidebar-header {
    background: transparent !important;
    border-bottom: 1px solid rgba(232, 184, 115, 0.18) !important;
    padding-block: 14px !important;
}

.fi-sidebar-nav {
    padding: 18px 16px 16px !important;
}

.fi-sidebar-group-label {
    font-size: 10px !important;
    font-weight: 700 !important;
    letter-spacing: 0.08em !important;
    color: rgba(232, 184, 115, 0.6) !important;
    text-transform: uppercase !important;
}

.fi-sidebar-item a,
.fi-sidebar-item button {
    border-radius: 7px !important;
    min-height: 36px !important;
    transition: background 0.15s ease, transform 0.1s ease !important;
}

.fi-sidebar-item-label,
.fi-sidebar-item-icon {
    font-size: 13px !important;
    color: rgba(255, 255, 255, 0.82) !important;
    transition: color 0.15s ease !important;
}

/* Survol — fond doré : le texte doit rester lisible dessus */
.fi-sidebar-item:not(.fi-sidebar-item-active) a:hover,
.fi-sidebar-item:not(.fi-sidebar-item-active) button:hover {
    background: var(--ns-gold) !important;
}

.fi-sidebar-item:not(.fi-sidebar-item-active) a:hover .fi-sidebar-item-label,
.fi-sidebar-item:not(.fi-sidebar-item-active) a:hover .fi-sidebar-item-icon,
.fi-sidebar-item:not(.fi-sidebar-item-active) button:hover .fi-sidebar-item-label,
.fi-sidebar-item:not(.fi-sidebar-item-active) button:hover .fi-sidebar-item-icon {
    color: var(--ns-primary-dark) !important;
}

/* Item actif — accent doré qui ressort sur le marine */
.fi-sidebar-item-active > a,
.fi-sidebar-item-active > button {
    background: var(--ns-gold) !important;
    box-shadow: 0 2px 8px rgba(232, 184, 115, 0.45) !important;
}

.fi-sidebar-item-active .fi-sidebar-item-label,
.fi-sidebar-item-active .fi-sidebar-item-icon {
    color: var(--ns-primary-dark) !important;
    font-weight: 700 !important;
}

/* Badges de compteur dans le sidebar */
.fi-sidebar-item .fi-badge {
    background: var(--ns-gold-accent) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
}

/* Groupes collapsibles */
.fi-sidebar-group-button svg {
    color: rgba(232, 184, 115, 0.6) !important;
}

/* ── Topbar ───────────────────────────────────────────── */
.fi-topbar {
    background: var(--ns-surface) !important;
    border-bottom: 1px solid var(--ns-border) !important;
    box-shadow: var(--ns-shadow-xs) !important;
}

.fi-topbar nav {
    background: var(--ns-surface) !important;
}

/* ── En-têtes de page ─────────────────────────────────── */
.fi-header-heading {
    font-size: 20px !important;
    color: var(--ns-primary) !important;
}

.fi-header-subheading {
    font-size: 13px !important;
    color: var(--ns-text-muted) !important;
}

.fi-simple-header {
    background: var(--ns-primary-dark) !important;
    margin: 0 !important;
    padding: 1.75rem 1.5rem 1.25rem !important;
    border-radius: var(--ns-radius-lg) var(--ns-radius-lg) 0 0 !important;
    box-shadow: var(--ns-shadow-xs) !important;
}

.fi-simple-header-heading {
    font-size: 19px !important;
    font-weight: 700 !important;
    color: var(--ns-teal) !important;
    letter-spacing: -0.02em !important;
}

/* Sous-titre de la page de connexion — accent doré cohérent avec la marque */
.fi-simple-page .fi-simple-header-subheading {
    color: var(--ns-gold) !important;
}

.fi-simple-header-subheading {
    font-size: 13px !important;
    color: var(--ns-text-muted) !important;
}

/* Labels des champs sur la page de connexion — alignés sur le reste des formulaires */
.fi-simple-page .fi-fo-field-wrp-label label {
    color: #475569 !important;
}

/* ── Sections / Cards ─────────────────────────────────── */
.fi-section,
.fi-wi-widget {
    border: 1px solid var(--ns-border) !important;
    box-shadow: var(--ns-shadow-xs) !important;
    border-radius: var(--ns-radius-lg) !important;
    transition: box-shadow 0.2s ease !important;
}

.fi-section:hover,
.fi-wi-widget:hover {
    box-shadow: var(--ns-shadow-md) !important;
}

.fi-section-header {
    background: var(--ns-surface-muted) !important;
    border-bottom: 1px solid var(--ns-border) !important;
    padding-block: 11px !important;
    border-radius: var(--ns-radius-lg) var(--ns-radius-lg) 0 0 !important;
}

.fi-section-header-heading {
    font-size: 13.5px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
}

.fi-section-content {
    padding: 14px !important;
}

/* ── Tables ───────────────────────────────────────────── */
.fi-ta {
    border: 1px solid var(--ns-border) !important;
    box-shadow: var(--ns-shadow-xs) !important;
    border-radius: var(--ns-radius-lg) !important;
    overflow: hidden !important;
}

.fi-ta-table thead tr {
    background: linear-gradient(180deg, var(--ns-surface-muted) 0%, #f1f5f9 100%) !important;
}

.fi-ta-header-cell-label {
    font-size: 11.5px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}

.fi-ta-cell {
    font-size: 13px !important;
    color: #334155 !important;
    border-color: var(--ns-border-soft) !important;
    padding-block: 9px !important;
}

.fi-ta-row {
    transition: background 0.12s ease !important;
}

.fi-ta-row:hover > * {
    background: rgba(232, 184, 115, 0.14) !important;
}

.fi-ta-header-toolbar,
.fi-ta-toolbar {
    background: var(--ns-surface) !important;
    border-color: var(--ns-border) !important;
    padding: 10px 14px !important;
    gap: 8px !important;
}

.fi-ta-pagination {
    background: var(--ns-surface-muted) !important;
    border-top: 1px solid var(--ns-border) !important;
}

.fi-ta-empty-state-heading {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
}

.fi-ta-empty-state-description {
    font-size: 12.5px !important;
    color: var(--ns-text-muted) !important;
}

/* ── Formulaires ──────────────────────────────────────── */
.fi-fo-field-wrp-label label {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #475569 !important;
}

.fi-fo-field-wrp-helper-text {
    font-size: 11.5px !important;
    color: #94a3b8 !important;
}

.fi-input-wrp,
.fi-select-input,
.fi-textarea {
    border-color: #cbd5e1 !important;
    border-width: 1.5px !important;
    border-radius: var(--ns-radius-sm) !important;
    box-shadow: none !important;
    background: var(--ns-surface) !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}

.fi-input,
.fi-select-input,
.fi-textarea {
    font-size: 13px !important;
    color: var(--ns-text) !important;
}

.fi-input-wrp:focus-within,
.fi-select-input:focus,
.fi-textarea:focus {
    border-color: var(--ns-primary) !important;
    box-shadow: 0 0 0 3px rgba(44, 74, 94, 0.12) !important;
}

.fi-input::placeholder,
.fi-textarea::placeholder {
    color: #a0aec0 !important;
    font-style: italic !important;
    font-size: 12.5px !important;
}

.fi-input:disabled,
.fi-select-input:disabled,
.fi-textarea:disabled {
    background: var(--ns-surface-muted) !important;
    border-color: var(--ns-border) !important;
    color: #94a3b8 !important;
    cursor: not-allowed !important;
}

/* ── Boutons ──────────────────────────────────────────── */
.fi-btn {
    border-radius: var(--ns-radius-sm) !important;
    box-shadow: none !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    min-height: 34px !important;
    transition: transform 0.1s ease, box-shadow 0.15s ease !important;
}

.fi-btn:active {
    transform: scale(0.97) !important;
}

.fi-icon-btn {
    border-radius: var(--ns-radius-sm) !important;
    transition: background 0.15s ease !important;
}

/* Bouton primaire — dégradé bleu marine */
.fi-btn[type="submit"],
.fi-ac-btn-action.fi-color-primary,
.fi-btn.fi-color-primary {
    background: linear-gradient(135deg, var(--ns-primary) 0%, var(--ns-primary-dark) 100%) !important;
    box-shadow: 0 2px 6px rgba(44, 74, 94, 0.28) !important;
}

.fi-btn[type="submit"]:hover,
.fi-ac-btn-action.fi-color-primary:hover,
.fi-btn.fi-color-primary:hover {
    box-shadow: 0 4px 10px rgba(44, 74, 94, 0.38) !important;
    transform: translateY(-1px) !important;
}

/* Boutons des autres couleurs — même profondeur, palette de chaque couleur inchangée */
.fi-ac-btn-action.fi-color-success,
.fi-btn.fi-color-success {
    box-shadow: 0 2px 6px rgb(var(--success-600) / 0.32) !important;
}

.fi-ac-btn-action.fi-color-warning,
.fi-btn.fi-color-warning {
    box-shadow: 0 2px 6px rgb(var(--warning-600) / 0.30) !important;
}

.fi-ac-btn-action.fi-color-danger,
.fi-btn.fi-color-danger {
    box-shadow: 0 2px 6px rgb(var(--danger-600) / 0.32) !important;
}

.fi-ac-btn-action.fi-color-info,
.fi-btn.fi-color-info {
    box-shadow: 0 2px 6px rgb(var(--info-600) / 0.30) !important;
}

.fi-ac-btn-action.fi-color-secondary,
.fi-btn.fi-color-secondary {
    box-shadow: 0 2px 6px rgb(var(--secondary-600) / 0.30) !important;
}

.fi-ac-btn-action.fi-color-gray,
.fi-btn.fi-color-gray {
    box-shadow: var(--ns-shadow-xs) !important;
}

.fi-ac-btn-action.fi-color-success:hover,
.fi-btn.fi-color-success:hover,
.fi-ac-btn-action.fi-color-warning:hover,
.fi-btn.fi-color-warning:hover,
.fi-ac-btn-action.fi-color-danger:hover,
.fi-btn.fi-color-danger:hover,
.fi-ac-btn-action.fi-color-info:hover,
.fi-btn.fi-color-info:hover,
.fi-ac-btn-action.fi-color-secondary:hover,
.fi-btn.fi-color-secondary:hover,
.fi-ac-btn-action.fi-color-gray:hover,
.fi-btn.fi-color-gray:hover {
    transform: translateY(-1px) !important;
}

.fi-btn:disabled {
    box-shadow: none !important;
    transform: none !important;
    opacity: 0.6 !important;
}

/* Accessibilité clavier — anneau de focus cohérent avec la marque */
.fi-btn:focus-visible,
.fi-icon-btn:focus-visible,
.fi-ac-btn-action:focus-visible {
    outline: 2px solid var(--ns-teal) !important;
    outline-offset: 2px !important;
}

/* ── Badges ───────────────────────────────────────────── */
.fi-badge {
    border-radius: 5px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    padding-inline: 7px !important;
}

/* ── Tabs ─────────────────────────────────────────────── */
.fi-tabs {
    background: var(--ns-surface) !important;
    border: 1px solid var(--ns-border) !important;
    box-shadow: none !important;
    border-radius: var(--ns-radius-md) !important;
}

.fi-tabs-item {
    font-size: 13px !important;
    font-weight: 500 !important;
    transition: color 0.15s ease !important;
}

.fi-tabs-item-active {
    color: var(--ns-teal) !important;
    font-weight: 700 !important;
}

/* ── Modals ───────────────────────────────────────────── */
.fi-modal-window {
    border-radius: var(--ns-radius-lg) !important;
    border: 1px solid var(--ns-border) !important;
    box-shadow: 0 12px 36px rgba(30, 52, 68, 0.18) !important;
}

.fi-modal-header {
    background: var(--ns-surface-muted) !important;
    border-bottom: 1px solid var(--ns-border) !important;
}

.fi-modal-heading {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
}

/* ── Notifications ────────────────────────────────────── */
.fi-notification {
    border-radius: var(--ns-radius-md) !important;
    box-shadow: 0 6px 20px rgba(30, 52, 68, 0.14) !important;
    border: 1px solid var(--ns-border) !important;
    border-left: 4px solid var(--ns-primary) !important;
}

.fi-notification.fi-color-success {
    border-left-color: var(--ns-gold-accent) !important;
}

.fi-notification.fi-color-warning {
    border-left-color: var(--ns-gold) !important;
}

/* ── Stats / Widgets — accent doré en haut + icône teintée ─ */
.fi-wi-stats-overview-stat {
    border: 1px solid var(--ns-border) !important;
    border-radius: var(--ns-radius-lg) !important;
    box-shadow: var(--ns-shadow-xs) !important;
    position: relative !important;
    overflow: hidden !important;
    transition: box-shadow 0.2s ease, transform 0.15s ease !important;
}

.fi-wi-stats-overview-stat::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--ns-primary), var(--ns-gold));
}

.fi-wi-stats-overview-stat:hover {
    box-shadow: var(--ns-shadow-md) !important;
    transform: translateY(-1px) !important;
}

.fi-wi-stats-overview-stat-label {
    font-size: 12px !important;
    color: var(--ns-text-muted) !important;
    font-weight: 600 !important;
}

.fi-wi-stats-overview-stat-value {
    font-size: 26px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
    letter-spacing: -0.03em !important;
}

.fi-wi-stats-overview-stat-description {
    font-weight: 600 !important;
}

/* Icône de la stat — teintée avec la couleur de la description quand le navigateur le permet */
.fi-wi-stats-overview-stat-icon {
    border-radius: var(--ns-radius-sm) !important;
    padding: 3px !important;
    box-sizing: content-box !important;
}

.fi-wi-stats-overview-stat:has(.fi-color-success) .fi-wi-stats-overview-stat-icon {
    color: rgb(var(--success-600)) !important;
    background: rgb(var(--success-600) / 0.12) !important;
}

.fi-wi-stats-overview-stat:has(.fi-color-warning) .fi-wi-stats-overview-stat-icon {
    color: rgb(var(--warning-600)) !important;
    background: rgb(var(--warning-600) / 0.12) !important;
}

.fi-wi-stats-overview-stat:has(.fi-color-danger) .fi-wi-stats-overview-stat-icon {
    color: rgb(var(--danger-600)) !important;
    background: rgb(var(--danger-600) / 0.12) !important;
}

.fi-wi-stats-overview-stat:has(.fi-color-primary) .fi-wi-stats-overview-stat-icon {
    color: var(--ns-primary) !important;
    background: rgba(44, 74, 94, 0.10) !important;
}

/* ── Infolist ─────────────────────────────────────────── */
.fi-in-entry-wrp-label {
    font-size: 11.5px !important;
    font-weight: 700 !important;
    color: var(--ns-text-muted) !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}

.fi-in-text,
.fi-in-text-entry {
    font-size: 13px !important;
    color: var(--ns-text) !important;
}

/* ── Dropdown ─────────────────────────────────────────── */
.fi-dropdown-panel {
    border: 1px solid var(--ns-border) !important;
    border-radius: var(--ns-radius-md) !important;
    box-shadow: 0 8px 24px rgba(30, 52, 68, 0.1) !important;
}

.fi-dropdown-list-item:hover {
    background: var(--ns-surface-muted) !important;
}

/* ── Page login ───────────────────────────────────────── */
.fi-simple-page {
    padding: 20px !important;
    border-radius: var(--ns-radius-lg) !important;
}

.fi-simple-main {
    border: none !important;
    border-radius: var(--ns-radius-xl) !important;
    box-shadow: 0 20px 50px rgba(30, 52, 68, 0.3) !important;
    overflow: hidden !important;
}

/* ── Scrollbar ─────────────────────────────────────────── */
* {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

*::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

*::-webkit-scrollbar-track {
    background: transparent;
}

*::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

*::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.fi-sidebar *::-webkit-scrollbar-thumb {
    background: rgba(232, 184, 115, 0.3);
}

.fi-sidebar *::-webkit-scrollbar-thumb:hover {
    background: rgba(232, 184, 115, 0.45);
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 768px) {
    .fi-header-heading {
        font-size: 16px !important;
    }
}

.fi-logo {
    text-align: center;
}

/* ── Widget : progression d'import ─────────────────────── */
.ns-import-progress {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: var(--ns-surface) !important;
    border: 1px solid var(--ns-border);
    border-radius: var(--ns-radius-lg);
    box-shadow: var(--ns-shadow-xs);
    padding: 14px 16px;
    margin-bottom: 4px;
    border-left: 4px solid var(--ns-primary);
}

.ns-import-progress--done {
    border-left-color: var(--ns-gold-accent);
}

.ns-import-progress--failed {
    border-left-color: #dc2626;
}

.ns-import-progress-icon {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    color: var(--ns-primary);
    margin-top: 1px;
}

.ns-import-progress--done .ns-import-progress-icon {
    color: var(--ns-gold-accent);
}

.ns-import-progress--failed .ns-import-progress-icon {
    color: #dc2626;
}

.ns-import-progress-icon svg {
    width: 100%;
    height: 100%;
}

.ns-import-progress-icon-spin {
    animation: ns-import-spin 1.1s linear infinite;
}

@keyframes ns-import-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.ns-import-progress-body {
    flex: 1;
    min-width: 0;
}

.ns-import-progress-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.ns-import-progress-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--ns-primary);
}

.ns-import-progress-percent {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--ns-text-muted);
    font-variant-numeric: tabular-nums;
}

.ns-import-progress-track {
    margin-top: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--ns-border-soft);
    overflow: hidden;
}

.ns-import-progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--ns-primary), var(--ns-teal));
    transition: width 0.3s ease;
}

.ns-import-progress-fill--indeterminate {
    animation: ns-import-indeterminate 1.4s ease-in-out infinite;
}

@keyframes ns-import-indeterminate {
    0% { margin-left: 0%; }
    50% { margin-left: 65%; }
    100% { margin-left: 0%; }
}

.ns-import-progress-meta {
    margin-top: 6px;
    font-size: 12px;
    color: var(--ns-text-muted);
    white-space: pre-line;
}

.ns-import-progress-dismiss {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    color: #94a3b8;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: color 0.15s ease;
}

.ns-import-progress-dismiss:hover {
    color: var(--ns-primary);
}

.ns-import-progress-dismiss svg {
    width: 100%;
    height: 100%;
}
</style>
