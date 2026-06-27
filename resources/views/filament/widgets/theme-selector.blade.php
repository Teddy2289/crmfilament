<div x-data="{
    theme: '{{ $theme }}',
    mode: '{{ $mode }}',
    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        if (this.mode === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" x-init="applyTheme()" @theme-changed.window="event.detail.theme && (theme = event.detail.theme); event.detail.mode && (mode = event.detail.mode); applyTheme()">
    <div class="flex items-center gap-2 p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Thème:</span>
            <select 
                x-model="theme" 
                @change="$wire.set('theme', $event.target.value)"
                class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="light">Clair</option>
                <option value="dark">Sombre</option>
                <option value="system">Système</option>
            </select>
        </div>
        <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Mode:</span>
            <select 
                x-model="mode" 
                @change="$wire.set('mode', $event.target.value)"
                class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="light">Clair</option>
                <option value="dark">Sombre</option>
            </select>
        </div>
    </div>
</div>
