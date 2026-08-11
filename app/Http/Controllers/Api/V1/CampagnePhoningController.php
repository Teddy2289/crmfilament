<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CampagnePhoning;
use App\Http\Resources\Api\V1\CampagnePhoningResource;
use App\Http\Resources\Api\V1\ProspectResource;
use App\Models\Prospect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class CampagnePhoningController extends ApiController
{
    /**
     * GET /api/v1/campagnes-phoning
     *
     * Returns a paginated list of phoning campaigns.
     * Requirements: 10.1
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CampagnePhoning::class);

        $query = QueryBuilder::for(CampagnePhoning::class)
            ->allowedSorts(['nom', 'statut', 'created_at', 'date_debut', 'date_fin'])
            ->defaultSort('-created_at');

        return $this->paginate($query, CampagnePhoningResource::class);
    }

    /**
     * GET /api/v1/campagnes-phoning/{campagne}
     *
     * Returns a single phoning campaign. 404 handled globally.
     * Requirements: 10.1
     */
    public function show(CampagnePhoning $campagne): JsonResponse
    {
        $this->authorize('view', $campagne);

        return $this->success(new CampagnePhoningResource($campagne));
    }

    /**
     * GET /api/v1/campagnes-phoning/{campagne}/prospects
     *
     * Returns the paginated list of prospects for the campaign,
     * sorted by phoning priority (priorite_phoning ASC, then created_at ASC).
     * Archived campaigns return prospects in read-only mode (same listing, no writes here).
     *
     * Requirements: 10.1, 10.2, 10.5
     */
    public function prospects(Request $request, CampagnePhoning $campagne): JsonResponse
    {
        $this->authorize('view', $campagne);

        $perPage = min((int) $request->input('per_page', 25), 100);

        // Only prospect-type campaigns have a prospects listing
        $query = Prospect::query()
            ->where('campagne_id', $campagne->id)
            ->orderBy('priorite_phoning', 'asc')
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => ProspectResource::collection($paginator->items()),
            'meta' => [
                'total'         => $paginator->total(),
                'per_page'      => $paginator->perPage(),
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'archived'      => $campagne->statut === 'archivee',
            ],
        ]);
    }
}
