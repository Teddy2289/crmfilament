<?php

namespace App\Services\Phoning;

use App\Enums\ProspectStatut;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\Prospect;
use App\Models\StatutPhoning;

/**
 * Builds and sanitizes the phoning call queue: assembles the default queue
 * from a user's active campaigns, drops entries that became invalid or
 * ineligible since being queued, and prioritizes CSE/rappel entries
 * (workflow v2 rule: overdue or same-day rappels jump to the front).
 */
class PhoningQueueBuilder
{
    public function buildDefaultQueue(int $userId, ?int $campagneId): array
    {
        $queue = [];
        $seen = [];

        $query = CampagnePhoning::active()->forUser($userId);

        // Si une campagne spécifique est demandée, ne charger que celle-là
        if ($campagneId) {
            $query->where('id', $campagneId);
        }

        $campagnes = $query->get();

        foreach ($campagnes as $campagne) {
            foreach ($campagne->getContactsQueue() as $contact) {
                $key = $contact['type'].'_'.$contact['id'];
                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $queue[] = $contact;
                }
            }
        }

        return $queue;
    }

    public function filterValidQueue(array $queue): array
    {
        $itemsByType = collect($queue)
            ->groupBy(fn (array $item): string => $item['type'] ?? '')
            ->map(fn ($items) => $items->pluck('id')->filter()->unique()->values()->all());

        $retireCodes = StatutPhoning::query()
            ->where('model_type', 'prospect')
            ->where('retire_de_file', true)
            ->pluck('code')
            ->all();

        $prospectIds = $itemsByType->get('prospect', []);
        $validProspectIds = $prospectIds === []
            ? []
            : Prospect::query()
                ->whereIn('id', $prospectIds)
                ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

        if ($validProspectIds !== [] && $retireCodes !== []) {
            $prospectsRetires = Appel::query()
                ->where('appelable_type', Prospect::class)
                ->whereIn('appelable_id', $validProspectIds)
                ->whereIn('phoning_status', $retireCodes)
                ->pluck('appelable_id')
                ->all();

            $validProspectIds = array_values(array_diff($validProspectIds, $prospectsRetires));
        }

        $validContactPartenaireIds = ($ids = $itemsByType->get('partenaire', [])) === []
            ? []
            : ContactPartenaire::query()
                ->whereIn('id', $ids)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

        $validClientIds = ($ids = $itemsByType->get('client', [])) === []
            ? []
            : Client::query()
                ->whereIn('id', $ids)
                ->whereNull('deleted_at')
                ->where(fn ($q) => $q->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false))
                ->pluck('id')
                ->all();

        $validIdsByType = [
            'prospect' => array_flip($validProspectIds),
            'partenaire' => array_flip($validContactPartenaireIds),
            'client' => array_flip($validClientIds),
        ];

        return collect($queue)->filter(function ($item) use ($validIdsByType) {
            return match ($item['type']) {
                'prospect', 'partenaire', 'client' => isset($validIdsByType[$item['type']][$item['id']]),
                default => true,
            };
        })->values()->toArray();
    }

    public function prioriserFile(array $queue): array
    {
        if (empty($queue)) {
            return $queue;
        }

        $prospects = Prospect::query()
            ->whereIn('id', collect($queue)
                ->where('type', 'prospect')
                ->pluck('id')
                ->unique()
                ->values()
                ->all())
            ->get(['id', 'statut', 'rappel_planifie_at'])
            ->keyBy('id');

        $prioritaires = [];
        $normaux = [];

        foreach ($queue as $item) {
            if (($item['type'] ?? '') !== 'prospect') {
                $normaux[] = $item;

                continue;
            }

            $prospect = $prospects->get($item['id']);
            if (! $prospect) {
                continue;
            }

            $estPrioritaire = $prospect->rappel_est_en_retard
                || ($prospect->rappel_planifie_at && $prospect->rappel_planifie_at->isToday());

            if ($estPrioritaire) {
                $prioritaires[] = $item;
            } else {
                $normaux[] = $item;
            }
        }

        return array_merge($prioritaires, $normaux);
    }
}
