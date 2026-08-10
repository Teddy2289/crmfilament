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
     * List tickets with optional statut filter.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $query = QueryBuilder::for(Ticket::class)
            ->allowedFilters([
                AllowedFilter::exact('statut'),
            ])
            ->allowedSorts(['created_at', 'statut', 'niveau_priorite', 'date_creation'])
            ->defaultSort('-created_at');

        return $this->paginate($query, TicketResource::class);
    }

    /**
     * GET /api/v1/tickets/{ticket}
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load('operateur');

        return $this->success(new TicketResource($ticket));
    }

    /**
     * POST /api/v1/tickets
     * Associates the authenticated user as operateur (created_by).
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = Ticket::create(array_merge(
            $request->validated(),
            ['operateur_id' => $request->user()->id]
        ));

        return $this->success(new TicketResource($ticket), 201);
    }

    /**
     * PUT/PATCH /api/v1/tickets/{ticket}
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $ticket->update($request->validated());

        return $this->success(new TicketResource($ticket));
    }
}
