<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\CommercialAgendaWidget;
use App\Filament\NsConseil\Widgets\CommercialKpiWidget;
use App\Filament\NsConseil\Widgets\DirectionDerniersPartenairesWidget;
use App\Filament\NsConseil\Widgets\DirectionKpiWidget;
use App\Filament\NsConseil\Widgets\DirectionRdvParDepartementChart;
use App\Filament\NsConseil\Widgets\FichesWordRecentesWidget;
use App\Filament\NsConseil\Widgets\MesPartenairesRecentWidget;
use App\Filament\NsConseil\Widgets\PipelinePartenairesWidget;
use App\Filament\NsConseil\Widgets\ProspectionKpiWidget;
use App\Filament\NsConseil\Widgets\ProspectionStatutsChart;
use App\Filament\NsConseil\Widgets\RappelsDuJourWidget;
use App\Filament\NsConseil\Widgets\StatsOverviewWidget;
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

    protected static ?string $title = 'Tableau de bord NS CONSEIL';

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

        // Direction (admin / super_admin)
        if ($user->hasRoleCache('admin') || $user->isSuperAdmin()) {
            $widgets[] = DirectionKpiWidget::class;
            $widgets[] = StatsOverviewWidget::class;
            $widgets[] = DirectionDerniersPartenairesWidget::class;
            $widgets[] = DirectionRdvParDepartementChart::class;
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
            $widgets[] = ProspectionKpiWidget::class;
            $widgets[] = ProspectionStatutsChart::class;
            $widgets[] = RappelsDuJourWidget::class;
            $widgets[] = FichesWordRecentesWidget::class;
        }

        // Commercial
        if ($user->hasRoleCache('commercial') || $user->hasRoleCache('superviseur') || $user->hasRoleCache('admin') || $user->isSuperAdmin()) {
            $widgets[] = CommercialKpiWidget::class;
            $widgets[] = CommercialAgendaWidget::class;
            $widgets[] = MesPartenairesRecentWidget::class;
            $widgets[] = FichesWordRecentesWidget::class;
        }

        return array_unique($widgets);
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
