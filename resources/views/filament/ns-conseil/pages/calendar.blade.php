<x-filament-panels::page>
    {{-- Sidebar panneau de contrôle --}}
    <div
        class="calendar-shell block mb-6"
        x-data="{
            period: '',
            sourceHidden: JSON.parse(localStorage.getItem('hiddenCalendarSources') || '[]').map(String),
            sourceVisible(source) { return !this.sourceHidden.includes(String(source).toLowerCase()); },
            applySourceVisibility() {
                document.querySelectorAll('[data-event-source]').forEach(el => {
                    const source = String(el.dataset.eventSource || '').toLowerCase();
                    el.style.display = this.sourceHidden.includes(source) ? 'none' : '';
                });
            },
            toggleSource(source) {
                const normalized = String(source).toLowerCase();
                this.sourceHidden = this.sourceHidden.includes(normalized)
                    ? this.sourceHidden.filter(value => value !== normalized)
                    : [...this.sourceHidden, normalized];
                localStorage.setItem('hiddenCalendarSources', JSON.stringify(this.sourceHidden));
                this.applySourceVisibility();
            },
            showAllSources() {
                this.sourceHidden = [];
                localStorage.setItem('hiddenCalendarSources', JSON.stringify(this.sourceHidden));
                this.applySourceVisibility();
            },
            syncPeriod() {
                const title = document.querySelector('.fi-wi-full-calendar .fc-toolbar-title');
                if (title?.textContent?.trim()) this.period = title.textContent.trim();
            },
            calendarCommand(command) {
                const selectors = {
                    prev: '.fc-prev-button',
                    next: '.fc-next-button',
                    today: '.fc-today-button',
                    month: '.fc-dayGridMonth-button',
                    week: '.fc-timeGridWeek-button',
                    day: '.fc-timeGridDay-button',
                    list: '.fc-listMonth-button'
                };
                document.querySelector(selectors[command])?.click();
                window.setTimeout(() => this.syncPeriod(), 80);
            },
            init() {
                this.$nextTick(() => {
                    this.applySourceVisibility();
                    this.syncPeriod();
                    const root = document.querySelector('.fi-wi-full-calendar') || document.body;
                    this.sourceObserver = new MutationObserver(() => {
                        this.applySourceVisibility();
                        this.syncPeriod();
                    });
                    this.sourceObserver.observe(root, { childList: true, subtree: true });
                });
            }
        }"
    >
        {{-- Barre compacte inspirée du design de référence --}}
        <div class="calendar-toolbar mb-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
                <div class="mr-auto flex min-w-[150px] items-center gap-2">
                    <span class="calendar-toolbar-period" x-text="period || 'Calendrier'"></span>
                    <span class="hidden rounded-md bg-primary-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-950/40 dark:text-primary-300 sm:inline-flex">Vue équipe</span>
                </div>
                <div class="inline-flex items-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/60">
                    <button type="button" @click="calendarCommand('prev')" class="calendar-toolbar-button" aria-label="Période précédente">‹</button>
                    <button type="button" @click="calendarCommand('today')" class="calendar-toolbar-today">Aujourd’hui</button>
                    <button type="button" @click="calendarCommand('next')" class="calendar-toolbar-button" aria-label="Période suivante">›</button>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                    <span class="hidden sm:inline">Afficher :</span>
                    <label class="calendar-source-chip"><input type="checkbox" :checked="sourceVisible('crm')" @change="toggleSource('crm')"><span>CRM</span></label>
                    <label class="calendar-source-chip"><input type="checkbox" :checked="sourceVisible('google')" @change="toggleSource('google')"><span>Google</span></label>
                </div>
                <div class="inline-flex overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/60">
                    <button type="button" @click="calendarCommand('month')" class="calendar-view-button">Mois</button>
                    <button type="button" @click="calendarCommand('week')" class="calendar-view-button">Semaine</button>
                    <button type="button" @click="calendarCommand('day')" class="calendar-view-button">Jour</button>
                    <button type="button" @click="calendarCommand('list')" class="calendar-view-button">Liste</button>
                </div>
            </div>
        </div>

        {{-- Bandeau compact des agendas partagés --}}
        <div class="calendar-shared-bar mb-3 rounded-xl border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Agendas partagés :</span>
                @forelse($googleCalendars as $calendarId => $cal)
                    <span class="calendar-shared-chip"><span class="calendar-shared-dot" style="background-color: {{ $cal['color'] ?? '#94a3b8' }}"></span>{{ $cal['name'] }}</span>
                @empty
                    <span class="text-xs text-gray-400">Aucun agenda Google disponible</span>
                @endforelse
            </div>
        </div>

        {{-- Panneau de gauche : statut et filtres --}}
        <div class="calendar-sidebar grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Statut Google --}}
            <div class="calendar-status-card md:col-span-2 rounded-2xl border @if(! $isGoogleConnected) border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100 dark:border-amber-800 dark:from-amber-950 dark:to-amber-900 @else border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 dark:border-emerald-800 dark:from-emerald-950 dark:to-emerald-900 @endif p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    @if(! $isGoogleConnected)
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-amber-500 shrink-0" />
                        <div>
                            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Google Calendar non connecté</p>
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Connectez votre compte pour synchroniser vos RDV</p>
                        </div>
                    @else
                        <x-heroicon-o-check-circle class="h-6 w-6 text-emerald-500 shrink-0" />
                        <div>
                            <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Google Calendar connecté</p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Synchronisation automatique active</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Filtre interactif des types de RDV --}}
            <div
                class="calendar-panel rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-4 shadow-sm"
                x-data="{
                    open: window.innerWidth >= 768,
                    hiddenTypes: JSON.parse(localStorage.getItem('hiddenCalendarEventTypes') || '[]').map(String),
                    types: ['appel', 'permanence', 'presentation', 'intervention', 'annule'],
                    normalize(value) { return String(value ?? '').toLowerCase(); },
                    isTypeVisible(type) { return !this.hiddenTypes.includes(this.normalize(type)); },
                    applyTypeVisibility() {
                        document.querySelectorAll('[data-event-type], [data-event-status]').forEach(el => {
                            const type = this.normalize(el.dataset.eventType);
                            const status = this.normalize(el.dataset.eventStatus);
                            const matchesHiddenType = type && this.hiddenTypes.includes(type);
                            const matchesHiddenCancelled = status === 'annule' && this.hiddenTypes.includes('annule');
                            el.style.display = matchesHiddenType || matchesHiddenCancelled ? 'none' : '';
                        });
                    },
                    toggleType(type) {
                        const normalized = this.normalize(type);
                        this.hiddenTypes = this.hiddenTypes.includes(normalized)
                            ? this.hiddenTypes.filter(value => value !== normalized)
                            : [...this.hiddenTypes, normalized];
                        localStorage.setItem('hiddenCalendarEventTypes', JSON.stringify(this.hiddenTypes));
                        this.applyTypeVisibility();
                    },
                    showAllTypes() {
                        this.hiddenTypes = [];
                        localStorage.setItem('hiddenCalendarEventTypes', JSON.stringify(this.hiddenTypes));
                        this.applyTypeVisibility();
                    },
                    hideAllTypes() {
                        this.hiddenTypes = [...this.types];
                        localStorage.setItem('hiddenCalendarEventTypes', JSON.stringify(this.hiddenTypes));
                        this.applyTypeVisibility();
                    },
                    init() {
                        this.$nextTick(() => {
                            this.applyTypeVisibility();
                            const root = this.$root.closest('.fi-wi-full-calendar') || document.body;
                            this.observer = new MutationObserver(() => this.applyTypeVisibility());
                            this.observer.observe(root, { childList: true, subtree: true });
                        });
                    }
                }"
            >
                <div class="flex items-center justify-between gap-3">
                    <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-left" @click="open = !open" :aria-expanded="open.toString()">
                        <span class="calendar-section-icon"><x-heroicon-o-calendar class="h-4 w-4" /></span>
                        <span class="min-w-0">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Types de RDV</span>
                            <span class="mt-0.5 block text-[11px] text-gray-500 dark:text-gray-400">Filtrer les événements affichés</span>
                        </span>
                        <x-heroicon-o-chevron-down class="ml-auto h-4 w-4 text-gray-400 transition-transform md:hidden" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                    <div class="flex shrink-0 items-center gap-1">
                        <button type="button" @click="showAllTypes()" class="rounded-md px-2 py-1 text-[11px] font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-950/30" title="Afficher tous les types">Tous</button>
                        <button type="button" @click="hideAllTypes()" class="rounded-md px-2 py-1 text-[11px] font-medium text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Masquer tous les types">Aucun</button>
                    </div>
                </div>
                <div x-show="open" class="mt-3 grid grid-cols-2 gap-2 md:grid-cols-1 xl:grid-cols-2">
                    <label class="calendar-type-filter" :class="isTypeVisible('appel') ? 'is-active' : 'is-muted'"><input type="checkbox" :checked="isTypeVisible('appel')" @change="toggleType('appel')"><span class="calendar-type-dot bg-[#0ea5e9]"></span><span>Appel</span></label>
                    <label class="calendar-type-filter" :class="isTypeVisible('permanence') ? 'is-active' : 'is-muted'"><input type="checkbox" :checked="isTypeVisible('permanence')" @change="toggleType('permanence')"><span class="calendar-type-dot bg-[#10b981]"></span><span>Permanence</span></label>
                    <label class="calendar-type-filter" :class="isTypeVisible('presentation') ? 'is-active' : 'is-muted'"><input type="checkbox" :checked="isTypeVisible('presentation')" @change="toggleType('presentation')"><span class="calendar-type-dot bg-[#6366f1]"></span><span>Présentation</span></label>
                    <label class="calendar-type-filter" :class="isTypeVisible('intervention') ? 'is-active' : 'is-muted'"><input type="checkbox" :checked="isTypeVisible('intervention')" @change="toggleType('intervention')"><span class="calendar-type-dot bg-[#f97316]"></span><span>Intervention</span></label>
                    <label class="calendar-type-filter" :class="isTypeVisible('annule') ? 'is-active' : 'is-muted'"><input type="checkbox" :checked="isTypeVisible('annule')" @change="toggleType('annule')"><span class="calendar-type-dot bg-[#9ca3af]"></span><span>Annulé</span></label>
                </div>
            </div>

            {{-- Légende Google interactive --}}
            <div class="calendar-panel rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-4 shadow-sm"
                x-data="{
                    open: window.innerWidth >= 768,
                    hidden: JSON.parse(localStorage.getItem('hiddenGoogleCalendars') || '[]').map(String),
                    colors: JSON.parse(localStorage.getItem('googleCalendarColors') || '{}'),
                    search: '',
                    calendarNames: @js(collect($googleCalendars)->pluck('name')->map(fn ($name) => mb_strtolower($name))->values()->all()),
                    calendarIds() {
                        return @js(array_keys($googleCalendars)).map(String);
                    },
                    isVisible(id) {
                        return !this.hidden.includes(this.normalizeId(id));
                    },
                    visibleCount() {
                        return this.calendarIds().filter(id => this.isVisible(id)).length;
                    },
                    hasSearchMatches() {
                        const query = this.search.trim().toLowerCase();
                        return !query || this.calendarNames.some(name => name.includes(query));
                    },
                    normalizeId(id) {
                        return String(id ?? '');
                    },
                    applyAllVisibility() {
                        document.querySelectorAll('[data-calendar-id]').forEach(el => {
                            const id = this.normalizeId(el.dataset.calendarId);
                            el.style.display = this.hidden.includes(id) ? 'none' : '';
                        });
                    },
                    applyVisibility(id) {
                        const normalizedId = this.normalizeId(id);
                        document.querySelectorAll('[data-calendar-id]').forEach(el => {
                            if (this.normalizeId(el.dataset.calendarId) === normalizedId) {
                                el.style.display = this.hidden.includes(normalizedId) ? 'none' : '';
                            }
                        });
                    },
                    init() {
                        this.$nextTick(() => {
                            this.applyAllVisibility();
                            const calendarRoot = this.$root.closest('.fi-wi-full-calendar') || document.body;
                            this.observer = new MutationObserver(() => this.applyAllVisibility());
                            this.observer.observe(calendarRoot, { childList: true, subtree: true });
                        });
                    },
                    applyColor(id, color) {
                        document.querySelectorAll('[data-calendar-id]').forEach(el => {
                            if (el.dataset.calendarId === id) {
                                el.style.setProperty('background-color', color, 'important');
                                el.style.setProperty('border-color', color, 'important');
                                el.querySelectorAll('.fc-event-title, .fc-event-time').forEach(child => child.style.setProperty('color', '#ffffff', 'important'));
                            }
                        });
                    },
                    toggle(id) {
                        const normalizedId = this.normalizeId(id);
                        this.hidden = this.hidden.includes(normalizedId)
                            ? this.hidden.filter(value => this.normalizeId(value) !== normalizedId)
                            : [...this.hidden, normalizedId];
                        localStorage.setItem('hiddenGoogleCalendars', JSON.stringify(this.hidden));
                        this.applyVisibility(normalizedId);
                    },
                    setColor(id, color) {
                        this.colors = {...this.colors, [id]: color};
                        localStorage.setItem('googleCalendarColors', JSON.stringify(this.colors));
                        this.applyColor(id, color);
                    },
                    colorFor(id, fallback) {
                        return this.colors[id] || fallback;
                    },
                    showAll() {
                        this.hidden = [];
                        localStorage.setItem('hiddenGoogleCalendars', JSON.stringify(this.hidden));
                        this.applyAllVisibility();
                    },
                    hideAll() {
                        this.hidden = @js(array_keys($googleCalendars)).map(String);
                        localStorage.setItem('hiddenGoogleCalendars', JSON.stringify(this.hidden));
                        this.applyAllVisibility();
                    },
                    shortcutToggle() {
                        this.hidden.length ? this.showAll() : this.hideAll();
                    },
                    resetColors() {
                        this.colors = {};
                        localStorage.removeItem('googleCalendarColors');
                        document.querySelectorAll('[data-calendar-id]').forEach(el => {
                            const id = el.dataset.calendarId;
                            const fallback = el.dataset.calendarDefaultColor;
                            if (fallback) this.applyColor(id, fallback);
                        });
                    },
                }"
                @keydown.window="if ($event.altKey && $event.key.toLowerCase() === 'g' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) { $event.preventDefault(); shortcutToggle(); }"
            >
                <div class="flex items-start justify-between gap-3 mb-3">
                    <button type="button" class="flex min-w-0 flex-1 items-start gap-2 text-left" @click="open = !open" :aria-expanded="open.toString()" aria-controls="google-calendar-options">
                        <x-heroicon-o-calendar-days class="h-4 w-4 text-gray-500 dark:text-gray-400 mt-0.5 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Calendriers Google</p>
                            @if(count($googleCalendars) > 0)
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    <span x-text="visibleCount()"></span> affiché(s) sur {{ count($googleCalendars) }}
                                </p>
                            @endif
                            <span class="mt-1 hidden text-[10px] text-gray-400 md:block">Alt+G pour afficher/masquer</span>
                        </div>
                        <x-heroicon-o-chevron-down class="ml-auto h-4 w-4 text-gray-400 transition-transform md:hidden" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                    @if(count($googleCalendars) > 0)
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" @click="showAll()" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-950/30" title="Afficher tous les calendriers">
                                <x-heroicon-o-eye class="h-3.5 w-3.5" />
                                <span class="hidden xl:inline">Tous</span>
                            </button>
                            <button type="button" @click="hideAll()" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Masquer tous les calendriers">
                                <x-heroicon-o-eye-slash class="h-3.5 w-3.5" />
                                <span class="hidden xl:inline">Aucun</span>
                            </button>
                            <button type="button" @click="resetColors()" class="inline-flex items-center rounded-md p-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700" title="Réinitialiser les couleurs">
                                <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    @endif
                </div>
                <div id="google-calendar-options" x-show="open" x-cloak>
                @if(count($googleCalendars) > 0)
                    <div class="relative mb-3">
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-2.5 top-2 h-4 w-4 text-gray-400" />
                        <input
                            type="search"
                            x-model="search"
                            @keydown.escape="search = ''"
                            placeholder="Rechercher un agenda…"
                            aria-label="Rechercher un agenda Google"
                            class="w-full rounded-lg border-gray-300 bg-gray-50 py-1.5 pl-8 pr-8 text-xs text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-200"
                        >
                        <button type="button" x-show="search" x-cloak @click="search = ''" class="absolute right-2 top-1.5 rounded p-0.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" title="Effacer la recherche">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>
                @endif
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                    @forelse($googleCalendars as $calendarId => $cal)
                        <div
                            x-show="!search.trim() || @js(mb_strtolower($cal['name'])).includes(search.trim().toLowerCase())"
                            x-cloak
                            class="flex items-center gap-2 rounded-lg p-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <label
                                class="flex min-w-0 flex-1 cursor-pointer items-center gap-2.5 text-sm text-gray-600 dark:text-gray-400"
                                :class="hidden.includes(String(@js($calendarId))) ? 'opacity-40 grayscale' : 'opacity-100'"
                                title="Afficher ou masquer ce calendrier"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                    :checked="!hidden.includes(String(@js($calendarId)))"
                                    @change="toggle(@js($calendarId))"
                                >
                                <span class="h-3 w-3 rounded-full shrink-0 ring-2 ring-offset-1 ring-gray-200 dark:ring-gray-600" :style="`background-color: ${colorFor(@js($calendarId), @js($cal['color']))}`"></span>
                                <span class="truncate text-left">{{ $cal['name'] }}</span>
                                <span
                                    class="hidden shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-medium sm:inline"
                                    :class="isVisible(@js($calendarId)) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                    x-text="isVisible(@js($calendarId)) ? 'Affiché' : 'Masqué'"
                                ></span>
                            </label>
                            <label class="flex shrink-0 cursor-pointer items-center gap-1 text-xs text-gray-500" title="Choisir la couleur de ce calendrier">
                                <span class="sr-only">Couleur de {{ $cal['name'] }}</span>
                                <input
                                    type="color"
                                    value="{{ $cal['color'] }}"
                                    :value="colorFor(@js($calendarId), @js($cal['color']))"
                                    @click.stop
                                    @input="setColor(@js($calendarId), $event.target.value)"
                                    class="h-6 w-6 cursor-pointer rounded border-0 bg-transparent p-0"
                                >
                            </label>
                        </div>
                    @empty
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 p-2">
                            <x-heroicon-o-information-circle class="h-4 w-4 shrink-0" />
                            <span>Aucun calendrier accessible</span>
                        </div>
                    @endforelse
                    @if(count($googleCalendars) > 0)
                        <div x-show="search.trim() && !hasSearchMatches()" x-cloak class="mt-2 rounded-lg border border-dashed border-gray-300 p-3 text-center text-xs text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            Aucun agenda ne correspond à cette recherche.
                        </div>
                    @endif
                </div>
                </div>
            </div>
        </div>

        {{-- Panneau principal : calendrier --}}
        <div class="w-full">
            <div class="calendar-main-card rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="calendar-main-header p-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calendar class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Calendrier</h3>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                CRM
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                Google
                            </span>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    {{-- Widget calendrier — rendu par Filament via getFooterWidgets() --}}
                </div>
            </div>
        </div>
    </div>

    @livewire('google-event-modal')

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #4b5563;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>

