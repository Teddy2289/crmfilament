<script>
(() => {
    if (window.__crmGlobalUiLoaded) return;
    window.__crmGlobalUiLoaded = true;

    const dispatch = (name, detail = {}) => window.dispatchEvent(new CustomEvent(name, { detail }));

    window.CrmUI = {
        toast(message, type = 'info', timeout = 4200) {
            dispatch('crm:toast', { message, type, timeout });
        },
        setBusy(element, busy = true) {
            if (!element) return;
            element.toggleAttribute('aria-busy', busy);
            element.classList.toggle('crm-is-loading', busy);
            if (busy) element.setAttribute('data-crm-original-disabled', element.disabled ? '1' : '0');
            if ('disabled' in element) element.disabled = busy;
        },
        rememberTab(key, value) {
            try { localStorage.setItem(`crm:tab:${key}`, value); } catch (_) {}
        },
        restoreTab(key, fallback = null) {
            try { return localStorage.getItem(`crm:tab:${key}`) || fallback; } catch (_) { return fallback; }
        },
        rememberTablePreferences(key, values) {
            try { localStorage.setItem(`crm:table:${key}`, JSON.stringify(values)); } catch (_) {}
        },
        restoreTablePreferences(key) {
            try { return JSON.parse(localStorage.getItem(`crm:table:${key}`) || '{}') || {}; } catch (_) { return {}; }
        },
        clearTablePreferences(key) {
            try { localStorage.removeItem(`crm:table:${key}`); } catch (_) {}
        },
        async loadRemoteTablePreferences(resource) {
            try {
                const response = await fetch(`/ns-conseil/user-preferences/${encodeURIComponent(resource)}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) return null;
                const payload = await response.json();
                return payload.preferences || {};
            } catch (_) { return null; }
        },
        async saveRemoteTablePreferences(resource, preferences) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(`/ns-conseil/user-preferences/${encodeURIComponent(resource)}`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                    },
                    body: JSON.stringify({ preferences }),
                });
                return response.ok;
            } catch (_) { return false; }
        },
        async clearRemoteTablePreferences(resource) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(`/ns-conseil/user-preferences/${encodeURIComponent(resource)}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                    },
                });
                return response.ok;
            } catch (_) { return false; }
        },
        once(element, key, callback) {
            if (!element || element.dataset[`crmBound${key}`]) return;
            element.dataset[`crmBound${key}`] = '1';
            callback(element);
        }
    };

    const ensureStyles = () => {
        if (document.getElementById('crm-global-ui-styles')) return;
        const style = document.createElement('style');
        style.id = 'crm-global-ui-styles';
        style.textContent = `
            .crm-is-loading { opacity: .65; cursor: wait !important; }
            [aria-busy="true"] { pointer-events: none; }
            .crm-global-toast-stack { position: fixed; right: 1rem; bottom: 1rem; z-index: 99999; display: grid; gap: .5rem; max-width: min(28rem, calc(100vw - 2rem)); }
            .crm-global-toast { padding: .75rem 1rem; border-radius: .65rem; color: #fff; font: 600 .875rem/1.35 system-ui, sans-serif; box-shadow: 0 8px 24px rgba(15,23,42,.18); }
            .crm-global-toast--success { background: #047857; }
            .crm-global-toast--error { background: #b91c1c; }
            .crm-global-toast--warning { background: #b45309; }
            .crm-global-toast--info { background: #1d4ed8; }
        `;
        document.head.appendChild(style);
    };

    const showToast = ({ message, type = 'info', timeout = 4200 } = {}) => {
        if (!message) return;
        ensureStyles();
        let stack = document.querySelector('.crm-global-toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'crm-global-toast-stack';
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }
        const toast = document.createElement('div');
        toast.className = `crm-global-toast crm-global-toast--${type}`;
        toast.textContent = message;
        stack.appendChild(toast);
        window.setTimeout(() => toast.remove(), timeout);
    };

    const bind = () => {
        ensureStyles();
        document.querySelectorAll('[data-crm-toast]').forEach((element) => {
            CrmUI.once(element, 'Toast', (node) => node.addEventListener('click', () => showToast({ message: node.dataset.crmToast, type: node.dataset.crmToastType || 'info' })));
        });
        document.querySelectorAll('[data-crm-tab-key][data-crm-tab-value]').forEach((element) => {
            CrmUI.once(element, 'Tab', (node) => node.addEventListener('click', () => CrmUI.rememberTab(node.dataset.crmTabKey, node.dataset.crmTabValue)));
        });
    };

    window.addEventListener('crm:toast', (event) => showToast(event.detail || {}));
    const bindTablePreferences = () => {
        const path = location.pathname;
        if (!/(campagne-phonings|prospects)/i.test(path)) return;
        const root = document.querySelector('main') || document;
        const fields = [...root.querySelectorAll('input, select, textarea')].filter((field) => {
            const model = field.getAttribute('wire:model') || field.getAttribute('wire:model.live') || field.getAttribute('wire:model.blur') || '';
            return /tableSearch|tableFilters|filters/i.test(model) || field.closest('.fi-ta-filters, [data-filters]');
        });
        if (!fields.length) return;
        const key = path.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '');
        const resource = /campagne-phonings/i.test(path) ? 'campagne-phonings' : 'prospects';
        let saveTimer = null;
        const read = () => Object.fromEntries(fields.map((field, index) => [
            field.name || field.id || `field-${index}`,
            field.type === 'checkbox' ? field.checked : field.value,
        ]));
        const stored = CrmUI.restoreTablePreferences(key);
        const persist = () => {
            const values = read();
            CrmUI.rememberTablePreferences(key, values);
            window.clearTimeout(saveTimer);
            saveTimer = window.setTimeout(() => CrmUI.saveRemoteTablePreferences(resource, values), 650);
        };
        fields.forEach((field, index) => {
            const fieldKey = field.name || field.id || `field-${index}`;
            CrmUI.once(field, 'TablePreference', (node) => {
                node.addEventListener('change', persist);
                if (node.tagName === 'INPUT' && node.type === 'search') {
                    node.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') persist();
                    });
                }
            });
            const current = field.type === 'checkbox' ? field.checked : field.value;
            if ((current === '' || current === null || current === undefined) && stored[fieldKey] !== undefined) {
                if (field.type === 'checkbox') field.checked = Boolean(stored[fieldKey]);
                else field.value = stored[fieldKey];
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        CrmUI.loadRemoteTablePreferences(resource).then((remote) => {
            if (!remote) return;
            Object.entries(remote).forEach(([fieldKey, value]) => {
                const field = fields.find((item, index) => (item.name || item.id || `field-${index}`) === fieldKey);
                if (!field) return;
                if (field.type === 'checkbox') field.checked = value === true || value === 'true' || value === '1';
                else field.value = value ?? '';
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
            CrmUI.rememberTablePreferences(key, remote);
        });
        const filters = root.querySelector('.fi-ta-filters, [data-filters]');
        const searchField = fields.find((field) => field.type === 'search' || /rechercher|search/i.test(field.placeholder || field.getAttribute('aria-label') || ''));
        if (filters && searchField && !filters.querySelector('[data-crm-run-table-search]')) {
            const run = document.createElement('button');
            run.type = 'button';
            run.dataset.crmRunTableSearch = '1';
            run.className = 'fi-btn fi-btn-size-sm fi-btn-color-primary fi-color-primary';
            run.textContent = 'Lancer la recherche';
            run.addEventListener('click', () => {
                persist();
                searchField.dispatchEvent(new Event('input', { bubbles: true }));
                searchField.dispatchEvent(new Event('change', { bubbles: true }));
                CrmUI.toast('Recherche lancée', 'info', 1800);
            });
            filters.appendChild(run);
        }
        if (filters && !filters.querySelector('[data-crm-clear-table-preferences]')) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.dataset.crmClearTablePreferences = '1';
            clear.className = 'fi-btn fi-btn-size-sm fi-btn-color-gray fi-color-gray';
            clear.textContent = 'Effacer mes préférences';
            clear.addEventListener('click', () => {
                CrmUI.clearTablePreferences(key);
                CrmUI.clearRemoteTablePreferences(resource);
                fields.forEach((field) => {
                    if (field.type === 'checkbox') field.checked = false;
                    else field.value = '';
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                });
                CrmUI.toast('Préférences de recherche effacées', 'success');
            });
            filters.appendChild(clear);
        }
    };

    const bindWorkflowTabs = () => {
        const tabs = document.querySelectorAll('.pw-info-tab[data-tab]');
        if (!tabs.length) return;
        const key = `workflow:${location.pathname}:${new URLSearchParams(location.search).get('contact_id') || 'current'}`;
        tabs.forEach((tab) => {
            CrmUI.once(tab, 'WorkflowTab', (node) => node.addEventListener('click', () => {
                CrmUI.rememberTab(key, node.dataset.tab);
            }));
        });
        const saved = CrmUI.restoreTab(key);
        if (saved && document.querySelector(`.pw-info-tab[data-tab="${CSS.escape(saved)}"]`) && typeof window.switchInfoTab === 'function') {
            window.switchInfoTab(saved);
        }
    };

    const bindAll = () => { bind(); bindTablePreferences(); bindWorkflowTabs(); };
    document.addEventListener('DOMContentLoaded', bindAll);
    document.addEventListener('livewire:navigated', bindAll);
    document.addEventListener('livewire:updated', bindAll);
})();
</script>
