<?php

namespace App\Services\Phoning;

use App\Enums\ProspectStatut;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use Illuminate\Support\Facades\Cache;

/**
 * Builds and sanitizes the phoning call queue: assembles the default queue
 * from a user's active campaigns (interleaved round-robin across campaigns
 * so none gets exhausted before the others are ever reached), drops entries
 * that became invalid or ineligible since being queued, and prioritizes
 * CSE/rappel entries (workflow v2 rule: overdue or same-day rappels jump to
 * the front).
 */
class PhoningQueueBuilder
{
    public function buildDefaultQueue(int $userId, ?int $campagneId): array
    {
        $query = CampagnePhoning::active()->forUser($userId);

        // Si une campagne spécifique est demandée, ne charger que celle-là
        if ($campagneId) {
            $query->where('id', $campagneId);
        }

        $listesParCampagne = $query->get()
            ->map(fn (CampagnePhoning $campagne) => $campagne->getContactsQueue())
            ->filter(fn (array $contacts) => $contacts !== [])
            ->values()
            ->all();

        return $this->interleave($listesParCampagne);
    }

    /**
     * Alterne les contacts des différentes campagnes en tour de rôle (round-robin) :
     * un contact de la campagne 1, un de la campagne 2, etc., pour qu'un
     * téléprospecteur affecté à plusieurs campagnes ne reste jamais bloqué sur
     * la première jusqu'à épuisement avant de voir les suivantes.
     */
    protected function interleave(array $listesParCampagne): array
    {
        if ($listesParCampagne === []) {
            return [];
        }

        $queue = [];
        $seen = [];
        $campagnes = collect($listesParCampagne)
            ->map(function (array $liste, int $index) {
                return [
                    'index' => $index,
                    'items' => $liste,
                ];
            })
            ->values()
            ->all();

        $restant = true;
        $tour = 0;

        while ($restant) {
            $restant = false;
            $ordre = range(0, count($campagnes) - 1);
            shuffle($ordre);

            foreach ($ordre as $campagneIndex) {
                $campagne = $campagnes[$campagneIndex];
                $liste = $campagne['items'];

                if ($liste === []) {
                    continue;
                }

                $cible = $liste[array_rand($liste)];
                $key = $cible['type'].'_'.$cible['id'];

                if (isset($seen[$key])) {
                    $remaining = collect($liste)->reject(fn ($item) => isset($seen[$item['type'].'_'.$item['id']]))->values()->all();
                    if ($remaining === []) {
                        continue;
                    }

                    $cible = $remaining[array_rand($remaining)];
                    $key = $cible['type'].'_'.$cible['id'];
                }

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $queue[] = $cible;
                $restant = true;

                $campagnes[$campagneIndex]['items'] = collect($liste)
                    ->reject(fn ($item) => ($item['type'].'_'.$item['id']) === $key)
                    ->values()
                    ->all();
            }

            $tour++;
            if ($tour > 1000) {
                break;
            }
        }

        return $queue;
    }

    public function reserveQueueForUser(int $userId, array $queue): array
    {
        if ($queue === []) {
            return [];
        }

        $available = [];

        foreach ($queue as $item) {
            $type = $item['type'] ?? null;
            $id = $item['id'] ?? null;

            if (! is_string($type) || $id === null) {
                continue;
            }

            $cacheKey = "phoning_queue_reservation_{$type}_{$id}";

            if (Cache::add($cacheKey, $userId, now()->addMinutes(15))) {
                $available[] = $item;
            }
        }

        return $available;
    }

    public function releaseQueueReservationForUser(int $userId, string $type, int $id): void
    {
        $cacheKey = "phoning_queue_reservation_{$type}_{$id}";
        $ownerId = Cache::get($cacheKey);

        if ((int) $ownerId === $userId) {
            Cache::forget($cacheKey);
        }
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
