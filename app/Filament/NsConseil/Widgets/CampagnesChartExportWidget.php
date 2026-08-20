<?php
namespace App\Filament\NsConseil\Widgets;
use Filament\Widgets\Widget;
class CampagnesChartExportWidget extends Widget
{
    protected static string $view = 'filament.ns-conseil.widgets.campagnes-chart-export';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = -8;
}
