<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventType;
use App\Http\Requests\Api\V1\Prospect\EnregistrerAppelRequest;
use App\Http\Requests\Api\V1\Prospect\StoreProspectRequest;
use App\Http\Requests\Api\V1\Prospect\UpdateProspectRequest;
use App\Http\Resources\Api\V1\ProspectResource;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProspectController extends ApiController
{
    /**
     * GET /api/v1/prospects
     *
     * Returns a paginated list of prospects.
     * Téléprospecteurs are restricted to their own assigned prospects.
     *
     * Filters : statut, campagne_id, search (partial, case-insensitive on nom/siret/telephone)
     * Sorts   : created_at, nom, statut, updated_at (prefix - for DESC)
     * Includes: teleprospecteur, commercial, campagne
     *
     * Requirements: 4.1, 4.2, 4.3, 4.4
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);

        $query = QueryBuilder::for(Prospect::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
                AllowedFilter::exact('campagne_id'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['created_at', 'nom', 'statut', 'updated_at'])
            ->allowedIncludes(['teleprospecteur', 'commercial', 'campagne'])
            ->defaultSort('-created_at');

        // Scope restriction for téléprospecteurs
        if ($request->user()->isTeleprospecteur()) {
            $query->where('teleprospecteur_id', $request->user()->id);
        }

        return $this->paginate($query, ProspectResource::class);
    }

    /**
     * GET /api/v1/prospects/{id}
     *
     * Returns a single prospect.
     * Requirements: 4.1, 4.5
     */
    public function show(Prospect $prospect): JsonResponse
    {
        $this->authorize('view', $prospect);

        return $this->success(new ProspectResource($prospect));
    }

    /**
     * POST /api/v1/prospects
     *
     * Creates a new prospect.
     * Requirements: 4.1, 4.6
     */
    public function store(StoreProspectRequest $request): JsonResponse
    {
        $this->authorize('create', Prospect::class);

        $prospect = Prospect::create($request->validated());

        return $this->success(new ProspectResource($prospect), 201);
    }

    /**
     * PUT /api/v1/prospects/{id}
     *
     * Updates an existing prospect.
     * Requirements: 4.1, 4.6
     */
    public function update(UpdateProspectRequest $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('update', $prospect);

        $prospect->update($request->validated());

        return $this->success(new ProspectResource($prospect));
    }

    /**
     * DELETE /api/v1/prospects/{id}
     *
     * Soft-deletes a prospect.
     * Requirements: 4.1
     */
    public function destroy(Prospect $prospect): JsonResponse
    {
        $this->authorize('delete', $prospect);

        $prospect->delete();

        return response()->json(null, 204);
    }

    /**
     * POST /api/v1/prospects/{prospect}/appel
     *
     * Records a phoning call for a prospect and updates its statut according
     * to the phoning workflow rules.
     *
     * Validation is handled by EnregistrerAppelRequest:
     *   - statut_phoning_id: required, integer, exists:statut_phonings,id → 422 if missing or invalid (Req 10.4)
     *   - commentaire, duree_secondes, date_heure, enregistrement_audio: optional
     *
     * Requirements: 10.3, 10.4
     */
    public function enregistrerAppel(EnregistrerAppelRequest $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('update', $prospect);

        $validated = $request->validated();

        // statut_phoning_id existence is already guaranteed by EnregistrerAppelRequest
        $statutPhoning = StatutPhoning::findOrFail($validated['statut_phoning_id']);

        // Record the call (polymorphic to Prospect)
        $appel = Appel::create([
            'appelable_type'       => Prospect::class,
            'appelable_id'         => $prospect->id,
            'user_id'              => $request->user()->id,
            'type'                 => EventType::Appel,
            'date_heure'           => $validated['date_heure'] ?? now(),
            'phoning_status'       => $statutPhoning->code,
            'phoning_notes'        => $validated['commentaire'] ?? null,
            'duree_secondes'       => $validated['duree_secondes'] ?? null,
            'commentaire'          => $validated['commentaire'] ?? null,
            'enregistrement_audio' => $validated['enregistrement_audio'] ?? null,
            'campagne_id'          => $prospect->campagne_id,
        ]);

        // Update ProspectStatut according to pipeline_statut mapping if set (Req 10.3)
        if ($statutPhoning->pipeline_statut) {
            $nouveauStatut = \App\Enums\ProspectStatut::tryFrom($statutPhoning->pipeline_statut);
            if ($nouveauStatut) {
                $prospect->update(['statut' => $nouveauStatut]);
                $prospect->refresh();
            }
        }

        return $this->success([
            'appel'    => ['id' => $appel->id, 'date_heure' => $appel->date_heure->toIso8601String()],
            'prospect' => new ProspectResource($prospect),
        ], 201);
    }
}
