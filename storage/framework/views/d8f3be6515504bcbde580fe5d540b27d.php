<style>
:root {
    --crm-login-bg: #f4f6f8;
    --crm-login-panel: #ffffff;
    --crm-login-sidebar: #f7f8fa;
    --crm-login-border: #d8dee5;
    --crm-login-border-soft: #e7ebef;
    --crm-login-text: #2f3b45;
    --crm-login-muted: #6e7b87;
    --crm-login-heading: #1f2a33;
    --crm-login-primary: #337ab7;
    --crm-login-primary-hover: #28689d;
}

#crm-ns-sidebar {
    display: none;
}

.fi-body,
.fi-simple-layout,
.fi-simple-main-ctn {
    background: var(--crm-login-bg) !important;
    font-family: Arial, Helvetica, ui-sans-serif, system-ui, sans-serif !important;
    color: var(--crm-login-text) !important;
}

.fi-simple-layout {
    min-height: 100vh !important;
}

.fi-simple-main {
    width: min(100%, 390px) !important;
    background: var(--crm-login-panel) !important;
    border: 1px solid var(--crm-login-border) !important;
    border-radius: 4px !important;
    box-shadow: 0 1px 2px rgba(31, 42, 51, .07) !important;
}

.fi-simple-page .fi-logo {
    color: var(--crm-login-heading) !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    letter-spacing: 0 !important;
}

.fi-simple-header {
    gap: 4px !important;
    margin-bottom: 6px !important;
}

.fi-simple-header-heading {
    color: var(--crm-login-heading) !important;
    font-size: 20px !important;
    font-weight: 500 !important;
    letter-spacing: 0 !important;
}

.fi-simple-header-subheading {
    color: var(--crm-login-muted) !important;
    font-size: 12px !important;
}

.fi-simple-page .fi-fo-field-wrp-label label,
.fi-simple-page label {
    color: var(--crm-login-text) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
}

.fi-simple-page .fi-input-wrp,
.fi-simple-page input[type="email"],
.fi-simple-page input[type="password"],
.fi-simple-page input[type="text"] {
    background: #fff !important;
    border-color: #cbd3db !important;
    border-radius: 3px !important;
    box-shadow: none !important;
}

.fi-simple-page .fi-input,
.fi-simple-page input[type="email"],
.fi-simple-page input[type="password"],
.fi-simple-page input[type="text"] {
    font-size: 13px !important;
    min-height: 34px !important;
}

.fi-simple-page .fi-input-wrp:focus-within,
.fi-simple-page input[type="email"]:focus,
.fi-simple-page input[type="password"]:focus,
.fi-simple-page input[type="text"]:focus {
    border-color: var(--crm-login-primary) !important;
    box-shadow: 0 0 0 2px rgba(51, 122, 183, .15) !important;
    outline: none !important;
}

.fi-simple-page .fi-btn,
.fi-simple-page button[type="submit"] {
    border-radius: 3px !important;
    box-shadow: none !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    min-height: 34px !important;
}

.fi-simple-page .fi-btn-color-primary {
    background: var(--crm-login-primary) !important;
    border-color: var(--crm-login-primary) !important;
    color: #fff !important;
}

.fi-simple-page .fi-btn-color-primary:hover {
    background: var(--crm-login-primary-hover) !important;
}

.fi-simple-page .fi-link {
    color: var(--crm-login-primary) !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    text-decoration: none !important;
}

.fi-simple-page .fi-link:hover {
    color: var(--crm-login-primary-hover) !important;
    text-decoration: underline !important;
}

.fi-simple-page .fi-fo-field-wrp-error-message {
    color: #b84e4e !important;
    font-size: 12px !important;
}

@media (min-width: 1024px) {
    #crm-ns-sidebar {
        display: flex !important;
        position: fixed;
        inset: 0 auto 0 0;
        z-index: 50;
        width: 282px;
        background: var(--crm-login-sidebar);
        border-right: 1px solid var(--crm-login-border);
        flex-direction: column;
        padding: 18px 14px;
    }

    .fi-simple-layout {
        margin-left: 282px !important;
    }
}

@media (max-width: 1023px) {
    .fi-simple-layout {
        padding: 14px !important;
    }
}
</style>
<?php /**PATH C:\laragon\www\crmfilament\resources\views/filament/ns-conseil/auth/login-styles.blade.php ENDPATH**/ ?>