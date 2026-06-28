<?php

namespace App\Filament\NsConseil\Pages;

use App\Services\Crm\SearchAndRelationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GlobalSearch extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Recherche globale';

    protected static ?string $navigationGroup = 'Recherche';

    protected static string $view = 'filament.ns-conseil.pages.global-search';

    public ?string $searchQuery = null;

    public array $results = [];

    protected SearchAndRelationService $searchService;

    public function mount(): void
    {
        $this->form->fill();
        $this->searchService = new SearchAndRelationService();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('searchQuery')
                    ->label('Rechercher (téléphone, email, nom, ref_client...)')
                    ->placeholder('Ex: 0612345678, jean.dupont@email.com, CLI-2024-001')
                    ->live()
                    ->debounce(500)
                    ->afterStateUpdated(fn () => $this->search()),
            ])
            ->statePath('data');
    }

    public function search(): void
    {
        $query = $this->form->getState()['searchQuery'] ?? null;

        if (empty($query) || strlen($query) < 3) {
            $this->results = [];
            return;
        }

        $this->results = $this->searchService->searchGlobal($query);
    }

    public function linkEntities(string $fromType, int $fromId, string $toType, int $toId): void
    {
        // Logique de liaison entre entités
        // À implémenter selon les besoins métier
        Notification::make()
            ->title('Liaison créée')
            ->success()
            ->send();
    }
}
