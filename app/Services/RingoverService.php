<?php

namespace App\Services;

use App\Exceptions\RingoverApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RingoverService
{
    private string $baseUrl;

    private ?string $apiToken;

    private string $authScheme;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = $this->resolveBaseUrl();
        $this->apiToken = config('ringover.api_token');
        $this->authScheme = config('ringover.auth_scheme', 'Bearer');
        $this->timeout = config('ringover.timeout');
    }

    private function resolveBaseUrl(): string
    {
        if (config('ringover.base_url')) {
            return config('ringover.base_url');
        }

        $region = config('ringover.region', 'europe');
        $urls = config('ringover.base_urls', []);

        return $urls[$region] ?? $urls['europe'] ?? 'https://public-api.ringover.com/v2';
    }

    private function client(): PendingRequest
    {
        $client = Http::timeout($this->timeout)
            ->baseUrl($this->baseUrl)
            ->acceptJson();

        if (filled($this->apiToken)) {
            $client = $client->withHeaders([
                'Authorization' => trim($this->authScheme.' '.$this->apiToken),
            ]);
        }

        return $client;
    }

    private function handleResponse(Response $response): array
    {
        $status = $response->status();

        return match ($status) {
            401 => throw RingoverApiException::unauthorized([
                'url' => $response->effectiveUri(),
                'monitoring_enabled' => config('ringover.monitoring_enabled', false),
            ]),
            429 => throw RingoverApiException::rateLimitExceeded(
                (int) $response->header('Retry-After', 60),
                ['url' => $response->effectiveUri()]
            ),
            402 => throw RingoverApiException::paymentRequired([
                'url' => $response->effectiveUri(),
            ]),
            406 => throw RingoverApiException::notAcceptable(
                $response->body() ?: 'Invalid data',
                ['url' => $response->effectiveUri()]
            ),
            default => $response->json(),
        };
    }

    public function getCalls(array $filters = []): array
    {
        return Cache::remember(
            'ringover_calls_'.md5(serialize($filters)),
            now()->addMinutes(2),
            fn () => $this->client()->get('/calls', $filters)->json('calls', [])
        );
    }

    public function getCallsWithCursor(int $limit = 50, ?string $lastId = null, array $filters = []): array
    {
        $params = array_merge($filters, [
            'per_page' => min($limit, 9000),
        ]);

        if ($lastId) {
            $params['last_id_returned'] = $lastId;
        }

        $response = $this->client()->get('/calls', $params);
        $calls = $response->json('calls', []);

        return [
            'calls' => $calls,
            'last_id' => ! empty($calls) ? end($calls)['id'] ?? null : null,
            'has_more' => count($calls) >= $limit,
        ];
    }

    public function getAllCallsCursor(int $limit = 50, array $filters = []): array
    {
        $allCalls = [];
        $lastId = null;
        $maxIterations = 200; // Protection contre boucle infinie
        $iterations = 0;

        do {
            $result = $this->getCallsWithCursor($limit, $lastId, $filters);
            $allCalls = array_merge($allCalls, $result['calls']);
            $lastId = $result['last_id'];
            $iterations++;

            if ($iterations >= $maxIterations) {
                Log::warning('Ringover cursor pagination max iterations reached', [
                    'iterations' => $iterations,
                    'total_calls' => count($allCalls),
                ]);
                break;
            }
        } while ($result['has_more'] && $lastId);

        return $allCalls;
    }

    public function getAllCalls(int $perPage = 50, int $page = 1): array
    {
        return Cache::remember(
            "ringover_all_calls_{$page}",
            now()->addMinutes(2),
            fn () => $this->client()->get('/calls', [
                'per_page' => $perPage,
                'page' => $page,
                'order' => 'desc',
            ])->json('calls', [])
        );
    }

    public function getCallsToday(): array
    {
        return $this->getCalls([
            'from' => now()->startOfDay()->timestamp,
            'to' => now()->endOfDay()->timestamp,
        ]);
    }

    public function getCall(string $callId): ?array
    {
        try {
            return $this->client()->get("/calls/{$callId}")->json('call');
        } catch (\Exception $e) {
            Log::error('Ringover getCall error', ['id' => $callId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function getUsers(): array
    {
        return Cache::remember('ringover_users', now()->addMinutes(10), function () {
            return $this->client()->get('/users')->json('users', []);
        });
    }

    public function getStats(?int $from = null, ?int $to = null): array
    {
        $allCalls = [];
        $page = 1;

        do {
            $filters = ['per_page' => 50, 'page' => $page, 'order' => 'desc'];
            if ($from) {
                $filters['from'] = $from;
            }
            if ($to) {
                $filters['to'] = $to;
            }

            $calls = Cache::remember(
                'ringover_stats_p'.$page.'_'.md5(serialize($filters)),
                now()->addMinutes(10),
                fn () => $this->client()->get('/calls', $filters)->json('calls', [])
            );

            $allCalls = array_merge($allCalls, $calls);
            $page++;
        } while (count($calls) === 50 && $page <= 20);

        $collection = collect($allCalls);

        $total = $collection->count();
        $entrants = $collection->where('direction', 'inbound')->count();
        $sortants = $collection->where('direction', 'outbound')->count();
        $repondus = $collection->whereIn('status', ['answered', 'done'])->count();
        $manques = $collection->whereIn('status', ['missed_customer', 'missed'])->count();
        $manquesEntrants = $collection->where('direction', 'inbound')
            ->whereIn('status', ['missed_customer', 'missed'])
            ->count();
        $dureeTotale = $collection->sum('duration');

        return [
            'total' => $total,
            'entrants' => $entrants,
            'sortants' => $sortants,
            'manques' => $manques,
            'manques_entrants' => $manquesEntrants,
            'repondus' => $repondus,
            'duree_totale' => $dureeTotale,
            'duree_moyenne' => $total > 0 ? (int) round($dureeTotale / $total) : 0,
            'taux_reponse' => $total > 0 ? round(($repondus / $total) * 100, 1) : 0,
        ];
    }

    public function testConnection(): bool
    {
        try {
            return $this->client()->get('/ping')->successful();
        } catch (\Exception) {
            return false;
        }
    }
}
