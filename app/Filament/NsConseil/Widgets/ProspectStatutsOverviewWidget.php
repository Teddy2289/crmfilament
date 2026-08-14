<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProspectStatutsOverviewWidget extends BaseWidget
{
    protected static ?string $heading = '📊 Distribution complète des statuts';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '120s';

    protected int|string|array $columnSpan = 'full';

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

        $statuts = ProspectStatut::cases();
        $stats = [];

        $colorMap = [
            'AC' => 'info',
            'STD_NR' => 'warning',
            'STD_Joint' => 'primary',
            'CSE_NR' => 'warning',
            'RP' => 'info',
            'RPC' => 'success',
            'KO' => 'danger',
            'QF' => 'success',
            'Répondeur' => 'gray',
            'NRP' => 'warning',
            'FAX' => 'gray',
            'SUPP' => 'gray',
            'CSE-NI' => 'danger',
            'RAPL-ELU' => 'info',
            'RAPL-STD' => 'info',
            'BLOC2' => 'danger',
            'NCSE+50' => 'gray',
        ];

        foreach ($statuts as $statut) {
            $query = Prospect::query()
                ->where('statut', $statut->value);

            if ($isTp) {
                $query->where('teleprospecteur_id', $user->id);
            }

            $count = $query->count();

            // Affiche que les statuts avec données
            if ($count > 0) {
                $description = $statut->description();
                $color = $colorMap[$statut->label()] ?? 'gray';
                $icon = 'heroicon-o-check-circle';

                $stats[] = Stat::make($statut->label(), $count)
                    ->description($description)
                    ->icon($icon)
                    ->color($color);
            }
        }

        return $stats ?: [
            Stat::make('Statuts', '—')
                ->description('Aucune donnée disponible')
                ->color('gray'),
        ];
    }
}
