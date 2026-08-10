<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Partenaire\StorePartenaireRequest;
use App\Http\Requests\Api\V1\Partenaire\UpdatePartenaireRequest;
use App\Http\Resources\Api\V1\PartenaireResource;
use App\Models\ContactPartenaire;
use App\Models\Partenaire;
use App\Models\RendezVous;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PartenaireController extends ApiController
{
    /**
     * GET /api/v1/partenaires
     *
     * Returns a paginated, filterable list of partenaires.
     * Filters: statut (OrganizationStatus), type (OrganizationType)
     * Default sort: nom ASC
     *
     * Requirements: 5.1, 5.2, 5.3
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Partenaire::class);

        $query = QueryBuilder::for(Partenaire::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['nom', 'statut', 'type', 'created_at', 'updated_at'])
            ->defaultSort('nom');

        return $this->paginate($query, PartenaireResource::class);
    }

    /**
     * GET /api/v1/partenaires/{partenaire}
     *
     * Returns a single partenaire. 404 handled globally by ModelNotFoundException.
     * Requirements: 5.1, 5.6
     */
    public function show(Partenaire $partenaire): JsonResponse
    {
        $this->authorize('view', $partenaire);

        return $this->success(new PartenaireResource($partenaire));
    }

    /**
     * POST /api/v1/partenaires
     *
     * Creates a new partenaire.
     * Requirements: 5.1
     */
    public function store(StorePartenaireRequest $request): JsonResponse
    {
        $this->authorize('create', Partenaire::class);

        $partenaire = Partenaire::create($request->validated());

        return $this->success(new PartenaireResource($partenaire), 201);
    }

    /**
     * PUT /api/v1/partenaires/{partenaire}
     *
     * Updates an existing partenaire.
     * Requirements: 5.1
     */
    public function update(UpdatePartenaireRequest $request, Partenaire $partenaire): JsonResponse
    {
        $this->authorize('update', $partenaire);

        $partenaire->update($request->validated());

        return $this->success(new PartenaireResource($partenaire));
    }

    /**
     * GET /api/v1/partenaires/{partenaire}/contacts
     *
     * Returns a paginated list of ContactPartenaire for the given partenaire.
     * Requirements: 5.4
     */
    public function contacts(Request $request, Partenaire $partenaire): JsonResponse
    {
        $this->authorize('view', $partenaire);

        $perPage   = min((int) $request->input('per_page', 25), 100);
        $paginator = $partenaire->contacts()->paginate($perPage);

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

    /**
     * GET /api/v1/partenaires/{partenaire}/rendez-vous
     *
     * Returns all RendezVous for the given partenaire, sorted by date descending.
     * Requirements: 5.5
     */
    public function rendezVous(Request $request, Partenaire $partenaire): JsonResponse
    {
        $this->authorize('view', $partenaire);

        $perPage   = min((int) $request->input('per_page', 25), 100);
        $paginator = $partenaire->rendezVous()
            ->orderBy('date_debut', 'desc')
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
