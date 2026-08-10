<?php

namespace App\Services;

class KeyboardShortcutService
{
    protected array $shortcuts = [];

    public function __construct()
    {
        $this->registerDefaultShortcuts();
    }

    protected function registerDefaultShortcuts(): void
    {
        $this->shortcuts = [
            'global' => [
                'mod+k' => [
                    'label' => 'Command Palette',
                    'action' => 'openCommandPalette',
                    'description' => 'Ouvrir la palette de commandes',
                ],
                'mod+/' => [
                    'label' => 'Afficher les raccourcis',
                    'action' => 'showShortcuts',
                    'description' => 'Afficher la liste des raccourcis clavier',
                ],
                'mod+b' => [
                    'label' => 'Barre latérale',
                    'action' => 'toggleSidebar',
                    'description' => 'Afficher/Masquer la barre latérale',
                ],
                'mod+d' => [
                    'label' => 'Mode sombre',
                    'action' => 'toggleDarkMode',
                    'description' => 'Basculer le mode sombre',
                ],
                'escape' => [
                    'label' => 'Fermer',
                    'action' => 'closeModal',
                    'description' => 'Fermer la modale ou le dialogue',
                ],
            ],
            'navigation' => [
                'mod+g' => [
                    'label' => 'Aller aux Gantt',
                    'action' => 'navigateToGantt',
                    'description' => 'Naviguer vers les diagrammes Gantt',
                ],
                'mod+r' => [
                    'label' => 'Aller aux Rapports',
                    'action' => 'navigateToReports',
                    'description' => 'Naviguer vers les rapports',
                ],
                'mod+i' => [
                    'label' => 'Aller aux Intégrations',
                    'action' => 'navigateToIntegrations',
                    'description' => 'Naviguer vers les intégrations',
                ],
                'mod+c' => [
                    'label' => 'Aller au Chat',
                    'action' => 'navigateToChat',
                    'description' => 'Naviguer vers le chat interne',
                ],
            ],
            'forms' => [
                'mod+s' => [
                    'label' => 'Sauvegarder',
                    'action' => 'saveForm',
                    'description' => 'Sauvegarder le formulaire',
                ],
                'mod+n' => [
                    'label' => 'Nouveau',
                    'action' => 'createNew',
                    'description' => 'Créer un nouvel enregistrement',
                ],
                'mod+e' => [
                    'label' => 'Éditer',
                    'action' => 'editRecord',
                    'description' => 'Éditer l\'enregistrement sélectionné',
                ],
                'mod+delete' => [
                    'label' => 'Supprimer',
                    'action' => 'deleteRecord',
                    'description' => 'Supprimer l\'enregistrement sélectionné',
                ],
            ],
            'tables' => [
                'mod+f' => [
                    'label' => 'Filtrer',
                    'action' => 'focusFilter',
                    'description' => 'Focus sur le filtre',
                ],
                'mod+arrowup' => [
                    'label' => 'Sélectionner précédent',
                    'action' => 'selectPrevious',
                    'description' => 'Sélectionner la ligne précédente',
                ],
                'mod+arrowdown' => [
                    'label' => 'Sélectionner suivant',
                    'action' => 'selectNext',
                    'description' => 'Sélectionner la ligne suivante',
                ],
                'mod+enter' => [
                    'label' => 'Ouvrir',
                    'action' => 'openRecord',
                    'description' => 'Ouvrir l\'enregistrement sélectionné',
                ],
            ],
        ];
    }

    public function getShortcuts(string $category = null): array
    {
        if ($category) {
            return $this->shortcuts[$category] ?? [];
        }
        return $this->shortcuts;
    }

    public function getFlatShortcuts(): array
    {
        $flat = [];
        foreach ($this->shortcuts as $category => $shortcuts) {
            foreach ($shortcuts as $key => $shortcut) {
                $flat[$key] = [
                    ...$shortcut,
                    'category' => $category,
                ];
            }
        }
        return $flat;
    }

    public function registerShortcut(string $category, string $key, array $config): void
    {
        if (!isset($this->shortcuts[$category])) {
            $this->shortcuts[$category] = [];
        }
        $this->shortcuts[$category][$key] = $config;
    }

    public function getShortcutForAction(string $action): ?array
    {
        foreach ($this->shortcuts as $category => $shortcuts) {
            foreach ($shortcuts as $key => $shortcut) {
                if ($shortcut['action'] === $action) {
                    return [
                        'key' => $key,
                        ...$shortcut,
                        'category' => $category,
                    ];
                }
            }
        }
        return null;
    }
}
