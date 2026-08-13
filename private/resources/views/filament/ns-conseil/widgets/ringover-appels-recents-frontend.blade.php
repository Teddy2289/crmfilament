<x-filament-widgets::widget>
    <x-filament::section heading="Historique des appels Ringover">
        <div x-data="ringoverTable()" @keydown.debounce.500ms="applyFilters">
            <!-- Filters Section -->
            <div class="grid gap-3 mb-4 lg:grid-cols-[repeat(4,minmax(0,1fr))]">
                <div class="flex items-center gap-2">
                    <button @click="filterDirection = ''"
                        :class="filterDirection === '' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-3 py-1 text-sm rounded-md transition">
                        Tous
                    </button>
                    <button @click="filterDirection = 'in'"
                        :class="filterDirection === 'in' ? 'bg-success-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-3 py-1 text-sm rounded-md transition">
                        Entrants
                    </button>
                    <button @click="filterDirection = 'out'"
                        :class="filterDirection === 'out' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-3 py-1 text-sm rounded-md transition">
                        Sortants
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">Agent</label>
                    <select x-model="filterAgent" @change="applyFilters" class="pw-field-input">
                        <option value="">Tous</option>
                        <template x-for="agent in agents" :key="agent.id">
                            <option :value="agent.id" x-text="agent.name"></option>
                        </template>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">Numero</label>
                    <input type="text" x-model="filterNumber" placeholder="Recherche numéro"
                        class="pw-field-input" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 w-full">Autres filtres</label>
                    <select x-model="filterAnswered" @change="applyFilters" class="pw-field-input">
                        <option value="">Tous statuts</option>
                        <option value="answered">Répondu</option>
                        <option value="missed">Manqué</option>
                    </select>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" x-model="filterHasRecording" @change="applyFilters" class="form-checkbox" />
                        Avec enregistrement
                    </label>
                    <button @click="clearFilters"
                        class="px-3 py-1 text-sm rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div x-show="errorMessage" x-cloak
                class="rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700 mb-4">
                <span x-text="errorMessage"></span>
            </div>

            <!-- Loading State -->
            <div x-show="loading" x-cloak class="flex items-center justify-center py-8">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-primary-600 rounded-full animate-bounce"></div>
                    <span class="text-sm text-gray-600">Chargement des appels...</span>
                </div>
            </div>

            <!-- Table -->
            <div x-show="!loading" x-cloak class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-2 pr-4">Date / Heure</th>
                            <th class="pb-2 pr-4">Direction</th>
                            <th class="pb-2 pr-4">Statut</th>
                            <th class="pb-2 pr-4">Durée</th>
                            <th class="pb-2 pr-4">Agent</th>
                            <th class="pb-2 pr-4">Numéro</th>
                            <th class="pb-2">Enregistrement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="call in calls" :key="call.id">
                            <tr class="border-b hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="py-2 pr-4 text-gray-700 dark:text-gray-300" x-text="formatDate(call.start_time)"></td>
                                <td class="py-2 pr-4">
                                    <span :class="call.direction === 'in' ? 'bg-success-100 text-success-700' : 'bg-primary-100 text-primary-700'"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                                        <span x-text="call.direction === 'in' ? 'Entrant' : 'Sortant'"></span>
                                    </span>
                                </td>
                                <td class="py-2 pr-4">
                                    <span :class="call.is_answered ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-700'"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                                        <span x-text="call.is_answered ? 'Réalisé' : 'Manqué'"></span>
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-400" x-text="call.duration_label"></td>
                                <td class="py-2 pr-4 text-gray-700 dark:text-gray-300" x-text="call.agent_name"></td>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 font-mono text-xs" x-text="call.numero"></td>
                                <td class="py-2">
                                    <template x-if="call.record_url">
                                        <div x-data="{ audioOpen: false }" class="flex flex-col gap-1">
                                            <button @click="audioOpen = !audioOpen"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-primary-50 text-primary-700 hover:bg-primary-100 transition w-fit">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                                </svg>
                                                <span x-text="audioOpen ? 'Fermer' : 'Écouter'"></span>
                                            </button>
                                            <div x-show="audioOpen" x-cloak>
                                                <audio controls preload="none" class="w-48 h-8" :src="call.record_url"></audio>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!call.record_url">
                                        <span class="text-gray-400 text-xs">-</span>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <!-- No results -->
                        <template x-if="calls.length === 0 && !loading">
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400">Aucun appel</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <template x-if="!loading">
                <div class="flex items-center justify-between mt-4 text-sm text-gray-500">
                    <span>Page <span x-text="pagination.current_page"></span></span>
                    <div class="flex gap-2">
                        <button @click="previousPage" :disabled="pagination.current_page <= 1"
                            class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50">
                            Précédent
                        </button>
                        <button @click="nextPage" :disabled="!pagination.has_more"
                            class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50">
                            Suivant
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-filament::section>

    <script>
        function ringoverTable() {
            return {
                calls: [],
                agents: [],
                loading: false,
                errorMessage: '',
                filterDirection: '',
                filterAgent: '',
                filterNumber: '',
                filterAnswered: '',
                filterHasRecording: false,
                pagination: {
                    current_page: 1,
                    per_page: 25,
                    total: 0,
                    has_more: false,
                },

                async init() {
                    await this.loadAgents();
                    await this.applyFilters();
                },

                async loadAgents() {
                    try {
                        const response = await fetch('{{ route("api.ringover.agents") }}');
                        const result = await response.json();
                        if (result.success) {
                            this.agents = result.data;
                        }
                    } catch (error) {
                        console.error('Failed to load agents:', error);
                    }
                },

                async applyFilters() {
                    this.pagination.current_page = 1;
                    await this.loadCalls();
                },

                async loadCalls() {
                    this.loading = true;
                    this.errorMessage = '';

                    try {
                        const params = new URLSearchParams({
                            direction: this.filterDirection,
                            filter_agent: this.filterAgent,
                            filter_number: this.filterNumber,
                            filter_answered: this.filterAnswered,
                            filter_has_recording: this.filterHasRecording ? '1' : '0',
                            page: this.pagination.current_page,
                            per_page: this.pagination.per_page,
                        });

                        const response = await fetch('{{ route("api.ringover.calls") }}?' + params.toString());
                        const result = await response.json();

                        if (result.success) {
                            this.calls = result.data;
                            this.pagination = result.pagination;
                        } else {
                            this.errorMessage = result.error || 'Erreur lors du chargement des appels';
                            this.calls = [];
                        }
                    } catch (error) {
                        this.errorMessage = 'Erreur lors du chargement des appels';
                        this.calls = [];
                        console.error('Failed to load calls:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                formatDate(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${day}/${month}/${year} ${hours}:${minutes}`;
                },

                clearFilters() {
                    this.filterDirection = '';
                    this.filterAgent = '';
                    this.filterNumber = '';
                    this.filterAnswered = '';
                    this.filterHasRecording = false;
                    this.applyFilters();
                },

                async previousPage() {
                    if (this.pagination.current_page > 1) {
                        this.pagination.current_page--;
                        await this.loadCalls();
                    }
                },

                async nextPage() {
                    if (this.pagination.has_more) {
                        this.pagination.current_page++;
                        await this.loadCalls();
                    }
                },
            };
        }
    </script>
</x-filament-widgets::widget>
