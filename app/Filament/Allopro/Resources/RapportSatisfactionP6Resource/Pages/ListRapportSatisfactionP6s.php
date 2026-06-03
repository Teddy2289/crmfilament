<?php

namespace App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages;

use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource;
use App\Models\RapportSatisfactionP6;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRapportSatisfactionP6s extends ListRecords
{
    protected static string $resource = RapportSatisfactionP6Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Saisir rapport P6')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous tous')
                ->badge(RapportSatisfactionP6::count()),

            'a_appeler' => Tab::make('📞 À appeler J+1')
                ->badge($this->getTicketsASappeler())
                ->badgeColor($this->getTicketsASappeler() > 0 ? 'danger' : 'gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('ticket', function (Builder $t) {
                    $t->where('statut', TicketStatut::InterventionRealisee->value)
                      ->whereDoesntHave('rapportSatisfaction');
                })),

            'satisfaits' => Tab::make('✅ Satisfaits')
                ->badge(RapportSatisfactionP6::query()->satisfaits()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->satisfaits()),

            'suivi_qualite' => Tab::make('⚠️ Suivi qualité')
                ->badge($this->getSuiviQualiteCount())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereBetween('note_nps', [6, 7])),

            'detracteurs' => Tab::make('🚨 Détracteurs')
                ->badge(RapportSatisfactionP6::query()->detracteurs()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->detracteurs()),

            'sans_feedback' => Tab::make('Feedback manquant')
                ->badge(RapportSatisfactionP6::query()->where('feedback_artisan', false)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('feedback_artisan', false)),

            'du_mois' => Tab::make('Ce mois')
                ->badge(RapportSatisfactionP6::duMois()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->duMois()),
        ];
    }

    private function getTicketsASappeler(): int
    {
        return Ticket::query()
            ->where('statut', TicketStatut::InterventionRealisee->value)
            ->doesntHave('rapportSatisfaction')
            ->count();
    }

    private function getSuiviQualiteCount(): int
    {
        return RapportSatisfactionP6::query()
            ->whereBetween('note_nps', [6, 7])
            ->count();
    }
}
