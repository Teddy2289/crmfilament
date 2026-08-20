<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\ActiviteTraitementWidget;
use App\Filament\NsConseil\Widgets\Crm\CrmActionRequiredWidget;
use App\Filament\NsConseil\Widgets\Crm\CrmKpiOverviewWidget;
use App\Filament\NsConseil\Widgets\Crm\CrmPipelineWidget;
use App\Filament\NsConseil\Widgets\Crm\CrmRecentActivityWidget;
use App\Filament\NsConseil\Widgets\CommercialAgendaWidget;
use App\Filament\NsConseil\Widgets\DashboardQuickActionsWidget;
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
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?string $title = 'Tableau de bord NS CONSEIL';
    public function getHeading(): string
    {
        $user = auth()->user();
        if ($user?->hasRoleCache('teleprospecteur')) return 'Mon espace de phoning';
        if ($user?->hasRoleCache('commercial')) return 'Mon pilotage commercial';
        if ($user?->hasRoleCache('superviseur')) return 'Pilotage de l’équipe';
        return 'Pilotage NS CONSEIL';
    }
    public function getSubheading(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRoleCache('teleprospecteur')) return 'Vos rappels, votre file et vos actions prioritaires';
        if ($user?->hasRoleCache('commercial')) return 'Vos rendez-vous, partenaires et opportunités à suivre';
        if ($user?->hasRoleCache('superviseur')) return 'Vue opérationnelle de l’activité de votre équipe';
        return 'Vue d’ensemble de l’activité et des priorités CRM';
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('startDate')
                ->label('Du')
                ->default(now()->startOfMonth()),
            DatePicker::make('endDate')
                ->label('Au')
                ->default(now()->endOfMonth()),
            Select::make('userId')
                ->label('Utilisateur')
                ->placeholder('Tous les utilisateurs')
                ->options(fn () => User::query()
                    ->where('actif', true)
                    ->orderBy('nom')
                    ->orderBy('prenom')
                    ->get()
                    ->mapWithKeys(fn (User $user) => [
                        $user->id => trim("{$user->prenom} {$user->nom}"),
                    ])
                    ->all())
                ->searchable()
                ->preload()
                ->visible(fn () => ! auth()->user()?->hasRoleCache('teleprospecteur')),
            Actions::make([
                Action::make('applyDashboardFilters')
                    ->label('Lancer la recherche')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('primary')
                    ->action(fn () => $this->applyDashboardFilters()),
                Action::make('resetDashboardFilters')
                    ->label('Réinitialiser')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn () => $this->resetDashboardFilters()),
            ])->fullWidth(),
        ]);
    }

    public function applyDashboardFilters(): void
    {
        $this->dispatch('dashboard-filters-applied');
    }

    public function resetDashboardFilters(): void
    {
        $this->filters = [
            'startDate' => now()->startOfMonth()->format('Y-m-d'),
            'endDate' => now()->endOfMonth()->format('Y-m-d'),
            'userId' => null,
        ];
        $this->dispatch('dashboard-filters-reset');
    }

    public function getWidgets(): array
    {
        $user = auth()->user();

        $widgets = [];
        $widgets[] = DashboardQuickActionsWidget::class;

        // Socle CRM partagé : ces widgets existaient déjà mais n’étaient pas
        // ajoutés à la page Dashboard.
        if ($user->hasRoleCache('commercial')
            || $user->hasRoleCache('superviseur')
            || $user->hasRoleCache('admin')
            || $user->isSuperAdmin()) {
            $widgets[] = CrmKpiOverviewWidget::class;
            $widgets[] = CrmPipelineWidget::class;
            $widgets[] = CrmActionRequiredWidget::class;
            $widgets[] = CrmRecentActivityWidget::class;
        }

        // Direction (admin / super_admin)
        if ($user->hasRoleCache('admin') || $user->isSuperAdmin()) {
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
