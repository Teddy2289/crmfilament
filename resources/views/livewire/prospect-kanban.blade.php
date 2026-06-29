<div x-data="kanbanBoard()" x-init="initBoard()" class="kanban-board">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Pipeline Prospects</h2>
        <div class="flex items-center gap-2">
            <button @click="loadData()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Rafraîchir
                </span>
            </button>
        </div>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($columns as $column)
        <div class="kanban-column flex-shrink-0 w-80 bg-gray-100 rounded-lg p-4" data-column="{{ $column['id'] }}">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-{{ $column['color'] }}-100 flex items-center justify-center">
                    <x-heroicon-{{ $column['icon'] }} class="w-5 h-5 text-{{ $column['color'] }}-600" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $column['label'] }}</h3>
                    <span class="text-sm text-gray-500">{{ count($prospects[$column['id']]['cards'] ?? []) }} prospects</span>
                </div>
            </div>

            <div class="kanban-cards space-y-3 min-h-[200px]" 
                 data-column="{{ $column['id'] }}"
                 @dragover.prevent
                 @drop="handleDrop($event, '{{ $column['id'] }}')">
                @if(isset($prospects[$column['id']]['cards']))
                    @foreach($prospects[$column['id']]['cards'] as $card)
                    <div class="kanban-card bg-white rounded-lg shadow-sm p-4 cursor-move hover:shadow-md transition"
                         draggable="true"
                         @dragstart="handleDragStart($event, {{ $card['id'] }}, '{{ $column['id'] }}')"
                         @dragend="handleDragEnd($event)"
                         data-card-id="{{ $card['id'] }}">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $card['nom'] }} {{ $card['prenom'] }}</h4>
                                <p class="text-sm text-gray-500">{{ $card['entreprise'] }}</p>
                            </div>
                            <a href="{{ route('filament.nsConseil.resources.prospects.edit', ['record' => $card['id']]) }}" 
                               class="text-indigo-600 hover:text-indigo-800">
                                <x-heroicon-pencil class="w-4 h-4" />
                            </a>
                        </div>
                        <div class="space-y-1 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <x-heroicon-phone class="w-4 h-4" />
                                <span>{{ $card['telephone'] }}</span>
                            </div>
                            @if($card['email'] && $card['email'] !== 'N/A')
                            <div class="flex items-center gap-2">
                                <x-heroicon-envelope class="w-4 h-4" />
                                <span class="truncate">{{ $card['email'] }}</span>
                            </div>
                            @endif
                            <div class="flex items-center gap-2">
                                <x-heroicon-building-office class="w-4 h-4" />
                                <span>{{ $card['entite'] }}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <span class="text-xs text-gray-400">Créé le {{ $card['date_creation'] }}</span>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

@script
<script>
function kanbanBoard() {
    return {
        draggedCardId: null,
        sourceColumnId: null,

        initBoard() {
            this.$wire.on('prospect-updated', () => {
                this.$wire.loadData();
            });
        },

        handleDragStart(event, cardId, columnId) {
            this.draggedCardId = cardId;
            this.sourceColumnId = columnId;
            event.dataTransfer.effectAllowed = 'move';
            event.target.classList.add('opacity-50');
        },

        handleDragEnd(event) {
            event.target.classList.remove('opacity-50');
            this.draggedCardId = null;
            this.sourceColumnId = null;
        },

        handleDrop(event, targetColumnId) {
            event.preventDefault();
            
            if (this.draggedCardId && this.sourceColumnId !== targetColumnId) {
                this.$wire.updateStatus(this.draggedCardId, targetColumnId);
            }
        },

        loadData() {
            this.$wire.loadData();
        }
    };
}
</script>
@endscript

<style>
.kanban-board {
    min-height: calc(100vh - 200px);
}

.kanban-column {
    min-height: 400px;
}

.kanban-card {
    transition: all 0.2s ease;
}

.kanban-card:hover {
    transform: translateY(-2px);
}

.kanban-card.opacity-50 {
    opacity: 0.5;
}
</style>

