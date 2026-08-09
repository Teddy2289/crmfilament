<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\ActiviteTraitementWidget;
use App\Filament\NsConseil\Widgets\AnalyticsChartWidget;
use App\Filament\NsConseil\Widgets\CommercialAgendaWidget;
use App\Filament\NsConseil\Widgets\CommercialKpiWidget;
use App\Filament\NsConseil\Widgets\ConversionFunnelWidget;
use App\Filament\NsConseil\Widgets\DirectionDerniersPartenairesWidget;
use App\Filament\NsConseil\Widgets\DirectionKpiWidget;
use App\Filament\NsConseil\Widgets\DirectionRdvParDepartementChart;
use App\Filament\NsConseil\Widgets\FichesWordRecentesWidget;
use App\Filament\NsConseil\Widgets\GlobalStatsWidget;
use App\Filament\NsConseil\Widgets\MesPartenairesRecentWidget;
use App\Filament\NsConseil\Widgets\OpportunitesStatsWidget;
use App\Filament\NsConseil\Widgets\PlanningCommercialWidget;
use App\Filament\NsConseil\Widgets\PipelinePartenairesWidget;
use App\Filament\NsConseil\Widgets\ProspectionKpiWidget;
use App\Filament\NsConseil\Widgets\ProspectionStatutsChart;
use App\Filament\NsConseil\Widgets\RappelsDuJourWidget;
use App\Filament\NsConseil\Widgets\StatsOverviewWidget;
use App\Filament\NsConseil\Widgets\TachesDuJourWidget;
use App\Filament\NsConseil\Widgets\TeamLeaderAlertsWidget;
use App\Filament\NsConseil\Widgets\TeamLeaderPerformanceWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?string $title = 'Tableau de bord AOPIA LIKE Formation';

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('startDate')
                ->label('Du')
                ->default(now()->startOfMonth()),
            DatePicker::make('endDate')
                ->label('Au')
                ->default(now()->endOfMonth()),
        ]);
    }

    public function getWidgets(): array
    {
        $user = auth()->user();

        $widgets = [];

        // Widget tâches du jour pour tous les utilisateurs connectés
        $widgets[] = TachesDuJourWidget::class;

        // Analytics avancés pour tous les utilisateurs connectés
        $widgets[] = AnalyticsChartWidget::class;
        $widgets[] = OpportunitesStatsWidget::class;
        $widgets[] = ConversionFunnelWidget::class;

        // Direction (admin / super_admin)
        if ($user->hasRoleCache('admin') || $user->isSuperAdmin()) {
            $widgets[] = GlobalStatsWidget::class;
            $widgets[] = DirectionKpiWidget::class;
            $widgets[] = StatsOverviewWidget::class;
            $widgets[] = DirectionDerniersPartenairesWidget::class;
            $widgets[] = DirectionRdvParDepartementChart::class;
            $widgets[] = CommercialAgendaWidget::class;
            $widgets[] = MesPartenairesRecentWidget::class;
            $widgets[] = PipelinePartenairesWidget::class;
            $widgets[] = FichesWordRecentesWidget::class;
        }

        // Responsable d'équipe / Superviseur
        if ($user->hasRoleCache('superviseur') || $user->hasRoleCache('admin') || $user->isSuperAdmin()) {
            $widgets[] = TeamLeaderAlertsWidget::class;
            $widgets[] = TeamLeaderPerformanceWidget::class;
        }

        // Téléprospecteur
        if ($user->hasRoleCache('teleprospecteur') || $user->hasRoleCache('superviseur') || $user->hasRoleCache('admin') || $user->isSuperAdmin()) {
            $widgets[] = ActiviteTraitementWidget::class;
            $widgets[] = ProspectionKpiWidget::class;
            $widgets[] = ProspectionStatutsChart::class;
            $widgets[] = RappelsDuJourWidget::class;
            $widgets[] = FichesWordRecentesWidget::class;
        }

        // Commercial
        if ($user->hasRoleCache('commercial') || $user->hasRoleCache('superviseur') || $user->hasRoleCache('admin') || $user->isSuperAdmin()) {
            $widgets[] = CommercialKpiWidget::class;
            $widgets[] = PlanningCommercialWidget::class;
            $widgets[] = DirectionRdvParDepartementChart::class;
            $widgets[] = CommercialAgendaWidget::class;
            $widgets[] = MesPartenairesRecentWidget::class;
            $widgets[] = FichesWordRecentesWidget::class;
        }

        return array_unique($widgets);
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 3,
            '2xl' => 3,
        ];
    }
}
