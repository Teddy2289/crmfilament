<style>
/* ============================================================
   ALLOPRO 24/24 — Centre de Contact — Custom Login Design
   ============================================================ */

/* ── Sidebar (desktop only) ── */
#crm-ap-sidebar {
    display: none;
}

@media (min-width: 1024px) {
    #crm-ap-sidebar {
        display: flex !important;
        position: fixed;
        left: 0;
        top: 0;
        width: 52%;
        height: 100vh;
        background: linear-gradient(150deg, #09090f 0%, #130d04 45%, #0d0905 100%);
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 3rem 3.5rem;
        z-index: 100;
        overflow: hidden;
    }

    /* Body background */
    .fi-body {
        background: #111118 !important;
    }

    /* Push Filament's layout to the right */
    .fi-simple-layout {
        margin-left: 52% !important;
        background: #111118 !important;
        min-height: 100vh !important;
    }
}

/* ── Mobile: dark background ── */
@media (max-width: 1023px) {
    .fi-body {
        background: linear-gradient(150deg, #09090f 0%, #130d04 100%) !important;
    }
}

/* ── Login card ── */
.fi-simple-main {
    background: #1c1c24 !important;
    border-radius: 20px !important;
    box-shadow: 0 24px 64px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(249, 115, 22, 0.1) !important;
    border: 1px solid rgba(249, 115, 22, 0.12) !important;
    overflow: visible !important;
}

/* ── Hide Filament brand logo ── */
.fi-simple-page .fi-logo {
    display: none !important;
}

/* ── Header: heading & subheading ── */
.fi-simple-header {
    gap: 6px !important;
    margin-bottom: 4px !important;
}

.fi-simple-header-heading {
    font-size: 26px !important;
    font-weight: 800 !important;
    letter-spacing: -0.6px !important;
    color: #f1f5f9 !important;
}

.fi-simple-header-subheading {
    font-size: 13.5px !important;
    color: #64748b !important;
    margin-top: 4px !important;
}

/* ── Form field labels ── */
.fi-simple-page .fi-fo-field-wrp-label label,
.fi-simple-page label {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #94a3b8 !important;
    letter-spacing: 0.1px !important;
}

/* ── Inputs ── */
.fi-simple-page .fi-input,
.fi-simple-page input[type="email"],
.fi-simple-page input[type="password"],
.fi-simple-page input[type="text"] {
    border-radius: 10px !important;
    height: 44px !important;
    font-size: 14px !important;
    border-color: rgba(249, 115, 22, 0.15) !important;
    background: rgba(255, 255, 255, 0.04) !important;
    color: #e2e8f0 !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
    padding-left: 14px !important;
    padding-right: 14px !important;
}

.fi-simple-page .fi-input:focus,
.fi-simple-page input[type="email"]:focus,
.fi-simple-page input[type="password"]:focus,
.fi-simple-page input[type="text"]:focus {
    border-color: rgba(249, 115, 22, 0.55) !important;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12) !important;
    background: rgba(255, 255, 255, 0.07) !important;
    outline: none !important;
}

.fi-simple-page .fi-input::placeholder,
.fi-simple-page input::placeholder {
    color: rgba(148, 163, 184, 0.45) !important;
}

/* ── Input wrapper ── */
.fi-simple-page .fi-input-wrp {
    border-radius: 10px !important;
    overflow: hidden !important;
    border-color: rgba(249, 115, 22, 0.15) !important;
    background: rgba(255, 255, 255, 0.04) !important;
}

/* ── Submit button ── */
.fi-simple-page .fi-btn,
.fi-simple-page button[type="submit"] {
    border-radius: 10px !important;
    height: 46px !important;
    font-size: 14.5px !important;
    font-weight: 600 !important;
    letter-spacing: 0.3px !important;
    transition: all 0.2s ease !important;
}

/* Primary button orange gradient */
.fi-simple-page .fi-btn-color-primary {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.38) !important;
    color: white !important;
}

.fi-simple-page .fi-btn-color-primary:hover {
    background: linear-gradient(135deg, #fb923c 0%, #f97316 100%) !important;
    box-shadow: 0 6px 20px rgba(249, 115, 22, 0.5) !important;
    transform: translateY(-1px) !important;
}

.fi-simple-page .fi-btn-color-primary:active {
    transform: translateY(0) !important;
    box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3) !important;
}

/* ── Error messages ── */
.fi-simple-page .fi-fo-field-wrp-error-message {
    font-size: 12.5px !important;
    color: #f87171 !important;
}

/* ── Forgot password link ── */
.fi-simple-page .fi-link {
    color: #fb923c !important;
    font-size: 13px !important;
    text-decoration: none !important;
    font-weight: 500 !important;
}

.fi-simple-page .fi-link:hover {
    color: #f97316 !important;
    text-decoration: underline !important;
}

/* ── Checkbox (remember me) ── */
.fi-simple-page .fi-checkbox-label {
    color: #94a3b8 !important;
    font-size: 13px !important;
}

/* ── Section spacing ── */
.fi-simple-page section {
    gap: 20px !important;
}
</style>
