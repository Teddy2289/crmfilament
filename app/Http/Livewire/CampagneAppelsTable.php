<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Appel;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CampagneAppelsTable extends Component
{
    public int $campagneId;

    public string $statut;

    public ?int $teleprospecteurId = null;
    public ?int $agentId = null;
    public ?string $type = null;
    public ?string $status = null;
    public ?string $search = null;
    public ?string $dateFrom = null;
    public ?string $dateUntil = null;

    public function getStatusBadgeClasses($status): string
    {
        if (! $status) {
            return 'bg-gray-50 text-gray-700';
        }

        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        return match (Str::lower($statusValue)) {
            'msg' => 'bg-emerald-50 text-emerald-700',
            'nfp' => 'bg-yellow-50 text-amber-700',
            'fax' => 'bg-slate-50 text-slate-700',
            'supp' => 'bg-blue-50 text-blue-700',
            'rdv' => 'bg-indigo-50 text-indigo-700',
            default => 'bg-gray-50 text-gray-700',
        };
    }

    public function mount(int $campagneId, string $statut): void
    {
        $this->campagneId = $campagneId;
        $this->statut = $statut;
    }

    public function getAppelsProperty()
    {
        return $this->getAppelsQuery()
            ->orderByDesc('date_heure')
            ->get();
    }

    protected function getAppelsQuery(): Builder
    {
        return Appel::with(['appelable', 'user'])
            ->where('campagne_id', $this->campagneId)
            ->when($this->statut, fn(Builder $query) => $query->where('phoning_status', $this->statut))
            ->when($this->teleprospecteurId, fn(Builder $query) => $query->whereHas('appelable', fn(Builder $q) => $q->where('teleprospecteur_id', $this->teleprospecteurId)))
            ->when($this->agentId, fn(Builder $query) => $query->where(function (Builder $query) {
                $query->where('phoning_agent_id', $this->agentId)
                    ->orWhere('user_id', $this->agentId);
            }))
            ->when($this->type, fn(Builder $query) => $query->where('type', $this->type))
            ->when($this->status, fn(Builder $query) => $query->where('phoning_status', $this->status))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('numero_appelant', 'like', '%'.$this->search.'%')
                        ->orWhereHasMorph('appelable', [Prospect::class, ContactPartenaire::class, Client::class], function (Builder $query) {
                            $query->where(function (Builder $query) {
                                $query->where('nom', 'like', '%'.$this->search.'%')
                                    ->orWhere('raison_sociale', 'like', '%'.$this->search.'%')
                                    ->orWhere('telephone', 'like', '%'.$this->search.'%')
                                    ->orWhere('telephone_alt', 'like', '%'.$this->search.'%')
                                    ->orWhere('nom_affichage', 'like', '%'.$this->search.'%')
                                    ->orWhere('nom_complet', 'like', '%'.$this->search.'%')
                                    ->orWhere('telephone_principal', 'like', '%'.$this->search.'%')
                                    ->orWhere('prenom', 'like', '%'.$this->search.'%')
                                    ->orWhere('nom_tiers', 'like', '%'.$this->search.'%')
                                    ->orWhere('entreprise', 'like', '%'.$this->search.'%');
                            });
                        });
                });
            })
            ->when($this->dateFrom, fn(Builder $query) => $query->whereDate('date_heure', '>=', $this->dateFrom))
            ->when($this->dateUntil, fn(Builder $query) => $query->whereDate('date_heure', '<=', $this->dateUntil));
    }

    public function getTeleprospecteursProperty(): array
    {
        return User::whereHas('roles', fn($query) => $query->where('name', 'teleprospecteur'))
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->mapWithKeys(fn(User $user) => [$user->id => trim("{$user->prenom} {$user->nom}")])
            ->toArray();
    }

    public function getAgentOptionsProperty(): array
    {
        return User::where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->mapWithKeys(fn(User $user) => [$user->id => trim("{$user->prenom} {$user->nom}")])
            ->toArray();
    }

    public function getTypeOptionsProperty(): array
    {
        return [
            'Appel' => 'Appel',
            'Permanence' => 'Permanence',
            'Presentation' => 'Présentation',
        ];
    }

    public function getStatusOptionsProperty(): array
    {
        return Appel::query()
            ->where('campagne_id', $this->campagneId)
            ->distinct()
            ->pluck('phoning_status')
            ->filter()
            ->mapWithKeys(fn($value) => [$value => strtoupper($value)])
            ->toArray();
    }

    public function resetFilters(): void
    {
        $this->teleprospecteurId = null;
        $this->agentId = null;
        $this->type = null;
        $this->status = null;
        $this->search = null;
        $this->dateFrom = null;
        $this->dateUntil = null;
    }

    public function downloadCsv()
    {
        $filename = sprintf('appels-%d-%s.csv', $this->campagneId, Str::slug($this->statut));

        return response()->streamDownload(function () {
            echo $this->buildCsv();
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    protected function buildCsv(): string
    {
        $headers = ['Contact', 'Téléphone', 'Date', 'Téléprospecteur', 'Statut'];
        $lines = [implode(';', $headers)];

        foreach ($this->getAppelsQuery()->orderByDesc('date_heure')->get() as $appel) {
            $contact = $appel->appelable?->nom ?? sprintf('Contact #%s', $appel->appelable_id);
            $phone = $appel->appelable?->telephone ?? $appel->numero_appelant;
            $date = optional($appel->date_heure)->format('d/m/Y H:i');
            $telepro = $appel->user?->nom ?? '';
            $status = strtoupper($appel->phoning_status instanceof \BackedEnum ? $appel->phoning_status->value : ($appel->phoning_status ?? ''));

            $row = [
                $this->csvEscape($contact),
                $this->csvEscape($phone),
                $this->csvEscape($date),
                $this->csvEscape($telepro),
                $this->csvEscape($status),
            ];

            $lines[] = implode(';', $row);
        }

        return implode("\n", $lines);
    }

    protected function csvEscape(?string $value): string
    {
        $escaped = str_replace('"', '""', trim((string) $value));
        return sprintf('"%s"', $escaped);
    }

    public function render()
    {
        return view('livewire.campagne-appels-table', [
            'appels' => $this->appels,
        ]);
    }
}
