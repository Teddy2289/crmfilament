// Keyboard Shortcuts for Filament CRM
document.addEventListener('DOMContentLoaded', function() {
    const shortcuts = {
        'mod+k': () => {
            // Open command palette
            const commandPalette = document.querySelector('[data-command-palette]');
            if (commandPalette) {
                commandPalette.click();
            }
        },
        'mod+/': () => {
            // Show shortcuts modal
            showShortcutsModal();
        },
        'mod+b': () => {
            // Toggle sidebar
            const sidebar = document.querySelector('[data-sidebar]');
            if (sidebar) {
                sidebar.classList.toggle('hidden');
            }
        },
        'mod+d': () => {
            // Toggle dark mode
            const darkModeToggle = document.querySelector('[data-dark-mode-toggle]');
            if (darkModeToggle) {
                darkModeToggle.click();
            }
        },
        'escape': () => {
            // Close modal
            const modal = document.querySelector('[data-modal]');
            if (modal) {
                const closeButton = modal.querySelector('[data-close-modal]');
                if (closeButton) {
                    closeButton.click();
                }
            }
        },
        'mod+s': (e) => {
            // Save form
            const saveButton = document.querySelector('[data-save-button]');
            if (saveButton) {
                e.preventDefault();
                saveButton.click();
            }
        },
        'mod+n': (e) => {
            // Create new
            const createButton = document.querySelector('[data-create-button]');
            if (createButton) {
                e.preventDefault();
                createButton.click();
            }
        },
        'mod+f': () => {
            // Focus filter
            const filterInput = document.querySelector('[data-filter-input]');
            if (filterInput) {
                filterInput.focus();
            }
        },
    };

    function showShortcutsModal() {
        // Create modal if it doesn't exist
        let modal = document.getElementById('shortcuts-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'shortcuts-modal';
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Raccourcis Clavier</h2>
                        <button onclick="document.getElementById('shortcuts-modal').remove()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Global</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Command Palette</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">⌘K</kbd></div>
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Afficher raccourcis</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">⌘/</kbd></div>
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Barre latérale</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">⌘B</kbd></div>
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Mode sombre</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">⌘D</kbd></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Formulaires</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Sauvegarder</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">⌘S</kbd></div>
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Nouveau</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">⌘N</kbd></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Tables</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Filtrer</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">⌘F</kbd></div>
                                <div class="flex justify-between"><span class="text-gray-700 dark:text-gray-300">Fermer</span><kbd class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">Esc</kbd></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        modal.classList.remove('hidden');
    }

    // Keyboard event listener
    document.addEventListener('keydown', function(e) {
        const key = e.key.toLowerCase();
        const modifiers = [];
        
        if (e.ctrlKey || e.metaKey) modifiers.push('mod');
        if (e.shiftKey) modifiers.push('shift');
        if (e.altKey) modifiers.push('alt');
        
        const shortcutKey = modifiers.length > 0 ? modifiers.join('+') + '+' + key : key;
        
        if (shortcuts[shortcutKey]) {
            shortcuts[shortcutKey](e);
        }
    });
});
