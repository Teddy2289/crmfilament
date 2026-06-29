<?php

namespace App\Livewire;

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
        // Définir les colonnes basées sur les statuts
        $this->columns = [
            ['id' => 'a_prospecter', 'label' => 'À prospecter', 'color' => 'gray', 'icon' => 'heroicon-o-magnifying-glass'],
            ['id' => 'en_cours_prospection', 'label' => 'En cours de prospection', 'color' => 'warning', 'icon' => 'heroicon-o-phone'],
            ['id' => 'rdv_en_cours', 'label' => 'RDV en cours', 'color' => 'info', 'icon' => 'heroicon-o-calendar-days'],
            ['id' => 'signe_accord_cadre', 'label' => 'Signé accord cadre', 'color' => 'success', 'icon' => 'heroicon-o-document-text'],
            ['id' => 'convention_engagement', 'label' => 'Convention d\'engagement', 'color' => 'primary', 'icon' => 'heroicon-o-check-circle'],
            ['id' => 'refus', 'label' => 'Refus', 'color' => 'danger', 'icon' => 'heroicon-o-x-circle'],
            ['id' => 'inactif', 'label' => 'Inactif', 'color' => 'gray', 'icon' => 'heroicon-o-minus-circle'],
        ];

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
        if ($partenaire && array_key_exists($newStatus, Partenaire::STATUTS)) {
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
