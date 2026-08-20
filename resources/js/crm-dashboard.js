/**
 * CRM Dashboard Module
 * Gère le dashboard CRM avec visualisation des KPIs, pipeline et activités
 */

export class CrmDashboard {
    constructor(containerId, initialData = null) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container ${containerId} not found`);
            return;
        }

        this.data = initialData || {};
        this.refreshing = false;

        this.nf = new Intl.NumberFormat('fr-FR');
        this.esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));

        this.tone = {
            blue: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
            emerald: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
            violet: 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300',
            amber: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
        };

        this.init();
    }

    init() {
        this.bindEvents();
        this.renderAll();
        this.startAutoRefresh();
    }

    bindEvents() {
        const refreshButton = document.getElementById('crm-dashboard-refresh');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => this.refreshFromApi());
        }
    }

    renderAll() {
        this.renderKpis();
        this.renderPipeline();
        this.renderActivities();
        this.renderActions();
    }

    renderKpis() {
        const container = document.getElementById('crm-dashboard-kpis');
        if (!container) return;

        container.innerHTML = (this.data.kpis || []).map((item) => `
            <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 dark:bg-gray-800/60 dark:ring-gray-700/60">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">${this.esc(item.label)}</span>
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold ${this.tone[item.tone] || this.tone.blue}">
                        ${this.esc(item.key)}
                    </span>
                </div>
                <div class="mt-4 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                    ${this.nf.format(item.value || 0)}
                </div>
                <div class="mt-2 text-xs text-gray-400">Données actuelles</div>
            </article>
        `).join('') || '<p class="text-sm text-gray-500">Aucun indicateur disponible.</p>';
    }

    renderPipeline() {
        const container = document.getElementById('crm-dashboard-pipeline');
        if (!container) return;

        const rows = this.data.pipeline || [];
        const max = Math.max(1, ...rows.map((row) => Number(row.total || 0)));

        container.innerHTML = rows.map((row) => `
            <div>
                <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">${this.esc(row.label)}</span>
                    <span class="font-semibold text-gray-950 dark:text-white">${this.nf.format(row.total || 0)}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-primary-500 transition-all duration-500" 
                         style="width:${Math.max(4, (Number(row.total || 0) / max) * 100)}%">
                    </div>
                </div>
            </div>
        `).join('') || '<p class="text-sm text-gray-500">Aucune donnée de pipeline disponible.</p>';
    }

    renderActivities() {
        const container = document.getElementById('crm-dashboard-activities');
        if (!container) return;

        container.innerHTML = (this.data.activities || []).map((row) => `
            <div class="flex gap-3 py-3 first:pt-0 last:pb-0">
                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-primary-500"></span>
                <div class="min-w-0">
                    <div class="flex flex-wrap gap-x-2 gap-y-1 text-xs text-gray-400">
                        <span>${this.esc(row.date || 'Date inconnue')}</span>
                        <span>·</span>
                        <span>${this.esc(row.user || 'Système')}</span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">${this.esc(row.type)}</p>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        ${this.esc(row.description || 'Aucune description')}
                    </p>
                </div>
            </div>
        `).join('') || '<p class="text-sm text-gray-500">Aucune activité récente.</p>';
    }

    renderActions() {
        const container = document.getElementById('crm-dashboard-actions');
        if (!container) return;

        const rows = this.data.actions || [];

        container.innerHTML = rows.length ? `
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gray-100 text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700">
                    <tr>
                        <th class="px-3 py-3 font-medium">Prospect</th>
                        <th class="px-3 py-3 font-medium">Statut</th>
                        <th class="px-3 py-3 font-medium">Dernière activité</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    ${rows.map((row) => `
                        <tr>
                            <td class="px-3 py-3 font-medium text-gray-800 dark:text-gray-200">${this.esc(row.name)}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                    ${this.esc(row.status)}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-gray-500">${this.esc(row.updated || '—')}</td>
                            <td class="px-3 py-3 text-right">
                                <a class="font-semibold text-primary-600 hover:text-primary-500" href="${this.esc(row.url)}">
                                    Ouvrir
                                </a>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        ` : '<p class="text-sm text-gray-500">Aucune action prioritaire.</p>';
    }

    async refreshFromApi() {
        if (this.refreshing) return;
        this.refreshing = true;

        const button = document.getElementById('crm-dashboard-refresh');
        if (button) {
            button.disabled = true;
            button.classList.add('opacity-60');
        }

        try {
            const response = await fetch('/api/crm-dashboard', { 
                headers: { 
                    'Accept': 'application/json', 
                    'X-Requested-With': 'XMLHttpRequest' 
                }, 
                credentials: 'same-origin', 
                cache: 'no-store' 
            });

            if (!response.ok) throw new Error(`API ${response.status}`);

            this.data = await response.json();
            this.renderAll();
        } catch (error) {
            console.warn('Actualisation Dashboard CRM indisponible', error);
        } finally {
            this.refreshing = false;
            if (button) {
                button.disabled = false;
                button.classList.remove('opacity-60');
            }
        }
    }

    startAutoRefresh() {
        setInterval(() => this.refreshFromApi(), 60000);
    }

    updateData(newData) {
        this.data = newData;
        this.renderAll();
    }
}
