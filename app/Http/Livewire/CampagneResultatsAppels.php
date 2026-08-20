<?php
namespace App\Http\Livewire;

use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;

class CampagneResultatsAppels extends Component
{
    public int $campagneId;
    public string $activeStatut = '';
    public ?int $teleprospecteurId = null;
    public ?string $dateFrom = null;
    public ?string $dateUntil = null;
    public string $search = '';

    public function mount(int $campagneId): void
    {
        $this->campagneId = $campagneId;
        $campagne = CampagnePhoning::find($campagneId);
        $requestedStatut = (string) request()->query('resultats_statut', '');
        $periode = request()->query('tableFilters.periode', request()->query('periode', []));
        $this->dateFrom = is_array($periode) ? ($periode['date_debut'] ?? request()->query('dateFrom')) : request()->query('dateFrom', request()->query('date_debut'));
        $this->dateUntil = is_array($periode) ? ($periode['date_fin'] ?? request()->query('dateUntil')) : request()->query('dateUntil', request()->query('date_fin'));
        $this->search = (string) request()->query('search', '');
        $this->teleprospecteurId = request()->query('teleprospecteurId') ?: null;
        $this->activeStatut = in_array($requestedStatut, $campagne?->statutsUtilises() ?? [], true) ? $requestedStatut : (string) ($campagne?->statutsUtilises()[0] ?? '');
    }

    public function selectStatut(string $statut): void
    {
        $this->activeStatut = $statut;
    }

    public function resetFilters(): void
    {
        $this->teleprospecteurId = null;
        $this->dateFrom = null;
        $this->dateUntil = null;
        $this->search = '';
    }

    public function getCampagneProperty(): ?CampagnePhoning
    {
        return CampagnePhoning::find($this->campagneId);
    }

    public function getStatutsProperty(): array
    {
        return $this->campagne?->statutsUtilises() ?? [];
    }

    public function getTeleprospecteursProperty(): array
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'teleprospecteur'))
            ->where('actif', true)->orderBy('nom')->orderBy('prenom')->get()
            ->mapWithKeys(fn (User $user) => [$user->id => trim("{$user->prenom} {$user->nom}")])->toArray();
    }

    public function getTotalAppelsProperty(): int
    {
        return (int) $this->getAppelsQuery(false)->count();
    }

    public function getStatusCountsProperty(): array
    {
        return $this->getAppelsQuery(false)->whereNotNull("phoning_status")
            ->selectRaw("phoning_status, COUNT(*) as total")->groupBy("phoning_status")
            ->pluck("total", "phoning_status")->map(fn ($value) => (int) $value)->all();
    }

    public function getContactsByStatusProperty(): array
    {
        return $this->getAppelsQuery(false)
            ->whereNotNull('phoning_status')
            ->selectRaw("phoning_status, COUNT(DISTINCT CONCAT(COALESCE(appelable_type, ''), ':', appelable_id)) as total")
            ->groupBy('phoning_status')
            ->pluck('total', 'phoning_status')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    public function getAppelsProperty()
    {
        return $this->getAppelsQuery(true)->with(['appelable', 'user'])->orderByDesc('date_heure')->get();
    }

    protected function getAppelsQuery(bool $withStatus = true): Builder
    {
        return Appel::query()->where('campagne_id', $this->campagneId)
            ->when($withStatus && $this->activeStatut, fn (Builder $q) => $q->where('phoning_status', $this->activeStatut))
            ->when($this->teleprospecteurId, fn (Builder $q) => $q->whereHas('appelable', fn (Builder $sub) => $sub->where('teleprospecteur_id', $this->teleprospecteurId)))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('date_heure', '>=', $this->dateFrom))
            ->when($this->dateUntil, fn (Builder $q) => $q->whereDate('date_heure', '<=', $this->dateUntil))
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $term = '%' . trim($this->search) . '%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('numero_appelant', 'like', $term)
                        ->orWhereHas('appelable', fn (Builder $contact) => $contact->where('nom', 'like', $term)->orWhere('raison_sociale', 'like', $term)->orWhere('telephone', 'like', $term));
                });
            });
    }

    public function downloadCsv()
    {
        $filename = sprintf('appels-%d-%s.csv', $this->campagneId, Str::slug($this->activeStatut ?: 'tous'));
        return response()->streamDownload(function (): void {
            $lines = [implode(';', ['Contact', 'Téléphone', 'Date', 'Téléprospecteur', 'Statut'])];
            foreach ($this->getAppelsQuery()->with(['appelable', 'user'])->orderByDesc('date_heure')->get() as $appel) {
                $contact = $appel->appelable?->nom ?? 'Contact #' . $appel->appelable_id;
                $phone = $appel->appelable?->telephone ?? $appel->numero_appelant ?? '';
                $agent = trim(($appel->user?->prenom ?? '') . ' ' . ($appel->user?->nom ?? ''));
                $values = [$contact, $phone, optional($appel->date_heure)->format('d/m/Y H:i'), $agent, strtoupper((string) $appel->phoning_status)];
                $lines[] = implode(';', array_map(fn ($value) => '"' . str_replace('"', '""', trim((string) $value)) . '"', $values));
            }
            echo implode("\n", $lines);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function render()
    {
        return view('livewire.campagne-resultats-appels', ['appels' => $this->appels]);
    }
}
