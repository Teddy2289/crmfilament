<style>
/* ============================================================
   NS CONSEIL — CRM Partenaires — Custom Login Design
   ============================================================ */

/* ── Sidebar (desktop only) ── */
#crm-ns-sidebar {
    display: none;
}

@media (min-width: 1024px) {
    #crm-ns-sidebar {
        display: flex !important;
        position: fixed;
        left: 0;
        top: 0;
        width: 52%;
        height: 100vh;
        background: linear-gradient(150deg, #040d1a 0%, #0b2040 45%, #071530 100%);
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 3rem 3.5rem;
        z-index: 100;
        overflow: hidden;
    }

    /* Body background */
    .fi-body {
        background: #eef2f7 !important;
    }

    /* Push Filament's layout to the right */
    .fi-simple-layout {
        margin-left: 52% !important;
        background: #eef2f7 !important;
        min-height: 100vh !important;
    }
}

/* ── Mobile: dark background, no sidebar ── */
@media (max-width: 1023px) {
    .fi-body {
        background: linear-gradient(150deg, #040d1a 0%, #0b2040 100%) !important;
    }

    .fi-simple-layout {
        padding: 1rem !important;
    }
}

/* ── Login card ── */
.fi-simple-main {
    background: #ffffff !important;
    border-radius: 20px !important;
    box-shadow: 0 24px 64px -12px rgba(0, 0, 0, 0.18) !important;
    border: 1px solid rgba(0, 0, 0, 0.05) !important;
    overflow: visible !important;
}

/* ── Hide Filament brand logo (NS branding is in the sidebar) ── */
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
    color: #0f172a !important;
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
    color: #374151 !important;
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
    border-color: #d1d5db !important;
    background: #f9fafb !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
    padding-left: 14px !important;
    padding-right: 14px !important;
}

.fi-simple-page .fi-input:focus,
.fi-simple-page input[type="email"]:focus,
.fi-simple-page input[type="password"]:focus,
.fi-simple-page input[type="text"]:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14) !important;
    background: #ffffff !important;
    outline: none !important;
}

/* ── Input wrapper border ── */
.fi-simple-page .fi-input-wrp {
    border-radius: 10px !important;
    overflow: hidden !important;
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

/* Primary button blue gradient */
.fi-simple-page .fi-btn-color-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.38) !important;
    color: white !important;
}

.fi-simple-page .fi-btn-color-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.48) !important;
    transform: translateY(-1px) !important;
}

.fi-simple-page .fi-btn-color-primary:active {
    transform: translateY(0) !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3) !important;
}

/* ── Remember me checkbox ── */
.fi-simple-page .fi-checkbox {
    border-radius: 5px !important;
}

/* ── Error messages ── */
.fi-simple-page .fi-fo-field-wrp-error-message {
    font-size: 12.5px !important;
    color: #ef4444 !important;
}

/* ── Forgot password link ── */
.fi-simple-page .fi-link {
    color: #3b82f6 !important;
    font-size: 13px !important;
    text-decoration: none !important;
    font-weight: 500 !important;
}

.fi-simple-page .fi-link:hover {
    color: #1d4ed8 !important;
    text-decoration: underline !important;
}

/* ── Section spacing ── */
.fi-simple-page section {
    gap: 20px !important;
}

/* ── Form spacing ── */
.fi-simple-page .fi-form {
    gap: 18px !important;
}

/* ── Divider between form fields ── */
.fi-fo-field-wrp {
    margin-bottom: 4px !important;
}
</style>
