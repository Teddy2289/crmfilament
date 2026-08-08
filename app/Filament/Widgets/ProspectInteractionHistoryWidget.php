<?php

namespace App\Filament\Widgets;

use App\Models\Appel;
use App\Models\HistoriqueModification;
use Filament\Widgets\Widget;

class ProspectInteractionHistoryWidget extends Widget
{
    protected static string $view = 'filament.widgets.prospect-interaction-history-widget';

    public ?string $modelType = null;

    public ?int $modelId = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Historique des interactions & modifications';

    public function getItems(): array
    {
        if (! $this->modelType || ! $this->modelId) {
            return [];
        }

        $interactions = Appel::with(['user', 'campagne'])
            ->where('appelable_type', $this->modelType)
            ->where('appelable_id', $this->modelId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (Appel $appel): array {
                $detailParts = [];

                if (filled($appel->phoning_status)) {
                    $detailParts[] = 'Statut : ' . $appel->phoning_status;
                }

                if (filled($appel->phoning_result)) {
                    $detailParts[] = $appel->phoning_result;
                }

                if (filled($appel->ringover_status_tag)) {
                    $detailParts[] = 'Ringover : ' . $appel->ringover_status_tag;
                }

                if (filled($appel->phoning_notes)) {
                    $detailParts[] = $appel->phoning_notes;
                } elseif (filled($appel->commentaire)) {
                    $detailParts[] = $appel->commentaire;
                }

                $body = implode(' • ', array_filter($detailParts));

                return [
                    'type' => 'interaction',
                    'type_label' => 'Interaction',
                    'date_sort' => $appel->created_at?->toDateTimeString() ?? '',
                    'date' => $appel->created_at?->format('d/m/Y H:i') ?? '—',
                    'title' => $appel->campagne?->nom ? 'Appel · ' . $appel->campagne->nom : 'Appel',
                    'body' => $body ?: 'Aucun compte-rendu',
                    'meta' => $appel->user?->name ? 'Par ' . $appel->user->name : null,
                ];
            })
            ->toArray();

        $modifications = HistoriqueModification::with('user')
            ->pourModel($this->modelType, $this->modelId)
            ->orderByDesc('date_modification')
            ->limit(10)
            ->get()
            ->map(function (HistoriqueModification $entry): array {
                return [
                    'type' => 'modification',
                    'type_label' => 'Modification',
                    'date_sort' => $entry->date_modification?->toDateTimeString() ?? '',
                    'date' => $entry->date_modification?->format('d/m/Y H:i') ?? '—',
                    'title' => $entry->champ_label ?: 'Enregistrement',
                    'body' => $entry->type_modification_label,
                    'meta' => $entry->user?->name ? 'Par ' . $entry->user->name : 'Par système',
                    'ancienne_valeur' => $entry->ancienne_valeur_formatee,
                    'nouvelle_valeur' => $entry->nouvelle_valeur_formatee,
                ];
            })
            ->toArray();

        $items = array_merge($interactions, $modifications);

        usort($items, fn (array $a, array $b): int => strcmp($b['date_sort'], $a['date_sort']));

        return array_slice($items, 0, 15);
    }
}
