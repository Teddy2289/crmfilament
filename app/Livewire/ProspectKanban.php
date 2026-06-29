<?php

namespace App\Livewire;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Livewire\Component;

class ProspectKanban extends Component
{
    public $columns = [];
    public $prospects = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Définir les colonnes basées sur les statuts
        $this->columns = collect(ProspectStatut::cases())
            ->reject(fn ($statut) => in_array($statut, [ProspectStatut::KO, ProspectStatut::QF]))
            ->map(fn ($statut) => [
                'id' => $statut->value,
                'label' => $statut->label(),
                'color' => $statut->color(),
                'icon' => $statut->icon(),
            ])
            ->values()
            ->toArray();

        // Charger les prospects par statut
        $this->prospects = Prospect::with(['entreprise', 'entiteCommerciale'])
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->get()
            ->groupBy('statut')
            ->map(fn ($group, $statut) => [
                'id' => $statut,
                'cards' => $group->map(fn ($prospect) => [
                    'id' => $prospect->id,
                    'nom' => $prospect->nom ?? 'N/A',
                    'prenom' => $prospect->prenom ?? '',
                    'entreprise' => $prospect->entreprise?->nom ?? 'N/A',
                    'telephone' => $prospect->telephone ?? 'N/A',
                    'email' => $prospect->email ?? 'N/A',
                    'entite' => $prospect->entiteCommerciale?->nom ?? 'N/A',
                    'date_creation' => $prospect->created_at?->format('d/m/Y') ?? 'N/A',
                ])->toArray(),
            ])
            ->toArray();
    }

    public function updateStatus($prospectId, $newStatus)
    {
        $prospect = Prospect::find($prospectId);
        if ($prospect) {
            $currentStatut = ProspectStatut::tryFrom($prospect->statut);
            $newStatut = ProspectStatut::tryFrom($newStatus);

            if ($currentStatut && $newStatut && $currentStatut->peutAllerVers($newStatut)) {
                $prospect->update(['statut' => $newStatus]);
                $this->loadData();
                $this->dispatch('prospect-updated');
            }
        }
    }

    public function render()
    {
        return view('livewire.prospect-kanban');
    }
}
