<?php
namespace App\Filament\NsConseil\Widgets;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\User;
use App\Models\StatutPhoning;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
class CampagnesAgentPerformanceWidget extends Widget
{
    protected static string $view = 'filament.ns-conseil.widgets.campagnes-agent-performance';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = -7;
    public array $rows = [];
    public array $periodeGlobale = [];
    public ?string $dateDebutPersonnalisee = null;
    public ?string $dateFinPersonnalisee = null;
    public function mount(): void { $this->refreshRows(); }
    #[On('campagnes-global-filters-updated')]
    public function updateGlobalPeriod(array $periode = []): void { $this->periodeGlobale = $periode; $this->refreshRows(); }
    public function updatedDateDebutPersonnalisee(): void { $this->refreshRows(); }
    public function updatedDateFinPersonnalisee(): void { $this->refreshRows(); }
    public function reinitialiserDatesPersonnalisees(): void { $this->dateDebutPersonnalisee = null; $this->dateFinPersonnalisee = null; $this->refreshRows(); }
    public function refreshRows(): void
    {
        $ids = CampagnePhoning::query()->pluck('id');
        [$from, $to] = $this->periodBounds();
        $appels = Appel::query()->whereIn('campagne_id', $ids)
            ->when($from, fn ($q) => $q->whereDate('date_heure', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_heure', '<=', $to))
            ->get(['user_id','phoning_agent_id','phoning_status','duree_secondes']);
        $labels = StatutPhoning::query()->pluck('label', 'code');
        $groups = $appels->groupBy(fn ($a) => $a->phoning_agent_id ?: $a->user_id ?: 0);
        $this->rows = $groups->map(function ($items, $agentId) {
            $user = $agentId ? User::find($agentId) : null;
            $total = $items->count();
            $aboutis = $items->whereNotIn('phoning_status', ['MSG','NRP','nrp'])->count();
            $statuts = $items->groupBy(fn ($appel) => $appel->phoning_status ?: 'sans_statut')
                ->map(fn ($statusItems, $code) => ['code' => $code, 'label' => $labels[$code] ?? ($code === 'sans_statut' ? 'Sans statut' : $code), 'total' => $statusItems->count()])
                ->sortByDesc('total')->values()->all();
            return ['agent' => $user ? trim(($user->prenom ?? '').' '.($user->nom ?? '')) : 'Agent non attribué', 'appels' => $total, 'aboutis' => $aboutis, 'taux' => $total ? round($aboutis * 100 / $total, 1) : 0, 'duree' => $items->whereNotNull('duree_secondes')->avg('duree_secondes'), 'statuts' => $statuts];
        })->sortByDesc('appels')->values()->all();
    }
    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'performances-agents-' . now()->format('Ymd-His') . '.csv';
        $rows = $this->rows;

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Agent', 'Appels passés', 'Aboutis', 'Taux d aboutissement', 'Durée moyenne (secondes)', 'Résultats par statut'], ';');
            foreach ($rows as $row) {
                $statuts = collect($row['statuts'] ?? [])
                    ->map(fn (array $statut): string => ($statut['label'] ?? $statut['code'] ?? '') . ' : ' . ($statut['total'] ?? 0))
                    ->implode(' | ');
                fputcsv($handle, [
                    $row['agent'] ?? '',
                    $row['appels'] ?? 0,
                    $row['aboutis'] ?? 0,
                    number_format((float) ($row['taux'] ?? 0), 1, ',', ''),
                    $row['duree'] !== null ? round((float) $row['duree']) : '',
                    $statuts,
                ], ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $filename = 'performances-agents-' . now()->format('Ymd-His') . '.pdf';
        return Pdf::loadView('filament.ns-conseil.widgets.campagnes-agent-performance-pdf', [
            'rows' => $this->rows,
            'dateDebut' => $this->dateDebutPersonnalisee ?: ($this->periodeGlobale['date_debut'] ?? null),
            'dateFin' => $this->dateFinPersonnalisee ?: ($this->periodeGlobale['date_fin'] ?? null),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    private function periodBounds(): array
    {
        if ($this->dateDebutPersonnalisee || $this->dateFinPersonnalisee) {
            return [$this->dateDebutPersonnalisee, $this->dateFinPersonnalisee];
        }
        return [$this->periodeGlobale['date_debut'] ?? null, $this->periodeGlobale['date_fin'] ?? null];
    }
}
