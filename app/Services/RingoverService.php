<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
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
        $this->baseUrl = config('ringover.base_url');
        $this->apiToken = config('ringover.api_token');
        $this->authScheme = config('ringover.auth_scheme', 'Bearer');
        $this->timeout = config('ringover.timeout');
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

    public function getCalls(array $filters = []): array
    {
        return Cache::remember(
            'ringover_calls_'.md5(serialize($filters)),
            now()->addMinutes(2),
            fn () => $this->client()->get('/calls', $filters)->json('calls', [])
        );
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
