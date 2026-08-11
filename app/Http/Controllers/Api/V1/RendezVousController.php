<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RendezVousController extends ApiController
{
    /**
     * GET /api/v1/rendez-vous
     *
     * Returns a paginated list of rendez-vous for the authenticated user.
     * Unless the user has a broadened visibility role, only their own rdvs are returned.
     *
     * Filters: date_debut (from, inclusive), date_fin (to, inclusive)
     *
     * Requirements: 9.1, 9.2, 9.3
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RendezVous::class);

        /** @var User $user */
        $user = $request->user();

        $query = QueryBuilder::for(RendezVous::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['date_heure', 'statut', 'type', 'created_at'])
            ->defaultSort('-date_heure');

        // Filter by date range if provided — Requirement 9.3
        if ($request->has('filter.date_debut')) {
            $query->where('date_heure', '>=', $request->input('filter.date_debut'));
        }
        if ($request->has('filter.date_fin')) {
            $query->where('date_heure', '<=', $request->input('filter.date_fin') . ' 23:59:59');
        }

        // Restrict to authenticated user's rdvs unless they have broader access — Requirement 9.2
        $broadRoles = ['super_admin', 'administrateur', 'commercial_manager'];
        $hasBroadAccess = collect($broadRoles)
            ->contains(fn ($role) => $user->hasRole($role));

        if (! $hasBroadAccess) {
            $query->where(function ($q) use ($user) {
                $q->where('commercial_id', $user->id)
                    ->orWhere('teleprospecteur_id', $user->id);
            });
        }

        return $this->paginate($query, \App\Http\Resources\Api\V1\RendezVousResource::class);
    }

    /**
     * GET /api/v1/rendez-vous/{rendezVous}
     *
     * Returns a single rendez-vous. 404 handled globally.
     * Requirements: 9.1
     */
    public function show(RendezVous $rendezVous): JsonResponse
    {
        $this->authorize('view', $rendezVous);

        return $this->success(new \App\Http\Resources\Api\V1\RendezVousResource($rendezVous));
    }

    /**
     * POST /api/v1/rendez-vous
     *
     * Creates a new rendez-vous. Returns 201 with the created resource.
     * Requirements: 9.1, 9.4, 9.5
     */
    public function store(
        \App\Http\Requests\Api\V1\RendezVous\StoreRendezVousRequest $request
    ): JsonResponse {
        $this->authorize('create', RendezVous::class);

        $rdv = RendezVous::create($request->validated());

        return $this->success(new \App\Http\Resources\Api\V1\RendezVousResource($rdv), 201);
    }

    /**
     * PUT /api/v1/rendez-vous/{rendezVous}
     *
     * Updates an existing rendez-vous.
     * Requirements: 9.1, 9.5
     */
    public function update(
        \App\Http\Requests\Api\V1\RendezVous\UpdateRendezVousRequest $request,
        RendezVous $rendezVous
    ): JsonResponse {
        $this->authorize('update', $rendezVous);

        $rendezVous->update($request->validated());

        return $this->success(new \App\Http\Resources\Api\V1\RendezVousResource($rendezVous));
    }
}
