<?php

namespace App\Filament\NsConseil\Pages;

use App\Services\CrmDashboardDataService;
use Filament\Pages\Page;

class CrmDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Dashboard CRM';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.ns-conseil.pages.crm-dashboard';
    protected ?string $heading = 'Dashboard CRM';

    public function getViewData(): array
    {
        return ['dashboardData' => $this->getDashboardData()];
    }

    public function getDashboardData(): array
    {
        return app(CrmDashboardDataService::class)->getData(
            request()->string('period')->toString(),
            request()->string('start_date')->toString(),
            request()->string('end_date')->toString(),
        );
    }

    public function getDashboardDataLegacy(): array
    {
        $empty = [
            'kpis' => [],
            'pipeline' => [],
            'activities' => [],
            'actions' => [],
        ];

        try {
            $pipeline = Prospect::query()
                ->selectRaw('COALESCE(statut, \'Sans statut\') as label, COUNT(*) as total')
                ->groupBy('statut')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($row) => ['label' => (string) $row->label, 'total' => (int) $row->total])
                ->values()
                ->all();

            $activities = HistoriqueInteractionUser::query()
                ->with('user:id,nom,prenom')
                ->latest('date_interaction')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'date' => optional($row->date_interaction)->format('d/m/Y H:i'),
                    'user' => (string) optional($row->user)->name,
                    'type' => is_object($row->type_interaction ?? null) ? (($row->type_interaction->label ?? null) ?: ($row->type_interaction->value ?? null) ?: ($row->type_interaction->name ?? 'Activité')) : (string) ($row->type_interaction_label ?? $row->type_interaction ?? 'Activité'),
                    'description' => (string) ($row->description ?? ''),
                ])
                ->values()
                ->all();

            $actions = Prospect::query()
                ->whereIn('statut', ['AC', 'RP'])
                ->latest('updated_at')
                ->limit(8)
                ->get(['id', 'nom', 'statut', 'updated_at'])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->nom ?? 'Prospect sans nom'),
                    'status' => is_object($row->statut ?? null) ? (($row->statut->label ?? null) ?: ($row->statut->value ?? null) ?: ($row->statut->name ?? '—')) : (string) ($row->statut ?? '—'),
                    'updated' => optional($row->updated_at)->format('d/m/Y H:i'),
                    'url' => url('/ns-conseil/prospects/'.$row->id),
                ])
                ->values()
                ->all();

            return [
                    'kpis' => [
                        ['key' => 'prospects', 'label' => 'Prospects actifs', 'value' => Prospect::count(), 'tone' => 'blue'],
                        ['key' => 'clients', 'label' => 'Clients', 'value' => Client::count(), 'tone' => 'emerald'],
                        ['key' => 'partenaires', 'label' => 'Partenaires', 'value' => Partenaire::count(), 'tone' => 'violet'],
                        ['key' => 'opportunites', 'label' => 'Opportunités', 'value' => Opportunite::count(), 'tone' => 'amber'],
                    ],
                    'pipeline' => $pipeline,
                    'activities' => $activities,
                    'actions' => $actions,
                ];
        } catch (\Throwable $exception) {
            Log::error('CrmDashboard JavaScript payload error', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $empty;
        }
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function getFooterWidgets(): array
    {
        return [];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
