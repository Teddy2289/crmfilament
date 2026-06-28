<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with workflow selector -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Éditeur de Workflow
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Configurez et personnalisez vos workflows de prospection
                    </p>
                </div>
                <x-filament::button wire:click="window.open('/super-admin/workflow-groupes', '_blank')">
                    <x-heroicon-o-cog class="w-4 h-4 mr-2" />
                    Gérer les workflows
                </x-filament::button>
            </div>

            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Workflow actif :
                </label>
                <select 
                    wire:model.live="selectedWorkflowGroupeId"
                    wire:change="selectWorkflowGroupe(selectedWorkflowGroupeId)"
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

        <!-- Workflow visualization -->
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

                <!-- Workflow steps visualization -->
                @if($workflowSteps->count() > 0)
                    <div class="space-y-4" x-data="{ draggedItem: null }">
                        @foreach($workflowSteps as $index => $step)
                            <div 
                                class="relative flex items-center gap-4 p-4 rounded-lg border-2 {{ $step->actif ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 opacity-60' }} transition-all hover:shadow-md"
                                draggable="true"
                                @if($index < $workflowSteps->count() - 1)
                                    x-on:dragstart="draggedItem = {{ $step->id }}"
                                    x-on:dragover.prevent
                                    x-on:drop="$wire.call('reorderSteps', [{{ $step->id }}, draggedItem]); draggedItem = null"
                                @endif
                            >
                                <!-- Order indicator -->
                                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full {{ $step->actif ? 'bg-blue-500 text-white' : 'bg-gray-400 text-white' }} font-semibold text-sm">
                                    {{ $index + 1 }}
                                </div>

                                <!-- Step content -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $step->label }}
                                        </span>
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $this->getStepTypeColor($step->type) }}">
                                            {{ $this->getStepTypes[$step->type] ?? $step->type }}
                                        </span>
                                        @if(!$step->actif)
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-400">
                                                Inactif
                                            </span>
                                        @endif
                                    </div>
                                    @if($step->code)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
                                            {{ $step->code }}
                                        </p>
                                    @endif
                                    @if($step->config && count($step->config) > 0)
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($step->config as $key => $value)
                                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ $key }}: {{ is_string($value) ? $value : json_encode($value) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Actions -->
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

                                <!-- Connector line -->
                                @if($index < $workflowSteps->count() - 1)
                                    <div class="absolute left-8 -bottom-4 w-0.5 h-4 bg-gray-300 dark:bg-gray-600"></div>
                                @endif
                            </div>
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

    @script
    <script>
        // Add drag and drop functionality
        document.addEventListener('alpine:init', () => {
            Alpine.data('workflowEditor', () => ({
                draggedItem: null,
                
                handleDragStart(id) {
                    this.draggedItem = id;
                },
                
                handleDragOver(event) {
                    event.preventDefault();
                },
                
                handleDrop(targetId) {
                    if (this.draggedItem && this.draggedItem !== targetId) {
                        @this.reorderSteps([targetId, this.draggedItem]);
                    }
                    this.draggedItem = null;
                }
            }));
        });
    </script>
    @endscript
</x-filament-panels::page>
