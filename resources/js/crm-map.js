/**
 * CRM Map Module
 * Gère la carte interactive pour la visualisation géographique des données CRM
 */

export class CrmMap {
    constructor(containerId, initialData = null) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container ${containerId} not found`);
            return;
        }

        this.data = initialData || {};
        this.refreshing = false;
        this.state = {
            department: '',
            city: '',
            types: { prospects: true, clients: true, partenaires: true },
            map: null,
            layer: null,
            marker: null
        };

        this.nf = new Intl.NumberFormat('fr-FR');
        this.esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[c]));

        this.init();
    }

    init() {
        if (typeof L === 'undefined') {
            console.error('Leaflet library not loaded');
            return;
        }

        this.setupMap();
        this.bindEvents();
        this.render();
        this.startAutoRefresh();
    }

    setupMap() {
        this.state.map = L.map('crm-map', {
            scrollWheelZoom: true,
            preferCanvas: true
        }).setView([46.6, 2.4], 6);

        const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            updateWhenIdle: true,
            keepBuffer: 2,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.state.map);

        tiles.on('tileerror', (event) => console.warn('Tuile cartographique indisponible', event.coords));

        const resizeMap = () => window.requestAnimationFrame(() => this.state.map.invalidateSize({ animate: false }));
        window.addEventListener('resize', resizeMap);
        setTimeout(resizeMap, 100);
        setTimeout(resizeMap, 600);

        this.loadGeoJSON();
    }

    async loadGeoJSON() {
        try {
            const response = await fetch('/vendor/maps/departements.geojson');
            const geo = await response.json();
            
            this.state.layer = L.geoJSON(geo, {
                style: (feature) => this.style(feature),
                onEachFeature: (feature, layer) => {
                    layer.on('click', () => {
                        this.state.department = feature.properties.code || feature.properties.nom || '';
                        this.state.city = '';
                        this.updateControls();
                        this.render();
                    });
                    layer.bindTooltip(`${feature.properties.code || ''} — ${feature.properties.nom || ''}`, { sticky: true });
                }
            }).addTo(this.state.map);
        } catch (error) {
            console.warn('GeoJSON loading failed', error);
        }
    }

    style(feature) {
        const code = feature.properties.code || feature.properties.nom || '';
        const row = (this.data.departments || []).find((item) => item.label === code);
        const max = Math.max(1, ...(this.data.departments || []).map((item) => item.total || 0));
        const total = row ? row.total : 0;

        return {
            color: this.state.department === code ? '#0f172a' : '#64748b',
            weight: this.state.department === code ? 3 : 1,
            fillColor: '#2563eb',
            fillOpacity: total ? 0.18 + Math.min(total / max, 1) * 0.55 : 0.04
        };
    }

    refreshLayer() {
        if (this.state.layer) {
            this.state.layer.setStyle((feature) => this.style(feature));
        }
    }

    bindEvents() {
        document.getElementById('crm-map-department')?.addEventListener('change', (event) => {
            this.state.department = event.target.value;
            this.state.city = '';
            this.updateControls();
            this.render();
        });

        document.getElementById('crm-map-city')?.addEventListener('change', (event) => {
            this.state.city = event.target.value;
            this.render();
            this.geocode();
        });

        document.getElementById('crm-map-reset')?.addEventListener('click', () => {
            this.state.department = '';
            this.state.city = '';
            this.state.types = { prospects: true, clients: true, partenaires: true };
            if (this.state.marker) {
                this.state.map.removeLayer(this.state.marker);
                this.state.marker = null;
            }
            this.updateControls();
            this.render();
            this.state.map.setView([46.6, 2.4], 6);
        });

        document.querySelectorAll('.crm-map-type').forEach((button) => {
            button.addEventListener('click', () => {
                this.state.types[button.dataset.type] = !this.state.types[button.dataset.type];
                if (!this.getActiveTypes().length) {
                    this.state.types[button.dataset.type] = true;
                }
                this.updateControls();
                this.render();
            });
        });

        document.getElementById('crm-map-departments')?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-department]');
            if (button) {
                this.state.department = button.dataset.department;
                this.state.city = '';
                this.updateControls();
                this.render();
            }
        });

        document.getElementById('crm-map-cities')?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-city]');
            if (button) {
                this.state.city = button.dataset.city;
                document.getElementById('crm-map-city').value = this.state.city;
                this.geocode();
            }
        });
    }

    getActiveTypes() {
        return Object.keys(this.state.types).filter((type) => this.state.types[type]);
    }

    match(row) {
        return this.getActiveTypes().some((type) => Number(row[type] || 0) > 0);
    }

    departments() {
        return (this.data.departments || []).filter((row) => 
            this.match(row) && (!this.state.department || row.label === this.state.department)
        );
    }

    cities() {
        return (this.data.cities || []).filter((row) => 
            this.match(row) && 
            (!this.state.department || row.department === this.state.department) && 
            (!this.state.city || row.label.toLowerCase().includes(this.state.city.toLowerCase()))
        );
    }

    count(row) {
        return this.getActiveTypes().reduce((sum, type) => sum + Number(row[type] || 0), 0);
    }

    render() {
        const visibleDepartments = this.departments();
        const visibleCities = this.cities();
        const total = visibleDepartments.reduce((sum, row) => sum + this.count(row), 0);

        this.renderKpis(visibleDepartments, visibleCities, total);
        this.renderDepartments(visibleDepartments);
        this.renderCities(visibleCities);
        this.renderScope();
        this.refreshLayer();
    }

    renderKpis(visibleDepartments, visibleCities, total) {
        const kpisContainer = document.getElementById('crm-map-kpis');
        if (!kpisContainer) return;

        const kpis = [
            ['Entités visibles', total, 'blue'],
            ['Départements', visibleDepartments.length, 'violet'],
            ['Villes', visibleCities.length, 'emerald'],
            ['Périmètre', this.state.department || 'France', 'amber']
        ];

        kpisContainer.innerHTML = kpis.map(([label, value, tone]) => `
            <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/60 dark:bg-gray-800/60 dark:ring-gray-700/60">
                <div class="text-sm text-gray-500">${label}</div>
                <div class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">
                    ${typeof value === 'number' ? this.nf.format(value) : this.esc(value)}
                </div>
                <div class="mt-1 text-xs text-gray-400">
                    ${tone === 'amber' ? 'Zone active' : 'Après filtres'}
                </div>
            </article>
        `).join('');
    }

    renderDepartments(visibleDepartments) {
        const container = document.getElementById('crm-map-departments');
        if (!container) return;

        const max = Math.max(1, ...visibleDepartments.map((row) => this.count(row)));

        container.innerHTML = visibleDepartments.slice(0, 16).map((row) => `
            <button data-department="${this.esc(row.label)}" class="w-full text-left">
                <div class="mb-1 flex justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">${this.esc(row.label)}</span>
                    <span class="font-semibold text-gray-950 dark:text-white">${this.nf.format(this.count(row))}</span>
                </div>
                <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-primary-500" style="width:${Math.max(4, this.count(row) / max * 100)}%"></div>
                </div>
            </button>
        `).join('') || '<p class="text-sm text-gray-500">Aucun département.</p>';
    }

    renderCities(visibleCities) {
        const container = document.getElementById('crm-map-cities');
        if (!container) return;

        container.innerHTML = visibleCities.slice(0, 40).map((row) => `
            <button data-city="${this.esc(row.label)}" class="rounded-lg border border-gray-200 px-3 py-2 text-left transition hover:border-primary-400 hover:bg-primary-50 dark:border-gray-700 dark:hover:bg-gray-700">
                <div class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">${this.esc(row.label)}</div>
                <div class="mt-1 text-xs text-gray-500">${this.esc(row.department || 'NC')} · ${this.nf.format(this.count(row))} entités</div>
            </button>
        `).join('') || '<p class="text-sm text-gray-500">Aucune ville.</p>';
    }

    renderScope() {
        const scopeElement = document.getElementById('crm-map-scope');
        if (!scopeElement) return;

        scopeElement.textContent = `${this.state.department ? 'Département ' + this.state.department : 'France entière'} · ${this.getActiveTypes().length === 3 ? 'Tous les types' : this.getActiveTypes().join(', ')}`;
    }

    updateControls() {
        const select = document.getElementById('crm-map-department');
        if (select) {
            select.innerHTML = '<option value="">Tous les départements</option>' + 
                (this.data.departments || []).map((row) => 
                    `<option value="${this.esc(row.label)}">${this.esc(row.label)} — ${this.nf.format(row.total)}</option>`
                ).join('');
            select.value = this.state.department;
        }

        const cityOptions = document.getElementById('crm-map-city-options');
        if (cityOptions) {
            cityOptions.innerHTML = (this.data.cities || [])
                .filter((row) => !this.state.department || row.department === this.state.department)
                .slice(0, 500)
                .map((row) => `<option value="${this.esc(row.label)}"></option>`)
                .join('');
        }

        document.querySelectorAll('.crm-map-type').forEach((button) => {
            button.classList.toggle('opacity-50', !this.state.types[button.dataset.type]);
        });
    }

    async geocode() {
        const row = (this.data.cities || []).find((item) => 
            item.label.toLowerCase() === this.state.city.toLowerCase() && 
            (!this.state.department || item.department === this.state.department)
        );

        if (!row) return;

        try {
            const params = new URLSearchParams({ 
                q: row.label, 
                limit: '1', 
                autocomplete: '0' 
            });
            const response = await fetch('https://api-adresse.data.gouv.fr/search/?' + params);
            const feature = (await response.json()).features?.[0];
            
            if (!feature) return;

            const [lon, lat] = feature.geometry.coordinates;
            if (this.state.marker) {
                this.state.map.removeLayer(this.state.marker);
            }
            this.state.marker = L.marker([lat, lon])
                .addTo(this.state.map)
                .bindPopup(`<strong>${this.esc(row.label)}</strong><br>${this.nf.format(this.count(row))} entités`)
                .openPopup();
            this.state.map.setView([lat, lon], 11);
        } catch (error) {
            console.warn('Géocodage indisponible', error);
        }
    }

    async refreshFromApi() {
        if (this.refreshing) return;
        this.refreshing = true;

        try {
            const response = await fetch('/api/crm-map', { 
                headers: { 
                    'Accept': 'application/json', 
                    'X-Requested-With': 'XMLHttpRequest' 
                }, 
                credentials: 'same-origin', 
                cache: 'no-store' 
            });

            if (!response.ok) throw new Error(`API ${response.status}`);
            
            this.data = await response.json();
            this.updateControls();
            this.render();
        } catch (error) {
            console.warn('Actualisation Carte CRM indisponible', error);
        } finally {
            this.refreshing = false;
        }
    }

    startAutoRefresh() {
        setInterval(() => this.refreshFromApi(), 60000);
    }

    updateData(newData) {
        this.data = newData;
        this.updateControls();
        this.render();
    }
}
