<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardQuickActionsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $user = auth()->user();

        $prospects = Prospect::query()
            ->when($user?->hasRoleCache('teleprospecteur'), fn ($query) => $query->where('teleprospecteur_id', $user->id))
            ->when($user?->hasRoleCache('commercial'), fn ($query) => $query->where('commercial_id', $user->id));

        $aTraiter = (clone $prospects)
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->count();

        $rappels = (clone $prospects)
            ->whereDate('rappel_planifie_at', today())
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->count();
        $enRetard = (clone $prospects)
            ->whereNotNull('rappel_planifie_at')
            ->where('rappel_planifie_at', '<', now())
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->count();

        return [
            Stat::make("Priorités en retard", number_format($enRetard, 0, ",", " "))
                ->description("Traiter les rappels échus")
                ->descriptionIcon("heroicon-m-exclamation-triangle")
                ->icon("heroicon-o-clock")
                ->color($enRetard > 0 ? "danger" : "success")
                ->url(url("/ns-conseil/phoning-workflow")),
            Stat::make('Prospects à traiter', number_format($aTraiter, 0, ',', ' '))
                ->description('Ouvrir la liste prospects')
                ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                ->icon('heroicon-o-users')
                ->color('primary')
                ->url(url('/ns-conseil/prospects')),
            Stat::make('Rappels du jour', number_format($rappels, 0, ',', ' '))
                ->description('Voir les actions prioritaires')
                ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                ->icon('heroicon-o-bell-alert')
                ->color($rappels > 0 ? 'warning' : 'success')
                ->url(url('/ns-conseil/phoning-workflow')),
            Stat::make('File de phoning', 'Démarrer')
                ->description('Lancer le prochain appel')
                ->descriptionIcon('heroicon-m-phone')
                ->icon('heroicon-o-queue-list')
                ->color('success')
                ->url(url('/ns-conseil/phoning-workflow')),
            Stat::make("Calendrier", "Ouvrir")
                ->description("Rendez-vous et agenda")
                ->descriptionIcon("heroicon-m-calendar-days")
                ->icon("heroicon-o-calendar-days")
                ->color("info")
                ->url(url("/ns-conseil/calendar")),
            Stat::make('Ringover', 'Ouvrir')
                ->description('Journal et performance des appels')
                ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                ->icon('heroicon-o-phone')
                ->color('info')
                ->url(url('/ns-conseil/ringover-dashboard')),
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRoleCache('admin')
            || $user->hasRoleCache('superviseur')
            || $user->hasRoleCache('teleprospecteur')
            || $user->hasRoleCache('commercial')
            || $user->isSuperAdmin());
    }
}
