<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\Client;
use App\Models\Opportunite;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class GlobalStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user
            && ($user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $aujourdhui = now()->startOfDay();
        $cetteSemaine = now()->startOfWeek();
        $ceMois = now()->startOfMonth();

        return [
            // Prospects
            Stat::make('Prospects', Prospect::count())
                ->description(Prospect::whereMonth('created_at', now()->month)->count().' ce mois')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info')
                ->chart([7, 12, 10, 14, 15, 18, 20]),

            // Partenaires
            Stat::make('Partenaires', Partenaire::count())
                ->description(Partenaire::whereMonth('created_at', now()->month)->count().' ce mois')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('primary')
                ->chart([5, 8, 12, 10, 15, 18, 22]),

            // Clients
            Stat::make('Clients', Client::count())
                ->description(Client::whereMonth('created_at', now()->month)->count().' ce mois')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart([10, 15, 20, 25, 30, 35, 40]),

            // Opportunités
            Stat::make('Opportunités', Opportunite::actives()->count())
                ->description(Opportunite::whereMonth('created_at', now()->month)->count().' nouvelles')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('warning')
                ->chart([3, 5, 8, 10, 12, 15, 18]),

            // RDV du jour
            Stat::make('RDV aujourd\'hui', RendezVous::duJour()->count())
                ->description(RendezVous::realises()->duJour()->count().' réalisés')
                ->descriptionIcon('heroicon-o-calendar')
                ->color(RendezVous::duJour()->count() > 0 ? 'primary' : 'gray'),

            // RDV de la semaine
            Stat::make('RDV semaine', RendezVous::deLaSemaine()->count())
                ->description(RendezVous::realises()->deLaSemaine()->count().' réalisés')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color(RendezVous::deLaSemaine()->count() > 0 ? 'success' : 'gray'),
        ];
    }
}
