<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Appel;
use App\Models\Prospect;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProspectionKpiWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('teleprospecteur')
                || $user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $isTp = $user->hasRoleCache('teleprospecteur');

        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        // Appels filtrés par téléprospecteur si rôle TP
        $appelsQuery = Appel::query()
            ->where('appelable_type', Prospect::class)
            ->when($isTp, fn ($q) => $q->where('user_id', $user->id));

        $appelsJour = (clone $appelsQuery)->whereDate('date_heure', today())->count();
        $appelsSemaine = (clone $appelsQuery)
            ->whereBetween('date_heure', [$debutSemaine, $finSemaine])
            ->count();

        // CSE joints = statuts STD_Joint ou au-delà
        $cseJoints = (clone $appelsQuery)
            ->whereBetween('date_heure', [$debutSemaine, $finSemaine])
            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
            ->count();

        // RDV QF validés cette semaine
        $prospectQuery = Prospect::query()
            ->when($isTp, fn ($q) => $q->where('teleprospecteur_id', $user->id));

        $rdvQf = (clone $prospectQuery)
            ->where('statut', ProspectStatut::QF->value)
            ->whereBetween('qf_valide_at', [$debutSemaine, $finSemaine])
            ->count();

        // Taux de conversion
        $tauxConversion = $appelsSemaine > 0
            ? round(($cseJoints / $appelsSemaine) * 100, 1)
            : 0;

        // Rappels du jour
        $rappelsDuJour = (clone $prospectQuery)
            ->whereDate('rappel_planifie_at', today())
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->count();

        // Base restante à contacter
        $baseRestante = (clone $prospectQuery)
            ->where('statut', ProspectStatut::AC->value)
            ->count();

        return [
            Stat::make('Appels du jour', $appelsJour)
                ->description("{$appelsSemaine} cette semaine")
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary'),

            Stat::make('CSE joints', $cseJoints)
                ->description("Taux : {$tauxConversion}%")
                ->icon('heroicon-o-user-group')
                ->color($tauxConversion >= 20 ? 'success' : 'warning'),

            Stat::make('RDV QF validés', $rdvQf)
                ->description('Cette semaine')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Rappels du jour', $rappelsDuJour)
                ->description("{$baseRestante} fiches AC restantes")
                ->icon('heroicon-o-clock')
                ->color($rappelsDuJour > 0 ? 'warning' : 'gray'),
        ];
    }
}
