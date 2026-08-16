<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RingoverService;
use App\Services\RingoverTagService;
use Illuminate\Http\JsonResponse;

class RingoverDashboardController extends Controller
{
    public function getDashboardData(RingoverService $ringoverService, RingoverTagService $tagService): JsonResponse
    {
        $connexionOk = $ringoverService->testConnection();
        $diagnostic = $tagService->diagnostic();

        return response()->json([
            'connexionOk' => $connexionOk,
            'diagnostic' => $diagnostic,
            'config' => [
                'hasApiToken' => filled(config('ringover.api_token')),
                'hasWebhookSecret' => filled(config('ringover.webhook_secret')),
            ],
            'webhookUrl' => url('/api/ringover/webhook'),
        ]);
    }
}