<style>
    .calendar-shell {
        --calendar-accent: #6366f1;
    }

    .calendar-toolbar-period {
        color: #111827;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -.02em;
        text-transform: capitalize;
    }

    .calendar-toolbar-button,
    .calendar-toolbar-today,
    .calendar-view-button {
        min-height: 2rem;
        padding: .35rem .7rem;
        color: #475569;
        font-size: .7rem;
        font-weight: 600;
        border-right: 1px solid #e5e7eb;
        transition: background-color 140ms ease, color 140ms ease;
    }

    .calendar-toolbar-button {
        width: 2rem;
        padding-left: 0;
        padding-right: 0;
        font-size: 1.1rem;
        line-height: 1;
    }

    .calendar-toolbar-button:hover,
    .calendar-toolbar-today:hover,
    .calendar-view-button:hover {
        background: #e0f2fe;
        color: #0369a1;
    }

    .calendar-toolbar-today {
        color: #0f172a;
        background: #ffffff;
    }

    .calendar-view-button:last-child,
    .calendar-toolbar-today:last-child {
        border-right: 0;
    }

    .calendar-source-chip,
    .calendar-shared-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        min-height: 1.7rem;
        padding: .25rem .55rem;
        border: 1px solid #dbe3ec;
        border-radius: 9999px;
        background: #f8fafc;
        color: #475569;
        font-size: .68rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .calendar-source-chip input {
        width: .78rem;
        height: .78rem;
        accent-color: #0284c7;
    }

    .calendar-shared-bar {
        min-height: 2.75rem;
    }

    .calendar-shared-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 9999px;
        box-shadow: 0 0 0 2px rgba(148, 163, 184, .18);
    }

    .dark .calendar-toolbar-period {
        color: #f8fafc;
    }

    .dark .calendar-toolbar-button,
    .dark .calendar-toolbar-today,
    .dark .calendar-view-button {
        color: #cbd5e1;
        border-color: #475569;
    }

    .dark .calendar-toolbar-button:hover,
    .dark .calendar-toolbar-today:hover,
    .dark .calendar-view-button:hover {
        background: rgba(14, 116, 144, .25);
        color: #bae6fd;
    }

    .dark .calendar-toolbar-today,
    .dark .calendar-source-chip,
    .dark .calendar-shared-chip {
        background: rgba(30, 41, 59, .75);
        color: #cbd5e1;
        border-color: #475569;
    }

    .calendar-status-card,
    .calendar-panel,
    .calendar-main-card {
        transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .calendar-status-card:hover,
    .calendar-panel:hover,
    .calendar-main-card:hover {
        border-color: color-mix(in srgb, var(--calendar-accent) 28%, #d1d5db);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .calendar-panel {
        position: relative;
        overflow: hidden;
    }

    .calendar-panel::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 3px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4);
        opacity: .85;
    }

    .calendar-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: .6rem;
        color: #6366f1;
        background: #eef2ff;
    }

    .dark .calendar-section-icon {
        color: #c4b5fd;
        background: rgba(79, 70, 229, .22);
    }

    .calendar-main-header {
        background: linear-gradient(180deg, rgba(248, 250, 252, .92), rgba(255, 255, 255, .75));
    }

    .dark .calendar-main-header {
        background: linear-gradient(180deg, rgba(31, 41, 55, .8), rgba(31, 41, 55, .45));
    }

    .calendar-main-card .fi-wi-full-calendar {
        border-radius: .85rem;
        overflow: hidden;
    }

    .calendar-panel input[type='checkbox'] {
        accent-color: #6366f1;
    }

    .calendar-type-filter {
        display: flex;
        align-items: center;
        gap: .55rem;
        min-width: 0;
        padding: .45rem .55rem;
        border: 1px solid transparent;
        border-radius: .65rem;
        color: #4b5563;
        font-size: .78rem;
        cursor: pointer;
        transition: background-color 140ms ease, border-color 140ms ease, opacity 140ms ease;
    }

    .calendar-type-filter:hover,
    .calendar-type-filter.is-active {
        background: #f8fafc;
        border-color: #e5e7eb;
    }

    .calendar-type-filter.is-muted {
        opacity: .45;
        background: #f3f4f6;
    }

    .calendar-type-filter > span:last-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .calendar-type-filter input {
        width: .9rem;
        height: .9rem;
        flex: 0 0 auto;
    }

    .calendar-type-dot {
        width: .55rem;
        height: .55rem;
        flex: 0 0 auto;
        border-radius: 9999px;
        box-shadow: 0 0 0 3px rgba(148, 163, 184, .16);
    }

    .dark .calendar-type-filter {
        color: #d1d5db;
    }

    .dark .calendar-type-filter:hover,
    .dark .calendar-type-filter.is-active {
        background: rgba(55, 65, 81, .6);
        border-color: #4b5563;
    }

    .dark .calendar-type-filter.is-muted {
        background: rgba(31, 41, 55, .7);
    }

    @media (max-width: 1023px) {
        .calendar-sidebar {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 639px) {
        .calendar-toolbar-period {
            font-size: .95rem;
        }

        .calendar-toolbar {
            padding: .55rem;
        }

        .calendar-toolbar > div {
            align-items: stretch;
        }

        .calendar-toolbar .calendar-source-chip span {
            display: none;
        }

        .calendar-toolbar .calendar-source-chip {
            padding-left: .45rem;
            padding-right: .45rem;
        }

        .calendar-sidebar {
            grid-template-columns: 1fr;
        }

        .calendar-main-header {
            padding: .8rem 1rem;
        }
    }
</style>
</x-filament-panels::page>
