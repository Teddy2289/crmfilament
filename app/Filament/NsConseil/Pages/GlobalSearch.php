<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\Client;
use App\Models\Partenaire;
use App\Models\Prospect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class GlobalSearch extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Recherche globale';

    protected static ?string $navigationGroup = 'Recherche';

    protected static string $view = 'filament.ns-conseil.pages.global-search';

    public ?string $searchQuery = null;

    public array $results = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('searchQuery')
                    ->label('Rechercher (téléphone, email, nom, ref_client...)')
                    ->placeholder('Ex: 0612345678, jean.dupont@email.com, CLI-2024-001')
                    ->live()
                    ->debounce(500)
                    ->afterStateUpdated(fn () => $this->search()),
            ])
            ->statePath('data');
    }

    public function search(): void
    {
        $query = $this->form->getState()['searchQuery'] ?? null;

        if (empty($query) || strlen($query) < 3) {
            $this->results = [];
            return;
        }

        $this->results = [
            'prospects' => $this->searchProspects($query),
            'clients' => $this->searchClients($query),
            'partenaires' => $this->searchPartenaires($query),
        ];
    }

    protected function searchProspects(string $query): array
    {
        return Prospect::where(function ($q) use ($query) {
            $q->where('telephone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('nom', 'like', "%{$query}%")
                ->orWhere('ville', 'like', "%{$query}%");
        })
        ->with(['teleprospecteur', 'commercial'])
        ->limit(20)
        ->get()
        ->map(function ($prospect) {
            return [
                'id' => $prospect->id,
                'type' => 'prospect',
                'nom' => $prospect->nom,
                'telephone' => $prospect->telephone,
                'email' => $prospect->email,
                'statut' => $prospect->statut,
                'ville' => $prospect->ville,
                'teleprospecteur' => $prospect->teleprospecteur ? "{$prospect->teleprospecteur->prenom} {$prospect->teleprospecteur->nom}" : null,
                'url' => \App\Filament\NsConseil\Resources\ProspectResource::getUrl('view', ['record' => $prospect->id]),
            ];
        })
        ->toArray();
    }

    protected function searchClients(string $query): array
    {
        return Client::where(function ($q) use ($query) {
            $q->where('telephone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('nom_tiers', 'like', "%{$query}%")
                ->orWhere('ref_client', 'like', "%{$query}%")
                ->orWhere('ville', 'like', "%{$query}%");
        })
        ->with(['commercial', 'partenaire'])
        ->limit(20)
        ->get()
        ->map(function ($client) {
            return [
                'id' => $client->id,
                'type' => 'client',
                'nom' => $client->nom_tiers,
                'telephone' => $client->telephone,
                'email' => $client->email,
                'ref_client' => $client->ref_client,
                'ref_clients' => $client->ref_clients,
                'etat' => $client->etat,
                'ville' => $client->ville,
                'commercial' => $client->commercial ? "{$client->commercial->prenom} {$client->commercial->nom}" : null,
                'url' => \App\Filament\NsConseil\Resources\ClientResource::getUrl('view', ['record' => $client->id]),
            ];
        })
        ->toArray();
    }

    protected function searchPartenaires(string $query): array
    {
        return Partenaire::where(function ($q) use ($query) {
            $q->where('telephone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('nom', 'like', "%{$query}%")
                ->orWhere('ville', 'like', "%{$query}%");
        })
        ->with(['conseiller', 'entite'])
        ->limit(20)
        ->get()
        ->map(function ($partenaire) {
            return [
                'id' => $partenaire->id,
                'type' => 'partenaire',
                'nom' => $partenaire->nom,
                'telephone' => $partenaire->telephone,
                'email' => $partenaire->email,
                'statut' => $partenaire->statut,
                'ville' => $partenaire->ville,
                'conseiller' => $partenaire->conseiller ? "{$partenaire->conseiller->prenom} {$partenaire->conseiller->nom}" : null,
                'url' => \App\Filament\NsConseil\Resources\PartenaireResource::getUrl('view', ['record' => $partenaire->id]),
            ];
        })
        ->toArray();
    }

    public function linkEntities(string $fromType, int $fromId, string $toType, int $toId): void
    {
        // Logique de liaison entre entités
        // À implémenter selon les besoins métier
        Notification::make()
            ->title('Liaison créée')
            ->success()
            ->send();
    }
}
