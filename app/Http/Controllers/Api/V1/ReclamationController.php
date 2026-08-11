<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatutReclamation;
use App\Http\Requests\Api\V1\Reclamation\StoreReclamationRequest;
use App\Http\Requests\Api\V1\Reclamation\UpdateReclamationRequest;
use App\Http\Resources\Api\V1\ReclamationResource;
use App\Models\ReclamationP8;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReclamationController extends ApiController
{
    /**
     * GET /api/v1/reclamations
     *
     * Requirements: 7.2
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReclamationP8::class);

        $query = QueryBuilder::for(ReclamationP8::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
                AllowedFilter::exact('ticket_id'),
            ])
            ->allowedSorts(['date_ouverture', 'statut', 'date_resolution_cible'])
            ->defaultSort('-date_ouverture');

        return $this->paginate($query, ReclamationResource::class);
    }

    /**
     * GET /api/v1/reclamations/{reclamation}
     *
     * Requirements: 7.2
     */
    public function show(ReclamationP8 $reclamation): JsonResponse
    {
        $this->authorize('view', $reclamation);

        return $this->success(new ReclamationResource($reclamation));
    }

    /**
     * POST /api/v1/reclamations
     *
     * Requirements: 7.2
     */
    public function store(StoreReclamationRequest $request): JsonResponse
    {
        $this->authorize('create', ReclamationP8::class);

        $reclamation = ReclamationP8::create($request->validated());

        return $this->success(new ReclamationResource($reclamation), 201);
    }

    /**
     * PUT /api/v1/reclamations/{reclamation}
     *
     * Status update is restricted by role (Requirement 7.5):
     * only users with 'edit_reclamation' permission may change statut.
     * The ReclamationPolicy::update() enforces this — returns 403 if unauthorized.
     *
     * Requirements: 7.2, 7.5
     */
    public function update(UpdateReclamationRequest $request, ReclamationP8 $reclamation): JsonResponse
    {
        $this->authorize('update', $reclamation);

        $data = $request->validated();

        // If a statut change is requested, use the domain method to enforce
        // transition rules (throws if invalid transition).
        if (isset($data['statut'])) {
            $nouveauStatut = StatutReclamation::from($data['statut']);

            if ($nouveauStatut !== $reclamation->statut) {
                $reclamation->changerStatut($nouveauStatut, $data['notes_resolution'] ?? null);
                unset($data['statut'], $data['notes_resolution']);
            }
        }

        if (! empty($data)) {
            $reclamation->update($data);
        }

        return $this->success(new ReclamationResource($reclamation->fresh()));
    }
}
