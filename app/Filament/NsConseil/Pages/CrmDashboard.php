<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\Crm\CrmPipelineWidget;
use App\Filament\NsConseil\Widgets\Crm\CrmKpiOverviewWidget;
use App\Filament\NsConseil\Widgets\Crm\CrmRecentActivityWidget;
use App\Filament\NsConseil\Widgets\Crm\CrmActionRequiredWidget;
use Filament\Pages\Page;

class CrmDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Dashboard CRM';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.ns-conseil.pages.crm-dashboard';

    protected ?string $heading = '📊 Dashboard CRM';

    public function getHeaderWidgets(): array
    {
        return [
            CrmKpiOverviewWidget::class,
            CrmPipelineWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            CrmRecentActivityWidget::class,
            CrmActionRequiredWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
            '2xl' => 4,
        ];
    }
}
