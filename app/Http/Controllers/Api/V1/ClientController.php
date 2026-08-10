<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientController extends ApiController
{
    /**
     * GET /api/v1/clients
     *
     * Returns a paginated list of clients (read-only — source of truth is Dolibarr).
     *
     * Filters:
     *   - partenaire_id : exact match
     *   - search        : partial, case-insensitive on nom_tiers, prenom, ref_client
     *
     * Requirements: 6.1, 6.2, 6.3
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        $query = QueryBuilder::for(Client::class)
            ->allowedFilters([
                AllowedFilter::exact('partenaire_id'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['nom_tiers', 'prenom', 'ref_client', 'created_at'])
            ->defaultSort('nom_tiers');

        return $this->paginate($query, ClientResource::class);
    }

    /**
     * GET /api/v1/clients/{client}
     *
     * Returns a single client. 404 is handled globally by ModelNotFoundException.
     * Requirements: 6.1
     */
    public function show(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        return $this->success(new ClientResource($client));
    }

    /**
     * GET /api/v1/clients/{client}/dossiers-formation
     *
     * Returns a paginated list of formation dossiers for the given client.
     * Requirements: 6.1
     */
    public function dossiersFormation(Request $request, Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $perPage   = min((int) $request->input('per_page', 25), 100);
        $paginator = $client->dossierFormations()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total'         => $paginator->total(),
                'per_page'      => $paginator->perPage(),
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ]);
    }
}
