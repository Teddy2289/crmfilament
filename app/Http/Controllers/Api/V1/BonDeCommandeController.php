<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\BonDeCommandeResource;
use App\Models\BonDeCommande;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BonDeCommandeController extends ApiController
{
    /**
     * GET /api/v1/bons-de-commande
     *
     * Read-only. Filters: statut, ticket_id.
     * Requirements: 8.2
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BonDeCommande::class);

        $query = QueryBuilder::for(BonDeCommande::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
                AllowedFilter::exact('ticket_id'),
                AllowedFilter::exact('artisan_id'),
            ])
            ->allowedSorts(['numero', 'statut', 'date_intervention_prevue', 'montant_total_ttc', 'created_at'])
            ->defaultSort('-created_at');

        return $this->paginate($query, BonDeCommandeResource::class);
    }

    /**
     * GET /api/v1/bons-de-commande/{bdc}
     *
     * Requirements: 8.2
     */
    public function show(BonDeCommande $bdc): JsonResponse
    {
        $this->authorize('view', $bdc);

        return $this->success(new BonDeCommandeResource($bdc));
    }
}
