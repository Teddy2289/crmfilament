<?php

namespace App\Traits;

trait HasResponsiveForm
{
    /**
     * Configure le formulaire pour être responsive
     */
    protected function configureResponsiveForm(): array
    {
        return [
            'columns' => $this->getResponsiveColumns(),
            'grid' => $this->getResponsiveGrid(),
        ];
    }

    /**
     * Définit le nombre de colonnes selon la taille d'écran
     */
    protected function getResponsiveColumns(): int|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 3,
            '2xl' => 3,
        ];
    }

    /**
     * Définit la grille responsive
     */
    protected function getResponsiveGrid(): array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 3,
            '2xl' => 3,
        ];
    }

    /**
     * Crée une section responsive
     */
    protected function makeResponsiveSection(string $heading, array $schema, ?string $icon = null): \Filament\Forms\Components\Section
    {
        return \Filament\Forms\Components\Section::make($heading)
            ->icon($icon)
            ->columns($this->getResponsiveColumns())
            ->schema($schema);
    }

    /**
     * Crée un groupe responsive
     */
    protected function makeResponsiveGroup(array $schema): \Filament\Forms\Components\Group
    {
        return \Filament\Forms\Components\Group::make($schema)
            ->columns($this->getResponsiveColumns());
    }
}
