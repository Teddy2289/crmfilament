<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Abstract base controller for API v1 endpoints.
 *
 * Provides consistent response envelopes:
 * - Success:  { "data": <any> }
 * - Error:    { "message": "...", "errors": { ... } }
 * - Paginated: { "data": [...], "meta": { total, per_page, current_page, last_page, next_page_url, prev_page_url } }
 */
abstract class ApiController extends Controller
{
    /**
     * Return a successful JSON response wrapped in the `data` envelope.
     *
     * @param  mixed  $data    The payload to include under the "data" key.
     * @param  int    $status  HTTP status code (default 200).
     */
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    /**
     * Return an error JSON response with the standard error envelope.
     *
     * @param  string  $message  Human-readable error description.
     * @param  int     $status   HTTP status code (e.g. 422, 403, 404).
     * @param  array   $errors   Optional field-level error details.
     */
    protected function error(string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Paginate a query and return results with full metadata.
     *
     * Reads `per_page` from the current request (default 25, silently capped at 100).
     * Returns the standard paginated envelope:
     * {
     *   "data": [...],
     *   "meta": {
     *     "total", "per_page", "current_page", "last_page",
     *     "next_page_url", "prev_page_url"
     *   }
     * }
     *
     * @param  QueryBuilder|Builder  $query          The query to paginate.
     * @param  string                $resourceClass  Fully-qualified API Resource class name.
     */
    protected function paginate(QueryBuilder|Builder $query, string $resourceClass): JsonResponse
    {
        $perPage   = min((int) request('per_page', 25), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $resourceClass::collection($paginator->items()),
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
