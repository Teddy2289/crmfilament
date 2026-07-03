<?php

namespace App\Services;

use App\Exceptions\RingoverApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RingoverCampaignService
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

    private function client()
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

    /**
     * Lister toutes les campagnes
     */
    public function listCampaigns(): array
    {
        try {
            $response = $this->client()->get('/campaigns');
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover list campaigns error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Créer une nouvelle campagne
     */
    public function createCampaign(array $data): array
    {
        try {
            $response = $this->client()->post('/campaigns', $data);
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover create campaign error', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Démarrer une campagne
     */
    public function startCampaign(string $uuid): array
    {
        try {
            $response = $this->client()->post("/campaigns/start/{$uuid}");
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover start campaign error', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            throw $e;
        }
    }

    /**
     * Arrêter une campagne
     */
    public function stopCampaign(string $uuid): array
    {
        try {
            $response = $this->client()->post("/campaigns/stop/{$uuid}");
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover stop campaign error', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            throw $e;
        }
    }

    /**
     * Supprimer une campagne
     */
    public function deleteCampaign(string $uuid): array
    {
        try {
            $response = $this->client()->delete("/campaigns/{$uuid}");
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover delete campaign error', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            throw $e;
        }
    }

    /**
     * Ajouter des numéros à une file d'attente de campagne
     * 
     * @param  array<int, array{number: string, json_csv_infos: string}>  $contacts
     */
    public function addNumbersToCampaign(string $uuid, array $contacts): array
    {
        try {
            $data = [
                'numbers' => array_map(function ($contact) {
                    return [
                        'number' => $contact['number'],
                        'json_csv_infos' => $this->stringifyJson($contact['json_csv_infos'] ?? []),
                    ];
                }, $contacts),
            ];

            $response = $this->client()->post("/campaigns/call/{$uuid}/numbers", $data);
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover add numbers to campaign error', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'contacts_count' => count($contacts),
            ]);
            throw $e;
        }
    }

    /**
     * Obtenir les détails d'une campagne
     */
    public function getCampaign(string $uuid): array
    {
        try {
            $response = $this->client()->get("/campaigns/{$uuid}");
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover get campaign error', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            throw $e;
        }
    }

    /**
     * Obtenir les statistiques d'une campagne
     */
    public function getCampaignStats(string $uuid): array
    {
        try {
            $response = $this->client()->get("/campaigns/{$uuid}/stats");
            
            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error('Ringover get campaign stats error', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            throw $e;
        }
    }

    /**
     * Vérifier si une campagne est prête à démarrer
     */
    public function validateCampaignForStart(string $uuid): bool
    {
        try {
            $campaign = $this->getCampaign($uuid);
            
            $hasNumbers = ! empty($campaign['numbers'] ?? []);
            $hasAgents = ! empty($campaign['agents'] ?? []);
            
            if (! $hasNumbers) {
                Log::warning('Campaign cannot start: no numbers assigned', ['uuid' => $uuid]);
                return false;
            }

            if (! $hasAgents) {
                Log::warning('Campaign cannot start: no agents assigned', ['uuid' => $uuid]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Ringover validate campaign error', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return false;
        }
    }

    /**
     * Convertir un tableau en chaîne JSON stringifiée
     * Format requis par Ringover pour json_csv_infos
     */
    private function stringifyJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Enrichir les données de contact avec des métadonnées métier
     */
    public function enrichContactData(array $contact, array $businessData): array
    {
        return [
            'number' => $contact['number'],
            'json_csv_infos' => $this->stringifyJson(array_merge(
                $contact['json_csv_infos'] ?? [],
                ['social_data' => $businessData]
            )),
        ];
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
}
