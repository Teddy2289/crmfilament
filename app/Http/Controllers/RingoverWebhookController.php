<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRingoverWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RingoverWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->isAuthorized($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        ProcessRingoverWebhook::dispatch($request->all(), source: 'webhook');

        return response()->json([
            'status' => 'queued',
            'message' => 'Webhook received and queued for processing',
        ]);
    }

    private function isAuthorized(Request $request): bool
    {
        $secret = config('ringover.webhook_secret');

        if (blank($secret)) {
            return true;
        }

        $candidate = $request->header('X-Ringover-Webhook-Secret')
            ?? $request->header('X-Webhook-Secret')
            ?? $request->bearerToken()
            ?? $request->query('secret');

        return is_string($candidate) && hash_equals((string) $secret, $candidate);
    }
}
