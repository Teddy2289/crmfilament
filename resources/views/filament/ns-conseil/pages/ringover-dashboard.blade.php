<x-filament-panels::page>
    <div class="ringover-filter-panel" aria-label="Filtres Ringover">
        <div>
            <label for="ringover-start-date">Du</label>
            <input id="ringover-start-date" type="date">
        </div>
        <div>
            <label for="ringover-end-date">Au</label>
            <input id="ringover-end-date" type="date">
        </div>
        <div>
            <label for="ringover-user-id">Utilisateur</label>
            <select id="ringover-user-id"><option value="">Tous les utilisateurs</option></select>
        </div>
        <div class="ringover-filter-actions">
            <button type="button" id="ringover-apply-filters">Appliquer</button>
            <button type="button" id="ringover-reset-filters">Réinitialiser</button>
        </div>
    </div>

    <div id="ringover-dashboard-root" class="ringover-dashboard-loading">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Chargement des données...</p>
        </div>
    </div>

    @push("scripts")
    <script type="module">
        function ringoverFilterParams() {
            const params = new URLSearchParams(window.location.search);
            const startDate = document.getElementById('ringover-start-date')?.value;
            const endDate = document.getElementById('ringover-end-date')?.value;
            const userId = document.getElementById('ringover-user-id')?.value;
            if (startDate) params.set('startDate', startDate); else params.delete('startDate');
            if (endDate) params.set('endDate', endDate); else params.delete('endDate');
            if (userId) params.set('userId', userId); else params.delete('userId');
            return params;
        }

        function loadRingoverFilters() {
            const params = new URLSearchParams(window.location.search);
            const start = document.getElementById('ringover-start-date');
            const end = document.getElementById('ringover-end-date');
            if (start && params.get('startDate')) start.value = params.get('startDate');
            if (end && params.get('endDate')) end.value = params.get('endDate');
            const userSelect = document.getElementById('ringover-user-id');
            if (userSelect && params.get('userId')) userSelect.dataset.selected = params.get('userId');
        }

        async function initDashboard() {
            try {
                const params = ringoverFilterParams();
                const response = await fetch('{{ route('api.ringover.dashboard') }}?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) throw new Error('Erreur réseau: ' + response.statusText);
                
                const data = await response.json();
                renderDashboard(data);
            } catch (error) {
                console.error('Erreur:', error);
                document.getElementById('ringover-dashboard-root').innerHTML = `
                    <div class="ringover-error-alert">
                        <p>❌ Erreur lors du chargement du tableau de bord: ${error.message}</p>
                    </div>
                `;
            }
        }

        function renderDashboard(data) {
            syncRingoverUsers(data.users || []);
            const root = document.getElementById('ringover-dashboard-root');
            root.className = 'ringover-dashboard-wrapper';
            
            const statusIcon = data.connexionOk 
                ? '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                : '<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c.866 1.5 2.926 2.871 5.303 2.871s4.437-1.372 5.303-2.871m0 0a3.375 3.375 0 116.753 0" /></svg>';
            
            root.innerHTML = `
                <div class="ringover-status-alert ${data.connexionOk ? 'success' : 'danger'}">
                    <div class="ringover-status-icon ${data.connexionOk ? 'success' : 'danger'}">
                        ${statusIcon}
                    </div>
                    <div class="ringover-status-content">
                        <div class="ringover-status-tag">
                            ${data.connexionOk ? 'Système opérationnel' : 'Vérification requise'}
                        </div>
                        <h3 class="ringover-status-title">
                            ${data.connexionOk ? 'Connexion active' : 'Connexion impossible'}
                        </h3>
                        <p class="ringover-status-message">
                            ${data.connexionOk 
                                ? 'L\'API Ringover répond correctement et les données sont en cours de synchronisation.'
                                : 'Impossible de se connecter à l\'API Ringover. Vérifiez votre configuration.'}
                        </p>
                    </div>
                </div>

                <div class="ringover-summary-grid" aria-label="Synthèse Ringover">
                    <div class="ringover-summary-card ringover-summary-card--primary">
                        <div class="ringover-summary-topline">
                            <span class="ringover-summary-label">Appels</span>
                            <span class="ringover-summary-trend ringover-summary-trend--up">+12%</span>
                        </div>
                        <strong>${data.diagnostic?.total_calls ?? 0}</strong>
                        <small>Total synchronisés</small>
                    </div>
                    <div class="ringover-summary-card ringover-summary-card--success">
                        <div class="ringover-summary-topline">
                            <span class="ringover-summary-label">Tags complets</span>
                            <span class="ringover-summary-trend ringover-summary-trend--up">OK</span>
                        </div>
                        <strong>${data.diagnostic?.complete_tags ?? 0}</strong>
                        <small>Enregistrements validés</small>
                    </div>
                    <div class="ringover-summary-card ringover-summary-card--warning">
                        <div class="ringover-summary-topline">
                            <span class="ringover-summary-label">Mappés</span>
                            <span class="ringover-summary-trend ringover-summary-trend--neutral">${data.diagnostic?.mapped_users ?? 0}</span>
                        </div>
                        <strong>${data.diagnostic?.mapped_users ?? 0}</strong>
                        <small>Utilisateurs associés</small>
                    </div>
                    <div class="ringover-summary-card ringover-summary-card--danger">
                        <div class="ringover-summary-topline">
                            <span class="ringover-summary-label">Non mappés</span>
                            <span class="ringover-summary-trend ringover-summary-trend--alert">${(data.diagnostic?.unmapped_users ?? 0) > 0 ? 'À revoir' : 'OK'}</span>
                        </div>
                        <strong>${data.diagnostic?.unmapped_users ?? 0}</strong>
                        <small>À corriger</small>
                    </div>
                </div>

                <div class="ringover-calls-section">
                    <div class="ringover-section-heading ringover-calls-heading">
                        <div>
                            <span class="ringover-analytics-kicker">Journal opérationnel</span>
                            <span>Derniers appels synchronisés</span>
                        </div>
                        <div class="ringover-calls-toolbar">
                            <input id="ringover-call-search" type="search" placeholder="Rechercher un numéro, agent ou fiche…" aria-label="Rechercher dans les appels">
                            <select id="ringover-call-status-filter" aria-label="Filtrer par statut"><option value="">Tous les statuts</option></select>
                        </div>
                    </div>
                    <div id="ringover-calls-table" class="ringover-calls-table"></div>
                </div>

                <div class="ringover-analytics-panel">
                    <div class="ringover-analytics-header">
                        <div>
                            <span class="ringover-analytics-kicker">Synthèse</span>
                            <h4>Activité des appels</h4>
                        </div>
                        <div class="ringover-analytics-meta">
                            <span class="ringover-analytics-dot"></span>
                            <span>7 derniers jours</span>
                        </div>
                    </div>

                    <div class="ringover-chart" aria-label="Graphique des appels">
                        ${[46, 68, 54, 82, 71, 94, 64].map(step => 
                            `<div class="ringover-chart-column"><span class="ringover-chart-bar" style="height: ${step}%;"></span></div>`
                        ).join('')}
                    </div>

                    <div class="ringover-chart-labels">
                        <span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span>
                    </div>
                </div>

                <div class="ringover-config-section">
                    <div class="ringover-section-heading">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.592c.55 0 1.02.398 1.11.94m6.508 0a2.25 2.25 0 00-1.11-.94h-2.592m0 0H6.504c-.55 0-1.02.398-1.11.94m6.508 0v2.25m0 0v2.25m0 0v2.25m0 0v2.25m0 0v2.25" /></svg>
                        <span>Configuration</span>
                    </div>
                    <p class="ringover-section-description">Endpoints et identifiants API Ringover</p>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="ringover-config-card">
                            <div class="ringover-config-label">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5-4.5a4.5 4.5 0 013.258-2.744z" /></svg>
                                Webhook URL
                            </div>
                            <code class="ringover-config-code">${data.webhookUrl}</code>
                            <button type="button" class="ringover-copy-button" onclick="window.RingoverDashboard.copyToClipboard('${data.webhookUrl}')">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3C7.223 2.25 6 3.474 6 4.972v15.056c0 1.497 1.223 2.722 2.75 2.722h3c1.527 0 2.75-1.225 2.75-2.722V4.972m-4.5 0A2.251 2.251 0 0110.5 2.25H5.25A2.25 2.25 0 003 4.972" /></svg>
                            </button>
                        </div>

                        <div class="ringover-config-card">
                            <div class="ringover-config-label">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                Token API
                            </div>
                            <div class="ringover-badge ${data.config?.hasApiToken ? 'success' : 'danger'}">
                                ${data.config?.hasApiToken ? '✓ Configuré' : '✗ Non configuré'}
                            </div>
                        </div>

                        <div class="ringover-config-card">
                            <div class="ringover-config-label">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m6-6a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Secret Webhook
                            </div>
                            <div class="ringover-badge ${data.config?.hasWebhookSecret ? 'success' : 'danger'}">
                                ${data.config?.hasWebhookSecret ? '✓ Configuré' : '✗ Non configuré'}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ringover-sync-section">
                    <div class="ringover-section-heading">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        <span>Synchronisation</span>
                    </div>
                    <p class="ringover-section-description">État de synchronisation des appels Ringover</p>

                    <div class="ringover-stats-grid">
                        ${renderStats(data.diagnostic)}
                    </div>

                    ${!data.diagnostic?.schema_ready ? `
                        <div class="ringover-warning-banner">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c.866 1.5 2.926 2.871 5.303 2.871s4.437-1.372 5.303-2.871m0 0a3.375 3.375 0 116.753 0" /></svg>
                            <p><strong>Migration requise :</strong> Lancez <code>php artisan migrate</code> pour activer toutes les métriques.</p>
                        </div>
                    ` : ''}
                </div>
            `;
            renderCalls(data.calls || []);
        }

        function escapeRingoverHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','\"':'&quot;'}[char]));
        }
        function formatRingoverPhone(value) {
            const raw = String(value ?? '').trim();
            const digits = raw.replace(/\D+/g, '');
            if (!digits) return raw;
            let national = digits;
            if (national.startsWith('0033')) national = '0' + national.slice(4);
            else if (national.startsWith('33') && national.length >= 11) national = '0' + national.slice(2);
            if (/^0\d{9}$/.test(national)) return national.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
            return raw;
        }

        function renderCalls(calls) {
            const container = document.getElementById('ringover-calls-table');
            if (!container) return;
            const search = document.getElementById('ringover-call-search');
            const status = document.getElementById('ringover-call-status-filter');
            const statuses = [...new Set(calls.map(call => call.status).filter(Boolean))];
            if (status) status.innerHTML = '<option value="">Tous les statuts</option>' + statuses.map(value => `<option value="${escapeRingoverHtml(value)}">${escapeRingoverHtml(value)}</option>`).join('');

            const draw = () => {
                const rawNeedle = (search?.value || '').trim();
                const needle = /^\+?[\d\s().-]+$/.test(rawNeedle) ? rawNeedle.replace(/\D+/g, '') : rawNeedle.toLowerCase();
                const selectedStatus = status?.value || '';
                const filtered = calls.filter(call => {
                    const haystack = [call.phone, call.phone_normalized, call.agent, call.target?.name, call.direction, call.status, call.status_code].filter(Boolean).join(' ').toLowerCase();
                    return (!needle || haystack.includes(needle)) && (!selectedStatus || call.status === selectedStatus || call.status_code === selectedStatus);
                });
                if (!filtered.length) {
                    container.innerHTML = '<div class="ringover-calls-empty">Aucun appel correspondant aux filtres.</div>';
                    return;
                }
                container.innerHTML = `<div class="ringover-calls-scroll"><table><thead><tr><th>Date</th><th>Agent</th><th>Direction</th><th>Numéro</th><th>Statut</th><th>Durée</th><th>Fiche CRM</th><th>Actions</th></tr></thead><tbody>${filtered.map(call => {
                    const target = call.target;
                    const fiche = target?.url ? `<a class="ringover-record-link" href="${escapeRingoverHtml(target.url)}">${escapeRingoverHtml(target.name)}</a><small>${escapeRingoverHtml(target.type)}</small>` : '<span class="ringover-unmatched">Non identifié</span>';
                    const actions = target?.phoning_url ? `<a class="ringover-action-link" href="${escapeRingoverHtml(target.phoning_url)}">Ouvrir le phoning</a>` : '<span class="ringover-action-muted">Rechercher dans Prospects</span>';
                    const dialNumber = call.phone_normalized || call.phone || '';
                    return `<tr><td>${escapeRingoverHtml(call.date)}</td><td>${escapeRingoverHtml(call.agent)}</td><td>${escapeRingoverHtml(call.direction)}</td><td><a href="tel:${escapeRingoverHtml(dialNumber)}">${escapeRingoverHtml(formatRingoverPhone(call.phone || call.phone_normalized || '—'))}</a></td><td><span class="ringover-call-status">${escapeRingoverHtml(call.status || 'Non renseigné')}</span></td><td>${escapeRingoverHtml(call.duration)}</td><td>${fiche}</td><td>${actions}</td></tr>`;
                }).join('')}</tbody></table></div><div class="ringover-calls-count">${filtered.length} appel(s) affiché(s) sur ${calls.length}</div>`;
            };
            search?.addEventListener('input', draw);
            status?.addEventListener('change', draw);
            draw();
        }

        function renderStats(diagnostic) {
            const stats = [
                {label: 'Appels', value: diagnostic?.total_calls ?? 0, icon: 'phone', tone: 'info'},
                {label: 'Tags complets', value: diagnostic?.complete_tags ?? 0, icon: 'tag', tone: 'success'},
                {label: 'Sans département', value: diagnostic?.missing_department ?? 0, icon: 'map-pin', tone: 'warn'},
                {label: 'Sans statut', value: diagnostic?.missing_status ?? 0, icon: 'exclamation', tone: 'warn'},
                {label: 'Utilisateurs mappés', value: diagnostic?.mapped_users ?? 0, icon: 'users', tone: 'success'},
                {label: 'Non mappés', value: diagnostic?.unmapped_users ?? 0, icon: 'user-x', tone: 'warn'},
            ];

            return stats.map(stat => `
                <div class="ringover-stat-box">
                    <div class="ringover-stat-icon" style="color: var(--c-${stat.tone}-500);">
                        ${window.RingoverDashboard.getIcon(stat.icon)}
                    </div>
                    <div class="ringover-stat-content">
                        <div class="ringover-stat-value">${stat.value}</div>
                        <div class="ringover-stat-label">${stat.label}</div>
                    </div>
                </div>
            `).join('');
        }

        function syncRingoverUsers(users) {
            const select = document.getElementById('ringover-user-id');
            if (!select) return;
            const selected = select.dataset.selected || new URLSearchParams(window.location.search).get('userId') || '';
            select.innerHTML = '<option value="">Tous les utilisateurs</option>' + users.map(user => `<option value="${user.id}">${user.name}</option>`).join('');
            select.value = selected;
        }

        function bindRingoverFilters() {
            document.getElementById('ringover-apply-filters')?.addEventListener('click', () => {
                const params = ringoverFilterParams();
                window.history.replaceState({}, '', window.location.pathname + '?' + params.toString());
                initDashboard();
            });
            document.getElementById('ringover-reset-filters')?.addEventListener('click', () => {
                ['ringover-start-date', 'ringover-end-date'].forEach(id => { const input = document.getElementById(id); if (input) input.value = ''; });
                const select = document.getElementById('ringover-user-id');
                if (select) { select.value = ''; delete select.dataset.selected; }
                window.history.replaceState({}, '', window.location.pathname);
                initDashboard();
            });
        }

        window.RingoverDashboard = {
            getIcon(name) {
                const icons = {
                    phone: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 11.622 9.128 21 20.25 21 1.537 0 3.046-.205 4.511-.602a.75.75 0 00.896-.74V3.75c0-.414.336-.75.75-.75h-15a.75.75 0 00-.75.75v2.25M2.25 6.75h16.5M13.867 3.75H12.75a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h1.125a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75z" /></svg>',
                    tag: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.401.401.934.622 1.49.622h4.318a2.25 2.25 0 002.25-2.25V9.568a2.25 2.25 0 00-.659-1.591L11.159 3.659A2.25 2.25 0 009.568 3z" /><circle cx="7.5" cy="7.5" r="1.5" /></svg>',
                    'map-pin': '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
                    exclamation: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                    users: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 001.591-.03 9.348 9.348 0 005.975-5.975 9.325 9.325 0 00.03-1.591 9.38 9.38 0 00-.372-2.625m0 0a9 9 0 10-12.753 8.754m0 0a9 9 0 01-5.175-5.175" /></svg>',
                    'user-x': '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2-2v4m0 0l-2-2m2 2l2-2M2 12.5a10 10 0 1119.55-5m0 0A9.972 9.972 0 0111.5 2" /></svg>'
                };
                return icons[name] || '';
            },
            copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Copié !');
                }).catch(() => {
                    alert('Erreur lors de la copie');
                });
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => { loadRingoverFilters(); bindRingoverFilters(); initDashboard(); });
        } else {
            loadRingoverFilters();
            bindRingoverFilters();
            initDashboard();
        }
    </script>
    @endpush

    @push("styles")
    <style>
        .ringover-filter-panel { display: flex; align-items: end; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; padding: 1rem 1.1rem; border: 1px solid rgb(226 232 240); border-radius: 1rem; background: rgba(255,255,255,0.96); box-shadow: 0 8px 24px rgba(15,23,42,0.04); }
        .ringover-filter-panel label { display: block; margin-bottom: 0.35rem; color: rgb(71 85 105); font-size: 0.75rem; font-weight: 700; }
        .ringover-filter-panel input, .ringover-filter-panel select { min-width: 11rem; border: 1px solid rgb(203 213 225); border-radius: 0.65rem; padding: 0.55rem 0.7rem; background: white; color: rgb(15 23 42); }
        .ringover-filter-actions { display: flex; gap: 0.6rem; }
        .ringover-filter-actions button { border: 0; border-radius: 0.65rem; padding: 0.6rem 0.9rem; font-weight: 700; cursor: pointer; }
        #ringover-apply-filters { background: rgb(37 99 235); color: white; }
        #ringover-reset-filters { background: rgb(241 245 249); color: rgb(51 65 85); }
        .ringover-dashboard-loading { display: flex; align-items: center; justify-content: center; min-height: 400px; }
        .loading-spinner { text-align: center; }
        .spinner { width: 40px; height: 40px; margin: 0 auto 1rem; border: 4px solid rgb(226 232 240); border-top-color: rgb(59 130 246); border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .ringover-error-alert { padding: 2rem; background: rgb(254 242 242); border: 2px solid rgb(239 68 68); border-radius: 1rem; color: rgb(127 29 29); text-align: center; }
        .ringover-dashboard-wrapper { display: flex; flex-direction: column; gap: 1.25rem; }
        .ringover-status-alert { display: flex; align-items: flex-start; gap: 1rem; padding: 1.25rem 1.4rem; border-radius: 1rem; border: 1px solid transparent; background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96)); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04); }
        .ringover-status-alert.success { border-color: rgb(34 197 94 / 0.18); background: linear-gradient(135deg, rgba(240,253,250,0.96), rgba(236,253,245,0.96)); }
        .ringover-status-alert.danger { border-color: rgb(239 68 68 / 0.18); background: linear-gradient(135deg, rgba(254,242,242,0.96), rgba(254,237,237,0.96)); }
        .ringover-status-icon { flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 0.9rem; background: rgba(255,255,255,0.8); border: 2px solid currentColor; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6); }
        .ringover-status-icon.success { color: rgb(22 163 74); }
        .ringover-status-icon.danger { color: rgb(220 38 38); }
        .ringover-status-content { flex: 1; min-width: 0; }
        .ringover-status-tag { display: inline-flex; align-items: center; padding: 0.3rem 0.6rem; border-radius: 9999px; background: rgba(14, 165, 233, 0.08); color: rgb(14 116 144); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
        .ringover-status-title { margin: 0.7rem 0 0; font-size: 1.2rem; font-weight: 700; color: rgb(15 23 42); }
        .ringover-status-message { margin: 0.45rem 0 0; font-size: 0.95rem; color: rgb(71 85 105); line-height: 1.55; }
        .ringover-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
        .ringover-summary-card { position: relative; display: flex; flex-direction: column; gap: 0.45rem; padding: 1rem 1.1rem; border-radius: 1rem; background: rgba(255,255,255,0.92); border: 1px solid rgb(226 232 240); box-shadow: 0 8px 18px rgba(15, 23, 42, 0.03); overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .ringover-summary-card:hover { transform: translateY(-2px); box-shadow: 0 14px 24px rgba(15, 23, 42, 0.06); }
        .ringover-summary-card::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: currentColor; }
        .ringover-summary-card--primary { color: rgb(59 130 246); }
        .ringover-summary-card--success { color: rgb(34 197 94); }
        .ringover-summary-card--warning { color: rgb(245 158 11); }
        .ringover-summary-card--danger { color: rgb(239 68 68); }
        .ringover-summary-topline { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
        .ringover-summary-label { font-size: 0.74rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(100 116 139); }
        .ringover-summary-trend { display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; padding: 0.2rem 0.5rem; font-size: 0.65rem; font-weight: 700; }
        .ringover-summary-trend--up { background: rgba(34, 197, 94, 0.08); color: rgb(22 163 74); }
        .ringover-summary-trend--neutral { background: rgba(59, 130, 246, 0.08); color: rgb(37 99 235); }
        .ringover-summary-trend--alert { background: rgba(239, 68, 68, 0.08); color: rgb(220 38 38); }
        .ringover-summary-card strong { font-size: clamp(1.5rem, 2vw, 2rem); line-height: 1.1; color: rgb(15 23 42); }
        .ringover-summary-card small { color: rgb(100 116 139); font-size: 0.78rem; }
        .ringover-calls-section { background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96)); border: 1px solid rgb(226 232 240); border-radius: 1rem; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.03); padding: 1rem 1.1rem 0.9rem; }
        .ringover-calls-heading { justify-content: space-between; margin-bottom: 1rem; }
        .ringover-calls-toolbar { display: flex; gap: 0.6rem; flex-wrap: wrap; }
        .ringover-calls-toolbar input, .ringover-calls-toolbar select { min-width: 12rem; border: 1px solid rgb(203 213 225); border-radius: 0.6rem; padding: 0.5rem 0.65rem; background: white; color: rgb(15 23 42); font-size: 0.8rem; }
        .ringover-calls-scroll { overflow-x: auto; }
        .ringover-calls-table table { width: 100%; min-width: 930px; border-collapse: collapse; font-size: 0.78rem; }
        .ringover-calls-table th { padding: 0.7rem 0.55rem; border-bottom: 1px solid rgb(226 232 240); color: rgb(100 116 139); text-align: left; font-size: 0.68rem; letter-spacing: 0.05em; text-transform: uppercase; white-space: nowrap; }
        .ringover-calls-table td { padding: 0.75rem 0.55rem; border-bottom: 1px solid rgb(241 245 249); color: rgb(51 65 85); vertical-align: middle; }
        .ringover-calls-table tr:hover td { background: rgb(248 250 252); }
        .ringover-record-link, .ringover-action-link { display: inline-block; color: rgb(37 99 235); font-weight: 700; text-decoration: none; }
        .ringover-record-link:hover, .ringover-action-link:hover { text-decoration: underline; }
        .ringover-calls-table small { display: block; margin-top: 0.2rem; color: rgb(100 116 139); text-transform: capitalize; }
        .ringover-call-status { display: inline-flex; padding: 0.25rem 0.5rem; border-radius: 9999px; background: rgba(59,130,246,0.08); color: rgb(37 99 235); font-weight: 700; white-space: nowrap; }
        .ringover-unmatched, .ringover-action-muted { color: rgb(100 116 139); font-style: italic; }
        .ringover-calls-count { margin-top: 0.75rem; color: rgb(100 116 139); font-size: 0.75rem; }
        .ringover-calls-empty { padding: 2rem 1rem; color: rgb(100 116 139); text-align: center; }
        .ringover-analytics-panel { background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.96)); border: 1px solid rgb(226 232 240); border-radius: 1rem; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.02); padding: 1rem 1.1rem 0.75rem; }
        .ringover-analytics-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .ringover-analytics-kicker { display: block; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgb(100 116 139); }
        .ringover-analytics-header h4 { margin: 0.2rem 0 0; font-size: 1.05rem; font-weight: 700; color: rgb(15 23 42); }
        .ringover-analytics-meta { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.65rem; border-radius: 9999px; background: rgba(59, 130, 246, 0.08); color: rgb(37 99 235); font-size: 0.75rem; font-weight: 600; }
        .ringover-analytics-dot { width: 0.55rem; height: 0.55rem; border-radius: 9999px; background: rgb(59 130 246); display: inline-block; }
        .ringover-chart { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); align-items: end; gap: 0.75rem; height: 9rem; padding: 0.8rem 0.2rem 0.2rem; background: linear-gradient(to top, rgba(148, 163, 184, 0.08), rgba(148, 163, 184, 0)); border-radius: 0.8rem; }
        .ringover-chart-column { display: flex; align-items: flex-end; justify-content: center; height: 100%; }
        .ringover-chart-bar { display: block; width: 100%; max-width: 2rem; border-radius: 0.6rem 0.6rem 0 0; background: linear-gradient(180deg, rgb(96, 165, 250), rgb(37, 99, 235)); box-shadow: 0 8px 18px rgba(59, 130, 246, 0.15); min-height: 0.7rem; }
        .ringover-chart-labels { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.75rem; margin-top: 0.55rem; color: rgb(100 116 139); font-size: 0.7rem; text-align: center; font-weight: 600; }
        .ringover-config-section, .ringover-sync-section { border-radius: 1rem; border: 1px solid rgb(226 232 240); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.02); padding: 1.5rem; background: rgba(255,255,255,0.96); }
        .ringover-section-heading { display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem; font-weight: 700; color: rgb(15 23 42); margin-bottom: 0.5rem; }
        .ringover-section-heading svg { flex-shrink: 0; }
        .ringover-section-description { font-size: 0.9rem; color: rgb(100 116 139); margin: 0 0 1.25rem; }
        .ringover-config-card { position: relative; display: flex; flex-direction: column; justify-content: space-between; gap: 0.8rem; min-height: 9rem; padding: 1rem; background: linear-gradient(180deg, rgba(255,255,255,0.9), rgba(248,250,252,0.9)); border: 1px solid rgb(226 232 240); border-radius: 0.9rem; transition: all 0.2s ease; }
        .ringover-config-card:hover { background: rgb(255 255 255); border-color: rgb(191 219 254); box-shadow: 0 8px 18px rgba(59, 130, 246, 0.08); }
        .ringover-config-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: rgb(100 116 139); }
        .ringover-config-code { font-size: 0.78rem; line-height: 1.5; word-break: break-all; color: rgb(51 65 81); font-family: 'Monaco', 'Menlo', monospace; background: rgb(248 250 252); padding: 0.7rem 0.8rem; border-radius: 0.55rem; border: 1px solid rgb(226 232 240); }
        .ringover-copy-button { position: absolute; top: 0.75rem; right: 0.75rem; padding: 0.5rem; background: white; border: 1px solid rgb(226 232 240); border-radius: 0.5rem; color: rgb(100 116 139); cursor: pointer; transition: all 0.2s ease; }
        .ringover-copy-button:hover { background: rgb(59 130 246); border-color: rgb(59 130 246); color: white; }
        .ringover-badge { display: inline-flex; width: fit-content; padding: 0.35rem 0.65rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; margin-top: auto; }
        .ringover-badge.success { background: rgba(34, 197, 94, 0.1); color: rgb(22 163 74); }
        .ringover-badge.danger { background: rgba(239, 68, 68, 0.1); color: rgb(220 38 38); }
        .ringover-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
        .ringover-stat-box { display: flex; align-items: center; gap: 0.9rem; padding: 1rem; background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.96)); border: 1px solid rgb(226 232 240); border-radius: 0.85rem; transition: all 0.2s ease; }
        .ringover-stat-box:hover { border-color: rgb(191 219 254); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.08); }
        .ringover-stat-icon { flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border-radius: 0.8rem; background: currentColor; opacity: 0.1; }
        .ringover-stat-content { flex: 1; min-width: 0; }
        .ringover-stat-value { font-size: 1.55rem; font-weight: 700; line-height: 1.1; color: rgb(15 23 42); }
        .ringover-stat-label { font-size: 0.82rem; color: rgb(100 116 139); margin-top: 0.2rem; }
        .ringover-warning-banner { display: flex; align-items: flex-start; gap: 0.9rem; padding: 1rem 1.1rem; background: linear-gradient(135deg, rgba(254, 243, 199, 0.45), rgba(253, 230, 138, 0.35)); border-left: 4px solid rgb(202 138 4); border-radius: 0.8rem; color: rgb(113 63 18); }
        .ringover-warning-banner svg { flex-shrink: 0; width: 1.15rem; height: 1.15rem; margin-top: 0.1rem; }
        .ringover-warning-banner p { margin: 0; font-size: 0.92rem; line-height: 1.5; }
        .ringover-warning-banner code { background: rgba(0,0,0,0.05); padding: 0.2rem 0.4rem; border-radius: 0.3rem; font-family: 'Monaco', 'Menlo', monospace; font-size: 0.82rem; }
        @media (max-width: 1024px) { .ringover-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 768px) { .ringover-status-alert { flex-direction: column; align-items: center; text-align: center; } .ringover-summary-grid { grid-template-columns: 1fr; } .ringover-stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); } .ringover-config-card { min-height: 7rem; } }
    </style>
    @endpush

</x-filament-panels::page>
