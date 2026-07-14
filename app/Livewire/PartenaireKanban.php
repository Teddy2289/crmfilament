<?php

namespace App\Livewire;

use App\Enums\OrganizationStatus;
use App\Models\Partenaire;
use Livewire\Component;

class PartenaireKanban extends Component
{
    public $columns = [];
    public $partenaires = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Colonnes dérivées de OrganizationStatus (source unique) pour ne
        // jamais afficher une colonne correspondant à un statut inexistant.
        $this->columns = collect(OrganizationStatus::cases())
            ->map(fn (OrganizationStatus $statut) => [
                'id' => $statut->value,
                'label' => $statut->label(),
                'color' => $statut->color(),
                'icon' => $statut->icon(),
            ])
            ->toArray();

        // Charger les partenaires par statut
        $this->partenaires = Partenaire::with(['entreprise', 'entiteCommerciale'])
            ->get()
            ->groupBy('statut')
            ->map(fn ($group, $statut) => [
                'id' => $statut,
                'cards' => $group->map(fn ($partenaire) => [
                    'id' => $partenaire->id,
                    'nom' => $partenaire->nom ?? 'N/A',
                    'entreprise' => $partenaire->entreprise?->nom ?? $partenaire->entreprise ?? 'N/A',
                    'telephone' => $partenaire->telephone ?? 'N/A',
                    'email' => $partenaire->email ?? 'N/A',
                    'entite' => $partenaire->entiteCommerciale?->nom ?? 'N/A',
                    'secteur' => $partenaire->secteur_activite ?? 'N/A',
                    'date_creation' => $partenaire->created_at?->format('d/m/Y') ?? 'N/A',
                ])->toArray(),
            ])
            ->toArray();
    }

    public function updateStatus($partenaireId, $newStatus)
    {
        $partenaire = Partenaire::find($partenaireId);
        if ($partenaire && OrganizationStatus::tryFrom($newStatus) !== null) {
            $partenaire->update([
                'statut' => $newStatus,
                'date_modification_statut' => now(),
            ]);
            $this->loadData();
            $this->dispatch('partenaire-updated');
        }
    }

    public function render()
    {
        return view('livewire.partenaire-kanban');
    }
}
