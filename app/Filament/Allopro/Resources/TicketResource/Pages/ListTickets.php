<?php
namespace App\Filament\Allopro\Resources\TicketResource\Pages;

use App\Enums\NiveauPriorite;
use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau ticket')
                ->icon('heroicon-o-plus')
                ->visible(fn() => auth()->user()?->hasAnyRole(['operateur_n1', 'responsable_plateau'])),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(Ticket::count()),

            'actifs' => Tab::make('Actifs')
                ->badge(Ticket::actifs()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->actifs()),

            'urgents' => Tab::make('🚨 Urgents')
                ->badge(Ticket::urgents()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->urgents()),

            'en_retard' => Tab::make('⏰ En retard')
                ->badge(Ticket::enRetard()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->enRetard()),

            'sans_artisan' => Tab::make('Sans artisan')
                ->badge(Ticket::sansArtisan()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->sansArtisan()),

            'bloquants' => Tab::make('Bloquants')
                ->badge(Ticket::bloquants()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->bloquants()),

            'du_jour' => Tab::make("Aujourd'hui")
                ->badge(Ticket::duJour()->count())
                ->modifyQueryUsing(fn($q) => $q->duJour()),

            'clotures' => Tab::make('Clôturés')
                ->badge(Ticket::clotures()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($q) => $q->clotures()),
        ];
    }
}
