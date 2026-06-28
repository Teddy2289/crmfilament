<div x-data="{
    currentView: '{{ $currentView }}',
    showSaveModal: false,
    saveForm: {
        name: '',
        type: '{{ $currentView }}',
        is_default: false
    }
}">
    <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-gray-700">Vue :</span>
        
        <select 
            x-model="currentView"
            @change="$wire.selectView(currentView)"
            class="rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
            @foreach($this->getViewSelectorOptions() as $key => $label)
                <option value="{{ $key }}" @if($key === $currentView) selected @endif>{{ $label }}</option>
            @endforeach
        </select>

        <button 
            @click="showSaveModal = true"
            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
        >
            Sauvegarder
        </button>
    </div>

    <!-- Modal de sauvegarde -->
    <div x-show="showSaveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
        <div class="w-96 rounded-lg bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900">Sauvegarder la vue</h3>
            
            <div class="mt-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nom de la vue</label>
                    <input 
                        type="text" 
                        x-model="saveForm.name"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Ma vue personnalisée"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Type de vue</label>
                    <select 
                        x-model="saveForm.type"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="list">Liste</option>
                        <option value="kanban">Kanban</option>
                    </select>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="saveForm.is_default" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Définir comme vue par défaut</span>
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button 
                    @click="showSaveModal = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Annuler
                </button>
                <button 
                    @click="$wire.saveView(saveForm); showSaveModal = false; saveForm.name = '';"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Sauvegarder
                </button>
            </div>
        </div>
    </div>
</div>
