<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupKeyboardShortcuts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-keyboard-shortcuts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup default keyboard shortcuts for the CRM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up keyboard shortcuts...');
        
        // This command can be used to publish keyboard shortcut configuration
        // The actual shortcuts are implemented via JavaScript in the Filament panel
        
        $this->info('Keyboard shortcuts are configured in the Filament panel provider.');
        $this->info('Default shortcuts:');
        $this->table(['Shortcut', 'Action'], [
            ['Ctrl/Cmd + N', 'Nouveau (Create)'],
            ['Ctrl/Cmd + S', 'Sauvegarder (Save)'],
            ['Ctrl/Cmd + F', 'Rechercher (Search)'],
            ['Ctrl/Cmd + K', 'Command Palette'],
            ['Esc', 'Fermer modal/dialog'],
            ['Ctrl/Cmd + /', 'Afficher les raccourcis'],
            ['Ctrl/Cmd + B', 'Barre latérale (Sidebar)'],
            ['Ctrl/Cmd + D', 'Mode sombre (Dark mode)'],
        ]);
        
        return Command::SUCCESS;
    }
}
