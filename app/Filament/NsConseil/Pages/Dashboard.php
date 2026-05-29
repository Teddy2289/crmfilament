<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\StatsOverviewWidget;
use App\Filament\NsConseil\Widgets\PipelinePartenairesWidget;
use App\Filament\NsConseil\Widgets\RappelsDuJourWidget;
use App\Filament\NsConseil\Widgets\MesPartenairesRecentWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;

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
        return [
            StatsOverviewWidget::class,
            RappelsDuJourWidget::class,
            PipelinePartenairesWidget::class,
            MesPartenairesRecentWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
