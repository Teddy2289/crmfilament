<?php

namespace App\Filament\SuperAdmin\Resources\UserResource\Pages;

use App\Enums\ProspectStatut;
use App\Filament\SuperAdmin\Resources\UserResource;
use App\Models\Prospect;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    // ── Vue Blade personnalisée ──────────────────────────────────────
    protected static string $view = 'filament.super-admin.resources.user-resource.pages.view-user';

    // ── Propriétés Livewire publiques ────────────────────────────────
    public bool $showAssignPanel = false;

    public array $selectedProspectIds = [];

    public int $assignLimit = 40;

    public string $filterStatut = '';

    public string $filterDepartement = '';

    public string $filterType = '';

    public string $filterSearch = '';

    // ── Exposer les données calculées à la vue via getViewData() ─────
    // Filament appelle cette méthode et merge le résultat avec les données
    // de la vue. C'est l'endroit correct pour exposer des variables calculées.
    protected function getViewData(): array
    {
        $hasProspectRole = $this->computeHasProspectRole();

        return [
            'hasProspectRole' => $hasProspectRole,
            'kpis' => $hasProspectRole ? $this->computeKpis() : [],
            'prospectsAssignes' => $hasProspectRole ? $this->computeProspectsAssignes() : [],
            'nbNonAssignes' => $hasProspectRole ? $this->computeNonAssignesCount() : 0,
            'fieldLabel' => $hasProspectRole ? $this->computeFieldLabel() : '',
            // Données du panneau (seulement si ouvert pour éviter les requêtes inutiles)
            'disponibles' => ($hasProspectRole && $this->showAssignPanel)
                ? $this->computeProspectsNonAssignes()
                : [],
            'departementsDispos' => ($hasProspectRole && $this->showAssignPanel)
                ? $this->computeDepartementsDisponibles()
                : [],
            'typesDispos' => ($hasProspectRole && $this->showAssignPanel)
                ? $this->computeTypesDisponibles()
                : [],
        ];
    }

    // ── Actions du header ────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifier')
                ->icon('heroicon-o-pencil-square'),

            Action::make('assigner_prospects')
                ->label('Assigner des prospects')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn () => $this->computeHasProspectRole())
                ->action(fn () => $this->toggleAssignPanel()),

            Action::make('desassigner_tous')
                ->label('Désassigner tout')
                ->icon('heroicon-o-user-minus')
                ->color('danger')
                ->visible(fn () => $this->computeProspectsAssignesCount() > 0)
                ->requiresConfirmation()
                ->modalHeading('Désassigner tous les prospects ?')
                ->modalDescription('Tous les prospects de cet utilisateur seront désassignés.')
                ->action(function () {
                    $field = $this->computeField();
                    Prospect::where($field, $this->record->id)
                        ->update([$field => null]);

                    Notification::make()
                        ->title('Prospects désassignés')
                        ->warning()
                        ->send();
                }),
        ];
    }

    // ── Actions Livewire (appelées depuis wire:click) ─────────────────

    public function toggleAssignPanel(): void
    {
        $this->showAssignPanel = ! $this->showAssignPanel;
        $this->selectedProspectIds = [];
    }

    public function closeAssignPanel(): void
    {
        $this->showAssignPanel = false;
        $this->selectedProspectIds = [];
        $this->filterStatut = '';
        $this->filterDepartement = '';
        $this->filterType = '';
        $this->filterSearch = '';
    }

    public function toggleProspect(int $id): void
    {
        if (in_array($id, $this->selectedProspectIds)) {
            $this->selectedProspectIds = array_values(
                array_filter($this->selectedProspectIds, fn ($i) => $i !== $id)
            );
        } else {
            if (count($this->selectedProspectIds) >= $this->assignLimit) {
                Notification::make()
                    ->title("Limite de {$this->assignLimit} atteinte")
                    ->warning()
                    ->send();

                return;
            }
            $this->selectedProspectIds[] = $id;
        }
    }

    public function selectAll(): void
    {
        $ids = $this->buildNonAssignesQuery()
            ->limit($this->assignLimit)
            ->pluck('id')
            ->toArray();

        $this->selectedProspectIds = $ids;
    }

    public function clearSelection(): void
    {
        $this->selectedProspectIds = [];
    }

    public function assignerSelection(): void
    {
        if (empty($this->selectedProspectIds)) {
            Notification::make()->title('Aucun prospect sélectionné')->warning()->send();

            return;
        }

        $field = $this->computeField();

        Prospect::whereIn('id', $this->selectedProspectIds)
            ->update([$field => $this->record->id]);

        $nb = count($this->selectedProspectIds);

        Notification::make()
            ->title("{$nb} prospect(s) assigné(s) ✓")
            ->body("Assignés à {$this->record->nom_complet}.")
            ->success()
            ->send();

        $this->closeAssignPanel();
    }

    public function desassignerProspect(int $prospectId): void
    {
        $field = $this->computeField();
        Prospect::where('id', $prospectId)->update([$field => null]);
        Notification::make()->title('Prospect désassigné')->warning()->send();
    }

    // ── Méthodes de calcul privées (prefixe compute) ──────────────────
    // Nommées "compute*" pour éviter toute collision avec les méthodes
    // magiques de Livewire/Filament.

    private function computeField(): string
    {
        return $this->record->hasRole(User::ROLE_TELEPROSPECTEUR)
            ? 'teleprospecteur_id'
            : 'commercial_id';
    }

    private function computeHasProspectRole(): bool
    {
        return $this->record->hasAnyRole([
            User::ROLE_TELEPROSPECTEUR,
            User::ROLE_COMMERCIAL,
        ]);
    }

    private function computeFieldLabel(): string
    {
        return $this->record->hasRole(User::ROLE_TELEPROSPECTEUR)
            ? 'téléprospecteur'
            : 'commercial';
    }

    private function buildNonAssignesQuery()
    {
        $field = $this->computeField();

        $query = Prospect::whereNull($field)
            ->whereNull('deleted_at')
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value]);

        if ($this->filterStatut) {
            $query->where('statut', $this->filterStatut);
        }
        if ($this->filterDepartement) {
            $query->where('departement', 'like', "%{$this->filterDepartement}%");
        }
        if ($this->filterType) {
            $query->where('type_pressenti', $this->filterType);
        }
        if ($this->filterSearch) {
            $query->where(function ($q) {
                $q->where('nom', 'like', "%{$this->filterSearch}%")
                    ->orWhere('ville', 'like', "%{$this->filterSearch}%")
                    ->orWhere('telephone', 'like', "%{$this->filterSearch}%")
                    ->orWhere('siret', 'like', "%{$this->filterSearch}%");
            });
        }

        return $query->orderByRaw("CASE
            WHEN statut = 'rpc'       THEN 1
            WHEN statut = 'rp'        THEN 2
            WHEN statut = 'std_joint' THEN 3
            WHEN statut = 'ac'        THEN 4
            WHEN statut = 'std_nr'    THEN 5
            WHEN statut = 'cse_nr'    THEN 6
            ELSE 7 END");
    }

    private function computeProspectsNonAssignes(): array
    {
        return $this->buildNonAssignesQuery()
            ->limit(200)
            ->get()
            ->map(fn (Prospect $p) => [
                'id' => $p->id,
                'nom' => $p->nom,
                'statut' => $p->statut->value,
                'statut_label' => $p->statut_label,
                'telephone' => $p->telephone,
                'ville' => $p->ville,
                'departement' => $p->departement,
                'type_pressenti' => $p->type_pressenti_label,
                'secteur_activite' => $p->secteur_activite,
                'nb_salaries' => $p->nb_salaries,
                'taux_engagement' => $p->taux_engagement,
                'interlocuteur' => $p->interlocuteur_complet !== 'Non défini'
                    ? $p->interlocuteur_complet : null,
                'description' => $p->description
                    ? \Str::limit(strip_tags($p->description), 60) : null,
                'selected' => in_array($p->id, $this->selectedProspectIds),
            ])
            ->toArray();
    }

    private function computeNonAssignesCount(): int
    {
        $field = $this->computeField();

        return Prospect::whereNull($field)
            ->whereNull('deleted_at')
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->count();
    }

    private function computeProspectsAssignes(): array
    {
        $field = $this->computeField();

        return Prospect::where($field, $this->record->id)
            ->whereNull('deleted_at')
            ->orderByRaw("CASE
                WHEN statut = 'rpc'       THEN 1
                WHEN statut = 'rp'        THEN 2
                WHEN statut = 'std_joint' THEN 3
                WHEN statut = 'ac'        THEN 4
                WHEN statut = 'std_nr'    THEN 5
                WHEN statut = 'cse_nr'    THEN 6
                WHEN statut = 'qf'        THEN 7
                WHEN statut = 'ko'        THEN 8
                ELSE 9 END")
            ->get()
            ->map(fn (Prospect $p) => [
                'id' => $p->id,
                'nom' => $p->nom,
                'statut' => $p->statut->value,
                'statut_label' => $p->statut_label,
                'telephone' => $p->telephone,
                'ville' => $p->ville,
                'departement' => $p->departement,
                'type_pressenti' => $p->type_pressenti_label,
                'taux_engagement' => $p->taux_engagement,
                'rappel_retard' => $p->rappel_est_en_retard,
                'rappel_at' => $p->rappel_planifie_at?->format('d/m/Y H:i'),
                'est_qualifie' => $p->est_qualifie,
                'est_ko' => $p->est_ko,
            ])
            ->toArray();
    }

    private function computeProspectsAssignesCount(): int
    {
        $field = $this->computeField();

        return Prospect::where($field, $this->record->id)
            ->whereNull('deleted_at')
            ->count();
    }

    private function computeKpis(): array
    {
        $field = $this->computeField();
        $base = Prospect::where($field, $this->record->id)->whereNull('deleted_at');
        $userId = $this->record->hasRole(User::ROLE_TELEPROSPECTEUR)
            ? $this->record->id
            : null;

        return [
            'total' => (clone $base)->count(),
            'actifs' => (clone $base)->whereNotIn('statut', [
                ProspectStatut::KO->value,
                ProspectStatut::QF->value,
            ])->count(),
            'qualifies' => (clone $base)->where('statut', ProspectStatut::QF->value)->count(),
            'ko' => (clone $base)->where('statut', ProspectStatut::KO->value)->count(),
            'rp_rpc' => (clone $base)->whereIn('statut', [
                ProspectStatut::RP->value,
                ProspectStatut::RPC->value,
            ])->count(),
            'retards' => (clone $base)
                ->whereNotNull('rappel_planifie_at')
                ->where('rappel_planifie_at', '<', now())
                ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                ->count(),
            'taux_qf' => Prospect::getTauxQualification($userId),
        ];
    }

    private function computeDepartementsDisponibles(): array
    {
        $field = $this->computeField();

        return Prospect::whereNull($field)
            ->whereNull('deleted_at')
            ->whereNotNull('departement')
            ->distinct()
            ->orderBy('departement')
            ->pluck('departement')
            ->toArray();
    }

    private function computeTypesDisponibles(): array
    {
        $field = $this->computeField();

        return Prospect::whereNull($field)
            ->whereNull('deleted_at')
            ->whereNotNull('type_pressenti')
            ->distinct()
            ->pluck('type_pressenti')
            ->toArray();
    }
}
