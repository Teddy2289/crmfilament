<?php

namespace App\Services\Phoning;

use App\Models\CampagnePhoning;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Orchestre la file d'appels phoning : cache, construction, priorisation et
 * réservation — point d'entrée unique pour HasContactQueue et HasQueueManagement.
 *
 * Requirements couverts : 7.1 – 7.10
 */
class PhoningQueueService
{
    public function __construct(
        private PhoningQueueBuilder $builder,
        private PhoningContactSearchService $search
    ) {}

    // ── File d'appels ────────────────────────────────────────────────

    /**
     * Retourne la file d'appels pour un utilisateur donné.
     *
     * - Cache hit  → filterValidQueue() pour nettoyer les entrées obsolètes
     * - Cache miss → buildDefaultQueue() pour construire une nouvelle file
     * - Toujours   → prioriserFile() puis reserveQueueForUser()
     *
     * Toute exception interne est absorbée et retourne [].
     * InvalidArgumentException sur userId/campagneId invalides.
     *
     * @throws InvalidArgumentException
     */
    public function getQueueForUser(int $userId, ?int $campagneId = null): array
    {
        // Req 7.9 : userId doit être > 0
        if ($userId <= 0) {
            throw new InvalidArgumentException("userId doit être supérieur à 0 (reçu : {$userId}).");
        }

        // Req 7.10 : campagneId, si fourni, doit être > 0
        if ($campagneId !== null && $campagneId <= 0) {
            throw new InvalidArgumentException("campagneId doit être supérieur à 0 (reçu : {$campagneId}).");
        }

        try {
            $cacheKey = "phoning_queue_user_{$userId}";
            $cached   = Cache::get($cacheKey);

            // Req 7.2 / 7.3 : cache hit ou miss
            $queue = $cached !== null
                ? $this->builder->filterValidQueue($cached)       // Req 7.2
                : $this->builder->buildDefaultQueue($userId, $campagneId); // Req 7.3

            // Req 7.4 : priorisation puis réservation, dans cet ordre
            $queue = $this->builder->prioriserFile($queue);
            $queue = $this->builder->reserveQueueForUser($userId, $queue);

            return $queue;
        } catch (InvalidArgumentException $e) {
            // Ne pas absorber : l'appelant doit gérer les arguments invalides
            throw $e;
        } catch (\Throwable) {
            // Req 7.5 : toute autre exception interne → [] sans propagation
            return [];
        }
    }

    /**
     * Persiste la file en cache pour une durée de 24 h (86 400 s).
     *
     * Req 7.6
     */
    public function saveQueueForUser(int $userId, array $queue): void
    {
        Cache::put("phoning_queue_user_{$userId}", $queue, 86400);
    }

    /**
     * Supprime la file en cache pour l'utilisateur donné.
     *
     * Req 7.7
     */
    public function clearQueueForUser(int $userId): void
    {
        Cache::forget("phoning_queue_user_{$userId}");
    }

    // ── Recherche de contacts ─────────────────────────────────────────

    /**
     * Recherche textuelle de contacts — délègue à PhoningContactSearchService.
     *
     * Req 7.8 : retourne [] immédiatement si la requête est vide ou blank.
     * Limite les résultats à 50 entrées.
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $query): array
    {
        if (trim($query) === '') {
            return [];
        }

        return array_slice($this->search->search($query), 0, 50);
    }

    /**
     * Recherche par numéro de téléphone — délègue à PhoningContactSearchService.
     *
     * @return list<array<string, mixed>>
     */
    public function findByPhone(string $phone): array
    {
        return $this->search->findByPhone($phone);
    }

    // ── Campagnes ────────────────────────────────────────────────────

    /**
     * Retourne les campagnes actives accessibles à l'utilisateur.
     *
     * @return list<array{id: int, nom: string, type_entite: string, statut: string}>
     */
    public function getCampagnesDisponibles(int $userId): array
    {
        return CampagnePhoning::active()
            ->forUser($userId)
            ->get()
            ->map(fn (CampagnePhoning $c) => [
                'id'          => $c->id,
                'nom'         => $c->nom,
                'type_entite' => $c->type_entite_label,
                'statut'      => $c->statut,
            ])
            ->values()
            ->all();
    }

    /**
     * Retourne les informations résumées d'une campagne, ou null si introuvable.
     *
     * @return array{name: string, count: int, progress: float|int}|null
     */
    public function getCampagneInfo(int $campagneId): ?array
    {
        $c = CampagnePhoning::find($campagneId);

        if ($c === null) {
            return null;
        }

        return [
            'name'     => $c->nom,
            'count'    => $c->countQueueContacts(),
            'progress' => $c->getStats()['progression'],
        ];
    }
}
