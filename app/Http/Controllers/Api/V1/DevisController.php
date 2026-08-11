<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\DevisResource;
use App\Models\Devis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DevisController extends ApiController
{
    /**
     * GET /api/v1/devis
     *
     * Read-only for Téléprospecteur and Opérateur N1 roles (enforced by DevisPolicy).
     * Filters: statut (StatutDevis), partenaire_id (via ticket → artisan)
     *
     * Requirements: 8.1, 8.3, 8.4, 8.5
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Devis::class);

        $query = QueryBuilder::for(Devis::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
                AllowedFilter::exact('ticket_id'),
            ])
            ->allowedSorts(['numero', 'statut', 'date_validite', 'total_ttc', 'created_at'])
            ->defaultSort('-created_at');

        return $this->paginate($query, DevisResource::class);
    }

    /**
     * GET /api/v1/devis/{devis}
     *
     * Requirements: 8.1
     */
    public function show(Devis $devis): JsonResponse
    {
        $this->authorize('view', $devis);

        return $this->success(new DevisResource($devis));
    }
}
