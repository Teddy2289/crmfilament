<?php
namespace App\Filament\NsConseil\Widgets;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\StatutPhoning;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;
class CampagnesCallStatusesPieWidget extends ChartWidget
{
    protected static ?string $heading = 'Répartition des statuts d’appels';
    protected static ?int $sort = -6;
    protected int|string|array $columnSpan = ['md' => 1];
    public array $periodeGlobale = [];
    #[On('campagnes-global-filters-updated')]
    public function updatePeriod(array $periode = []): void { $this->periodeGlobale = $periode; $this->updateChartData(); }
    protected function getType(): string { return 'pie'; }
    protected function getData(): array
    {
        $ids = CampagnePhoning::query()->pluck('id');
        [$from, $to] = [$this->periodeGlobale['date_debut'] ?? null, $this->periodeGlobale['date_fin'] ?? null];
        $rows = Appel::query()->whereIn('campagne_id', $ids)->whereNotNull('phoning_status')
            ->when($from, fn ($q) => $q->whereDate('date_heure', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_heure', '<=', $to))
            ->selectRaw('phoning_status, COUNT(*) as total')->groupBy('phoning_status')->orderByDesc('total')->get();
        $labels = StatutPhoning::query()->whereIn('code', $rows->pluck('phoning_status'))->pluck('label', 'code');
        $colors = ['#f59e0b','#3b82f6','#10b981','#ef4444','#8b5cf6','#14b8a6','#f97316','#64748b','#ec4899','#84cc16'];
        return ['labels' => $rows->map(fn ($r) => $labels[$r->phoning_status] ?? $r->phoning_status)->all(), 'datasets' => [['data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(), 'backgroundColor' => array_slice($colors, 0, $rows->count()), 'borderWidth' => 1]]];
    }
}
