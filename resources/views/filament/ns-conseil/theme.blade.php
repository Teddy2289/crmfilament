<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;1,14..32,400&display=swap" rel="stylesheet">

<style>
/* ════════════════════════════════════════════════════════
   NS CONSEIL — Thème minimaliste professionnel
   Filament v3 — Juin 2026
════════════════════════════════════════════════════════ */

/* ── Base typographie ─────────────────────────────────── */
.fi-body {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
    font-size: 13.5px !important;
    letter-spacing: -0.01em !important;
    color: #1e293b !important;
}

/* ── Sidebar ──────────────────────────────────────────── */
.fi-sidebar {
    background: #f8fafc !important;
    border-right: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
}

.fi-sidebar-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

.fi-sidebar-nav {
    padding: 18px 20px 16px !important;
}

.fi-sidebar-group-label {
    font-size: 10px !important;
    font-weight: 700 !important;
    letter-spacing: 0.06em !important;
    color: #94a3b8 !important;
    text-transform: uppercase !important;
}

.fi-sidebar-item a,
.fi-sidebar-item button {
    border-radius: 5px !important;
    min-height: 34px !important;
}

.fi-sidebar-item-label,
.fi-sidebar-item-icon {
    font-size: 13px !important;
    color: #475569 !important;
}

.fi-sidebar-item-active > a,
.fi-sidebar-item-active > button {
    background: #eff6ff !important;
    box-shadow: none !important;
}

.fi-sidebar-item-active .fi-sidebar-item-label,
.fi-sidebar-item-active .fi-sidebar-item-icon {
    color: #1d4ed8 !important;
    font-weight: 600 !important;
}

/* ── Topbar ───────────────────────────────────────────── */
.fi-topbar {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
}

/* ── En-têtes de page ─────────────────────────────────── */
.fi-header-heading,
.fi-simple-header-heading {
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
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
    box-shadow: none !important;
    border-radius: 7px !important;
}

.fi-section-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding-block: 10px !important;
}

.fi-section-header-heading {
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
}

.fi-section-content {
    padding: 14px !important;
}

/* ── Tables ───────────────────────────────────────────── */
.fi-ta {
    border: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
    border-radius: 7px !important;
    overflow: hidden !important;
}

.fi-ta-table thead tr {
    background: #f8fafc !important;
}

.fi-ta-header-cell-label {
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.04em !important;
}

.fi-ta-cell {
    font-size: 13px !important;
    color: #334155 !important;
    border-color: #f1f5f9 !important;
    padding-block: 9px !important;
}

.fi-ta-row:hover > * {
    background: #f8fafc !important;
}

.fi-ta-header-toolbar,
.fi-ta-toolbar {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    padding: 8px 12px !important;
    gap: 8px !important;
}

.fi-ta-pagination {
    background: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
}

.fi-ta-empty-state-heading {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
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
    border-radius: 5px !important;
    box-shadow: none !important;
}

.fi-input,
.fi-select-input,
.fi-textarea {
    font-size: 13px !important;
    color: #1e293b !important;
}

.fi-input-wrp:focus-within {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12) !important;
}

/* ── Boutons ──────────────────────────────────────────── */
.fi-btn {
    border-radius: 5px !important;
    box-shadow: none !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    min-height: 34px !important;
}

.fi-icon-btn {
    border-radius: 5px !important;
}

/* ── Badges ───────────────────────────────────────────── */
.fi-badge {
    border-radius: 4px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    padding-inline: 7px !important;
}

/* ── Tabs ─────────────────────────────────────────────── */
.fi-tabs {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: none !important;
    border-radius: 6px !important;
}

.fi-tabs-item {
    font-size: 13px !important;
    font-weight: 500 !important;
}

/* ── Modals ───────────────────────────────────────────── */
.fi-modal-window {
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 8px 32px rgba(15, 23, 42, 0.10) !important;
}

.fi-modal-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

.fi-modal-heading {
    font-size: 15px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
}

/* ── Notifications ────────────────────────────────────── */
.fi-notification {
    border-radius: 7px !important;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.10) !important;
    border: 1px solid #e2e8f0 !important;
}

/* ── Stats / Widgets ──────────────────────────────────── */
.fi-wi-stats-overview-stat {
    border: 1px solid #e2e8f0 !important;
    border-radius: 7px !important;
    box-shadow: none !important;
}

.fi-wi-stats-overview-stat-label {
    font-size: 12px !important;
    color: #64748b !important;
    font-weight: 500 !important;
}

.fi-wi-stats-overview-stat-value {
    font-size: 24px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    letter-spacing: -0.03em !important;
}

/* ── Infolist ─────────────────────────────────────────── */
.fi-in-entry-wrp-label {
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.04em !important;
}

.fi-in-text,
.fi-in-text-entry {
    font-size: 13px !important;
    color: #1e293b !important;
}

/* ── Dropdown ─────────────────────────────────────────── */
.fi-dropdown-panel {
    border: 1px solid #e2e8f0 !important;
    border-radius: 7px !important;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08) !important;
}

/* ── Page login ───────────────────────────────────────── */
.fi-simple-main {
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
}

/* ── Scrollbar minimaliste ────────────────────────────── */
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

/* Sidebar scrollbar encore plus discrète */
.fi-sidebar *::-webkit-scrollbar-thumb {
    background: #e2e8f0;
}

.fi-sidebar *::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

/* ── Inputs — bordure visible mais épurée ─────────────── */
.fi-input-wrp,
.fi-select-input,
.fi-textarea {
    border-color: #b8c4d0 !important;
    border-width: 1.5px !important;
    border-radius: 5px !important;
    box-shadow: none !important;
    background: #ffffff !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}

.fi-input-wrp:focus-within,
.fi-select-input:focus,
.fi-textarea:focus {
    border-color: #2563eb !important;
    border-width: 1.5px !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10) !important;
}

/* Placeholder plus visible */
.fi-input::placeholder,
.fi-textarea::placeholder {
    color: #a0aec0 !important;
    font-style: italic !important;
    font-size: 12.5px !important;
}

/* Input disabled — indication claire */
.fi-input:disabled,
.fi-select-input:disabled,
.fi-textarea:disabled {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    border-width: 1.5px !important;
    color: #94a3b8 !important;
    cursor: not-allowed !important;
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 768px) {
    .fi-header-heading {
        font-size: 16px !important;
    }
}
</style>
