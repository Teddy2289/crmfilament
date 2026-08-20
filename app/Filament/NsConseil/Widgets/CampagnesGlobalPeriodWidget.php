<?php
namespace App\Filament\NsConseil\Widgets;
use Carbon\Carbon;
use Filament\Widgets\Widget;
class CampagnesGlobalPeriodWidget extends Widget
{
    protected static string $view = 'filament.ns-conseil.widgets.campagnes-global-period';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = -20;
    public string $periodeGlobale = 'all';
    public function mount(): void { $this->dispatchPeriod(); }
    public function updatedPeriodeGlobale(): void { $this->dispatchPeriod(); }
    private function dispatchPeriod(): void
    {
        [$from, $to] = match ($this->periodeGlobale) {
            'week' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            default => [null, null],
        };
        $this->dispatch('campagnes-global-filters-updated', periode: ['date_debut' => $from, 'date_fin' => $to, 'preset' => $this->periodeGlobale]);
    }
}
