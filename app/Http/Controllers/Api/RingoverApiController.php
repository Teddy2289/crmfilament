<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RingoverService;
use App\Support\PhoneFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RingoverApiController extends Controller
{
    public function __construct(private RingoverService $ringoverService)
    {
    }

    /**
     * Get filtered Ringover calls for frontend consumption
     */
    public function calls(Request $request): JsonResponse
    {
        try {
            $filters = [
                'limit_count' => max($request->input('per_page', 25) * 4, 100),
                'limit_offset' => 0,
            ];

            if ($direction = $request->input('direction')) {
                $filters['direction'] = $direction;
            }

            $rawCalls = $this->ringoverService->getCalls($filters);
            
            // Apply client-side filters
            $filterNumber = $request->input('filter_number', '');
            $filterAgent = $request->input('filter_agent', '');
            $filterAnswered = $request->input('filter_answered', '');
            $filterHasRecording = $request->input('filter_has_recording', false);

            $filteredCalls = array_filter($rawCalls, function (array $call) use (
                $filterNumber,
                $filterAgent,
                $filterAnswered,
                $filterHasRecording
            ) {
                // Filter by number
                if ($filterNumber) {
                    $needle = preg_replace('/\D+/', '', $filterNumber);
                    $hasMatch = false;
                    foreach (['contact_number', 'from_number', 'to_number'] as $field) {
                        $value = data_get($call, $field);
                        if (!empty($value) && str_contains(preg_replace('/\D+/', '', (string) $value), $needle)) {
                            $hasMatch = true;
                            break;
                        }
                    }
                    if (!$hasMatch) {
                        return false;
                    }
                }

                // Filter by agent
                if ($filterAgent && ((string) data_get($call, 'user.id') !== $filterAgent)) {
                    return false;
                }

                // Filter by answer status
                if ($filterAnswered === 'answered' && !data_get($call, 'is_answered')) {
                    return false;
                }
                if ($filterAnswered === 'missed' && data_get($call, 'is_answered')) {
                    return false;
                }

                // Filter by recording
                if ($filterHasRecording && empty(data_get($call, 'record'))) {
                    return false;
                }

                return true;
            });

            // Format calls for display
            $formattedCalls = array_map(function (array $call) {
                $numero = $call['contact_number'] ?? $call['from_number'] ?? $call['to_number'] ?? '';
                $duree = $call['total_duration'] ?? $call['incall_duration'] ?? 0;
                $min = floor($duree / 60);
                $sec = $duree % 60;

                return [
                    'id' => $call['id'] ?? null,
                    'start_time' => $call['start_time'] ?? null,
                    'direction' => $call['direction'] ?? '',
                    'is_answered' => $call['is_answered'] ?? false,
                    'duration_label' => $min > 0 ? "{$min}min {$sec}s" : "{$sec}s",
                    'agent_name' => $call['user']['concat_name'] ?? '-',
                    'numero' => !empty($numero) ? PhoneFormatter::toNationalFrench($numero) : '-',
                    'record_url' => $call['record'] ?? null,
                ];
            }, array_values($filteredCalls));

            $perPage = $request->input('per_page', 25);
            $page = max(1, $request->input('page', 1));
            $paginatedCalls = array_slice($formattedCalls, ($page - 1) * $perPage, $perPage);
            $totalCount = count($formattedCalls);
            $hasMore = $totalCount > $page * $perPage;

            return response()->json([
                'success' => true,
                'data' => $paginatedCalls,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'has_more' => $hasMore,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('RingoverApiController calls failed', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Impossible de charger les appels Ringover.',
            ], 500);
        }
    }

    /**
     * Get list of Ringover users (agents)
     */
    public function agents(): JsonResponse
    {
        try {
            $users = $this->ringoverService->getUsers();

            $agents = collect($users)
                ->map(function (array $user): array {
                    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['name'] ?? ($user['email'] ?? ''));

                    return [
                        'id' => (string) ($user['id'] ?? $user['user_id'] ?? ''),
                        'name' => $name,
                    ];
                })
                ->where('id')
                ->sortBy('name')
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'data' => $agents,
            ]);
        } catch (\Exception $exception) {
            Log::error('RingoverApiController agents failed', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Impossible de charger les utilisateurs Ringover.',
            ], 500);
        }
    }
}
