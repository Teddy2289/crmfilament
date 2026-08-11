<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Ticket\StoreTicketRequest;
use App\Http\Requests\Api\V1\Ticket\UpdateTicketRequest;
use App\Http\Resources\Api\V1\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TicketController extends ApiController
{
    /**
     * GET /api/v1/tickets
     *
     * Returns a paginated list of tickets.
     * Filter: statut (TicketStatut)
     *
     * Requirements: 7.1, 7.3
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $query = QueryBuilder::for(Ticket::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
            ])
            ->allowedSorts(['created_at', 'date_creation', 'niveau_priorite', 'statut'])
            ->defaultSort('-date_creation');

        return $this->paginate($query, TicketResource::class);
    }

    /**
     * GET /api/v1/tickets/{ticket}
     *
     * Returns a single ticket. 404 handled globally.
     * Requirements: 7.1
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return $this->success(new TicketResource($ticket));
    }

    /**
     * POST /api/v1/tickets
     *
     * Creates a new ticket and associates it to the authenticated user as operateur (created_by).
     * Requirements: 7.1, 7.4
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        $data = $request->validated();

        // Associate the authenticated user as the operator (created_by) — Requirement 7.4
        $data['operateur_id'] = $request->user()->id;

        $ticket = Ticket::create($data);

        return $this->success(new TicketResource($ticket), 201);
    }

    /**
     * PUT /api/v1/tickets/{ticket}
     *
     * Updates an existing ticket.
     * Requirements: 7.1
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $ticket->update($request->validated());

        return $this->success(new TicketResource($ticket));
    }

    /**
     * DELETE /api/v1/tickets/{ticket}
     *
     * Soft-deletes a ticket.
     * Requirements: 7.1
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(null, 204);
    }
}
