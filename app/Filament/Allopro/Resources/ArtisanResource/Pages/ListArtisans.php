<?php
namespace App\Filament\Allopro\Resources\ArtisanResource\Pages;

use App\Filament\Allopro\Resources\ArtisanResource;
use App\Models\Artisan;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListArtisans extends ListRecords
{
    protected static string $resource = ArtisanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvel artisan')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(Artisan::count()),

            'actifs' => Tab::make('Actifs')
                ->badge(Artisan::actifs()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($q) => $q->actifs()),

            'en_attente' => Tab::make('En attente')
                ->badge(Artisan::enAttente()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->enAttente()),

            'suspendus' => Tab::make('Suspendus')
                ->badge(Artisan::suspendus()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->suspendus()),

            'sans_agenda' => Tab::make('Sans agenda')
                ->modifyQueryUsing(fn($q) => $q->where('agenda_disponibilites', false)->actifs()),
        ];
    }
}
