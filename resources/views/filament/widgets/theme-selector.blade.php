<div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            <x-heroicon-o-sun class="w-5 h-5 text-yellow-500" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Thème:</span>
            <select 
                wire:model.live="theme"
                class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="light">Clair</option>
                <option value="dark">Sombre</option>
                <option value="system">Système</option>
            </select>
        </div>
        <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>
        <div class="flex items-center gap-2">
            <x-heroicon-o-moon class="w-5 h-5 text-indigo-500" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Mode:</span>
            <select 
                wire:model.live="mode"
                class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="light">Clair</option>
                <option value="dark">Sombre</option>
            </select>
        </div>
    </div>
    <x-filament::button 
        wire:click="saveTheme"
        size="sm"
    >
        Sauvegarder
    </x-filament::button>
</div>
