<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProspectStatutsOverviewWidget extends BaseWidget
{
    protected ?string $heading = '📊 Distribution complète des statuts';

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

        // Couleurs et icônes plus appropriées par statut
        $statusConfig = [
            'AC' => ['color' => 'info', 'icon' => 'heroicon-o-document'],
            'STD_NR' => ['color' => 'warning', 'icon' => 'heroicon-o-phone-missed-call'],
            'STD_Joint' => ['color' => 'primary', 'icon' => 'heroicon-o-phone'],
            'CSE_NR' => ['color' => 'warning', 'icon' => 'heroicon-o-user-minus'],
            'RP' => ['color' => 'info', 'icon' => 'heroicon-o-calendar-days'],
            'RPC' => ['color' => 'success', 'icon' => 'heroicon-o-check-circle'],
            'KO' => ['color' => 'danger', 'icon' => 'heroicon-o-x-circle'],
            'QF' => ['color' => 'success', 'icon' => 'heroicon-o-star'],
            'Répondeur' => ['color' => 'warning', 'icon' => 'heroicon-o-volume-2'],
            'NRP' => ['color' => 'warning', 'icon' => 'heroicon-o-exclamation-circle'],
            'FAX' => ['color' => 'gray', 'icon' => 'heroicon-o-document-text'],
            'SUPP' => ['color' => 'gray', 'icon' => 'heroicon-o-trash'],
            'CSE-NI' => ['color' => 'danger', 'icon' => 'heroicon-o-hand-raised'],
            'RAPL-ELU' => ['color' => 'info', 'icon' => 'heroicon-o-sparkles'],
            'RAPL-STD' => ['color' => 'info', 'icon' => 'heroicon-o-arrow-path'],
            'BLOC2' => ['color' => 'danger', 'icon' => 'heroicon-o-lock-closed'],
            'NCSE+50' => ['color' => 'gray', 'icon' => 'heroicon-o-users'],
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
                $label = $statut->label();
                $description = $statut->description();
                $config = $statusConfig[$label] ?? ['color' => 'gray', 'icon' => 'heroicon-o-check-circle'];

                $stats[] = Stat::make($label, $count)
                    ->description($description)
                    ->icon($config['icon'])
                    ->color($config['color']);
            }
        }

        return $stats ?: [
            Stat::make('Statuts', '—')
                ->description('Aucune donnée disponible')
                ->color('gray'),
        ];
    }
}
