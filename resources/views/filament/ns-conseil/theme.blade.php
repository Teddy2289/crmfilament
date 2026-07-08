<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;1,14..32,400&display=swap" rel="stylesheet">

<style>
/* ════════════════════════════════════════════════════════
   NS CONSEIL — Thème "Marine & Doré"
   Filament v3 — Juillet 2026
   Palette extraite du visuel NS CONSEIL :
   #2C4A5E (bleu marine) · #3F8FA3 (teal titre) · #E8B873 (doré/tan)
   #D9A455 (doré accent) · #FFFFFF
════════════════════════════════════════════════════════ */

:root {
    --ns-primary: #2C4A5E;
    --ns-primary-dark: #1E3444;
    --ns-teal: #3F8FA3;
    --ns-gold: #E8B873;
    --ns-gold-accent: #D9A455;
}

/* ── Base typographie ─────────────────────────────────── */
.fi-body {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
    font-size: 13.5px !important;
    letter-spacing: -0.01em !important;
    color: #1e293b !important;
    background: #f4f7fb !important;
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

.fi-sidebar-item a:hover,
.fi-sidebar-item button:hover {
    background: rgba(232, 184, 115, 0.1) !important;
}

.fi-sidebar-item-label,
.fi-sidebar-item-icon {
    font-size: 13px !important;
    color: rgba(255, 255, 255, 0.82) !important;
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
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(30, 52, 68, 0.04) !important;
}

.fi-topbar nav {
    background: #ffffff !important;
}

/* ── En-têtes de page ─────────────────────────────────── */
.fi-header-heading,
.fi-simple-header-heading {
    font-size: 19px !important;
    font-weight: 700 !important;
    color: var(--ns-teal) !important;
    letter-spacing: -0.02em !important;
}

.fi-header-subheading,
.fi-simple-header-subheading {
    font-size: 13px !important;
    color: #64748b !important;
}

/* ── Sections / Cards ─────────────────────────────────── */
.fi-section,
.fi-wi-widget {
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(30, 52, 68, 0.04) !important;
    border-radius: 10px !important;
    transition: box-shadow 0.2s ease !important;
}

.fi-section:hover,
.fi-wi-widget:hover {
    box-shadow: 0 4px 12px rgba(30, 52, 68, 0.08) !important;
}

.fi-section-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding-block: 11px !important;
    border-radius: 10px 10px 0 0 !important;
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
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(30, 52, 68, 0.04) !important;
    border-radius: 10px !important;
    overflow: hidden !important;
}

.fi-ta-table thead tr {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%) !important;
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
    border-color: #f1f5f9 !important;
    padding-block: 9px !important;
}

.fi-ta-row {
    transition: background 0.12s ease !important;
}

.fi-ta-row:hover > * {
    background: #f2ede1 !important;
}

.fi-ta-header-toolbar,
.fi-ta-toolbar {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    padding: 10px 14px !important;
    gap: 8px !important;
}

.fi-ta-pagination {
    background: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
}

.fi-ta-empty-state-heading {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
}

.fi-ta-empty-state-description {
    font-size: 12.5px !important;
    color: #64748b !important;
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
    border-radius: 6px !important;
    box-shadow: none !important;
    background: #ffffff !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}

.fi-input,
.fi-select-input,
.fi-textarea {
    font-size: 13px !important;
    color: #1e293b !important;
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
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    color: #94a3b8 !important;
    cursor: not-allowed !important;
}

/* ── Boutons ──────────────────────────────────────────── */
.fi-btn {
    border-radius: 6px !important;
    box-shadow: none !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    min-height: 34px !important;
    transition: transform 0.1s ease, box-shadow 0.15s ease !important;
}

.fi-btn:active {
    transform: scale(0.97) !important;
}

/* Bouton primaire — dégradé bleu marine */
.fi-btn[type="submit"],
.fi-ac-btn-action.fi-color-primary {
    background: linear-gradient(135deg, var(--ns-primary) 0%, var(--ns-primary-dark) 100%) !important;
    box-shadow: 0 2px 6px rgba(44, 74, 94, 0.28) !important;
}

.fi-btn[type="submit"]:hover,
.fi-ac-btn-action.fi-color-primary:hover {
    box-shadow: 0 4px 10px rgba(44, 74, 94, 0.38) !important;
}

.fi-icon-btn {
    border-radius: 6px !important;
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
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
    border-radius: 8px !important;
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
    border-radius: 10px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 12px 36px rgba(30, 52, 68, 0.18) !important;
}

.fi-modal-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

.fi-modal-heading {
    font-size: 15px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
}

/* ── Notifications ────────────────────────────────────── */
.fi-notification {
    border-radius: 8px !important;
    box-shadow: 0 6px 20px rgba(30, 52, 68, 0.14) !important;
    border: 1px solid #e2e8f0 !important;
    border-left: 4px solid var(--ns-primary) !important;
}

.fi-notification.fi-color-success {
    border-left-color: var(--ns-gold-accent) !important;
}

.fi-notification.fi-color-warning {
    border-left-color: var(--ns-gold) !important;
}

/* ── Stats / Widgets — accent doré en haut ────────────── */
.fi-wi-stats-overview-stat {
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    box-shadow: 0 1px 3px rgba(30, 52, 68, 0.04) !important;
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
    box-shadow: 0 6px 16px rgba(30, 52, 68, 0.08) !important;
    transform: translateY(-1px) !important;
}

.fi-wi-stats-overview-stat-label {
    font-size: 12px !important;
    color: #64748b !important;
    font-weight: 600 !important;
}

.fi-wi-stats-overview-stat-value {
    font-size: 25px !important;
    font-weight: 700 !important;
    color: var(--ns-primary) !important;
    letter-spacing: -0.03em !important;
}

.fi-wi-stats-overview-stat-description {
    font-weight: 600 !important;
}

/* ── Infolist ─────────────────────────────────────────── */
.fi-in-entry-wrp-label {
    font-size: 11.5px !important;
    font-weight: 700 !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}

.fi-in-text,
.fi-in-text-entry {
    font-size: 13px !important;
    color: #1e293b !important;
}

/* ── Dropdown ─────────────────────────────────────────── */
.fi-dropdown-panel {
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    box-shadow: 0 8px 24px rgba(30, 52, 68, 0.1) !important;
}

/* ── Page login ───────────────────────────────────────── */
.fi-simple-page {
    background: linear-gradient(135deg, var(--ns-primary) 0%, var(--ns-primary-dark) 60%, #12222d 100%) !important;
    padding: 20px !important;
    border-radius: 10px !important;
}

.fi-simple-main {
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 20px 50px rgba(30, 52, 68, 0.3) !important;
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

.fi-logo{
    text-align: center;
}

.fi-section-header{
    background-color: #f2ede1 !important;
}


</style>