<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Timeout API Ringover</x-slot>
        <x-slot name="description">Réglez le délai d'attente des requêtes vers l'API Ringover en secondes.</x-slot>

        <div class="grid gap-4 md:grid-cols-3 items-end">
            <div class="md:col-span-1">
                <label class="text-sm font-medium text-gray-700">Timeout</label>
                <input
                    type="number"
                    min="1"
                    wire:model.defer="ringoverTimeout"
                    class="pw-field-input mt-2 w-full"
                />
                <p class="text-xs text-gray-500 mt-1">Valeur en secondes utilisée par l'intégration Ringover.</p>
            </div>

            <div class="md:col-span-2 flex items-center gap-3">
                <button
                    type="button"
                    wire:click="saveRingoverTimeout"
                    class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700"
                >
                    Enregistrer
                </button>
                <span class="text-sm text-gray-500">La valeur est synchronisée dans le fichier <code class="font-mono">.env</code>.</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
