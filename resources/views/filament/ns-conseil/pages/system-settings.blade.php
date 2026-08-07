<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">Écrans d'erreur</x-slot>
        <x-slot name="description">
            Active ou désactive l'affichage des écrans d'erreur détaillés (Ignition / stack trace).
            En production, laissez cette option <strong>désactivée</strong> pour ne pas exposer
            d'informations sensibles aux utilisateurs.
        </x-slot>

        <div class="flex items-center gap-6">
            {{-- Toggle --}}
            <label class="relative inline-flex cursor-pointer items-center gap-3">
                <div class="relative">
                    <input
                        type="checkbox"
                        wire:model.live="debugMode"
                        class="sr-only peer"
                        id="debug-toggle"
                    />
                    <div class="h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-danger-500
                                peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-danger-400
                                transition-colors duration-200 dark:bg-gray-700">
                    </div>
                    <div class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow
                                transition-transform duration-200
                                peer-checked:translate-x-5">
                    </div>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    @if ($debugMode)
                        <span class="text-danger-600 font-semibold">Activés</span>
                        — les erreurs sont visibles par tous
                    @else
                        <span class="text-success-600 font-semibold">Désactivés</span>
                        — les erreurs sont masquées
                    @endif
                </span>
            </label>
        </div>

        @if ($debugMode)
        <div class="mt-4 rounded-lg border border-danger-200 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-950">
            <div class="flex items-start gap-3">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-danger-600 mt-0.5" />
                <p class="text-sm text-danger-700 dark:text-danger-400">
                    <strong>Attention :</strong> les écrans d'erreur détaillés sont actuellement
                    <strong>activés</strong>. Cela peut exposer des données sensibles (chemins de
                    fichiers, requêtes SQL, variables d'environnement) à n'importe quel utilisateur.
                    Désactivez cette option dès la fin du débogage.
                </p>
            </div>
        </div>
        @endif

        <div class="mt-5 flex items-center gap-4">
            <button
                type="button"
                wire:click="saveDebugMode"
                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium shadow-sm
                       transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2
                       {{ $debugMode
                            ? 'bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500'
                            : 'bg-success-600 text-white hover:bg-success-700 focus:ring-success-500' }}"
            >
                <x-heroicon-o-check class="mr-1.5 h-4 w-4" />
                Enregistrer
            </button>

            <span class="text-xs text-gray-500 dark:text-gray-400">
                La valeur est écrite dans <code class="font-mono">.env</code>
                et prend effet immédiatement (cache config vidé).
            </span>
        </div>
    </x-filament::section>

</x-filament-panels::page>
