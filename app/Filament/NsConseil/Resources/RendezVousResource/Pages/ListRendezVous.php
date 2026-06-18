<?php

// ── app/Filament/NsConseil/Resources/RendezVousResource/Pages/ListRendezVous.php

namespace App\Filament\NsConseil\Resources\RendezVousResource\Pages;

use App\Enums\RendezVousStatut;
use App\Filament\NsConseil\Resources\RendezVousResource;
use App\Models\RendezVous;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRendezVous extends ListRecords
{
    protected static string $resource = RendezVousResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        return [
            'tous' => Tab::make('Tous')
                ->badge(RendezVous::withoutTrashed()->count()),

            'a_venir' => Tab::make('À venir')
                ->badge(RendezVous::withoutTrashed()
                    ->whereIn('statut', [RendezVousStatut::Planifie->value, RendezVousStatut::Decale->value])
                    ->where('date_heure', '>=', now())
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(function (Builder $q) {
                    return $q->whereIn('statut', [RendezVousStatut::Planifie->value, RendezVousStatut::Decale->value])
                        ->where('date_heure', '>=', now());
                }),

            'aujourd_hui' => Tab::make("Aujourd'hui")
                ->badge(RendezVous::withoutTrashed()->whereDate('date_heure', today())->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(function (Builder $q) {
                    return $q->whereDate('date_heure', today());
                }),

            'realises' => Tab::make('Réalisés')
                ->badge(RendezVous::withoutTrashed()->where('statut', RendezVousStatut::Realise->value)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(function (Builder $q) {
                    return $q->where('statut', RendezVousStatut::Realise->value);
                }),

            'annules' => Tab::make('Annulés')
                ->badge(RendezVous::withoutTrashed()->where('statut', RendezVousStatut::Annule->value)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(function (Builder $q) {
                    return $q->where('statut', RendezVousStatut::Annule->value);
                }),

            'mes_rdv' => Tab::make('Mes RDV')
                ->modifyQueryUsing(function (Builder $q) use ($user) {
                    return $q->where(function (Builder $q) use ($user) {
                        $q->where('commercial_id', $user->id)
                            ->orWhere('teleprospecteur_id', $user->id);
                    });
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return RendezVous::query()->withoutTrashed();
    }
}
