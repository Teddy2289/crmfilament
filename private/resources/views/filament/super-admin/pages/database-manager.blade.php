{{-- resources/views/filament/super-admin/pages/database-manager.blade.php --}}
<x-filament-panels::page>

    {{-- ── Tout dans un seul x-data pour que les modals partagent le scope ── --}}
    <div x-data="{ confirmDrop: null, confirmDropTable: null }" class="relative">

        <div class="flex gap-4 h-[calc(100vh-12rem)]">

            {{-- ── Sidebar : liste des tables ── --}}
            <div
                class="w-64 shrink-0 bg-white dark:bg-gray-900 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10 flex flex-col overflow-hidden">
                <div class="p-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        {{ $this->getTableCount() }} tables
                    </div>
                    <input type="text" placeholder="Filtrer les tables…" x-data="{ filter: '' }" x-model="filter"
                        @input="
                    document.querySelectorAll('[data-table-name]').forEach(el => {
                        el.style.display = el.dataset.tableName.includes(filter) ? '' : 'none';
                    })
                "
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-2 py-1.5" />
                </div>
                <div class="overflow-y-auto flex-1 py-1">
                    @foreach ($this->getTables() as $table)
                        <div data-table-name="{{ $table }}" wire:click="selectTable('{{ $table }}')"
                            class="group flex items-center justify-between px-3 py-2 cursor-pointer text-sm transition-colors
                           {{ $selectedTable === $table
                               ? 'bg-violet-50 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-medium'
                               : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                            <span class="truncate flex items-center gap-1.5">
                                <x-heroicon-o-table-cells class="w-3.5 h-3.5 opacity-50 shrink-0" />
                                {{ $table }}
                            </span>
                            @php
                                $protected = [
                                    'users',
                                    'roles',
                                    'permissions',
                                    'model_has_roles',
                                    'model_has_permissions',
                                    'role_has_permissions',
                                    'migrations',
                                ];
                            @endphp
                            @if (!in_array($table, $protected))
                                <button @click.prevent.stop="confirmDropTable = '{{ $table }}'"
                                    class="opacity-0 group-hover:opacity-100 transition text-danger-400 hover:text-danger-600 ml-1"
                                    title="Supprimer la table">
                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                </button>
                            @else
                                <span class="opacity-0 group-hover:opacity-50 text-xs text-gray-400 ml-1"
                                    title="Table protégée">🔒</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Zone principale ── --}}
            <div
                class="flex-1 flex flex-col overflow-hidden bg-white dark:bg-gray-900 rounded-xl shadow ring-1 ring-gray-950/5 dark:ring-white/10">

                @if (!$selectedTable)
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <x-heroicon-o-circle-stack class="w-16 h-16 mb-4 opacity-30" />
                        <p class="text-lg font-medium">Sélectionner une table</p>
                        <p class="text-sm mt-1">Cliquez sur une table dans la liste à gauche</p>
                    </div>
                @else
                    {{-- Header ──}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <span class="font-mono font-semibold text-violet-600 dark:text-violet-400 text-sm">{{ $selectedTable }}</span>
                    <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">
                        {{ number_format($totalRows) }} lignes
                    </span>
                </div>
                <div class="flex gap-1">
                    @foreach (['data' => 'Données', 'structure' => 'Structure', 'sql' => 'SQL libre'] as $tab => $label)
                        <button
                            wire:click="$set('activeTab', '{{ $tab }}')"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium
                                   {{ $activeTab === $tab
                                       ? 'bg-violet-600 text-white'
                                       : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ── Onglet Données ── --}}
                    @if ($activeTab === 'data')
                        <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 dark:border-gray-800">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 shrink-0" />
                            <input type="text" wire:model.live.debounce.300ms="searchQuery"
                                placeholder="Rechercher dans toutes les colonnes…"
                                class="flex-1 text-sm border-0 bg-transparent focus:ring-0 text-gray-700 dark:text-gray-300 placeholder-gray-400" />
                            @if ($searchQuery)
                                <button wire:click="$set('searchQuery', null)"
                                    class="text-gray-400 hover:text-gray-600">
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </button>
                            @endif
                        </div>

                        <div class="overflow-auto flex-1">
                            @if (count($tableData) > 0)
                                <table class="w-full text-xs">
                                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800 z-10">
                                        <tr>
                                            <th
                                                class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700 w-10">
                                                #</th>
                                            @foreach (array_keys($tableData[0]) as $col)
                                                <th
                                                    class="px-3 py-2 text-left text-gray-600 dark:text-gray-400 font-medium border-b border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                                    {{ $col }}
                                                </th>
                                            @endforeach
                                            <th class="px-3 py-2 border-b border-gray-200 dark:border-gray-700 w-16">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tableData as $i => $row)
                                            <tr
                                                class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                                <td class="px-3 py-1.5 text-gray-400">
                                                    {{ ($currentPage - 1) * $perPage + $i + 1 }}</td>
                                                @foreach ($row as $key => $value)
                                                    <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300 max-w-xs">
                                                        @if (is_null($value))
                                                            <span
                                                                class="text-gray-300 dark:text-gray-600 italic">NULL</span>
                                                        @elseif(strlen((string) $value) > 60)
                                                            <span title="{{ htmlspecialchars($value) }}"
                                                                class="cursor-help">
                                                                {{ Str::limit((string) $value, 60) }}
                                                            </span>
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="px-3 py-1.5">
                                                    @if (isset($row['id']))
                                                        <button @click.prevent.stop="confirmDrop = {{ $row['id'] }}"
                                                            class="text-danger-400 hover:text-danger-600 transition"
                                                            title="Supprimer cette ligne">
                                                            <x-heroicon-o-trash class="w-4 h-4" />
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="flex items-center justify-center h-40 text-gray-400 text-sm">
                                    Aucune donnée{{ $searchQuery ? " pour « $searchQuery »" : '' }}
                                </div>
                            @endif
                        </div>

                        @if ($totalRows > $perPage)
                            <div
                                class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                                <span class="text-xs text-gray-500">
                                    Page {{ $currentPage }} / {{ $this->getTotalPages() }} —
                                    {{ number_format($totalRows) }} lignes
                                </span>
                                <div class="flex gap-2">
                                    <button wire:click="prevPage" @class([
                                        'text-xs px-3 py-1.5 rounded-lg border transition',
                                        'opacity-40 cursor-not-allowed' => $currentPage <= 1,
                                    ])
                                        @disabled($currentPage <= 1)>
                                        ← Précédent
                                    </button>
                                    <button wire:click="nextPage" @class([
                                        'text-xs px-3 py-1.5 rounded-lg border transition',
                                        'opacity-40 cursor-not-allowed' => $currentPage >= $this->getTotalPages(),
                                    ])
                                        @disabled($currentPage >= $this->getTotalPages())>
                                        Suivant →
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- ── Onglet Structure ── --}}
                    @elseif($activeTab === 'structure')
                        <div class="overflow-auto flex-1 p-4">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        @foreach (['Colonne', 'Type', 'Null', 'Clé', 'Défaut', 'Extra', ''] as $h)
                                            <th
                                                class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                                {{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tableColumns as $col)
                                        @php $protected = ['id', 'created_at', 'updated_at', 'deleted_at']; @endphp
                                        <tr
                                            class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <td
                                                class="px-3 py-2 font-mono font-semibold text-violet-600 dark:text-violet-400">
                                                {{ $col->Field }}
                                                @if (in_array($col->Field, $protected))
                                                    <span class="text-gray-300 text-xs ml-1">🔒</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 font-mono text-blue-600 dark:text-blue-400">
                                                {{ $col->Type }}</td>
                                            <td class="px-3 py-2">
                                                <span @class([
                                                    'text-xs px-1.5 py-0.5 rounded',
                                                    'bg-green-100 text-green-700' => $col->Null === 'YES',
                                                    'bg-red-100 text-red-700' => $col->Null === 'NO',
                                                ])>
                                                    {{ $col->Null }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2">
                                                @if ($col->Key)
                                                    <span
                                                        class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">{{ $col->Key }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-500 italic">{{ $col->Default ?? 'NULL' }}
                                            </td>
                                            <td class="px-3 py-2 text-gray-500">{{ $col->Extra }}</td>
                                            <td class="px-3 py-2">
                                                @php
                                                    $rules = \App\Models\FieldVisibility::getRulesForTable(
                                                        $selectedTable,
                                                    )->where('column_name', $col->Field);
                                                    $hidden = $rules->where('visible', false)->pluck('role_name');
                                                @endphp
                                                @if ($hidden->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach ($hidden as $role)
                                                            <span
                                                                class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-600">
                                                                🚫 {{ $role }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-300">Tous</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                @if (!in_array($col->Field, $protected))
                                                    <button wire:click="dropColumn('{{ $col->Field }}')"
                                                        wire:confirm="Supprimer la colonne `{{ $col->Field }}` ?"
                                                        class="text-danger-400 hover:text-danger-600 transition"
                                                        title="Supprimer">
                                                        <x-heroicon-o-trash class="w-4 h-4" />
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- ── Onglet SQL ── --}}
                    @elseif($activeTab === 'structure')
                        <div class="overflow-auto flex-1 p-4">
                            <div class="mb-3 flex items-center gap-2 text-xs text-gray-500">
                                <span class="px-2 py-0.5 rounded bg-red-100 text-red-700 font-medium">REQUIRED</span>
                                <span
                                    class="px-2 py-0.5 rounded bg-green-100 text-green-700 font-medium">NULLABLE</span>
                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-medium">PRI /
                                    UNI</span>
                                <span class="ml-auto text-gray-400">{{ count($tableColumns) }} colonnes</span>
                            </div>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        <th
                                            class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                            Colonne</th>
                                        <th
                                            class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                            Type</th>
                                        <th
                                            class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                            Contrainte</th>
                                        <th
                                            class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                            Clé</th>
                                        <th
                                            class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                            Défaut</th>
                                        <th
                                            class="px-3 py-2 text-left text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                            Extra</th>
                                        <th class="px-3 py-2 border-b border-gray-200 dark:border-gray-700 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tableColumns as $col)
                                        @php
                                            $protectedCols = ['id', 'created_at', 'updated_at', 'deleted_at'];
                                            $isRequired =
                                                $col->Null === 'NO' &&
                                                $col->Default === null &&
                                                $col->Extra !== 'auto_increment';
                                        @endphp
                                        <tr
                                            class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">

                                            {{-- Nom colonne --}}
                                            <td
                                                class="px-3 py-2 font-mono font-semibold text-violet-600 dark:text-violet-400">
                                                {{ $col->Field }}
                                                @if (in_array($col->Field, $protectedCols))
                                                    <span class="text-gray-300 text-xs ml-1">🔒</span>
                                                @endif
                                            </td>

                                            {{-- Type --}}
                                            <td class="px-3 py-2 font-mono text-blue-600 dark:text-blue-400">
                                                {{ $col->Type }}
                                            </td>

                                            {{-- Contrainte Required / Nullable --}}
                                            <td class="px-3 py-2">
                                                @if ($col->Extra === 'auto_increment')
                                                    <span
                                                        class="text-xs px-2 py-0.5 rounded bg-purple-100 text-purple-700 font-medium">AUTO</span>
                                                @elseif($isRequired)
                                                    <span
                                                        class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 font-medium">REQUIRED</span>
                                                @else
                                                    <span
                                                        class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700 font-medium">NULLABLE</span>
                                                @endif
                                            </td>

                                            {{-- Clé --}}
                                            <td class="px-3 py-2">
                                                @if ($col->Key === 'PRI')
                                                    <span
                                                        class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-medium">PRI</span>
                                                @elseif($col->Key === 'UNI')
                                                    <span
                                                        class="text-xs px-2 py-0.5 rounded bg-sky-100 text-sky-700 font-medium">UNI</span>
                                                @elseif($col->Key === 'MUL')
                                                    <span
                                                        class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-medium">FK</span>
                                                @endif
                                            </td>

                                            {{-- Défaut --}}
                                            <td class="px-3 py-2 text-gray-500 italic font-mono text-xs">
                                                @if ($col->Default === null)
                                                    <span class="text-gray-300">NULL</span>
                                                @else
                                                    {{ $col->Default }}
                                                @endif
                                            </td>

                                            {{-- Extra --}}
                                            <td class="px-3 py-2 text-gray-400 text-xs">{{ $col->Extra }}</td>

                                            {{-- Supprimer --}}
                                            <td class="px-3 py-2">
                                                @if (!in_array($col->Field, $protectedCols))
                                                    <button wire:click="dropColumn('{{ $col->Field }}')"
                                                        wire:confirm="Supprimer la colonne `{{ $col->Field }}` ?"
                                                        class="text-danger-400 hover:text-danger-600 transition"
                                                        title="Supprimer">
                                                        <x-heroicon-o-trash class="w-4 h-4" />
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- ── Modal confirmation suppression ligne ── --}}
        <div x-show="confirmDrop !== null" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 max-w-sm w-full mx-4">
                <div class="flex items-center gap-3 mb-4">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-500" />
                    <h3 class="font-semibold text-gray-900 dark:text-white">Supprimer cette ligne ?</h3>
                </div>
                <p class="text-sm text-gray-500 mb-6">Cette action est irréversible.</p>
                <div class="flex gap-3">
                    <button @click="confirmDrop = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm">Annuler</button>
                    <button @click="$wire.deleteRow(confirmDrop); confirmDrop = null"
                        class="flex-1 px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-sm font-medium">Supprimer</button>
                </div>
            </div>
        </div>

        {{-- ── Modal confirmation suppression table ── --}}
        <div x-show="confirmDropTable !== null" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 max-w-sm w-full mx-4">
                <div class="flex items-center gap-3 mb-4">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-500" />
                    <h3 class="font-semibold text-gray-900 dark:text-white">Supprimer la table ?</h3>
                </div>
                <p class="text-sm text-gray-500 mb-2">Table : <code x-text="confirmDropTable"
                        class="bg-gray-100 dark:bg-gray-800 px-1 rounded font-mono"></code></p>
                <p class="text-sm text-red-600 font-medium mb-6">⚠️ Toutes les données seront perdues définitivement.
                </p>
                <div class="flex gap-3">
                    <button @click="confirmDropTable = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm">Annuler</button>
                    <button @click="$wire.dropTable(confirmDropTable); confirmDropTable = null"
                        class="flex-1 px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-sm font-medium">Supprimer
                        définitivement</button>
                </div>
            </div>
        </div>

    </div>{{-- fin x-data global --}}

</x-filament-panels::page>
