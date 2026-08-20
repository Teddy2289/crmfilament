<x-filament-panels::page>
    <script type="application/json" id="crm-dashboard-data">@json($dashboardData ?? [])</script>
    <div id="crm-dashboard-app" class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-primary-600">Pilotage commercial</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Dashboard CRM</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Une vue rapide des volumes, du pipeline et des actions prioritaires.</p>
            </div>
            <button id="crm-dashboard-refresh" type="button" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <span aria-hidden="true">↻</span><span>Actualiser</span>
            </button>
        </div>

        <div class="crm-dashboard-toolbar flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-gray-700 dark:bg-gray-800/70">
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5 font-medium"><span id="crm-dashboard-live-dot" class="h-2 w-2 rounded-full bg-emerald-500"></span><span id="crm-dashboard-live-label">Données en direct</span></span>
                <span class="hidden text-gray-300 sm:inline">|</span>
                <span id="crm-dashboard-updated-at">Chargement…</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="sr-only" for="crm-dashboard-period">Période du dashboard</label>
                <select id="crm-dashboard-period" class="rounded-lg border-gray-300 bg-gray-50 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <option value="all">Toutes les périodes</option>
                    <option value="today">Aujourd’hui</option>
                    <option value="7d">7 derniers jours</option>
                    <option value="30d">30 derniers jours</option>
                    <option value="custom">Personnalisée</option>
                </select>
                <div id="crm-dashboard-custom-period" class="hidden items-center gap-1">
                    <label class="sr-only" for="crm-dashboard-start-date">Date de début</label>
                    <input id="crm-dashboard-start-date" type="date" class="rounded-lg border-gray-300 bg-gray-50 text-xs shadow-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <span class="text-xs text-gray-400">→</span>
                    <label class="sr-only" for="crm-dashboard-end-date">Date de fin</label>
                    <input id="crm-dashboard-end-date" type="date" class="rounded-lg border-gray-300 bg-gray-50 text-xs shadow-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <label class="sr-only" for="crm-dashboard-search">Rechercher dans le dashboard</label>
                <input id="crm-dashboard-search" type="search" placeholder="Rechercher une action…" class="min-w-[190px] rounded-lg border-gray-300 bg-gray-50 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <label class="sr-only" for="crm-dashboard-agent">Filtrer par agent</label>
                <select id="crm-dashboard-agent" class="rounded-lg border-gray-300 bg-gray-50 text-xs shadow-none focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Tous les agents</option>
                </select>
                <button id="crm-dashboard-reset-filters" type="button" class="rounded-lg px-2.5 py-2 text-xs font-semibold text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">Réinitialiser</button>
            </div>
        </div>

        <div id="crm-dashboard-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"></div>

        <div class="grid gap-6 xl:grid-cols-5">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 xl:col-span-3 dark:bg-gray-800/60 dark:ring-gray-700/60">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold text-gray-950 dark:text-white">Pipeline commercial</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Répartition des prospects par statut.</p></div>
                    <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">Temps réel</span>
                </div>
                <div id="crm-dashboard-pipeline" class="space-y-4"></div>
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 xl:col-span-2 dark:bg-gray-800/60 dark:ring-gray-700/60">
                <div class="mb-5"><h2 class="font-semibold text-gray-950 dark:text-white">Activité récente</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les derniers mouvements enregistrés.</p></div>
                <div id="crm-dashboard-activities" class="divide-y divide-gray-100 dark:divide-gray-700"></div>
            </section>
        </div>

        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 dark:bg-gray-800/60 dark:ring-gray-700/60">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-gray-950 dark:text-white">À traiter en priorité</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les prospects nécessitant une action rapide.</p></div><a href="{{ url('/ns-conseil/prospects') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500">Voir tous les prospects →</a></div>
            <div id="crm-dashboard-actions" class="overflow-x-auto"></div>
        </section>
    </div>

    <script>
        (() => {
            const root = document.getElementById('crm-dashboard-app');
            if (!root) return;
            let data = JSON.parse(document.getElementById('crm-dashboard-data')?.textContent || '{}');
            let refreshing = false;
            let lastUpdatedAt = null;
            const nf = new Intl.NumberFormat('fr-FR');
            const filters = { search: '', agent: '', period: data.period?.key || 'all', startDate: data.period?.start || '', endDate: data.period?.end || '' };
            const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
            const tone = { blue: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300', emerald: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300', violet: 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300', amber: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' };

            const renderKpis = () => {
                document.getElementById('crm-dashboard-kpis').innerHTML = (data.kpis || []).map((item) => `
                    <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 dark:bg-gray-800/60 dark:ring-gray-700/60">
                        <div class="flex items-center justify-between gap-3"><span class="text-sm text-gray-500 dark:text-gray-400">${esc(item.label)}</span><span class="rounded-lg px-2.5 py-1 text-xs font-semibold ${tone[item.tone] || tone.blue}">${esc(item.key)}</span></div>
                        <div class="mt-4 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">${nf.format(item.value || 0)}</div><div class="mt-2 text-xs text-gray-400">Données actuelles</div>
                    </article>`).join('') || '<p class="text-sm text-gray-500">Aucun indicateur disponible.</p>';
            };
            const renderPipeline = () => {
                const rows = data.pipeline || [], max = Math.max(1, ...rows.map((row) => Number(row.total || 0)));
                document.getElementById('crm-dashboard-pipeline').innerHTML = rows.map((row) => `<div><div class="mb-1 flex items-center justify-between gap-3 text-sm"><span class="font-medium text-gray-700 dark:text-gray-200">${esc(row.label)}</span><span class="font-semibold text-gray-950 dark:text-white">${nf.format(row.total || 0)}</span></div><div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700"><div class="h-full rounded-full bg-primary-500 transition-all duration-500" style="width:${Math.max(4, (Number(row.total || 0) / max) * 100)}%"></div></div></div>`).join('') || '<p class="text-sm text-gray-500">Aucune donnée de pipeline disponible.</p>';
            };
            const renderActivities = () => {
                const rows = (data.activities || []).filter((row) => !filters.agent || String(row.user || '') === filters.agent);
                document.getElementById('crm-dashboard-activities').innerHTML = rows.map((row) => `<div class="flex gap-3 py-3 first:pt-0 last:pb-0"><span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-primary-500"></span><div class="min-w-0"><div class="flex flex-wrap gap-x-2 gap-y-1 text-xs text-gray-400"><span>${esc(row.date || 'Date inconnue')}</span><span>·</span><span>${esc(row.user || 'Système')}</span></div><p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">${esc(row.type)}</p><p class="truncate text-sm text-gray-500 dark:text-gray-400">${esc(row.description || 'Aucune description')}</p></div></div>`).join('') || '<p class="text-sm text-gray-500">Aucune activité pour ce filtre.</p>';
            };
            const renderActions = () => {
                const query = filters.search.trim().toLowerCase();
                const rows = (data.actions || []).filter((row) => !query || [row.name, row.status, row.updated].some((value) => String(value || '').toLowerCase().includes(query)));
                document.getElementById('crm-dashboard-actions').innerHTML = rows.length ? `<table class="min-w-full text-left text-sm"><thead class="border-b border-gray-100 text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700"><tr><th class="px-3 py-3 font-medium">Prospect</th><th class="px-3 py-3 font-medium">Statut</th><th class="px-3 py-3 font-medium">Dernière activité</th><th class="px-3 py-3"></th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700">${rows.map((row) => `<tr><td class="px-3 py-3 font-medium text-gray-800 dark:text-gray-200">${esc(row.name)}</td><td class="px-3 py-3"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">${esc(row.status)}</span></td><td class="px-3 py-3 text-gray-500">${esc(row.updated || '—')}</td><td class="px-3 py-3 text-right"><a class="font-semibold text-primary-600 hover:text-primary-500" href="${esc(row.url)}">Ouvrir</a></td></tr>`).join('')}</tbody></table>` : '<p class="text-sm text-gray-500">Aucune action prioritaire.</p>';
            };
            const populateAgents = () => {
                const select = document.getElementById('crm-dashboard-agent');
                if (!select) return;
                const current = select.value;
                const agents = [...new Set((data.activities || []).map((row) => String(row.user || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'fr'));
                select.innerHTML = '<option value="">Tous les agents</option>' + agents.map((agent) => `<option value="${esc(agent)}">${esc(agent)}</option>`).join('');
                select.value = agents.includes(current) ? current : '';
            };
            const updateFreshness = () => {
                lastUpdatedAt = new Date();
                const target = document.getElementById('crm-dashboard-updated-at');
                if (target) target.textContent = `Mis à jour à ${lastUpdatedAt.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`;
            };
            const renderAll = () => { populateAgents(); renderKpis(); renderPipeline(); renderActivities(); renderActions(); updateFreshness(); };
            const refreshFromApi = async () => {
                if (refreshing) return;
                refreshing = true;
                const button = document.getElementById('crm-dashboard-refresh');
                if (button) { button.disabled = true; button.classList.add('opacity-60'); }
                try {
                    const params = new URLSearchParams({ period: filters.period || 'all' });
                    if (filters.period === 'custom') {
                        if (filters.startDate) params.set('start_date', filters.startDate);
                        if (filters.endDate) params.set('end_date', filters.endDate);
                    }
                    const response = await fetch(`/ns-conseil/api/crm-dashboard?${params.toString()}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' });
                    if (!response.ok) throw new Error(`API ${response.status}`);
                    data = await response.json();
                    populateAgents();
                    renderKpis(); renderPipeline(); renderActivities(); renderActions(); updateFreshness();
                } catch (error) { console.warn('Actualisation Dashboard CRM indisponible', error); }
                finally { refreshing = false; if (button) { button.disabled = false; button.classList.remove('opacity-60'); } }
            };
            const syncPeriodControls = () => {
                const select = document.getElementById('crm-dashboard-period');
                const custom = document.getElementById('crm-dashboard-custom-period');
                if (select) select.value = filters.period;
                if (custom) custom.classList.toggle('hidden', filters.period !== 'custom');
                if (custom) custom.classList.toggle('flex', filters.period === 'custom');
            };
            syncPeriodControls();
            renderAll();
            document.getElementById('crm-dashboard-refresh')?.addEventListener('click', refreshFromApi);
            document.getElementById('crm-dashboard-period')?.addEventListener('change', (event) => { filters.period = event.target.value; syncPeriodControls(); if (filters.period !== 'custom' || (filters.startDate && filters.endDate)) refreshFromApi(); });
            document.getElementById('crm-dashboard-start-date')?.addEventListener('change', (event) => { filters.startDate = event.target.value; if (filters.period === 'custom' && filters.startDate && filters.endDate) refreshFromApi(); });
            document.getElementById('crm-dashboard-end-date')?.addEventListener('change', (event) => { filters.endDate = event.target.value; if (filters.period === 'custom' && filters.startDate && filters.endDate) refreshFromApi(); });
            document.getElementById('crm-dashboard-search')?.addEventListener('input', (event) => { filters.search = event.target.value; renderActions(); });
            document.getElementById('crm-dashboard-agent')?.addEventListener('change', (event) => { filters.agent = event.target.value; renderActivities(); });
            document.getElementById('crm-dashboard-reset-filters')?.addEventListener('click', () => {
                filters.search = ''; filters.agent = ''; filters.period = 'all'; filters.startDate = ''; filters.endDate = '';
                syncPeriodControls();
                const search = document.getElementById('crm-dashboard-search');
                const agent = document.getElementById('crm-dashboard-agent');
                if (search) search.value = '';
                if (agent) agent.value = '';
                renderActions(); renderActivities();
            });
            window.setInterval(refreshFromApi, 60000);
        })();
    </script>
</x-filament-panels::page>
