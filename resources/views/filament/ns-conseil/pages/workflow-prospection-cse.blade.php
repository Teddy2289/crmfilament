<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with workflow selector -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Logigramme de prospection CSE
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Visualisation et gestion des workflows de prospection
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="window.open('/super-admin/workflow-groupes', '_blank')">
                        <x-heroicon-o-cog class="w-4 h-4 mr-2" />
                        Gérer les workflows
                    </x-filament::button>
                    <a 
                        href="{{ asset('docs/aopiacrm/Workflow_prospection_CSE_v2.html') }}"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        <x-heroicon-o-document class="w-4 h-4 mr-2" />
                        Voir l'original
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Workflow actif :
                </label>
                <select 
                    wire:model.live="selectedWorkflowGroupeId"
                    class="fi-input flex-1 max-w-md"
                >
                    @foreach($this->workflowGroupes as $groupe)
                        <option value="{{ $groupe->id }}" {{ $selectedWorkflowGroupeId == $groupe->id ? 'selected' : '' }}>
                            {{ $groupe->label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Workflow visualization - Logigramme style -->
        @if($selectedGroupe)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $selectedGroupe->label }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Code: {{ $selectedGroupe->code }} | {{ $workflowSteps->count() }} étapes
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/20">
                            Actif
                        </span>
                    </div>
                </div>

                <!-- Workflow steps visualization - Logigramme style -->
                @if($workflowSteps->count() > 0)
                    <div class="space-y-6">
                        @foreach($workflowSteps as $index => $step)
                            {{-- Step card --}}
                            <div 
                                class="relative border-2 rounded-xl p-5 {{ $step->actif ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 opacity-60' }} transition-all hover:shadow-md"
                            >
                                {{-- Step header --}}
                                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg {{ $step->actif ? 'bg-blue-500 text-white' : 'bg-gray-400 text-white' }} font-bold text-lg">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-900 dark:text-gray-100 text-lg">
                                                {{ $step->label }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $this->getStepTypeColor($step->type) }}">
                                                {{ $this->getStepTypes[$step->type] ?? $step->type }}
                                            </span>
                                            @if(!$step->actif)
                                                <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-400">
                                                    Inactif
                                                </span>
                                            @endif
                                        </div>
                                        @if($step->code)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
                                                {{ $step->code }}
                                            </p>
                                        @endif
                                    </div>
                                    
                                    {{-- Actions --}}
                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="toggleStepActif({{ $step->id }})"
                                            class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                            title="{{ $step->actif ? 'Désactiver' : 'Activer' }}"
                                        >
                                            @if($step->actif)
                                                <x-heroicon-o-eye class="w-5 h-5 text-green-600 dark:text-green-400" />
                                            @else
                                                <x-heroicon-o-eye-slash class="w-5 h-5 text-gray-400" />
                                            @endif
                                        </button>
                                        <a 
                                            href="/super-admin/workflow-steps/{{ $step->id }}/edit"
                                            target="_blank"
                                            class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                            title="Modifier"
                                        >
                                            <x-heroicon-o-pencil class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </a>
                                    </div>
                                </div>

                                {{-- Step content/branches --}}
                                @if($step->config && count($step->config) > 0)
                                    <div class="space-y-3">
                                        {{-- If step has branches, show them --}}
                                        @if(isset($step->config['branches']))
                                            <div class="grid {{ count($step->config['branches']) == 2 ? 'grid-cols-2' : 'grid-cols-3' }} gap-3">
                                                @foreach($step->config['branches'] as $branch)
                                                    <div class="rounded-lg p-4 border-2 {{ $this->getBranchColor($branch) }}">
                                                        <div class="text-xs font-bold uppercase tracking-wider mb-2 {{ $this->getBranchTextColor($branch) }}">
                                                            {{ $this->getBranchLabel($branch) }}
                                                        </div>
                                                        <div class="font-medium {{ $this->getBranchContentColor($branch) }}">
                                                            {{ $step->config['branch_content'][$branch] ?? 'Action' }}
                                                        </div>
                                                        @if(isset($step->config['branch_detail'][$branch]))
                                                            <div class="text-xs mt-2 opacity-75 {{ $this->getBranchContentColor($branch) }}">
                                                                {{ $step->config['branch_detail'][$branch] }}
                                                            </div>
                                                        @endif
                                                        @if(isset($step->config['tag'][$branch]))
                                                            <div class="inline-block mt-2 px-2 py-0.5 text-xs font-mono rounded {{ $this->getTagColor($step->config['tag'][$branch]) }}">
                                                                {{ $step->config['tag'][$branch] }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- Simple step with config --}}
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($step->config as $key => $value)
                                                    @if(!is_array($value))
                                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                            <span class="font-medium">{{ $key }}:</span>
                                                            <span class="ml-1">{{ $value }}</span>
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Connector arrow --}}
                            @if($index < $workflowSteps->count() - 1)
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
                            Aucune étape configurée pour ce workflow
                        </p>
                        <a 
                            href="/super-admin/workflow-steps/create?workflow_groupe_id={{ $selectedGroupe->id }}"
                            target="_blank"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                            Ajouter une étape
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center">
                <x-heroicon-o-folder-open class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Aucun workflow disponible
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    Créez d'abord un groupe de workflow dans le panel administration
                </p>
                <a 
                    href="/super-admin/workflow-groupes/create"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                    Créer un workflow
                </a>
            </div>
        @endif

        <!-- Quick actions -->
        @if($selectedGroupe && $workflowSteps->count() > 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Actions rapides
                </h3>
                <div class="flex flex-wrap gap-3">
                    <a 
                        href="/super-admin/workflow-steps/create?workflow_groupe_id={{ $selectedGroupe->id }}"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                        Ajouter une étape
                    </a>
                    <a 
                        href="/super-admin/workflow-groupes/{{ $selectedGroupe->id }}/edit"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        <x-heroicon-o-pencil class="w-4 h-4 mr-2" />
                        Modifier le workflow
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
