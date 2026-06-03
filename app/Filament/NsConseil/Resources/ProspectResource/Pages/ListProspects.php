<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\NsConseil\Resources\ProspectResource\Actions\ImportProspectsAction;
use App\Models\Prospect;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    // ─── Vue active ─────────────────────────────────────────────────
    public string $viewMode = 'table';
    protected static string $view = 'filament.ns-conseil.pages.prospects-kanban';

    public function switchView(string $mode): void
    {
        $this->viewMode = $mode;
    }

    // ─── Données injectées dans la vue ──────────────────────────────
    public function getViewData(): array
    {
        $data = parent::getViewData();

        $grouped = [];
        foreach (ProspectStatut::cases() as $statut) {
            $grouped[$statut->value] = [
                'statut'    => $statut,
                'label'     => $statut->label(),
                'color'     => $statut->color(),
                'prospects' => $this->viewMode === 'kanban'
                    ? Prospect::where('statut', $statut->value)
                        ->whereNull('deleted_at')
                        ->orderByDesc('created_at')
                        ->limit(50)
                        ->get()
                    : collect(),
            ];
        }

        $data['kanbanGroups'] = $grouped;
        $data['viewMode']     = $this->viewMode;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('campagne')
                ->label("Campagne d'appels")
                ->icon('heroicon-o-phone-arrow-up-right')
                ->url(PhoningWorkflow::getUrl())
                ->color('success'),

            Actions\CreateAction::make()->label('Nouveau prospect'),

            ImportProspectsAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'tous' => Tab::make('Tous')
                ->badge(Prospect::count()),
        ];

        foreach (ProspectStatut::cases() as $statut) {
            $tabs[$statut->value] = Tab::make($statut->label())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', $statut))
                ->badge(Prospect::where('statut', $statut)->count())
                ->badgeColor($statut->color());
        }

        return $tabs;
    }
}
