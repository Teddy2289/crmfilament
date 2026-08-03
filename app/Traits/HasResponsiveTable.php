<?php

namespace App\Traits;

use Filament\Tables\Table;

trait HasResponsiveTable
{
    /**
     * Configure la table pour être responsive
     */
    protected function configureResponsiveTable(Table $table): Table
    {
        return $table
            ->columns($this->getResponsiveColumns())
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Définit les colonnes avec leur visibilité responsive
     * À surcharger dans les classes filles pour personnaliser
     */
    protected function getResponsiveColumns(): array
    {
        return [];
    }

    /**
     * Configure le responsive pour une colonne de texte
     */
    protected function makeResponsiveTextColumn(string $name, string $label): \Filament\Tables\Columns\TextColumn
    {
        return \Filament\Tables\Columns\TextColumn::make($name)
            ->label($label)
            ->toggleable(isToggledHiddenByDefault: false)
            ->searchable();
    }

    /**
     * Configure le responsive pour une colonne cachée sur mobile
     */
    protected function makeHiddenOnMobileColumn(string $name, string $label): \Filament\Tables\Columns\TextColumn
    {
        return \Filament\Tables\Columns\TextColumn::make($name)
            ->label($label)
            ->toggleable(isToggledHiddenByDefault: true)
            ->visible(fn (): bool => ! $this->isMobile());
    }

    /**
     * Détermine si l'utilisateur est sur mobile
     */
    protected function isMobile(): bool
    {
        return str_contains(request()->userAgent() ?? '', 'Mobile')
            || str_contains(request()->userAgent() ?? '', 'Android')
            || str_contains(request()->userAgent() ?? '', 'iPhone');
    }
}
