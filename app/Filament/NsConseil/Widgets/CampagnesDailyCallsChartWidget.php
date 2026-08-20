<?php
namespace App\Filament\NsConseil\Widgets;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;
class CampagnesDailyCallsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution journalière des appels';
    protected static ?int $sort = -9;
    protected int|string|array $columnSpan = 'full';
    public array $periode = [];
    public ?string $filter = 'all';
    protected function getFilters(): ?array
    {
        return ['all' => 'Tous les types', 'prospects' => 'Prospects', 'partenaires' => 'Partenaires', 'clients' => 'Clients'];
    }
    public function mount(): void { $this->periode = request()->query('tableFilters.periode', []); }
    #[On('campagnes-global-filters-updated')]
    public function updatePeriod(array $periode = []): void { $this->periode = $periode; $this->updateChartData(); }
    protected function getData(): array
    {
        $dateDebut = $this->date($this->periode['date_debut'] ?? null);
        $dateFin = $this->date($this->periode['date_fin'] ?? null);
        $ids = CampagnePhoning::query()
            ->when($this->filter && $this->filter !== 'all', fn ($q) => $q->where('type_entite', $this->filter))
            ->when($dateDebut, fn ($q) => $q->where(fn ($p) => $p->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateDebut->toDateString())))
            ->when($dateFin, fn ($q) => $q->where(fn ($p) => $p->whereNull('date_debut')->orWhereDate('date_debut', '<=', $dateFin->toDateString())))
            ->pluck('id');
        $query = Appel::query()->whereIn('campagne_id', $ids)
            ->when($dateDebut, fn ($q) => $q->whereDate('date_heure', '>=', $dateDebut->toDateString()))
            ->when($dateFin, fn ($q) => $q->whereDate('date_heure', '<=', $dateFin->toDateString()));
        $rows = $query->selectRaw('DATE(date_heure) as jour, COUNT(*) as total')->whereNotNull('date_heure')->groupBy('jour')->orderBy('jour')->pluck('total', 'jour');
        return ['datasets' => [['label' => 'Appels', 'data' => array_values($rows->map(fn ($v) => (int) $v)->all()), 'borderColor' => '#f59e0b', 'backgroundColor' => 'rgba(245,158,11,.18)', 'fill' => true, 'tension' => .25]], 'labels' => array_keys($rows->all())];
    }
    protected function getType(): string { return 'line'; }
    private function date(?string $value): ?Carbon { if (!filled($value)) return null; try { return Carbon::parse($value); } catch (\Throwable) { return null; } }
}
