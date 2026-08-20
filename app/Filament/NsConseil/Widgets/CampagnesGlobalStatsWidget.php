<?php
namespace App\Filament\NsConseil\Widgets;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\StatutPhoning;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;
class CampagnesGlobalStatsWidget extends BaseWidget
{
    protected static ?int $sort = -10;
    protected int|string|array $columnSpan = 'full';
    public array $periode = [];

    public function getHeading(): ?string
    {
        return 'Contacts traités par statut';
    }
    public function mount(): void
    {
        $this->periode = request()->query('tableFilters.periode', []);
    }
    #[On('campagnes-global-filters-updated')]
    public function updatePeriod(array $periode = []): void
    {
        $this->periode = $periode;
        $this->dispatch('$refresh');
    }
    protected function getStats(): array
    {
        $campagnes = CampagnePhoning::query();
        $dateDebut = $this->parseDate($this->periode['date_debut'] ?? null);
        $dateFin = $this->parseDate($this->periode['date_fin'] ?? null);
        $campagnes->when($dateDebut, function ($query) use ($dateDebut): void {
            $query->where(function ($period) use ($dateDebut): void {
                $period->whereNull('date_fin')->orWhereDate('date_fin', '>=', $dateDebut->toDateString());
            });
        })->when($dateFin, function ($query) use ($dateFin): void {
            $query->where(function ($period) use ($dateFin): void {
                $period->whereNull('date_debut')->orWhereDate('date_debut', '<=', $dateFin->toDateString());
            });
        });
        $campagneIds = (clone $campagnes)->pluck('id');
        $total = $campagneIds->count();
        $actives = (clone $campagnes)->where('statut', 'active')->count();
        $planifiees = (clone $campagnes)->where('statut', 'planifiee')->count();
        $terminees = (clone $campagnes)->where('statut', 'terminee')->count();
        // La période doit filtrer les appels eux-mêmes, pas uniquement les campagnes
        // qui chevauchent la période. Sinon une campagne commencée avant la date
        // sélectionnée restitue encore tous ses appels historiques.
        $appels = Appel::query()->whereIn('campagne_id', $campagneIds)
            ->when($dateDebut, fn ($query) => $query->whereDate('date_heure', '>=', $dateDebut->toDateString()))
            ->when($dateFin, fn ($query) => $query->whereDate('date_heure', '<=', $dateFin->toDateString()));
        $appelsTotal = (clone $appels)->count();
        $aboutisTotal = (clone $appels)->whereNotIn('phoning_status', ['MSG', 'NRP', 'nrp'])->count();
        $conversion = $appelsTotal ? round(($aboutisTotal / $appelsTotal) * 100, 1) : 0;
        // Les cartes de statut comptent des contacts uniques, et non des appels.
        // Le type est inclus pour éviter une collision d'identifiant entre prospects et partenaires.
        $topRows = (clone $appels)
            ->selectRaw("phoning_status, COUNT(DISTINCT CONCAT(COALESCE(appelable_type, ''), ':', appelable_id)) as total")
            ->whereNotNull('phoning_status')
            ->groupBy('phoning_status')
            ->orderByDesc('total')
            ->limit(12)
            ->get();
        $labels = StatutPhoning::query()->whereIn('code', $topRows->pluck('phoning_status')->all())->pluck('label', 'code');
        $hasPeriod = $dateDebut || $dateFin;
        $stats = [
            Stat::make('Campagnes au total', $total)->description($actives . ' active(s)')->icon('heroicon-o-rectangle-stack')->color('primary')->url($this->detailsUrl()),
            Stat::make('Campagnes actives', $actives)->description($planifiees . ' planifiée(s)')->icon('heroicon-o-play-circle')->color('success')->url($this->detailsUrl()),
            Stat::make('Campagnes terminées', $terminees)->description($hasPeriod ? 'Période sélectionnée' : 'Historique consolidé')->icon('heroicon-o-check-circle')->color('gray')->url($this->detailsUrl()),
            Stat::make('Appels enregistrés', $appelsTotal)->description($hasPeriod ? 'Qualifications de la période' : 'Résultats consolidés')->icon('heroicon-o-phone')->color('warning')->url($this->detailsUrl()),
            Stat::make('Taux de conversion', number_format($conversion, 1, ',', ' ') . ' %')->description($aboutisTotal . ' aboutis / ' . $appelsTotal . ' appels')->icon('heroicon-o-chart-bar')->color('success')->url($this->detailsUrl()),
        ];
        foreach ($topRows as $row) {
            $stats[] = Stat::make($labels[$row->phoning_status] ?? $row->phoning_status, (int) $row->total)
                ->description($hasPeriod ? 'Contacts uniques · période' : 'Contacts uniques · total')
                ->icon('heroicon-o-user-group')->url($this->detailsUrl((string) $row->phoning_status))
                ->color('info');
        }
        return $stats;
    }
    private function detailsUrl(?string $statut = null): string
    {
        $query = array_filter([
            "statut" => $statut,
            "date_debut" => $this->periode["date_debut"] ?? null,
            "date_fin" => $this->periode["date_fin"] ?? null,
        ], fn ($value) => filled($value));
        return route("ns-conseil.campagnes.contacts-traites", $query);
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (! filled($date)) return null;
        try { return Carbon::parse($date); } catch (\Throwable) { return null; }
    }
}
