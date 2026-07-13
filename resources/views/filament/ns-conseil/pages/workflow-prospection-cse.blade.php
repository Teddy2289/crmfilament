<x-filament-panels::page>
    <div class="space-y-6">
        <!-- En-tête avec sélecteur de parcours -->
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Logigramme de prospection CSE
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Visualisation et gestion des parcours de prospection
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Parcours actif :
                </label>
                <select wire:model.live="selectedWorkflowGroupeId" class="fi-input flex-1 max-w-md">
                    @foreach ($this->workflowGroupes as $groupe)
                    <option value="{{ $groupe->id }}"
                        {{ $selectedWorkflowGroupeId == $groupe->id ? 'selected' : '' }}>
                        {{ $groupe->label }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Visualisation du parcours - style logigramme -->
        @if ($selectedGroupe)
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $selectedGroupe->label }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Code: {{ $selectedGroupe->code }} | {{ $workflowSteps->count() }} cas
                    </p>
                </div>
                <x-filament::badge color="success">Actif</x-filament::badge>
            </div>

            @if ($workflowSteps->count() > 0)
            <div class="space-y-6">
                @foreach ($workflowSteps as $index => $step)
                {{-- Carte de case (étape racine) --}}
                <div
                    class="relative border-2 rounded-xl p-5 {{ $step->actif ? 'border-primary-200 bg-primary-50/50 dark:border-primary-800 dark:bg-primary-900/10' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 opacity-60' }} transition-all hover:shadow-md">

                    {{-- Header de la case --}}
                    <div
                        class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <div
                            class="flex-shrink-0 mx-1 w-10 h-10 flex items-center justify-center rounded-lg {{ $step->actif ? 'bg-primary-500 text-white' : 'bg-gray-400 text-white' }} font-bold text-lg">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1 p-3">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 dark:text-gray-100 text-lg">
                                    {{ $step->label }}
                                </span>
                                <x-filament::badge :color="$this->getStepTypeColor($step->type)">
                                    {{ $this->getStepTypes[$step->type] ?? $step->type }}
                                </x-filament::badge>
                                @if (!$step->actif)
                                <x-filament::badge color="danger">Inactif</x-filament::badge>
                                @endif
                            </div>
                            @if ($step->code)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
                                {{ $step->code }}
                            </p>
                            @endif
                            @if ($step->config['description'] ?? null)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                {{ $step->config['description'] }}
                            </p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            <button wire:click="toggleStepActif({{ $step->id }})"
                                class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                title="{{ $step->actif ? 'Désactiver' : 'Activer' }}">
                                @if ($step->actif)
                                <x-heroicon-o-eye class="w-5 h-5 text-success-600 dark:text-success-400" />
                                @else
                                <x-heroicon-o-eye-slash class="w-5 h-5 text-gray-400" />
                                @endif
                            </button>
                            <a href="/super-admin/workflow-steps/{{ $step->id }}/edit" target="_blank"
                                class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                title="Modifier">
                                <x-heroicon-o-pencil class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            </a>
                        </div>
                    </div>

                    {{-- Tag de la case (si présent, hors branches) --}}
                    @if ($step->config['tag'] ?? null)
                    <div class="mb-3">
                        <x-filament::badge :color="$this->getTagColor($step->config['tag'])">
                            {{ $step->config['tag'] }}
                        </x-filament::badge>
                    </div>
                    @endif

                    {{-- Branches (étapes enfants) --}}
                    @if ($step->childSteps->isNotEmpty())
                    <div class="mt-3 p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
                            ↓ Issues possibles
                        </div>
                        <div class="grid {{ $this->getBranchesGridClass($step->childSteps->count()) }} gap-3">
                            @foreach ($step->childSteps as $branch)
                            @php
                            $color = $this->branchKeyFromCondition($branch->condition_label);
                            @endphp
                            <div
                                class="relative rounded-lg p-4 border-2 {{ !$branch->actif ? 'opacity-50' : '' }} border-{{ $color }}-300 bg-{{ $color }}-50">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-{{ $color }}-700">
                                        {{ $branch->condition_label ?: $branch->label }}
                                    </span>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <button wire:click="toggleStepActif({{ $branch->id }})"
                                            class="p-1 rounded hover:bg-black/5 dark:hover:bg-white/10 transition-colors"
                                            title="{{ $branch->actif ? 'Désactiver' : 'Activer' }}">
                                            @if ($branch->actif)
                                            <x-heroicon-o-eye class="w-4 h-4 text-success-600 dark:text-success-400" />
                                            @else
                                            <x-heroicon-o-eye-slash class="w-4 h-4 text-gray-400" />
                                            @endif
                                        </button>
                                        {{ ($this->editStepAction)(['step' => $branch->id]) }}
                                    </div>
                                </div>
                                <div class="font-medium text-{{ $color }}-900">
                                    {{ $branch->label }}
                                </div>
                                @if ($branch->config['description'] ?? null)
                                <div class="text-xs mt-2 opacity-75 text-{{ $color }}-900">
                                    {{ $branch->config['description'] }}
                                </div>
                                @endif
                                @if ($branch->config['tag'] ?? null)
                                <div class="mt-2">
                                    <x-filament::badge :color="$this->getTagColor($branch->config['tag'])">
                                        {{ $branch->config['tag'] }}
                                    </x-filament::badge>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Flèche de liaison entre cases --}}
                @if ($index < $workflowSteps->count() - 1)
                    <div class="flex justify-center py-2">
                        <div class="w-0.5 h-8 bg-gray-300 dark:bg-gray-600"></div>
                    </div>
                    @endif
                    @endforeach
            </div>
            @else
            <div class="text-center py-12">
                <x-heroicon-o-clipboard-document-list class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <p class="text-gray-500 dark:text-gray-400">
                    Aucune étape configurée pour ce parcours
                </p>
                <a href="/super-admin/workflow-steps/create?workflow_groupe_id={{ $selectedGroupe->id }}"
                    target="_blank"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                    Ajouter une étape
                </a>
            </div>
            @endif
        </div>
        @else
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center">
            <x-heroicon-o-folder-open class="w-16 h-16 mx-auto text-gray-400 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Aucun parcours disponible
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
                Créez d'abord un groupe de parcours dans le panneau d'administration
            </p>
            <a href="/super-admin/workflow-groupes/create" target="_blank"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                Créer un parcours
            </a>
        </div>
        @endif

        <!-- Actions rapides -->
        @if ($selectedGroupe && $workflowSteps->count() > 0)
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Actions rapides
            </h3>
            <div class="flex flex-wrap gap-3">
                <a href="/super-admin/workflow-steps/create?workflow_groupe_id={{ $selectedGroupe->id }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                    Ajouter une étape
                </a>
                <a href="/super-admin/workflow-groupes/{{ $selectedGroupe->id }}/edit" target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-pencil class="w-4 h-4 mr-2" />
                    Modifier le parcours
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- OBLIGATOIRE : sans cette ligne, editStepAction() ne s'affichera jamais --}}
    <x-filament-actions::modals />
</x-filament-panels::page>